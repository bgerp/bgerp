<?php



/**
 * Плъгин за Регистрите, който им добавя възможност обекти от регистрите да влизат като пера
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_Registry extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        $mvc->interfaces = arr::make($mvc->interfaces);
        $mvc->interfaces['acc_RegisterIntf'] = 'acc_RegisterIntf';
        
        // Подсигуряваме, че първичния ключ на регистъра-приемник ще се запомни преди изтриване
        $mvc->fetchFieldsBeforeDelete = arr::make($mvc->fetchFieldsBeforeDelete, TRUE);
        $mvc->fetchFieldsBeforeDelete['id'] = 'id';
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            acc_Lists::updateItem($mvc, $rec->id, NULL);
        }
    }
    
    
    /**
     * Допустимите номенклатури минус евентуално $autoList номенклатурата.
     *
     * @param core_Mvc $mvc
     * @return array
     */
    private static function getSelectableLists($mvc)
    {
        if ($suggestions = acc_Lists::getPossibleLists($mvc)) {
            if (!empty($mvc->autoList)) {
                $autoListId = acc_Lists::fetchField(array("#systemId = '[#1#]'", $mvc->autoList), 'id');
                
                if (isset($suggestions[$autoListId])) {
                    unset($suggestions[$autoListId]);
                }
            }
        }
        
        return $suggestions;
    }
}
