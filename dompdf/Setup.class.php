<?php



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