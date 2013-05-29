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
        
        // Добавяне на полета, свързани с фунционалността "Коригиращи документи"
        $mvc->FLD('isCorrection', 'enum(no,yes)', 'input=none,notNull,default=no');
        $mvc->FLD('correctionDocId', 'key(mvc='.get_class($mvc).')', 'input=none');
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     * 
     * @param core_Manager $mvc
     * @param core_ET $res
     * @param string $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if ($action == 'correction') {
            $mvc->requireRightFor('correction');

            expect($id  = core_Request::get('id', 'key(mvc='.get_class($mvc).')'));
            
            $rec = $mvc->fetchRec($id);
            
            $mvc->requireRightFor('correction', $rec);

            $corrRec = $mvc->createCorrectionDocument($rec);
            
            if (empty($corrRec)) {
                $notifMsg  = 'Проблем при създаване на коригиращ документ';
                $notifType = 'error';
                $redirUrl  = core_App::getRetUrl();
            } else {
                $notifMsg  = 'Успешно създаден коригиращ документ';
                $notifType = 'info';
                $redirUrl  = array($mvc, 'single', $corrRec->id);
            }
            
            $res = new core_Redirect($redirUrl, $notifMsg, $notifType);
            
            return FALSE; // Прекатяваме изпълнението на екшъна до тук
        }
    }
    
    public static function on_AfterCreateCorrectionDocument(core_Manager $mvc, &$corrRec, $rec)
    {
        $corrRec = clone $rec;
        
        unset($corrRec->id);
        unset($corrRec->containerId);
        unset($corrRec->correctionDocId);
        
        $corrRec->originId     = $rec->containerId;
        $corrRec->isCorrection = 'yes';
        $corrRec->state        = 'draft';
        
        if ($mvc->save($corrRec)) {
            $rec->correctionDocId = $corrRec->id;
            $mvc->save($rec);
        } else {
            $corrRec = FALSE;
        }
    }
    
    
    /**
     * След създаване на документ-корекция, "клонира" детайлите на оригинала
     * 
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_AfterCreate(core_Manager $mvc, $rec)
    {
        if ($rec->isCorrection != 'yes') {
            return;
        }
        
        expect($origin = $mvc->getOrigin($rec));
        
        $originalId   = $origin->id();
        $correctionId = $rec->id;

        $details = arr::make($mvc->details);
        
        // "клонираме" всички детайли на оригинала, прикачайки клонингите към документа-корекция
        foreach ($details as $detailName) {
            $DetailManager = cls::get($detailName);
            $detailQuery   = $DetailManager->getQuery();
            $masterKey     = $DetailManager->masterKey;
            $detailQuery->where("#{$masterKey} = {$originalId}");
            
            while ($dRec = $detailQuery->fetch()) {
                $dRec->{$masterKey} = $correctionId;
                unset($dRec->id);
                $DetailManager->save($dRec);
            }
        }
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
        
        if ($mvc->haveRightFor('correction', $data->rec)) {
            $correctionUrl = array(
                $mvc,
                'correction',
                $data->rec->id,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Корекция', $correctionUrl, "id=btnCorrection-{$data->rec->id},class=btn-correction,warning=Наистина ли желаете да коригирате документа?");
        }
        
        $journalRec = acc_Journal::fetch("#docId={$data->rec->id} && #docType='{$mvc::getClassId()}'");
		if(($data->rec->state == 'active' || $data->rec->state == 'closed') && acc_Journal::haveRightFor('read') && $journalRec) {
    		$journalUrl = array('acc_Journal', 'single', $journalRec->id);
    		$data->toolbar->addBtn('Журнал', $journalUrl, 'row=2,ef_icon=img/16/book.png');
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
        } elseif ($action == 'correction') {
            if ($rec->state == 'draft' || $rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
            /*
             * @TODO 
             */
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