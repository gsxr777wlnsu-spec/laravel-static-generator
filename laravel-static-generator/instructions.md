# Инструкция по импорту, мультиязычности и AI-генерации контента

Документ описывает актуальный рабочий процесс Laravel Static Generator:

- импорт сайтов через `.txt` шаблоны из `storage/import/txt/`;
- мультиязычные страницы и общие блоки сайта;
- настройку нескольких AI-моделей на `/admin/ai-agent`;
- генерацию контента прямо в редакторе модулей страницы;
- правила AI Prompt для конкретных полей шаблона;
- загрузку, выбор и безопасное удаление медиафайлов сайта;
- Preview, Preview History и историю ревизий модулей.

## 1. Где лежат import-шаблоны

Шаблоны импорта страниц лежат здесь:

```txt
storage/import/txt/
```

Сейчас доступны шаблоны для всех страниц base/test-шаблона:

```txt
1win.txt
app.txt
authors.txt
bonuses.txt
comparison.txt
contact-us.txt
cookie-policy.txt
demo.txt
index.txt
privacy-policy.txt
reviews.txt
sitemap.txt
terms-and-conditions.txt
tips.txt
```

`index.txt` является эталонным шаблоном для главной страницы. Остальные файлы сгенерированы в том же формате: поля raw HTML разбиты на виртуальные поля `.__text__`, `.__attr__`, `.__style__` с техническими ключами `prompt_path`, `target_key`, `block_key`, `tag`, `attribute`.

## 2. Как импортировать сайт

1. Откройте `/admin/sites/create`.
2. Нажмите `Import`.
3. Выберите нужный `.txt` файл из `storage/import/txt/`.
4. Проверьте заполненные поля формы, page/template fields и queued operations.
5. Нажмите `Create Site`.

Важно: `Import` только заполняет форму. Сайт создаётся только после `Create Site`.

## 3. Основные блоки import-файла

```txt
[FORM]
```

Настройки сайта: домен, template set, output path, locale, SFTP, источник raw_html-шаблонов.

```txt
[FIELD]
```

Поля конкретного raw_html-файла: title, meta, canonical, тексты, атрибуты изображений, JSON-LD, CSS background-image.

```txt
[OPERATION]
```

Структурные операции: добавить текст, список, таблицу, строку таблицы, карточку, секцию или удалить блок. Сейчас такие операции детально подготовлены прежде всего в `index.txt`.

## 4. Переменные import-файла

Переменные объявляются в начале файла:

```txt
{site_name} = Cleopatra Slot in Canada
{site} = cleopatraslot.ca
{html_lang} = en_US
{canonical_url} = https://{site}/app.html
```

Правила:

- имя переменной пишется в фигурных скобках;
- используйте латиницу, цифры, `_`, `-`, `:`;
- переменные можно использовать в `value`, `prompt`, `items`, `rows` и операциях;
- внутри JSON-LD значения экранируются автоматически;
- необъявленная переменная останется как есть и даст warning при импорте;
- объявленная, но неиспользованная переменная тоже даст warning.

## 5. Блок [FORM]

Типичный блок:

````txt
[FORM]
name = cleopatraslot
domain = {site}
template_set = base
output_path = generated/{site}
status = draft
locale = {html_lang}

sftp_host =
sftp_port = 22
sftp_username =
sftp_auth_method = key
sftp_password =
sftp_remote_path =

ai_clone_templates = true
ai_source_domain = test.com
````

`ai_clone_templates = true` включает клонирование raw_html-шаблонов из `ai_source_domain`.

Обычно `ai_source_domain = test.com`, потому что исходные raw_html-файлы лежат в:

```txt
storage/import-deploy/md/test/raw_html/test.com/
```

## 6. Что можно менять в import-файле

Безопасно менять:

```txt
name =
domain =
output_path =
status =
locale =
sftp_host =
sftp_username =
sftp_password =
sftp_remote_path =
send_current_value =
value:
prompt:
enabled =
items:
rows:
icon_src =
icon_alt =
```

Не меняйте без необходимости:

```txt
file =
path =
prompt_path =
section_path =
target_key =
block_key =
container_key =
anchor_key =
```

Эти значения связывают import-файл с конкретным узлом HTML.

## 7. Формат [FIELD]

Пример обычного поля:

````txt
[FIELD]
file = app-raw_html.md

