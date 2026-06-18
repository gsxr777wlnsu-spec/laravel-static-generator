const fs = require('fs');
const path = require('path');

function parseImportMultiline(lines, startIndex) {
    const marker = lines[startIndex]?.trim() || '';
    if (!marker.startsWith('```')) {
        return {
            value: '',
            nextIndex: startIndex,
        };
    }

    const valueLines = [];
    let index = startIndex + 1;
    while (index < lines.length && lines[index].trim() !== '```') {
        valueLines.push(lines[index]);
        index += 1;
    }

    return {
        value: valueLines.join('\n'),
        nextIndex: index,
    };
}

function parseImportVariableDeclaration(rawLine) {
    const variableMatch = rawLine.match(/^\{([A-Za-z0-9_:-]+)\}\s*=\s*(.*)$/);
    if (!variableMatch) {
        return null;
    }

    return {
        name: variableMatch[1],
        value: variableMatch[2].trim(),
    };
}

function substituteImportVariables(value, variables) {
    let output = String(value ?? '');

    Object.entries(variables || {}).forEach(([name, replacement]) => {
        output = output.replaceAll(`{${name}}`, String(replacement ?? ''));
    });

    return output;
}

function escapeImportVariableForJson(value) {
    return JSON.stringify(String(value ?? '')).slice(1, -1);
}

function substituteImportVariablesForJsonLd(value, variables) {
    let output = String(value ?? '');

    Object.entries(variables || {}).forEach(([name, replacement]) => {
        output = output.replaceAll(`{${name}}`, escapeImportVariableForJson(replacement));
    });

    return output;
}

function collectImportVariableTokens(value) {
    const matches = String(value ?? '').match(/\{([A-Za-z0-9_:-]+)\}/g) || [];
    return matches.map((token) => token.slice(1, -1));
}

function parseCreateSiteImportTemplate(rawText) {
    const lines = String(rawText || '').replaceAll('\r\n', '\n').replaceAll('\r', '\n').split('\n');
    const blocks = [];
    const variables = {};
    const variableDeclarationCounts = {};
    const variableUsage = {};
    const warnings = [];
    let current = null;

    const pushCurrent = () => {
        if (current) {
            blocks.push(current);
        }
    };

    const registerVariableUsage = (value) => {
        collectImportVariableTokens(value).forEach((name) => {
            variableUsage[name] = (variableUsage[name] || 0) + 1;
        });
    };

    for (let index = 0; index < lines.length; index += 1) {
        const rawLine = lines[index];
        const line = rawLine.trim();
        const blockMatch = line.match(/^\[(FORM|FIELD|OPERATION)\]$/);

        if (blockMatch) {
            pushCurrent();
            current = {
                type: blockMatch[1],
                values: {},
            };
            continue;
        }

        if (!current) {
            if (line === '' || line.startsWith('#')) {
                continue;
            }

            const variable = parseImportVariableDeclaration(rawLine);
            if (variable) {
                variableDeclarationCounts[variable.name] = (variableDeclarationCounts[variable.name] || 0) + 1;
                variables[variable.name] = substituteImportVariables(variable.value, variables);
                registerVariableUsage(variable.value);
            }

            continue;
        }

        if (line === '' || line.startsWith('#')) {
            continue;
        }

        const multilineMatch = rawLine.match(/^([A-Za-z0-9_]+):\s*$/);
        if (multilineMatch) {
            const parsed = parseImportMultiline(lines, index + 1);
            registerVariableUsage(parsed.value);
            current.values[multilineMatch[1]] = parsed.value.includes('<script type="application/ld+json">')
                ? substituteImportVariablesForJsonLd(parsed.value, variables)
                : substituteImportVariables(parsed.value, variables);
            index = parsed.nextIndex;
            continue;
        }

        const scalarMatch = rawLine.match(/^([A-Za-z0-9_]+)\s*=\s*(.*)$/);
        if (scalarMatch) {
            registerVariableUsage(scalarMatch[2].trim());
            current.values[scalarMatch[1]] = substituteImportVariables(scalarMatch[2].trim(), variables);
        }
    }

    pushCurrent();

    Object.entries(variableDeclarationCounts).forEach(([name, count]) => {
        if (count > 1) {
            warnings.push(`Variable {${name}} is declared ${count} times. Last value wins.`);
        }
    });

    Object.keys(variableUsage).forEach((name) => {
        if (!Object.prototype.hasOwnProperty.call(variables, name)) {
            warnings.push(`Variable {${name}} is used but not declared at the top of the file.`);
        }
    });

    Object.keys(variables).forEach((name) => {
        if (!variableUsage[name]) {
            warnings.push(`Variable {${name}} is declared but not used anywhere in the import file.`);
        }
    });

    return {
        blocks,
        warnings,
        variables,
    };
}

const templatePath = path.resolve(process.cwd(), 'index-raw-html-import-template-test.txt');
const rawText = fs.readFileSync(templatePath, 'utf8');
const parsed = parseCreateSiteImportTemplate(rawText);

const wantedPaths = [
    'pages.0.locale',
    'pages.0.canonical',
    'pages.0.og_data.head_links.0.href',
    'pages.0.og_data.head_links.1.rel',
    'pages.0.og_data.head_links.1.href',
    'pages.0.og_data.head_links.1.hreflang',
];

const targetFields = parsed.blocks
    .filter((block) => block.type === 'FIELD')
    .map((block) => block.values)
    .filter((values) => wantedPaths.includes(values.path));

const valuesByPath = Object.fromEntries(targetFields.map((field) => [field.path, field.value]));
const cleopatraTail = targetFields.filter((field) => String(field.value).includes('cleopatraslot2.ca'));

console.log(JSON.stringify({
    template: path.basename(templatePath),
    variables: {
        site: parsed.variables.site,
        canonical_url: parsed.variables.canonical_url,
        alt_en_url: parsed.variables.alt_en_url,
        alt_es_url: parsed.variables.alt_es_url,
        html_lang: parsed.variables.html_lang,
    },
    ai_field_edits_preview: valuesByPath,
    cleopatraslot2_tail_found: cleopatraTail.length > 0,
    cleopatraslot2_tail_paths: cleopatraTail.map((field) => field.path),
    warnings: parsed.warnings,
}, null, 2));
