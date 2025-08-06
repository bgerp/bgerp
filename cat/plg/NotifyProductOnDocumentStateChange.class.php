<?php


/**
 * Плъгин за извикване на събитие в драйвера на артикулите при промяна на състоянието на документ
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_plg_NotifyProductOnDocumentStateChange extends core_Plugin
{
    /**
     * След контиране на документа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetchRec($rec);
        if($rec->brState != 'rejected'){
            $mvc->notifyProductsForDocumentChangedState[$rec->id] = (object)array('id' => $rec->id, 'threadId' => $rec->threadId, 'action' => 'activate');
        }
    }


    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if($rec->brState == 'active'){
            $mvc->notifyProductsForDocumentChangedState[$rec->id] = (object)array('id' => $rec->id, 'threadId' => $rec->threadId, 'action' => 'reject');
        }
    }


    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterRestore($mvc, &$res, &$rec)
    {
        $rec = $mvc->fetchRec($rec);
        if($rec->state == 'active'){
            $mvc->notifyProductsForDocumentChangedState[$rec->id] = (object)array('id' => $rec->id, 'threadId' => $rec->threadId, 'action' => 'restore');
        }
    }


    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        // Ако има заопашени документи и те са към продажба ще се нотифицират драйверите на артикулите
        if (!empty($mvc->notifyProductsForDocumentChangedState)) {
            foreach ($mvc->notifyProductsForDocumentChangedState as $rec) {
                $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
                if($firstDoc->isInstanceOf('sales_Sales')){
                    self::notifyProductsForDocumentChangedState($mvc, $rec->id, $rec->action);
                }
            }
        }
    }


    /**
     * Извикване на ивент в драйвера на артикулите, че е променено състоянието на документа
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param string $action
     * @return void
     */
    public static function notifyProductsForDocumentChangedState($mvc, $id, $action)
    {
        if(!isset($mvc->mainDetail)) return;
        $Detail = cls::get($mvc->mainDetail);
        if(!isset($Detail->productFld)) return;
        $Products = cls::get('cat_Products');

        // Кои са артикулите от детайла, ако има вика им се ивента за промяна
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$id}");
        $dQuery->show($Detail->productFld);
        while($dRec = $dQuery->fetch()){
            $Driver = cat_Products::getDriver($dRec->{$Detail->productFld});
            $Driver->invoke('AfterDocumentInWhichIsUsedHasChangedState', array($Products, $dRec->{$Detail->productFld}, $mvc, $id, $Detail, $dRec->id, $action));
        }
    }
}