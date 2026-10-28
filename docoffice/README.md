# JODConverter в bgERP

Проверено на 27.10.2026 г. Добавена е стабилната [JODConverter 5.1.1](https://github.com/jodconverter/jodconverter/releases/tag/v5.1.1) чрез отделен Composer проект. Нови JAR/ZIP файлове не се добавят в Git. Включената `3.0b4` остава непроменена, както и defaults:

```php
OFFICE_CONVERTER_CLASS = 'docoffice_Jodconverter'
OFFICE_JODCONVERTER_VERSION = '3.0b4'
```

Версията на bgERP setup остава `0.1`. Премахната е невалидната setup зависимост `permanent=0.1`: използваният `core_Permanent` е част от ядрото, а отделен пакет `permanent` няма.

## Избор и инсталиране

1. В `core_Packs` отворете настройките на `docoffice`, оставете класа JodConverter и изберете `5.1.1`.
2. Запишете и инициализирайте `docoffice`, за да инсталирате официалната дистрибуция чрез Composer.
3. Проверете собствен тестов Office документ във файловия мениджър. Връщането към `3.0b4` става от същото поле, без изтриване на новата библиотека.

Изборът е само за съответната инсталация. Полето за версия се показва само при JodConverter и запазва стойността си при превключване на класа. Unoconv и досегашният Office lifecycle не са променени.

Нужни са Java **17 или по-нова**, инсталиран LibreOffice/OpenOffice, достъпни за уеб/CLI потребителя, и права за временни файлове и локална Office връзка. [Изискването за Java е от upstream 5.x](https://jodconverter.github.io/jodconverter/latest/migration-guides/migration-guide-5.0.0/). PHP адаптерът е съвместим с PHP 7.4 и 8.2, защото Java работи като отделен процес. Няма нови PHP runtime зависимости.

Setup проверява Java и изпълнимия `OFFICE_PACKET_PATH --version`. При липса показва стандартната грешка на `core_Packs` и спира преди Composer. CLI открива Office инсталацията автоматично; нестандартни/portable Office разположения не са проверени. На отделен сървър за конвертиране също трябва да има подходяща Java, Office и дистрибуция. За стандартния PDF към JPG preview е необходим и Ghostscript, както досега. Съществуващият helper за свободен порт използва `netstat`.

За първата инсталация са нужни интернет, Composer и PHP zip или unzip. Общият `core_Composer` остава непроменен. `core_ComposerProject::install()` използва неговия executable и `composer install` с отделни manifest, lock и vendor чрез `COMPOSER` и `COMPOSER_VENDOR_DIR`. Средата се възстановява във `finally`. Същият helper се използва от Tika, без дублиране на installer логиката.

Проектът е `EF_VENDOR_PATH/docoffice`, библиотеките са в `vendor/bgerp/jodconverter-cli/` спрямо него. `bgerp/jodconverter-cli` е локална Composer декларация за официалния архив, а не официален PHP пакет или wrapper. Packagist, plugins и scripts са изключени. Версията и checksum са фиксирани в [composer.json](composer/composer.json) и [composer.lock](composer/composer.lock). Общите Composer зависимости не се обновяват. Конвертиранията не стартират Composer и не изтеглят файлове.

## Нов CLI и лиценз

Новият CLI се стартира с classpath към целия `lib/` и `org.jodconverter.cli.Convert`, защото няма `Main-Class` за старото `java -jar`. Управлява собствен временен Office процес, използва свободен порт и `--existing-process-action fail`, за да не прекратява чужд процес на зает порт. Запазени са `fconv_Script`, callback-ите, файловите индекси и общите ключалки. При `3.0b4` остават досегашната Java команда, locale, Office подготовка и брояч.

[Новостите в 5.1.1](https://jodconverter.github.io/jodconverter/latest/release-notes/release-notes-5.1.1/) включват по-точно съобщаване на грешна парола, еднакви размери при експорт на слайдове без зададен размер, по-подходящо ниво на Office stderr логовете и отделно изключение при несъществуваща страница. Те са upstream възможности; bgERP не добавя нови настройки за тях в този пакет.

Старият включен изходен код на 3.0b4 е LGPL-3.0-or-later. [JODConverter 5.1.1 е Apache-2.0](https://jodconverter.github.io/jodconverter/latest/license/). [Apache-2.0 е съвместим с GPLv3](https://www.apache.org/licenses/GPL-compatibility.html). Composer запазва целия официален комплект с LICENSE, NOTICE и лицензите в зависимостите. Java комплектът включва JODConverter core/local/remote/cli 5.1.1, UNO библиотеките 4.1.2, Commons IO 2.22.0, Commons CLI 1.11.0, Gson 2.14.0, SLF4J 2.0.20, Log4j 2.26.1, SnakeYAML Engine 3.2 и Error Prone annotations 2.48.0. Те са отделни от PHP autoloader-а на приложението.

Използван е [официалният ZIP](https://github.com/jodconverter/jodconverter/releases/download/v5.1.1/jodconverter-cli-5.1.1.zip). Провереният SHA-256 е `9def3e0b9dc0b1bfbde5fd4d1e09e02bf16b804b493a1986a716d6137a41fb46`, съвпадащ с digest на release asset. Composer проверява фиксирания SHA-1 `dist.shasum`. Detached PGP подписът не е проверяван.

## Повторими тестове

От корена на хранилището, с Java, LibreOffice, `pdftotext`, PHP mbstring и zip:

```sh
php docoffice/tests/config.php
jod_test_dir=$(mktemp -d)
BGERP_TEST_COMPOSER="$jod_composer_phar" php docoffice/tests/convert.php 5.1.1 "$jod_test_dir"
jod_old_dir=$(mktemp -d)
php docoffice/tests/convert.php 3.0b4 "$jod_old_dir"
```

`jod_composer_phar` трябва да сочи към наличен Composer PHAR. Тестът пропуска bootstrap/self-update. Използва отделен Composer проект в изходната директория; `BGERP_TEST_VENDOR_PATH` може да зададе друга тестова база. Незадължителен трети аргумент на `convert.php` е предишният `Jodconverter.class.php` за сравнение. Не се използва база данни. Framework/fconv услугите са изолирани, а Java и Office са реални.

Проверени са PHP 7.4.33 + Java 21.0.12.1 + LibreOffice 24.2.7.2 и PHP 8.2.34 + Java 17.0.21 + LibreOffice 7.4.7. За всяка среда са изпълнени шест конвертирания с новата версия, шест със старата през променения адаптер и шест с предишния адаптер: HTML, RTF, DOCX, DOCX с кирилско име/интервал/апостроф, XLSX и HTML с 500 реда. Проверени са кирилицата, `00123`, `37,50 EUR`, таблицата и краят на големия документ. PDF страниците са визуално прегледани за новата версия. Допълнително са проверени CSV -> XLS -> PDF с 3.0b4 и 5.1.1 на двата PHP runtime-а: BIFF сигнатура, кирилица и числови суми. Office тестовете в среди със споделен списък от процеси се изпълняват последователно, за да не се пресичат временните Office портове.

Старата версия дава точно същия извлечен текст през предишния и променения адаптер и на двата PHP runtime-а. Новата запазва текста. В средата с LibreOffice 7.4 големият HTML има различно страниране: 19 A4 вместо 20 Letter; разликата в текста е само в разделителите на страниците. Новият CLI използва `C.UTF-8`, старият запазва `en_US.UTF-8`. За стария CLI трябва да е наличен този locale, включително за кирилски файлови имена. Оформлението зависи от Office, locale и наличните шрифтове; не се обещава двоично идентичен PDF.

`config.php` проверява липсваща/неразпозната Java, Java 8/11/17/21, липсващ Office, отказ преди Composer и запазване на defaults/стария конвертор/Unoconv. Общият installer е проверен и с `apachetika/tests/install.php` на PHP 7.4 и 8.2: чиста/повторна инсталация, checksum отказ, възстановяване на средата след успех/грешка/изключение и непроменен общ Composer проект.

Браузърната проверка използва синтетичен DOCX, действителни `fileman_webdrv_Office::convertToFile()`, `fconv_Script` и асинхронен callback със запазване на PDF. На двата runtime-а са проверени новата версия и връщане `5.1.1 -> 3.0b4 -> 5.1.1`. Проверен е и стандартният графичен preview (Office -> PDF -> Ghostscript -> JPG) с 5.1.1 на двата runtime-а. Проверена е стандартната грешка в `core_Packs` със симулирана Java 11 само в тестовата заявка. За повторение създавайте документ с ново съдържание след смяна на версията, за да не сравнявате кеширан PDF.

Не са проверявани всички Office формати, защитени с парола документи, OpenOffice, Windows, отдалечени конвертиращи сървъри и конкурентно натоварване. Тези проверки не са гаранция за идентично оформление на всички бизнес документи.
