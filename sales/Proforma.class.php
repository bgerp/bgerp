<?php



/**
 * Документ за проформа фактура от продажба
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Proforma extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Prof';
    
    
    /**
     * Заглавие
     */
    public $title = 'Проформи фактура';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Проформа фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, doc_DocumentPlg, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, Sale=sales_Sales,
                    doc_plg_BusinessDoc2, doc_plg_HidePrices, acc_plg_DocumentSummary, doc_ActivatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, number, date, saleId, folderId, createdOn, createdBy';
    
    
    /**
     * Колоната, в която да се появят инструментите на plg_RowTools
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
    
	/**
	 * Поле за единичния изглед
	 */
	public $rowToolsSingleField = 'number';
	
	
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number,folderId,saleId';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutProforma.shtml';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.8|Търговия";
    
    
    /**
     * Полета свързани с цени
     */
    //public $priceFields = 'dealValue,vatAmount,baseAmount,total,vatPercent,discountAmount';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'date';
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('saleId', 'key(mvc=sales_Sales)', 'caption=Продажба,input=hidden,mandatory');
    	$this->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,  notNull, mandatory');
        $this->FLD('number', 'int', 'caption=Номер, export=Csv');
        $this->FLD('note', 'text(rows=3)', 'caption=Допълнително->Условия,width=100%');
    	
        $this->setDbUnique('number');
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$form = &$data->form;
    	
    	$form->rec->date = dt::today();
        if(!haveRole('ceo,sales')){
        	$form->setField('number', 'input=none');
        }
        
    	$origin = $mvc->getOrigin($form->rec);
    	$form->rec->saleId = $origin->that;
    	
    	$form->toolbar->removeBtn('activate');
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	
    		if($rec->number){
		        if(!$mvc->isNumberInRange($rec->number)){
					$form->setError('number', "Номер '{$rec->number}' е извън позволения интервал");
				}
	        }
    	}
    }
    
    
	/**
     * След подготовка на еденичния изглед
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingle($mvc, &$data)
    {
    	$rec = &$data->rec;
    	$row = &$data->row;
    	
    	$Double = cls::get('type_Double');
    	
    	$sData = new stdClass();
    	$sData->rec = $mvc->Sale->fetch($rec->saleId);
    	$sData->fromProforma = TRUE;
    	
        $mvc->Sale->prepareSingle($sData);
        $data->saleData = $sData;
    	$dRows = &$sData->sales_SalesDetails->rows;
    	if(count($dRows)){
    		foreach ($dRows as $id  => &$dRow){
    			$dRow->quantity->removeBlock('packQuantityDelivered');
    			$dRow->productId = strip_tags($dRow->productId);
    		}
    	}
    	
    	$rec = (object)((array)$data->rec + (array)$sData->rec);
    	$row = (object)((array)$data->row + (array)$sData->row);
    	
    	$mvc->prepareSale($row, $rec);
    }
    
    
    /**
     * Филтър на продажбите
     */
    static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
		$data->listFilter->showFields .= ',search';
    }
    
    
    /**
     * След като се подготвят данните на продажбата
     */
    private function prepareSale(&$row, $rec)
    {
    	if($rec->bankAccountId){
	    	$Varchar = cls::get('type_Varchar');
	    	$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->bankAccountId);
	    	$row->bank = $Varchar->toVerbal($ownAcc->bank);
	    	$row->bic = $Varchar->toVerbal($ownAcc->bic);
	    }
    	
	    $row->header = "Проформа №<b>{$rec->id}</b> ({$row->state})" ;
	    $userRec = core_Users::fetch($rec->createdBy);
		$row->username = core_Users::recToVerbal($userRec, 'names')->names;
		
		if($rec->currencyRate == 1){
			unset($row->currencyRate);
		}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */	
    		
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($rec->number){
    		$row->number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
    	}
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		
    		if($rec->number){
    			$row->number = ht::createLink($row->number, array($mvc, 'single', $rec->id),NULL, 'ef_icon=img/16/invoice.png');
    		}
    		
    		$row->saleId = $mvc->Sale->getLink($rec->saleId);
    	}
    }
    
    
    /**
     * След подготовка на еденичния изглед
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterRenderSingle($mvc, &$tpl, &$data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	$tpl->push('sales/tpl/invoiceStyles.css', 'CSS');
    	
    	$saleDetails = $data->saleData->sales_SalesDetails;
    	$dTpl = $mvc->Sale->sales_SalesDetails->renderDetailLayout($saleDetails);
        $dTpl->append($mvc->Sale->sales_SalesDetails->renderListTable($saleDetails), 'ListTable');
    	
        $tpl->append($dTpl, 'PRODUCTS');
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
    	if($rec->state == 'active'){
        	if(empty($rec->number)){
        		$rec->number = $mvc->getNexNumber();
        	}
    	}
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
	/**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Дали документа може да се добави към нишката
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        
        if($firstDoc->instance instanceof sales_Sales){
        	$state = $firstDoc->fetchField('state');
        	
        	return ($state != 'active') ? FALSE : TRUE;
        }
        
        return FALSE;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
		$row = new stdClass();
        $row->title = "Проформа фактура №{$id}";
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->authorId = $rec->createdBy;
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
	/**
     * Дали подадения номер е в позволения диапазон за номера на фактури
     * @param $number - номера на фактурата
     */
    private static function isNumberInRange($number)
    {
    	expect($number);
    	$conf = core_Packs::getConfig('sales');
    	
    	return ($conf->SALE_PROFORMA_MIN_NUMBER <= $number && $number <= $conf->SALE_PROFORMA_MAX_NUMBER);
    }
    
    
	/**
     * Ф-я връщаща следващия номер на проформата, ако той е в границите
     * @return int - следващия номер на проформата
     */
    private function getNexNumber()
    {
    	$conf = core_Packs::getConfig('sales');
    	
    	$query = $this->getQuery();
    	$query->XPR('maxNum', 'int', 'MAX(#number)');
    	if(!$maxNum = $query->fetch()->maxNum){
    		$maxNum = $conf->SALE_PROFORMA_MIN_NUMBER;
    	}
    	$nextNum = $maxNum + 1;
    	
    	if($nextNum > $conf->SALE_PROFORMA_MAX_NUMBER) return NULL;
    	
    	return $nextNum;
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата проформа фактура") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
}