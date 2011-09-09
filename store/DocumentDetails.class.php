<?php

/**
 * Документи за склада
 */
class store_DocumentDetails extends core_Detail {


    /**
     *  @todo Чака за документация...
     */
    var $title = 'Детайли на документ';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, store_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, details, tools=Пулт';

    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'documentId';

    
    /**
     *  @todo Чака за документация...
     */
    var $tabName = "store_Documents";    
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('documentId', 'key(mvc=store_Documents, select=docType)', 'caption=Документ');
    	$this->FLD('details', 'varchar(255)', 'caption=Dummy for test');
    }
    
}