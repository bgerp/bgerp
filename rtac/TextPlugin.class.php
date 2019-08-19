<?php


/**
 * Плъгин за autocomplete в type_Text
 *
 * @category  bgerp
 * @package   rtac
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rtac_TextPlugin extends core_Plugin
{
    /**
     *
     * Изпълнява се преди рендирането на input
     *
     * @param core_Mvc $invoker
     * @param core_Et  $tpl
     * @param string   $name
     * @param string   $value
     * @param array    $attr
     */
    public function on_BeforeRenderInput(&$mvc, &$ret, $name, $value, &$attr = array())
    {
        // Задаваме уникално id
        ht::setUniqId($attr);
    }
    
    
    /**
     *
     * Изпълнява се след рендирането на input
     *
     * @param core_Mvc $invoker
     * @param core_Et  $tpl
     * @param string   $name
     * @param string   $value
     * @param array    $attr
     */
    public function on_AfterRenderInput(&$mvc, &$tpl, $name, $value, $attr = array())
    {
        $conf = core_Packs::getConfig('rtac');
        
        // Интанция на избрания клас
        $inst = cls::get($conf->RTAC_AUTOCOMPLETE_CLASS);
        
        // Зареждаме необходимите пакети
        $inst->loadPacks($tpl);
        
        // id на ричтекста
        list($id) = explode(' ', $attr['id']);
        $id = trim($id);
        
        // Обект за данните
        $tpl->appendOnce('var rtacObj = {};', 'SCRIPTS');
        
        // Ако не са зададени права в параметрите
        if (!($userRolesForTextComplete = $mvc->params['userRolesForTextComplete'])) {
            $userRolesForTextComplete = $conf->RTAC_DEFAULT_ROLES_FOR_TEXTCOMPLETE;
        }
        
        // Ако потребителя има права за добавяне на блокови елементи
        if (core_Users::haveRole($userRolesForTextComplete) && ($suggestionsArr = $mvc->getSuggestions())) {
            unset($suggestionsArr['']);
            if ($suggestionsArr) {
                // Добавяме данните
                $tpl->appendOnce('rtacObj.textCompleteObj = {};', 'SCRIPTS');
                
                $tpl->appendOnce('rtacObj.textCompleteStrEnd = {};', 'SCRIPTS');
                if (!isset($mvc->params['strAfterSuggestion'])) {
                    $strEnd = ' ';
                } elseif ($mvc->params['strAfterSuggestion'] == 'strAfterSuggestion') {
                    $strEnd = '';
                } else {
                    $strEnd = $mvc->params['strAfterSuggestion'];
                }
                
                $tpl->appendOnce("rtacObj.textCompleteStrEnd.{$id} = " . json_encode($strEnd) . ';', 'SCRIPTS');
                
                $tpl->appendOnce("rtacObj.textCompleteObj.{$id} = " . json_encode($suggestionsArr) . ';', 'SCRIPTS');
                
                // Стартираме скрипта
                $inst->runAutocompleteText($tpl, $id);
            }
        }
    }
}
