<?php

/**
 * Документи за склада
 */
class store_Documents extends core_Master {


    /**
     *  @todo Чака за документация...
     */
    var $title = 'Документи за склада';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, store_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, docType, tools=Пулт';

    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = array('store_DocumentDetails');    
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('docType',    'enum(SR=складова разписка,
                                       EN=експедиционно нареждане,
                                       IM=искане за материали,
                                       OOP=отчет за произведена продукция)', 'caption=Тип документ');
    }
    
}