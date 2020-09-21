<?php


/**
 * Драйвър за експортиране на приходни и разходни банкови документи към Bulmar Office
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
class bulmar_BankDocumentExport extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ExportIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Експортиране на банкови документи към Bulmar Office';
    
    
    /**
     * Към кои мениджъри да се показва драйвъра
     */
    protected static $applyOnlyTo = 'bank_IncomeDocuments,bank_SpendingDocuments';
    
    
    /**
     * Подготвя формата за експорт
     *
     * @param core_Form $form
     */
    public function prepareExportForm(core_Form &$form)
    {
        $form->FLD('from', 'date', 'caption=От,mandatory');
        $form->FLD('to', 'date', 'caption=До,mandatory');
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
        $ownAccounts = bank_OwnAccounts::getOwnAccounts(true, 'BGN');
        
        if(!countR($ownAccounts)){
            core_Statuses::newStatus('|Няма наши банкови сметки в лева');
            
            return;
        }
        
        $ownAccounts = array_keys($ownAccounts);
        
        $query = $this->mvc->getQuery();
        $query->where("#state = 'active'");
        $query->between('valior', $filter->from, $filter->to);
        $query->in("ownAccount", $ownAccounts);
        $query->orderBy('id', 'DESC');
        $recs = $query->fetchAll();
       
        $nonCashRecs = array();
        if($this->mvc instanceof bank_IncomeDocuments){
            
            $cQuery = cash_InternalMoneyTransfer::getQuery();
            $cQuery->EXT('sourceClassId', 'doc_Containers', 'externalName=docClass,externalKey=sourceId');
            $cQuery->where("#state = 'active' AND #operationSysId = 'nonecash2bank' AND #sourceId IS NOT NULL AND #sourceClassId = " . cash_Pko::getClassId());
            
            $cQuery->in('debitBank', $ownAccounts);
            $cQuery->between('valior', $filter->from, $filter->to);
            $nonCashRecs = $cQuery->fetchAll();
        }
        
        if (!countR($recs) && !countR($nonCashRecs)) {
            $title = mb_strtolower($this->mvc->title);
            core_Statuses::newStatus("Няма налични {$title} за експортиране");
            
            return;
        }
        
        $data = $this->prepareExportData($recs, $nonCashRecs);
        
        if(countR($data->error)){
            $msg = implode(', ', $data->error);
            core_Statuses::newStatus("Сметките|* {$msg} нямат въведени съответните аналитичности от bulmarOffice");
            
            return;
        }
        
        $content = $this->prepareFileContent($data);
        $content = iconv('utf-8', 'CP1251', $content);
        
        return $content;
    }
    
    
    /**
     * Може ли да се добавя към този мениджър
     */
    public function isApplicable($mvc)
    {
        $applyTo = arr::make(self::$applyOnlyTo, true);
     
        return in_array($mvc->className, $applyTo);
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
     * Подготвя данните за експорт
     *
     * @param array $recs - фактурите за експортиране
     *
     * @return stdClass $data - подготвените данни
     */
    private function prepareExportData($recs, $nonCashRecs)
    {
        $data = new stdClass();
        
        $data->static = $this->getStaticData();
        $data->recs = $data->error = array();
        
        $count = 0;
       
        foreach (array('recs' => $recs, 'nonCashRecs' => $nonCashRecs) as $key => $arr){
            foreach ($arr as $rec) {
                $count++;
                $newRec = ($key == 'recs') ? $this->prepareRec($rec, $count) : $this->prepareNoncashRec($rec, $count);
                
                $accountId = null;
                $ownAccountId = $newRec->accountId;
                array_walk($data->static->mapAccounts, function($a) use ($ownAccountId, &$accountId) {if($a->ownAccountId == $ownAccountId) {$accountId = $a->itemId;}});
                
                if($accountId){
                    $newRec->accountId = $accountId;
                    $data->recs[$rec->containerId] = $newRec;
                } else {
                    $data->error[$ownAccountId] = bank_OwnAccounts::getTitleById($ownAccountId);
                }
            }
        }
        
        
        return $data;
    }
    
    
    /**
     * Подготовка на данните за инкасирано безналично плащане
     */
    private function prepareNoncashRec($rec, $count)
    {
        $nRec = new stdClass();
        
        $amount = $rec->amount;
        
        $nRec->id = $rec->id . "003";
        $nRec->num = $count;
        $nRec->amount = $amount;
        $nRec->valior = $rec->valior;
        $nRec->endDate =  dt::getLastDayOfMonth($nRec->valior);
        $nRec->valior = dt::mysql2verbal($nRec->valior, 'd.m.Y');
        $nRec->endDate = dt::mysql2verbal($nRec->endDate, 'd.m.Y');
        
        $nRec->reason = $nRec->contragentName = null;
        $nRec->accountId = $rec->debitBank;
        
        if($rec->sourceId){
            if($Source = doc_Containers::getDocument($rec->sourceId)){
                
                if($Source->isInstanceOf('cash_Pko')){
                    $sourceRec = $Source->fetch();
                    
                    if($sourceRec->fromContainerId){
                        if($Document = doc_Containers::getDocument($sourceRec->fromContainerId)){
                            if($Document->isInstanceOf('deals_InvoiceMaster')){
                                $invoiceDate = $Document->fetchField('date');
                                $invoiceDate = dt::mysql2verbal($invoiceDate, 'd.m.Y');
                                
                                $nRec->reason .= "#" . str_pad($Document->fetchField('number'), 10, '0', STR_PAD_LEFT) . "/" . $invoiceDate;
                                $nRec->contragentName = cls::get($sourceRec->contragentClassId)->getVerbal($sourceRec->contragentId, 'name');
                            
                                $cData = cls::get($sourceRec->contragentClassId)->getContragentData($sourceRec->contragentId);
                                $nRec->EIC = ($cData->vatNo) ? $cData->vatNo : $cData->uicId;
                                $Vats = cls::get('drdata_Vats');
                                $nRec->EIC = $Vats->canonize($nRec->EIC);
                            }
                        }
                    }
                }
            }
        }
        
        $nRec->type = !empty($nRec->reason) ? 'creditClient' : 'creditUnknown';
       
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
            $line = "{$rec->num}|{$static->documentNumber}|{$rec->id}|{$rec->valior}|{$rec->EIC}|{$rec->endDate}|{$static->folder}|{$rec->contragentName}|" . "\r\n";
           
            switch($rec->type){
                case 'debitSupplier';
                $line .= "{$rec->num}|1|{$static->operationType}|{$static->debitSupplier}|PN|{$rec->reason}|{$rec->amount}||{$static->creditBank}|{$rec->accountId}||{$rec->amount}||" . "\r\n";
                
                break;
                case 'creditSupplier';
                $line .= "{$rec->num}|1|{$static->operationType}|{$static->debitBank}|{$rec->accountId}||{$rec->amount}||{$static->creditSupplier}|PN|{$rec->reason}|{$rec->amount}||" . "\r\n";
                
                break;
                case 'creditClient';
                $line .= "{$rec->num}|1|{$static->operationType}|{$static->debitBank}|{$rec->accountId}||{$rec->amount}||{$static->creditClient}|AN|{$rec->reason}|{$rec->amount}||" . "\r\n";
                
                break;
                case 'debitClient';
                $line .= "{$rec->num}|1|{$static->operationType}|{$static->debitClient}|PN|{$rec->reason}|{$rec->amount}||{$static->creditBank}|{$rec->accountId}||{$rec->amount}||" . "\r\n";
                
                break;
                case 'creditUnknown';
                $line .= "{$rec->num}|1|{$static->operationType}|{$static->debitBank}|{$rec->accountId}||{$rec->amount}||{$static->creditUnknown}|||{$rec->amount}||" . "\r\n";
                break;
                case 'debitUnknown';
                $line .= "{$rec->num}|1|{$static->operationType}|{$static->debitUnknown}|||{$rec->amount}||{$static->creditBank}|{$rec->accountId}||{$rec->amount}||" . "\r\n";
                break;
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
        
        $staticData->folder = $conf->BULMAR_BANK_DOCUMENT_FOLDER;
        $staticData->operationType = $conf->BULMAR_BANK_DOCUMENT_OPERATION_TYPE;
        $staticData->documentNumber = $conf->BULMAR_BANK_DOCUMENT_NUMBER;
        $staticData->debitSupplier = $conf->BULMAR_BANK_DOCUMENT_DEBIT_SUPPLIER;
        $staticData->creditSupplier = $conf->BULMAR_BANK_DOCUMENT_CREDIT_SUPPLIER;
        $staticData->creditBank = $conf->BULMAR_BANK_DOCUMENT_CREDIT_BANK;
        $staticData->debitBank = $conf->BULMAR_BANK_DOCUMENT_DEBIT_BANK;
        $staticData->creditClient = $conf->BULMAR_BANK_DOCUMENT_CREDIT_CLIENT;
        $staticData->debitClient = $conf->BULMAR_BANK_DOCUMENT_DEBIT_CLIENT;
        $staticData->debitUnknown = $conf->BULMAR_BANK_DOCUMENT_DEBIT_UNKNOWN;
        $staticData->creditUnknown = $conf->BULMAR_BANK_DOCUMENT_CREDIT_UNKNOWN;
        $staticData->mapAccounts = type_Table::toArray($conf->BULMAR_BANK_DOCUMENT_OWN_ACCOUNT_MAP);
        
        $myCompany = crm_Companies::fetchOwnCompany();
        $staticData->OWN_COMPANY_BULSTAT = str_replace('BG', '', $myCompany->vatNo);
        
        return $staticData;
    }
    
    /**
     * Подготвя записа
     */
    private function prepareRec($rec, $count)
    {
        $nRec = new stdClass();
        
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        if ($rec->currencyId == $baseCurrencyId) {
            $amount = $rec->amount;
        } elseif ($rec->dealCurrencyId == $baseCurrencyId) {
            $amount = $rec->amountDeal;
        } else {
            $amount = $rec->amount * $rec->rate;
        }
        
        $nRec->id = $rec->id . (($this->mvc instanceOf bank_IncomeDocuments) ? "001" : "002");
        $nRec->num = $count;
        $nRec->amount = $amount;
        $nRec->valior = $rec->valior;
        $nRec->endDate =  dt::getLastDayOfMonth($nRec->valior);
        $nRec->valior = dt::mysql2verbal($nRec->valior, 'd.m.Y');
        $nRec->endDate = dt::mysql2verbal($nRec->endDate, 'd.m.Y');
        
        $nRec->reason = $nRec->contragentName = null;
        $nRec->accountId = $rec->ownAccount;
        
        if($rec->fromContainerId){
            if($Document = doc_Containers::getDocument($rec->fromContainerId)){
                
                if($Document->isInstanceOf('deals_InvoiceMaster')){
                    $invoiceDate = $Document->fetchField('date');
                    $invoiceDate = dt::mysql2verbal($invoiceDate, 'd.m.Y');
                    
                    $nRec->reason .= "#" . str_pad($Document->fetchField('number'), 10, '0', STR_PAD_LEFT) . "/" . $invoiceDate;
                    $nRec->contragentName = cls::get($rec->contragentClassId)->getVerbal($rec->contragentId, 'name');
                
                    $cData = cls::get($rec->contragentClassId)->getContragentData($rec->contragentId);
                    $nRec->EIC = ($cData->vatNo) ? $cData->vatNo : $cData->uicId;
                    $Vats = cls::get('drdata_Vats');
                    $nRec->EIC = $Vats->canonize($nRec->EIC);
                }
            }
        }
        
        if($this->mvc instanceOf bank_IncomeDocuments){
            if(!empty($nRec->reason)){
                $nRec->type = ($rec->isReverse == 'no') ? 'creditClient' : 'creditSupplier';
            } else {
                $nRec->type = ($rec->isReverse == 'no') ? 'creditUnknown' : 'debitUnknown';
            }
            
        } else {
            if(!empty($nRec->reason)){
                $nRec->type = ($rec->isReverse == 'no') ? 'debitSupplier' : 'debitClient';
            } else {
                $nRec->type = ($rec->isReverse == 'no') ? 'debitUnknown' : 'creditUnknown';
            }
        }
        
        return $nRec;
    }
    
    
    /**
     * Връща името на експортирания файл
     *
     * @return string $name
     */
    public function getExportedFileName()
    {
        $timestamp = time();
        $name = "{$this->mvc->className}{$timestamp}.txt";
        
        return $name;
    }
}