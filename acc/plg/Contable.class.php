<?php


/**
 * Плъгин за документите източници на счетоводни транзакции
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
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
        if (empty($mvc->fields['isContable'])) {
            $mvc->FLD('isContable', 'enum(yes,no,activate)', 'input=none,notNull,value=no');
        }
        
        setIfNot($mvc->canDebugreconto, 'debug');
        setIfNot($mvc->canCorrection, 'ceo, accMaster');
        setIfNot($mvc->valiorFld, 'valior');
        setIfNot($mvc->lockBalances, false);
        setIfNot($mvc->fieldsNotToClone, $mvc->valiorFld);
        setIfNot($mvc->canViewpsingle, 'powerUser');
        setIfNot($mvc->moveDocToFolder, false);
        setIfNot($mvc->autoHideDoc, false); // @see doc_HiddenContainers - да не се скрива автоматично
        
        // Зареждаме плъгина, който проверява може ли да се оттегли/възстанови докумена
        $mvc->load('acc_plg_RejectContoDocuments');
        
        // Ако е оказано, че при контиране/възстановяване/оттегляне да се заключва баланса зареждаме плъгина 'acc_plg_LockBalances'
        if ($mvc->lockBalances === true) {
            
            // Зареждаме плъгина, така се подсигуряваме, че ивентите му ще се изпълняват винаги след тези на 'acc_plg_Contable'
            $mvc->load('acc_plg_LockBalanceRecalc');
        }
        
        if (!empty($mvc->fields[$mvc->valiorFld]) && !isset($mvc->dbIndexes[$mvc->valiorFld])) {
            $mvc->setDbIndex($mvc->valiorFld);
        }
        setIfNot($mvc->createView, true);
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET      $res
     * @param string       $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (strtolower($action) == strtolower('getTransaction')) {
            requireRole('debug');
            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $mvc);
            $transaction = $transactionSource->getTransaction($rec);
            
            Mode::set('wrapper', 'page_Empty');
            
            $transactionRes = null;
            if (!static::hasContableTransaction($mvc, $rec, $transactionRes)) {
                $res = ht::wrapMixedToHtml(ht::mixedToHtml(array($transactionRes, $transaction), 4));
            } else {
                $res = ht::wrapMixedToHtml(ht::mixedToHtml($transaction, 4));
            }
            
            return false;
        }
        
        if (strtolower($action) == strtolower('debugreconto')) {
            $mvc->requireRightFor('debugreconto');
            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            $mvc->requireRightFor('debugreconto', $rec);
            
            // Изтриваме му транзакцията
            acc_Journal::deleteTransaction($mvc, $rec->id);
            
            // Записване на новата транзакция на документа
            Mode::push('recontoTransaction', true);
            $success = acc_Journal::saveTransaction($mvc, $rec->id, false);
            Mode::pop('recontoTransaction');
            $msg = ($success) ? 'Документът е реконтиран|*!' : 'Документът не е реконтиран|*!';
            $msgType = ($success) ? 'notice' : 'error';
            $mvc->logWrite('Ръчно реконтиране', $rec->id);
            
            followRetUrl(null, $msg, $msgType);
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass     $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (!empty($rec->state) && $rec->state != 'draft' && $rec->state != 'pending') {
            
            return;
        }
        
        try {
            // Подсигуряваме се че записа е пълен
            $tRec = clone $rec;
            if (isset($rec->id)) {
                $oldRec = $mvc->fetch($rec->id);
                $tRec = (object) arr::fillMissingKeys($tRec, $oldRec);
            }
            
            // Дали документа може да се активира
            $canActivate = $mvc->canActivate($tRec);
            $transaction = $mvc->getValidatedTransaction($tRec);
            
            // Ако има валидна транзакция
            if ($transaction !== false) {
                
                // Ако транзакцията е празна и документа може да се активира
                if ($transaction->isEmpty() && $canActivate) {
                    $rec->isContable = 'activate';
                } elseif (!$transaction->isEmpty() && $canActivate) {
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
        
        if ($rec->id) {
            $mvc->save_($rec, 'isContable');
        }
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        
        $error = $mvc->getContoBtnErrStr($rec);
        $error = $error ? "error={$error}," : '';
       
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Транзакция', array($mvc, 'getTransaction', $rec->id), 'ef_icon=img/16/bug.png,title=Дебъг информация,row=2');
        }
        
        $row = 1;
        if ($mvc->haveRightFor('conto', $rec)) {
            $row = 2;
            $caption = ($rec->isContable == 'activate') ? 'Активиране' : 'Контиране';
            
            // Урл-то за контиране
            $contoUrl = $mvc->getContoUrl($rec->id);
            $warning = $mvc->getContoWarning($rec->id, $rec->isContable);
            $data->toolbar->addBtn($caption, $contoUrl, array('id' => 'btnConto', 'warning' => $warning), "{$error}ef_icon = img/16/tick-circle-frame.png,title=Контиране на документа");
        }
        
        // Бутон за заявка
        if ($mvc->haveRightFor('pending', $rec)) {
            if ($rec->state != 'pending') {
                $data->toolbar->addBtn('Заявка', array($mvc, 'changePending', $rec->id), "id=btnRequest,warning=Наистина ли желаете документът да стане заявка?,row={$row}", 'ef_icon = img/16/tick-circle-frame.png,title=Превръщане на документа в заявка');
            } else {
                $data->toolbar->addBtn('Чернова', array($mvc, 'changePending', $rec->id), 'id=btnDraft,warning=Наистина ли желаете да върнете възможността за редакция?', 'ef_icon = img/16/arrow-undo.png,title=Връщане на възможността за редакция');
            }
        }
        
        if ($mvc->haveRightFor('revert', $rec)) {
            $rejectUrl = array(
                'acc_Journal',
                'revert',
                'docId' => $rec->id,
                'docType' => $mvc->getClassId(),
                'ret_url' => true
            );
            $data->toolbar->addBtn('Сторно', $rejectUrl, 'id=revert,warning=Наистина ли желаете документът да бъде сторниран|*?', 'ef_icon = img/16/red-back.png,title=Сторниране на документа, row=2');
        } else {
            
            // Ако потребителя може да създава коригиращ документ, слагаме бутон
            if ($mvc->haveRightFor('correction', $rec)) {
                $correctionUrl = array(
                    'acc_Articles',
                    'RevertArticle',
                    'docType' => $mvc->getClassId(),
                    'docId' => $rec->id,
                    'ret_url' => true
                );
                $data->toolbar->addBtn('Корекция||Correct', $correctionUrl, "id=btnCorrection-{$rec->id},class=btn-correction,warning=Наистина ли желаете да коригирате документа?{$error},title=Създаване на обратен мемориален ордер,ef_icon=img/16/page_red.png,row=2");
            }
        }
        
        // Ако има запис в журнала и потребителя има права за него, слагаме бутон
        $journalRec = acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id);
        
        if (($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'pending' || $rec->state == 'stopped') && acc_Journal::haveRightFor('read') && $journalRec) {
            $journalUrl = array('acc_Journal', 'single', $journalRec->id, 'ret_url' => true);
            $data->toolbar->addBtn('Журнал', $journalUrl, 'row=2,ef_icon=img/16/book.png,title=Преглед на контировката на документа в журнала');
        }
        
        if ($data->toolbar->haveButton("btnRestore{$rec->containerId}")) {
            if ($error = $mvc->getRestoreBtnErrStr($rec)) {
                $data->toolbar->setError("btnRestore{$rec->containerId}", $error);
            }
        }
        
        // Ако потребителя може да създава коригиращ документ, слагаме бутон
        if ($mvc->haveRightFor('reconto', $rec)) {
            $correctionUrl = array(
                'acc_Articles',
                'RevertArticle',
                'docType' => $mvc->getClassId(),
                'docId' => $rec->id,
                'ret_url' => true
            );
            $data->toolbar->addBtn('Корекция||Correct', $correctionUrl, "id=btnCorrection-{$rec->id},class=btn-correction,warning=Наистина ли желаете да коригирате документа?{$error},title=Създаване на обратен мемориален ордер,ef_icon=img/16/page_red.png,row=2");
        }
        
        if($mvc->haveRightFor('debugreconto', $rec)){
            $data->toolbar->addBtn('Реконтиране', array($mvc, 'debugreconto', $rec->id, 'ret_url' => true), "id=btnDebugreconto-{$rec->id},warning=Наистина ли желаете да реконтирате документа?,title=Реконтиране на документа,ef_icon=img/16/bug.png,row=3");
        }
    }
    
    
    /**
     * Уорнинг на бутона за контиране/активиране
     */
    public static function on_AfterGetContoWarning($mvc, &$res, $id, $isContable)
    {
        if (empty($res)) {
            $action = ($isContable == 'activate') ? 'активиран' : 'контиран';
            $res = "|Наистина ли желаете документът да бъде {$action}|*?";
        }
    }
    
    
    /**
     * Взимане на грешка в бутона за възстановяване
     */
    public static function on_AfterGetRestoreBtnErrStr($mvc, &$res, $rec)
    {
    }
    
    
    /**
     * Взимане на грешка в бутона за контиране
     */
    public static function on_AfterGetContoBtnErrStr($mvc, &$res, $rec)
    {
        if ($mvc->haveRightFor('conto', $rec)) {
            $error = null;
            if (!self::checkPeriod($mvc->getValiorValue($rec), $error)) {
                $res = $error;
            }
        }
    }
    
    
    /**
     * Ф-я проверяваща периода в който е датата и връща съобщение за грешка
     *
     * @param datetime  $valior - дата
     * @param mixed $error  - съобщение за грешка, NULL ако няма
     *
     * @return bool
     */
    public static function checkPeriod($valior, &$error)
    {
        $docPeriod = acc_Periods::fetchByDate($valior);
        
        if ($docPeriod) {
            if ($docPeriod->state == 'closed') {
                $error = "Не може да се контира в затворения сч. период|* \'{$docPeriod->title}\'";
            } elseif ($docPeriod->state == 'draft') {
                $error = "Не може да се контира в бъдещия сч. период|* \'{$docPeriod->title}\'";
            }
        } else {
            $error = 'Не може да се контира в несъществуващ сч. период';
        }
        
        return ($error) ? false : true;
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
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'conto') {
            
            // Не може да се контира в състояние, което не е чернова
            if ($rec->id && ($rec->state != 'draft' && $rec->state != 'pending')) {
                $requiredRoles = 'no_one';
            }
            
            // Не може да се контира, ако документа не генерира валидна транзакция
            if (isset($rec) && $rec->isContable == 'no') {
                $requiredRoles = 'no_one';
            }
            
            // '@sys' може да контира документи
            if ($userId == '-1') {
                $requiredRoles = 'every_one';
            }
            
            // Кой може да реконтира документа( изпълнява се след възстановяване на оттеглен документ)
        } elseif ($action == 'reconto' && isset($rec)) {
            
            // Който може да възстановява, той може и да реконтира
            $requiredRoles = $mvc->getRequiredRoles('restore', $rec);
            
            // Не може да се реконтират само активни и приключени документи
            if ($rec->id && ($rec->state == 'draft' || $rec->state == 'rejected' || $rec->state == 'pending' || $rec->state == 'stopped')) {
                $requiredRoles = 'no_one';
            }
            
            // Не може да се контира, ако документа не генерира валидна транзакция
            if ($rec->isContable == 'no') {
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
                if ($periodRec->state == 'closed' && $rec->state != 'draft') {
                    $requiredRoles = 'no_one';
                } else {
                    
                    // Ако потребителя не може да контира документа, не може и да го оттегля
                    if (!(core_Packs::isInstalled('colab') && core_Users::haveRole('partner', $userId) && $rec->createdBy == $userId && ($rec->state == 'draft' || $rec->state == 'pending'))) {
                        if ($rec->state == 'draft' || $rec->state == 'pending') {
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
            if (isset($rec)) {
                
                // Ако потребителя не може да контира документа, не може и да го възстановява
                if (!(core_Packs::isInstalled('colab') && core_Users::haveRole('partner', $userId) && $rec->createdBy == $userId)) {
                    if ($rec->state == 'rejected' && ($rec->brState == 'draft' || $rec->brState == 'pending')) {
                        $requiredRoles = $mvc->getRequiredRoles('add');
                    } else {
                        $clone = clone $rec;
                        $clone->state = 'draft';
                        $requiredRoles = $mvc->getRequiredRoles('conto', $clone);
                    }
                }
                
                // Ако сч. период на записа е затворен, документа не може да се възстановява
                $periodRec = acc_Periods::fetchByDate($mvc->getValiorValue($rec));
                if ($periodRec->state == 'closed' && $rec->brState != 'draft') {
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
            if (!acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id)) {
                $requiredRoles = 'no_one';
            }
            
            // Ако документа не генерира валидна и непразна транзакция - не може да му се прави корекция
            if (!$rec->isContable) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако папката на документа е затворена не може да се контира/поправя/сторнира/оттегля/реконтира
        if (($action == 'correction' || $action == 'revert' || $action == 'reject' || $action == 'conto' || $action == 'reconto') && isset($rec->folderId)) {
            if ($requiredRoles != 'no_one') {
                $folderState = doc_Folders::fetchField($rec->folderId, 'state');
                if ($folderState == 'closed') {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'closewith' && isset($rec)) {
            if ($rec->state == 'pending') {
                $requiredRoles = 'no_one';
            }
        }
        
        // Проверка за права за частния сингъл
        if ($action == 'viewpsingle') {
            $rolesAll = acc_plg_DocumentSummary::$rolesAllMap[$mvc->className];
            if (!$rolesAll || !haveRole($rolesAll, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'debugreconto' && isset($rec)) {
            $journalRec = acc_Journal::fetchByDoc($mvc, $rec->id);
            if(!$journalRec){
                $requiredRoles = 'no_one';
            } else {
                if(acc_Periods::isClosed($journalRec->valior)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    
    /**
     * Помощен метод, енкапсулиращ условието за валидност на счетоводна транзакция
     *
     * @param core_Manager $mvc
     * @param stdClass     $rec
     *
     * @return bool
     */
    protected static function hasContableTransaction(core_Manager $mvc, $rec, &$res = null)
    {
        try {
            $result = ($mvc->getValidatedTransaction($rec)) !== false;
        } catch (acc_journal_Exception $ex) {
            $res = $ex->getMessage();
            $result = false;
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
        $cRes = acc_Journal::saveTransaction($mvc->getClassId(), $rec);
        
        if (empty($cRes)) {
            $handle = $mvc->getHandle($rec->id);
            $cRes = 'НЕ Е контиран';
            status_Messages::newStatus("#{$handle} |" . $cRes);
        } elseif ($rec->state == 'active' && $rec->_reconto !== true) {
            $mvc->logWrite('Контиране на документ', $id);
        }
        
        // Нотифициране на създателя на документа и на създателя на първия документ в нишката
        $users = self::getWhichUsersToNotifyOnConto($rec);
        self::notifyCreatorsForPostedDocument($users, $mvc, $rec);
    }
    
    
    /**
     * Контиране на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        try {
            self::conto($mvc, $rec);
        } catch (acc_journal_RejectRedirect $e) {
            $url = $mvc->getSingleUrlArray($rec->id);
            redirect($url, false, '|' . $e->getMessage(), 'error');
        }
    }
    
    
    /**
     * Ре-контиране на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReConto(core_Mvc $mvc, &$res, $id)
    {
        self::conto($mvc, $id);
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
        
        // Оттегляме транзакцията при нужда
        acc_Journal::rejectTransaction($mvc->getClassId(), $rec->id);
        
        // Премахване на нотифицирането за контиране
        if ($rec->brState == 'active') {
            $users = self::getWhichUsersToNotifyOnConto($rec);
            self::removeCreatorsNotificationOnReject($users, $mvc, $rec);
        }
    }
    
    
    /**
     * Нотифицира нужните потребители, че контиран документ е оттеглен
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function notifyUsersForReject($mvc, $rec)
    {
        $users = array();
        $users[$rec->activatedBy] = $rec->activatedBy;
        $users[$rec->createdBy] = $rec->createdBy;
        
        self::sendActionNotifications($users, $mvc, $rec, 'reject');
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
        $rec = $mvc->fetchRec($id);
        if ($rec->state == 'active' || $rec->state == 'closed') {
            try {
                // Ре-контиране на документа след възстановяването му
                $rec->_reconto = true;
                $mvc->reConto($rec);
            } catch (acc_journal_RejectRedirect $e) {
                $mvc->reject($rec);
                
                $url = $mvc->getSingleUrlArray($rec->id);
                redirect($url, false, '|' . $e->getMessage(), 'error');
            }
        }
    }
    
    
    /**
     * Обект-транзакция, съответстващ на счетоводен документ, ако е възможно да се генерира
     *
     * @param core_Mvc                $mvc
     * @param acc_journal_Transaction $transaction FALSE, ако не може да се генерира транзакция
     * @param stdClass                $rec
     */
    public static function on_AfterGetValidatedTransaction(core_Mvc $mvc, &$transaction, $rec)
    {
        if (empty($rec)) {
            $transaction = false;
            
            return;
        }
        
        $rec = $mvc->fetchRec($rec);
        $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        $transaction = $transactionSource->getTransaction($rec);
        
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
        if (!$res) {
            if (!empty($rec->id) && $rec->state != 'draft' && $rec->state != 'pending') {
                $res = false;
            } elseif (countR($mvc->details)) {
                $hasDetail = false;
                
                // Ако класа има поне един запис в детаил, той може да се активира
                foreach ($mvc->details as $name) {
                    $Details = $mvc->{$name};
                    if (!$Details->masterKey) {
                        $hasDetail = true;
                        continue;
                    }
                    
                    if ($rec->id) {
                        if ($Details->fetch("#{$Details->masterKey} = {$rec->id}")) {
                            $hasDetail = true;
                            break;
                        }
                    }
                }
                
                $res = $hasDetail;
            } else {
                $res = true;
            }
        }
    }
    
    
    /**
     * Връща основанието за транзакцията, по подразбиране е основанието на журнала
     */
    public static function on_AfterGetContoReason($mvc, &$res, $id, $reasonCode = null)
    {
        if (empty($res)) {
            if (isset($reasonCode)) {
                
                // Ако има основание, връщаме му вербалното представяне
                $res = acc_Operations::getTitleById($reasonCode, false);
            } else {
                $rec = $mvc->fetchRec($id);
                $Cover = doc_Folders::getCover($rec->folderId);
                
                if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
                    $res = $Cover->getShortHyperLink();
                } else {
                    // Ако няма основание, но журнала на документа има връщаме него
                    if ($jRec = acc_Journal::fetchByDoc($mvc->getClassId(), $id)) {
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
        if (!$res) {
            $res = ($mvc->canUseClosedItems === true) ? true : false;
        }
    }
    
    
    /**
     * Връща вальора на документа по подразбиране
     *
     * @param core_Mvc $mvc
     * @param datetime     $res
     * @param mixed    $rec
     */
    public static function on_AfterGetValiorValue($mvc, &$res, $rec)
    {
        if (!$res) {
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
        if ($part == 'state') {
            if ($rec->state == 'rejected' && $rec->brState == 'active') {
                $num = tr('Анулиран');
            } elseif ($rec->state == 'active') {
                if ($rec->isContable == 'activate') {
                    $num = tr('Активиран');
                } elseif ($rec->isContable == 'yes') {
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
        if (Request::get('Rejected', 'int')) {
            $data->listFields['state'] = 'Състояние';
        }
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            $valior = $mvc->getValiorValue($rec);
            
            if ($warning = acc_Periods::checkDocumentDate($valior)) {
                $form->setWarning($mvc->valiorFld, $warning);
            }
        }
    }
    
    
    /**
     * Кои потребители да се нотифицират при контиране на документа
     *
     * @param stdClass $rec
     *
     * @return array $userArr
     */
    private static function getWhichUsersToNotifyOnConto($rec)
    {
        // Това са създателят на документа
        $userArr = array($rec->createdBy => $rec->createdBy);
        
        return $userArr;
    }
    
    
    /**
     * Нотифицира потребители че документат е бил контиран
     *
     * @param array    $userArr
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    private static function notifyCreatorsForPostedDocument($userArr, $mvc, $rec)
    {
        if (!countR($userArr)) {
            
            return;
        }
        
        // Ако глобално в настройките е зададено да се нотифицира или не
        $docSettings = doc_Setup::get('NOTIFY_FOR_CONTO');
        if ($docSettings == 'no') {
            $userArr = array();
        } elseif ($docSettings == 'yes') {
            $userArr = core_Users::getByRole('powerUser');
        }
        
        $pSettingsKey = crm_Profiles::getSettingsKey();
        
        // Ако е зададено в персоналните настройки на потребителя за всички папки
        doc_Containers::prepareUsersArrForNotifications($userArr, $pSettingsKey, 'DOC_NOTIFY_FOR_CONTO', $rec->threadId);
        
        self::sendActionNotifications($userArr, $mvc, $rec, 'conto');
    }
    
    
    /**
     * Изпраща нотификация за извъшено действие
     * 
     * @param array $users
     * @param mixed $mvc
     * @param stdClass $rec
     * @param string $action
     * @param int|null $userId
     */
    private static function sendActionNotifications($users, $mvc, $rec, $action, $userId = null)
    {
        if(!isset($userId)){
            $userId = core_Users::getCurrent();
        }
        
        $currUserNick = core_Users::fetchField($userId, 'nick');
        $currUserNick = type_Nick::normalize($currUserNick);
        
        $docRow = $mvc->getDocumentRow($rec->id);
        $docTitle = $docRow->title;
        
        $folderTitle = doc_Threads::getThreadTitle($rec->threadId);
        $actionVerbal = ($action == 'reject') ? 'оттегли' : 'контира';
        $message = "{$currUserNick} |{$actionVerbal}|* \"|{$docTitle}|*\" |в нишка|* \"{$folderTitle}\"";
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        $url = ($firstDoc->fetchField('state') == 'rejected') ? array('doc_Containers', 'list', "threadId" => $rec->threadId) : array($mvc, 'single', $rec->id);
        
        foreach ($users as $uId) {
            bgerp_Notifications::add($message, $url, $uId);
        }
    }
    
    
    /**
     * Премахване на нотификацията за контиране при оттегляне
     *
     * @param array    $userArr
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    private static function removeCreatorsNotificationOnReject($userArr, $mvc, $rec)
    {
        if (!countR($userArr)) {
            
            return;
        }
        
        doc_ThreadUsers::removeContainer($rec->containerId);
        $threadRec = doc_Threads::fetch($rec->threadId);
        $threadRec->shared = keylist::fromArray(doc_ThreadUsers::getShared($rec->threadId));
        doc_Threads::save($threadRec, 'shared');
        
        foreach ($userArr as $uId) {
            bgerp_Notifications::setHidden(array($mvc, 'single', $rec->id), 'yes', $uId);
        }
    }
    
    
    /**
     * Има ли контиращи документи в състояние заявка в нишката
     *
     * @param int $threadId
     *
     * @return bool
     */
    public static function havePendingDocuments($threadId)
    {
        $contoClasses = core_Classes::getOptionsByInterface('acc_TransactionSourceIntf');
        $contoClasses = array_keys($contoClasses);
        
        $cQuery = doc_Containers::getQuery();
        $cQuery->where("#state = 'pending'");
        $cQuery->in('docClass', $contoClasses);
        $cQuery->where("#threadId = {$threadId}");
        
        return ($cQuery->fetch()) ? true : false;
    }
    
    
    /**
     * Ролбакване на транзакцията за контиране
     */
    public static function on_AfterRollbackConto($mvc, $res, $id)
    {
        if(!isset($res)){
            $rec = $mvc->fetchRec($id);
            
            if(acc_Journal::fetchByDoc($mvc, $rec->id)){
                acc_Journal::deleteTransaction($mvc, $rec->id);
                $rec->state = $rec->brState;
                $rec->brState = 'active';
                $mvc->save($rec, 'state,brState');
                $res = true;
            }
        }
    }
}
