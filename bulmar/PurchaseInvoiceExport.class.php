<?php


/**
 * Драйвър за експортиране на 'purchase_Invoices' входящи фактури към Bulmar Office
 *
 *
 * @category  bgerp
 * @package   bulmar
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bulmar_PurchaseInvoiceExport extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ExportIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Експортиране на входящи фактури към Bulmar Office';
    
    
    /**
     * Към кои мениджъри да се показва драйвъра
     */
    protected static $applyOnlyTo = 'purchase_Invoices';
    
    
    /**
     * Мениджъри за зареждане
     */
    public $loadList = 'Invoices=purchase_Invoices';
    
    
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
    public function prepareExportForm(core_Form &$form)
    {
        $form->FLD('from', 'date', 'caption=От,mandatory');
        $form->FLD('to', 'date', 'caption=До,mandatory');
        
        $form->info = tr('Ще се експортират само входящите фактури за покупка на стоки от контрагенти от "България"');
        $form->info = "<div class='richtext-message richtext-info'>{$form->info}</div>";
    }
    
    
    /**
     * Проверява импорт формата
     *
     * @param core_Form $form
     */
    public function checkExportForm(core_Form &$form)
    {
        if ($form->rec->from > $form->rec->to) {
            $form->setError('from,to', 'Началната дата трябва да е по-малка от голямата');
        }
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param mixed $data - данни
     *
     * @return mixed - експортираните данни
     */
    public function export($filter)
    {
        $query = $this->Invoices->getQuery();
        $query->where("#state = 'active'");
        $query->between('date', $filter->from, $filter->to);
        $query->orderBy('#number', 'ASC');
        
        $recs = $query->fetchAll();
        $data = $this->prepareExportData($recs);
        if (!countR($data->recs)) {
            
            return;
        }
        
        $content = $this->prepareFileContent($data);
        $content = iconv('utf-8', 'CP1251', $content);
        
        return $content;
    }
    
    
    /**
     * Подготвя данните за експорт
     *
     * @param array $recs - фактурите за експортиране
     *
     * @return stdClass $data - подготвените данни
     */
    private function prepareExportData($recs)
    {
        $data = new stdClass();
        $data->static = $this->getStaticData();
        $data->recs = array();
        
        $count = 0;
        foreach ($recs as $rec) {
            $count++;
            $newRec = $this->prepareRec($rec, $count);
            if(is_object($newRec)){
                $data->recs[$rec->id] = $newRec;
            }
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя записа
     */
    private function prepareRec($rec, $count)
    {
        $nRec = new stdClass();
        
        // Пропускат се фактурите за контрагенти извън 'България'
        if(isset($rec->contragentCountryId)){
            $bgId =   drdata_Countries::getIdByName('Bulgaria');
            if($rec->contragentCountryId != $bgId){
                
                return null;
            }
        }
        
        $nRec->contragent = $rec->contragentName;
        $nRec->invNumber = purchase_Invoices::getVerbal($rec, 'number');
        $nRec->date = dt::mysql2verbal($rec->date, 'd.m.Y');
        $nRec->num = $count;
        if ($rec->type == 'dc_note') {
            $rec->type = ($rec->dealValue <= 0) ? 'credit_note' : 'debit_note';
            $sign = 1;
        } else {
            $sign = ($rec->type == 'credit_note') ? -1 : 1;
        }
        
        $nRec->type = self::$invTypes[$rec->type];
        $baseAmount = round($rec->dealValue - $rec->discountAmount, 6);
        if ($rec->dpOperation == 'deducted') {
            $baseAmount += abs($rec->dpAmount);
        }
        
        $byProducts = $byServices = 0;
        $dQuery = purchase_InvoiceDetails::getQuery();
        $dQuery->where("#invoiceId = {$rec->id}");
        
        $vatDecimals = sales_Setup::get('SALE_INV_VAT_DISPLAY', true) == 'yes' ? 20 : 2;
        
        while ($dRec = $dQuery->fetch()) {
            if (empty($this->cache[$dRec->productId])) {
                $this->cache[$dRec->productId] = cat_Products::fetch($dRec->productId, 'canStore');
            }
           
            $dRec->amount = round($dRec->packPrice * $dRec->quantity, $vatDecimals);
            
            if ($this->cache[$dRec->productId]->canStore == 'no') {
                $byServices += $dRec->amount * (1 - $dRec->discount);
            } else {
                $byProducts += $dRec->amount * (1 - $dRec->discount);
            }
        }
       
        // Пропускат се фактурите в които има услуги
        if(!empty($byServices)){
            
            return null;
        }
        
        if ($rec->type != 'invoice') {
            $origin = $this->Invoices->getOrigin($rec);
            $oRec = $origin->rec();
            $number = $origin->getInstance()->recToVerbal($oRec)->number;
            $nRec->reason = "Ф. №{$number}";
            
            if($oRec->dpOperation == 'accrued'){
                $nRec->downpaymentChanged = $rec->changeAmount;
            }
        } else {
            if ($byServices != 0 && $byProducts == 0) {
                $nRec->reason = 'Покупка на услуги';
            } elseif ($byServices == 0 && $byProducts != 0) {
                $nRec->reason = 'Покупка на стоки';
            } else {
                $nRec->reason = 'Покупка';
            }
        }
        
        $vat = round($rec->vatAmount, 2);
        $nRec->vat = $sign * $vat;
        $nRec->productsAmount = $sign * round($byProducts, 2);
        $nRec->servicesAmount = $sign * round($byServices, 2);
        $nRec->amount = $sign * (round($baseAmount, 2) + round($rec->vatAmount, 2));
        $nRec->baseAmount = $sign * round($baseAmount, 2);
        
        if ($rec->dpOperation) {
            $nRec->dpOperation = $rec->dpOperation;
            $nRec->dpAmount = round($rec->dpAmount, 2);
            if ($rec->dpOperation == 'accrued') {
                $nRec->reason = 'Фактура за аванс';
            }
        }
        
        $nRec->contragentEik = ($rec->contragentVatNo) ? $rec->contragentVatNo : $rec->uicNo;
        
        $Vats = cls::get('drdata_Vats');
        $nRec->contragentEik = $Vats->canonize($nRec->contragentEik);
        $rec->paymentType = ($rec->paymentType) ? $rec->paymentType : $rec->autoPaymentType;
        
        if ($rec->paymentType == 'cash') {
            $nRec->amountPaid = $nRec->amount;
        }
        
        if(round($nRec->productsAmount + $nRec->servicesAmount, 2) != round($nRec->baseAmount, 2)){
            if(empty($nRec->productsAmount) && !empty($nRec->servicesAmount)){
                $nRec->servicesAmount = $nRec->baseAmount;
            } elseif(!empty($nRec->productsAmount) && empty($nRec->servicesAmount)){
                $nRec->productsAmount = $nRec->baseAmount;
            } elseif(!empty($nRec->productsAmount) && !empty($nRec->servicesAmount)){
                $nRec->servicesAmount = $nRec->baseAmount - $nRec->productsAmount;
                $nRec->productsAmount = $nRec->baseAmount - $nRec->servicesAmount;
            }
        }
        
        return $nRec;
    }
    
    
    /**
     * Подготвя съдържанието на файла
     */
    private function prepareFileContent(&$data)
    {
        $static = $data->static;
        $content = 'Text Export To BMScety V2.0' . "\r\n";
        $content .= "BULSTAT={$static->OWN_COMPANY_BULSTAT}" . "\r\n";
        
        // Добавяме информацията за фактурите
        foreach ($data->recs as $rec) {
            
            $line = "{$rec->num}|{$rec->type}|{$rec->invNumber}|{$rec->date}|{$rec->contragentEik}|{$rec->date}|{$static->folder}|{$rec->contragent}|" . "\r\n";
            
            $creditAcc = $static->creditPurchase;
            if ($rec->dpOperation == 'accrued') {
                unset($rec->productsAmount);
                
                $line .= "{$rec->num}|1|{$static->downpaymentOperation}|{$static->downpaymentAcc}|AN|$|{$rec->baseAmount}||{$static->debitPurchaseVat}|||{$rec->vat}||{$creditAcc}|PN|$|{$rec->amount}||" . "\r\n";
                
            } elseif ($rec->dpOperation == 'deducted') {
                
                // Ако ф-та има приспадане на аванс приспадаме го от общата сума и сумата на платеното
                $rec->amount += $rec->dpAmount;
                
                if (isset($rec->amountPaid)) {
                    $rec->amountPaid += $rec->dpAmount;
                }
               
                $line .= "{$rec->num}|1|{$static->purchaseProductsOperation}|{$static->debitPurchaseProducts}|||{$rec->baseAmount}||{$static->downpaymentAcc}|AN|$|{$rec->dpAmount}||{$static->debitPurchaseVat}|||{$rec->vat}||{$creditAcc}|PN|$|{$rec->amount}||" . "\r\n";
                $rec->baseAmount += $rec->dpAmount;
            } else {
                $debitAcc =  "{$static->debitPurchaseProducts}|||";
                if($rec->downpaymentChanged){
                    $debitAcc =  "{$static->downpaymentAcc}|AN|$|";
                }
                
                
                $line .= "{$rec->num}|1|{$static->purchaseProductsOperation}|{$debitAcc}{$rec->baseAmount}||{$static->debitPurchaseVat}|||{$rec->vat}||{$creditAcc}|PN|$|{$rec->amount}||" . "\r\n";
            }
            
            $line .= "{$rec->num}|1|POK|{$rec->reason}|1||||{$rec->baseAmount}|{$rec->vat}|||||||||||||" . "\r\n";
            
            if ($rec->amountPaid) {
                $line .= "{$rec->num}|2|{$static->paymentOp}|{$static->debitPayment}|PN|$|{$rec->amountPaid}||{$static->creditCase}|||{$rec->amountPaid}||" . "\r\n";
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
        $staticData->folder = $conf->BULMAR_PURINV_CONTR_FOLDER;
        $staticData->creditPurchase = $conf->BULMAR_PURINV_CREDIT_PURCHASE;
        $staticData->debitPurchaseProducts = $conf->BULMAR_PURINV_DEBIT_PURCHASE_PRODUCTS;
        $staticData->debitPurchaseService = $conf->BULMAR_PURINV_DEBIT_PURCHASE_SERVICES;
        
        $staticData->debitPurchaseVat = $conf->BULMAR_PURINV_DEBIT_PURCHASE_VAT;
        $staticData->creditCase = $conf->BULMAR_PURINV_CREDIT_CASE;
        $staticData->paymentOp = $conf->BULMAR_PURINV_PAYMENT_OPERATION;
        $staticData->downpaymentAcc = $conf->BULMAR_PURINV_DEBIT_DOWNPAYMENT;
        
        $staticData->downpaymentOperation = $conf->BULMAR_PURINV_DOWNPAYMENT_OPERATION;
        $staticData->purchaseProductsOperation = $conf->BULMAR_PURINV_PURCHASE_PRODUCTS_OPER;
        $staticData->purchaseServiceOperation = $conf->BULMAR_PURINV_PURCHASE_SERVICES_OPER;
        $staticData->paymentOperation = $conf->BULMAR_PURINV_PAYMENT_OPERATION;
        $staticData->debitPayment = $conf->BULMAR_PURINV_DEBIT_PAYMENT;
        
        $myCompany = crm_Companies::fetchOwnCompany();
        
        $num = (!empty($myCompany->vatNo)) ? str_replace('BG', '', $myCompany->vatNo) : $myCompany->uicId;
        $staticData->OWN_COMPANY_BULSTAT = $num;
        
        return $staticData;
    }
    
    
    /**
     * Може ли да се добавя към този мениджър
     */
    public function isApplicable($mvc)
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
        $name = "purinvoices{$timestamp}.txt";
        
        return $name;
    }
}