section = HEAD

label = meta_title (16 chars)

path = pages.0.meta_title
send_current_value = true
value:
```text
App Aviator Game
```

prompt:
```text

```
````

Если `prompt` пустой, AI не меняет поле. Для ручной замены меняйте только содержимое `value`.

Пример raw_html text-поля:

````txt
[FIELD]
file = 1win-raw_html.md

section = HERO

label = hero :: h1 text (17 chars)

path = pages.0.sections.0.raw_html.__text__.c6e930d8da1a15b3
send_current_value = true
prompt_path = pages.0.sections.0.raw_html.__text__.c6e930d8da1a15b3

target_key = c6e930d8da1a15b3

block_key = c6e930d8da1a15b3

tag = h1

value:
```text
1WIN Aviator Game
```

prompt:
```text

```
````

## 8. send_current_value

```txt
send_current_value = true
```

AI получит текущее значение поля вместе с prompt.

```txt
send_current_value = false
```

AI не получит текущее значение. Используйте это для полной генерации нового текста.

## 9. Raw HTML и структура модулей

Секции base-шаблона сохраняются как `raw_html`. Нельзя упрощать структуру до плоского HTML, потому что стили завязаны на BEM-классы, вложенность, `id`, `aria-*`, `data-*`.

Правильно:

```html
<section class="hero" id="hero">
    <div class="hero__inner">
        <div class="hero__content">
            <h1 class="hero__title">Aviator Game</h1>
        </div>
    </div>
</section>
```

Плохо:

```html
<section class="hero">
    <p>Aviator Game</p>
</section>
```

## 10. Menu, Mobile Menu, Footer

Общие блоки сайта хранятся отдельно:

- `Menu`;
- `Mobile Menu`;
- `Footer`.

Их можно редактировать на страницах:

```txt
/admin/sites/{siteId}/pages/shared/menu/{locale}
/admin/sites/{siteId}/pages/shared/mobile-menu/{locale}
/admin/sites/{siteId}/pages/shared/footer/{locale}
```

При импорте Laravel извлекает:

- меню из `<header>` или `.header__inner`;
- mobile menu из `.mobile-menu`;
- footer из `<footer>` или `.footer...`.

В редакторе обычной страницы эти общие блоки скрываются из секций, чтобы не редактировать их как часть hero/raw_html.

## 11. Мультиязычность

На странице `/admin/sites/{siteId}/pages` есть кнопка `Add Language`.

Можно вводить:

- ISO-код: `es`, `fr`, `de`, `ar`;
- английское название: `Spanish`, `Arabic`;
- русское название: `Испанский`, `Арабский`.

Система создаёт локализованные страницы и shared blocks для выбранного языка. Для каждого языка доступны отдельные страницы:

```txt
/admin/sites/{siteId}/pages/{pageId}/edit
```

Для shared blocks язык указывается в URL:

```txt
/admin/sites/{siteId}/pages/shared/menu/es
```

В import-файлах переменные:

```txt
{html_lang}
{og_locale_alternate}
{alternate_lang}
```

используются для locale, OpenGraph и alternate links.

## 12. AI Agent: настройка моделей

Настройки находятся на:

```txt
/admin/ai-agent
```

Есть 3 группы моделей:

- `big`;
- `medium`;
- `small`.

В каждой группе есть 2 слота:

- `main`;
- `alternate`.

Итого 6 model slots:

```txt
big_main
big_alternate
medium_main
medium_alternate
small_main
small_alternate
```

Текущая рабочая конфигурация слотов:

```txt
big_main = openrouter / openai/gpt-5.6-sol-pro
big_alternate = openrouter / ~anthropic/claude-fable-latest
medium_main = openrouter / openai/gpt-5.6-terra-pro
medium_alternate = openrouter / anthropic/claude-sonnet-5
small_main = openrouter / openai/gpt-5.6-luna-pro
small_alternate = openrouter / ~x-ai/grok-latest
```

Для каждого slot задаются индивидуально:

- Provider;
- Model;
- Base URL;
- API Key;
- Temperature;
- Tone;
- Max Tokens;
- Top P;
- Frequency Penalty;
- Presence Penalty.

Индивидуальный API Key хранится зашифрованно. Если у slot свой ключ не задан, используется общий API Key из основной конфигурации AI Agent.

