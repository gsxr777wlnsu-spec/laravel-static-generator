@php
    $pageFields = is_array($fileItem['page_fields'] ?? null) ? $fileItem['page_fields'] : [];
    $sectionFields = is_array($fileItem['section_fields'] ?? null) ? $fileItem['section_fields'] : [];
    $sectionControls = is_array($fileItem['section_block_controls'] ?? null) ? $fileItem['section_block_controls'] : [];
    $controlsBySection = collect($sectionControls)->keyBy('section_path');
    $fieldsBySection = collect($sectionFields)->groupBy(fn ($field) => (string) ($field['section_path'] ?? ''));
    $knownSectionPaths = collect($sectionControls)
        ->pluck('section_path')
        ->merge($fieldsBySection->keys())
        ->filter()
        ->unique()
        ->values();

    $renderField = function (array $field, string $fileName): string {
        $rows = (int) ($field['input_rows'] ?? 2);
        $rows = max(2, $rows);
        $path = e((string) ($field['path'] ?? ''));
        $promptPath = e((string) ($field['prompt_path'] ?? $field['path'] ?? ''));
        $file = e($fileName);
        $label = e((string) ($field['field'] ?? 'Field'));
        $length = (int) ($field['length'] ?? mb_strlen((string) ($field['value'] ?? '')));
        $value = e((string) ($field['value'] ?? ''));
        $showPrompt = ($field['show_prompt'] ?? true) === true;
        $promptNotice = !$showPrompt && (($field['prompt_path'] ?? '') !== ($field['path'] ?? ''));
        $tag = e((string) ($field['tag'] ?? ''));
        $attribute = e((string) ($field['attribute'] ?? ''));
        $assetKind = strtolower((string) ($field['asset_kind'] ?? ''));
        $isImageSrc = strtolower((string) ($field['tag'] ?? '')) === 'img'
            && strtolower((string) ($field['attribute'] ?? '')) === 'src';
        $isAssetUrl = $isImageSrc || $assetKind === 'background-image-url';
        $imageClass = trim((string) ($field['image_class'] ?? ''));
        $imageAlt = trim((string) ($field['image_alt'] ?? ''));

        $html = '<div class="ai-prompt-row rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800" data-file="' . $file . '" data-path="' . $path . '" data-prompt-path="' . $promptPath . '" data-tag="' . $tag . '" data-attribute="' . $attribute . '">';
        $html .= '<div class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">' . $label . ' (<span class="ai-field-length">' . $length . '</span> chars)</div>';
        $html .= '<textarea rows="' . $rows . '" data-default-rows="' . $rows . '" class="ai-manual-input mb-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Edit field value manually">' . $value . '</textarea>';
        if ($isAssetUrl) {
            $imageMeta = [];
            if ($imageClass !== '') {
                $imageMeta[] = 'class: ' . e($imageClass);
            }
            if ($imageAlt !== '') {
                $imageMeta[] = 'alt: ' . e($imageAlt);
            }
            if ($imageMeta !== []) {
                $html .= '<div class="mb-2 text-xs text-gray-500 dark:text-gray-400">' . implode(' | ', $imageMeta) . '</div>';
            }
            $html .= '<label class="mb-2 inline-flex cursor-pointer items-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Import Image<input type="file" accept="image/*" class="ai-image-replacement-input hidden"></label>';
            $html .= '<div class="ai-image-replacement-name mb-2 hidden text-xs text-gray-500 dark:text-gray-400"></div>';
        }

        if ($showPrompt) {
            $html .= '<textarea rows="2" class="ai-prompt-input block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI to rewrite this field"></textarea>';
            $html .= '<label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300"><input type="checkbox" class="ai-send-current-value-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked><span>Send current value to AI</span></label>';
        } elseif ($promptNotice) {
            $html .= '<div class="text-xs text-gray-500 dark:text-gray-400">Shared AI prompt is attached to the first line of this heading.</div>';
        }

        $html .= '</div>';

        return $html;
    };

    $findByKey = function (array $items, string $key): ?array {
        foreach ($items as $item) {
            if (is_array($item) && (string) ($item['key'] ?? '') === $key) {
                return $item;
            }
        }

        return null;
    };

    $inlineButtonClass = 'inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-white';
    $standardBulletedItems = [
        'We will explore the key features of the Aviator',
        'Casino game, discuss its gameplay mechanics',
        'User interface and overall experience',
    ];
    $standardOrderedItems = [
        'Visit the Website: Go to truefortune-casino.uk from any device to begin sign up.',
        'Click the Registration Button: Find and select "Join Now" or "Register" in the top-right corner of the homepage.',
        'Enter Personal Details: Provide email, username, password, name, date of birth, and phone number for the TrueFortune account.',
    ];
    $standardTableHeaders = [
        'Method',
        'Withdrawal Availability',
        'Min Deposit/Withdrawal',
        'Withdrawal Time',
        'Fees',
    ];
    $standardTableRows = [
        ['Visa', 'Yes (limited)', '€25/€50', '1-3 days', '3% on deposit'],
        ['Mastercard', 'Yes (limited)', '€25/€50', '1-3 days', '3% on deposit'],
        ['Skrill', 'Yes', '€10/€20', '24-48 hours', 'None'],
        ['Neteller', 'Yes', '€10/€20', '24-48 hours', 'None'],
        ['EcoPayz', 'Yes', '€10/€20', 'Instant-24 hours', 'None'],
        ['Bitcoin', 'Yes', '€20 equiv/€100', '1-3 hours', 'None'],
        ['Paysafecard', 'No', '€5/N/A', 'N/A', 'None'],
        ['Bank Transfer', 'Yes', '€100/€100', '3-7 days', 'Varies by bank'],
    ];
