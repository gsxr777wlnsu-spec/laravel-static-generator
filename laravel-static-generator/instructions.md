# Инструкция по импорту, мультиязычности и AI-генерации контента

Документ описывает актуальный рабочий процесс Laravel Static Generator:

- импорт сайтов через `.txt` шаблоны из `storage/import/txt/`;
- мультиязычные страницы и общие блоки сайта;
- настройку нескольких AI-моделей на `/admin/ai-agent`;
- генерацию контента прямо в редакторе модулей страницы.

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

Стандартные слоты сейчас заполняются так:

```txt
big_main = openai / gpt-5.5
big_alternate = anthropic / claude-opus-4.9
medium_main = openrouter / z-ai/glm-5.2
medium_alternate = anthropic / claude-sonnet-5
small_main = openrouter / qwen/qwen3.3
small_alternate = anthropic / claude-haiku
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

Если выбранный slot неизвестен или у него пустой `Model`, генерация вернёт ошибку. Если у slot нет собственного ключа, должен быть заполнен общий `API Key`.

Такие же поля `Model` и `Context` есть у prompt-полей на `/admin/sites/create` после импорта `.txt`. Они сохраняются в `ai_field_prompts` как:

```txt
model_key
context_mode
context_section_paths
```

## 14. Режимы Context для Generate

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

## 15. Примеры prompt в редакторе модуля

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

## 16. TipTap editor, изображения и assets

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

### Background images

Если в `raw_html` есть CSS `background-image`, Visual editor показывает sidebar для замены URL. Для модулей `hero` и `conclusion` есть fallback-цели:

```txt
.hero background-image -> /assets/images/hero/hero-background.webp
.conclusion__card::before background-image -> /assets/images/hero/conclusion-background.webp
```

Upload для этих background override принимает `.webp` и сохраняет замену в HTML модуля.

## 17. Preview и сохранение

Кнопка `Preview` не пересохраняет все модули автоматически.

Правильный порядок:

1. Изменили модуль.
2. Нажали `Save Module` или `Save Changes`.
3. Дождались статуса сохранения.
4. Нажали `Preview`.

Preview создаёт временный каталог:

```txt
storage/generated/preview/{token}/
```

Если assets конкретного сайта недоступны, генератор пытается использовать доступный fallback. Недоступный `site1/assets` не должен ломать открытие preview.

## 18. Структурные операции [OPERATION]

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

## 19. Быстрая памятка

- Импорт: `/admin/sites/create` -> `Import` -> выбрать `.txt` -> `Create Site`.
- Все import-файлы лежат в `storage/import/txt/`.
- Для главной используйте `index.txt`.
- Для остальных страниц используйте соответствующий файл: `app.txt`, `reviews.txt`, `tips.txt` и т.д.
- Для ручной замены меняйте `value`.
- Для AI-запроса заполняйте `prompt`.
- Если `prompt` пустой, AI не меняет поле.
- Не меняйте `path`, `prompt_path`, `target_key`, `block_key`, `container_key`, `anchor_key`.
- Для изображений используйте только `/assets/...`.
- Меню, mobile menu и footer редактируйте через shared pages.
- Для мультиязычности используйте `Add Language` на странице списка страниц сайта.
- AI-модели настраиваются на `/admin/ai-agent`.
- Для генерации выбирайте один из слотов `big_main`, `big_alternate`, `medium_main`, `medium_alternate`, `small_main`, `small_alternate`.
- В редакторе модулей `Generate` не сохраняет результат автоматически: после генерации нажмите `Save Module`.
- В Visual editor TipTap сохраняет сложный `raw_html` через patch text/image attributes; структуру секции меняйте через Code/JSON или `[OPERATION]`.
- После любых правок сначала сохраните, затем открывайте `Preview`.
