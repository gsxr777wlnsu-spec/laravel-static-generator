# Инструкция для заполнения index-raw-html-import-template.txt

Эта инструкция нужна для файла `index-raw-html-import-template.txt`, который лежит в корне проекта. Файл можно импортировать на странице `/admin/sites/create` через кнопку `Import`.

## 1. Общая логика

Файл импорта состоит из трёх типов блоков:

```txt
[FORM]
```

Основные настройки сайта: имя, домен, путь генерации, SFTP, источник шаблонов.

```txt
[FIELD]
```

Обычные поля страницы `index-raw_html.md`: title, meta description, тексты секций, alt, JSON-LD и так далее.

```txt
[OPERATION]
```

Операции со структурой: добавить блок, удалить блок, добавить пункт списка, добавить строку таблицы, добавить секцию.

## 2. Как импортировать

1. Откройте `/admin/sites/create`.
2. Нажмите кнопку `Import` в самом верху формы.
3. Выберите заполненный файл `index-raw-html-import-template.txt`.
4. Проверьте, что поля на странице заполнились.
5. Нажмите `Create Site`.

Важно: кнопка `Import` не создаёт сайт. Она только заполняет форму.

## 3. Переменные в начале файла

В самом верху файла, до первого блока `[FORM]`, `[FIELD]` или `[OPERATION]`, можно объявлять общие переменные.

Формат:

```txt
# Комментарий
{site} = test.ratel.im
{site_name} = Play Aviator Game
{promt_lang} = английском
{meta_description_total} = 160
```

Правила:

- переменные объявляются только в начале файла;
- имя переменной пишется в фигурных скобках на английском прописными и если состоит из нескольких слов то разделяется нижним подчеркиванием;
- значение переменной пишется после = и пробела и может иметь любые буквенные и цифровые значения;
- одну и ту же переменную можно использовать сколько угодно раз;
- переменные работают и в обычных полях, и в `prompt`, и в операциях добавления блоков;
- внутри JSON-LD блоков `<script type="application/ld+json">` переменные автоматически JSON-экранируются;
- если переменная не объявлена, текст вида `{site}` останется как есть, а при импорте будет предупреждение;
- если переменная объявлена, но нигде не используется, при импорте будет предупреждение;
- если одна и та же переменная объявлена несколько раз, при импорте будет предупреждение, а использоваться будет последнее значение.

Пример использования в ручном значении:

````txt
value:
```text
https://{site}/
```
````

Пример использования в AI промте:

````txt
prompt:
```text
напиши сео оптимизированное описание на {meta_description_total} символов на {promt_lang} языке для сайта {site_name} {site}
```
````

Параметр `send_current_value` внутри `[FIELD]`:

````txt
[FIELD]
file = index-raw_html.md
section = HEAD
label = meta_title
path = pages.0.meta_title
send_current_value = false

value:
```text
Aviator Game
```

prompt:
```text
Generate a completely new SEO title without relying on the current value.
```
````

Логика:

- `send_current_value = true` — в запрос к AI добавляется `Current value: ...`
- `send_current_value = false` — текущее значение в запрос к AI не отправляется
- если параметр не указан, используется `true`

## 4. Что можно менять

Можно менять эти значения:

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
```

## 5. Что лучше не менять

Не меняйте эти значения без необходимости:

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

Это технические id. По ним Laravel понимает, какое поле или блок нужно изменить.

## 6. Заполнение Site Information

В начале файла есть блок `[FORM]`.

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

Если сайт создаётся только локально, SFTP поля можно оставить пустыми.

## 7. Как изменить поле вручную

Каждое поле выглядит примерно так:

````txt
[FIELD]
file = index-raw_html.md
section = HEAD
label = title (12 chars)
path = pages.0.title

value:
```text
Aviator Game
```

prompt:
```text

```
````

Для ручной замены текста меняйте только текст внутри `value`.

Пример:

````txt
value:
```text
Aviator Casino Game
```
````

Если `prompt` пустой, AI агент это поле не трогает.

## 8. Как написать промт для AI агента

Промт пишется внутри блока `prompt`.

Пример:

````txt
value:
```text
Aviator Game
```

prompt:
```text
Generate an SEO title in English up to 60 characters for a gambling website about Aviator Game.
```
````

Если `prompt` заполнен, AI агент перепишет поле. Ручное значение в `value` остаётся исходным текстом для контекста.

## 9. Как оставить поле без изменений

Оставьте `prompt` пустым:

````txt
prompt:
```text

