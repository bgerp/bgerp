<?php



/**
 * Ценоразписи за продукти от каталога
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценоразписи
 */
class price_Lists extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Ценоразписи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, price_Wrapper';
                    
    
    /**
     * Детайла, на модела
     */
    var $details = 'price_ListRules';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, parent, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'price,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'price,ceo';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'price,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'price,ceo';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('parent', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Наследява');
        $this->FLD('title', 'varchar(128)', 'mandatory,caption=Наименование');
        $this->FLD('currency', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'mandatory,caption=Валута');
        $this->FLD('vat', 'enum(yes=С начислен ДДС,no=Без ДДС)', 'mandatory,caption=ДДС');
        $this->FLD('roundingPrecision', 'double', 'caption=Закръгляне->Точност');
        $this->FLD('roundingOffset', 'double', 'caption=Закръгляне->Отместване');
    }


    function on_AfterSetupMVC($mvc, $res)
    {
        $conf = core_Packs::getConfig('price');

        if(!$mvc->fetchField($conf->PRICE_LIST_COST, 'id')) {
            $rec = new stdClass();
            $rec->id = $conf->PRICE_LIST_COST;
            $rec->parent = NULL;
            $rec->title  = 'Себестойност';
            $rec->createdOn = dt::verbal2mysql();
            $rec->createdBy = -1;
            $mvc->save($rec, NULL, 'REPLACE');
        }
        
        if(!$mvc->fetchField($conf->PRICE_LIST_CATALOG, 'id')) {
            $rec = new stdClass();
            $rec->id = $conf->PRICE_LIST_CATALOG;
            $rec->parent = $conf->PRICE_LIST_COST;
            $rec->title  = 'Каталог';
            $rec->createdOn = dt::verbal2mysql();
            $rec->createdBy = -1;
            $mvc->save($rec, NULL, 'REPLACE');
        }


    }
    
}