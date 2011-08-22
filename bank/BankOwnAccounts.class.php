<?php

/**
 * Банкови сметки на фирмата
 */
class bank_BankOwnAccounts extends core_Manager {


    /**
     *  @todo Чака за документация...
     */
    var $title = 'Банкови сметки на фирмата';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, bank_Wrapper';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',         'varchar(128)', 'caption=Наименование');
        $this->FLD('bankAccountId', 'key(mvc=bank_BankAccounts,select=iban)', 'caption=Сметка,mandatory');
        $this->FLD('holders',       'key(mvc=core_Users, select=names)', 'caption=Титуляри->Име');                
        $this->FLD('together',      'enum(no,yes)', 'caption=Титуляри->Заедно / поотделно');
        $this->FLD('operator',      'key(mvc=core_Users, select=names)', 'caption=Оператор');
    }
    
}