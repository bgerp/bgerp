<?php


/**
 * Класа, който ще се използва за конвертиране
 */
defIfNot('OFFICE_CONVERTER_CLASS', 'docoffice_Unoconv');


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
class docoffice_Setup
{
    
    
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
        'OFFICE_CONVERTER_CLASS' => array ('class(interface=docoffice_ConverterIntf, select=title)', 'mandatory'),
    
        // Коя програма да се използва за конвертиране
        'OFFICE_CONVERTER_UNOCONV' => array ('varchar', ''),
        
        // Кой python да се използва
    	'OFFICE_CONVERTER_PYTHON' => array ('varchar', ''),
    );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'docoffice_Jodconverter',
            'docoffice_Unoconv',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Проверяваме дали офис пакета е инсталиран и работи коректно
        if (docoffice_Office::startOffice()) {
            $html .= "<li style='color:green'>Офис пакета работи коректно.";  
        } else {
            $html .= "<li style='color:red'>Не може да бъде стартиране офис пакета.";
        }
        
        // Конфигурационните константи
        $conf = core_Packs::getConfig('docoffice');
        $unoconv = $conf->OFFICE_CONVERTER_UNOCONV;
        exec($unoconv, $dummy, $errorCode);
        
        // Ако програмата не е инсталирана
        if ($errorCode == 127) {
            $html .= "<li style='color:red'>Програмата '{$unoconv}' не е инсталирана.";  
        } else {
            $html .= "<li style='color:green'>Програмата '{$unoconv}' работи коректно.";
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
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}