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
    var $loadList = 'BankAccountTypes=bank_BankAccountTypes, plg_RowTools, acc_plg_Registry, bank_Wrapper';
    
    
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
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,mandatory');
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
            $Currencies = &cls::get('currency_Currencies');
            
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
    
}