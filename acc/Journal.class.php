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
    var $loadList = 'plg_Created, plg_State, plg_RowTools, plg_Printing,
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
    var $canReject = 'no_one';
   
    
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
        $this->FLD('totalAmount', 'double(decimals=2)', 'caption=Оборот,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Активна,revert=Сторнирана)', 'caption=Състояние,input=none');
 //       $this->XPR('isRejected', 'int', "#state = 'rejected'", 'column=none,input=none');
        
        $this->setDbUnique('docType,docId,state');
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->totalAmount = '<strong>' . $row->totalAmount . '</strong>';
        
        if($rec->docType && cls::load($rec->docType, TRUE)) {
            $docClass = cls::getInterface('acc_TransactionSourceIntf', $rec->docType, NULL, TRUE);
            
            if($docClass) {
                $row->docType = $docClass->getLink($rec->docId);
            } 
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderSingleLayout_($data)
    {
        
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
         
        $res = new ET(
        "[#SingleToolbar#]" .
        "<h2>[#SingleTitle#]</h2>" .
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
        "[#DETAILS#]" .
        ''
        );
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    function on_AfterPrepareSingleTitle($mvc, $res, $data)
    {
        $data->title .= " (" . $mvc->getVerbal($data->rec, 'state') . ")";
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
            	self::rollbackTransaction($transactionData->id);

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
            	'valior'      => dt::today(),
            	'totalAmount' => -$rec->totalAmount,
            	'state'       => 'draft',
                'docType'     => $docClassId,
                'docId'       => $docId
            );

            if ($result = self::save($reverseRec)) {
	            // 2. Създаваме "обратни" детайли в журнала
	            $query = acc_JournalDetails::getQuery();
	            $query->where("#journalId = {$rec->id}");
	            
	            while ($result && ($ent = $query->fetch()) ) {
	            	$reverseEnt = (object) array(
	            		'journalId'   => $reverseRec->id,
	            		'quantity'    => -$ent->quantity,
	            		'price'       => -$ent->price,
	            		'amount'      => -$ent->amount,
	            		'debitAccId'  => $ent->debitAccId,
	            		'debitEnt1'   => $ent->debitEnt1,
	            		'debitEnt2'   => $ent->debitEnt2,
	            		'debitEnt3'   => $ent->debitEnt3,
	            		'creditAccId' => $ent->creditAccId,
	            		'creditEnt1'  => $ent->creditEnt1,
	            		'creditEnt2'  => $ent->creditEnt2,
	            		'creditEnt3'  => $ent->creditEnt3,
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
            $rec->state    = 'revert';
            
            $result = self::save($rec);

            $reverseRec->state = 'active';
            self::save($reverseRec, 'state');
        }
        
        return $result;
    }
    
    /**
     * Заличава (изтрива от БД) транзакция
     * 
     * Заличава всички записи, свързани със счетоводна транзакция - както мастър записа, така и 
     * детайл-записите
     *
     * @param int $id ид на мастър запис
     */
    private static function rollbackTransaction($id)
    {
		acc_JournalDetails::delete("#journalId = {$id}");
		self::delete($id);
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
        
        $mvc = cls::get($docClassId);
        
        $mvc->requireRightFor('conto', $docId);
        	
        $docClass = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        
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
    function act_Revert()
    {
        expect($docId      = Request::get('docId', 'int'));
        expect($docClassId = Request::get('docType', 'class(interface=acc_TransactionSourceIntf)'));
        
        $mvc = cls::get($docClassId);

        $mvc->requireRightFor('revert', $docId);

        if (!self::revertTransaction($docClassId, $docId)) {
            core_Message::redirect(
                	"Невъзможно сторниране", 
                	'tpl_Error', 
                	NULL, 
                	getRetUrl() 
                ); 
        }
            
        $docClass = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        
        $docClass->rejectTransaction($docId);
        
        return new Redirect(array($mvc, 'single', $docId));
    }
    
    
}