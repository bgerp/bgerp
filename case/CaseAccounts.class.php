<?php

/**
 * Каса сметки
 */
class case_CaseAccounts extends core_Manager {

    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf';

    /**
     *  @todo Чака за документация...
     */
    var $title = 'Касови сметки';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, acc_RegisterPlg, case_Wrapper';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Наименование'); // Да се смята на on_BeforeSave() ако е празно.
    }
    
}