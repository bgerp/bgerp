<?php



/**
 * class bnav_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   bnav
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bnav_Setup
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
    var $info = "Плъгин за работа с Бизнес навигатор";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    { 
        $html = '';
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('bnavPlugin', 'bnav_Plugin', 'cat_Products', 'private');
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('bnav_importCsv', 'CSV на Бизнес навигатор', 'csv', '10MB', 'user', 'every_one');
        
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
         // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        if($delCnt = $Plugins->deinstallPlugin('bnav_Plugin')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'keyboard_Plugin'";
        } else {
            $html .= "<li>Не са премахнати закачания на плъгина";
        }
        
        return $html;
    }
}