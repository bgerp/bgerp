<?php


/**
 * Мениджър на мемориални ордери (преди "счетоводни статии")
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Articles extends core_Master
{
    
	
	/**
	 * Над колко записа, при създаването на обратен МО, да не попълва детайлите
	 */
	protected static $maxDefaultEntriesForReverseArticle = 80;
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'acc_TransactionSourceIntf=acc_transaction_Article';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Мемориални ордери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, plg_Printing, doc_plg_HidePrices,
                     acc_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, acc_plg_DocumentSummary, bgerp_plg_Blank, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, title= Документ, reason, valior, totalAmount, tools=Пулт";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'acc_ArticleDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Мемориален ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/blog.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Mo";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'acc,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'acc,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'acc,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'acc,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'acc,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,acc';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'acc/tpl/SingleArticle.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'reason, valior, id';
    
    
    /**
     * Полета свързани с цени
     */
    var $priceFields = 'totalAmount';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "6.1|Счетоводни";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('reason', 'varchar(128)', 'caption=Основание,mandatory');
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('totalAmount', 'double(decimals=2)', 'caption=Оборот,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Контиран,rejected=Оттеглен)', 'caption=Състояние,input=none');
        $this->FLD('useCloseItems', 'enum(no=Не,yes=Да)', 'caption=Използване на приключени пера->Избор,maxRadio=2,notNull,default=no,input=none');
    
        // Ако потребителя има роля 'accMaster', може да контира/оотегля/възстановява МО с приключени права
        if(haveRole('accMaster,ceo')){
        	$this->canUseClosedItems = TRUE;
        }
    }
    
    
    /**
     * Дали могат да се използват затворени пера в контировката на документа
     */
    public function canUseClosedItems($id)
    {
    	$rec = $this->fetchRec($id);
    	if(!empty($rec->originId) || ($this->canUseClosedItems === TRUE)){
    		
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Ако потребителя може да избира приключени пера, показваме опцията за избор на формата
        if($mvc->canUseClosedItems === TRUE){
            
            $data->form->setField('useCloseItems', 'input');
            $data->form->setDefault('useCloseItems', 'no');
        }
    }
    
    
    /**
     * Прави заглавие на МО от данните в записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        $valior = self::getVerbal($rec, 'valior');
        
        return tr('Мемориален ордер') . " №{$rec->id} / {$valior}";
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if(empty($rec->totalAmount)){
            $row->totalAmount = $mvc->getFieldType('totalAmount')->toVerbal(0);
        }
        
        $row->title = $mvc->getLink($rec->id, 0);
        $row->totalAmount = '<strong>' . $row->totalAmount . '</strong>';
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    public static function on_AfterPrepareSingleTitle($mvc, &$res, $data)
    {
        $data->title .= " (" . $mvc->getVerbal($data->rec, 'state') . ")";
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $row = &$data->row;
        $rec = &$data->rec;
        
        if ($rec->originId) {
            $doc = doc_Containers::getDocument($rec->originId);
            $row->originId = "#" . $doc->getHandle();
            if($doc->haveRightFor('single')){
            	$row->originId = ht::createLink($row->originId, array($doc->getInstance(), 'single', $doc->that));
            }
        }
    }
    
    
    /**
     * Преизчислява дебитното и кредитното салдо на статия
     *
     * @param int $id първичен ключ на статия
     */
    public function updateMaster_($id, $modified = TRUE)
    {
        $dQuery = acc_ArticleDetails::getQuery();
        $dQuery->XPR('sumAmount', 'double', 'SUM(#amount)', array('dependFromFields' => 'amount'));
        $dQuery->show('articleId, sumAmount');
        $dQuery->groupBy('articleId');
        
        $result = NULL;
        
        $rec = $this->fetch($id);
        
        if ($r = $dQuery->fetch("#articleId = {$id}")) {
            $rec->totalAmount = $r->sumAmount;
        } else {
            $rec->totalAmount = 0;
        }
        
        if($modified){
            $this->save($rec);
        } else {
            $this->save_($rec);
        }
    }
    
    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        $row->title = tr("Мемориален ордер");
        
        if($rec->state == 'draft') {
            $row->title .= ' (' . tr("чернова") . ')';
        } else {
            $row->title .= ' (' . $this->getVerbal($rec, 'totalAmount') . ' BGN' . ')';
            $row->title = str_replace("&nbsp;", " ", $row->title);
        }
        
        $row->subTitle = type_Varchar::escape($rec->reason);
        
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;
        
        return $row;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
        
        return cls::haveInterface('doc_ContragentDataIntf', $folderClass) || $folderClass == 'doc_UnsortedFolders';
    }
    
    
    /**
     * Екшън създаващ обратен мемориален ордер на контиран документ
     */
    public function act_RevertArticle()
    {
        $this->requireRightFor('write');
        expect($docClassId = Request::get('docType', 'int'));
        expect($docId = Request::get('docId', 'int'));
        
        $DocClass = cls::get($docClassId);
        $DocClass->requireRightFor('correction', $docId);
        expect($journlRec = acc_Journal::fetchByDoc($docClassId, $docId));
        expect($result = static::createReverseArticle($journlRec));
        
        return Redirect(array('acc_Articles', 'single', $result[1]), FALSE, "Създаден е успешно обратен Мемориален ордер");
    }
    
    
    /**
     * Създава нов МЕМОРИАЛЕН ОРДЕР-чернова, обратен на зададения документ.
     *
     * Контирането на този МО би неутрализирало счетоводния ефект, породен от контирането на
     * оригиналния документ, зададен с <$docClass, $docId>
     *
     * @param stdClass $journlRec - запис от журнала
     */
    public static function createReverseArticle($journlRec)
    {
        $mvc = cls::get($journlRec->docType);
        
        $articleRec = (object)array(
            'reason'      => tr('Сторниране на') . " " .  mb_strtolower($mvc->singleTitle) . " №{$journlRec->docId} / " . acc_Journal::recToVerbal($journlRec, 'valior')->valior,
            'valior'      => dt::now(),
            'totalAmount' => $journlRec->totalAmount,
            'state'       => 'draft',
        );
        
        $journalDetailsQuery = acc_JournalDetails::getQuery();
        $entries = $journalDetailsQuery->fetchAll("#journalId = {$journlRec->id}");
        
        if (cls::haveInterface('doc_DocumentIntf', $mvc)) {
            $mvcRec = $mvc->fetch($journlRec->docId);
            
            $articleRec->folderId = $mvcRec->folderId;
            $articleRec->threadId = $mvcRec->threadId;
            $articleRec->originId = $mvcRec->containerId;
        } else {
            $articleRec->folderId = doc_UnsortedFolders::forceCoverAndFolder((object)array('name' => 'Сторно'));
        }
        
        if (!$articleId = static::save($articleRec)) {
            return FALSE;
        }
        
        // Попълваме детайлите само ако са под допустимата стойност 
        if(count($entries) <= static::$maxDefaultEntriesForReverseArticle){
        	foreach ($entries as $entry) {
        		$articleDetailRec = array(
        				'articleId'      => $articleId,
        				'debitAccId'     => $entry->debitAccId,
        				'debitEnt1'      => $entry->debitItem1,
        				'debitEnt2'      => $entry->debitItem2,
        				'debitEnt3'      => $entry->debitItem3,
        				'debitQuantity'  => isset($entry->debitQuantity) ? -$entry->debitQuantity : $entry->debitQuantity,
        				'debitPrice'     => $entry->debitPrice,
        				'creditAccId'    => $entry->creditAccId,
        				'creditEnt1'     => $entry->creditItem1,
        				'creditEnt2'     => $entry->creditItem2,
        				'creditEnt3'     => $entry->creditItem3,
        				'creditQuantity' => isset($entry->creditQuantity) ? -$entry->creditQuantity : $entry->creditQuantity,
        				'creditPrice'    => $entry->creditPrice,
        				'amount'         => isset($entry->amount) ? -$entry->amount : $entry->amount,
        		);
        	
        		if (!$bSuccess = acc_ArticleDetails::save((object)$articleDetailRec)) {
        			break;
        		}
        	}
        	
        	if (!$bSuccess) {
        		// Възникнала е грешка - изтрива се всичко!
        		static::delete($articleId);
        		acc_ArticleDetails::delete("#articleId = {$articleId}");
        	
        		return FALSE;
        	}
        }
        
        return array('acc_Articles', $articleId);
    }
    
    
    /**
     * Изпълнява се след обновяване на журнала
     */
    public static function on_AfterJournalUpdated($mvc, $id, $journalId)
    {
        // Ако отнякъде е променена статията на документа, обновяваме го с новата информация
        
        // Всички детайли на МО
        $rec = $mvc->fetchRec($id);
        $dQuery = acc_ArticleDetails::getQuery();
        $dQuery->where("#articleId = {$id}");
        
        // Всички детайли на променения журнал
        $jQuery = acc_JournalDetails::getQuery();
        $jQuery->where("#journalId = {$journalId}");
        $jRecs = $jQuery->fetchAll();
        
        while($dRec = $dQuery->fetch()){
            foreach ($jRecs as $jRec){
                if($dRec->debitAccId == $jRec->debitAccId && $dRec->debitEnt1 == $jRec->debitItem1 && $dRec->debitEnt2 == $jRec->debitItem2 && $dRec->debitEnt3 == $jRec->debitItem3 &&
                    $dRec->creditAccId == $jRec->creditAccId && $dRec->creditEnt1 == $jRec->creditItem1 && $dRec->creditEnt2 == $jRec->creditItem2 && $dRec->creditEnt3 == $jRec->creditItem3){
                    if(!is_null($jRec->debitPrice)){
                        $dRec->debitPrice = $jRec->debitPrice;
                    }
                    
                    if(!is_null($jRec->creditPrice)){
                        $dRec->creditPrice = $jRec->creditPrice;
                    }
                    
                    $dRec->amount = $jRec->amount;
                    
                    break;
                }
            }
            
            acc_ArticleDetails::save($dRec);
        }
        
        $mvc->updateMaster($id, TRUE);
    }
}
