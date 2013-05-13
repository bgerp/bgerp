<?php



/**
 * Плъгин за документите източници на счетоводни транзакции
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_Contable extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     * 
     * @param core_Mvc $mvc
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('acc_TransactionSourceIntf');
        
        $mvc->fields['state']->type->options['revert'] = 'Сторниран';
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('conto', $data->rec)) {
            $contoUrl = array(
                'acc_Journal',
                'conto',
                'docId' => $data->rec->id,
                'docType' => $mvc->className,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Контиране', $contoUrl, 'id=btnConto,class=btn-conto,warning=Наистина ли желаете документа да бъде контиран?');
        }
        
        if ($mvc->haveRightFor('revert', $data->rec)) {
            $rejectUrl = array(
                'acc_Journal',
                'revert',
                'docId' => $data->rec->id,
                'docType' => $mvc->className,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Сторно', $rejectUrl, 'id=revert,class=btn-revert,warning=Наистина ли желаете документа да бъде сторниран?');
        }
        
        $journalRec = acc_Journal::fetch("#docId={$data->rec->id} && #docType='{$mvc::getClassId()}'");
		if($data->rec->state == 'active' || $data->rec->state == 'closed' && acc_Journal::haveRightFor('read') && $journalRec) {
    		$journalUrl = array('acc_Journal', 'single', $journalRec->id);
    		$data->toolbar->addBtn('Журнал', $journalUrl, '');
    	}
    }
    
    
    /**
     * Реализация по подразбиране на acc_TransactionSourceIntf::getLink()
     *
     * @param core_Manager $mvc
     * @param mixed $res
     * @param mixed $id
     */
    static function on_AfterGetLink($mvc, &$res, $id)
    {
        if(!$res) {
            $title = sprintf('%s&nbsp;№%d',
                empty($mvc->singleTitle) ? $mvc->title : $mvc->singleTitle,
                $id
            );
            
            $res = Ht::createLink($title, array($mvc, 'single', $id));
        }
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'conto') {
            if ($rec->id && $rec->state != 'draft') {
                $requiredRoles = 'no_one';
            }
        } elseif ($action == 'revert') {
            if ($rec->id) {
                $periodRec = acc_Periods::fetchByDate($rec->valior);
                
                if ($rec->state != 'active' || ($periodRec->state != 'closed')) {
                    $requiredRoles = 'no_one';
                }
            }
        } elseif ($action == 'reject') {
            if ($rec->id) {
                $periodRec = acc_Periods::fetchByDate($rec->valior);
                
                if ($periodRec->state == 'closed') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }

    
    /**
     * Контиране на счетоводен документ
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterConto(core_Mvc $mvc, &$res, $id)
    {
        if (is_object($id)) {
            $id = $id->id;
        }
        
        $res = acc_Journal::saveTransaction($mvc->getClassId(), $id);        
    }
    
    
    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        if (is_object($id)) {
            $id = $id->id;
        }
        
        $res = acc_Journal::rejectTransaction($mvc->getClassId(), $id);
    }
    
    
    /**
     * Реакция в счетоводния журнал при възстановяване на оттеглен счетоводен документ
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        if (is_object($id)) {
            $id = $id->id;
        }
        
        $res = acc_Journal::saveTransaction($mvc->getClassId(), $id);
    }
    
    
    /**
     * Обект-транзакция, съответстващ на счетоводен документ, ако е възможно да се генерира
     * 
     * @param core_Mvc $mvc
     * @param acc_journal_Transaction $transation FALSE, ако не може да се генерира транзакция
     * @param stdClass $rec
     */
    public static function on_AfterGetValidatedTransaction(core_Mvc $mvc, &$transaction, $rec)
    {
        try {
            $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $mvc);
            $transaction       = $transactionSource->getTransaction($rec);
            
            expect(!empty($transaction), 'Класът ' . get_class($mvc) . ' не върна транзакция!');
            
            // Проверяваме валидността на транзакцията
            $transaction = new acc_journal_Transaction($transaction);
            if (!$transaction->check()) {
                return FALSE;
            }
        } catch (core_exception_Expect $ex) {
            // Транзакцията не се валидира
            $transaction = FALSE;
        }
    }
}