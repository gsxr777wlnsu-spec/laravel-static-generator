import { Editor, mergeAttributes, Node } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';

const editors = new Map();
const modalState = {
    element: null,
    directoryList: null,
    gallery: null,
    uploadInput: null,
    uploadStatus: null,
    onSelect: null,
    directory: 'assets/images/upload',
    currentItem: null,
    directories: [],
};

const MediaImage = Node.create({
    name: 'image',
    group: 'block',
    draggable: true,
    selectable: true,
    inline: false,

    addAttributes() {
        return {
            src: { default: null },
            alt: { default: null },
            title: { default: null },
            width: { default: null },
            height: { default: null },
            class: { default: null },
            loading: { default: null },
            decoding: { default: null },
            srcset: { default: null },
            sizes: { default: null },
            style: { default: null },
            rawImageIndex: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-raw-image-index'),
                renderHTML: (attributes) => {
                    if (!attributes.rawImageIndex) {
                        return {};
                    }

                    return { 'data-raw-image-index': attributes.rawImageIndex };
                },
            },
        };
    },

    parseHTML() {
        return [{ tag: 'img[src]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['img', mergeAttributes(HTMLAttributes)];
    },

    addCommands() {
        return {
            setImage: (attributes) => ({ commands }) => commands.insertContent({ type: this.name, attrs: attributes }),
        };
    },

    addNodeView() {
        return ({ node, HTMLAttributes, editor, getPos }) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'tiptap-image-node';
            wrapper.contentEditable = 'false';

            const image = document.createElement('img');
            image.className = 'tiptap-image-element';
            image.draggable = true;

            const applyAttributes = () => {
                const attributes = mergeAttributes(HTMLAttributes, node.attrs || {});

                [
                    'src',
                    'alt',
                    'title',
                    'width',
                    'height',
                    'class',
                    'loading',
                    'decoding',
                    'srcset',
                    'sizes',
                    'style',
                ].forEach((key) => {
                    const value = attributes[key];

                    if (value === null || value === undefined || value === '') {
                        image.removeAttribute(key);
                        return;
                    }

                    image.setAttribute(key, String(value));
                });

                if (attributes.rawImageIndex === null || attributes.rawImageIndex === undefined || attributes.rawImageIndex === '') {
                    image.removeAttribute('data-raw-image-index');
                } else {
                    image.setAttribute('data-raw-image-index', String(attributes.rawImageIndex));
                }

                image.classList.add('tiptap-image-element');

                if (!image.getAttribute('decoding')) {
                    image.setAttribute('decoding', 'async');
                }

                if (!image.getAttribute('loading')) {
                    image.setAttribute('loading', 'eager');
                }
            };

            applyAttributes();

            image.addEventListener('dblclick', () => {
                if (typeof getPos !== 'function') {
                    return;
                }

                editor.chain().focus().setNodeSelection(getPos()).run();

                openModal((item) => {
                    const attrs = {
                        src: item.url,
                        alt: item.alt || '',
                        title: item.title || item.alt || '',
                    };
                    const state = getStateByEditor(editor);

                    patchRawImageAttributes(state, node.attrs.rawImageIndex, attrs);

                    editor
                        .chain()
                        .focus()
                        .setNodeSelection(getPos())
                        .updateAttributes('image', attrs)
                        .run();
                }, {
                    directory: getAssetDirectoryFromUrl(node.attrs.src || ''),
                    currentItem: {
                        url: node.attrs.src || '',
                        alt: node.attrs.alt || '',
                        title: node.attrs.title || '',
                        path: (() => {
                            const src = node.attrs.src || '';
                            const match = src.match(/\/assets\/(.+)$/i);
                            return match ? `assets/${match[1]}` : src;
                        })(),
                    },
                });
            });

            image.addEventListener('click', () => {
                if (typeof getPos !== 'function') {
                    return;
                }

                editor.chain().focus().setNodeSelection(getPos()).run();
            });

            wrapper.appendChild(image);

            return {
                dom: wrapper,
                update: (updatedNode) => {
                    if (updatedNode.type.name !== this.name) {
                        return false;
                    }

                    node = updatedNode;
                    applyAttributes();
                    return true;
                },
            };
        };
    },
});

function getConfig() {
    const node = document.getElementById('page-editor-config');

    return {
        siteId: Number(node?.dataset.siteId || 0),
    };
}

function getSectionJson(container) {
    const textarea = container.querySelector('.section-content');

    if (!textarea) {
        return {};
    }

    try {
        return JSON.parse(textarea.value) || {};
    } catch (error) {
        return {};
    }
}

function setSectionJson(container, content) {
    const textarea = container.querySelector('.section-content');

    if (!textarea) {
        return;
    }

    textarea.value = JSON.stringify(content, null, 4);
}

