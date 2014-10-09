<?php



/**
 * class tremol_Setup
 *
 * Инсталиране/Деинсталиране на
 * Драйвър за работа на POS модула с фискален принтер на Тремол
 *
 *
 * @category  vendors
 * @package   tremol
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tremol_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Фискален принтер на Тремол";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    { 
        $html = parent::install();
        
        // Добавяме драйвъра в core_Classes
        $html .= core_Classes::add('tremol_FiscPrinterDriver');
        
        return $html;
    }
}