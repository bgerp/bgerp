<?php


/**
 * Класа, който ще се използва за конвертиране
 */
defIfNot('OFFICE_CONVERTER_CLASS', 'docoffice_Jodconverter');


/**
 * Широчината на preview' то
 */
defIfNot('OFFICE_PREVIEW_WIDTH', 850);


/**
 * Височината на preview' то
 */
defIfNot('OFFICE_PREVIEW_HEIGHT', 1200);


/**
 * Широчината на preview' то в мобилен режим
 */
defIfNot('OFFICE_PREVIEW_WIDTH_NARROW', 560);


/**
 * Височината на preview' то в мобилен режим
 */
defIfNot('OFFICE_PREVIEW_HEIGHT_NARROW', 800);


/**
 * Пътя до python' a, който искаме да използваме
 */
defIfNot('OFFICE_CONVERTER_PYTHON', '');


/**
 * Пътя до unoconv' a, който искаме да използваме
 */
defIfNot('OFFICE_CONVERTER_UNOCONV', 'unoconv');


/**
 * Версията на JodConverter'а
 */
defIfNot('OFFICE_JODCONVERTER_VERSION', '3.0b4');


/** Версия на отделния Unoserver адаптер. */
defIfNot('OFFICE_UNOSERVER_VERSION', '3.7');


/** Python с UNO модула на инсталирания LibreOffice. */
defIfNot('OFFICE_UNOSERVER_PYTHON', 'python3');


