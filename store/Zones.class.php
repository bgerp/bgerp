<?php



/**
 * Зони
 *
 *
 * @category  all
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Zones extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Стелажи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_LastUsedKeys, store_Wrapper, plg_RowTools';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'storeId';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,store';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,store';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,store';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,store';
    
    
    /**
     * @todo Чака за документация...
     */
    var $canSingle = 'admin,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'code,comment,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=hidden');
        $this->FLD('code', 'varchar(4)', 'caption=Код,mandatory');
        $this->FLD('comment', 'varchar(32)', 'caption=Коментар,mandatory');
        
        $this->setDbUnique('storeId,code');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Забранява изтриването/редакцията на зоните, които не са празни
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id && ($action == 'delete')) {
            
            $mvc->palletsInStoreArr = store_Pallets::getPalletsInStore();
            
            $rec = $mvc->fetch($rec->id);
            
            if ($mvc->palletsInStoreArr[$rec->id]) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Смяна на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListTitle($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $data->title = "Зони в СКЛАД № {$selectedStoreId}";
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        
        $data->query->where("#storeId = {$selectedStoreId}");
        $data->query->orderBy('id');
    }
    
    
    /**
     * При нов запис
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if (!$rec->id) {
            $rec->storeId = store_Stores::getCurrent();
        }
    }
}