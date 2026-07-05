# Инструкция для заполнения index-raw-html-import-template.txt

Эта инструкция относится к файлу `index-raw-html-import-template.txt` в корне проекта. Файл импортируется на странице `/admin/sites/create` через кнопку `Import`.

Цель шаблона: создать сайт из `index-raw_html.md`, сохранить исходную HTML-структуру секций, вынести общие блоки `Menu`, `Mobile Menu`, `Footer` в отдельное хранение сайта и дать возможность безопасно редактировать текст/изображения в админке после импорта.

## 1. Как импортировать

1. Откройте `/admin/sites/create`.
2. Нажмите `Import` в верхней части формы.
3. Выберите заполненный `index-raw-html-import-template.txt`.
4. Проверьте заполненные поля, секции и операции.
5. Нажмите `Create Site`.

Важно: `Import` только заполняет форму. Сайт создаётся после `Create Site`.

## 2. Типы блоков в файле

```txt
[FORM]
```

Настройки сайта: имя, домен, шаблон, путь генерации, SFTP, источник шаблонов.

```txt
[FIELD]
```

Поля страницы `index-raw_html.md`: title, meta, тексты, атрибуты изображений, JSON-LD, HTML-секции.

```txt
[OPERATION]
```

Операции со структурой: добавить текстовый блок, список, строку таблицы, карточку, секцию или удалить элемент.

## 3. Переменные

Переменные объявляются в начале файла, до первого `[FORM]`, `[FIELD]` или `[OPERATION]`.

```txt
{site} = test.ratel.im
{site_name} = Play Aviator Game
{promt_lang} = английском
{meta_description_total} = 160
```

Правила:

- имя переменной пишется в фигурных скобках;
- используйте латиницу и нижнее подчёркивание: `{site_name}`;
- значение пишется после `=`;
- переменные можно использовать в `value`, `prompt`, `items`, `rows` и операциях;
- внутри `<script type="application/ld+json">` значения JSON-экранируются автоматически;
- необъявленная переменная останется в тексте как есть и даст warning при импорте;
- объявленная, но неиспользованная переменная тоже даст warning.

Пример:

````txt
value:
```text
https://{site}/
```
````

## 4. Блок [FORM]

Пример:

````txt
[FORM]
name = test5
domain = test5.com
template_set = base
output_path = generated/test5.com
status = draft
locale = en

sftp_host =
sftp_port = 22
sftp_username =
sftp_auth_method = key
sftp_password =
sftp_remote_path =

ai_clone_templates = true
ai_source_domain = test.com
````

Если сайт создаётся локально, SFTP-поля можно оставить пустыми.

`ai_source_domain` важен для импорта шаблонов и медиа. Обычно это домен исходного шаблона, например `test.com`.

## 5. Что можно менять

Обычно безопасно менять:

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
tag =
class =
items:
rows:
icon_src =
icon_alt =
```

## 6. Что нельзя менять без необходимости

Не меняйте технические ключи, если не понимаете последствия:

```txt
file = index-raw_html.md
path = pages.0.title
prompt_path = ...
section_path = ...
target_key = ...
block_key = ...
container_key = ...
anchor_key = ...
```

По этим значениям Laravel находит конкретный текст, атрибут, список или блок внутри raw HTML.

## 7. Поля [FIELD]

Типичный блок:

````txt
[FIELD]
file = index-raw_html.md
section = HEAD
label = title
path = pages.0.title

value:
```text
Aviator Game
```

prompt:
```text

```
````

Если `prompt` пустой, AI не меняет поле. Для ручной замены меняйте только текст внутри `value`.

## 8. send_current_value

```txt
send_current_value = true
```

В AI-запрос добавится текущее значение поля.

```txt
send_current_value = false
```

Текущее значение не отправится в AI-запрос. Используйте это, когда нужно сгенерировать полностью новый текст без опоры на старый.

Если параметр не указан, используется `true`.

## 9. Raw HTML секций

Основной контент импортируется как `raw_html`. Это значит, что система должна сохранить исходную HTML-структуру: классы, вложенность, `id`, `aria-*`, `data-*`, списки, таблицы, карточки.

Хорошо:

```html
<section class="hero" id="hero">
    <div class="hero__inner">
        <div class="hero__content">
            <h1 class="hero__title">Aviator Game</h1>
        </div>
        <div class="hero__media">
            <img class="hero__image" src="/assets/images/hero/aviator.webp" width="560" height="582" alt="Aviator">
        </div>
    </div>
</section>
```

Плохо:

```html
<section class="hero">
    <p>Aviator Game</p>
    <img src="/assets/images/hero/aviator.webp">
