<?php

/**
 * Разделителна способност по подразбиране
 */
defIfNot("DOMPDF_DPI", "120");

/**
 * @todo Чака за документация...
 */
defIfNot('DOMPDF_VER', '3.0');


/**
 * Дефинира име на папка в която ще се съхраняват временните данни данните
 */
defIfNot('DOMPDF_TEMP_DIR', EF_TEMP_PATH . "/dompdf");

/**
 * Възможност да се използват ресурси от Интернет
 */
 defIfNot("DOMPDF_ENABLE_REMOTE", TRUE);

 
/**
 * class dompdf_Setup
 *
 * Инсталиране/Деинсталиране на
 * пакета за конвертиране html -> pdf
 *
 *
 * @category  vendors
 * @package   dompdf
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dompdf_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    /**
     * Мениджър - входна точка в пакета
     */
    // var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    // var $startAct = 'default';
    
    
    
    /**
     * Описание на модула
     */
    var $info = "Конвертиране .html => .pdf";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
            'DOMPDF_VER' => array ('double'),
            'DOMPDF_DPI'   => array ('int'),
        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'dompdf_Converter'
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
    }
}