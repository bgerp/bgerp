<?php


/**
 * Клас 'bgfisc_plg_Rko' - за добавяне на функционалност от наредба 18 към РКО
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgfisc_plg_Rko extends core_Plugin
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_plg_Rko';


    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('stornoReason', 'varchar(nullIfEmpty)', 'caption=Основание за сторно на ФУ->Основание,after=peroCase,input=hidden');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if (bgfisc_plg_CashDocument::isApplicable($data->form->rec->threadId)) {
            $remFields = $data->form->getFieldParam('peroCase', 'removeAndRefreshForm');
            $remFields .= "|stornoReason";
            $data->form->setField('peroCase', "removeAndRefreshForm={$remFields}");
        }
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;
        
        if (bgfisc_plg_CashDocument::isApplicable($rec->threadId)) {
            $registerRec = bgfisc_Register::getFiscDevice($rec->peroCase);
            
            if (!empty($registerRec)) {
                $Driver = peripheral_Devices::getDriver($registerRec);
                $reasonOptions = $Driver->getStornoReasons($registerRec);
                $form->setField('stornoReason', 'input,mandatory');
                $form->setOptions('stornoReason', $reasonOptions);
                $form->setDefault('stornoReason', key($reasonOptions));
            }
        }
    }
    
    
    /**
     * Изпълнява се преди контиране на документа
     */
    public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if (!bgfisc_plg_CashDocument::isApplicable($rec->threadId)) {
            
            return;
        }
        
        $Origin = doc_Containers::getDocument($rec->originId);
        $amountOrigin = $Origin->fetchField('amountDeal');

        $caseId = ($rec->peroCase) ? $rec->peroCase : $mvc->getDefaultCase($rec);
        bgfisc_Register::getFiscDevice($caseId, $serialNum);
        if($serialNum != bgfisc_Register::WITHOUT_REG_NUM) {
            if (empty($rec->fromContainerId) && round($amountOrigin, 2) != round($rec->amountDeal, 2) && !empty($amountOrigin) && !empty($rec->amountDeal)) {
                $action = Request::get('Act');

                if($action != 'hardconto'){

                    throw new core_exception_Expect('Трябва да е посочено Кредитно известие/Складова разписка за да контирате документа, или той да е точно за сумата на сторнирания документ|*!', 'Несъответствие');
                }
            }

            if (empty($rec->stornoReason)) {

                throw new core_exception_Expect('Трябва да е посочено основание за сторниране|*', 'Несъответствие');
            }
        }
    }
    
    
    /**
     * След взимане на основанията за контиране
     */
    public static function on_AfterGetReasonContainerOptions($mvc, &$res, $rec)
    {
        if (bgfisc_plg_CashDocument::isApplicable($rec->threadId)) {
            $res = is_array($res) ? $res : array();
            $rQuery = store_Receipts::getQuery();
            $rQuery->where("#threadId = {$rec->threadId} AND #state = 'active'");
            while ($rRec = $rQuery->fetch()) {
                $res[$rRec->containerId] = "#Sr{$rRec->id}";
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->reason = "{$row->reason}{$row->stornoReason}";
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (in_array($action, array('selectinvoice')) && isset($rec)) {
            if (!bgfisc_plg_CashDocument::isApplicable($rec->threadId)) {
                
                return;
            }
            if ($rec->state == 'active' || $rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'add' && isset($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->fetchField('state') != 'active') {
                $requiredRoles = 'no_one';
            }
        }
    }
}