/**
 *
 * @category  bgerp
 * @package   docoffice
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class docoffice_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Конвертиране на документи';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // Кой клас да се използва за конвертиране на офис документи
        'OFFICE_CONVERTER_CLASS' => array('class(interface=docoffice_ConverterIntf, select=title)', 'mandatory, refreshForm, caption=Кой клас да се използва за конвертиране на офис документи->Клас'),

        'OFFICE_JODCONVERTER_VERSION' => array('enum(3.0b4,5.1.1)', 'mandatory, caption=JodConverter->Версия'),

        'OFFICE_UNOSERVER_VERSION' => array('enum(3.7)', 'mandatory, caption=Unoserver->Версия'),

        'OFFICE_UNOSERVER_PYTHON' => array('varchar', 'mandatory, caption=Unoserver->Python с UNO'),
        
        'OFFICE_PREVIEW_WIDTH' => array('int', 'caption=Размер на изгледа в широк режим->Широчина,unit=pix'),
        
        'OFFICE_PREVIEW_HEIGHT' => array('int', 'caption=Размер на изгледа в широк режим->Височина,unit=pix'),
        
        'OFFICE_PREVIEW_WIDTH_NARROW' => array('int', 'caption=Размер на изгледа в мобилен режим->Широчина,unit=pix'),
        
        'OFFICE_PREVIEW_HEIGHT_NARROW' => array('int', 'caption=Размер на изгледа в мобилен режим->Височина,unit=pix'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'docoffice_Jodconverter',
        'docoffice_Unoconv',
        'docoffice_Unoserver',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $converter = cls::getClassName(self::get('OFFICE_CONVERTER_CLASS', true));
        $unoserver = $converter === 'docoffice_Unoserver';
        if ($unoserver || ($converter === 'docoffice_Jodconverter'
            && self::get('OFFICE_JODCONVERTER_VERSION', true) === '5.1.1')) {
            if ($error = $this->checkConfig()) {
                return $html . "<li class='debug-error'>" . $error . '</li>';
            }
            $label = $unoserver ? 'Unoserver 3.7' : 'JODConverter 5.1.1';
            $result = core_ComposerProject::install(__DIR__ . ($unoserver ? '/composer-unoserver' : '/composer'), self::getComposerDir($unoserver));
            if ($result === false) {
                return $html . "<li class='debug-error'>{$label} Composer: " . core_Type::escape(core_Composer::$error ?? '') . '</li>';
            }
            if ($unoserver) {
                self::getUnoserverPath();
            } else {
                self::getJodconverterPath();
            }

            return $html . "<li class='debug-new'>Composer: {$label} е инсталиран</li>";
        }
        
        // Проверяваме дали офис пакета е инсталиран и работи коректно
        if (docoffice_Office::startOffice()) {
            $html .= '<li>Офис пакета работи коректно.';
        } else {
            $html .= "<li class='debug-error'>Не може да бъде стартиран офис пакета.";
        }
        
        // Конфигурационните константи
        $conf = core_Packs::getConfig('docoffice');
        $unoconv = $conf->OFFICE_CONVERTER_UNOCONV;
        @exec($unoconv, $dummy, $errorCode);
        
        // Ако програмата не е инсталирана
        if ($errorCode == 127) {
            $html .= "<li class='debug-error'>Програмата '{$unoconv}' не е инсталирана.";
        } else {
            $html .= "<li>Програмата '{$unoconv}' работи коректно.";
        }
        
        // Убиваме офис пакета
        docoffice_Office::killOffice();
        
        return $html;
    }


    /** Показва настройките на избрания адаптер, като запазва стойностите на останалите. */
    public function manageConfigDescriptionForm(&$configForm)
    {
        $configForm->input('OFFICE_CONVERTER_CLASS', 'silent');
        $converter = $configForm->rec->OFFICE_CONVERTER_CLASS ?? null;
        if (!$converter || cls::getClassName($converter) !== 'docoffice_Jodconverter') {
            $configForm->setField('OFFICE_JODCONVERTER_VERSION', 'input=hidden');
        }
        if (!$converter || cls::getClassName($converter) !== 'docoffice_Unoserver') {
            $configForm->setField('OFFICE_UNOSERVER_VERSION', 'input=hidden');
            $configForm->setField('OFFICE_UNOSERVER_PYTHON', 'input=hidden');
        }
    }


    /** Проверява изискванията на новия CLI, без да стартира или спира Office процеси. */
    public function checkConfig()
    {
        $converter = cls::getClassName(self::get('OFFICE_CONVERTER_CLASS', true));
        if ($converter === 'docoffice_Unoserver') {
            $probe = 'import sys, os; assert sys.version_info >= (3, 8) and os.name == "posix"; import uno';
            exec(escapeshellarg(self::get('OFFICE_UNOSERVER_PYTHON', true)) . ' -c ' . escapeshellarg($probe) . ' 2>&1', $output, $code);
            if ($code !== 0) {
                return 'Unoserver 3.7 изисква Python 3.8+ с UNO модула на LibreOffice (python3-uno) под Linux/Unix. Проверете избрания Python и достъпа на уеб потребителя.';
            }
        } elseif ($converter !== 'docoffice_Jodconverter'
            || self::get('OFFICE_JODCONVERTER_VERSION', true) !== '5.1.1') {
            return;
        } else {
            exec('java -version 2>&1', $output, $code);
            if ($code !== 0 || !preg_match('/(?:openjdk|java) (?:version )?"(?:1\\.)?(\\d+)/i', implode("\n", $output), $matches)
                || (int) $matches[1] < 17) {
                return 'JODConverter 5.1.1 изисква Java 17 или по-нова, достъпна за уеб потребителя.';
            }
        }
        cls::load('docoffice_Office');
        exec(escapeshellarg(OFFICE_PACKET_PATH) . ' --version 2>&1', $officeOutput, $code);
        if ($code !== 0) {
            return 'Конверторът изисква инсталиран LibreOffice/OpenOffice. Проверете OFFICE_PACKET_PATH и достъпа на уеб потребителя.';
        }
    }


    /** Връща отделния Composer проект на конвертора; досегашният път остава по подразбиране. */
    public static function getComposerDir($unoserver = false)
    {
        cls::load('core_Composer');

        return rtrim(EF_VENDOR_PATH, '/') . ($unoserver ? '/docoffice-unoserver' : '/docoffice');
    }


    /** Намира Composer доставката на Unoserver, без инсталиране при конвертиране. */
    public static function getUnoserverPath()
    {
        $path = self::getComposerDir(true) . '/vendor/bgerp/unoserver';
        expect(is_file($path . '/unoserver/server.py') && is_file($path . '/unoserver-3.7.dist-info/METADATA'),
            'Липсва Unoserver 3.7. Инициализирайте пакета docoffice');

        return $path;
    }


    /** Връща стария JAR или classpath на избраната нова дистрибуция, без изтегляне при конвертиране. */
    public static function getJodconverterPath($version = null)
    {
        $version = $version ?? self::get('OFFICE_JODCONVERTER_VERSION', true);
        if ($version === '5.1.1') {
            $lib = self::getComposerDir() . '/vendor/bgerp/jodconverter-cli/lib';
            expect(is_file($lib . '/jodconverter-cli-5.1.1.jar'), 'Липсва JODConverter 5.1.1. Инициализирайте пакета docoffice');

            return $lib . '/*';
        }

        return getFullPath('docoffice/jodconverter/' . $version . '/lib/jodconverter-core.jar');
    }
}
