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
    var $title = 'Правила за ценообразуване';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, price_Wrapper, plg_NoChange';
                    
    
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
     * Поле за връзка към единичния изглед
     */
    var $rowToolsSingleField = 'title';

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('parent', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Наследява,noChange');
        $this->FLD('title', 'varchar(128)', 'mandatory,caption=Наименование');
        $this->FLD('public', 'enum(no=Не,yes=Да)', 'caption=Публичен');
        $this->FLD('currency', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'mandatory,caption=Валута,noChange');
        $this->FLD('vat', 'enum(yes=С начислен ДДС,no=Без ДДС)', 'mandatory,caption=ДДС,noChange');
        $this->FLD('roundingPrecision', 'double', 'caption=Закръгляне->Точност');
        $this->FLD('roundingOffset', 'double', 'caption=Закръгляне->Отместване');
        
        $this->FLD('cClass', 'class(select=title)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Клиент->Обект,input=hidden,silent');

        $this->setDbUnique('title');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $form->rec;

        if($rec->classId) {
            $form->setField('public', 'input=none');
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if($rec->parent) {
            $row->parent = ht::createLink($row->parent, array('price_Lists', 'Single', $rec->parent));
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'delete') {
            if($rec->id && (self::fetch("#parent = {$rec->id}") || price_ListToCustomers::fetch("#listId = {$rec->id}")) ) {
                $requiredRoles = 'no_one';
            }
        }
    }

    
    /**
     *
     */
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