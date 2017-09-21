<?php


/**
 * Драйвер за протокол за продажбите без фактура (съгл. Чл. 119, ал. 1 до 4 от ЗДДС)
 *
 * @category  bgerp
 * @package   sales
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Продажби без фактура
 */
class sales_reports_ZDDSRep extends frame2_driver_TableData
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'ceo, store, sales, admin, purchase';
	
	
	 /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField = 'containerId';
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = 'containerId';
	
    
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,after=title');
	}


	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
	{
	    $form = &$data->form;
	    
	    $prevMonth = dt::addMonths(-1,dt::today());
	    $op = acc_Periods::fetchByDate($prevMonth);

	    $form->setDefault('periodId', $op->id);
	}

	
	/**
	 * Кои записи ще се показват в таблицата
	 *
	 * @param stdClass $rec
	 * @param stdClass $data
	 * @return array
	 */
	protected function prepareRecs($rec, &$data = NULL)
	{

	    $recs = array();
	
	    core_App::setTimeLimit(500);
	    $Sales = cls::get('sales_Sales');

	    $period = acc_Periods::fetch($rec->periodId); 
	    
	    // Обикаляме по бързите Продажби
	    $this->prepareQuery($query, $data, $period, 'sales_Sales', 'sales_SalesDetails', 'saleId');
	    // Обикаляме по Експедиционните
	    $this->prepareQuery($query, $data, $period, 'store_ShipmentOrders', 'store_ShipmentOrderDetails', 'shipmentId');
	    // Обикаляме по Складовите
	    $this->prepareQuery($query, $data, $period, 'store_Receipts', 'store_ReceiptDetails', 'receiptId');
	    // Обикаляме по Фактурите
	    $this->prepareQuery($query, $data, $period, 'sales_Invoices', 'sales_InvoiceDetails', 'invoiceId');
	    
	    if(is_array($data->recs)) {
    	    foreach($data->recs as $pRec) {
    
    	        $quantity = 0;
    	        $amount = 0;
    	       
        	    switch (strstr($pRec->doc,"|", TRUE)) {
        	        case 'sales_Sales':
        	            $quantity += $pRec->quantity;
        	            $amount += $pRec->amount;
        	            break;
        	            
        	        case 'store_ShipmentOrders':
        	            $quantity += $pRec->quantity;
        	            $amount += $pRec->amount;
        	            break;
        	        
        	        case 'store_Receipts':
        	            $quantity -= $pRec->quantity;
        	            $amount -= $pRec->amount;
        	            break;
    
        	    }
    
    
    	        if(!array_key_exists($pRec->article, $recs)) {
    	           $recs[$pRec->article] = (object) array ('code' => $pRec->code,
                                        'article' => $pRec->article,
                                        'price' => "",
                                        'measure' => $pRec->measure,
                                        'quantity' => $quantity,
                                        'amount' => $amount,
                                        'quantityInv' => $pRec->quantityInv,
                                        'amountInv' => $pRec->amountInv,
                                        'amountVat' => '',
                                        'amountVatInv' => '');
    	        } else {
    	            $obj = &$recs[$pRec->article];
    	            
    	            switch (strstr($pRec->doc,"|", TRUE)) {
    	                case 'sales_Sales':
    	                    $obj->quantity += $pRec->quantity;
    	                    $obj->amount += $pRec->amount;
    	                    break;
    	                     
    	                case 'store_ShipmentOrders':
    	                    $obj->quantity += $pRec->quantity;
    	                    $obj->amount += $pRec->amount;
    	                    break;
    	                     
    	                case 'store_Receipts':
    	                    $obj->quantity -= $pRec->quantity;
    	                    $obj->amount -= $pRec->amount;
    	                    break;
    	            } 
    	            
    	            $obj->quantityInv += $pRec->quantityInv;
    	            
    	            if(isset($type) == 'dc_note') {
    	                if($pRec->amountInv < 0){
    	                    $obj->amountInv -= $pRec->amountInv;
    	                } else {
    	                    $obj->amountInv += $pRec->amountInv;
    	                }
    	            } 
    	            
    	            if(isset($type) == 'invoice') {
    	               $obj->amountInv += $pRec->amountInv;
    	            }
    	        }
    	    }
    
    	    foreach($recs as $id=>$r) {
    	        $r->amount = round($r->amount,2);
    	        
    	        $vat = cat_Products::getVat($r->article, $r->valior);
    	
    	        $r->amountVat = $r->amount + ($r->amount * $vat); 
    	        
    	        if($r->amount && $r->quantity > 0) {
    	           $r->price = $r->amount / $r->quantity;
    	        }
    	        
    	        if(isset($r->amountInv)) {
    	           $r->amountVatInv = $r->amountInv + ($r->amountInv * $vat); 
    	        }
    
    	        $r->priceVat = $r->price + ($r->price * $vat); 
    
    	    }
	    }
	    
	    usort($recs, function($a, $b) {
	        	
	        return strnatcmp(mb_strtolower($a->code, 'UTF-8'), mb_strtolower($b->code, 'UTF-8'));
	    });

	    return $recs;
	}

	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec      - записа
	 * @param boolean $export    - таблицата за експорт ли е
	 * @return core_FieldSet     - полетата
	 */
	protected function getTableFieldSet($rec, $export = FALSE)
	{
	    $fld = cls::get('core_FieldSet');
	
	    if($export === FALSE){
	        $fld->FLD('code', 'varchar', 'caption=Код');
	        $fld->FLD('article', 'varchar', 'caption=Артикул');
            $fld->FLD('measure', 'int', 'caption=Мярка');
            $fld->FLD('price', 'double', 'caption=Ед.Стойност->без ДДС');
            $fld->FLD('priceVat', 'double', 'caption=Ед.Стойност->с ДДС');
            $fld->FLD('quantity', 'double', 'caption=Количество->Доставено');
	        $fld->FLD('quantityInv', 'double', 'caption=Количество->Фактурирано');
	        $fld->FLD('amount', 'double', 'caption=Стойност->Доставено');
	        $fld->FLD('amountInv', 'double', 'caption=Стойност->Фактурирано');
	        $fld->FLD('amountVat', 'double', 'caption=Стойност с ДДС->Доставено');
	        $fld->FLD('amountVatInv', 'double', 'caption=Стойност с ДДС->Фактурирано');
	    } else {

	    }
	
	    return $fld;
	}
	
	
	/**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec  - записа
	 * @param stdClass $dRec - чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected function detailRecToVerbal($rec, &$dRec)
	{
		$isPlain = Mode::is('text', 'plain');
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;

		$row = new stdClass();
		
		if(isset($dRec->code)) {
		    //$row->code = cat_Products::fetchField($rec->product,'code');
		    $row->code = $dRec->code;
		}
		
		if(isset($dRec->article)) {
		    $row->article = ($isPlain) ? cat_Products::getTitleById($dRec->article, FALSE) : cat_Products::getShortHyperlink($dRec->article);
		}
		
		if(isset($dRec->measure)) {
		    $row->measure = cat_UoM::fetchField($dRec->measure,'shortName');
		}
		
		// Сумираме всички суми и к-ва
		foreach (array('price','priceVat','quantity', 'quantityInv', 'amount', 'amountInv', 'amountVat', 'amountVatInv') as $fld){
		    if(isset($dRec->{$fld})){
		        $row->{$fld} = $Double->toVerbal($dRec->{$fld});
		    }
		}
		
		return $row;
	}

	
	/**
	 * След вербализирането на данните
	 *
	 * @param frame2_driver_Proto $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $row
	 * @param stdClass $rec
	 * @param array $fields
	 */
	protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
	{
	    if(isset($rec->periodId)){
	       $row->periodId = acc_Periods::getTitleById($rec->periodId);
	    }
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
	{
		$fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div><!--ET_BEGIN periodId-->|Период|*: [#periodId#]<!--ET_END periodId--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
		if(isset($data->rec->periodId)){
		    $fieldTpl->append($data->row->periodId, 'periodId');
		}
		
		$tpl->append($fieldTpl, 'DRIVER_FIELDS');
	}


    /**
     * Подготвяме заявката към мастър класа и детайла
     * 
     * @param query $query
     * @param stdClass $period
     * @param string $masterClass
     * @param string $detailClass
     * @param string $masterKey
     */
    protected function prepareQuery(&$query, &$data, $period, $masterClass, $detailClass, $masterKey)
    {
        $query = $masterClass::getQuery();
        
        if($masterClass == 'sales_Invoices'){
            $fld = 'date';
            $exp = "(#state = 'active' OR #state = 'closed')";
        }elseif($masterClass == 'sale_Sales') {
            $fld = 'closedOn';
            $exp = "(#state = 'closed')";

        } else {
            $fld = 'valior';
            $exp = "(#state = 'active' OR #state = 'closed')";
        }

        $query->where("(#$fld >= '{$period->start}' AND #$fld <= '{$period->end}') AND $exp");
        
        $recs = array();
        $recsDet = array();
       
        while ($rec = $query->fetch()) {
            $detQuery = $detailClass::getQuery();
            $detQuery->where("#$masterKey = '{$rec->id}'");
            
            while($recDet = $detQuery->fetch()) {
                
                 $this->addRecs($data,$rec,$recDet,$masterClass);
            }
        }
    }
    
    
    /**
     * Добавяме запис в резултатния масив
     *
     * @param stdClass $data
     * @param stdClass $rec
     * @param stdClass $recDetail
     * @param string $class
     */
    private  function addRecs(&$data, $rec, $recDetail, $class)
    {
        if (isset($recDetail->discount)) {
            $amount = $recDetail->amount - ($recDetail->amount*$recDetail->discount);
            
        } else {
            $amount  = $recDetail->amount;
        }

        if(isset($recDetail->price)) {
            $price = $recDetail->price;
        }
         
        if (isset($recDetail->packQuantity)) {
            $quantity = $recDetail->packQuantity;
        } else {
            $quantity = $recDetail->quantity;
        }

        if (isset($rec->currencyId)) {
            $currencyId = $rec->currencyId;
        }

        if($class == 'sales_Sales') {
            // гледаме дали тя е бърза
            $actions = type_Set::toArray($rec->contoActions);
            if(!isset($actions['ship'])){
                return;
            }
        }
        
        if(isset($rec->valior)) {
            $valior = $rec->valior;
        }
        
        if($class == 'sales_Invoices') { 

            if (isset($recDetail->discount)) {
                $amountInv = $recDetail->amount - ($recDetail->amount*$recDetail->discount);
            } else {
                $amountInv  = $recDetail->amount;
            }
        
            if (isset($recDetail->packQuantity)) {
                $quantityInv = $recDetail->packQuantity;
            } else {
                $quantityInv = $recDetail->quantity;
            }
            
            if(isset($rec->type)) {
                $type = $rec->type;
            }
        }
        
        $id = $recDetail->productId;

        $data->recs[] = (object) array ( 
                                    'doc'=> $class . "|" . $rec->id,
                                    'docNum'=> $rec->id,
                                    'valior'=> $valior,
                                    'code' => cat_Products::fetchField($recDetail->productId, 'code'),
                                    'article' => $recDetail->productId,
                                    'measure' =>cat_Products::getProductInfo($recDetail->productId)->productRec->measureId,
                                    'quantity' => $quantity,
                                    'amount' => $amount,
                                    'price' => $price,
                                    'quantityInv' => $quantityInv,
                                    'amountInv' => $amountInv,
                                    'amountVat' => '',
                                    'amountVatInv' => '',
                                    'type' => $type);
    }
}