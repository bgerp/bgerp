<?php



/**
 * Документи за склада
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_PalletDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на палет';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, store_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, details, tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'palletId';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = "store_Pallets";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('palletId', 'key(mvc=store_Pallets, select=id)', 'caption=Палет');
        $this->FLD('details', 'varchar(255)', 'caption=Dummy for test');
        $this->FLD('productId', 'key(mvc=store_Products, select=name)', 'caption=Съдържание->Продукт');
        $this->FLD('quantity', 'int', 'caption=Количество');
        $this->FLD('comment', 'varchar(256)', 'caption=Коментар');
        $this->FLD('width', 'double(decimals=2)', 'caption=Дименсии->Широчина [м]');
        $this->FLD('height', 'double(decimals=2)', 'caption=Дименсии->Височина [м]');
        $this->FLD('weight', 'double(decimals=2)', 'caption=Дименсии->Тегло [kg]');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Място->Склад');
        $this->FLD('rackNum', 'int', 'caption=Място->Стелаж');
        $this->FLD('row', 'enum(A,B,C,D,E,F,G,H)', 'caption=Място->Ред');
        $this->FLD('column', 'int', 'caption=Място->Колонa');
        $this->FLD('action', 'varchar(255)', 'caption=Действие');
    }
}