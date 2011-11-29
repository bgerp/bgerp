<?php
/**
 * Стелажи
 */
class store_RackLabels extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Етикети на стелажите';


    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_LastUsedKeys, store_Wrapper';


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
    var $listFields = 'rackId, label';


    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';

    function description()
    {
        $this->FLD('storeId',           'key(mvc=store_Stores,select=name)', 'caption=Склад, input=hidden');
        $this->FLD('rackId',            'key(mvc=store_Racks, select=id)',   'caption=Стелаж №');
        $this->FLD('label',             'varchar(32)',                       'caption=Етикет, mandatory');

        $this->setDbUnique('storeId,rackId');
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Забранява изтриването/редакцията на стелажите, които не са празни
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

        $data->title = "Стелажи в СКЛАД № {$selectedStoreId}";
    }


    /**
     * Форма за add/edit на стелаж
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
            
        $form = $data->form;
        $form->setDefault('storeId', $selectedStoreId);

        // В случай на add
        if (!$data->form->rec->id) {
        	/* Подготвя масив с всички стелажи от избрания склад, за които има етикети */
        	$query = $mvc->getQuery();
        	$where = "#storeId = {$selectedStoreId}";
        	
            while($recRacksLabels = $query->fetch($where)) {
                $availableRacksWithLabelsArr[] = $recRacksLabels->rackId;
            }
            
            unset($recRacksLabels, $where);
            /* ENDOF Подготвя масив с всички стелажи от избрания склад, за които има етикети */
        	
        	/* Подготвя масив, с тези стелажи за които няма етикети */
            $queryRacks = store_Racks::getQuery();
            $where = "#storeId = {$selectedStoreId}";

            while($recRacks = $queryRacks->fetch($where)) {
                if (!in_array($recRacks->id, $availableRacksWithLabelsArr)) {
                	$availableRacksWithNoLabelsArr[] = $recRacks->id;
                }
            }

            $data->form->setOptions('rackId', $availableRacksWithNoLabelsArr);
            /* ENDOF Подготвя масив, с тези стелажи за които няма етикети */
        }
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

}