<?php



/**
 * Мениджър Журнал
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Journal extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Журнал";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State, plg_RowTools, plg_Printing,
                     acc_Wrapper, Entries=acc_JournalDetails, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, valior, docType, totalAmount";
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'acc_JournalDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Счетоводна статия';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,acc';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'no_one';
    
    
    /**
     * @var acc_JournalDetails
     */
    var $Entries;
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'acc/tpl/SingleLayoutJournal.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('docType', 'class(interface=acc_TransactionSourceIntf)', 'caption=Основание,input=none');
        
        //        $this->FLD('reason', 'varchar', 'caption=Основание,input=none');
        $this->FLD('docId', 'int', 'input=none,column=none');
        $this->FLD('totalAmount', 'double(decimals=2)', 'caption=Оборот,input=none');
        $this->FLD('reason', 'varchar', 'caption=Основание,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Активна,revert=Сторнирана)', 'caption=Състояние,input=none');
        
        //       $this->XPR('isRejected', 'int', "#state = 'rejected'", 'column=none,input=none');
        
        $this->setDbUnique('docType,docId,state');
    }
    
    
    /**
     * След всеки запис в журнала
     * 
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if ($rec->state != 'draft') {
            // Нотифицираме съотв. период че има нови транзакции
            acc_Periods::touch($rec->valior);
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->totalAmount = '<strong>' . $row->totalAmount . '</strong>';
        
        if($rec->docType && cls::load($rec->docType, TRUE)) {
            $mvc = cls::get($rec->docType);
            $doc = new core_ObjectReference($rec->docType, $rec->docId);
            
            if($doc) {
                $row->docType = $doc->getLink();
            }
        }
    }
   
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    static function on_AfterPrepareSingleTitle($mvc, &$res, $data)
    {
        $data->title .= " (" . $mvc->getVerbal($data->rec, 'state') . ")";
    }
    
    
    /**
     * Контиране на счетоводен документ.
     *
     * Документа се задава чрез двойката параметри в URL `docId` и `docType`. Класът, зададен
     * в `docType` трябва да поддържа интерфейса `acc_TransactionSourceIntf`
     *
     * @param int $docId (от URL)
     * @param mixed $docType (от URL) ид или име на клас поддържащ интерфейса
     * `acc_TransactionSourceIntf`
     */
    function act_Conto()
    {
        expect($docId = Request::get('docId', 'int'));
        expect($docClassId = Request::get('docType', 'class(interface=acc_TransactionSourceIntf)'));
        
        $mvc = cls::get($docClassId);
        
        $mvc->requireRightFor('conto', $docId);
        
        try {
            $message = $mvc->conto($docId);
        } catch (core_exception_Expect $ex) {
            redirect(array('acc_Accounts'), FALSE, "Грешка при контиране: " . $ex->args(1));
        }
        
        return followRetUrl(array($mvc, 'single', $docId), $message /*, $success ? 'success' : 'error'*/);
    }
    
    
    /**
     * Сторниране на счетоводен документ.
     *
     * Документа се задава чрез двойката параметри в URL `docId` и `docType`. Класът, зададен
     * в `docType` трябва да поддържа интерфейса `acc_TransactionSourceIntf`
     *
     * @param int $docId (от URL)
     * @param mixed $docType (от URL) ид или име на клас поддържащ интерфейса
     * `acc_TransactionSourceIntf`
     */
    function act_Revert()
    {
        expect($docId = Request::get('docId', 'int'));
        expect($docClassId = Request::get('docType', 'class(interface=acc_TransactionSourceIntf)'));
        
        $mvc = cls::get($docClassId);
        
        $mvc->requireRightFor('revert', $docId);
        
        if (!$result = self::rejectTransaction($docClassId, $docId)) {
            core_Message::redirect(
                "Невъзможно сторниране",
                'page_Error',
                NULL,
                getRetUrl()
            );
        }
        
        list($docClassId, $docId) = $result;
        
        return new Redirect(array($docClassId, 'single', $docId));
    }
    
    
    /**
     * Записва счетоводната транзакция, породена от документ
     * 
     * Документът ($docClassId, $docId) ТРЯБВА да поддържа интерфейс acc_TransactionSourceIntf
     * 
     * @param int $docClassId
     * @param int $docId
     */
    public static function saveTransaction($docClassId, $docId)
    {
        $mvc      = cls::get($docClassId);
        $docClass = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        
        expect($transaction = $docClass->getTransaction($docId));
        
        $transaction->docType = $mvc->getClassId();
        $transaction->docId   = $docId;
        
        $transaction = new acc_journal_Transaction($transaction);
        
        if ($success = $transaction->save()) {
            // Нотифицира мениджъра на документа за успешно приключилата транзакция
            $docClass->finalizeTransaction($docId);
            $success = 'Документът е контиран успешно';
        } else {
            $success = 'Документът НЕ Е контиран';
        }

        return $success;
    }
    
    
    /**
     * Валидира един по един списък от редове на транзакция
     * 
     * @param stdClass $transaction
     * @return boolean
     */
    protected static function validateTransaction($transaction)
    {
        $transaction = new acc_journal_Transaction($transaction);
        
        return $transaction->check();
    }


    /**
     * @todo Чака за документация...
     * @todo Имплементация
     */
    public static function rejectTransaction($docClassId, $docId)
    {
        if (!($rec = self::fetch("#docType = {$docClassId} AND #docId = {$docId}"))) {
            return FALSE;
        }
    
        if (!($periodRec = acc_Periods::fetchByDate($rec->valior))) {
            return FALSE;
        }
    
        if ($periodRec->state == 'closed') {
            return static::createReverseArticle($rec);
        } else {
            return static::deleteTransaction($rec);
        }
    }
    
    
    /**
     * Създава нов МЕМОРИАЛЕН ОРДЕР-чернова, обратен на зададения документ.
     * 
     * Контирането на този МО би неутрализирало счетоводния ефект, породен от контирането на 
     * оригиналния документ, зададен с <$docClass, $docId>
     * 
     * @param int $docClassId
     * @param int $docId
     */
    protected static function createReverseArticle($rec)
    {
        $articleRec = (object)array(
            'reason' => tr('Сторниране на ') . $transaction->reason . ' / ' . $transaction->valior,
            'valior' => dt::now(),
            'totalAmount' => $rec->totalAmount,
            'state' => 'draft',
        );
        
        /* @var $journalDetailsQuery core_Query */
        $journalDetailsQuery = acc_JournalDetails::getQuery();
        $entries = $journalDetailsQuery->fetchAll("#journalId = {$rec->id}");
        
        /* @var $mvc core_Manager */
        $mvc = cls::get($rec->docType);
        
        if (cls::haveInterface('doc_DocumentIntf', $mvc)) {
            $mvcRec = $mvc->fetch($rec->docId);
            
            $articleRec->folderId = $mvcRec->folderId;
            $articleRec->threadId = $mvcRec->threadId;
            $articleRec->originId = $mvcRec->containerId;
        } else {
            $articleRec->folderId = doc_UnsortedFolders::forceCoverAndFolder('Сторно');
        }
        
        if (!$articleId = acc_Articles::save($articleRec)) {
            return FALSE;
        }
        
        foreach ($entries as $entry) {
            $articleDetailRec = array(
                'articleId'      => $articleId,
                'debitAccId'     => $entry->debitAccId,
                'debitEnt1'      => $entry->debitItem1,
                'debitEnt2'      => $entry->debitItem2,
                'debitEnt3'      => $entry->debitItem3,
                'debitQuantity'  => -$entry->debitQuantity,
                'debitPrice'     => $entry->debitPrice,
                'creditAccId'    => $entry->creditAccId,
                'creditEnt1'     => $entry->creditItem1,
                'creditEnt2'     => $entry->creditItem2,
                'creditEnt3'     => $entry->creditItem3,
                'creditQuantity' => -$entry->creditQuantity,
                'creditPrice'    => $entry->creditPrice,
                'amount'         => -$entry->amount,
            );
            
            if (!$bSuccess = acc_ArticleDetails::save((object)$articleDetailRec)) {
                break;
            }
        }
        
        if (!$bSuccess) {
            // Възникнала е грешка - изтриваме всичко!
            acc_Articles::delete($articleId);
            acc_ArticleDetails::delete("#articleId = {$articleId}");
            
            return FALSE;
        }
        
        return array('acc_Articles', $articleId);
    }
    
    
    public static function deleteTransaction($docClassId, $docId = NULL)
    {
        if (is_object($docClassId)) {
            $rec = $docClassId;
            $docId = $rec->docId;
        } else {
            $rec = self::fetch("#docType = {$docClassId} AND #docId = {$docId}");
        }
        
        if (!$rec) {
            return FALSE;
        }

        acc_JournalDetails::delete("#journalId = $rec->id");
        
        static::delete($rec->id);
        
        return array($docClassId, $docId);
    }
}
