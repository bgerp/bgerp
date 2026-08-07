<?php


/**
 * Път до изпълнимия файл `markitdown` (от Python пакета markitdown)
 */
defIfNot('MARKITDOWN_PATH', 'markitdown');


/**
 * Разширения на файловете, от които ще се извлича съдържание в markdown
 *
 * Изображенията (jpg, jpeg, png) съзнателно не са в списъка - за тях се използва OCR.
 * Аудио файловете също не са - за тях markitdown иска външна услуга за транскрибиране.
 */
defIfNot('MARKITDOWN_EXTENSIONS', 'pdf, docx, xlsx, xls, pptx, csv, epub, msg, ipynb, html, htm, xml, txt, text, md, markdown, json, jsonl, zip');


/**
 * Максимален размер на файловете, от които ще се извлича съдържание
 */
defIfNot('MARKITDOWN_MAX_FILE_LEN', 20971520); // 20 MB


/**
 * Максимална дължина на съдържанието, което се записва в индекса
 */
defIfNot('MARKITDOWN_MAX_CONTENT_LEN', 1000000);


/**
 * Максимален размер на архивите (компресиран), от които ще се извлича съдържание
 */
defIfNot('MARKITDOWN_MAX_ARCHIVE_LEN', 5242880); // 5 MB


/**
 * Максимален брой файлове в архива, от който ще се извлича съдържание
 */
defIfNot('MARKITDOWN_MAX_ARCHIVE_FILES', 100);


/**
 * Максимален размер на файловете в архива след разархивиране
 */
defIfNot('MARKITDOWN_MAX_ARCHIVE_CONTENT_LEN', 52428800); // 50 MB


/**
 * Дали да се използват инсталираните външни плъгини на markitdown
 */
defIfNot('MARKITDOWN_USE_PLUGINS', 'no');


/**
 * Извличане на съдържанието на файловете в markdown чрез MarkItDown (на Microsoft)
 *
 * Изисква инсталиран `markitdown` на сървъра (Python пакет, в отделен venv - вижте Readme.md),
 * а пълният път до него се задава в MARKITDOWN_PATH.
 *
 * @category  bgerp
 * @package   markitdown
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 * @link      https://github.com/microsoft/markitdown
 */
