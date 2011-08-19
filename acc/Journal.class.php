<?php

/**
 * Мениджър Журнал
 */
class acc_Journal extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Журнал";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_State, plg_RowTools, plg_Printing,
                     acc_Wrapper, Entries=acc_JournalDetails, plg_Sorting';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = "id, valior, docType, totalAmount";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = 'acc_JournalDetails';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = 'Счетоводна статия';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'no_one';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'no_one';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'no_one';
    
    
    /**
     * @var acc_JournalDetails
     */
    var $Entries;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('docType', 'class(interface=acc_TransactionSourceIntf)', 'caption=Основание,input=none');
//        $this->FLD('reason', 'varchar', 'caption=Основание,input=none');
        $this->FLD('docId', 'int', 'input=none,column=none');
        $this->FLD('totalAmount', 'double', 'caption=Оборот,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Активна,rejected=Оттеглена)', 'caption=Състояние,input=none');
        $this->XPR('isRejected', 'int', "#state = 'rejected'", 'column=none,input=none');
        
        $this->setDbUnique('docType,docId');
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->totalAmount = '<strong>' . $row->totalAmount . '</strong>';
        
        $docClass = cls::getInterface('acc_TransactionSourceIntf', $rec->docType);
        $row->docType = $docClass->getLink($rec->docId);
        
        if ($rec->state != 'rejected') {
            $row->rejectedOn = $row->rejectedBy = NULL;
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderSingleLayout_($data)
    {
        if( count($this->details) ) {
            foreach($this->details as $var => $className) {
                $detailsTpl .= "[#Detail{$var}#]";
            }
        }
        
        $fieldsHtml = "";
        
        $fieldsHtml .=
        "<tr><td align=\"right\">{$data->singleFields['id']}:</td><td><b>[#id#]</b></td></tr>";
        $fieldsHtml .=
        "<tr><td align=\"right\">{$data->singleFields['valior']}:</td><td><b>[#valior#]</b></td></tr>";
        $fieldsHtml .=
        '<!--ET_BEGIN docType-->' .
        "<tr><td align=\"right\">Документ:</td><td><b>[#docType#]</b> ([#docId#])</td></tr>" .
        '<!--ET_END docType-->';
        $fieldsHtml .=
        "<tr><td align=\"right\">Създаване:</td><td><b>[#createdOn#]</b> <span class=\"quiet\">от</span> <b>[#createdBy#]</b></td></tr>";
        $fieldsHtml .=
        '<!--ET_BEGIN rejectedOn-->' .
        "<tr><td align=\"right\">Оттегляне:</td><td><b>[#rejectedOn#]</b> <span class=\"quiet\">от</span> <b>[#rejectedBy#]</b></td></tr>" .
        '<!--ET_END rejectedOn-->';
        
        $res = new ET(
        "[#SingleToolbar#]" .
        "<h2>[#SingleTitle#] ([#state#])</h2>" .
        '<table>' .
        '<tr>'.
        '<td valign="top" style="padding-right: 5em;">' .
        "<table>{$fieldsHtml}</table>".
        '</td>' .
        '<td valign="top">' .
        '<div class="amounts">' .
        'Оборот: <b>[#totalAmount#]</b>' .
        '</div>' .
        '</td>' .
        '</tr>' .
        '</table>' .
        "{$detailsTpl}" .
        ''
        );
        
        return $res;
    }
    
    
    /**
     *  Записва транзакция в Журнала
     *  
     *  @param stdClass @see acc_TransactionSourceIntf::getTransaction
     *  @return boolean  
     */
    private static function recordTransaction(&$transactionData)
    {        
        $transactionData->state = 'draft';
        
        // Начало на транзакция: създаваме draft мастър запис, за да имаме ключ за детайлите
        if (!self::save($transactionData)) {
        	// Не стана създаването на мастър запис, аборт!
        	return false;
        }
        
        foreach ($transactionData->entries as &$entry) {
            $entry->journalId = $transactionData->id;
            if (!acc_JournalDetails::save($entry)) {
            	// Проблем при записването на детайл-запис. Rollback!!!
            	acc_JournalDetails::delete("#journalId = {$transactionData->id}");
            	self::delete($transactionData->id);

            	return false;
            }
        }
        
        //  Транзакцията е записана. Активираме
        $transactionData->state = 'active';
        
        return self::save($transactionData);
    }
    
    
    /**
     *  @todo Чака за документация...
     *  @todo Имплементация
     */
    private function rejectTransaction($mvc, $docId)
    {
        $docType = core_Classes::fetchField(array("#name = '[#1#]'", $mvc->className), 'id');
        
        $journalRec = $this->fetch("#docType = {$docType} AND #docId = {$docId}");
        
        if (!$journalRec) {
            return FALSE;
        }
        
        $Periods = &cls::get('acc_Periods');
        
        $periodRec = $Periods->fetchByDate($journalRec->valior);
        
        if (!$periodRec) {
            return false;
        }
        
        if ($periodRec->state == 'closed') {
            //
            // Приключен период - записваме в журнала обратна транзакция.
            //
            
            // 1. Създаваме "обратен" мастер в журнала с вальор - днешна дата:
            unset($journalRec->id, $journalRec->createdBy, $journalRec->createdOn);
            
            $journalRec->totalAmount = -$journalRec->totalAmount;
            $journalRec->valior = dt::today();
            
            $result = $this->save($journalRec);
            
            // 2. Създаваме "обратни" детайли в журнала
            $query = $this->Entries->getQuery();
            $query->where("#journalId = {$journalRec->id}");
            
            while ($rec = $query->fetch()) {
                unset($rec->id, $rec->createdBy, $rec->createdOn);
                
                $rec->journalId = $journalRec->id;
                $rec->quantity = -$rec->quantity;
                $rec->price = -$rec->price;
                $rec->amount = -$rec->amount;
                
                $result = $result && $this->Entries->save($rec);
            }
        } else {
            //
            // Неприключен период - маркираме транзакцията като reject-ната
            //
            $journalRec->rejected = TRUE;
            $journalRec->state = 'rejected';
            
            $result = $this->save($journalRec);
        }
        
        return $result;
    }
    
    
    /**
     *  Контиране на счетоводен документ.
     *  
     *  Документа се задава чрез двойката параметри в URL `docId` и `docType`. Класът, зададен
     *  в `docType` трябва да поддържа интерфейса `acc_TransactionSourceIntf`
     *  
     *  @param int $docId (от URL)
     *  @param mixed $docType (от URL) ид или име на клас поддържащ интерфейса 
     *  					  `acc_TransactionSourceIntf`
     */
    function act_Conto()
    {
        expect($docId      = Request::get('docId', 'int'));
        expect($docClassId = Request::get('docType', 'class(interface=acc_TransactionSourceIntf)'));
        
        $mvc      = cls::get($docClassId);
        $docClass = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        
        if ($mvc->haveRightFor('conto', $docId)) {
        	if (!($transaction = $docClass->getTransaction($docId))) {
        		core_Message::redirect(
        			"Невъзможно контиране", 
        			'tpl_Error', 
        			NULL, 
        			array($mvc, 'single', $rec->id)
        		);
        	}
        	
        	$transaction->docType = $docClassId;
        	$transaction->docId   = $docId;
        	
        	if (!self::recordTransaction($transaction)) {
        		core_Message::redirect(
        			"Невъзможно контиране", 
        			'tpl_Error', 
        			NULL, 
        			array($mvc, 'single', $docId)
        		);
        	}
        	
        	// Нотифицира мениджъра на документа за успешно приключилата транзакция
        	$docClass->finalizeTransaction($docId);
        }
        
        return new Redirect(array($mvc, 'single', $docId));
    }
    
    
    /**
     *  Сторниране на счетоводен документ.
     *  
     *  Документа се задава чрез двойката параметри в URL `docId` и `docType`. Класът, зададен
     *  в `docType` трябва да поддържа интерфейса `acc_TransactionSourceIntf`
     *  
     *  @param int $docId (от URL)
     *  @param mixed $docType (от URL) ид или име на клас поддържащ интерфейса 
     *  					  `acc_TransactionSourceIntf`
     */
    function act_Reject()
    {
        expect($docId      = Request::get('docId', 'int'));
        expect($docClassId = Request::get('docType', 'class(interface=acc_TransactionSourceIntf)'));
        
        $mvc      = cls::get($docClassId);
        $docClass = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        
        if ($this->haveRightFor('reject', $docId)) {
            $res = $this->reject($rec->id);
            
            if ($res === false) {
                core_Message::redirect(
                	"Невъзможно сторниране", 
                	'tpl_Error', 
                	NULL, 
                	array($mvc, 'single', $docId)
                );
            }
        }
        
        return new Redirect(array($mvc, 'single', $docId));
    }
    
    
}