Поля верхнего уровня (`Provider`, `Model`, общий `API Key`, `Temperature`, `Tone` и т.д.) остаются fallback-конфигурацией. При генерации через выбранный slot используются параметры slot; пустые числовые параметры и пустой `Tone` берутся из общей конфигурации. Для `medium_main` пустой `Model` также может быть взят из общего поля `Model`.

По умолчанию в редакторе модулей выбрана основная модель группы `medium` (`medium_main`).

## 13. Генерация контента в редакторе страницы

Откройте страницу:

```txt
/admin/sites/{siteId}/pages/{pageId}/edit
```

Под каждым модулем есть блок AI:

- `AI Prompt`;
- свёрнутый блок `AI Prompt Rule`;
- кнопка `Generate`;
- поле `Model`;
- поле `Context`.

Порядок работы:

1. Введите prompt.
2. Выберите модель.
3. Выберите режим контекста.
4. Нажмите `Generate`.
5. Проверьте результат в Visual/Code/JSON.
6. Нажмите `Save Module`.

`Generate` только подставляет результат в редактор. В БД результат сохраняется после `Save Module` или `Save Changes`.

Во время запроса кнопка блокируется, показывает spinner и текст `Generating…`. Повторно нажимать её не нужно. Если модель недоступна, провайдер вернул `content_filter`, пустой ответ или сетевую ошибку, сообщение выводится непосредственно под нажатой кнопкой. Для пустого ответа сообщение содержит фактический model ID и `finish_reason`, если провайдер их вернул.

Если выбранный slot неизвестен или у него пустой `Model`, генерация вернёт ошибку. Если у slot нет собственного ключа, должен быть заполнен общий `API Key`.

В `SECTION HEAD` на странице редактирования также есть AI Prompt-поля для:

```txt
title
meta_title
meta_description
head_meta.3.content (og:title)
head_meta.4.content (og:description)
```

Они работают так же: `Generate` подставляет результат в поле, а сохранение происходит после `Save Changes`.

Такие же поля `Model` и `Context` есть у prompt-полей на `/admin/sites/create` после импорта `.txt`. Они сохраняются в `ai_field_prompts` как:

```txt
model_key
context_mode
context_section_paths
```

### История и избранное AI Prompt

История работает в редакторе существующих страниц и в prompt-полях `/admin/sites/create`. Область общей истории задаётся точным ключом:

```txt
template_set + page_key + module_key/head + locale + field_key
```

Site ID в ключ не входит. Поэтому последний промпт, применённый для определённого поля модуля одного сайта, становится доступен тому же полю всех существующих и новых сайтов с тем же шаблоном, страницей, модулем и языком.

Промпт считается применённым только после последовательности:

```txt
Generate -> успешный Save Module / Save Changes
```

Сохраняется текст, реально отправленный при `Generate`. Если после генерации изменить поле prompt, при Save сохранится использованный промпт, а не новое неотправленное значение. Для `/admin/sites/create` prompt фиксируется после успешного создания сайта.

Правила истории:

- хранится не более пяти разных обычных промптов одного ключа;
- последний автоматически подставляется в поле `AI Prompt`;
- четыре предыдущих показываются в `History`;
- повторное применение идентичного текста обновляет его дату, не создавая дубликат;
- шестой уникальный prompt удаляет самый ранний;
- `Delete current` удаляет последний prompt;
- кнопка `Delete` удаляет выбранную запись истории вручную;
- клик по тексту истории возвращает его в поле.

`Add to favorites` сохраняет отдельную избранную копию. Избранное не входит в лимит пяти, количество избранных не ограничено, автоматическая ротация его не удаляет. Удаление избранного возможно только вручную.

История хранится в таблице `ai_prompt_histories`. `AI Prompt Rule` является отдельной сущностью и в историю prompt не входит.

## 14. AI Prompt Rule

У каждого AI Prompt есть свёрнутый блок `AI Prompt Rule`.

Правило хранится в БД в таблице `ai_prompt_rules` и привязано к конкретному полю шаблона:

```txt
template_set + page_key + field_key
```

Примеры ключей:

```txt
base / demo / meta_title
base / demo / meta_description
base / demo / hero-main/module_prompt
base / index / casino/module_prompt
```

