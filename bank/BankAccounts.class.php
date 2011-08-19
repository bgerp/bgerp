<?php

/**
 * Банкови сметки
 */
class bank_BankAccounts extends core_Manager {

    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf';

    /**
     *  @todo Чака за документация...
     */
    var $title = 'Банкови сметки';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'BankAccountTypes=bank_BankAccountTypes, plg_RowTools, acc_RegisterPlg, bank_Wrapper';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('contragentId', 'key(mvc=crm_Companies,select=name)', 'caption=Контрагент,mandatory');
        $this->FLD('title', 'varchar(128)', 'caption=Наименование'); // Да се смята на on_BeforeSave() ако е празно.
        $this->FLD('number', 'varchar(64)', 'caption=Номер');
        $this->FLD('iban', 'iban_Type', 'caption=IBAN'); // Макс. IBAN дължина е 34 символа (http://www.nordea.dk/Erhverv/Betalinger%2bog%2bkort/Betalinger/IBAN/40532.html)
        $this->FLD('bic', 'varchar(16)', 'caption=BIC');
        $this->FLD('bankId', 'key(mvc=crm_Companies,select=name)', 'caption=Банка,mandatory');
        $this->FLD('typeId', 'key(mvc=bank_BankAccountTypes,select=name)', 'caption=Тип,mandatory,oldFieldName=type');
        $this->FLD('currencyId', 'key(mvc=common_Currencies, select=code)', 'caption=Валута,mandatory');
        $this->FLD('minBalance', 'double', 'caption=Мин.баланс,value=0');
        $this->FLD('comment', 'text', 'caption=@Коментар');
    }
    
    
    /**
     * След зареждане на форма от заявката. (@see core_Form::input())
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {
            return;
        }
        
        $rec = &$form->rec;
        
        //
        // Валидация: Задължително е попълването на поне едно от полетата
        //               number или iban
        //
        if (empty($rec->number) && empty($rec->iban)) {
            $form->setError('number,iban', 'Задължително е попълването на номер с/ка или IBAN');
        }
        
        //
        // Валидация: За някой държави въвеждането на IBAN и BIC е задължително
        //
        if (!empty($rec->bankId) && $this->isIbanRequired($rec->bankId)) {
            if (empty($rec->iban) || empty($rec->bic)) {
                // @todo Да се откоментира след pending bug в core.
                // $form->setError('iban,bic', 'Задължително е попълването на BIC и IBAN');
            }
        }
        
        //
        // Установяване на наименованието по подразбиране (когато не е зададено от потребителя)
        //
        if (empty($rec->title) && !empty($rec->bankId) && !empty($rec->typeId) && !empty($rec->currencyId)) {
            $Contacts = &cls::get('crm_Companies');
            $BankAccountTypes = &cls::get('bank_BankAccountTypes');
            $Currencies = &cls::get('common_Currencies');
            
            $bankName = $Contacts->fetchField($rec->bankId, 'name');
            $typeName = $BankAccountTypes->fetchField($rec->typeId, 'name');
            $currCode = $Currencies->fetchField($rec->currencyId, 'code');
            
            $title = $rec->title = "{$bankName} {$currCode} - {$typeName}";
            
            // Подсигуряваме уникалност на наименованието на с/ката
            $nn = 1;
            
            while ($this->fetch(array("#title = '[#1#]'", $title))) {
                $title = $rec->title . ' (' . $nn++ .')';
            }
            
            $rec->title = $title;
        }
    }
    
    
    /**
     * Задължително ли е наличието на IBAN за държавата, от която е зададения контакт?
     *
     * @param int $contactId
     * @return boolean
     */
    function isIbanRequired($contactId)
    {
        // Масив, съдържащ двубуквени ISO кодове на държави, в които 
        // IBAN *Е* задължителен.
        $requireIBAN = array(
            'BG'
        );
        
        $isRequired = FALSE;
        
        $Contacts = &cls::get('crm_Companies');
        $Countries = &cls::get('drdata_Countries');
        
        if($countryId = $Contacts->fetchField($contactId, 'country')) {
            $countryCode = $Countries->fetchField($contactId, 'letterCode2');
            
            $isRequired = in_array($countryCode, $requireIBAN);
        }
        
        return $isRequired;
    }
    
    
    /**
     * Връща заглавието на перото за банковата сметка
     *
     * Част от интерфейса: intf_Register
     */
    function getAccItemRec($rec)
    {
        $title = $rec->title ? $rec->title : $rec->iban;
        
        return (object) array('title' => $title);
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
    }
    
    
    function act_Razpiska()
    {
    	$viewRazpiska = cls::get('bank_tpl_SingleRazpiskaLayout', array('data' => $data));
    	
    	$razpiska['execBank']                = 'ОБЕДИНЕНА БЪЛГАРСКА БАНКА';
    	$razpiska['issuePlaceAndDate']       = 'Варна, 20.08.2011';
    	$razpiska['execBranch']              = 'Варна, офис - Център';
    	$razpiska['execBranchAddress']       = 'бул. България, 141';
    	$razpiska['ordererName']             = 'Петър Петров Петров';
    	$razpiska['ordererIban']             = 'BG23 BCBC 1233 1233 1233 01';
    	$razpiska['ordererBank']             = 'Пощенска Банка';
    	$razpiska['currencyId']              = 'BGN';
    	$razpiska['amount']                  = '120.50';
    	$razpiska['sayWords']                = 'Сто и двадесет лева и петдесет стотинки';
    	$razpiska['proxyName']               = 'Иван иванов Иванов';
    	$razpiska['proxyIdentityCardNumber'] = '00138795';
    	$razpiska['proxyEgn']                = '7603031111';
    	
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
    
}