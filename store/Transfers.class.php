<?php
/**
 * Клас 'store_Transfers'
 * Документ за междускладови трансфери
 *
 * 
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Transfers extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Междускладови трансфери';


    /**
     * Абревиатура
     */
    public $abbr = 'St';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, store_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, store_plg_Document, doc_plg_BusinessDoc2';

    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,store';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,store';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,store';


    /**
     * Кой може да го види?
     */
    public $canViewprices = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
   public $listFields = 'id, valior, fromStore, toStore, folderId, volume, weight,createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'store_TransfersDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Междускладов трансфер';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutTransfers.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Логистика";


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('fromStore', 'key(mvc=store_Stores,select=name)', 'caption=От склад,mandatory');
 		$this->FLD('toStore', 'key(mvc=store_Stores,select=name)', 'caption=До склад,mandatory');
 		$this->FLD('weight', 'double(decimals=2)', 'input=none,caption=Тегло');
        $this->FLD('volume', 'double(decimals=2)', 'input=none,caption=Обем');
        
        // Доставка
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        $this->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty)', 'caption=Транс. линия');
        
        // Допълнително
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    }



    /**
     * След промяна в детайлите на обект от този клас
     *
     * @param core_Manager $mvc
     * @param int $id ид на мастър записа, чиито детайли са били променени
     * @param core_Manager $detailMvc мениджър на детайлите, които са били променени
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
    	$rec = $mvc->fetch($id);
    	$dQuery = $detailMvc->getQuery();
    	$dQuery->where("#transferId = {$id}");
    	$measures = $mvc->getMeasures($dQuery->fetchAll());
    	
    	$rec->weight = $measures->weight;
    	$rec->volume = $measures->volume;
    	
    	$mvc->save($rec);
    }
    
    
    /**
     * След рендиране на сингъла
     */
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
    	$data->row->header = $mvc->singleTitle . " №<b>{$data->row->id}</b> ({$data->row->state})";
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param store_Stores $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('valior', dt::today());
        $data->form->setDefault('fromStore', store_Stores::getCurrent('id', FALSE));
    }
    
    
	/**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	
        	if($rec->fromStore == $rec->toStore){
        		$form->setError('toStore', 'Складовете трябва да са различни');
        	}
        }
    }


    /**
     * СР не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
    	
        return cls::haveInterface('store_TransferFolderCoverIntf', $folderClass);
    }
        
    
    /**
     * @param int $id key(mvc=store_Receipts)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = "Междускладов трансфер №{$rec->id} / " . $this->getVerbal($rec, 'valior');
        
        $row = (object)array(
            'title'    => $title,
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
	/**
     * Връща масив от използваните нестандартни артикули в СР-то
     * @param int $id - ид на СР
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->store_TransfersDetails->getQuery();
    	$dQuery->EXT('state', 'store_Transfers', 'externalKey=transferId');
    	$dQuery->where("#transferId = '{$id}'");
    	$dQuery->groupBy('productId,classId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->classId);
    		if(cls::haveInterface('doc_DocumentIntf', $productMan)){
    			$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
    		}
    	}
    	return $res;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('store_TransferFolderCoverIntf');
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function getTransaction($id)
    {
        // Извличане на мастър-записа
        expect($rec = self::fetchRec($id));

        $result = (object)array(
            'reason' => $rec->reason,
            'valior' => $rec->valior,
            'totalAmount' => $rec->totalAmount,
            'entries' => array()
        );
        
        $dQuery = store_TransfersDetails::getQuery();
        $dQuery->where("#transferId = '{$rec->id}'");
        while($dRec = $dQuery->fetch()){
        	
        	// Ако артикула е вложим сметка 302 иначе 321
        	$accId = ($dRec->isConvertable == 'yes') ? '302' : '321';
        	$result->entries[] = array(
        		 'debit'  => array($accId, // Сметка "302. Суровини и материали" или Сметка "321. Стоки и Продукти"
                       array('store_Stores', $rec->fromStore), // Перо 1 - Склад
                       array($dRec->classId, $dRec->productId),  // Перо 2 - Артикул
                  'quantity' => $dRec->quantity, // Количество продукт в основната му мярка,
	             ),
	             
                  'credit' => array($accId, // Сметка "302. Суровини и материали" или Сметка "321. Стоки и Продукти"
                       array('store_Stores', $rec->toStore), // Перо 1 - Склад
                       array($dRec->classId, $dRec->productId),  // Перо 2 - Артикул
                  'quantity' => $dRec->quantity, // Количество продукт в основната му мярка
	             ),
	       );
        }
        
        return $result;
    }
        
    
	/**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function finalizeTransaction($id)
    {
        $rec = self::fetchRec($id);
        $rec->state = 'active';
        
        return self::save($rec, 'state');
    }
    
    
    /**
     * Помощен метод за показване на документа в транспортните линии
     * @param stdClass $rec - запис на документа
     * @param stdClass $row - вербалния запис
     */
    private function prepareLineRows($rec)
    {
    	$row = $this->recToVerbal($rec);
    	$row->rowNumb = $rec->rowNumb;
    	//$row->address = $oldRow->contragentName;
    	$row->TR_CLASS = ($rec->rowNumb % 2 == 0) ? 'zebra0' : 'zebra1';
    	$row->docId = $this->getDocLink($rec->id);
    	
    	return $row;
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function prepareTransfers($data)
    {
    	$masterRec = $data->masterData->rec;
    	$query = $this->getQuery();
    	$query->where("#lineId = {$masterRec->id}");
    	$query->orderBy("#createdOn", 'DESC');
    	
    	$i = 1;
    	while($dRec = $query->fetch()){
    		$dRec->rowNumb = $i;
    		$data->transfers[$dRec->id] = $this->prepareLineRows($dRec);
    		$i++;
    	}
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderTransfers($data)
    {
    	$table = cls::get('core_TableView');
    	$fields = "rowNumb=№,docId=Документ,weight=Тегло,volume=Обем,address=@Адрес";
    	
    	return $table->get($data->transfers, $fields);
    }
}