```
````

Такое поле будет проигнорировано AI агентом.

## 10. Как найти нужную секцию

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

## 11. Как добавить новый параграф

Новые блоки добавляются через `[OPERATION]`.

По умолчанию все операции выключены:

```txt
enabled = false
```

Чтобы операция выполнилась, поменяйте на:

```txt
enabled = true
```

Если нужно добавить несколько новых блоков подряд друг за другом, можно оставить одинаковый исходный `anchor_key` у всех этих операций. Система сама привяжет второй блок к первому, третий ко второму и так далее, сохранив порядок строк в файле импорта.

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
This Aviator review explains the rules, RTP, bonuses, and real money gameplay.
```

value_prompt:
```text
Rewrite this paragraph in English for SEO. Keep it concise and natural.
```
````

Что важно:

- `action = add_text` означает добавить текстовый тег.
- `tag = p` означает добавить `<p>`.
- `class = review__description` означает класс нового тега.
- `anchor_position = after` означает вставить после блока с `anchor_key`.
- `value_prompt` можно оставить пустым, если AI не нужен.

## 12. Как добавить заголовок h2, h3, h4, h5 или h6

Пример добавления `h2`:

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

Если класс пустой, система попробует взять стандартный класс такого же тега из этой секции. Если такого класса нет, тег добавится без класса.

## 12. Как правильно писать классы

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

## 13. Как добавить пункт списка li

Пример:

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

## 14. Как добавить карточку с иконкой

Пример:

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

`icon_alt` тоже текстовое значение. Его можно заполнить вручную.

## 15. Как добавить маркированный список ul

Пример:

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
  "Rewrite this item in English for SEO.",
  "Rewrite this item in English for SEO.",
  "Rewrite this item in English for SEO."
]
```
````

Количество строк в `items` и `item_prompts` должно совпадать.

Если AI не нужен:

```json
[
  "",
  "",
  ""
]
```

## 16. Как добавить нумерованный список ol

Пример:

````txt
[OPERATION]
enabled = true
label = Add standard OL in SECTION STEPS
file = index-raw_html.md
section = STEPS
section_path = pages.0.sections.10
action = add_list_block
anchor_key = оставить_как_в_шаблоне
anchor_position = after
list_tag = ol
class = list list--ordered
item_class = list__item
aria_label = How to play ordered list

items:
```json
[
  "Choose a trusted casino with Aviator",
  "Create an account and make a deposit",
  "Place a bet and cash out at the right time"
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

## 17. Как добавить строку таблицы

Пример:

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

col1_prompt:
```text
Rewrite this table cell label in English.
```

col2:
```text
Shows the current payout growth before the plane flies away.
```

col2_prompt:
```text
Rewrite this table cell description in English for SEO.
```
````

## 18. Как добавить целую таблицу

Пример:

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

header_prompts:
```json
[
  "",
  "",
  ""
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

row_prompts:
```json
[
  ["", "", ""],
  ["", "", ""],
  ["", "", ""]
]
```
````

Количество колонок в каждой строке должно совпадать с количеством `headers`.

## 19. Как удалить блок

Удаление обычного блока:

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

Удаление последнего пункта списка:

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

Удаление последней строки таблицы:

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

## 20. Как добавить новую секцию

Пример:

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

`module` должен быть существующим модулем проекта. Примеры:

```txt
hero
casino
review
symbols
gameplay
faq
```

## 21. Минимальный пример для meta title

````txt
[FIELD]
file = index-raw_html.md
section = HEAD
label = meta_title (12 chars)
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

Результат: AI агент перепишет `meta_title`.

## 22. Минимальный пример ручного изменения h1

````txt
[FIELD]
file = index-raw_html.md
section = HERO
label = hero :: h1 text (34 chars)
path = pages.0.sections.0.raw_html.__text__.оставить_как_в_шаблоне

value:
```text
Aviator Game - Play for Real Money
```

prompt:
```text

```
````

Результат: h1 будет изменён вручную, AI агент не будет его переписывать.

## 23. Быстрая памятка

- Изменить текст вручную: редактируйте `value`.
- Дать задачу AI: заполните `prompt`.
- Оставить поле как есть: оставьте `prompt` пустым и не меняйте `value`.
- Добавить блок: найдите `[OPERATION]`, поставьте `enabled = true`.
- Удалить блок: используйте `action = remove_block` и `enabled = true`.
- Добавить пункт списка: используйте `action = add_list_item`.
- Добавить строку таблицы: используйте `action = add_table_row`.
- Классы пишутся без точки: `class = casino__description`.
- Технические id лучше не менять.
