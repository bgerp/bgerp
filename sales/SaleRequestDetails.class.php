<?php



/**
 * Мениджър за "Детайли на заявките за продажба" 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class sales_SaleRequestDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на заявките за продажба';
    
    
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'requestId';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'ceo,sales';
    
    
    /**
     * Кой може да променя?
     */
    var $canDelete = 'ceo,sales';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_AlignDecimals, plg_RowNumbering, sales_Wrapper';
    
    
    /**
     * Кой може да променя?
     */
    var $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, quantity, uomId=Мярка, price, discount, amount=Сума';
    
    
    /**
     * Кой таб да бъде отворен
     */
    var $currentTab = 'Заявки';
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('requestId', 'key(mvc=sales_SaleRequests)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('productManId', 'key(mvc=core_Classes)', 'input=hidden,caption=Продуктов мениджър, silent');
    	$this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'input=hidden,caption=Политика, silent');
    	$this->FLD('quantity', 'double', 'caption=К-во,width=8em');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Ед. цена,width=8em');
        $this->FLD('discount', 'percent(decimals=2,min=0)', 'caption=Отстъпка,width=8em');
    }
    
    
	/**
     * Сортиране по ред на добавяне
     */
    static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'ASC');
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$rec = &$data->form->rec;
    	$productMan = ($rec->productManId) ? cls::get($rec->productManId) : cls::get($rec->policyId)->getProductMan();
    	$data->form->setOptions('productId', array($rec->productId => $productMan->getTitleById($rec->productId)));
    	$masterRec = $data->masterRec;
    	
     	if(!empty($rec->price)) {
            if($masterRec->vat == 'yes') {
                $rec->price *= 1 + $productMan->getVat($rec->productId, $masterRec->createdOn);
            }
            $rec->price /= $masterRec->rate;
        }
        
        $data->form->title = "Редактиране на запис към заявка #Sr{$masterRec->id}";
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, $form)
    { 
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	$masterRec = $mvc->Master->fetch($rec->requestId);
        	if(!empty($rec->price)){
        	 	$rec->price *= $masterRec->rate;
                $productMan = ($rec->productManId) ? cls::get($rec->productManId) : cls::get($rec->policyId)->getProductMan();
                if ($masterRec->vat == 'yes') {
                    $rec->price /= 1 + $productMan->getVat($rec->productId, $masterRec->createdOn);
                	
                }
        	} else {
        		$Policy = cls::get($rec->policyId);
        		$price = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, NULL, $rec->quantity, $masterRec->createdOn);
        		if(!$price){
        			$form->setError('price', 'Неможе да оставите празна цена !');
        		}
        		$rec->price = $price->price;
        	}
        	
        	if(!$rec->quantity){
        		$form->setError('quantity', 'Трябва да се посочи к-во !');
        	}
        }
    }   
        
    
    /**
     * Скриване на колоната за отстъпка ако няма отстъпки
     */
    public function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $rows = $data->rows;
        $haveDiscount = FALSE;
        if(count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
            	$haveDiscount = $haveDiscount || !empty($data->recs[$i]->discount);
            }
        }
        
    	if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$productMan = ($rec->productManId) ? cls::get($rec->productManId) : cls::get($rec->policyId)->getProductMan();
    	
    	$applyVat = $mvc->Master->fetchField($rec->requestId, 'vat');
    	$row->productId = $productMan->getTitleById($rec->productId);
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && $productMan->haveRightFor('read', $rec->productId)){
    		$row->productId = ht::createLinkRef($row->productId, array($productMan, 'single', $rec->productId));
    	}
    		
    	if($applyVat == 'yes'){
    		$vat = $productMan->getVat($rec->productId);
    		$rec->price = $rec->price * (1 + $vat);
    	}
    	
    	$measureId = $productMan->getProductInfo($rec->productId, NULL)->productRec->measureId;
    	$row->uomId = cat_UoM::getTitleById($measureId);
    	
    	$row->amount = $mvc->fields['price']->type->toverbal($rec->price * $rec->quantity);
    	$row->amount = "<div style='text-align:right'>{$row->amount}</div>";
    	$row->price = $mvc->fields['price']->type->toverbal($rec->price);
    }
    
    
	/**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if(($action == 'edit' || $action == 'delete') && isset($rec)){
    		$masterState = $mvc->Master->fetchField($rec->requestId, 'state');
    		if($masterState != 'draft'){
    			$res = 'no_one';
    		}
    	}
    }
}