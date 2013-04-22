<?php
/**
 * Плъгин, който се прикача към мениджъри, съдържащи информация за експедируема стока.
 * 
 * @category  bgerp
 * @package   store
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class store_plg_Shippable extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('store_ShipmentIntf');
    }
    
    
    /**
     * Подготовка на бутоните в единичния тулбар
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar(core_Mvc $mvc, $data)
    {
        /* @var $toolbar core_Toolbar */ 
        $toolbar = $data->toolbar;

        if ($mvc->haveRightFor('ship', $data->rec)) {
            $toolbar->addBtn('Експедиране', array('store_ShipmentOrders', 'add', 'originId'=>$data->rec->containerId, 'ret_url'=>true), array('class'=>'btn-add'));
        }
    }
    
    
    /**
     * Определяне на ролите, допускани то зададено действие
     * 
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
        if ($action == 'ship') {
            if ($rec->state != 'active') {
                $requiredRoles = 'no_one';
            }
        }
    }
}