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


/**
 *
 * @category  bgerp
 * @package   docoffice
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class docoffice_Setup extends core_ProtoSetup
{
    
    
    /**
     * От кои други пакети зависи
     */
    var $depends = 'permanent=0.1';
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Конвертиране на документи";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
        // Кой клас да се използва за конвертиране на офис документи
        'OFFICE_CONVERTER_CLASS' => array ('class(interface=docoffice_ConverterIntf, select=title)', 'mandatory, caption=Кой клас да се използва за конвертиране на офис документи->Клас'),
        
    	'OFFICE_PREVIEW_WIDTH'   => array ('int', 'caption=Размер на изгледа в широк режим->Широчина,unit=pix'),
           
    	'OFFICE_PREVIEW_HEIGHT'   => array ('int', 'caption=Размер на изгледа в широк режим->Височина,unit=pix'), 

    	'OFFICE_PREVIEW_WIDTH_NARROW'   => array ('int', 'caption=Размер на изгледа в мобилен режим->Широчина,unit=pix'),

    	'OFFICE_PREVIEW_HEIGHT_NARROW'   => array ('int', 'caption=Размер на изгледа в мобилен режим->Височина,unit=pix'), 
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'docoffice_Jodconverter',
            'docoffice_Unoconv',
        );

        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
      	$html = parent::install();
      	
        // Проверяваме дали офис пакета е инсталиран и работи коректно
        if (docoffice_Office::startOffice()) {
            $html .= "<li>Офис пакета работи коректно.";  
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
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}