`template_set` берётся из сайта, например `base`. `page_key` берётся из `template_key` страницы, если он задан, иначе из `slug`. `field_key` для полей `SECTION HEAD` равен имени поля (`title`, `meta_title`, `meta_description`). Для модулей используется формат:

```txt
{module_key}/module_prompt
```

Правило применяется ко всем сайтам и страницам с тем же `template_set`, `page_key` и `field_key`. Например правило для:

```txt
base / demo / meta_title
```

будет применяться ко всем страницам типа `demo` всех сайтов на шаблоне `base`.

Правило передаётся в LLM перед пользовательским prompt отдельным обязательным блоком:

```txt
Mandatory rule for this field: ...
User prompt cannot override this rule.
```

Если правило пустое, оно не передаётся в LLM.

Важно: правило имеет приоритет над текстом в `AI Prompt`. Если правило требует `meta_title` от 45 до 65 символов, а пользовательский prompt просит 70 символов, модель должна следовать правилу.

Правила применяются:

- при генерации модулей на странице редактирования;
- при генерации `title`, `meta_title`, `meta_description` в `SECTION HEAD`;
- при AI-генерации import field prompts во время создания сайта.

## 15. SECTION HEAD по умолчанию

При создании сайта или страницы через админку `SECTION HEAD` заполняется дефолтами из `.txt` import-шаблонов:

```txt
storage/import/txt/{page}.txt
```

Например для страницы `demo` используется:

```txt
storage/import/txt/demo.txt
```

Система берёт значения из блоков `[FIELD]`, где:

```txt
section = HEAD
path = pages.0....
```

Автоматически заполняются доступные поля:

```txt
title
meta_title
meta_description
canonical
og_data.head_meta
og_data.head_extra
og_data.head_custom
body_extra
```

Для стандартных `head_meta` строк также заполняются `name` и `property`, например:

```txt
robots
og:type
og:locale
og:title
og:description
article:published_time
article:modified_time
article:author
twitter:card
```

Если у страницы уже есть значение, оно не перетирается. Дефолты добавляют только отсутствующие поля.

## 16. Режимы Context для Generate

Доступны режимы:

```txt
Nothing
Previous module only
Next module only
Previous and next modules
All modules
Selected modules
```

По умолчанию выбран `Nothing`.

Если выбран `Selected modules`, ниже показываются чекбоксы модулей текущей страницы. В AI-запрос попадут только выбранные модули.

Важно: перед отправкой в AI HTML секции очищается от shared header/menu/mobile-menu. Ответ AI также очищается от этих блоков перед подстановкой в редактор.

На `/admin/sites/create` у import prompt-полей те же режимы называются через `section`: `Previous section only`, `Next section only`, `Previous and next sections`, `All sections`, `Selected sections`. Режим `Selected sections` использует `context_section_paths`, а не числовые ID секций. В редакторе существующей страницы используются ID текущих секций.

Для пяти полей `SECTION HEAD` доступны режимы `Nothing`, `All modules`, `Selected modules`. Режимы previous/next для HEAD не используются, потому что HEAD не является модулем в последовательности секций.

## 17. Примеры prompt в редакторе модуля

Для H1:

```txt
Сгенерируй SEO текст для тега h1. Верни только HTML текущего модуля, сохрани классы и структуру.
```

Для описания:

```txt
Перепиши текст описания в hero под Канаду. Сохрани HTML, классы, кнопки и изображения.
```

Для FAQ:

```txt
Сгенерируй 5 вопросов и ответов для FAQ на английском. Сохрани текущую HTML-структуру FAQ.
```

## 18. TipTap editor, изображения и assets

Редактор модуля работает в трёх режимах:

```txt
Visual
Code
JSON
```

Visual использует TipTap. Code редактирует `raw_html` напрямую. JSON редактирует весь JSON секции. При переключении:

- `Code -> Visual` перечитывает HTML из Code;
- `Visual -> JSON` синхронизирует HTML из TipTap в JSON;
- перед сохранением все TipTap-редакторы синхронизируются с JSON.

Для сложного `raw_html` редактор включает preserve mode: он сохраняет исходную HTML-структуру секции и патчит только изменённые текстовые узлы или атрибуты изображений. Это нужно, чтобы не потерять BEM-классы, вложенность, `style`, `aria-*`, `data-*`, таблицы и другие технические атрибуты.

