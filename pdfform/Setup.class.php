<?php


/**
 * Път до Python интерпретатора с инсталиран pypdf
 */
defIfNot('PDFFORM_PYTHON_PATH', '');


/**
 * Четене и попълване на PDF формуляри - полета (AcroForm), текстови и сканирани бланки
 *
 * Полетата се обработват с Python библиотеката pypdf, която се инсталира автоматично
 * в собствен venv в EF_VENDOR_PATH. Текстът върху страниците се пише с TCPDF.
 *
 * @category  bgerp
 * @package   pdfform
 *
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 * @link      https://github.com/py-pdf/pypdf
 */
class pdfform_Setup extends core_ProtoSetup
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
    public $info = 'Четене и попълване на PDF формуляри (pypdf)';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PDFFORM_PYTHON_PATH' => array('varchar', 'caption=Python с инсталиран pypdf->Път'),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Ако pypdf липсва - опитваме да го инсталираме в собствен venv
        $error = $this->checkConfig();
        if ($error) {
            $html .= $this->installVenv();
            $error = $this->checkConfig();
        }

        if ($error) {
            $html .= "<li class='debug-error'>{$error}</li>";
        } else {
            $html .= "<li class='debug-new'>Намерен е <b>pypdf</b> (" . type_Varchar::escape(self::get('PYTHON_PATH')) . ')</li>';
        }

        return $html;
    }


    /**
     * Инсталира pypdf в собствен Python venv в EF_VENDOR_PATH, без root права - както markitdown
     *
     * @return string
     */
    private function installVenv()
    {
        if (core_Os::isWindows()) {

            return "<li class='debug-error'><strong>pdfform</strong>: няма автоматична инсталация за Windows - инсталирайте pypdf ръчно и задайте PDFFORM_PYTHON_PATH</li>";
        }

        // Дефинира EF_VENDOR_PATH, ако core_Composer още не е зареждан
        cls::load('core_Composer');

        $vPath = rtrim(EF_VENDOR_PATH, '/');
        $envDir = $vPath . '/pdfform';
        $python = $envDir . '/bin/python';

        if (!is_executable($python)) {
            $sysPython = trim((string) shell_exec('command -v python3 2>/dev/null'));
            if (!strlen($sysPython)) {

                return "<li class='debug-error'><strong>pdfform</strong>: не е намерен `python3` - инсталирайте го с root права (apt install python3) и пуснете инсталацията отново</li>";
            }

            $pVer = trim((string) shell_exec(escapeshellarg($sysPython) . ' -c "import sys; print(\"%d.%d\" % sys.version_info[:2])" 2>/dev/null'));
            if (!strlen($pVer) || version_compare($pVer, '3.9', '<')) {

                return "<li class='debug-error'><strong>pdfform</strong>: наличният python3 е версия `" . type_Varchar::escape($pVer) . "`, а pypdf изисква >= 3.9</li>";
            }

            if (!is_dir($vPath) && !@mkdir($vPath)) {

                return "<li class='debug-error'><strong>pdfform</strong>: няма права за създаване на `{$vPath}`</li>";
            }

            // Без pip във venv-а - така не е нужен apt пакетът python3-venv
            exec(escapeshellarg($sysPython) . ' -m venv --without-pip ' . escapeshellarg($envDir) . ' 2>&1', $output, $code);
            if ($code != 0) {

                return "<li class='debug-error'><strong>pdfform</strong>: грешка при създаване на venv: " . type_Varchar::escape(implode('; ', (array) $output)) . '</li>';
            }
        }

        core_App::setTimeLimit(600);

        $pipPyz = $vPath . '/pdfform-pip.pyz';
        if (!file_exists($pipPyz) && !@copy('https://bootstrap.pypa.io/pip/pip.pyz', $pipPyz)) {

            return "<li class='debug-error'><strong>pdfform</strong>: грешка при изтегляне на `https://bootstrap.pypa.io/pip/pip.pyz`</li>";
        }

        // crypto е за формулярите, защитени с AES
        exec(escapeshellarg($python) . ' ' . escapeshellarg($pipPyz) . ' install "pypdf[crypto]>=5" 2>&1', $pipOut, $pipCode);
        @unlink($pipPyz);

        if ($pipCode != 0) {

            return "<li class='debug-error'><strong>pdfform</strong>: грешка при pip install: " . type_Varchar::escape(implode('; ', array_slice((array) $pipOut, -5))) . '</li>';
        }

        // Записваме пътя и инвалидираме кеша на конфигурацията за повторната проверка
        core_Packs::setConfig('pdfform', array('PDFFORM_PYTHON_PATH' => $python));
        unset(core_ProtoSetup::$conf['pdfform']);

        return "<li class='debug-new'><strong>pdfform</strong>: pypdf е инсталиран в собствен venv `{$envDir}`</li>";
    }


    /**
     * Проверява дали Python с pypdf е наличен
     *
     * @return NULL|string
     */
    public function checkConfig()
    {
        $python = trim((string) self::get('PYTHON_PATH'));
        if (!strlen($python) || !is_executable($python)) {

            return 'Не е намерен Python с pypdf (' . type_Varchar::escape($python) . '). Инициализирайте пакета pdfform, за да се инсталира автоматично в собствен venv.';
        }

        @exec(escapeshellarg($python) . ' -I -c "import pypdf" 2>&1', $output, $code);
        if ($code != 0) {

            return 'Python библиотеката pypdf не е инсталирана за ' . type_Varchar::escape($python) . '. Инициализирайте пакета pdfform отново.';
        }
    }
}
