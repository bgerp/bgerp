<?php


/**
 * Драйвър за експортиране на 'sales_Invoices' изходящи фактури към Bulmar Office
 *
 *
 * @category  bgerp
 * @package   bulmar
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bulmar_InvoiceExport extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ExportIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Експортиране на фактури към Bulmar Office';
    
    
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
    

    public static function getOwnCompanyOptions()
    {
        $ownCompanyIds = array(crm_Setup::BGERP_OWN_COMPANY_ID);
        if(core_Packs::isInstalled('holding')){
            $ownCompanyIds += array_keys(holding_Companies::getOwnCompanyOptions());
        }

        $ownCompanyOptions = array();
        $companyClassId = crm_Companies::getClassId();
        foreach ($ownCompanyIds as $ownCompanyId){
            $myCompany = crm_Companies::fetchOurCompany('*', $ownCompanyId);
            $num = (!empty($myCompany->vatNo)) ? str_replace('BG', '', $myCompany->vatNo) : $myCompany->uicId;
            $ownCompanyOptions["{$ownCompanyId}|{$num}|{$myCompany->name}"] = $myCompany->name .  ((!empty($num)) ? " [ {$num} ]" : '');

            $cQuery = change_History::getQuery();
            $cQuery->where("#classId = {$companyClassId} AND #objectId = {$ownCompanyId}");
            $cQuery->orderBy('validFrom', 'DESC');
            $cQuery->show("data");
            if($cQuery->count()){
                while($cRec = $cQuery->fetch()){
                    $num = (!empty($cRec->data->vatId)) ? str_replace('BG', '', $cRec->data->vatId) : $cRec->data->uicId;
                    $ownCompanyOptions["{$ownCompanyId}|{$num}|{$cRec->data->name}"] = $cRec->data->name . " [ {$num} ]";
                }
            }
        }

        return $ownCompanyOptions;
    }


    /**
     * Подготвя формата за експорт
     *
     * @param core_Form $form
     */
    public function prepareExportForm(core_Form &$form)
    {
        $ownCompanyOptions = static::getOwnCompanyOptions();

        $form->FLD('ownCompanyId', 'varchar', 'caption=Наша фирма');
        $form->setOptions('ownCompanyId', $ownCompanyOptions);
        $form->setDefault('ownCompanyId', key($ownCompanyOptions));

        $form->FLD('from', 'date', 'caption=От,mandatory');
        $form->FLD('to', 'date', 'caption=До,mandatory');

        $form->FLD('csvFile', 'fileman_FileType(bucket=bnav_importCsv)', 'width=100%,caption=Импорт от CSV->CSV файл,autohide=any');
        $form->FLD('delimiter', 'varchar(1,size=5)', 'width=100%,caption=Импорт от CSV->Разделител,maxRadio=5,placeholder=Автоматично,autohide=any');
        $form->FLD('enclosure', 'varchar(1,size=3)', 'width=100%,caption=Импорт от CSV->Ограждане,placeholder=Автоматично,autohide=any');
        $form->FLD('firstRow', 'enum(,data=Данни,columnNames=Имена на колони)', 'width=100%,caption=Импорт от CSV->Първи ред,placeholder=Автоматично,autohide=any');

        $form->setSuggestions('delimiter', array('' => '', ',' => ',', ';' => ';', ':' => ':', '|' => '|', '\t' => 'Таб'));
        $form->setSuggestions('enclosure', array('' => '', '"' => '"', '\'' => '\''));
        $form->setDefault('delimiter', ',');
        $form->setDefault('enclosure', '"');

        $i = 1;
        $csvFields = array('numberCol' => 'Номер', 'paymentCol' => 'Плащане', 'stateCol' => 'Статус', 'typeCol' => 'Вид', 'dateCol' => 'Дата', 'placeCol' => 'Място', 'contragentNameCol' => 'Контрагент', 'vatIdCol' => 'ДДС №', 'uicCol' => 'ЕИК', 'currencyCol' => 'Валута', 'amountCol' => 'Сума без ДДС', 'vatCol' => 'ДДС', 'totalCol' => 'Общо');
        foreach ($csvFields as $colName => $colValue) {
            $form->FLD($colName, 'int', "caption=Импорт от CSV->{$colValue},unit=колона,autohide=any");
            $form->setSuggestions($colName, array(1 => 1,2 => 2,3 => 3,4 => 4,5 => 5,6 => 6,7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12));
            $form->setDefault($colName, $i);
            $i++;
        }
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

        $recs = array();
        list($ownCompanyId, $uicId, $companyName) = explode('|', $filter->ownCompanyId);

        while($rec = $query->fetch()){
            $ownCompanyFieldValue = core_Packs::isInstalled('holding') ? $rec->{$this->Invoices->ownCompanyFieldName} : null;
            $ownCompanyRec = crm_Companies::fetchOurCompany('*', $ownCompanyFieldValue, $rec->activatedOn);
            $num = (!empty($ownCompanyRec->vatNo)) ? str_replace('BG', '', $ownCompanyRec->vatNo) : $ownCompanyRec->uicId;

            if($ownCompanyId == $ownCompanyRec->id && $num == $uicId && $ownCompanyRec->name == $companyName){
                $recs[$rec->id] = $rec;
            }
        }

        if(!empty($filter->csvFile)){
            $csvRecs = self::getRecsFromCsv($filter);
            if(countR($csvRecs)){
                $recs += $csvRecs;
            }
        }

        if (!countR($recs)) return;

        core_App::setTimeLimit(0.4 * countR($recs), false, 300);
        $data = $this->prepareExportData($recs, $filter);
        $content = $this->prepareFileContent($data);
        $content = iconv('utf-8', 'CP1251', $content);
        
        return $content;
    }
    
    
    /**
     * Подготвя данните за експорт
     *
     * @param array $recs - фактурите за експортиране
     * @param stdClass $filter - филтър
     * @return stdClass $data - подготвените данни
     */
    protected function prepareExportData($recs, $filter)
    {
        $data = new stdClass();

        $data->static = $this->getStaticData($filter);
        $data->recs = array();
        $count = 0;
        foreach ($recs as $rec) {
            $count++;
            $data->recs[$rec->id] = $this->prepareRec($rec, $count);
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя записа
     */
    protected function prepareRec($rec, $count)
    {
        $nRec = new stdClass();
        $nRec->contragent = $rec->contragentName;
        $nRec->invNumber = ($rec->_isVirtual) ? $rec->number : $this->Invoices->getVerbal($rec, 'number');

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
        if(!$rec->_isVirtual){
            $dQuery = sales_InvoiceDetails::getQuery();
            $dQuery->where("#invoiceId = {$rec->id}");
            $details = $dQuery->fetchAll();

            if($rec->type != 'invoice'){
                sales_InvoiceDetails::modifyDcDetails($details, $rec, cls::get('sales_InvoiceDetails'));
            }

            $vatDecimals = sales_Setup::get('SALE_INV_VAT_DISPLAY', true) == 'yes' ? 20 : 2;

            foreach ($details as $dRec) {
                if (empty($this->cache[$dRec->productId])) {
                    $this->cache[$dRec->productId] = cat_Products::getProductInfo($dRec->productId);
                }

                $pInfo = $this->cache[$dRec->productId];
                $dRec->amount = round($dRec->packPrice * $dRec->quantity, $vatDecimals);

                if (empty($pInfo->meta['canStore'])) {
                    $byServices += $dRec->amount * (1 - $dRec->discount);
                } else {
                    $byProducts += $dRec->amount * (1 - $dRec->discount);
                }
            }
        } else {
            $byProducts += $rec->dealValue;
        }

        if ($byServices != 0 && $byProducts == 0) {
            $nRec->reason = 'Приход от продажба на услуги';
        } elseif ($byServices == 0 && $byProducts != 0) {
            $nRec->reason = 'Приход от продажба на стоки';
        } else {
            $nRec->reason = 'Приход от продажба';
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
            
            // Ако към ф-та има ПКО и ВКТ за инкасиране на банково плащане да не се води като платена в брой
            if(!$rec->_isVirtual){
                $pkoQuery = cash_Pko::getQuery();
                $pkoQuery->where("#state = 'active' AND #fromContainerId = {$rec->containerId}");
                $pkoQuery->show('containerId');
                $pkos = arr::extractValuesFromArray($pkoQuery->fetchAll(), 'containerId');
                if(countR($pkos)){
                    $cQuery = cash_InternalMoneyTransfer::getQuery();
                    $cQuery->where("#state = 'active' AND #operationSysId = 'nonecash2bank'");
                    $cQuery->in('sourceId', $pkos);
                    if($cQuery->count()){
                        unset($nRec->amountPaid);
                    }
                }
            }
        } elseif($rec->paymentType == 'card'){
            $nRec->amountCardPaid = $nRec->amount;
        } elseif($rec->paymentType == 'postal'){
            $nRec->amountPostalPaid = $nRec->amount;
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
    protected function prepareFileContent(&$data)
    {
        $static = $data->static;
        $content = 'Text Export To BMScety V2.0' . "\r\n";
        $content .= "BULSTAT={$static->OWN_COMPANY_BULSTAT}" . "\r\n";
        
        // Добавяме информацията за фактурите
        foreach ($data->recs as $rec) {
            $operationId = $static->saleProducts;
            if ($rec->dpOperation == 'accrued') {
                unset($rec->productsAmount);
                $operationId = $static->advancePayment;
            } elseif ($rec->dpOperation == 'deducted') {
                
                // Ако ф-та има приспадане на аванс приспадаме го от общата сума и сумата на платеното
                $rec->amount += $rec->dpAmount;
                $operationId = $static->advancePayment;
                $rec->baseAmount += $rec->dpAmount;
                
                if (isset($rec->amountPaid)) {
                    $rec->amountPaid += $rec->dpAmount;
                }
            }
            
            $line = "{$rec->num}|{$rec->type}|{$rec->invNumber}|{$rec->date}|{$rec->contragentEik}|{$rec->date}|{$static->folder}|{$rec->contragent}|". "\r\n";
            $line .= "{$rec->num}|1|{$operationId}|{$static->debitSale}|AN|$|{$rec->amount}||";
            if ($rec->dpAmount) {
                $line .= "{$static->creditAdvance}|PA|$|{$rec->dpAmount}||";
            }
            
            if ($rec->productsAmount) {
                $line .= "{$static->creditSaleProducts}|||{$rec->productsAmount}||";
            }
            
            if ($rec->servicesAmount) {
                $line .= "{$static->creditSaleServices}|||{$rec->servicesAmount}||";
            }
            
            if($rec->type != 1 && empty($rec->servicesAmount) && empty($rec->productsAmount)){
                $line .= "{$static->creditSaleProducts}|||{$rec->baseAmount}||";
            }
            
            $line .= "{$static->creditSaleVat}|||{$rec->vat}||" . "\r\n";
            
            $line .= "{$rec->num}|1|Prod|{$rec->reason}|0|||{$rec->baseAmount}|{$rec->vat}|{$rec->baseAmount}|{$rec->vat}|||||||||||||" . "\r\n";
            
            if ($rec->amountPaid) {
                $line .= "{$rec->num}|2|{$static->paymentOp}|{$static->debitPayment}|||{$rec->amountPaid}||{$static->creditPayment}|AN|$|{$rec->amountPaid}||" . "\r\n";
            }

            if ($rec->amountCardPaid) {
                $line .= "{$rec->num}|2|{$static->pptAndCardOperation}|{$static->pptAndCardAccount}|{$static->cardAnal}|$|{$rec->amountCardPaid}||{$static->creditPayment}|AN|$|{$rec->amountCardPaid}||" . "\r\n";
            }

            if ($rec->amountPostalPaid) {
                $line .= "{$rec->num}|2|{$static->pptAndCardOperation}|{$static->pptAndCardAccount}|{$static->pptAnal}|$|{$rec->amountPostalPaid}||{$static->creditPayment}|AN|$|{$rec->amountPostalPaid}||" . "\r\n";
            }

            $content .= $line;
        }
        
        // Няма да се импортира ако не завършва на 0
        $content .= "0\r\n";
        
        return $content;
    }
    
    
    /**
     * Извлича статичните данни от настройките
     *
     * @param stdClass $filter
     * @return array $staticData
     */
    protected function getStaticData($filter)
    {
        $staticData = new stdClass();
        
        $conf = core_Packs::getConfig('bulmar');
        $staticData->folder = $conf->BULMAR_INV_CONTR_FOLDER;
        $staticData->saleProducts = $conf->BULMAR_INV_TAX_OPERATION_SALE_PRODUCTS;
        $staticData->saleServices = $conf->BULMAR_INV_TAX_OPERATION_SALE_SERVICES;
        $staticData->paymentOp = $conf->BULMAR_INV_TAX_OPERATION_PAYMENT;
        $staticData->debitSale = $conf->BULMAR_INV_DEBIT_SALE;
        $staticData->creditSaleProducts = $conf->BULMAR_INV_FIRST_CREDIT_SALE_PRODUCTS;
        $staticData->creditSaleServices = $conf->BULMAR_INV_FIRST_CREDIT_SALE_SERVICES;
        $staticData->creditSaleVat = $conf->BULMAR_INV_SECOND_CREDIT_SALE;
        $staticData->debitPayment = $conf->BULMAR_INV_DEBIT_PAYMENT;
        $staticData->creditPayment = $conf->BULMAR_INV_CREDIT_PAYMENT;
        $staticData->advancePayment = $conf->BULMAR_INV_AV_OPERATION;
        $staticData->creditAdvance = $conf->BULMAR_INV_CREDIT_AV;

        $staticData->pptAnal = $conf->BULMAR_INV_PPT_ANAL;
        $staticData->cardAnal = $conf->BULMAR_INV_CARD_PAYMENT_ANAL;
        $staticData->pptAndCardAccount = $conf->BULMAR_INV_PPT_AND_CARD_PAYMENT;
        $staticData->pptAndCardOperation = $conf->BULMAR_INV_PPT_AND_CARD_OPERATION;

        list( , $uicId) = explode('|', $filter->ownCompanyId);
        $staticData->OWN_COMPANY_BULSTAT = $uicId;
        
        return $staticData;
    }
    
    
    /**
     * Може ли да се добавя към този мениджър
     */
    public function isApplicable($mvc)
    {
        return $mvc->className == static::$applyOnlyTo;
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


    /**
     * Филтрира данните от csv
     *
     * @param $filter
     * @return array
     */
    public static function getRecsFromCsv($filter)
    {
        $res = array();

        $csvRows = csv_Lib::getCsvRowsFromFile(fileman::extractStr($filter->csvFile), array('delimiter' => $filter->delimiter, 'enclosure' => $filter->enclosure, 'firstRow' => $filter->firstRow));
        $csvRows = $csvRows['data'];

        $i = 0;
        foreach ($csvRows as $row) {
            if($row[$filter->stateCol] == 'active' || $row[$filter->stateCol] == 'active & rejected'){
                $nRec = (object) array('type' => $row[$filter->typeCol], 'date' => $row[$filter->dateCol], 'currencyId' => $row[$filter->currencyCol], 'contragentName' => $row[$filter->contragentNameCol], 'place' => $row[$filter->placeCol], 'contragentVatNo' => $row[$filter->vatIdCol], 'uicNo' => $row[$filter->uicCol], 'paymentType' => $row[$filter->paymentCol]);
                $nRec->number = str_pad($row[$filter->numberCol], 10, '0', STR_PAD_LEFT);
                $nRec->dealValue = $row[$filter->amountCol];
                $nRec->vatAmount = $row[$filter->vatCol];
                $nRec->_isVirtual = true;
                $nRec->id = 100000 + $i;
                $res[$nRec->number] = $nRec;
                $i++;
            }
        }

        return $res;
    }
}
