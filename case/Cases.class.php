<?php

/**
 * Каса сметки
 */
class case_Cases extends core_Manager {

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
    var $loadList = 'plg_RowTools, acc_plg_Registry, case_Wrapper';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('caseId', 'int', 'caption=Номер на каса');
    	$this->FLD('title', 'varchar(255)', 'caption=Наименование');
    }
    
}