Составные заголовки с акцентной частью поддерживаются отдельно:

```html
<h2 class="symbols__title"><span class="title--accent">Recommended</span> Casinos</h2>
```

В Visual можно независимо менять или полностью удалять текст внутри и снаружи `span.title--accent`. TipTap сохраняет `h2`, `span`, их классы, порядок и значимые пробелы. Полное удаление соседнего текстового узла также переносится в `raw_html`.

При сохранении сервер удаляет случайные XML processing instructions и XML-комментарии. Если редактор прислал дублирующую оболочку вида `<section class="benefits"><section id="benefits">…`, безопасная одиночная оболочка разворачивается. Это предотвращает двойные секции, лишние padding, разрывы градиентных фонов и отступы между модулями.

TipTap toolbar поддерживает:

```txt
Bold
Italic
Underline
H2
H3
Bulleted list
Numbered list
Quote
Link
Image
Undo
Redo
```

Таблицы отображаются и сохраняются, но их структуру безопаснее менять через Code/JSON или import `[OPERATION]`, если нужно добавлять строки/блоки.

### Изображения

В import-файлах используйте только site-relative пути:

```txt
/assets/images/hero/aviator.webp
/assets/svg/star.svg
```

Не используйте:

```txt
/admin/sites/{id}/media/serve/...
http://...
```

Допустимые форматы:

- `.webp`;
- `.avif`;
- `.svg`;
- `.jpg`;
- `.jpeg`;
- `.png`.

После создания сайта изображения можно менять через Visual editor:

1. Откройте модуль.
2. Дважды кликните по изображению.
3. Выберите или загрузите файл.
4. При необходимости отредактируйте `src`, `alt`, `title`, `width`, `height` в боковой панели изображения.
5. Сохраните модуль.

Редактор показывает assets через `/admin/sites/{id}/media/serve/...`, но при сохранении возвращает путь к `/assets/...`.

Кнопка `Image` вставляет новое изображение или заменяет выбранное. Двойной клик по существующему изображению открывает media library в папке текущего asset.

### Удаление изображений в проводнике

В media library кнопка `Delete` показывается только у файлов, которые физически находятся в каталоге текущего сайта на диске `sites`:

```txt
storage/sites/{site_id}/assets/...
```

Перед удалением браузер запрашивает подтверждение. После подтверждения:

1. Удаляется медиафайл из каталога текущего сайта.
2. Если для точного пути файла существует запись в таблице `media`, эта запись также удаляется.
3. Если у записи указан сгенерированный `webp_path`, WebP-файл удаляется вместе с исходным файлом.
4. Список изображений в проводнике обновляется автоматически.

Удаление не изменяет уже сохранённые `src` или `background-image` в содержимом страницы. Если удалённый файл использовался в модуле, выберите другой файл и сохраните модуль.

Файлы, показанные через fallback-каталоги шаблона или генератора, доступны только для выбора. Кнопка удаления для них не отображается. В частности, удаление не затрагивает:

```txt
base
storage/generated/site1/...
storage/generated/1/...
другие generated/fallback-каталоги
```

Сервер повторно проверяет источник файла независимо от интерфейса. Endpoint `DELETE /api/media/file` принимает `site_id` и site-relative `path`, формирует путь только внутри `{site_id}/assets/...` на диске `sites` и возвращает `404`, если такого файла сайта нет. Поэтому запрос с путём к файлу `base` или другого сайта не удалит шаблонный файл.

Обычный endpoint `DELETE /api/media/{id}` продолжает использоваться на административной странице Media Library для удаления медиа по записи `media`.

### Background images

Если в `raw_html` есть CSS `background-image`, Visual editor показывает sidebar для замены URL. Для модулей `hero` и `conclusion` есть fallback-цели:

```txt
.hero background-image -> /assets/images/hero/hero-background.webp
.conclusion__card::before background-image -> /assets/images/hero/conclusion-background.webp
```

Upload для этих background override принимает `.webp` и сохраняет замену в HTML модуля.

## 19. Preview, Preview History и сохранение

На странице редактирования есть две кнопки Preview:

- обычная кнопка `Preview` в верхней панели;
- круглая закреплённая кнопка Preview в правом нижнем углу.

Обе кнопки работают одинаково:

1. Сохраняют настройки страницы.
2. Сохраняют все модули страницы.
3. Генерируют preview.
4. Открывают preview в новой вкладке.

