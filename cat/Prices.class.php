<?php

/**
 * Мениджър за ценовите листи
 */
class cat_Prices extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Цени";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created,  plg_RowTools, 
                     cat_Wrapper, plg_Printing, Products = cat_Products';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, tools, code,title,price,date';
    
    
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
        $this->FLD('productId', 'key(mvc=cat_Products, select=title)', 'caption=Продукт');
        $this->FNC('code', 'varchar', 'caption=Код');
        $this->FNC('title', 'varchar', 'caption=Продукт');
        $this->FLD('groups', 'varchar(255)', 'column=none, input=hidden');
        $this->FLD('units', 'key(mvc=common_Units,select=name)', 'caption=Мярка');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена');
        $this->FLD('date', 'date', 'caption=От дата');
        $this->FLD('allowRoles', 'keylist(mvc=core_Roles, select=role)', 'caption=Достъпна за');
    }
    
    
    /**
     * Подготвяме за показване таблица с цени за всяка продуктова група
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, &$data)
    {
        // $groupsListed е масив, чийто елементи са id и title на всички групи за продуктите,
        // които са видими в ценовите листи (inPriceLists = yes)
        $Groups = cls::get('cat_Groups');
        $queryGroups = $Groups->getQuery();
        $queryGroups->where("#inPriceLists = 'yes'");
        $queryGroups->show("id, title");
        
        while ($groupsRec = $queryGroups->fetch($where)) {
            $groupsRec->title = type_Varchar::escape($groupsRec->title);
            $groupsListed[$groupsRec->id] = $groupsRec;
        }
        
        $data->groupsListed = $groupsListed;
        
        // Всеки елемент на priceListByGroups[] съдържа всички цени по дадена група продукти
        foreach ($groupsListed as $groupListed) {
            $query = clone($data->query);
            
            $cond = "1=1";
            $query->where($cond);
            
            while ($recPrices = $query->fetch($where)) {
                $groups = type_Keylist::toArray($mvc->Products->fetchField($recPrices->productId, 'groups'));
                
                if (in_array($groupListed->id, $groups)) {
                    $priceListByGroups[$groupListed->id][] = $mvc->recToVerbal($recPrices);
                }
            }
        }
        
        $data->priceListByGroups = $priceListByGroups;
    }
    
    
    /**
     * Презаписваме метода за рендиране на таблицата с редовете
     *
     * @param stdClass $data
     */
    function renderListTable_($data)
    {
        $table = cls::get('core_TableView', array('mvc' => $this));
        
        $data->listFields = arr::make($data->listFields, TRUE);
        
        // Цикъл за всяка група
        foreach ($data->groupsListed as $group) {
            $fields = array();
            
            if (count($data->priceListByGroups[$group->id])) {
                foreach ($data->listFields as $key => $value) {
                    $fields[$key] = $group->title . "->" . $value;
                }
                
                $tpl .= $table->get($data->priceListByGroups[$group->id], $fields);
                $tpl .= "<br/><br/>";
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Промяна на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareListRecs($mvc, $tpl, $data)
    {
        $data->title = "Ценова листа на ЕКСТРАПАК ООД към " . date('Y-m-d');
    }
    
    
    /**
     * Добавяне в таблицата на линк към детайли на продукта. Обр. на данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->code = $mvc->Products->fetchField($rec->productId, 'code');
        $row->title = type_Varchar::escape($mvc->Products->fetchField($rec->productId, 'title'));
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave($mvc, &$id, &$rec)
    {
        $Products = cls::get('cat_Products');
        $groups = $Products->fetchField($rec->productId, 'groups');
        $rec->groups = $groups;
    }
}