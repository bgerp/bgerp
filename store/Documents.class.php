<?php



/**
 * Документи за склада
 *
 *
 * @category  all
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Documents extends core_Master {
    
    
    /**
     * Заглавие
     */
    var $title = 'Документи за склада';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, store_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, docType, tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'store_DocumentDetails';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('docType', 'enum(SR=складова разписка,
                                       EN=експедиционно нареждане,
                                       IM=искане за материали,
                                       OOP=отчет за произведена продукция)', 'caption=Тип документ');
    }
}