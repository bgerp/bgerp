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
        $this->FLD('state', 'enum(draft=Чернова,active=Активна,revert=Сторнирана)', 'caption=Състояние,input=none');
        
        //       $this->XPR('isRejected', 'int', "#state = 'rejected'", 'column=none,input=none');
        
        $this->setDbUnique('docType,docId,state');
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
     * @todo Чака за документация...
     * @todo Имплементация
     */
    private static function revertTransaction($docClassId, $docId)
    {
        if (!($rec = self::fetch("#docType = {$docClassId} AND #docId = {$docId} AND #state = 'active'"))) {
            //return FALSE;
        }
        
        if (!($periodRec = acc_Periods::fetchByDate($rec->valior))) {
            return FALSE;
        }
        $result = TRUE;
        
        if ($periodRec->state == 'closed') {
            //
            // Приключен период - записваме в журнала обратна транзакция.
            //
            
            // 1. Създаваме "обратен" мастер в журнала с вальор - днешна дата:
            $reverseRec = (object)array(
                'valior' => dt::today(),
                'totalAmount' => -$rec->totalAmount,
                'state' => 'draft',
                'docType' => $docClassId,
                'docId' => $docId
            );
            
            if ($result = self::save($reverseRec)) {
                // 2. Създаваме "обратни" детайли в журнала
                $query = acc_JournalDetails::getQuery();
                $query->where("#journalId = {$rec->id}");
                
                while ($result && ($ent = $query->fetch())) {
                    $reverseEnt = (object) array(
                        'journalId' => $reverseRec->id,
                        'amount' => -$ent->amount,
                        'debitAccId' => $ent->debitAccId,
                        'debitEnt1' => $ent->debitEnt1,
                        'debitEnt2' => $ent->debitEnt2,
                        'debitEnt3' => $ent->debitEnt3,
                        'debitQuantity' => -$ent->debitQuantity,
                        'debitPrice' => -$ent->debitPrice,
                        'creditAccId' => $ent->creditAccId,
                        'creditEnt1' => $ent->creditEnt1,
                        'creditEnt2' => $ent->creditEnt2,
                        'creditEnt3' => $ent->creditEnt3,
                        'creditQuantity' => -$ent->creditQuantity,
                        'creditPrice' => -$ent->creditPrice,
                    );
                    
                    $result = acc_JournalDetails::save($ent);
                }
                
                if (!$result) {
                    // Rollback!!! Изтриваме всичко, направено до момента
                    self::rollbackTransaction($reverseRec->id);
                }
            }
        }
        
        if ($result) {
            //
            // маркираме оригиналната транзакция като reject-ната
            //
            $rec->rejected = TRUE;
            $rec->state = 'revert';
            
            $result = self::save($rec);
            
            $reverseRec->state = 'active';
            self::save($reverseRec, 'state');
        }
        
        return $result;
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
        
        $docClass = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        
        if (!($transaction = $docClass->getTransaction($docId))) {
            core_Message::redirect(
                "Невъзможно контиране",
                'page_Error',
                NULL,
                array($mvc, 'single', $rec->id)
            );
        }
        
        $transaction->docType = $docClassId;
        $transaction->docId = $docId;
        
        try {
            $transaction = new acc_journal_Transaction($transaction);
            $transaction->save();
        } catch (core_exception_Expect $ex) {
            redirect(array('acc_Accounts'), FALSE, "Грешка при контиране: " . $ex->args(1));
        }
        
        // Нотифицира мениджъра на документа за успешно приключилата транзакция
        $docClass->finalizeTransaction($docId);
        
        return followRetUrl(array($mvc, 'single', $docId));
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
        
        if (!self::revertTransaction($docClassId, $docId)) {
            core_Message::redirect(
                "Невъзможно сторниране",
                'page_Error',
                NULL,
                getRetUrl()
            );
        }
        
        $mvc->reject($docId);
        
        return new Redirect(array($mvc, 'single', $docId));
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
}
