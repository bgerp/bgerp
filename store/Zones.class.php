<?php
/**
 * Зони
 */
class store_Zones extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Стелажи';


    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_LastUsedKeys, store_Wrapper, plg_RowTools';


    var $lastUsedKeys = 'storeId';

    /**
     * Права
     */
    var $canRead = 'admin,store';


    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,store';


    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,store';


    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,store';


    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,store';


    /**
     *  @todo Чака за документация...
     */
    var $canSingle = 'admin,store';


    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 10;


    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'title,comment,tools=Пулт';


    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';

    
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=hidden');
        $this->FLD('title',   'varchar(4)',                        'caption=Име,mandatory');
        $this->FLD('comment', 'varchar(32)',                       'caption=Коментар,mandatory');

        $this->setDbUnique('storeId,title');
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
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
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
    function on_AfterPrepareListTitle($mvc, $data)
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
    function on_BeforePrepareListRecs($mvc, &$res, $data)
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
    function on_BeforeSave($mvc,&$id,$rec)
    {
        if (!$rec->id) {
            $rec->storeId = store_Stores::getCurrent();
        }
    }    

}