<?php


/**
 * Мениджър на мемориални ордери (преди "счетоводни статии")
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
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
    public $interfaces = 'acc_TransactionSourceIntf=acc_transaction_Article';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Мемориални ордери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Clone, plg_Printing, doc_plg_HidePrices,doc_plg_Prototype,
                     acc_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, acc_plg_DocumentSummary, bgerp_plg_Blank, plg_Search, doc_plg_SelectFolder';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     * 
     * @see plg_Clone
     */
    public $cloneDetails = 'acc_ArticleDetails';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "title= Документ, reason, valior, totalAmount";
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'acc_ArticleDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Мемориален ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/blog.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Mo";
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'acc,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'acc,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,acc';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'acc/tpl/SingleArticle.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'reason, valior, id';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'totalAmount';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "6.1|Счетоводни";
    
    
    /**
     * Да се правили проверка дали документа може да се контира в нишката
     */
    public $checkIfCanContoInThread = FALSE;
    
    
    /**
     * Дали може да се използват затворени пера
     */
    public $canUseClosedItems = FALSE;
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'valior';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('reason', 'varchar(128)', 'caption=Основание,mandatory');
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('totalAmount', 'double(decimals=2)', 'caption=Оборот,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Контиран,rejected=Оттеглен,template=Шаблон,stopped=Спряно)', 'caption=Състояние,input=none');
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
        $form = $data->form;
        
        // Ако потребителя може да избира приключени пера, показваме опцията за избор на формата
        if($mvc->canUseClosedItems === TRUE){
            $form->setField('useCloseItems', 'input');
            $form->setDefault('useCloseItems', 'no');
        }
        
        if(isset($form->rec->id)){
        	if(acc_ArticleDetails::fetchField("#articleId = {$form->rec->id}")){
        		$form->setReadOnly('useCloseItems');
        	}
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
            $row->totalAmount = "<b class='quiet'>{$row->totalAmount}</b>";
        } elseif($rec->totalAmount < 0){
        	$row->totalAmount = "<span class='red'>{$row->totalAmount}</span>";
        } else {
        	$row->totalAmount = '<strong>' . $row->totalAmount . '</strong>';
        }
        
        $row->title = $mvc->getLink($rec->id, 0);
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
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id, $modified = TRUE)
    {
        $dQuery = acc_ArticleDetails::getQuery();
        $dQuery->XPR('sumAmount', 'double', 'SUM(#amount)', array('dependFromFields' => 'amount'));
        $dQuery->show('articleId, sumAmount');
        $dQuery->groupBy('articleId');
        
        $rec = $this->fetch($id);
        
        if (!$rec) return NULL;
        
        if ($r = $dQuery->fetch("#articleId = {$id}")) {
            $rec->totalAmount = $r->sumAmount;
        } else {
            $rec->totalAmount = 0;
        }
        
        if($modified){
            $id = $this->save($rec);
        } else {
            $id = $this->save_($rec);
        }
        
        return $id;
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
        
        return cls::haveInterface('crm_ContragentAccRegIntf', $folderClass) || $folderClass == 'doc_UnsortedFolders';
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
        
        if (!Request::get('ajax_mode')) {
        	// Записваме, че потребителя е разглеждал този списък
        	$this->logWrite('Създаване на обратен мемориален ордер', $result[1]);
        }
        
        return new Redirect(array('acc_Articles', 'single', $result[1]), "|Създаден е успешно обратен мемориален ордер");
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
            'reason'        => tr('Сторниране на') . " " .  mb_strtolower($mvc->singleTitle) . " №{$journlRec->docId} / " . acc_Journal::recToVerbal($journlRec, 'valior')->valior,
            'valior'        => dt::now(),
            'useCloseItems' => 'yes',
            'totalAmount'   => $journlRec->totalAmount,
            'state'         => 'draft',
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

        $count = 0;
        while($dRec = $dQuery->fetch()){
        	$count++;
        	$jCount = 0;
        	
            foreach ($jRecs as $jRec){
            	$jCount++;
            	
                if($count === $jCount && $dRec->debitAccId == $jRec->debitAccId && $dRec->debitEnt1 == $jRec->debitItem1 && $dRec->debitEnt2 == $jRec->debitItem2 && $dRec->debitEnt3 == $jRec->debitItem3 &&
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
    
    
    /**
     * След подготовка на полетата
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
    	$baseCode = acc_Periods::getBaseCurrencyCode();
    	$data->listFields['totalAmount'] .= "|* ({$baseCode})";
    }
    
    
    /**
     * Метод за създаване на чернова МО
     * 
     * @param int $folderId       - в коя папка
     * @param date|NULL $valior   - вальор
     * @param string|NULL $reason - описание
     * @return int 
     */
    public static function createDraft($folderId, $valior = NULL, $reason = NULL)
    {
    	// Проверка
    	expect(doc_Folders::fetch($folderId), 'Не е валидна папка');
    	expect(static::canAddToFolder($folderId), 'Не може да се добави в папката');
    	expect(doc_Folders::haveRightToFolder($folderId), 'Потребителя няма достъп до папката');
    	
    	// Девербализиране на стойностите
    	$valior = isset($valior) ? cls::get('type_Date')->fromVerbal($valior) : dt::today();
    	expect($valior);
    	
    	$reason = (!empty($reason)) ? cls::get('type_Varchar')->fromVerbal($reason) : NULL;
    	
    	// Създаване на МО
    	$rec = (object)array('valior' => $valior, 'reason' => $reason, 'folderId' => $folderId);
    	static::save($rec);
    	
    	// Връщане на ид-то на създадения МО
    	return $rec->id;
    }
    
    
    /**
     * Метод за добавяне на ред към чернова на МО
     * 
     * @param int $id             - ид на МО
     * @param array $debitArr     - Дебитен масив
     * 				[0]           - Систем ид на сметка
     * 				[1]           - Перо на първа позиция или масив със клас и ид на запис който ще се форсира в номенклатурата при нужда
     * 				[2]           - Перо на втора позиция или масив със клас и ид на запис който ще се форсира в номенклатурата при нужда
     * 				[3]           - Перо на трета позиция или масив със клас и ид на запис който ще се форсира в номенклатурата при нужда
     * 				['quantity']  - количество на дебита
     * @param array $creditArr    - Кредитен масив
     * 				[0]           - Систем ид на сметка
     * 				[1]           - Перо на първа позиция или масив със клас и ид на запис който ще се форсира в номенклатурата при нужда
     * 				[2]           - Перо на втора позиция или масив със клас и ид на запис който ще се форсира в номенклатурата при нужда
     * 				[3]           - Перо на трета позиция или масив със клас и ид на запис който ще се форсира в номенклатурата при нужда
     * 				['quantity']  - количество на дебита
     * @param string|NULL $amount - Сума на операцията в основна валута
     * @return int
     */
    public static function addRow($id, $debitArr, $creditArr, $amount = NULL)
    {
    	// Проверки
    	expect($masterRec = acc_Articles::fetch($id), "Несъществуващ мемориален ордер");
    	expect($masterRec->state == 'draft', "Мемориалния ордер трябва не е чернова");
    	
    	expect(is_array($debitArr));
    	expect(count($debitArr) == 5);
    	
    	expect($debitAccRec = acc_Accounts::getRecBySystemId($debitArr[0]), "Няма сметка с това sysId '{$debitArr[0]}'");
    	expect($creditAccRec = acc_Accounts::getRecBySystemId($creditArr[0]), "Няма сметка с това sysId '{$creditArr[0]}'");
    	
    	// Дали се изисква само количество
    	$quantityOnly = ($debitAccRec->type == 'passive' && $debitAccRec->strategy) ||
    	($creditAccRec->type == 'active' && $creditAccRec->strategy);
    	
    	// За дебита и кредита
    	foreach (array('debit', 'credit') as $type){
    		$arr = &${"{$type}Arr"};
    		$accRec = ${"{$type}AccRec"};
    		$arr[0] = $accRec->id;
    		
    		// За всяка позиция
    		foreach (range(1, 3) as $i){
    			
    			// Ако има номенклатура на тази позиция
    			if(isset($accRec->{"groupId{$i}"})){
    				expect($item = $arr[$i], "Трябва да има перо на позиция {$i}");
    				$listRec = acc_Lists::fetch($accRec->{"groupId{$i}"});
    				$interface = core_Interfaces::fetchField($listRec->regInterfaceId, 'name');
    				
    				// И перото е масив форсира се, ако може
    				if(is_array($item)){
    					expect(count($item) == 2, 'Масива трябва да е точно с 2 елемента');
    					expect($Class = cls::get($item[0]), "Невалиден клас");
    					expect($Class->fetch($item[1]), "Няма такъв запис");
    					
    					expect(cls::haveInterface($listRec->regInterfaceId, $Class), "'{$Class->className}' няма интерфейс '{$interface}'");
    					if(cls::haveInterface('doc_DocumentIntf', $Class)){
    						expect($Class->fetch($item[1])->state != 'draft', 'Документа не трябва да е чернова');
    					}
    		
    					$arr[$i] = acc_Items::force($item[0], $item[1], $accRec->{"groupId{$i}"});
    					
    				} else {
    					
    					// Ако е подадено ид, се очаква това да е перо от номенклатурата
    					expect($itemRec = acc_Items::fetch($item), "Няма перо с ид '{$item}'");
    					expect(cls::haveInterface($listRec->regInterfaceId, $itemRec->classId), "Перото с ид {$item} няма нужния интерфейс '{$interface}'");
    				}
    			} else {
    				
    				// Ако няма номенклатура, не трябва да има перо
    				expect(is_null($arr[$i]), "На позиция {$i} не трябва да има перо");
    			}
    		
    			expect(isset($arr['quantity']), 'Няма количество');
    			expect($arr['quantity'] = cls::get('type_Double')->fromVerbal($arr['quantity']), 'Невалидно количество');
    		}
    	}
    	
    	// Ако сумата ще не идва по стратегия, трябва да е подадена
    	if($quantityOnly === FALSE){
    		expect(isset($amount), 'Трябва да има задължително сума на транзакцията');
    	}
    	
    	if(isset($amount)){
    		expect($amount = cls::get('type_Double')->fromVerbal($amount), "Невалидна сума '{$amount}'");
    	} else {
    		$amount = NULL;
    	}
    	
    	// Подготовка на записа
    	$rec = (object)array('articleId'      => $masterRec->id, 
    						 'debitAccId'     => $debitArr[0],
    						 'debitEnt1'      => $debitArr[1],
    			             'debitEnt2'      => $debitArr[2],
    					     'debitEnt3'      => $debitArr[3],
    						 'debitQuantity'  => $debitArr['quantity'],
    			             'creditAccId'    => $creditArr[0],
    			             'creditEnt1'     => $creditArr[1],
    			             'creditEnt2'     => $creditArr[2],
    			             'creditEnt3'     => $creditArr[3],
    			             'creditQuantity' => $creditArr['quantity'],
    						 'amount'         => $amount,);
    	
    	// Запис
    	return acc_ArticleDetails::save($rec);
    }
}
