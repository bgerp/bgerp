<?php


/**
 * class tsm_Setup
 *
 * Инсталиране/Деинсталиране на драйвери за устройствата на TSM - Ireland 
 *
 * @category  bgerp
 * @package   tsm
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tsm_Setup extends core_ProtoSetup
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
    var $info = "Драйвери за гравиметрични системи на TSM - Ирландия";
    
            
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
                                 
        // Добавяме наличните драйвери
        $drivers = array(
            'tsm_TSM',
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
        }
         
        return $html;
    }
    
}
