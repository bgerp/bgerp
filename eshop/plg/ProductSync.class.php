<?php


/**
 * Плъгин синхронизиращ състоянието на ешоп артикулите с артикулите
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class eshop_plg_ProductSync extends core_Plugin
{
    
    
    /**
     * Обновява състоянието на детайлите на е-артикула с тези на Артикула
     *
     * @param int $productId - ид или запис на артикул
     *
     * @return void
     */
    private static function syncStatesByProductId($productId)
    {
        $productId = is_object($productId) ? $productId->id : $productId;
        $pState = cat_Products::fetchField($productId, 'state');
        
        $Details = cls::get('eshop_ProductDetails');
        $dQuery = $Details->getQuery();
        $dQuery->where("#productId = {$productId}");
        while($dRec = $dQuery->fetch()){
            if($dRec->state == 'active' && $pState != 'active'){
                $dRec->state = 'closed';
            } elseif($dRec->state == 'closed' && $pState == 'active'){
                $dRec->state = 'active';
            }
            
            $Details->save_($dRec, 'state');
        }
    }
    
    
    /**
     * След промяна на състоянието
     */
    public static function on_AfterChangeState($mvc, &$rec, $action)
    {
        self::syncStatesByProductId($rec->id);
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
        self::syncStatesByProductId($id);
    }
    
    
    /**
     * Реакция в счетоводния журнал при възстановяване на оттеглен счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        self::syncStatesByProductId($id);
    }
    
    
    /**
     * Изпълнява се след закачане на детайлите
     */
    public static function on_BeforeAttachDetails(core_Mvc $mvc, &$res, &$details)
    {
        $details = arr::make($details);
        $details['eshopProductDetail'] = 'eshop_ProductDetails';
        $details = arr::fromArray($details);
    }


    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        // При клониране, ако артикула може да бъде добавен към ешоп се добавя поле за избор
        if($data->action == 'clone' && eshop_Products::canProductBeAddedToEshop($rec->id)){
            if(eshop_ProductDetails::fetchField("#productId = {$rec->id}")){
                $form->FLD('cloneToEshop', 'enum(yes=Да,no=Не)', 'caption=Добавяне в Е-маг след клониране->Избор');

                $default = core_Permanent::get("addToEshopAfterClone{$rec->id}");
                $form->setDefault('cloneToEshop', $default);
            }
        }
    }


    /**
     * Проверка и валидиране на формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {

            // Запомняне при клониране дали този артикул да се добавя към ешопа или не
            if(isset($rec->cloneToEshop)){
                core_Permanent::set("addToEshopAfterClone{$rec->id}", $rec->cloneToEshop, core_Permanent::FOREVER_VALUE);
            }
        }
    }


    /**
     * След клониране на записа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec  - клонирания запис
     * @param stdClass $nRec - новия запис
     */
    public static function on_AfterSaveCloneRec($mvc, $rec, $nRec)
    {
        // Ако потребителя иска да клонира връзката към Е-маг
        if($nRec->cloneToEshop == 'yes'){
            $eDetails = cls::get('eshop_ProductDetails');

            $dQuery = $eDetails->getQuery();
            $dQuery->where("#productId = {$rec->id}");
            while($dRec = $dQuery->fetch()){
                $newRec = (object)array('eshopProductId' => $dRec->eshopProductId,
                    'productId'      => $nRec->id,
                    'packagings'     => keylist::addKey('', $nRec->measureId),
                    'action'         => $dRec->action,
                    'moq'            => $dRec->moq,
                    'state'          => 'active');

                $eDetails->save($newRec);
            }

            $mvc->logWrite("Връзване в е-маг след клониране", $nRec->id);
        }
    }
}