</section>
```

Плоский HTML может визуально отображаться, но стили сайта завязаны на BEM-классы и вложенность.

## 10. Menu / Mobile Menu / Footer

После выноса общих блоков в админке верхнее меню, мобильное меню и футер хранятся отдельно от страниц:

- `menu_html` для `Menu`;
- `mobile_menu_html` для `Mobile Menu`;
- `footer_html` для `Footer`.

При импорте Laravel сам ищет эти блоки внутри raw HTML страниц:

- `Menu`: HTML с `<header>` или `header__inner`;
- `Mobile Menu`: HTML с `mobile-menu` или module `mobile-menu`;
- `Footer`: HTML с `<footer>` или классами `footer...`.

Найденные блоки нормализуются и сохраняются в сайт. Потом они рендерятся на всех страницах через общие layout-блоки.

## 11. Правильная структура Menu

Для верхнего меню лучше передавать внутренность `<header>`, то есть блок `header__inner`. Можно передать и целый `<header>`, импорт сам извлечёт внутренность.

Рекомендуемый формат:

```html
<div class="header__inner">
    <div class="header__logo">
        <a class="header__logo-wrapper" href="index.html" aria-label="To the main page">
            <img src="/assets/images/logo/logo.webp" width="141" height="41" alt="Aviator">
        </a>
    </div>

    <nav class="header__nav menu" aria-label="Main navigation">
        <ul class="menu__list">
            <li class="menu__item">
                <a class="menu__link" href="app.html">App</a>
            </li>
        </ul>
        <a class="btn__cta" href="#play-now">Play now!</a>
    </nav>
</div>
```

Не сохраняйте меню как простой список без обёрток:

```html
<img src="/assets/images/logo/logo.webp">
<ul>
    <li><a class="menu__link" href="app.html">App</a></li>
</ul>
```

Такой HTML ломает стили, потому что нет `header__inner`, `header__logo`, `header__nav`, `menu__list`, `menu__item`.

## 12. Правильная структура Mobile Menu

Для мобильного меню нужен целый блок с классом `mobile-menu`.

Пример:

```html
<div class="mobile-menu" data-mobile-menu>
    <div class="mobile-menu__overlay" data-mobile-menu-close></div>
    <div class="mobile-menu__panel">
        <nav class="mobile-menu__nav" aria-label="Mobile navigation">
            <ul class="mobile-menu__list">
                <li class="mobile-menu__item">
                    <a class="mobile-menu__link" href="app.html">App</a>
                </li>
            </ul>
        </nav>
    </div>
</div>
```

Важно сохранить:

- корневой класс `mobile-menu`;
- классы элементов `mobile-menu__...`;
- `data-*` атрибуты, если они есть в исходном шаблоне.

## 13. Правильная структура Footer

Для футера можно передать целый `<footer>` или его внутренность. Импорт извлечёт внутренность `<footer>`.

Рекомендуемый формат:

```html
<div class="footer__inner">
    <div class="footer__main" aria-label="Footer navigation">
        <nav class="footer__col footer__col--links" aria-label="Footer column 1">
            <ul class="footer__links">
                <li class="footer__link-item">
                    <a class="footer__link" href="privacy-policy.html">Privacy Policy</a>
                </li>
            </ul>
        </nav>
    </div>
</div>
```

Не удаляйте `footer__inner` и внутренние классы `footer__...`.

## 14. Изображения при импорте

Используйте site-relative пути:

```html
<img src="/assets/images/hero/aviator.webp" alt="Aviator" width="560" height="582">
```

Допустимые форматы:

- `.webp`
- `.avif`
- `.svg`
- `.jpg`
- `.jpeg`
- `.png`

Что происходит при создании сайта:

- базовые assets копируются из эталонного сгенерированного сайта или последнего полного preview;
- при ручной замене изображения через импорт/форму файл сохраняется в шаблонную директорию нового сайта и попадает в `generated/site{id}/assets/...`;
- в preview путь `/assets/...` преобразуется в относительный `assets/...`;
- в редакторе админки путь показывается через `/admin/sites/{id}/media/serve/...`, но при сохранении возвращается к `/assets/...`.

## 15. Как заменить изображение в import-файле

Если в шаблоне есть поле `img src`, меняйте значение `value` или соответствующую замену изображения на путь внутри `/assets/...`.

Пример:

````txt
[FIELD]
file = index-raw_html.md
section = HERO
label = hero :: img src
path = pages.0.sections.0.raw_html.__attr__.оставить_как_в_шаблоне.src

value:
```text
/assets/images/hero/new-hero.webp
```

prompt:
```text

```
````

Правила:

- не используйте абсолютный URL `http://a.ratel.im/admin/sites/.../media/serve/...` в import-файле;
- не используйте путь с `/admin/sites/...`;
- путь должен начинаться с `/assets/`;
- если меняете `src`, проверьте `alt`, `title`, `width`, `height`, чтобы вёрстка не прыгала;
- для hero-фото обязательно сохраняйте исходный класс, например `class="hero__image"`.