function isComplexRawHtml(rawHtml) {
    if (typeof rawHtml !== 'string') {
        return false;
    }

    return (
        /<section\b/i.test(rawHtml) && /<(?:div|header|nav|article|aside)\b/i.test(rawHtml)
    ) || (
        /class=(["'])[^"']*__[a-z0-9_-]+[^"']*\1/i.test(rawHtml) && /<(?:div|header|nav|ul|footer)\b/i.test(rawHtml)
    );
}

function shouldPreserveRawHtml(container, rawHtml) {
    return container?.dataset.preserveRawHtml === 'true' || isComplexRawHtml(rawHtml);
}

function annotateRawImages(rawHtml) {
    if (typeof rawHtml !== 'string' || rawHtml.trim() === '') {
        return rawHtml;
    }

    const template = document.createElement('template');
    template.innerHTML = rawHtml;

    template.content.querySelectorAll('img').forEach((image, index) => {
        image.setAttribute('data-raw-image-index', String(index));
    });

    return template.innerHTML;
}

function getEditorHtml(editor) {
    let html = editor.getHTML();
    html = html.replace(/<p><\/p>$/i, '');

    if (typeof window.toStorageAssetUrls === 'function') {
        html = window.toStorageAssetUrls(html);
    }

    return html
        .replace(/\s*bis_[a-z]+="[^"]*"/gi, '')
        .replace(/\s*data-raw-image-index=(["'])[^"']*\1/gi, '');
}

function getDisplayHtml(rawHtml) {
    if (typeof window.toEditorAssetUrls === 'function') {
        return window.toEditorAssetUrls(rawHtml);
    }

    return rawHtml;
}

function getAssetDirectoryFromUrl(url) {
    if (typeof url !== 'string' || url.trim() === '') {
        return 'assets/images/upload';
    }

    const normalized = url.trim();
    const match = normalized.match(/\/assets\/(.+)$/i);
    if (!match) {
        return 'assets/images/upload';
    }

    const relativePath = `assets/${match[1]}`.replace(/\/+/g, '/');
    const slashIndex = relativePath.lastIndexOf('/');

    if (slashIndex === -1) {
        return 'assets/images/upload';
    }

    return relativePath.slice(0, slashIndex);
}

function getStateByEditor(editor) {
    for (const state of editors.values()) {
        if (state.editor === editor) {
            return state;
        }
    }

    return null;
}

function setRawHtmlForState(container, state, html) {
    if (!state) {
        return;
    }

    const normalizedHtml = typeof window.toStorageAssetUrls === 'function'
        ? window.toStorageAssetUrls(html)
        : html;

    state.originalRawHtml = normalizedHtml;

    const content = getSectionJson(container);
    content.raw_html = normalizedHtml;
    if (normalizedHtml.trim() !== '') {
        content.render_mode = 'raw_html';
    }

    setSectionJson(container, content);

    if (state.codeTextarea) {
        state.codeTextarea.value = normalizedHtml;
    }
}

function patchRawImageAttributes(state, rawImageIndex, attrs) {
    if (!state?.preserveRawHtml || rawImageIndex === null || rawImageIndex === undefined || rawImageIndex === '') {
        return false;
    }

    const index = Number(rawImageIndex);
    if (!Number.isInteger(index) || index < 0) {
        return false;
    }

    const template = document.createElement('template');
    template.innerHTML = state.originalRawHtml || state.codeTextarea?.value || '';

    const image = template.content.querySelectorAll('img')[index];
    if (!image) {
        return false;
    }

    Object.entries(attrs).forEach(([attr, value]) => {
        if (value === null || value === undefined || value === '') {
            image.removeAttribute(attr);
            return;
        }

        image.setAttribute(attr, String(value));
    });

    setRawHtmlForState(state.container, state, template.innerHTML);
    return true;
}

function extractRawBackgroundTargets(rawHtml) {
    if (typeof rawHtml !== 'string' || rawHtml.trim() === '') {
        return [];
    }

    const template = document.createElement('template');
    template.innerHTML = rawHtml;

    const targets = [];

    template.content.querySelectorAll('style').forEach((styleNode, styleIndex) => {
        const cssText = styleNode.textContent || '';
        const blockRegex = /([^{}]+)\{([^{}]*)\}/gs;
        let blockMatch;
        let blockIndex = 0;

        while ((blockMatch = blockRegex.exec(cssText)) !== null) {
            const selector = String(blockMatch[1] || '').trim();
            const body = String(blockMatch[2] || '');
            const declarationRegex = /(background-image)\s*:\s*([^;]+)\s*;?/gi;
            let declarationMatch;
            let declarationIndex = 0;

            while ((declarationMatch = declarationRegex.exec(body)) !== null) {
                const property = String(declarationMatch[1] || 'background-image').toLowerCase();
                const value = String(declarationMatch[2] || '');
                const urlRegex = /url\((["']?)([^)"']+)\1\)/gi;
                let urlMatch;
                let urlIndex = 0;

                while ((urlMatch = urlRegex.exec(value)) !== null) {
                    const url = String(urlMatch[2] || '').trim();
                    if (url !== '') {
                        targets.push({
                            key: `style:${styleIndex}:${blockIndex}:${declarationIndex}:${urlIndex}`,
                            styleIndex,
                            blockIndex,
                            declarationIndex,
                            urlIndex,
                            selector,
                            property,
                            url,
                            label: `${selector} ${property}`,
                        });
                    }

                    urlIndex += 1;
                }

                declarationIndex += 1;
            }

            blockIndex += 1;
        }
    });

    return targets;
}

function replaceRawBackgroundTarget(rawHtml, targetKey, nextUrl) {
    if (typeof rawHtml !== 'string' || rawHtml.trim() === '') {
        return rawHtml;
    }

    const [stylePrefix, styleIndexText, blockIndexText, declarationIndexText, urlIndexText] = String(targetKey || '').split(':');
    if (stylePrefix !== 'style') {
        return rawHtml;
    }

    const styleIndex = Number(styleIndexText);
    const blockIndex = Number(blockIndexText);
    const declarationIndex = Number(declarationIndexText);
    const urlIndex = Number(urlIndexText);

    if (![styleIndex, blockIndex, declarationIndex, urlIndex].every(Number.isInteger)) {
        return rawHtml;
    }

    const template = document.createElement('template');
    template.innerHTML = rawHtml;
    const styleNode = template.content.querySelectorAll('style')[styleIndex];
    if (!styleNode) {
        return rawHtml;
    }

    const cssText = styleNode.textContent || '';
    const blocks = [...cssText.matchAll(/([^{}]+)\{([^{}]*)\}/gs)];
    if (!blocks[blockIndex]) {
        return rawHtml;
    }

    const blockMatch = blocks[blockIndex];
    const blockText = blockMatch[0];
    const blockOffset = blockMatch.index ?? -1;
    if (blockOffset < 0) {
        return rawHtml;
    }

    const declarations = [...blockText.matchAll(/(background-image)\s*:\s*([^;]+)\s*;?/gi)];
    if (!declarations[declarationIndex]) {
        return rawHtml;
    }

    const declarationMatch = declarations[declarationIndex];
    const declarationValue = declarationMatch[2] || '';
    const declarationOffset = declarationMatch.index ?? -1;
    const declarationPrefixLength = declarationMatch[0].indexOf(declarationValue);

    if (declarationOffset < 0 || declarationPrefixLength < 0) {
        return rawHtml;
    }

    const urls = [...declarationValue.matchAll(/url\((["']?)([^)"']+)\1\)/gi)];
    if (!urls[urlIndex]) {
        return rawHtml;
    }

    const urlMatch = urls[urlIndex];
    const currentUrl = urlMatch[2] || '';
    const fullUrlOffset = urlMatch.index ?? -1;
    const currentUrlOffset = urlMatch[0].indexOf(currentUrl);

    if (fullUrlOffset < 0 || currentUrlOffset < 0) {
        return rawHtml;
    }

    const absoluteStart = blockOffset + declarationOffset + declarationPrefixLength + fullUrlOffset + currentUrlOffset;
    const absoluteEnd = absoluteStart + currentUrl.length;
    const nextCssText = `${cssText.slice(0, absoluteStart)}${nextUrl}${cssText.slice(absoluteEnd)}`;

    styleNode.textContent = nextCssText;
    return template.innerHTML;
}

function sectionModuleKey(container) {
    const content = getSectionJson(container);
    return String(content.module || content.module_key || '').trim();
}

function syntheticBackgroundTargets(moduleKey) {
    if (moduleKey === 'hero') {
        return [{
            key: 'synthetic:hero',
            selector: '.hero',
            property: 'background-image',
            url: '/assets/images/hero/hero-background.webp',
            label: '.hero background-image',
            synthetic: true,
        }];
    }

    if (moduleKey === 'conclusion') {
        return [{
            key: 'synthetic:conclusion',
            selector: '.conclusion__card::before',
            property: 'background-image',
            url: '/assets/images/hero/conclusion-background.webp',
            label: '.conclusion__card::before background-image',
            synthetic: true,
        }];
    }

    return [];
}

function ensureBackgroundStyleOverride(rawHtml, target, nextUrl) {
    if (target?.key && !String(target.key).startsWith('synthetic:')) {
        return replaceRawBackgroundTarget(rawHtml, target.key, nextUrl);
    }

    if (typeof rawHtml !== 'string' || rawHtml.trim() === '') {
        return rawHtml;
    }

    const selector = String(target?.selector || '').trim();
    if (selector === '') {
        return rawHtml;
    }

    const propertyValue = selector === '.conclusion__card::before'
        ? `linear-gradient(270deg, #121629 0%, rgba(18, 22, 41, 0) 100%), url(${nextUrl})`
        : `url(${nextUrl})`;

    const cssRule = `${selector} { background-image: ${propertyValue}; }`;
    const template = document.createElement('template');
    template.innerHTML = rawHtml;

    const styleNodes = Array.from(template.content.querySelectorAll('style'));
    const matchingStyleNode = styleNodes.find((node) => (node.textContent || '').includes(selector));

    if (matchingStyleNode) {
        const cssText = matchingStyleNode.textContent || '';
        const selectorRegex = new RegExp(`${selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*\\{[^{}]*\\}`, 's');
        if (selectorRegex.test(cssText)) {
            matchingStyleNode.textContent = cssText.replace(selectorRegex, cssRule);
        } else {
            matchingStyleNode.textContent = `${cssText}\n${cssRule}`;
        }

        return template.innerHTML;
    }

    const firstElement = template.content.firstElementChild;
    if (!firstElement) {
        return rawHtml;
    }

    const styleNode = document.createElement('style');
    styleNode.textContent = cssRule;
    firstElement.prepend(styleNode);

    return template.innerHTML;
}

function generatedBackgroundOverrideConfig(target) {
    const selector = String(target?.selector || '').trim();

    if (selector === '.hero') {
        return {
            targetPath: 'assets/images/hero/hero-background.webp',
            replacementUrl: '/assets/images/hero/hero-background.webp',
            buttonLabel: 'Upload Hero Background',
        };
    }

    if (selector === '.conclusion__card::before') {
        return {
            targetPath: 'assets/images/hero/conclusion-background.webp',
            replacementUrl: '/assets/images/hero/conclusion-background.webp',
            buttonLabel: 'Upload Conclusion Background',
        };
    }

    return null;
}

async function uploadGeneratedBackgroundOverride(sectionId, targetPath, file) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('target_path', targetPath);

    const response = await fetch(`/api/sections/${sectionId}/generated-background-override`, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: formData,
    });

    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || `Request failed with status ${response.status}`);
        throw new Error(message);
    }

    return result;
}

function updateBackgroundSidebar(container) {
    const state = editors.get(container);
    const sidebar = state?.backgroundSidebar;
    const fields = state?.backgroundFields;

    if (!state || !sidebar || !fields) {
        return;
    }

    const rawHtml = state.originalRawHtml || state.codeTextarea?.value || '';
    const moduleKey = sectionModuleKey(container);
    const extractedTargets = extractRawBackgroundTargets(rawHtml);
    const targets = extractedTargets.length > 0
        ? extractedTargets
        : syntheticBackgroundTargets(moduleKey);

    fields.innerHTML = '';
    sidebar.classList.toggle('hidden', targets.length === 0);

    targets.forEach((target) => {
        const wrapper = document.createElement('div');
        const overrideConfig = generatedBackgroundOverrideConfig(target);
        wrapper.innerHTML = `
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">${target.label}</label>
            <input type="text" class="tiptap-background-input mt-1 block w-full rounded-md shadow-sm sm:text-sm" value="${target.url.replace(/"/g, '&quot;')}">
            ${overrideConfig ? `
                <label class="mt-2 inline-flex cursor-pointer items-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                    ${overrideConfig.buttonLabel}
                    <input type="file" accept="image/webp" class="tiptap-background-upload hidden">
                </label>
                <div class="tiptap-background-upload-status mt-2 text-xs text-gray-500 dark:text-gray-400 hidden"></div>
            ` : ''}
        `;

        const input = wrapper.querySelector('input');
        input?.addEventListener('input', () => {
            const nextHtml = ensureBackgroundStyleOverride(state.originalRawHtml || state.codeTextarea?.value || '', target, input.value);
            if (nextHtml === (state.originalRawHtml || state.codeTextarea?.value || '')) {
                return;
            }

            setRawHtmlForState(container, state, nextHtml);
            state.rawTextNodeMap = buildRawTextNodeMap(state.originalRawHtml, state.editorTextSnapshot || []);
            updateBackgroundSidebar(container);
        });

        const uploadInput = wrapper.querySelector('.tiptap-background-upload');
        const uploadStatus = wrapper.querySelector('.tiptap-background-upload-status');
        uploadInput?.addEventListener('change', async () => {
            const file = uploadInput.files?.[0];
            if (!file || !overrideConfig) {
                return;
            }

            if (uploadStatus) {
                uploadStatus.textContent = 'Uploading...';
                uploadStatus.classList.remove('hidden');
            }

            try {
                await uploadGeneratedBackgroundOverride(
                    Number(container.dataset.sectionId || 0),
                    overrideConfig.targetPath,
                    file
                );

                const nextHtml = ensureBackgroundStyleOverride(
                    state.originalRawHtml || state.codeTextarea?.value || '',
                    target,
                    overrideConfig.replacementUrl
                );
                setRawHtmlForState(container, state, nextHtml);
                state.rawTextNodeMap = buildRawTextNodeMap(state.originalRawHtml, state.editorTextSnapshot || []);

                if (typeof window.savePageSection === 'function') {
                    await window.savePageSection(container.dataset.sectionId, container, { silent: true });
                }

                if (typeof window.renderPageEditStatus === 'function') {
                    window.renderPageEditStatus('Generated background override uploaded successfully.', 'success');
                }

                updateBackgroundSidebar(container);
            } catch (error) {
                if (typeof window.renderPageEditStatus === 'function') {
                    window.renderPageEditStatus(`Background upload failed: ${error.message}`, 'error');
                }
            } finally {
                if (uploadStatus) {
                    uploadStatus.textContent = '';
                    uploadStatus.classList.add('hidden');
                }
                uploadInput.value = '';
            }
        });

        fields.appendChild(wrapper);
    });
}

function collectEditorTextNodes(editor) {
    const texts = [];

    editor.state.doc.descendants((node) => {
        if (node.isText && typeof node.text === 'string' && node.text.trim() !== '') {
            texts.push(node.text);
        }
    });

    return texts;
}

function collectRawTextNodes(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode: (node) => {
            const parent = node.parentElement;
            if (!parent || ['SCRIPT', 'STYLE', 'NOSCRIPT'].includes(parent.tagName)) {
                return NodeFilter.FILTER_REJECT;
            }

            return node.textContent.trim() === ''
                ? NodeFilter.FILTER_REJECT
                : NodeFilter.FILTER_ACCEPT;
        },
    });

    const nodes = [];
    let node = walker.nextNode();

    while (node) {
        nodes.push(node);
        node = walker.nextNode();
    }

    return nodes;
}

function normalizePatchText(text) {
    return String(text || '').replace(/\s+/g, ' ').trim();
}

function joinPatchTextParts(parts) {
    return parts.map((part) => normalizePatchText(part)).filter(Boolean).join(' ');
}

function findRawTextNodeSpan(rawTextNodes, searchOffset, text) {
    const normalizedText = normalizePatchText(text);

    for (let start = searchOffset; start < rawTextNodes.length; start++) {
        const indexes = [];
        const parts = [];

        for (let current = start; current < rawTextNodes.length; current++) {
            indexes.push(current);
            parts.push(rawTextNodes[current].textContent || '');

            const combinedText = joinPatchTextParts(parts);
            if (combinedText === normalizedText) {
                return { indexes, parts };
            }

            if (
                combinedText.length >= normalizedText.length
                || !normalizedText.startsWith(combinedText)
            ) {
                break;
            }
        }
    }

    return null;
}

function buildRawTextNodeMap(rawHtml, editorTexts) {
    const template = document.createElement('template');
    template.innerHTML = rawHtml || '';

    const rawTextNodes = collectRawTextNodes(template.content);
    const mappedIndexes = [];
    let searchOffset = 0;

    editorTexts.forEach((text) => {
        const normalizedText = normalizePatchText(text);
        let mappedEntry = rawTextNodes.findIndex((node, nodeIndex) => {
            if (nodeIndex < searchOffset) {
                return false;
            }

            return node.textContent === text || normalizePatchText(node.textContent) === normalizedText;
        });

        if (mappedEntry === -1) {
            mappedEntry = findRawTextNodeSpan(rawTextNodes, searchOffset, text) || -1;
        }

        mappedIndexes.push(mappedEntry);

        if (Number.isInteger(mappedEntry) && mappedEntry !== -1) {
            searchOffset = mappedEntry + 1;
        } else if (mappedEntry?.indexes?.length > 0) {
            searchOffset = mappedEntry.indexes[mappedEntry.indexes.length - 1] + 1;
        }
    });

    return mappedIndexes;
}

function splitTextAcrossRawParts(text, parts) {
    const normalizedText = normalizePatchText(text);
    const previousParts = Array.isArray(parts) ? parts : [];
    const nextParts = [];
    let remainingText = normalizedText;

    previousParts.forEach((part, index) => {
        if (index === previousParts.length - 1) {
            nextParts.push(remainingText);
            return;
        }

        const normalizedPart = normalizePatchText(part);
        if (normalizedPart !== '' && remainingText.startsWith(`${normalizedPart} `)) {
            nextParts.push(normalizedPart);
            remainingText = remainingText.slice(normalizedPart.length).trim();
            return;
        }

        nextParts.push(index === 0 ? remainingText : '');
        remainingText = '';
    });

    return nextParts;
}

function patchRawTextNodesFromEditor(state) {
    if (!state?.preserveRawHtml) {
        return false;
    }

    const nextTexts = collectEditorTextNodes(state.editor);
    const previousTexts = state.editorTextSnapshot || [];

    if (nextTexts.length !== previousTexts.length) {
        state.editorTextSnapshot = nextTexts;
        state.rawTextNodeMap = buildRawTextNodeMap(state.originalRawHtml || state.codeTextarea?.value || '', nextTexts);
        return false;
    }

    const template = document.createElement('template');
    template.innerHTML = state.originalRawHtml || state.codeTextarea?.value || '';

    let changed = false;
    const rawTextNodes = collectRawTextNodes(template.content);
    const rawTextNodeMap = Array.isArray(state.rawTextNodeMap)
        ? state.rawTextNodeMap
        : buildRawTextNodeMap(template.innerHTML, previousTexts);
    let searchOffset = 0;

    nextTexts.forEach((text, index) => {
        const previousText = previousTexts[index];
        if (text === previousText) {
            return;
        }

        let rawIndex = rawTextNodeMap[index];

        if (
            !rawIndex?.indexes
            && (!Number.isInteger(rawIndex) || rawIndex < 0 || !rawTextNodes[rawIndex])
        ) {
            const normalizedPreviousText = normalizePatchText(previousText);
            rawIndex = rawTextNodes.findIndex((node, nodeIndex) => (
                nodeIndex >= searchOffset
                && (
                    node.textContent === previousText
                    || normalizePatchText(node.textContent) === normalizedPreviousText
                )
            ));
        }

        if ((!Number.isInteger(rawIndex) || rawIndex === -1) && rawIndex?.indexes?.length > 0) {
            const nextParts = splitTextAcrossRawParts(text, rawIndex.parts);

            rawIndex.indexes.forEach((nodeIndex, partIndex) => {
                if (!rawTextNodes[nodeIndex]) {
                    return;
                }

                rawTextNodes[nodeIndex].textContent = nextParts[partIndex] || '';
            });

            searchOffset = rawIndex.indexes[rawIndex.indexes.length - 1] + 1;
            changed = true;
            return;
        }

        if (rawIndex === -1) {
            return;
        }

        rawTextNodes[rawIndex].textContent = text;
        searchOffset = rawIndex + 1;
        changed = true;
    });

    state.editorTextSnapshot = nextTexts;

    if (!changed) {
        return false;
    }

    setRawHtmlForState(state.container, state, template.innerHTML);
    state.rawTextNodeMap = buildRawTextNodeMap(state.originalRawHtml, nextTexts);
    return true;
}

function createToolbarButton(label, command, title) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'tiptap-toolbar-button';
    button.dataset.command = command;
    button.textContent = label;
    button.title = title;

    return button;
}

function updateImageSidebar(container) {
    const state = editors.get(container);
    if (!state) {
        return;
    }

    const sidebar = state.imageSidebar;
    if (!sidebar) {
        return;
    }

    const { editor } = state;
    const selectedImage = editor.isActive('image') ? editor.getAttributes('image') : null;
    sidebar.classList.toggle('hidden', !selectedImage);

    if (!selectedImage) {
        return;
    }

    sidebar.querySelectorAll('[data-image-attr]').forEach((input) => {
        const attr = input.dataset.imageAttr;
        input.value = selectedImage[attr] ?? '';
    });
}

function updateToolbarState(container) {
    const state = editors.get(container);
    if (!state) {
        return;
    }

    const { editor, toolbar } = state;
    if (!toolbar) {
        return;
    }

    toolbar.querySelectorAll('[data-command]').forEach((button) => {
        const { command } = button.dataset;
        const active = (
            (command === 'bold' && editor.isActive('bold'))
            || (command === 'italic' && editor.isActive('italic'))
            || (command === 'underline' && editor.isActive('underline'))
            || (command === 'h2' && editor.isActive('heading', { level: 2 }))
            || (command === 'h3' && editor.isActive('heading', { level: 3 }))
            || (command === 'bulletList' && editor.isActive('bulletList'))
            || (command === 'orderedList' && editor.isActive('orderedList'))
            || (command === 'blockquote' && editor.isActive('blockquote'))
            || (command === 'link' && editor.isActive('link'))
            || (command === 'image' && editor.isActive('image'))
        );

        button.classList.toggle('is-active', active);
    });

    updateImageSidebar(container);
    updateBackgroundSidebar(container);
}

function syncCodeFromEditor(container) {
    const state = editors.get(container);
    if (!state?.codeTextarea) {
        return;
    }

    state.codeTextarea.value = state.preserveRawHtml ? state.originalRawHtml : getEditorHtml(state.editor);
}

function syncEditorFromCode(container) {
    const state = editors.get(container);
    if (!state?.codeTextarea) {
        return;
    }

    const html = getDisplayHtml(annotateRawImages(state.codeTextarea.value));
    state.editor.commands.setContent(html, false);
    state.originalRawHtml = state.codeTextarea.value;
    state.editorTextSnapshot = collectEditorTextNodes(state.editor);
    state.rawTextNodeMap = buildRawTextNodeMap(state.originalRawHtml, state.editorTextSnapshot);
    updateToolbarState(container);
}

function syncJsonFromEditor(container) {
    const state = editors.get(container);
    if (!state) {
        return;
    }

    patchRawTextNodesFromEditor(state);

    const content = getSectionJson(container);
    const html = state.preserveRawHtml ? state.originalRawHtml : getEditorHtml(state.editor);

    content.raw_html = html;
    if (html.trim() !== '') {
        content.render_mode = 'raw_html';
    }

    setSectionJson(container, content);
    if (state.codeTextarea) {
        state.codeTextarea.value = html;
    }
}

function applySelectedImage(editor, item) {
    const attrs = {
        src: item.url,
        alt: item.alt || '',
        title: item.title || item.alt || '',
    };

    if (editor.isActive('image')) {
        const state = getStateByEditor(editor);
        const imageAttrs = editor.getAttributes('image');

        patchRawImageAttributes(state, imageAttrs.rawImageIndex, attrs);
        editor.chain().focus().updateAttributes('image', attrs).run();
        return;
    }

    editor.chain().focus().setImage(attrs).run();
}

function ensureModal() {
    if (modalState.element) {
        return modalState;
    }

    const modal = document.createElement('div');
    modal.className = 'tiptap-media-modal hidden';
    modal.innerHTML = `
        <div class="tiptap-media-dialog">
            <div class="tiptap-media-header">
                <div>
                    <h4>Select Image</h4>
                    <p>Use the site media library or upload a new file.</p>
                </div>
                <button type="button" class="tiptap-media-close" aria-label="Close">x</button>
            </div>
            <div class="tiptap-media-actions">
                <span class="tiptap-media-directory-label"></span>
                <span class="tiptap-media-upload-status hidden">Uploading...</span>
                <button type="button" class="tiptap-media-upload-button">Upload</button>
                <input type="file" class="hidden" accept="image/*">
            </div>
            <div class="tiptap-media-browser">
                <aside class="tiptap-media-explorer">
                    <div class="tiptap-media-explorer-header">
                        <h5>Folders</h5>
                    </div>
                    <div class="tiptap-media-directory-list"></div>
                </aside>
                <div class="tiptap-media-gallery">Loading...</div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    modalState.element = modal;
    modalState.directoryList = modal.querySelector('.tiptap-media-directory-list');
    modalState.gallery = modal.querySelector('.tiptap-media-gallery');
    modalState.uploadInput = modal.querySelector('input[type="file"]');
    modalState.uploadStatus = modal.querySelector('.tiptap-media-upload-status');

    modal.querySelector('.tiptap-media-close')?.addEventListener('click', () => closeModal());
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    modal.querySelector('.tiptap-media-upload-button')?.addEventListener('click', () => {
        modalState.uploadInput?.click();
    });

    modalState.uploadInput?.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('site_id', String(getConfig().siteId));
        formData.append('alt', file.name);
        formData.append('target_directory', modalState.directory || 'assets/images/upload');

        modalState.uploadStatus?.classList.remove('hidden');

        try {
            const response = await fetch('/api/media', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const result = await response.json();

            if (!response.ok) {
                const message = result.error || JSON.stringify(result.errors || {});
                throw new Error(message);
            }

            if (typeof window.renderPageEditStatus === 'function') {
                window.renderPageEditStatus('Upload completed successfully.', 'success');
            }

            await loadGallery();
        } catch (error) {
            if (typeof window.renderPageEditStatus === 'function') {
                window.renderPageEditStatus(`Upload failed: ${error.message}`, 'error');
            }
        } finally {
            modalState.uploadStatus?.classList.add('hidden');
            modalState.uploadInput.value = '';
        }
    });

    return modalState;
}

function renderDirectoryList() {
    const modal = ensureModal();
    if (!modal.directoryList) {
        return;
    }

    const directories = Array.isArray(modal.directories) ? modal.directories : [];
    if (directories.length === 0) {
        modal.directoryList.innerHTML = '<p class="tiptap-media-empty">No folders</p>';
        return;
    }

    modal.directoryList.innerHTML = '';

    directories.forEach((directory) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'tiptap-media-directory-button';
        button.textContent = directory.replace(/^assets\//, '');
        button.classList.toggle('is-active', directory === modal.directory);
        button.addEventListener('click', () => {
            modal.directory = directory;
            loadGallery();
        });
        modal.directoryList.appendChild(button);
    });
}

async function loadGallery() {
    const modal = ensureModal();
    modal.gallery.textContent = 'Loading...';

    try {
        const query = new URLSearchParams({
            site_id: String(getConfig().siteId),
            directory: modal.directory || 'assets/images/upload',
        });

        const response = await fetch(`/api/media?${query.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const result = await response.json();

        if (!response.ok || result === null || typeof result !== 'object') {
            throw new Error(result.error || 'Failed to load media library');
        }

        const normalizedResult = Array.isArray(result)
            ? {
                files: result,
                directories: [modal.directory],
                current_directory: modal.directory,
            }
            : result;

        modal.directories = Array.isArray(normalizedResult.directories)
            ? normalizedResult.directories.slice()
            : [modal.directory];
        modal.directory = typeof normalizedResult.current_directory === 'string' && normalizedResult.current_directory.trim() !== ''
            ? normalizedResult.current_directory
            : modal.directory;
        modal.element.querySelector('.tiptap-media-directory-label').textContent = modal.directory;
        renderDirectoryList();

        const items = Array.isArray(normalizedResult.files) ? normalizedResult.files.slice() : [];

        if (
            modal.currentItem
            && typeof modal.currentItem.path === 'string'
            && !items.some((item) => item.path === modal.currentItem.path)
        ) {
            items.unshift({
                id: null,
                site_id: getConfig().siteId,
                path: modal.currentItem.path,
                alt: modal.currentItem.alt || '',
                title: modal.currentItem.title || '',
                mime_type: null,
                url: modal.currentItem.url,
                created_at: null,
            });
        }

        if (items.length === 0) {
            modal.gallery.innerHTML = '<p class="tiptap-media-empty">No images uploaded yet.</p>';
            return;
        }

        modal.gallery.innerHTML = '';

        items
            .slice()
            .sort((left, right) => {
                const leftTime = left.created_at ? new Date(left.created_at).getTime() : 0;
                const rightTime = right.created_at ? new Date(right.created_at).getTime() : 0;
                return rightTime - leftTime;
            })
            .forEach((item) => {
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'tiptap-media-card';
                card.innerHTML = `
                    <div class="tiptap-media-card-preview">
                        <img src="${item.url}" alt="${item.alt || ''}">
                    </div>
                    <span>${(item.path || '').split('/').pop() || 'image'}</span>
                `;

                card.addEventListener('click', () => {
                    modal.onSelect?.(item);
                    closeModal();
                });

                modal.gallery.appendChild(card);
            });
    } catch (error) {
        modal.gallery.innerHTML = `<p class="tiptap-media-empty">${error.message}</p>`;
    }
}

function openModal(onSelect, options = {}) {
    const modal = ensureModal();
    modal.onSelect = onSelect;
    modal.directory = typeof options.directory === 'string' && options.directory.trim() !== ''
        ? options.directory.trim().replace(/\/+$/g, '')
        : 'assets/images/upload';
    modal.currentItem = options.currentItem || null;
    modal.element.classList.remove('hidden');
    loadGallery();
}

function closeModal() {
    if (!modalState.element) {
        return;
    }

    modalState.element.classList.add('hidden');
    modalState.onSelect = null;
    modalState.currentItem = null;
    modalState.directories = [];
}

function runToolbarCommand(container, command) {
    const state = editors.get(container);
    if (!state) {
        return;
    }

    const { editor } = state;

    switch (command) {
        case 'bold':
            editor.chain().focus().toggleBold().run();
            break;
        case 'italic':
            editor.chain().focus().toggleItalic().run();
            break;
        case 'underline':
            editor.chain().focus().toggleUnderline().run();
            break;
        case 'h2':
            editor.chain().focus().toggleHeading({ level: 2 }).run();
            break;
        case 'h3':
            editor.chain().focus().toggleHeading({ level: 3 }).run();
            break;
        case 'bulletList':
            editor.chain().focus().toggleBulletList().run();
            break;
        case 'orderedList':
            editor.chain().focus().toggleOrderedList().run();
            break;
        case 'blockquote':
            editor.chain().focus().toggleBlockquote().run();
            break;
        case 'link': {
            const previousUrl = editor.getAttributes('link').href || '';
            const url = window.prompt('Link URL', previousUrl);

            if (url === null) {
                break;
            }

            if (url.trim() === '') {
                editor.chain().focus().unsetLink().run();
                break;
            }

            editor.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
            break;
        }
        case 'image':
            openModal((item) => applySelectedImage(editor, item));
            break;
        case 'undo':
            editor.chain().focus().undo().run();
            break;
        case 'redo':
            editor.chain().focus().redo().run();
            break;
        default:
            break;
    }

    updateToolbarState(container);
    syncJsonFromEditor(container);
}

function buildToolbar(container) {
    const toolbar = container.querySelector('.tiptap-toolbar');
    if (!toolbar || toolbar.childElementCount > 0) {
        return toolbar;
    }

    [
        ['B', 'bold', 'Bold'],
        ['I', 'italic', 'Italic'],
        ['U', 'underline', 'Underline'],
        ['H2', 'h2', 'Heading 2'],
        ['H3', 'h3', 'Heading 3'],
        ['UL', 'bulletList', 'Bulleted list'],
        ['OL', 'orderedList', 'Numbered list'],
        ['"', 'blockquote', 'Quote'],
        ['Link', 'link', 'Insert link'],
        ['Image', 'image', 'Insert or replace image'],
        ['Undo', 'undo', 'Undo'],
        ['Redo', 'redo', 'Redo'],
    ].forEach(([label, command, title]) => {
        toolbar.appendChild(createToolbarButton(label, command, title));
    });

    toolbar.addEventListener('click', (event) => {
        const button = event.target.closest('[data-command]');
        if (!button) {
            return;
        }

        runToolbarCommand(container, button.dataset.command);
    });

    return toolbar;
}

function setActiveTab(container, tabName) {
    const state = editors.get(container);
    if (!state) {
        return;
    }

    if (tabName === 'code') {
        syncCodeFromEditor(container);
    }

    if (tabName === 'visual' && state.activeTab === 'code') {
        syncEditorFromCode(container);
    }

    if (tabName === 'json') {
        syncJsonFromEditor(container);
    }

    container.querySelectorAll('[data-editor-tab]').forEach((button) => {
        const isActive = button.dataset.editorTab === tabName;
        button.classList.toggle('border-indigo-500', isActive);
        button.classList.toggle('text-indigo-600', isActive);
        button.classList.toggle('dark:text-indigo-400', isActive);
        button.classList.toggle('border-transparent', !isActive);
        button.classList.toggle('text-gray-500', !isActive);
    });

    container.querySelectorAll('[data-editor-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.editorPanel !== tabName);
    });

    state.activeTab = tabName;
}

function bindTabs(container) {
    container.querySelectorAll('[data-editor-tab]').forEach((button) => {
        button.addEventListener('click', () => setActiveTab(container, button.dataset.editorTab));
    });
}

function bindImageSidebar(container) {
    const state = editors.get(container);
    if (!state?.imageSidebar) {
        return;
    }

    state.imageSidebar.querySelectorAll('[data-image-attr]').forEach((input) => {
        input.addEventListener('input', () => {
            if (!state.editor.isActive('image')) {
                return;
            }

            const attr = input.dataset.imageAttr;
            let value = input.value;

            if (attr === 'width' || attr === 'height') {
                const numericValue = Number(value);
                value = value.trim() === '' || !Number.isFinite(numericValue)
                    ? null
                    : String(Math.max(1, Math.round(numericValue)));
            } else {
                value = value.trim() === '' ? null : value;
            }

            state.editor.chain().focus().updateAttributes('image', {
                [attr]: value,
            }).run();

            const imageAttrs = state.editor.getAttributes('image');
            patchRawImageAttributes(state, imageAttrs.rawImageIndex, {
                [attr]: value,
            });
        });
    });
}

function createEditor(container) {
    const editorNode = container.querySelector('.tiptap-editor');
    const codeTextarea = container.querySelector('.section-raw-html');
    const toolbar = buildToolbar(container);
    const imageSidebar = container.querySelector('.tiptap-image-sidebar');
    const backgroundSidebar = container.querySelector('.tiptap-background-sidebar');
    const backgroundFields = container.querySelector('.tiptap-background-fields');
    const content = getSectionJson(container);
    const rawHtml = typeof content.raw_html === 'string' ? content.raw_html : '';

    const editor = new Editor({
        element: editorNode,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
            }),
            Underline,
            Link.configure({
                openOnClick: false,
                autolink: true,
                protocols: ['http', 'https', 'mailto', 'tel'],
            }),
            MediaImage,
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Placeholder.configure({
                placeholder: 'Edit module HTML content',
            }),
        ],
        content: getDisplayHtml(annotateRawImages(rawHtml)),
        onUpdate: () => {
            syncJsonFromEditor(container);
            updateToolbarState(container);
        },
        onSelectionUpdate: () => updateToolbarState(container),
        editorProps: {
            attributes: {
                class: 'tiptap-prose',
            },
        },
    });

    editors.set(container, {
        container,
        editor,
        toolbar,
        codeTextarea,
        imageSidebar,
        backgroundSidebar,
        backgroundFields,
        activeTab: 'visual',
        originalRawHtml: rawHtml,
        preserveRawHtml: shouldPreserveRawHtml(container, rawHtml),
        editorTextSnapshot: collectEditorTextNodes(editor),
        rawTextNodeMap: [],
    });

    const state = editors.get(container);
    state.rawTextNodeMap = buildRawTextNodeMap(rawHtml, state.editorTextSnapshot);

    bindImageSidebar(container);

    codeTextarea?.addEventListener('input', () => {
        const json = getSectionJson(container);
        json.raw_html = codeTextarea.value;
        if (codeTextarea.value.trim() !== '') {
            json.render_mode = 'raw_html';
        }
        setSectionJson(container, json);

        const currentState = editors.get(container);
        if (currentState) {
            currentState.originalRawHtml = codeTextarea.value;
            currentState.preserveRawHtml = shouldPreserveRawHtml(container, codeTextarea.value);
            currentState.editorTextSnapshot = collectEditorTextNodes(currentState.editor);
            currentState.rawTextNodeMap = buildRawTextNodeMap(currentState.originalRawHtml, currentState.editorTextSnapshot);
            updateBackgroundSidebar(container);
        }
    });

    container.querySelector('.section-content')?.addEventListener('input', () => {
        const currentState = editors.get(container);
        if (!currentState || currentState.activeTab === 'json') {
            return;
        }

        const json = getSectionJson(container);
        const nextHtml = typeof json.raw_html === 'string' ? json.raw_html : '';
        currentState.originalRawHtml = nextHtml;
        currentState.preserveRawHtml = shouldPreserveRawHtml(container, nextHtml);
        currentState.editor.commands.setContent(getDisplayHtml(annotateRawImages(nextHtml)), false);
        currentState.editorTextSnapshot = collectEditorTextNodes(currentState.editor);
        currentState.rawTextNodeMap = buildRawTextNodeMap(nextHtml, currentState.editorTextSnapshot);
        if (currentState.codeTextarea) {
            currentState.codeTextarea.value = nextHtml;
        }
        updateBackgroundSidebar(container);
        updateToolbarState(container);
    });

    bindTabs(container);
    syncJsonFromEditor(container);
    updateToolbarState(container);
    setActiveTab(container, 'visual');
}

function initTipTap(container) {
    if (!container || editors.has(container)) {
        return;
    }

    createEditor(container);
}

function syncFromTipTap(container) {
    if (!container) {
        return;
    }

    const state = editors.get(container);
    if (!state) {
        initTipTap(container);
    }

    const refreshedState = editors.get(container);
    if (!refreshedState) {
        return;
    }

    if (refreshedState.activeTab === 'code') {
        syncEditorFromCode(container);
    }

    syncJsonFromEditor(container);
}

function syncAll() {
    document.querySelectorAll('.section-item').forEach((container) => syncFromTipTap(container));
}

window.initTipTap = initTipTap;
window.syncFromTipTap = syncFromTipTap;
window.syncAllTipTapEditors = syncAll;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.section-item').forEach((container) => initTipTap(container));
});
