# markitdown — извличане на съдържанието на файловете в markdown

Адаптер към [MarkItDown](https://github.com/microsoft/markitdown) на Microsoft — конвертира
офис документи, PDF, таблици и др. в **markdown** със запазена структура (заглавия, списъци,
таблици, връзки). Предназначен е за подаване към AI модели и за преглед на съдържанието.

Разликата с текстовия индекс (Apache Tika / pdftotext): там резултатът е плосък текст, тук
таблиците остават таблици, а заглавията — заглавия.

---

## 1. Инсталация на програмата

MarkItDown е **Python** пакет (изисква Python >= 3.10) — няма composer вариант.

**Не се инсталира системно с `pip`.** На Ubuntu 24.04 (и на всяка система с PEP 668) системният
Python е "externally managed" и `pip install markitdown` завършва с
`error: externally-managed-environment`. Насилването с `--break-system-packages` може да счупи
apt-овите Python пакети на машината.

Правилният начин е **отделен venv**, а пълният път до програмата се задава в `MARKITDOWN_PATH`.

### Стандартно (със sudo)

```bash
sudo apt install python3.12-venv
sudo python3 -m venv /opt/markitdown
sudo /opt/markitdown/bin/pip install 'markitdown[pdf,docx,xlsx,xls,pptx,outlook]'
```

### Без root (когато няма sudo или пакетът `python3-venv` липсва)

`--without-pip` не изисква apt пакета `python3-venv`, а pip се подава като официалния standalone
zipapp. Във venv няма маркер "externally managed", така че инсталацията минава нормално:

```bash
python3 -m venv --without-pip /path/to/markitdown
curl -sSLO https://bootstrap.pypa.io/pip/pip.pyz
/path/to/markitdown/bin/python pip.pyz install 'markitdown[pdf,docx,xlsx,xls,pptx,outlook]'
```

### Кои extras

`[pdf,docx,xlsx,xls,pptx,outlook]` покриват списъка по подразбиране (вижте
`MARKITDOWN_EXTENSIONS`); csv, html, epub, json, txt, xml, ipynb и zip минават с базовите
зависимости. `[all]` добавя и Azure SDK-тата, YouTube и аудио транскрибирането — не ни трябват.

### Проверка

Програмата се пуска от уеб потребителя (обикновено `www-data`), затова той трябва да може да я
изпълни — всички директории по пътя да са `o+x`:

```bash
sudo -u www-data /path/to/markitdown/bin/markitdown --version
```

После пълният път се задава в конфигурацията на пакета:

```
MARKITDOWN_PATH = /path/to/markitdown/bin/markitdown
```

Инсталацията на пакета в bgERP проверява дали програмата е налична на този път и показва
предупреждение, ако я няма.

---

## 2. Как работи

- `markitdown_Converter` имплементира `fileman_MarkdownIntf` и се задава в конфигурацията на
  `fileman` (`FILEMAN_MARKDOWN`) — при инсталация се избира автоматично, ако няма друга програма.
- Извличането минава през `fconv_Script` (както tesseract/apachetika) и се изпълнява асинхронно:
  `markitdown <входен файл> -o <изходен файл>`.
- Резултатът се записва във `fileman_Indexes` чрез `fileman_Indexes::saveContent()` с тип
  **`markdown`**, като за файла се показва таб **Markdown**.

Извличането се стартира от три места:

| Откъде | Кога |
| --- | --- |
| `fileman_webdrv_Generic::startProcessing()` | при отваряне на инфо страницата на файла |
| `fileman_Indexes::processFile()` | от крона, при индексиране на новите файлове |
| бутон **MD** в тулбара на файла | ръчно; изтрива стария индекс и извлича наново |

Съдържанието се чете с:

```php
// Само ако вече е извлечено
$md = fileman_Indexes::getMarkdownForIndex($fileHnd);

// Форсира извличането, ако още няма и изчаква обработката
$md = fileman_Indexes::forceMarkdownForIndex($fileHnd);
```

---

## 3. Конфигурация

| Константа | По подразбиране | Значение |
| --- | --- | --- |
| `MARKITDOWN_PATH` | `markitdown` | път до изпълнимия файл |
| `MARKITDOWN_EXTENSIONS` | `pdf, docx, xlsx, xls, pptx, csv, epub, msg, ipynb, html, htm, xml, txt, text, md, markdown, json, jsonl, zip` | от кои файлове се извлича |
| `MARKITDOWN_MAX_FILE_LEN` | 20 MB | по-големите файлове се пропускат |
| `MARKITDOWN_MAX_CONTENT_LEN` | 1 000 000 | ограничение на записаното съдържание |
| `MARKITDOWN_MAX_ARCHIVE_LEN` | 5 MB | максимален размер на архив (компресиран) |
| `MARKITDOWN_MAX_ARCHIVE_FILES` | 100 | максимален брой файлове в архива |
| `MARKITDOWN_MAX_ARCHIVE_CONTENT_LEN` | 50 MB | максимален размер на архива след разархивиране |
| `MARKITDOWN_USE_PLUGINS` | Не | дали да се ползват външните плъгини на markitdown |

**Изображенията (jpg, jpeg, png) съзнателно не са в списъка** — за тях markitdown вади само
EXIF метаданни (същинският текст се разпознава с OCR — `FILEMAN_OCR`).

**Аудиото (wav, mp3, m4a) също не е** — markitdown го транскрибира през `SpeechRecognition`,
което по подразбиране праща звука към външна услуга (Google). Ако това е приемливо, разширенията
се добавят в списъка, а програмата се инсталира с `pip install 'markitdown[audio-transcription]'`.

---

## 3.1. Архиви (zip)

markitdown обхожда **всички** файлове в архива, конвертира всеки според разширението му и ги
лепи в един документ със заглавия `## File: <път в архива>`. Вложените архиви се разгъват
рекурсивно.

Понеже това става в паметта и без ограничения, `markitdown_Converter::canExtractFromArchive()`
преглежда архива преди обработката — чете само индекса му (без разархивиране и без временно
копие) и **пропуска** архива, ако:

- е над `MARKITDOWN_MAX_ARCHIVE_LEN` (компресиран),
- има повече от `MARKITDOWN_MAX_ARCHIVE_FILES` файла,
- след разархивиране е над `MARKITDOWN_MAX_ARCHIVE_CONTENT_LEN` (защита от „бомби“),
- е защитен с парола (markitdown не може да го прочете),
- съдържа **вложен архив** — не може да се предвиди колко ще се разгъне.

Пропуснатите архиви просто нямат таб Markdown; останалите обработки на файла не се променят.

---

## 4. Индексиране на старите файлове

Новите файлове се обработват от крона (`fileman_Data::cron_ProcessFiles`). Вече обработените
данни са с `processed = 'yes'` и не се пипат отново. За тях:

- markdown се извлича при отваряне на файла (таба се появява след обработката), или
- от `fileman_Indexes` → **Регенериране** → *Ключови думи* (с ограничение на броя), което ги
  връща в опашката на крона.

---

## 5. Поддържани формати от MarkItDown

PDF, DOCX, XLSX/XLS, PPTX, CSV, JSON, XML, HTML, EPUB, Outlook MSG, Jupyter notebooks, ZIP
(обхожда съдържанието), изображения (само EXIF/LLM описание), аудио (EXIF + транскрибиране).

Форматите с истинска полза от markdown са таблиците и документите — xlsx/xls/csv излизат като
markdown таблици (`| … | … |`), а docx/pptx/pdf запазват заглавията и списъците. За txt/md/json
резултатът е близък до текстовия индекс, но минава през разпознаване на кодировката на markitdown.
