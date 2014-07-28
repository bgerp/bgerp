<?php



/**
 * Документ "Проформа фактура"
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Proformas extends core_Master
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
    public $title = 'Проформа фактури';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Проформа фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, Sale=sales_Sales,
                    doc_plg_HidePrices, doc_ActivatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, number=Номер, date, saleId, folderId, createdOn, createdBy';
    
    
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
    public $searchFields = 'folderId,saleId';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutProforma.shtml';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'sales_Proforma';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.8|Търговия";
    
    
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
        $this->FLD('note', 'text(rows=3)', 'caption=Допълнително->Условия');
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	// Попълване на дефолт данни
    	$form = &$data->form;
    	
    	$origin = $mvc->getOrigin($form->rec);
    	$form->rec->saleId = $origin->that;
    	$form->rec->date = dt::today();
    	
    	// Да не може да се активира при редакция
    	$form->toolbar->removeBtn('activate');
    }
    
    
	/**
     * След подготовка на еденичния изглед
     */
    static function on_AfterPrepareSingle($mvc, &$data)
    {
    	$rec = &$data->rec;
    	$row = &$data->row;
    	
    	// Подготвяне на данни за подготовка на продажбата
    	$sData = new stdClass();
    	$sData->rec = $mvc->Sale->fetch($rec->saleId);
    	
    	// Флаг че се създава извиква от проформа
    	$sData->fromProforma = TRUE;
    	$sData->noTotal = $data->noTotal;
    	
    	// Подготвяне на сингъл данните на продажбата за използване в проформата
        $mvc->Sale->prepareSingle($sData);
        $data->saleData = $sData;
        
        // Премахване на някои специфични неща от продажбата, които нетрябва да се показват в проформа
    	$dRows = &$sData->sales_SalesDetails->rows;
    	if(count($dRows)){
    		foreach ($dRows as $id  => &$dRow){
    			$dRow->quantity = $dRow->packQuantity;
    			$dRow->productId = strip_tags($dRow->productId);
    		}
    	}
    	
    	// Добавяне към проформата готовите данни от продажбата
    	$rec = (object)((array)$data->rec + (array)$sData->rec);
    	$row = (object)((array)$data->row + (array)$sData->row);
    	
    	// Допълнителни обработки по представянето
    	$mvc->prepareSale($row, $rec);
    }
    
    
    /**
     * Филтър на проформите
     */
    static function on_AfterPrepareListFilter(core_Mvc $mvc, &$data)
    {
		$data->listFilter->showFields .= ', search';
    }
    
    
    /**
     * След като се подготвят данните на продажбата
     */
    private function prepareSale(&$row, $rec)
    {
    	// Показване на името и бика на банката, ако има б. сметка
    	if($rec->bankAccountId){
	    	$ownAcc = bank_Accounts::fetch($rec->bankAccountId);
	    	$accRow = bank_Accounts::recToVerbal($ownAcc);
	    	$row->bank = $accRow->bank;
	    	$row->bic = $accRow->bic;
	    	$rec->bankAccountId = $accRow->iban;
	    }
    	
	    $row->header = "{$this->singleTitle} #<b>{$this->abbr}{$rec->id}</b> ({$row->state})" ;
	    $userRec = core_Users::fetch($rec->createdBy);
		$row->username = core_Users::recToVerbal($userRec, 'names')->names;
		
		// Ако курса е 1-ца, не се показва
		if($rec->currencyRate == 1){
			unset($row->currencyRate);
		}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */	
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = str_pad($rec->id, '10', '0', STR_PAD_LEFT);
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		$row->number = ht::createLink($row->number, array($mvc, 'single', $rec->id),NULL, 'ef_icon=img/16/invoice.png');
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
    	// Премахване на блока 'header'
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	
    	$tpl->push('sales/tpl/invoiceStyles.css', 'CSS');
    	
    	// Рендиране на детайлите на продажбата, за показване в проформата
    	$saleDetails = $data->saleData->sales_SalesDetails;
    	$dTpl = $mvc->Sale->sales_SalesDetails->renderDetailLayout($saleDetails);
        $dTpl->append($mvc->Sale->sales_SalesDetails->renderListTable($saleDetails), 'ListTable');
    	
        // Добавяне на детайлите на продажбата в шаблона на проформата
        $tpl->append($dTpl, 'PRODUCTS');
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
    	// От лист изгледа не може да се добавя проформа
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
        // Кой е първия документ в нишката
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
        
    	// Ако е продажба
        if($firstDoc->instance instanceof sales_Sales){
        	
        	// Ако е активирана продажба
        	$state = $firstDoc->fetchField('state');
        	
        	// Може да се добавя само към активирана продажба
        	return ($state != 'active') ? FALSE : TRUE;
        }
        
        // Ако първия документ не е продажба, връщаме FALSE
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