## 16. Загрузка фото в новом редакторе

После создания сайта фото можно заменить в админке:

1. Откройте страницу редактирования модуля.
2. В Visual-редакторе выберите изображение.
3. Дважды кликните по изображению или используйте кнопку Image в toolbar.
4. В media modal выберите файл или нажмите `Upload`.
5. Выберите директорию, например `assets/images/hero` или `assets/images/casino`.
6. Сохраните через `Save Module` или `Save Changes`.

Новый редактор сохраняет сложный raw HTML безопасно:

- для сложных секций он не пересобирает весь блок через TipTap;
- замена изображения меняет только нужный `<img>`;
- текстовые изменения переносятся обратно в исходную структуру;
- временный атрибут `data-raw-image-index` нужен только внутри редактора и не должен попадать в итоговый HTML.

Если редактируете `Menu`, `Mobile Menu` или `Footer`, используйте отдельные страницы:

- `/admin/sites/{siteId}/pages/shared/menu`
- `/admin/sites/{siteId}/pages/shared/mobile-menu`
- `/admin/sites/{siteId}/pages/shared/footer`

Эти страницы принудительно сохраняют исходную HTML-структуру. При проблемах с визуальным редактором откройте вкладку `Code`, восстановите нормальный HTML и нажмите Save.

## 17. Preview и сохранение

Кнопка `Preview` больше не пересохраняет все модули автоматически. Это сделано специально, чтобы случайно не разрушать raw HTML.

Правильный порядок:

1. Изменили модуль.
2. Нажали `Save Module` или верхнюю `Save Changes`.
3. Дождались статуса `saved at ...`.
4. Нажали `Preview`.

`Save Module` сохраняет один модуль. `Save Changes` сохраняет настройки страницы и все модули.

## 18. Как найти нужную секцию

В файле есть заголовки:

```txt
# SECTION HEAD
# SECTION HERO
# SECTION CASINO
# SECTION REVIEW
# SECTION SYMBOLS
# SECTION FAQ
```

Ниже каждого заголовка идут поля этой секции.

## 19. Как добавить новый параграф

Новые блоки добавляются через `[OPERATION]`. По умолчанию операции выключены:

```txt
enabled = false
```

Чтобы операция выполнилась:

```txt
enabled = true
```

