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
        'OFFICE_CONVERTER_UNOCONV' => array ('varchar', 'mandatory'),
        
        // Кой python да се използва
    	'OFFICE_CONVERTER_PYTHON' => array ('varchar', ''),
    );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'docoffice_Unoconv',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
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