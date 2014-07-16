<?php


/**
 * Плъгин за autocomplete в ричтекста
 *
 * @category  vendors
 * @package   rtac
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rtac_Plugin extends core_Plugin
{
    
    
    /**
     * 
     * Изпълнява се преди рендирането на input
     * 
     * @param core_Mvc $invoker
     * @param core_Et $tpl
     * @param string $name
     * @param string $value
     * @param array $attr
     */
    function on_BeforeRenderInput(&$mvc, &$ret, $name, $value, &$attr = array())
    {
        // Задаваме уникално id
        ht::setUniqId($attr);
    }
    
    
    /**
     * 
     * Изпълнява се след рендирането на input
     * 
     * @param core_Mvc $invoker
     * @param core_Et $tpl
     * @param string $name
     * @param string $value
     * @param array $attr
     */
    function on_AfterRenderInput(&$mvc, &$tpl, $name, $value, $attr = array())
    {
        $conf = core_Packs::getConfig('rtac');
        
        // Интанция на избрания клас
        $inst = cls::get($conf->RTAC_AUTOCOMPLETE_CLASS);
        
        // Зареждаме необходимите пакети
    	$inst->loadPacks($tpl);        
    	
    	// Стартираме autocomplete-a за добавяне на потребител
        $inst->runAutocompleteUsers($tpl,$attr['id']);
        
        // Добавяме масива с потребителите в JS
        $usersArr = core_Users::getUsersArr();
        $tpl->appendOnce("var sharedUsers=" . json_encode($usersArr), 'SCRIPTS');
    }
}