Пример:

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
This Aviator review explains the rules, RTP, bonuses, and real money gameplay.
```

value_prompt:
```text
Rewrite this paragraph in English for SEO. Keep it concise and natural.
```
````

Если нужно добавить несколько блоков подряд, можно оставить одинаковый `anchor_key`. Система вставит их по порядку строк в import-файле.

## 20. Как добавить заголовок

````txt
[OPERATION]
enabled = true
label = Add h2 in SECTION CASINO
file = index-raw_html.md
section = CASINO
section_path = pages.0.sections.2
action = add_text
anchor_key = оставить_как_в_шаблоне
anchor_position = after
tag = h2
class = casino__title

value:
```text
Best Aviator Casinos
```

value_prompt:
```text
Generate a short SEO h2 heading in English for a casino section.
```
````

Если класс не нужен:

```txt
class =
```

Если класс пустой, система попробует взять стандартный класс такого же тега из этой секции.

## 21. Классы

Класс пишется без точки.

Правильно:

```txt
class = casino__description
```

Неправильно:

```txt
class = .casino__description
```

Можно указать несколько классов:

```txt
class = text text--limited text--pt20
```

## 22. Как добавить пункт списка li

````txt
[OPERATION]
enabled = true
label = Add LI in SECTION CASINO
file = index-raw_html.md
section = CASINO
section_path = pages.0.sections.2
action = add_list_item
container_key = оставить_как_в_шаблоне
class = casino__item

value:
```text
Fast withdrawals and secure payments
```

value_prompt:
```text
Rewrite this list item in English for a casino benefits list.
```
````

`container_key` указывает, в какой список добавить новый `<li>`.

## 23. Как добавить карточку с иконкой

````txt
[OPERATION]
enabled = true
label = Add card feature in SECTION CASINO
file = index-raw_html.md
section = CASINO
section_path = pages.0.sections.2
action = add_card_feature
container_key = оставить_как_в_шаблоне
icon_src = /assets/svg/star-list.svg
icon_alt = Star icon

text:
```text
Exclusive welcome bonuses for new Aviator players
```

text_prompt:
```text
Rewrite this card feature in English. Make it short and benefit-focused.
```
````

`icon_src` тоже должен быть site-relative путём внутри `/assets/...`.

## 24. Как добавить маркированный или нумерованный список

Маркированный список:

````txt
[OPERATION]
enabled = true
label = Add standard UL in SECTION GAMEPLAY
file = index-raw_html.md
section = GAMEPLAY
section_path = pages.0.sections.6
action = add_list_block
anchor_key = оставить_как_в_шаблоне
anchor_position = after
list_tag = ul
class = list list--bulleted
item_class = list__item
aria_label = Gameplay bullet list

items:
```json
[
  "Learn the Aviator rules before betting",
  "Use small bets while testing strategies",
  "Cash out before the multiplier crashes"
]
```

item_prompts:
```json
[
  "",
  "",
  ""
]
```
````

Для нумерованного списка используйте:

```txt
list_tag = ol
```

Количество строк в `items` и `item_prompts` должно совпадать.

## 25. Как добавить строку таблицы

````txt
[OPERATION]
enabled = true
label = Add table row in SECTION SYMBOLS
file = index-raw_html.md
section = SYMBOLS
section_path = pages.0.sections.5
action = add_table_row
container_key = оставить_как_в_шаблоне
row_class = symbols__row

col1:
```text
Multiplier
```

col2:
```text
Shows the current payout growth before the plane flies away.
```
````

## 26. Как добавить целую таблицу

````txt
[OPERATION]
enabled = true
label = Add standard table in SECTION REVIEW
file = index-raw_html.md
section = REVIEW
section_path = pages.0.sections.4
action = add_table_block
anchor_key = оставить_как_в_шаблоне
anchor_position = after
class = payments__tables
aria_label = Payment methods list

headers:
```json
[
  "Method",
  "Min Deposit",
  "Withdrawal Time"
]
```

rows:
```json
[
  ["Visa", "€25", "1-3 days"],
  ["Skrill", "€10", "24-48 hours"],
  ["Bitcoin", "€20", "1-3 hours"]
]
```
````

Количество колонок в каждой строке должно совпадать с количеством `headers`.

## 27. Как удалить блок

Удалить обычный блок:

````txt
[OPERATION]
enabled = true
label = Remove block in SECTION REVIEW
file = index-raw_html.md
section = REVIEW
section_path = pages.0.sections.4
action = remove_block
target_key = оставить_как_в_шаблоне
````

Удалить последний пункт списка:

````txt
[OPERATION]
enabled = true
label = Remove last list item in SECTION CASINO
file = index-raw_html.md
section = CASINO
section_path = pages.0.sections.2
action = remove_last_list_item
container_key = оставить_как_в_шаблоне
````

Удалить последнюю строку таблицы:

````txt
[OPERATION]
enabled = true
label = Remove last table row in SECTION SYMBOLS
file = index-raw_html.md
section = SYMBOLS
section_path = pages.0.sections.5
action = remove_last_table_row
container_key = оставить_как_в_шаблоне
````

## 28. Как добавить новую секцию

````txt
[OPERATION]
enabled = true
label = Add section after SECTION REVIEW
file = index-raw_html.md
section = REVIEW
section_path = pages.0.sections.4
action = add_section
module = faq
````

`module` должен быть существующим модулем проекта, например:

```txt
hero
casino
review
symbols
gameplay
faq
```

## 29. Минимальный пример meta title

````txt
[FIELD]
file = index-raw_html.md
section = HEAD
label = meta_title
path = pages.0.meta_title

value:
```text
Aviator Game
```

prompt:
```text
Generate an SEO meta title in English up to 60 characters for an Aviator gambling website.
```
````

## 30. Минимальный пример ручного изменения h1

````txt
[FIELD]
file = index-raw_html.md
section = HERO
label = hero :: h1 text
path = pages.0.sections.0.raw_html.__text__.оставить_как_в_шаблоне

value:
```text
Aviator Game - Play for Real Money
```

prompt:
```text

```
````

## 31. Быстрая памятка

- Импортируйте через `/admin/sites/create` -> `Import`, затем `Create Site`.
- Для текста меняйте `value`.
- Для AI заполните `prompt`.
- Чтобы AI не менял поле, оставьте `prompt` пустым.
- Для изображений используйте только `/assets/...`, не `/admin/sites/...`.
- После импорта изображения в админке показываются через media serve, но сохраняются обратно как `/assets/...`.
- `Menu`, `Mobile Menu`, `Footer` должны сохранять полную BEM-структуру.
- Верхнее меню должно содержать `header__inner`.
- Мобильное меню должно содержать корневой `mobile-menu`.
- Футер должен сохранять `footer__inner` и `footer__...` классы.
- После ручной правки в админке нажмите `Save Module`, `Save Changes` или `Save MENU/MOBILE-MENU/FOOTER`, дождитесь статуса `saved at ...`, затем открывайте `Preview`.
- Классы пишутся без точки.
- Технические id (`path`, `anchor_key`, `container_key`) лучше не менять.
