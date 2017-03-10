<?php



/**
 * Драйвър за експортиране на 'sales_Invoices' изходящи фактури към Bulmar Office
 * 
 * 
 * @category  bgerp
 * @package   bulmar
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bulmar_InvoiceExport extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'bgerp_ExportIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Експортиране на фактури към Bulmar Office";
    
    
    /**
     * Към кои мениджъри да се показва драйвъра
     */
    protected static $applyOnlyTo = 'sales_Invoices';
    
    
    /**
     * Мениджъри за зареждане
     */
    public $loadList = 'Invoices=sales_Invoices';
    
    
    /**
     * Кой номер съответства на типа фактура
     */
    private static $invTypes = array('invoice' => 1, 'debit_note' => 2, 'credit_note' => 3);
    
    
    /**
     * Работен кеш
     */
    private $sales = array();
    
    
    /**
     * Кеш
     */
    private $cache = array();
    
    
    /**
     * Подготвя формата за експорт
     * 
     * @param core_Form $form
     */
    function prepareExportForm(core_Form &$form)
    {
    	$form->FLD('from', 'date', 'caption=От,mandatory');
    	$form->FLD('to', 'date', 'caption=До,mandatory');
    }
    
    
    /**
     * Проверява импорт формата
     * 
     * @param core_Form $form
     */
    function checkExportForm(core_Form &$form)
    {
    	if($form->rec->from > $form->rec->to){
    		$form->setError('from,to', 'Началната дата трябва да е по-малка от голямата');
    	}
    }
    
    
    /**
	 * Инпортиране на csv-файл в даден мениджър
     * 
     * @param mixed $data - данни
     * @return mixed - експортираните данни
	 */
    public function export($filter)
    {
    	$query = $this->Invoices->getQuery();
    	$query->where("#state = 'active'");
    	$query->between('date', $filter->from, $filter->to);
    	$query->orderBy("#number", 'ASC');
    	
    	$recs = $query->fetchAll();
    	
    	if(!count($recs)){
    		core_Statuses::newStatus('|Няма налични фактури за експортиране');
    		return;
    	}
    	
    	$data = $this->prepareExportData($recs);
    	$content = $this->prepareFileContent($data);
    	$content = iconv('utf-8', 'CP1251', $content);
    	
    	return $content;
    }
    
    
    /**
     * Подготвя данните за експорт
     * 
     * @param array $recs - фактурите за експортиране
     * @return stdClass $data - подготвените данни
     */
    private function prepareExportData($recs)
    {
    	$data = new stdClass();
    	
    	$data->static = $this->getStaticData();
    	$data->recs = array();
    	
    	$count = 0;
    	foreach ($recs as $rec){
    		$count++;
    		$data->recs[$rec->id] = $this->prepareRec($rec, $count);
    	}
    	
    	return $data;
    }
    
    
    /**
     * Подготвя записа
     */
    private function prepareRec($rec, $count)
    {
    	$nRec = new stdClass();
    	$nRec->contragent = $rec->contragentName;
    	$nRec->invNumber = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
    	$nRec->date = dt::mysql2verbal($rec->date, 'd.m.Y');
    	$nRec->num = $count;
    	if($rec->type == 'dc_note'){
    		$rec->type = ($rec->dealValue <= 0) ? 'credit_note' : 'debit_note';
    		$sign = 1;
    	} else {
    		$sign = ($rec->type == 'credit_note') ? -1 : 1;
    	}
    	
    	$nRec->type = self::$invTypes[$rec->type];
    	$baseAmount = round($rec->dealValue - $rec->discountAmount, 6);
    	if($rec->dpOperation == 'deducted'){
    		$baseAmount += abs($rec->dpAmount);
    	}
    	
    	$byProducts = $byServices = 0;
    	$dQuery = sales_InvoiceDetails::getQuery();
    	$dQuery->where("#invoiceId = {$rec->id}");
    	
    	while($dRec = $dQuery->fetch()){
    		if(empty($this->cache[$dRec->productId])){
    			$this->cache[$dRec->productId] = cat_Products::getProductInfo($dRec->productId);
    		}
    		
    		$pInfo = $this->cache[$dRec->productId];
    		
    		if(!isset($dRec->amount)){
    			$dRec->amount = $dRec->packPrice * $dRec->quantity;
    		}
    		
    		if(empty($pInfo->meta['canStore'])){
    			$byServices += $dRec->amount * (1 - $dRec->discount);
    		} else {
    			$byProducts += $dRec->amount * (1 - $dRec->discount);
    		}
    	}
    	
    	if($rec->type != 'invoice'){
    		$origin = $this->Invoices->getOrigin($rec);
    		$oRec = $origin->rec();
    		$number = $origin->getInstance()->recToVerbal($oRec)->number;
    		$nRec->reason = "Ф. №{$number}";
    	} else {
    		if($byServices != 0 && $byProducts == 0){
    			$nRec->reason = 'Приход от продажба на услуги';
    		} elseif($byServices == 0 && $byProducts != 0){
    			$nRec->reason = 'Приход от продажба на стоки';
    		} else {
    			$nRec->reason = 'Приход от продажба';
    		}
    	}
    	
    	$vat = round($rec->vatAmount, 2);
    	$nRec->vat = $sign * $vat;
    	$nRec->productsAmount = $sign * round($byProducts, 2);
    	$nRec->servicesAmount = $sign * round($byServices, 2);
    	$nRec->amount = $sign * (round($baseAmount, 2) + round($rec->vatAmount, 2));
    	$nRec->baseAmount = $sign * round($baseAmount, 2);
    	
    	if($rec->dpOperation){
    		$nRec->dpOperation = $rec->dpOperation;
    		$nRec->dpAmount = round($rec->dpAmount, 2);
    		if($rec->dpOperation == 'accrued'){
    			$nRec->reason = 'Фактура за аванс';
    		}
    	}
    	
    	$nRec->contragentEik = ($rec->contragentVatNo) ? $rec->contragentVatNo : $rec->uicNo;
    	
    	$Vats = cls::get('drdata_Vats');
    	$nRec->contragentEik = $Vats->canonize($nRec->contragentEik);
    	$rec->paymentType = ($rec->paymentType) ? $rec->paymentType : $rec->autoPaymentType;
    	
    	if($rec->paymentType == 'cash'){
    		$nRec->amountPaid = $nRec->amount;
    	}
    	
    	return $nRec;
    }
    
    
    /**
     * Подготвя съдържанието на файла
     */
    private function prepareFileContent(&$data)
    {
    	$static = $data->static;
    	$content = "Text Export To BMScety V2.0" . "\r\n";
    	$content .= "BULSTAT={$static->OWN_COMPANY_BULSTAT}" . "\r\n";
    	
    	// Добавяме информацията за фактурите
    	foreach ($data->recs as $rec){
    		$operationId = $static->saleProducts;
    		if($rec->dpOperation == 'accrued'){
    			unset($rec->productsAmount);
    			$operationId = $static->advancePayment;
    		} elseif($rec->dpOperation == 'deducted'){
    			
    			// Ако ф-та има приспадане на аванс приспадаме го от общата сума и сумата на платеното
    			$rec->amount += $rec->dpAmount;
    			$operationId = $static->advancePayment;
    			$rec->baseAmount += $rec->dpAmount;
    			
    			if(isset($rec->amountPaid)){
    				$rec->amountPaid += $rec->dpAmount;
    			}
    		}
    		
    		$line = "{$rec->num}|{$rec->type}|{$rec->invNumber}|{$rec->date}|{$rec->contragentEik}|{$rec->date}|{$static->folder}|{$rec->contragent}|". "\r\n";
    		$line .= "{$rec->num}|1|{$operationId}|{$static->debitSale}|AN|$|{$rec->amount}||";
    		if($rec->dpAmount){
    			$line .= "{$static->creditAdvance}|PA|$|{$rec->dpAmount}||";
    		}
    		
    		if($rec->productsAmount){
    			$line .= "{$static->creditSaleProducts}|||{$rec->productsAmount}||";
    		}
    		if($rec->servicesAmount){
    			$line .= "{$static->creditSaleServices}|||{$rec->servicesAmount}||";
    		}
    		
    		$line .= "{$static->creditSaleVat}|||{$rec->vat}||" . "\r\n";
    		
    		$line .= "{$rec->num}|1|Prod|{$rec->reason}|0|||{$rec->baseAmount}|{$rec->vat}|{$rec->baseAmount}|{$rec->vat}|||||||||||||" . "\r\n";
    		
    		if($rec->amountPaid){
    			$line .= "{$rec->num}|2|{$static->paymentOp}|{$static->debitPayment}|||{$rec->amountPaid}||{$static->creditPayment}|AN|$|{$rec->amountPaid}||" . "\r\n";
    		}
    		
    		$content .= $line;
    	}
    	
    	// Няма да се импортира ако не завършва на 0
    	$content .= "0\r\n";
    	
    	return $content;
    }
    
    
    /**
     * Извлича статичните данни от настройките
     */
    private function getStaticData()
    {
    	$staticData = new stdClass();
    	
    	$conf = core_Packs::getConfig('bulmar');
    	$staticData->folder             = $conf->BULMAR_INV_CONTR_FOLDER;
    	$staticData->saleProducts       = $conf->BULMAR_INV_TAX_OPERATION_SALE_PRODUCTS;
    	$staticData->saleServices       = $conf->BULMAR_INV_TAX_OPERATION_SALE_SERVICES;
    	$staticData->paymentOp          = $conf->BULMAR_INV_TAX_OPERATION_PAYMENT;
    	$staticData->debitSale          = $conf->BULMAR_INV_DEBIT_SALE;
    	$staticData->creditSaleProducts = $conf->BULMAR_INV_FIRST_CREDIT_SALE_PRODUCTS;
    	$staticData->creditSaleServices = $conf->BULMAR_INV_FIRST_CREDIT_SALE_SERVICES;
    	$staticData->creditSaleVat      = $conf->BULMAR_INV_SECOND_CREDIT_SALE;
    	$staticData->debitPayment       = $conf->BULMAR_INV_DEBIT_PAYMENT;
    	$staticData->creditPayment      = $conf->BULMAR_INV_CREDIT_PAYMENT;
    	$staticData->advancePayment     = $conf->BULMAR_INV_AV_OPERATION;
    	$staticData->creditAdvance      = $conf->BULMAR_INV_CREDIT_AV;
    	
    	$myCompany = crm_Companies::fetchOwnCompany();
    	$staticData->OWN_COMPANY_BULSTAT = str_replace('BG', '', $myCompany->vatNo);
    	
    	return $staticData;
    }
    
    
    /**
     * Може ли да се добавя към този мениджър
     */
    function isApplicable($mvc)
    {
    	return $mvc->className == self::$applyOnlyTo;
    }
    
    
    /**
     * Връща името на експортирания файл
     * 
     * @return string $name
     */
    public function getExportedFileName()
    {
    	$timestamp = time();
    	$name = "invoices{$timestamp}.txt";
    	
    	return $name;
    }
}