<?php



/**
 * Модел Отчети
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_Reports extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'PoS Репорти';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, pos_Wrapper,  plg_Printing,
     	  doc_DocumentPlg, bgerp_plg_Blank, doc_ActivatePlg';
    
  
    /**
     * Кои полета да се показват в листовия изглед
     */
    //var $listFields = 'id, iban, contragent=Контрагент, currencyId, type';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Отчет";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/report.png';
    

    /**
	 *  Брой елементи на страница 
	 */
    var $listItemsPerPage = "20";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'pos, ceo, admin';
    
    
    /**
	 * Детайли на репорта
	 */
	var $details = 'pos_ReportDetails';
	
	
	/**
     * Абревиатура
     */
    var $abbr = "Rep";
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'pos, ceo, admin';
    
    
    /**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'pos/tpl/SingleReport.shtml';
	
	
	/**
     * Групиране на документите
     */
    var $newBtnGroup = "3.4|Търговия";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('cashier', 'user(roles=pos|admin)', 'caption=Касиер, width=9em');
    	$this->FLD('pointId', 'key(mvc=pos_Points, select=title)', 'caption=Точка, width=9em, mandatory');
    	$this->FLD('beginDate', 'date(format=d.m.Y)', 'caption=Период->От,width=6em');
    	$this->FLD('endDate', 'date(format=d.m.Y)', 'caption=Период->До,width=6em');
    	$this->FLD('paid', 'float(minDecimals=2)', 'caption=Общо, input=none, value=0');
    	$this->FLD('total', 'float(minDecimals=2)', 'caption=Общо, input=none, value=0');
    	$this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,input=none,width=8em');
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	if(haveRole('pos')) {
    		$data->form->setField('cashier', core_Users::getCurrent());
    	}
    	$data->form->setDefault('endDate', dt::verbal2mysql());
    	$data->form->setField('cashier',pos_Points::getCurrent('id', FALSE));
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	if($form->isSubmitted()) {
    		if($form->rec->beginDate && $form->rec->endDate) {
    			if($form->rec->beginDate >= $form->rec->endDate) {
    				$form->setError('beginDate', "Началната дата е по-голяма от крайната !");
    			}
    		}
    	}
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	// Показваме заглавието само ако не сме в режим принтиране
    	if(!Mode::is('printing')){
    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	}
    	
    	if($rec->state == 'draft'){
    		$row->total = "<span style='color:darkred'>" .tr("Репорта още не е активиран") . "</span>";
    	}
    }
    
    
     static function on_AfterSave($mvc, &$id, $rec)
     {
    	if($rec->state == 'draft') {
    		pos_ReportDetails::delete("#reportId = {$rec->id}");
    		$reportData = pos_Receipts::fetchReportData($rec->pointId, $rec->cashier, $rec->beginDate, $rec->endDate);
    		foreach($reportData as $detail){
    			$detail->reportId = $id;
    			$mvc->pos_ReportDetails->save($detail);
    		}
    		
    		$saleAmount = $paymentAmount = 0;
    		foreach($reportData as $detail) {
    			${"{$detail->action}Amount"} += $detail->amount;	
    		}
    	}
     }
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "PoS Репорт №{$rec->id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
}