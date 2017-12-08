<?php


/**
 * 
 *
 * @category  vendors
 * @package   escpos
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class escpos_PrintPlg extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->canAgentprint, 'powerUser');
    }
    
    
    /**
     * 
     * @param core_Manager $mvc
     * @param stdObject $res
     * @param stdObject $data
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Добавяме бутон за тестово отпечатване в bluetooth принтер
        if (isset($data->rec->id) && $mvc->haveRightFor('Agentprint', $data->rec)) {
        	$data->toolbar->addBtn('MP', $mvc->prepareLinkForAgent($data->rec->id),
        			"id=escpos_{$data->rec->containerId},class=fright,row=2, order=38,title=" . "Печат чрез bgERP Agent",  'ef_icon = img/16/print_go.png');
        }
    }
    
    
    /**
     * Поготвя URL за печатане с bgERP агент
     * 
     * @param core_Mvc $mvc
     * @param NULL|string $res
     * @param int $id
     */
    protected static function on_AfterPrepareLinkForAgent($mvc, &$res, $id)
    {
        $res = 'bgerp://print/' . escpos_Print::prepareUrlIdForAgent($mvc, $id);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'agentprint') {
            if (!$requiredRoles != 'no_one' && $rec) {
                if (($rec->state == 'rejected') || ($rec->state == 'draft')) {
                    $requiredRoles = 'no_one';
                }
            }
            
            if (!$requiredRoles != 'no_one') {
                if (!$mvc->haveRightFor('single', $rec, $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
