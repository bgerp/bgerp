<?php

/**
 * Банкови документи
 */
class bank_BankDocuments extends core_Manager {


    /**
     *  @todo Чака за документация...
     */
    var $title = 'Банкови документи';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, bank_Wrapper';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('docType',    'enum(NR=нареждане разписка, PN=преводно нареждане)', 'caption=Тип документ');
        $this->FLD('dtAcc',      'varchar(255)', 'caption=ДТ сметка');
        $this->FLD('dtPero',     'varchar(255)', 'caption=ДТ перо');
        $this->FLD('ctAcc',      'varchar(255)', 'caption=КТ сметка');
        $this->FLD('ctPero',     'varchar(255)', 'caption=КТ перо');
        $this->FLD('amount',     'double(decimals=2)', 'caption=Сума');                
        $this->FLD('currencyId', 'key(mvc=common_Currencies, select=code)', 'caption=Валута,mandatory');
    	$this->FLD('reason',     'varchar(255)', 'caption=Основание');
    	$this->FNC('viewLink',   'varchar(255)', 'caption=Изглед');
    	
    	// NR
    	$this->FLD('issuePlaceAndDate',  'varchar(255)', 'caption=Място и дата на подаване');
    	$this->FLD('ordererIban',        'key(mvc=bank_BankAccounts, select=title)', 'caption=Банкова с-ка на фирмата');
    	$this->FLD('caseId',             'key(mvc=case_CaseAccounts, select=title)', 'caption=Каса');
        $this->FLD('confirmedByCashier', 'varchar(255)', 'caption=Потвърждение от Касиер');
    }
    
    
    /**
     * При нов запис, който още няма детайли показваме полето 'Сума' да е 0.00
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	$row->viewLink =  Ht::createLink('Изглед', array($this, 'Razpiska', $rec->id)); 
    }    
    
    
    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, $data, $rec)
    {
        $data->toolbar->addBtn('Нареждане разписка', array('Ctr' => $this,
                                                           'Act' => 'Razpiska',
                                                           'ret_url' => TRUE));
        
        $data->toolbar->addBtn('Вносна бележка', array('Ctr' => $this,
                                                       'Act' => 'Vnosna',
                                                       'ret_url' => TRUE));

        $data->toolbar->addBtn('Преводно нареждане', array('Ctr' => $this,
                                                           'Act' => 'Prevodno',
                                                           'ret_url' => TRUE));
        
        $data->toolbar->removeBtn('btnAdd');
        
        $data->toolbar->addBtn('Добави Нареждане разписка', array('Ctr' => $this,
                                                                  'Act' => 'add',
                                                                  'ret_url' => TRUE, 
                                                                  'docType' => 'NR'));
    }
    
    
    function act_Razpiska()
    {
        $viewRazpiska = cls::get('bank_tpl_SingleRazpiskaLayout', array('data' => $data));
        
        $recId = Request::get('id');
        
        $query = $this->getQuery();
        
        $where = "#id = ".$recId;
        
        while($rec = $query->fetch($where)) {
            $razpiska['issuePlaceAndDate'] = $rec->issuePlaceAndDate;
            
	        $razpiska['execBank']                = '';
	        $razpiska['execBranch']              = '';
	        $razpiska['execBranchAddress']       = '';
	        $razpiska['ordererName']             = '';
	        
	        // ordererIban
            $BankAccounts = cls::get('bank_BankAccounts');
            $ordererIban = $BankAccounts->fetchField("#id = '" . $rec->ordererIban . "'", 'iban');            
            $razpiska['ordererIban'] = $ordererIban;	        
	        
            // ordererBank
	        $razpiska['ordererBank']             = '';
	        
	        // currency
            $Currencies = cls::get('common_Currencies');
            $currencyCode = $Currencies->fetchField("#id = '" . $rec->currencyId . "'", 'code');	        
            $razpiska['currencyId']              = $currencyCode;
            
	        $razpiska['amount']                  = $rec->amount;
	        $razpiska['sayWords']                = '';
	        $razpiska['proxyName']               = '';
	        $razpiska['proxyIdentityCardNumber'] = '';
	        $razpiska['proxyEgn']                = '';             
        }        
        
        
        /*

        */
        
        foreach ($razpiska as $k => $v) {
            if (!$razpiska[$k] ) {
                $razpiska[$k] = '&nbsp;';                 
            }
            
            $viewRazpiska->replace($razpiska[$k], $k);
        }
        
        return $viewRazpiska;
    }
    
    
    function act_Vnosna()
    {
        $viewVnosna = cls::get('bank_tpl_SingleVnosnaLayout', array('data' => $data));
        
        $vnosna['execBank']                = 'ОБЕДИНЕНА БЪЛГАРСКА БАНКА';
        $vnosna['issuePlaceAndDate']       = 'Варна, 20.08.2011';
        $vnosna['execBranch']              = 'Варна, офис - Център';
        $vnosna['execBranchAddress']       = 'бул. България, 141';
        $vnosna['beneficiaryName']         = 'Петър Петров Петров';
        $vnosna['beneficiaryIban']         = 'BG23 BCBC 1233 1233 1233 01';
        $vnosna['beneficiaryBank']         = 'Пощенска Банка';
        $vnosna['currencyId']              = 'BGN';
        $vnosna['amount']                  = '120.50';
        $vnosna['sayWords']                = 'Сто и двадесет лева и петдесет стотинки';
        $vnosna['depositorName']           = 'Иван иванов Иванов';
        $vnosna['reason']                  = 'Захранване';
        
        foreach ($vnosna as $k => $v) {
            if (!$vnosna[$k] ) {
                $vnosna[$k] = '&nbsp;';                 
            }
            
            $viewVnosna->replace($vnosna[$k], $k);
        }
        
        return $viewVnosna;
    }

    
    function act_Prevodno()
    {
        $viewPrevodno = cls::get('bank_tpl_SinglePrevodnoLayout', array('data' => $data));
        
        $prevodno['execBank']                = 'ОБЕДИНЕНА БЪЛГАРСКА БАНКА';
        $prevodno['execBranch']              = 'Варна, офис - Център';
        $prevodno['issueDate']               = '20.08.2011';
        $prevodno['execBranchAddress']       = 'бул. България, 141';
        $prevodno['beneficiaryName']         = 'Петър Петров Петров';
        $prevodno['beneficiaryIban']         = 'BG23 BCBC 1233 1233 1233 01';
        $prevodno['beneficiaryBic']          = 'BIC2323023';
        $prevodno['beneficiaryBank']         = 'Пощенска Банка';
        $prevodno['currencyId']              = 'BGN';
        $prevodno['amount']                  = '120.50';
        $prevodno['reason']                  = 'Захранване';
        $prevodno['moreReason']              = '';
        $prevodno['ordererName']             = 'Иван иванов Иванов';
        $prevodno['ordererIban']             = 'BG43 MNMN 2342 2323 2323 34';
        $prevodno['ordererBic']              = 'BIC1323011';
        $prevodno['paymentSystem']           = 'SWIFT';
        
        foreach ($prevodno as $k => $v) {
            if (!$prevodno[$k] ) {
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
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$docType = Request::get('docType');
    	
        switch($docType) {
        	case 'NR':
		        $data->form->title = "Нареждане разписка";
		        
		        // docType		        
		        $data->form->setDefault('docType', 'NR');
		        $data->form->setField('docType', 'input=hidden');
		        
		        // issuePlaceAndDate
		        $data->form->setField('issuePlaceAndDate', 'caption=Място и дата на подаване');
		        
		        // ordererIban
		        $selectedAccountId = Mode::get('selectedAccountId');
		        $data->form->setField('ordererIban', 'input=hidden');
		        $data->form->setDefault('ordererIban', $selectedAccountId);
		        
		        // caseId
		        $data->form->setField('caseId', 'caption=За Каса');
		        
		        
		        // get id for BGN
		        $Currencies = cls::get('common_Currencies');
		        $defaultCurrencyId = $Currencies->fetchField("#code = '".BGERP_BASE_CURRENCY."'", 'id');
		        
		        // currencyId
		        $data->form->setDefault('currencyId', $defaultCurrencyId);

		        // amount
		        $data->form->setField('amount', 'caption=Са изтеглени');
		        
		        $data->form->showFields = 'docType, 
		                                   issuePlaceAndDate, 
		                                   ordererIban, 
		                                   amount, 
		                                   currencyId, 
		                                   caseId, 
		                                   reason';        		
		        break;
        }
    }    
       
}