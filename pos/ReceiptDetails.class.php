<?php



/**
 * Мениджър за "Бележки за продажби" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_ReceiptDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на бележката';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, survey_Wrapper, plg_Sorting';
    
  
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'receiptId';
	
	
    /**
     * Кои полета да се показват в листовия изглед
     */
    //var $listFields = 'tools=Пулт, surveyId, label, image';
    
    
    /**
	 *  Брой елементи на страница 
	 */
	var $listItemsPerPage = "20";

    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('receiptId', 'key(mvc=pos_Receipts)', 'caption=Бележка, input=hidden, silent');
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,notNull,mandatory');
    	$this->FLD('price', 'float(minDecimals=2)', 'caption=Цена,width=6em');
        $this->FLD('quantity', 'int', 'caption=К-во, mandatory,width=6em');
    	$this->FLD('amount', 'float(minDecimals=2)', 'caption=Сума, input=none,width=6em');
    	$this->FLD('discountPercent', 'percent', 'caption=Отстъпка->Процент,width=6em');
        $this->FLD('discountSum', 'float(minDecimals=2)', 'caption=Отстъпка->Сума,width=6em');
    }
    
    
	/**
     * Подготовка на Детайлите
     */
    function prepareDetail_($data)
    {
    	$this->prepareAddForm($data);
    	parent::prepareDetail_($data);
    	
    }
    
    
    /**
     * 
     */
    function prepareAddForm(&$data)
    {
    	if($this->haveRightFor('add')) {
	    	$form = static::getForm();
	    	$form->layout= new ET(getFileContent("pos/tpl/DetailsForm.shtml"));
	    	$form->fieldsLayout= new ET(getFileContent("pos/tpl/DetailsFormFields.shtml"));
	    	$form->action = array($this, 'save', 'ret_url' => TRUE);
	    	$form->setDefault('receiptId', $data->masterId);
	    	$form->toolbar->addSbBtn('запис');
	    	$this->invoke('AfterPrepareEditForm', array($form));
	    	$data->addForm = $form;
	    }
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET(getFileContent('pos/tpl/ReceiptDetail.shtml'));
    	$tplAlt = $tpl->getBlock('ROW');
    	if($data->rows) {
	    	foreach($data->rows as $row) {
	    		$rowTpl = clone($tplAlt);
	    		$rowTpl->placeObject($row);
	    		$rowTpl->removeBlocks();
	    		$tpl->append($rowTpl);
	    	}
    	}
    	
    	$tpl->append($data->addForm->renderHtml(), 'ADD_FORM');
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	$row->amount = $double->toVerbal($rec->amount);
    	$row->price = $double->toVerbal($rec->price);
    	$row->quantity = $double->toVerbal($rec->quantity);
    	
    	//@TODO
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	//@TODO
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	//@TODO
    }
}