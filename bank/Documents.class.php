<?php



/**
 * Банкови документи
 *
 *
 * @category  all
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Documents extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Банкови документи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('docType', 'enum(NR=нареждане разписка, PN=преводно нареждане)', 'caption=Тип документ');
        $this->FLD('dtAcc', 'varchar(255)', 'caption=ДТ сметка');
        $this->FLD('dtPero', 'varchar(255)', 'caption=ДТ перо');
        $this->FLD('ctAcc', 'varchar(255)', 'caption=КТ сметка');
        $this->FLD('ctPero', 'varchar(255)', 'caption=КТ перо');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,mandatory');
        $this->FLD('reason', 'varchar(255)', 'caption=Основание');
        $this->FNC('viewLink', 'varchar(255)', 'caption=Изглед');
        
        // NR
        $this->FLD('issuePlace', 'varchar(255)', 'caption=Място на подаване');
        $this->FLD('issueDate', 'date', 'caption=Дата на подаване');
        
        // $this->FLD('issuePlaceAndDate',  'varchar(255)', 'caption=Място и дата на подаване');
        $this->FLD('ordererIban', 'key(mvc=bank_Accounts, select=title)', 'caption=Банкова с-ка на фирмата');
        $this->FLD('caseId', 'key(mvc=cash_Cases, select=title)', 'caption=Каса');
        $this->FLD('confirmedByCashier', 'varchar(255)', 'caption=Потвърждение от Касиер');
    }
    
    
    /**
     * При нов запис, който още няма детайли показваме полето 'Сума' да е 0.00
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->viewLink = Ht::createLink('Отвори', array($this, 'RazpiskaAddTempDataForPrint', $rec->id));
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data, $rec)
    {
        $data->toolbar->removeBtn('btnAdd');
        $data->toolbar->addBtn('Добави Нареждане разписка', array('Ctr' => $this,
                'Act' => 'add',
                'ret_url' => TRUE,
                'docType' => 'NR'));
    }
    
    
    /**
     * Метода дава форма, през която добавяме данни, които не влизат в модела,
     * а само се отразяват в бланката за печат
     *
     * @return core_Html
     */
    function act_RazpiskaAddTempDataForPrint()
    {
        $recId = Request::get('id');
        
        // Prepare form
        $form = cls::get('core_form', array('method' => 'GET'));
        $form->title = "Добавяне данни за печат за банков документ";
        
        $form->FNC('execBank', 'varchar(255)', 'caption=Банка->Име');
        $form->FNC('execBranch', 'varchar(255)', 'caption=Банка->Клон');
        $form->FNC('execBranchAddress', 'varchar(255)', 'caption=Банка->Адрес');
        $form->FNC('issuePlace', 'varchar(255)', 'caption=Място на подаване');
        $form->FNC('ordererName', 'varchar(255)', 'caption=Наредител');
        
        $form->showFields = 'execBank, 
                             execBranch, 
                             execBranchAddress, 
                             issuePlace, 
                             ordererName';
        
        $form->setAction(array('bank_Documents', 'RazpiskaPrint', $recId));
        
        $form->toolbar->addSbBtn('Печат');
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Рендира темплейта за 'нареждане разписка'
     *
     * @return core_Html
     */
    function act_RazpiskaPrint()
    {
        $viewRazpiska = cls::get('bank_tpl_SingleRazpiskaLayout', array('data' => $data));
        
        $recId = Request::get('id');
        
        $razpiska['execBank'] = Request::get('execBank');
        $razpiska['execBranch'] = Request::get('execBranch');
        $razpiska['execBranchAddress'] = Request::get('execBranchAddress');
        $razpiska['issuePlace'] = Request::get('issuePlace');
        $razpiska['ordererName'] = Request::get('ordererName');
        
        $query = $this->getQuery();
        
        $where = "#id = " . $recId;
        
        while($rec = $query->fetch($where)) {
            $razpiska['issuePlaceAndDate'] = $razpiska['issuePlace'] . ", " . substr($rec->issueDate, 0, 10);
            
            // ordererIban
            $bankAccounts = cls::get('bank_Accounts');
            $ordererIban = $bankAccounts->fetchField("#id = '" . $rec->ordererIban . "'", 'iban');
            $razpiska['ordererIban'] = $ordererIban;
            
            // ordererBank
            $razpiska['ordererBank'] = '';
            
            // currency
            $currency = cls::get('currency_Currencies');
            $currencyCode = $currency->fetchField("#id = '" . $rec->currencyId . "'", 'code');
            $razpiska['currencyId'] = $currencyCode;
            
            $razpiska['amount'] = $rec->amount;
            $razpiska['sayWords'] = '';
            $razpiska['proxyName'] = '';
            $razpiska['proxyIdentityCardNumber'] = '';
            $razpiska['proxyEgn'] = '';
        }
        
        // replace
        foreach ($razpiska as $k => $v) {
            if (!$razpiska[$k]) {
                $razpiska[$k] = '&nbsp;';
            }
            
            $viewRazpiska->replace($razpiska[$k], $k);
        }
        
        return $viewRazpiska;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Vnosna()
    {
        $viewVnosna = cls::get('bank_tpl_SingleVnosnaLayout', array('data' => $data));
        
        $vnosna['execBank'] = 'ОБЕДИНЕНА БЪЛГАРСКА БАНКА';
        $vnosna['issuePlaceAndDate'] = 'Варна, 20.08.2011';
        $vnosna['execBranch'] = 'Варна, офис - Център';
        $vnosna['execBranchAddress'] = 'бул. България, 141';
        $vnosna['beneficiaryName'] = 'Петър Петров Петров';
        $vnosna['beneficiaryIban'] = 'BG23 BCBC 1233 1233 1233 01';
        $vnosna['beneficiaryBank'] = 'Пощенска Банка';
        $vnosna['currencyId'] = 'BGN';
        $vnosna['amount'] = '120.50';
        $vnosna['sayWords'] = 'Сто и двадесет лева и петдесет стотинки';
        $vnosna['depositorName'] = 'Иван иванов Иванов';
        $vnosna['reason'] = 'Захранване';
        
        foreach ($vnosna as $k => $v) {
            if (!$vnosna[$k]) {
                $vnosna[$k] = '&nbsp;';
            }
            
            $viewVnosna->replace($vnosna[$k], $k);
        }
        
        return $viewVnosna;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Prevodno()
    {
        $viewPrevodno = cls::get('bank_tpl_SinglePrevodnoLayout', array('data' => $data));
        
        $prevodno['execBank'] = 'ОБЕДИНЕНА БЪЛГАРСКА БАНКА';
        $prevodno['execBranch'] = 'Варна, офис - Център';
        $prevodno['issueDate'] = '20.08.2011';
        $prevodno['execBranchAddress'] = 'бул. България, 141';
        $prevodno['beneficiaryName'] = 'Петър Петров Петров';
        $prevodno['beneficiaryIban'] = 'BG23 BCBC 1233 1233 1233 01';
        $prevodno['beneficiaryBic'] = 'BIC2323023';
        $prevodno['beneficiaryBank'] = 'Пощенска Банка';
        $prevodno['currencyId'] = 'BGN';
        $prevodno['amount'] = '120.50';
        $prevodno['reason'] = 'Захранване';
        $prevodno['moreReason'] = '';
        $prevodno['ordererName'] = 'Иван иванов Иванов';
        $prevodno['ordererIban'] = 'BG43 MNMN 2342 2323 2323 34';
        $prevodno['ordererBic'] = 'BIC1323011';
        $prevodno['paymentSystem'] = 'SWIFT';
        
        foreach ($prevodno as $k => $v) {
            if (!$prevodno[$k]) {
                $prevodno[$k] = '&nbsp;';
            }
            
            $viewPrevodno->replace($prevodno[$k], $k);
        }
        
        return $viewPrevodno;
    }
    
    
    /**
     * По подразбиране нов запис е със state 'active'
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $docType = Request::get('docType');
        
        switch($docType) {
            case 'NR' :
                $data->form->title = "Нареждане разписка";
                
                // docType                
                $data->form->setField('docType', 'input=hidden');
                $data->form->setDefault('docType', 'NR');
                
                // issuePlace
                $data->form->setField('issuePlace', 'caption=Място на подаване');
                
                // issueDate
                $data->form->setField('issueDate', 'caption=Дата на подаване');
                
                // ordererIban
                $selectedOwnAccountId = bank_OwnAccounts::getCurrent();
                $bankAccountId = bank_OwnAccounts::fetchField("#id = {$selectedOwnAccountId}", 'bankAccountId');
                
                $ordererIban = bank_Accounts::fetchField("#id = {$bankAccountId}", 'iban');
                
                $data->form->setField('ordererIban', 'input=hidden');
                $data->form->setDefault('ordererIban', $bankAccountId);
                
                // caseId
                $data->form->setField('caseId', 'caption=За Каса');
                
                // get id for currency
                $defaultCurrencyId = currency_Currencies::fetchField("#code = '" . BGERP_BASE_CURRENCY . "'", 'id');
                
                // $defaultCurrencyId = currency_Currencies::fetchField("#code = 'CAD'", 'id');
                
                // currencyId
                $data->form->setDefault('currencyId', $defaultCurrencyId);
                
                // amount
                $data->form->setField('amount', 'caption=Са изтеглени');
                
                $data->form->showFields = 'docType, 
                                           issueDate, 
                                           ordererIban, 
                                           amount, 
                                           caseId, 
                                           reason';
                break;
        }
    }
}