class markitdown_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';


    /**
     * Описание на модула
     */
    public $info = 'Извличане на съдържанието на файловете в markdown (MarkItDown)';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'markitdown_Converter',
    );


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'MARKITDOWN_PATH' => array('varchar', 'caption=Път до markitdown->Път'),
        'MARKITDOWN_EXTENSIONS' => array('varchar', 'caption=Разширения на файловете|*&comma;| от които ще се извлича съдържание->Разширения'),
        'MARKITDOWN_MAX_FILE_LEN' => array('fileman_FileSize', 'caption=Максимален размер на файловете|*&comma;| от които ще се извлича съдържание->Размер, suggestions=5 MB|10 MB|20 MB|50 MB'),
        'MARKITDOWN_MAX_CONTENT_LEN' => array('int(min=1000)', 'caption=Максимална дължина на извлеченото съдържание->Символи'),

        'MARKITDOWN_MAX_ARCHIVE_LEN' => array('fileman_FileSize', 'caption=Архиви->Максимален размер, suggestions=1 MB|5 MB|10 MB'),
        'MARKITDOWN_MAX_ARCHIVE_FILES' => array('int(min=1)', 'caption=Архиви->Максимален брой файлове'),
        'MARKITDOWN_MAX_ARCHIVE_CONTENT_LEN' => array('fileman_FileSize', 'caption=Архиви->Максимален размер след разархивиране, suggestions=10 MB|50 MB|100 MB'),

        'MARKITDOWN_USE_PLUGINS' => array('enum(no=Не, yes=Да)', 'caption=Използване на външните плъгини на markitdown->Избор'),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $error = $this->checkConfig();

        // Ако програмата липсва - опитваме да я инсталираме в собствен venv
        if ($error) {
            $html .= $this->installMarkitdownBin();
            $error = $this->checkConfig();
        }

        if ($error) {
            $html .= "<li class='debug-error'>{$error}</li>";
        } else {
            $html .= "<li class='debug-new'>Намерен е <b>markitdown</b> (" . type_Varchar::escape(self::get('PATH')) . ')</li>';
        }

        return $html;
    }


    /**
     * Инсталира markitdown в собствен Python venv в EF_VENDOR_PATH, без root права -
     * venv-ът се създава с --without-pip (не изисква apt пакета python3-venv),
     * а pip се подава като standalone zipapp (вижте Readme.md)
     *
     * @return string
     */
    private function installMarkitdownBin()
    {
        if (core_Os::isWindows()) {

            return "<li class='debug-error'><strong>markitdown</strong>: няма автоматична инсталация за Windows - инсталирайте ръчно и задайте MARKITDOWN_PATH</li>";
        }

        // Дефинира EF_VENDOR_PATH, ако core_Composer още не е зареждан
        cls::load('core_Composer');

        $vPath = rtrim(EF_VENDOR_PATH, '/');
        $envDir = $vPath . '/markitdown';
        $binFile = $envDir . '/bin/markitdown';

        if (!is_executable($binFile)) {

            // Търсим python3 >= 3.10
            $python = trim((string) shell_exec('command -v python3 2>/dev/null'));
            if (!strlen($python)) {

                return "<li class='debug-error'><strong>markitdown</strong>: не е намерен `python3` - инсталирайте го с root права (apt install python3) и пуснете инсталацията отново</li>";
            }

            $pVer = trim((string) shell_exec(escapeshellarg($python) . ' -c "import sys; print(\"%d.%d\" % sys.version_info[:2])" 2>/dev/null'));
            if (!strlen($pVer) || version_compare($pVer, '3.10', '<')) {

                return "<li class='debug-error'><strong>markitdown</strong>: наличният python3 е версия `{$pVer}`, а MarkItDown изисква >= 3.10</li>";
            }

            if (!is_dir($vPath) && !@mkdir($vPath)) {

                return "<li class='debug-error'><strong>markitdown</strong>: няма права за създаване на `{$vPath}`</li>";
            }

            core_App::setTimeLimit(600);

            // Създаваме venv без pip - така не е нужен apt пакетът python3-venv
            exec(escapeshellarg($python) . ' -m venv --without-pip ' . escapeshellarg($envDir) . ' 2>&1', $output, $code);
            if ($code != 0) {

                return "<li class='debug-error'><strong>markitdown</strong>: грешка при създаване на venv: " . type_Varchar::escape(implode('; ', (array) $output)) . '</li>';
            }

            // Изтегляме standalone pip (zipapp)
            $pipPyz = $vPath . '/pip.pyz';
            if (!file_exists($pipPyz) && !@copy('https://bootstrap.pypa.io/pip/pip.pyz', $pipPyz)) {

                return "<li class='debug-error'><strong>markitdown</strong>: грешка при изтегляне на `https://bootstrap.pypa.io/pip/pip.pyz`</li>";
            }

            // Инсталираме markitdown с нужните extras във venv-а
            $cmd = escapeshellarg($envDir . '/bin/python') . ' ' . escapeshellarg($pipPyz) . ' install "markitdown[pdf,docx,xlsx,xls,pptx,outlook]" 2>&1';
            exec($cmd, $pipOut, $pipCode);
            @unlink($pipPyz);

            if ($pipCode != 0) {

                return "<li class='debug-error'><strong>markitdown</strong>: грешка при pip install: " . type_Varchar::escape(implode('; ', array_slice((array) $pipOut, -5))) . '</li>';
            }

            if (!is_executable($binFile)) {

                return "<li class='debug-error'><strong>markitdown</strong>: `{$binFile}` липсва след инсталацията</li>";
            }
        }

        // Записваме пътя в конфигурацията и инвалидираме кеша й,
        // за да го види повторната проверка в текущата заявка
        core_Packs::setConfig('markitdown', array('MARKITDOWN_PATH' => $binFile));
        unset(core_ProtoSetup::$conf['markitdown']);

        return "<li class='debug-new'><strong>markitdown</strong>: инсталиран е в собствен venv `{$envDir}`</li>";
    }


    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();

        // Вземаме конфига
        $conf = core_Packs::getConfig('fileman');

        // Ако текущия клас е избран по подразбиране, премахваме го
        $classId = markitdown_Converter::getClassId();

        if ($classId && !empty($conf->_data['FILEMAN_MARKDOWN']) && ($conf->_data['FILEMAN_MARKDOWN'] == $classId)) {
            $data = array();
            $data['FILEMAN_MARKDOWN'] = null;

            core_Packs::setConfig('fileman', $data);

            $html .= "<li class=\"green\">Премахнат е 'markitdown_Converter' от конфигурацията</li>";
        }

        return $html;
    }


    /**
     * Проверява дали програмата е инсталирана на сървъра
     *
     * @return NULL|string
     */
    public function checkConfig()
    {
        if (fconv_Remote::canRunRemote('markitdown')) {

            return ;
        }

        $markitdown = escapeshellcmd(self::get('PATH'));

        $haveError = false;

        if (core_Os::isWindows()) {
            @exec($markitdown . ' --version', $output, $code);
            if ($code != 0) {
                $haveError = true;
            }
        } else {
            if (!(is_executable($markitdown) || @exec('which ' . $markitdown))) {
                $haveError = true;
            }
        }

        if ($haveError) {

            return 'Не е намерена програмата ' . type_Varchar::escape(self::get('PATH')) .
                '. Инсталира се в отделен venv (системният pip е заключен - PEP 668), а пълният път до `bin/markitdown` се задава тук. Вижте markitdown/Readme.md';
        }
    }
}
