<?php



/**
 * Плъгин за документите източници на счетоводни транзакции
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
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
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('acc_TransactionSourceIntf');
        
        $mvc->getFieldType('state')->options['revert'] = 'Сторниран';
        
        // Добавяне на кеш-поле за контируемостта на документа. Обновява се при (преди) всеки 
        // запис. Използва се при определяне на правата за контиране.
        if(empty($mvc->fields['isContable'])){
            $mvc->FLD('isContable', 'enum(yes,no,activate)', 'input=none,notNull,value=no');
        }
        
        setIfNot($mvc->canCorrection, 'ceo, accMaster');
        setIfNot($mvc->valiorFld, 'valior');
        setIfNot($mvc->lockBalances, FALSE);
        setIfNot($mvc->fieldsNotToClone, $mvc->valiorFld);
        setIfNot($mvc->canPending, 'no_one');
        setIfNot($mvc->canViewpsingle, 'powerUser');
        
        // Зареждаме плъгина, който проверява може ли да се оттегли/възстанови докумена
        $mvc->load('acc_plg_RejectContoDocuments');
       
        // Ако е оказано, че при контиране/възстановяване/оттегляне да се заключва баланса зареждаме плъгина 'acc_plg_LockBalances'
        if($mvc->lockBalances === TRUE){
        	
        	// Зареждаме плъгина, така се подсигуряваме, че ивентите му ще се изпълняват винаги след тези на 'acc_plg_Contable'
        	$mvc->load('acc_plg_LockBalanceRecalc');
        }
        
        if (!empty($mvc->fields[$mvc->valiorFld]) && !isset($mvc->dbIndexes[$mvc->valiorFld])) {
            $mvc->setDbIndex($mvc->valiorFld);
        }
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
        if(strtolower($action) == strtolower('getTransaction')) {
            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $mvc);
            $transaction       = $transactionSource->getTransaction($rec);
            
            Mode::set('wrapper', 'page_Empty');
            
            if(!static::hasContableTransaction($mvc, $rec, $transactionRes)){
                $res = ht::wrapMixedToHtml(ht::mixedToHtml(array($transactionRes, $transaction), 4));
            } else {
                $res = ht::wrapMixedToHtml(ht::mixedToHtml($transaction, 4));
            }
            
            return FALSE;
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (!empty($rec->state) && $rec->state != 'draft') {
            return;
        }
        
        try {
        	// Подсигуряваме се че записа е пълен
        	$tRec = clone $rec;
        	if(isset($rec->id)){
        		$oldRec = $mvc->fetch($rec->id);
        		$tRec = (object)arr::fillMissingKeys($tRec, $oldRec);
        	}
        	
            // Дали документа може да се активира
            $canActivate = $mvc->canActivate($tRec);
            $transaction = $mvc->getValidatedTransaction($tRec);
            
            // Ако има валидна транзакция
            if($transaction !== FALSE){
                
                // Ако транзакцията е празна и документа може да се активира
                if($transaction->isEmpty() && $canActivate){
                    $rec->isContable = 'activate';
                } elseif(!$transaction->isEmpty() && $canActivate) {
                    $rec->isContable = 'yes';
                } else {
                    $rec->isContable = 'no';
                }
            } else {
                $rec->isContable = 'no';
            }
        } catch (acc_journal_Exception $ex) {
            $rec->isContable = 'no';
        }
       
        if($rec->id){
        	$mvc->save_($rec, 'isContable');
        }
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        
        $error = $mvc->getBtnErrStr($rec);
        $error = $error ? ",error={$error}" : '';
        
        if(haveRole('debug')) {
            $data->toolbar->addBtn('Транзакция', array($mvc, 'getTransaction', $rec->id), "ef_icon=img/16/bug.png,title=Дебъг информация,row=2");
        }
        
        $row = 1;
        if ($mvc->haveRightFor('conto', $rec)) {
        	$row = 2;
        	
            if($rec->isContable == 'activate'){
            	$caption = 'Активиране';
            	$action = 'активиран';
            } else {
            	$caption = 'Контиране';
            	$action = 'контиран';
            }
           
            // Урл-то за контиране
            $contoUrl = $mvc->getContoUrl($rec->id);
            $data->toolbar->addBtn($caption, $contoUrl, "id=btnConto,warning=Наистина ли желаете документът да бъде {$action}?{$error}", 'ef_icon = img/16/tick-circle-frame.png,title=Контиране на документа');
        }
        
        // Бутон за заявка
        if($mvc->haveRightFor('pending', $rec)){
        	if($rec->state != 'pending'){
        		$data->toolbar->addBtn('Заявка', array($mvc, 'changePending', $rec->id), "id=btnRequest,warning=Наистина ли желаете документът да стане заявка?,row={$row}{$error}", 'ef_icon = img/16/tick-circle-frame.png,title=Превръщане на документа в заявка');
        	} else{
        		$data->toolbar->addBtn('Чернова', array($mvc, 'changePending', $rec->id), "id=btnDraft,warning=Наистина ли желаете да върнете възможността за редакция?{$error}", 'ef_icon = img/16/arrow-undo.png,title=Връщане на възможността за редакция');
        	}
        }
        
        if ($mvc->haveRightFor('revert', $rec)) {
            $rejectUrl = array(
                'acc_Journal',
                'revert',
                'docId' => $rec->id,
                'docType' => $mvc->getClassId(),
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Сторно', $rejectUrl, "id=revert,warning=Наистина ли желаете документът да бъде сторниран?{$error}", 'ef_icon = img/16/red-back.png,title=Сторниране на документа, row=2');
        } else {
        	
        	// Ако потребителя може да създава коригиращ документ, слагаме бутон
        	if ($mvc->haveRightFor('correction', $rec)) {
        		$correctionUrl = array(
        				'acc_Articles',
        				'RevertArticle',
        				'docType' => $mvc->getClassId(),
        				'docId' => $rec->id,
        				'ret_url' => TRUE
        		);
        		$data->toolbar->addBtn('Корекция||Correct', $correctionUrl, "id=btnCorrection-{$rec->id},class=btn-correction,warning=Наистина ли желаете да коригирате документа?{$error},title=Създаване на обратен мемориален ордер,ef_icon=img/16/page_red.png,row=2");
        	}
        }
        
        // Ако има запис в журнала и потребителя има права за него, слагаме бутон
        $journalRec = acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id);
        
        if(($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'pending' || $rec->state == 'stopped') && acc_Journal::haveRightFor('read') && $journalRec) {
            $journalUrl = array('acc_Journal', 'single', $journalRec->id, 'ret_url' => TRUE);
            $data->toolbar->addBtn('Журнал', $journalUrl, "row=2,ef_icon=img/16/book.png,title=Преглед на контировката на документа в журнала{$error}");
        }
    }
    
    
    /**
     * 
     * 
     * @param core_Manager $mvc
     * @param string|NULL $res
     * @param stdObject $rec
     */
    public function on_AfterGetBtnErrStr($mvc, &$res, $rec)
    {
        if ($mvc->haveRightFor('conto', $rec)) {
            if(!self::checkPeriod($mvc->getValiorValue($rec), $error)){
                $res = $error;
            }
        }
    }
    
    
    /**
     * Ф-я проверяваща периода в който е датата и връща съобщение за грешка
     *
     * @param date $valior - дата
     * @param mixed $error - съобщение за грешка, NULL ако няма
     * @return boolean
     */
    public static function checkPeriod($valior, &$error)
    {
        $docPeriod = acc_Periods::fetchByDate($valior);
        
        if($docPeriod){
            if($docPeriod->state == 'closed'){
                $error = "Не може да се контира в затворения сч. период|* \'{$docPeriod->title}\'";
            } elseif($docPeriod->state == 'draft'){
                $error = "Не може да се контира в бъдещия сч. период|* \'{$docPeriod->title}\'";
            }
        } else {
            $error = "Не може да се контира в несъществуващ сч. период";
        }
        
        return ($error) ? FALSE : TRUE;
    }
    
    
    /**
     * Метод връщащ урл-то за контиране на документа.
     * Може да се използва в мениджъра за подмяна на контиращото урл
     */
    public static function on_AfterGetContoUrl(core_Manager $mvc, &$res, $id)
    {
        $res = acc_Journal::getContoUrl($mvc, $id);
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'conto') {
            
            // Не може да се контира в състояние, което не е чернова
            if ($rec->id && ($rec->state != 'draft' && $rec->state != 'pending')) {
                $requiredRoles = 'no_one';
            }
            
            // Не може да се контира, ако документа не генерира валидна транзакция
            if (isset($rec) && $rec->isContable == 'no'){
                $requiredRoles = 'no_one';
            }
            
            // '@sys' може да контира документи
            if($userId == '-1'){
                $requiredRoles = 'every_one';
            }
            
            // Кой може да реконтира документа( изпълнява се след възстановяване на оттеглен документ)
        } elseif($action == 'reconto' && isset($rec)){
            
            // Който може да възстановява, той може и да реконтира
            $requiredRoles = $mvc->getRequiredRoles('restore', $rec);
            
            // Не може да се реконтират само активни и приключени документи
            if ($rec->id && ($rec->state == 'draft' || $rec->state == 'rejected' || $rec->state == 'pending' || $rec->state == 'stopped')) {
                $requiredRoles = 'no_one';
            }
            
            // Не може да се контира, ако документа не генерира валидна транзакция
            if ($rec->isContable == 'no'){
                $requiredRoles = 'no_one';
            }
        } elseif ($action == 'revert') {
            if ($rec->id) {
            	
            	// Ако има запис в журнала, вальора е този от него, иначе е полето за вальор от документа
            	$jRec = acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id);
            	$valior = isset($jRec) ? $jRec->valior : $mvc->getValiorValue($rec);
                $periodRec = acc_Periods::fetchByDate($valior);
                
                // Само активни документи с транзакция и в незатворен период могат да се сторнират
                if (($periodRec->state != 'closed') || ($rec->state != 'active' && $rec->state != 'closed') || empty($jRec)) {
                   $requiredRoles = 'no_one';
                }
            }
        } elseif ($action == 'reject') {
            if ($rec->id) {
                
            	// Ако има запис в журнала, вальора е този от него, иначе е полето за вальор от документа
            	$jRec = acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id);
            	$valior = !empty($jRec) ? $jRec->valior : $mvc->getValiorValue($rec);
            	
            	$periodRec = acc_Periods::fetchByDate($valior);
                
            	// Ако периода на вальора е затворен, забраняваме
                if ($periodRec->state == 'closed') {
                    $requiredRoles = 'no_one';
                } else {
                    
                    // Ако потребителя не може да контира документа, не може и да го оттегля
                    if(!(core_Packs::isInstalled('colab') && core_Users::haveRole('partner', $userId) && $rec->createdBy == $userId && ($rec->state == 'draft' || $rec->state == 'pending'))){
                    	if($rec->state == 'draft' || $rec->state == 'pending'){
                    		$requiredRoles = $mvc->getRequiredRoles('add');
                    	} else {
                    		$clone = clone $rec;
                    		$clone->state = 'draft';
                    		$requiredRoles = $mvc->getRequiredRoles('conto', $clone);
                    	}
                    }
                }
            }
        } elseif ($action == 'restore') {
        	if(isset($rec)){
        		
        		// Ако потребителя не може да контира документа, не може и да го възстановява
        		if(!(core_Packs::isInstalled('colab') && core_Users::haveRole('partner', $userId) && $rec->createdBy == $userId)){
        		
        			if($rec->state == 'rejected' && ($rec->brState == 'draft' || $rec->brState == 'pending')){
        				$requiredRoles = $mvc->getRequiredRoles('add');
        			} else {
        				$clone = clone $rec;
        				$clone->state = 'draft';
        				$requiredRoles = $mvc->getRequiredRoles('conto', $clone);
        			}
        		}
        		
            	// Ако сч. период на записа е затворен, документа не може да се възстановява
            	$periodRec = acc_Periods::fetchByDate($mvc->getValiorValue($rec));
            	if ($periodRec->state == 'closed') {
            		$requiredRoles = 'no_one';
            	}
            }
            
        } elseif ($action == 'correction') {
            
            // Кой може да създава коригиращ документ
            $requiredRoles = $mvc->canCorrection;
            
            // Трябва да има запис
            if (!$rec) {
                return;
            }
            
            // Черновите и оттеглените документи немогат да се коригират
            if ($rec->state == 'draft' || $rec->state == 'rejected' || $rec->state == 'pending' || $rec->state == 'stopped') {
                $requiredRoles = 'no_one';
            }
            
            // Ако няма какво да се коригира в журнала, не може да се създаде корекция
            if(!acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id)){
                $requiredRoles = 'no_one';
            }
            
            // Ако документа не генерира валидна и непразна транзакция - не може да му се прави корекция
            if (!$rec->isContable) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако папката на документа е затворена не може да се контира/поправя/сторнира/оттегля/реконтира
        if(($action == 'correction' || $action == 'revert' || $action == 'reject' || $action == 'conto' || $action == 'reconto') && isset($rec->folderId)){
        	if($requiredRoles != 'no_one'){
        		$folderState = doc_Folders::fetchField($rec->folderId, 'state');
        		if($folderState == 'closed'){
        			$requiredRoles = 'no_one';
        		}
        	}
        }
        
        if($action == 'closewith' && isset($rec)){
            if($rec->state == 'pending'){
            	$requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'viewpsingle') {
            $allowedClsArr = type_Keylist::toArray(acc_Setup::get('CLASSES_FOR_VIEW_ACCESS'));
            
            // Ако не е позволено в класа, да не може да се използва
            if (!$allowedClsArr[$mvc->getClassId()]) {
                $requiredRoles = 'no_one';
            } else {
                // Заобиколяване за вземане на правата
                $cRec = NULL;
                if (is_object($rec)) {
                    $cRec = clone $rec;
                    $cRec->state = 'draft';
                }
                 
                $requiredRoles = $mvc->getRequiredRoles('conto', $cRec, $userId);
            }
        }
    }
    
    
    /**
     * Помощен метод, енкапсулиращ условието за валидност на счетоводна транзакция
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     * @return boolean
     */
    protected static function hasContableTransaction(core_Manager $mvc, $rec, &$res = NULL)
    {
        try {
            $result = ($transaction = $mvc->getValidatedTransaction($rec)) !== FALSE;
        } catch (acc_journal_Exception $ex) {
            $res = $ex->getMessage();
            $result = FALSE;
        }
        
        return $result;
    }
    
    
    /**
     * Помощна ф-я за контиране на документ
     */
    private static function conto($mvc, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        // Контирането е позволено само в съществуващ активен/чакащ/текущ период;
        $period = acc_Periods::fetchByDate($rec->valior);
        expect($period && ($period->state != 'closed' && $period->state != 'draft'), 'Не може да се контира в несъществуващ, бъдещ или затворен период');
        
        try{
       		$cRes = acc_Journal::saveTransaction($mvc->getClassId(), $rec);
        } catch (acc_journal_RejectRedirect $e){
        	
        	$url = $mvc->getSingleUrlArray($rec->id);
        	redirect($url, FALSE, '|' . $e->getMessage(), 'error');
        }
        
        if(empty($cRes)){
        	$handle = $mvc->getHandle($rec->id);
        	$cRes = 'НЕ Е контиран';
        	status_Messages::newStatus("#{$handle} |" . $cRes);
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
        self::conto($mvc, $id);
    }
    
    
    /**
     * Ре-контиране на счетоводен документ
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterReConto(core_Mvc $mvc, &$res, $id)
    {
        self::conto($mvc, $id); 
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
        
        // Оттегляме транзакцията при нужда
        acc_Journal::rejectTransaction($mvc->getClassId(), $id);
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
        $rec = $mvc->fetchRec($id);
        
        if($rec->state == 'active' || $rec->state == 'closed'){
            // Ре-контиране на документа след възстановяването му
            $mvc->reConto($id);
        }
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
        if (empty($rec)) {
            $transaction = FALSE;
            
            return;
        }
        
        $rec = $mvc->fetchRec($rec);
        $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        $transaction       = $transactionSource->getTransaction($rec);
      
        expect(!empty($transaction), 'Класът ' . get_class($mvc) . ' не върна транзакция!');
        
        // Проверяваме валидността на транзакцията
        $transaction = new acc_journal_Transaction($transaction);
        
        $transaction->check();
    }
    
    
    /**
     * Метод по подразбиране на canActivate
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
        if(!$res){
            if (!empty($rec->id) && $rec->state != 'draft') {
                $res = FALSE;
            } elseif(count($mvc->details)){
                $hasDetail = FALSE;
                
                // Ако класа има поне един запис в детаил, той може да се активира
                foreach ($mvc->details as $name){
                    $Details = $mvc->{$name};
                    if(!$Details->masterKey) {
                        $hasDetail = TRUE;
                        continue;
                    }
                        
                    if($rec->id){
                        if($Details->fetch("#{$Details->masterKey} = {$rec->id}")){
                        	$hasDetail = TRUE;
                        	break;
                        }
                    }
                }
                
                $res = $hasDetail;
            } else {
                $res = TRUE;
            }
        }
    }
    
    
    /**
     * Връща основанието за транзакцията, по подразбиране е основанието на журнала
     */
    public static function on_AfterGetContoReason($mvc, &$res, $id, $reasonCode = NULL)
    {
        if(empty($res)){
        	if(isset($reasonCode)){
        		
        		// Ако има основание, връщаме му вербалното представяне
        		$res = acc_Operations::getTitleById($reasonCode, FALSE);
        	} else {
        		$rec = $mvc->fetchRec($id);
        		$Cover = doc_Folders::getCover($rec->folderId);
        		
        		if($Cover->haveInterface('crm_ContragentAccRegIntf')){
        			$res = $Cover->getShortHyperLink();
        		} else {
        			// Aко няма основание, но журнала на документа има връщаме него
        			if($jRec = acc_Journal::fetchByDoc($mvc->getClassId(), $id)){
        				$Varchar = cls::get('type_Varchar');
        				$res = $Varchar->toVerbal($jRec->reason);
        			}
        		}
        	}
        }
    }
    
    
    /**
     * Дали могат да се използват затворени пера в контировката на документа
     */
    public static function on_AfterCanUseClosedItems($mvc, &$res, $id)
    {
    	if(!$res){
    		$res = ($mvc->canUseClosedItems === TRUE) ? TRUE : FALSE;
    	}
    }
    
    
    /**
     * Връща вальора на документа по подразбиране
     * 
     * @param core_Mvc $mvc
     * @param date $res
     * @param mixed $rec
     */
    public static function on_AfterGetValiorValue($mvc, &$res, $rec)
    {
    	if(!$res){
    		$rec = $mvc->fetchRec($rec);
    		$res = $rec->{$mvc->valiorFld};
    	}
    }
    
    
    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
    	// Искаме състоянието на оттеглените чернови да се казва 'Анулиран'
    	if($part == 'state'){
    		if($rec->state == 'rejected' && $rec->brState == 'active'){
    			$num = tr('Анулиран');
    		} elseif($rec->state == 'active'){
    			if($rec->isContable == 'activate'){
    				$num = tr('Активиран');
    			} elseif($rec->isContable == 'yes'){
    				$num = tr('Контиран');
    			}
    		}
    	}
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
    	if(Request::get('Rejected', 'int')){
    		$data->listFields['state'] = 'Състояние';
    	}
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		$valior = $mvc->getValiorValue($rec);
    	
    		if($warning = acc_Periods::checkDocumentDate($valior)){
    			$form->setWarning($mvc->valiorFld, $warning);
    		}
    	}
    }
}