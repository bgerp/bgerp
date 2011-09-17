<?php
/**
 * 
 * Складове
 * 
 * Мениджър на складове
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 * @TODO Това е само примерна реализация за тестване на продажбите. Да се вземе реализацията
 * от bagdealing.
 * 
 */
class store_Racks extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Стелажи';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, 
                     acc_plg_Registry, store_Wrapper';
    
    
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
    var $canDelete = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 300;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'num, rows, columns, specification, comment, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        $this->FLD('storeId',         'key(mvc=store_Stores,select=name)',        'caption=Склад, input=hidden');
        $this->FLD('num',             'int',                                      'caption=Стелаж №');
        $this->FLD('rows',            'enum(1,2,3,4,5,6,7,8)',                    'caption=Редове,mandatory');
        $this->FLD('columns',         'int(max=24)',                              'caption=Колони,mandatory');
        $this->FLD('specification',   'varchar(255)',                             'caption=Спецификация');
        $this->FLD('comment',         'text',                                     'caption=Коментар');        
    }
    
    
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        
        $form = $data->form;
        $form->setDefault('storeId', $selectedStoreId);
        
        if (!$data->form->rec->id) {
        	$query = $mvc->getQuery();
        	$where = "1=1";
            $query->limit(1);
            $query->orderBy('num', 'DESC');        
    
            while($recRacks = $query->fetch($where)) {
                $lastNum = $recRacks->num;
            }

            $data->form->setDefault('num', $lastNum + 1);        	
        	$data->form->setDefault('rows', 7);
            $data->form->setDefault('rows', 7);
            $data->form->setDefault('columns', 24);
        }
    }
    
    
    /**
     * Преди извличане на записите от БД филтър по date
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$selectedStoreId}");
    }
        
        
}