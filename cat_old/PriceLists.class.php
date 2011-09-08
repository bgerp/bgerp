<?php

/**
 * Мениджър за ценовите листи
 */
class cat_PriceLists extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Ценови листи";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created,  plg_RowTools, cat_Wrapper, plg_Sorting, plg_Printing, Products=cat_Products';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'tools=Ред,title=Ценови листи';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,cat,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,cat';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,cat';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,cat';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,cat';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Име, mandatory');
        $this->FLD('allowRoles', 'keylist(mvc=core_Roles, select=role)', 'caption=Достъпна за');
    }
    
    
    /**
     * Слагаме productId and productTitle в сесията
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $productId = Request::get('productId', 'int');
        
        Mode::setPermanent('productId', $productId);
        Mode::setPermanent('productTitle', $mvc->Products->fetchField($productId, 'title'));
        
        if (haveRole('admin,cat')) {
            return;
        }
        
        $data->query->likeKeylist("allowRoles", Users::getCurrent('roles'));
    }
    
    
    /**
     * Добавяне на линк към ценовата листа
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
        $row->title = Ht::createLink($row->title, array('cat_PriceListDetails', 'List', 'priceListId' => $rec->id, 'productId' => Mode::get('productId')));
    }
    
    
    /**
     * Смяна на бутона
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        /*
        if (Request::get('productId', 'int')) {
            $data->toolbar->removeBtn('*');
        }
        */
    }
    
    
    /**
     * Ако няма дефинирани атрибути, дефинира 2 атрибута при инсталиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        $data = array(
            array(
                'title' => 'Цени на едро',
            ),
            array(
                'title' => 'Цени на дребно',
            ),
            array(
                'title' => 'VIP цени',
            ),
            array(
                'title' => 'Цени за Русия',
            )
        );
        
        $nAffected = 0;
        
        foreach ($data as $rec) {
            $rec = (object)$rec;
            
            if (!$this->fetch("#title='{$rec->title}'")) {
                if ($this->save($rec)) {
                    $nAffected++;
                }
            }
        }
        
        if ($nAffected) {
            $res .= "<li>Добавени са {$nAffected} ценови листи.</li>";
        }
    }
}