@endphp

<details open class="rounded-md border border-gray-200 dark:border-gray-700">
    <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-gray-800 dark:text-gray-200">
        {{ $fileItem['file'] }}
    </summary>

    <div class="border-t border-gray-200 px-4 py-8 dark:border-gray-700">
        <details class="mb-20 rounded-md border border-gray-200 p-4 dark:border-gray-700">
            <summary class="ai-section-title mb-10 cursor-pointer break-words font-semibold uppercase leading-none text-gray-950 dark:text-white">
                SECTION HEAD
            </summary>

            <div class="mb-[60px]">
                <h3 class="ai-block-title mb-6 break-words font-semibold uppercase leading-tight text-gray-900 dark:text-white">
                    HEAD META
                </h3>

                <div class="space-y-5">
                    @foreach($pageFields as $field)
                        {!! $renderField($field, (string) ($fileItem['file'] ?? '')) !!}
                    @endforeach
                </div>
            </div>
        </details>

        @foreach($knownSectionPaths as $sectionPath)
            @php
                $control = $controlsBySection->get($sectionPath);
                $firstSectionField = $fieldsBySection->get($sectionPath, collect())->first();
                $module = (string) (($control['module'] ?? '') ?: (is_array($firstSectionField) ? ($firstSectionField['module'] ?? '') : '') ?: $sectionPath);
                $sectionTitle = 'SECTION ' . strtoupper(str_replace(['-', '_'], ' ', $module));
                $sectionFieldsList = $fieldsBySection->get($sectionPath, collect())->values();
                $blocks = $sectionFieldsList
                    ->groupBy(fn ($field) => (string) ($field['block_key'] ?? $field['path'] ?? ''))
                    ->map(function ($items, $blockKey) {
                        $first = $items->first();
                        return [
                            'key' => (string) $blockKey,
                            'type' => (string) ($first['block_type'] ?? 'text'),
                            'tag' => (string) ($first['block_tag'] ?? $first['tag'] ?? 'text'),
                            'label' => (string) ($first['block_label'] ?? $first['field'] ?? 'Block'),
                            'fields' => $items->values(),
                        ];
                    })
                    ->values();

                $listContainers = is_array($control['list_containers'] ?? null) ? $control['list_containers'] : [];
                $cardContainers = is_array($control['card_feature_containers'] ?? null) ? $control['card_feature_containers'] : [];
                $tableContainers = is_array($control['table_containers'] ?? null) ? $control['table_containers'] : [];
            @endphp

            <details class="ai-template-section mb-20 rounded-md border border-gray-200 p-4 dark:border-gray-700" data-file="{{ $fileItem['file'] }}" data-section-path="{{ $sectionPath }}" data-section-label="{{ $sectionTitle }}">
                <summary class="ai-section-title mb-10 cursor-pointer break-words font-semibold uppercase leading-none text-gray-950 dark:text-white">
                    {{ $sectionTitle }}
                </summary>

                <div class="space-y-[60px]">
                    @foreach($blocks as $block)
                        @php
                            $blockType = $block['type'];
                            $blockKey = $block['key'];
                            $blockLabel = strtoupper(str_replace(['::', '<', '>'], [' ', '', ''], $block['label']));
                            $listContainer = $findByKey($blockType === 'card' ? $cardContainers : $listContainers, $blockKey);
                            $tableContainer = $findByKey($tableContainers, $blockKey);
                            $isListLike = in_array($blockType, ['list', 'card'], true);
                            $isTable = $blockType === 'table';
                        @endphp

                        <div class="ai-template-block ai-structural-control rounded-md border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900"
                             data-file="{{ $fileItem['file'] }}"
                             data-section-path="{{ $sectionPath }}"
                             data-block-key="{{ $blockKey }}"
                             data-block-type="{{ $blockType }}"
                             data-block-label="{{ $block['label'] }}">
                            <h3 class="ai-block-title mb-6 break-words font-semibold uppercase leading-tight text-gray-900 dark:text-white">
                                {{ $blockLabel }}
                            </h3>

                            <div class="space-y-5">
                                @if($isTable || $isListLike)
                                    @php
                                        $itemGroups = $block['fields']
                                            ->groupBy(fn ($field) => (string) ($field['item_key'] ?? $field['path'] ?? ''))
                                            ->values();
                                    @endphp

                                    @foreach($itemGroups as $itemIndex => $itemFields)
                                        @php
                                            $firstItemField = $itemFields->first();
                                            $itemLabel = (string) ($firstItemField['item_label'] ?? '');
                                            $itemTitle = $itemLabel !== ''
                                                ? $itemLabel
                                                : ($isTable ? 'Table row ' . ($itemIndex + 1) : 'List item ' . ($itemIndex + 1));
                                        @endphp

                                        <div class="rounded-md border border-gray-300 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                            <div class="mb-3 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                {{ $itemTitle }}
                                            </div>
                                            <div class="space-y-3">
                                                @foreach($itemFields as $field)
                                                    {!! $renderField($field, (string) ($fileItem['file'] ?? '')) !!}
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    @foreach($block['fields'] as $field)
                                        <div>
                                            {!! $renderField($field, (string) ($fileItem['file'] ?? '')) !!}
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="mt-5 flex items-center gap-2">
                                <button type="button"
                                        class="ai-inline-toggle-add {{ $inlineButtonClass }} bg-indigo-600 hover:bg-indigo-500"
                                        title="Add block or item">
                                    +
                                </button>
                                <button type="button"
                                        class="ai-inline-remove-btn {{ $inlineButtonClass }} bg-rose-600 hover:bg-rose-500"
                                        title="Remove block or item"
                                        data-action="{{ $isTable ? 'remove_last_table_row' : ($isListLike ? 'remove_last_list_item' : 'remove_block') }}"
                                        data-target-key="{{ $blockKey }}"
                                        data-container-key="{{ $blockKey }}"
                                        data-confirm-label="{{ $isTable ? 'last table row in ' . $block['label'] : ($isListLike ? 'last list/card item in ' . $block['label'] : $block['label']) }}">
                                    -
                                </button>
                            </div>

                            <div class="ai-inline-add-panel mt-4 hidden rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                @if($isTable)
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <input type="text" class="ai-block-table-col1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Column 1">
                                            <textarea rows="2" class="ai-block-table-col1-prompt mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI to rewrite this field"></textarea>
                                        </div>
                                        <div>
                                            <input type="text" class="ai-block-table-col2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Column 2">
                                            <textarea rows="2" class="ai-block-table-col2-prompt mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI to rewrite this field"></textarea>
                                        </div>
                                    </div>
                                    <button type="button" class="ai-block-op-btn mt-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                            data-action="add_table_row"
                                            data-container-key="{{ $blockKey }}"
                                            data-anchor-key="{{ $blockKey }}"
                                            data-anchor-position="after">
                                        Queue Add Row
                                    </button>
                                @elseif($blockType === 'card')
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <input type="text" class="ai-block-feature-icon-src rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="/assets/svg/" placeholder="Icon src">
                                        <input type="text" class="ai-block-feature-icon-alt rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Icon alt">
                                        <textarea rows="2" class="ai-block-feature-text sm:col-span-2 rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Feature text"></textarea>
                                        <textarea rows="2" class="ai-block-feature-prompt sm:col-span-2 rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI to rewrite this field"></textarea>
                                    </div>
                                    <button type="button" class="ai-block-op-btn mt-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                            data-action="add_card_feature"
                                            data-container-key="{{ $blockKey }}">
                                        Queue Add Feature
                                    </button>
                                @elseif($isListLike)
                                    <textarea rows="2" class="ai-block-list-item-text block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="LI text"></textarea>
                                    <textarea rows="2" class="ai-block-list-item-prompt mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI to rewrite this field"></textarea>
                                    <button type="button" class="ai-block-op-btn mt-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                            data-action="add_list_item"
                                            data-container-key="{{ $blockKey }}">
                                        Queue Add LI
                                    </button>
                                @else
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <select class="ai-block-text-tag rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            @foreach(($control['addable_tags'] ?? ['h2', 'h3', 'h4', 'h5', 'h6', 'p']) as $tag)
                                                <option value="{{ $tag }}" @selected($tag === $block['tag'])>{{ strtoupper($tag) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" class="ai-block-text-class rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Optional class">
                                        <textarea rows="2" class="ai-block-text-value sm:col-span-2 rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Text content"></textarea>
                                        <textarea rows="2" class="ai-block-text-prompt sm:col-span-2 rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI to rewrite this module field"></textarea>
                                    </div>
                                    <button type="button" class="ai-block-op-btn mt-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                            data-action="add_text"
                                            data-anchor-key="{{ $blockKey }}"
                                            data-anchor-position="after">
                                        Queue Add Block
                                    </button>
                                @endif

                                <div class="mt-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                                    <div class="mb-3 text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                        Add standard block
                                    </div>
                                    <select class="ai-standard-block-type block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="ul">Bulleted list</option>
                                        <option value="ol">Numbered list</option>
                                        <option value="table">Payment table</option>
                                    </select>

                                    <div class="ai-standard-block-panel ai-standard-block-panel-ul mt-3 space-y-2" data-standard-panel="ul">
                                        <input type="text" class="ai-standard-list-class block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="list list--bulleted" placeholder="UL class">
                                        <input type="text" class="ai-standard-list-aria block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="Gameplay bullet list" placeholder="UL aria-label">
                                        <input type="text" class="ai-standard-list-item-class block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="list__item" placeholder="LI class">
                                        @foreach($standardBulletedItems as $item)
                                            <div class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                                <textarea rows="2" class="ai-standard-list-item block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="LI text">{{ $item }}</textarea>
                                                <textarea rows="2" class="ai-standard-list-item-prompt mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI to rewrite this field"></textarea>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="ai-standard-block-panel ai-standard-block-panel-ol mt-3 hidden space-y-2" data-standard-panel="ol">
                                        <input type="text" class="ai-standard-list-class block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="list list--ordered" placeholder="OL class">
                                        <input type="text" class="ai-standard-list-aria block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="How to deposit numbered list" placeholder="OL aria-label">
                                        <input type="text" class="ai-standard-list-item-class block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="list__item" placeholder="LI class">
                                        @foreach($standardOrderedItems as $item)
                                            <div class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                                <textarea rows="2" class="ai-standard-list-item block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="LI text">{{ $item }}</textarea>
                                                <textarea rows="2" class="ai-standard-list-item-prompt mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI to rewrite this field"></textarea>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="ai-standard-block-panel ai-standard-block-panel-table mt-3 hidden space-y-3" data-standard-panel="table">
                                        <input type="text" class="ai-standard-table-class block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="payments__tables" placeholder="Outer div class">
                                        <input type="text" class="ai-standard-table-aria block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="Payment methods list" placeholder="Outer aria-label">
                                        <div class="grid grid-cols-1 gap-2 md:grid-cols-5">
                                            @foreach($standardTableHeaders as $header)
                                                <div>
                                                    <input type="text" class="ai-standard-table-header block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="{{ $header }}" placeholder="Header">
                                                    <textarea rows="2" class="ai-standard-table-header-prompt mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI"></textarea>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($standardTableRows as $row)
                                                <div class="ai-standard-table-row grid grid-cols-1 gap-2 rounded-md border border-gray-200 p-2 dark:border-gray-700 md:grid-cols-5">
                                                    @foreach($row as $cell)
                                                        <div>
                                                            <input type="text" class="ai-standard-table-cell block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" value="{{ $cell }}" placeholder="Cell">
                                                            <textarea rows="2" class="ai-standard-table-cell-prompt mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI"></textarea>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <button type="button" class="ai-block-op-btn mt-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                            data-action="add_standard_block"
                                            data-anchor-key="{{ $blockKey }}"
                                            data-anchor-position="after">
                                        Queue Add Standard Block
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="ai-section-actions ai-structural-control mt-8 rounded-md border border-dashed border-gray-300 p-4 dark:border-gray-600"
                     data-file="{{ $fileItem['file'] }}"
                     data-section-path="{{ $sectionPath }}">
                    <div class="flex items-center gap-2">
                        <button type="button"
                                class="ai-inline-toggle-add {{ $inlineButtonClass }} bg-indigo-600 hover:bg-indigo-500"
                                title="Add section">
                            +
                        </button>
                        <button type="button"
                                class="ai-inline-remove-btn {{ $inlineButtonClass }} bg-rose-600 hover:bg-rose-500"
                                title="Remove section"
                                data-action="remove_section"
                                data-confirm-label="{{ $sectionTitle }}">
                            -
                        </button>
                    </div>

                    <div class="ai-inline-add-panel mt-4 hidden rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <select class="ai-section-module-select block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @foreach(($moduleCatalog ?? []) as $moduleItem)
                                <option value="{{ $moduleItem['key'] ?? '' }}">{{ $moduleItem['label'] ?? ($moduleItem['key'] ?? '') }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="ai-block-op-btn mt-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                data-action="add_section">
                            Queue Add Section
                        </button>
                    </div>
                </div>
            </details>
        @endforeach
    </div>
</details>
