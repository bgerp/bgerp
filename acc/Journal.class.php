<?php



/**
 * Мениджър Журнал
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
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
    var $loadList = 'plg_Created, plg_State, plg_RowTools, plg_Printing, plg_Search,
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
    var $canRead = 'ceo,acc';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,acc';
    
	
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
     * Полета за търсене
     */
    var $searchFields = 'reason';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Ефективна дата
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');

        // Пораждащ документ
        $this->FLD('docType', 'class(interface=acc_TransactionSourceIntf)', 'caption=Основание,input=none');
        $this->FLD('docId', 'int', 'input=none,column=none');

        // Обща сума
        $this->FLD('totalAmount', 'double(decimals=2)', 'caption=Оборот,input=none');

        // Основание за транзакцията
        $this->FLD('reason', 'varchar', 'caption=Основание,input=none');
        
        // Състояние
        $this->FLD('state', 'enum(draft=Чернова,active=Активна,revert=Сторнирана)', 'caption=Състояние,input=none');
                
        $this->setDbUnique('docType,docId,state');
    }
    
    
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->FNC('dateFrom', 'date', 'input,caption=От');
    	$data->listFilter->FNC('dateTo', 'date', 'input,caption=До');
    	
    	$data->listFilter->setDefault('dateFrom', date('Y-m-01'));
		$data->listFilter->setDefault('dateTo', date("Y-m-t", strtotime(dt::now())));
    	
    	$data->listFilter->showFields = 'dateFrom,dateTo,search';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list', 'show' => Request::get('show')), 'id=filter', 'ef_icon = img/16/funnel.png');
    	
    	// Активиране на филтъра
        $data->listFilter->input(NULL, 'silent');
        
    	$data->query->orderBy('id', 'DESC');
    	
    	if($data->listFilter->rec->dateFrom){
    		$data->query->where(array("#valior >= '[#1#]'", $data->listFilter->rec->dateFrom));
    	}
    	
    	if($data->listFilter->rec->dateTo){
    		$data->query->where(array("#valior <= '[#1#] 23:59:59'", $data->listFilter->rec->dateTo));
    	}
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
        
        // Контиране на документа
		$message = $mvc->conto($docId);
		
		// Слагане на статус за потребителя
        core_Statuses::add(tr($message));
        
        // Редирект към сингъла, ако не е зададен друг ret_url
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
        $docRec   = $mvc->fetchRec($docId);
        
        try {
            $transaction = $mvc->getValidatedTransaction($docRec);
        } catch (acc_journal_Exception $ex) {
            $tr = $docClass->getTransaction($docRec->id);
            core_Html::$dumpMaxDepth = 6;
            bp($ex->getMessage(), $tr);
        }
        
        $transaction->rec->docType = $mvc->getClassId();
        $transaction->rec->docId   = $docRec->id;
        
        if ($success = $transaction->save()) {
            // Нотифицира мениджъра на документа за успешно приключилата транзакция
            $docClass->finalizeTransaction($docRec);
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
     * Отменя контирането на счетоводен документ
     * 
     * Ако периода, в който е бил контиран документа е отворен - транзакцията се изтрива от
     * журнала. Ако периода е приключен (т.е. затворен), то в текущия период се създава нова
     * транзакция, обратна на тази, генерирана при контирането на документа. 
     *  
     * @param int $docClassId
     * @param int $docId
     * @return boolean
     */
    public static function rejectTransaction($docClassId, $docId)
    {
        if (!($rec = self::fetchByDoc($docClassId, $docId))) {
            return FALSE;
        }
    
        if (!($periodRec = acc_Periods::fetchByDate($rec->valior))) {
            return FALSE;
        }
    
        if ($periodRec->state == 'closed') {
            return acc_Articles::createReverseArticle($rec);
        } else {
            return static::deleteTransaction($rec);
        }
    }
    
    
    /**
     * Връща записа отговарящ на даден документ
     */
    public static function fetchByDoc($docClassId, $docId)
    {
    	return self::fetch("#docType = {$docClassId} AND #docId = {$docId}");
    }
    
    
    /**
     * Изтриване на транзакция
     */
    public static function deleteTransaction($docClassId, $docId = NULL)
    {
        if (is_object($docClassId)) {
            $rec = $docClassId;
            $docId = $rec->docId;
        } else {
            $rec = self::fetchByDoc($docClassId, $docId);
        }
        
        if (!$rec) {
            return FALSE;
        }

        acc_JournalDetails::delete("#journalId = $rec->id");
        
        static::delete($rec->id);

        acc_Periods::touch($rec->valior);

        return array($docClassId, $docId);
    }
    
    
    /**
      * Добавя ключови думи за пълнотекстово търсене
      */
     function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
    	// Думите за търсене са името на документа-основания
     	$object = new core_ObjectReference($rec->docType, $rec->docId);
     	if($object->haveInterface('doc_DocumentIntf')){
	     	$title = $object->getDocumentRow()->title;
	     	$res .= " " . plg_Search::normalizeText($title);
     	}
     }
     
     
     /**
      * Метод връщащ урл-то за контиране на документ
      * @param core_Manager $mvc - мениджър
      * @param int $recId - ид на записа, който ще се контира
      */
     public static function getContoUrl(core_Manager $mvc, $recId)
     {
     	$contoUrl = array('acc_Journal',
	           			  'conto',
	           			  'docId' => $recId,
	           			  'docType' => $mvc->className,
	           			  'ret_url' => TRUE
            			 );
            	
        return $contoUrl;
     }
}