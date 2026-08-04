<?php


/**
 * API ключ за връзка с Doubleword.ai
 */
defIfNot('DOUBLEWORD_API_KEY', '');


/**
 * URL за OpenAI-съвместимия Chat Completions endpoint
 */
defIfNot('DOUBLEWORD_API_URL', 'https://api.doubleword.ai/v1/chat/completions');


/**
 * Моделът, който ще се използва за OCR
 */
defIfNot('DOUBLEWORD_OCR_MODEL', 'allenai/olmOCR-2-7B-1025-FP8');


/**
 * Път до pdftoppm за преобразуване на PDF страници в изображения
 */
defIfNot('DOUBLEWORD_PDFTOPPM_PATH', 'pdftoppm');


/**
 * Максимален брой страници в един PDF документ
 */
defIfNot('DOUBLEWORD_MAX_PDF_PAGES', 20);


/**
 * Максимално време за локалното преобразуване на един PDF
 */
defIfNot('DOUBLEWORD_PDF_RENDER_TIMEOUT', 300);


/**
 * Адаптер за OCR с olmOCR-2-7B в Doubleword.ai
 *
 * @category  vendors
 * @package   doubleword
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doubleword_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Зависимости на пакета
     */
    public $depends = 'fileman=0.1,fconv=0.1,thumb=0.1,markdown=0.1';


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
    public $info = 'Адаптер за Doubleword.ai olmOCR-2-7B - разпознаване на текст в сканирани документи';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'doubleword_Converter',
    );


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'DOUBLEWORD_API_KEY' => array('password', 'caption=API ключ, mandatory'),
        'DOUBLEWORD_API_URL' => array('Url', 'caption=API URL за OCR->URL,mandatory'),
        'DOUBLEWORD_OCR_MODEL' => array('varchar(128)', 'caption=OCR модел,mandatory'),
        'DOUBLEWORD_PDFTOPPM_PATH' => array('varchar(255)', 'caption=Път до pdftoppm->Път'),
        'DOUBLEWORD_MAX_PDF_PAGES' => array('int(min=1,max=20)', 'caption=PDF документи->Максимален брой страници'),
        'DOUBLEWORD_PDF_RENDER_TIMEOUT' => array('int(min=30,max=1800)', 'caption=PDF документи->Максимално време за преобразуване, unit=сек.'),
    );


    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        $conf = core_Packs::getConfig('fileman');
        $classId = doubleword_Converter::getClassId();
        if ($classId && !empty($conf->_data['FILEMAN_OCR']) &&
            ($conf->_data['FILEMAN_OCR'] == $classId)) {
            core_Packs::setConfig('fileman', array('FILEMAN_OCR' => null));
            $html .= "<li class=\"green\">Премахнат е 'doubleword_Converter' от конфигурацията</li>";
        }

        return $html;
    }


    /**
     * Проверява конфигурацията на пакета
     *
     * @return null|string
     */
    public function checkConfig()
    {
        if (!strlen(trim((string) self::get('API_KEY')))) {

            return 'Не е зададен API ключ за връзка с Doubleword.ai';
        }

        if (!strlen(trim((string) self::get('API_URL')))) {

            return 'Не е зададен API адресът за връзка с Doubleword.ai';
        }

        if (!strlen(trim((string) self::get('OCR_MODEL')))) {

            return 'Не е зададен OCR модел за Doubleword.ai';
        }

        if (!function_exists('curl_init') || !function_exists('curl_multi_init')) {

            return 'PHP разширението cURL не е инсталирано.';
        }

        if (!function_exists('imagecreatefromstring')) {

            return 'PHP разширението GD не е инсталирано.';
        }

        if (core_Os::isWindows()) {

            return 'Doubleword PDF OCR изисква Linux/Unix среда с GNU timeout и pdftoppm.';
        }

        $pdftoppm = trim((string) self::get('PDFTOPPM_PATH'));
        $found = false;
        if (strpos($pdftoppm, '/') !== false || strpos($pdftoppm, '\\') !== false) {
            $found = is_file($pdftoppm) && is_executable($pdftoppm);
        } elseif ($pdftoppm !== '') {
            $res = @exec('which ' . escapeshellarg($pdftoppm), $output, $code);
            $found = ($res && $code == 0);
        }

        if (!$found) {

            return 'Програмата ' . type_Varchar::escape($pdftoppm) . ' не е инсталирана.';
        }

        $timeout = @exec('which timeout', $timeoutOutput, $timeoutCode);
        if (!$timeout || $timeoutCode != 0) {

            return 'Програмата timeout от GNU coreutils не е инсталирана.';
        }

        $timeoutVersionOutput = array();
        @exec(escapeshellarg(trim($timeout)) . ' --version 2>&1', $timeoutVersionOutput, $timeoutVersionCode);
        if ($timeoutVersionCode != 0 ||
            stripos(implode("\n", $timeoutVersionOutput), 'GNU coreutils') === false) {

            return 'Инсталираната програма timeout не е GNU coreutils и не поддържа нужните опции.';
        }
    }
}
