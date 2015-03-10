<?php


/**
 * class teracom_Setup
 *
 * Драйвери за електромер SATEC - Израел 
 *
 * @category  bgerp
 * @package   satec
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class satec_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    var $depends = '';
    
      
    /**
     * Описание на модула
     */
    var $info = "Драйвер за електромер SATEC - Израел";
    
            
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
                                 
        // Добавяме наличните драйвери
        $drivers = array(
            'satec_PM175',
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
        }
         
        return $html;
    }
    
}