Если сохранение страницы или модулей завершилось ошибкой, preview не открывается из старой версии.

Preview создаёт временный каталог:

```txt
storage/generated/preview/{token}/
```

Каждый созданный preview записывается в таблицу `page_previews`.

Кнопка `Preview History` открывает список preview текущей страницы:

- клик по названию preview открывает его в новой вкладке;
- кнопка `Delete` удаляет запись из БД и весь каталог `storage/generated/preview/{token}/`;
- список относится только к текущей странице.

Если assets конкретного сайта недоступны, генератор пытается использовать доступный fallback. Недоступный `site1/assets` не должен ломать открытие preview.

## 20. История ревизий модулей

У каждого модуля рядом с `Delete Module` есть кнопка `History`.

При каждом сохранении модуля старое состояние секции сохраняется в таблицу:

```txt
section_histories
```

История хранится отдельно для каждого `section_id`.

Правила:

- показываются последние 10 сохранённых версий;
- в БД автоматически остаются только последние 10 версий для каждого модуля;
- старые записи удаляются при превышении лимита;
- при откате текущая версия сначала сохраняется в историю;
- после отката страница перезагружается, чтобы Visual/Code/JSON редакторы получили восстановленное состояние.

Клик по записи в `History` откатывает только конкретный модуль.

## 21. Структурные операции [OPERATION]

Операции включаются явно:

```txt
enabled = true
```

Если `enabled = false`, операция игнорируется.

Пример добавления параграфа:

````txt
[OPERATION]
enabled = true
label = Add paragraph in SECTION REVIEW
file = index-raw_html.md
section = REVIEW
section_path = pages.0.sections.4
action = add_text
anchor_key = оставить_как_в_шаблоне
anchor_position = after
tag = p
class = review__description

value:
```text
This review explains the rules, RTP, bonuses, and real money gameplay.
```

value_prompt:
```text
Rewrite this paragraph in English for SEO.
```
````

Поддерживаемые действия:

```txt
add_text
add_list_item
add_card_feature
add_list_block
add_table_row
add_table_block
add_section
remove_block
remove_last_list_item
remove_last_table_row
```

## 22. Быстрая памятка

- Импорт: `/admin/sites/create` -> `Import` -> выбрать `.txt` -> `Create Site`.
- Все import-файлы лежат в `storage/import/txt/`.
- Для главной используйте `index.txt`.
- Для остальных страниц используйте соответствующий файл: `app.txt`, `reviews.txt`, `tips.txt` и т.д.
- Для ручной замены меняйте `value`.
- Для AI-запроса заполняйте `prompt`.
- Если `prompt` пустой, AI не меняет поле.
- Для обязательных ограничений используйте `AI Prompt Rule`; правило имеет приоритет над prompt.
- Не меняйте `path`, `prompt_path`, `target_key`, `block_key`, `container_key`, `anchor_key`.
- Для изображений используйте только `/assets/...`.
- В проводнике удаляются только файлы текущего сайта из `storage/sites/{site_id}/assets/...`; запись `media` удаляется вместе с файлом, а `base` и generated/fallback assets защищены.
- Меню, mobile menu и footer редактируйте через shared pages.
- Для мультиязычности используйте `Add Language` на странице списка страниц сайта.
- AI-модели настраиваются на `/admin/ai-agent`.
- Для генерации выбирайте один из слотов `big_main`, `big_alternate`, `medium_main`, `medium_alternate`, `small_main`, `small_alternate`.
- В редакторе модулей `Generate` не сохраняет результат автоматически: после генерации нажмите `Save Module`.
- Во время генерации ориентируйтесь на spinner и `Generating…`; ошибка модели отображается под соответствующей кнопкой.
- Последний применённый AI Prompt подставляется автоматически; четыре предыдущих находятся в `History`, а постоянные варианты — в `Favorites`.
- В Visual editor TipTap сохраняет сложный `raw_html` через patch text/image attributes; структуру секции меняйте через Code/JSON или `[OPERATION]`.
- `Preview` автоматически сохраняет страницу и модули перед открытием новой вкладки.
- `Preview History` показывает preview текущей страницы и позволяет удалить preview из БД и с диска.
- `History` у модуля показывает последние 10 ревизий и позволяет откатить конкретный модуль.
