<?php


/**
 * Онлайн поръчка по Н-18 има един документ за доставка и един за плащане - връщанията са свободни
 *
 *
 * @category  bgerp
 * @package   eshopn18
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshopn18_plg_SingleDeliveryPayment extends core_Plugin
{
    /**
     * Групите документи и действието на продажбата, което се брои към всяка
     */
    protected static $groups = array(
        'delivery' => array('classes' => array('store_ShipmentOrders', 'sales_Services'), 'saleAction' => 'ship',
            'error' => 'По онлайн поръчката вече има контиран документ за доставка'),
        'payment' => array('classes' => array('cash_Pko', 'bank_IncomeDocuments'), 'saleAction' => 'pay',
            'error' => 'По онлайн поръчката вече има контиран документ за плащане'),
    );


    /**
     * Нов или клониран документ от групата не се създава, ако по поръчката вече има контиран
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        $action = strtolower($action);
        if ($action == 'add') {
            if (Request::get('reverseContainerId', 'int')) {

                return;
            }

            $rec = (object) array('threadId' => Request::get('threadId', 'int'));
            if (empty($rec->threadId) && ($originId = Request::get('originId', 'int'))) {
                $rec->threadId = doc_Containers::fetchField($originId, 'threadId');
            }
        } elseif ($action == 'clonefields') {
            $cloneRec = $mvc->fetch(Request::get('id', 'int'));
            if (!is_object($cloneRec)) {

                return;
            }

            // Копието е нов документ в същата нишка - оригиналът също се брои
            $rec = (object) array('threadId' => $cloneRec->threadId ?? null, 'isReverse' => $cloneRec->isReverse ?? null);
        } else {

            return;
        }

        if (empty($rec->threadId)) {

            return;
        }

        if ($error = self::getError($mvc, $rec)) {
            $res = new Redirect(getRetUrl() ? getRetUrl() : array('doc_Containers', 'list', 'threadId' => $rec->threadId), $error, 'error');

            return false;
        }
    }


    /**
     * Спира контирането, ако по поръчката вече има такъв документ
     */
    public static function on_BeforeConto($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if ($error = self::getError($mvc, $rec)) {
            core_Statuses::newStatus($error, 'error');

            return false;
        }
    }


    /**
     * Възстановеният контиран документ се контира отново - важи същото ограничение
     */
    public static function on_BeforeRestore($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if (!in_array($rec->brState ?? null, array('active', 'closed'))) {

            return;
        }

        if ($error = self::getError($mvc, $rec)) {
            core_Statuses::newStatus($error, 'error');

            return false;
        }
    }


    /**
     * Грешката, ако документът не може да се контира, или null
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     *
     * @return string|null
     */
    protected static function getError($mvc, $rec)
    {
        $group = null;
        foreach (static::$groups as $key => $gRec) {
            if (in_array($mvc->className, $gRec['classes'])) {
                $group = $key;
                break;
            }
        }

        if (empty($group) || empty($rec->threadId) || ($rec->isReverse ?? null) == 'yes') {

            return null;
        }

        // Пристигналото плащане с карта е вече взето и не се спира - справката го показва
        if ($mvc->className == 'bank_IncomeDocuments' && !empty($rec->id) && eshop_Carts::$autoContoIncomeDocumentId == $rec->id) {

            return null;
        }

        $handle = self::getExistingHandle($rec->threadId, $group, $mvc->className, $rec->id ?? null);

        return !empty($handle) ? '|' . static::$groups[$group]['error'] . "|* #{$handle}!" : null;
    }


    /**
     * Хендълът на вече контирания документ от групата в нишката на продажба по Н-18, или null
     *
     * @param int    $threadId
     * @param string $group
     * @param string $className - класът на проверявания документ
     * @param int    $id        - проверяваният документ не се брои
     *
     * @return string|null
     */
    protected static function getExistingHandle($threadId, $group, $className, $id)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        if (!is_object($firstDoc) || !$firstDoc->isInstanceOf('sales_Sales')) {

            return null;
        }

        $cartRec = eshop_Carts::fetch(array("#saleId = [#1#]", $firstDoc->that), 'id,domainId');
        if (!is_object($cartRec) || empty($cartRec->domainId)) {

            return null;
        }

        $settings = cms_Domains::getSettings($cartRec->domainId);
        if (!is_object($settings) || ($settings->n18Mode ?? null) != 'yes') {

            return null;
        }

        // Продажба, контирана с експедиране/плащане, е вече доставката/плащането
        $saleRec = $firstDoc->fetch('contoActions,state');
        $contoActions = type_Set::toArray($saleRec->contoActions ?? null);
        if (in_array($saleRec->state ?? null, array('active', 'closed')) && isset($contoActions[static::$groups[$group]['saleAction']])) {

            return $firstDoc->getHandle();
        }

        foreach (static::$groups[$group]['classes'] as $class) {
            $query = $class::getQuery();
            $query->where(array("#threadId = [#1#] AND #state IN ('active', 'closed') AND #isReverse != 'yes'", $threadId));
            if ($class == $className && !empty($id)) {
                $query->where(array("#id != [#1#]", $id));
            }
            $query->show('id');
            $query->limit(1);
            if ($dRec = $query->fetch()) {

                return $class::getHandle($dRec->id);
            }
        }

        return null;
    }
}
