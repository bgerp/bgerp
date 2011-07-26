<?php 

/**
 * Менаджира детайлите на продуктите (Details)
 */
class cat_ProductDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Детайли на продукт";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created,  plg_RowTools, cat_Wrapper, plg_Sorting, 
                     plg_State2, Products=cat_Products, Units=common_Units';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'productId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'tools=Ред,attrId,value';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products, select=title)', 'caption=Продукт, input=hidden, silent');
        $this->FLD('attrId', 'key(mvc=cat_Attributes, select=name)', 'caption=Атрибут, notSorting');
        $this->FLD('value', 'varchar', 'caption=Стойност, notSorting');
    }
    
    
    /**
     * Зарежда записа само за $id-то от заявката
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $productId = Request::get('id', 'int');
        
        if ($productId > 0) {
            Mode::setPermanent('productId', $productId);
            Mode::setPermanent('productTitle', $mvc->Products->fetchField($productId, 'title'));
        } else {
            $productId = Mode::get('productId');
            $productTitle = Mode::get('productTitle');
        }
        
        expect($productId);
        
        $data->query->where("#productId = {$productId}");
    }
    
    
    /**
     * Променя заглавието и добавя стойност по default в селекта за избор на продукт
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $productId = Mode::get('productId');
        $productTitle = Mode::get('productTitle');
        
        $data->form->title = "Добавяне на запис в \"Детайли на продукт\" за продукт \"" . type_Varchar::toVerbal($productTitle) . "\"";
        $data->form->setDefault('productId', $productId);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderDetailLayout_($data)
    {
        // Шаблон за листовия изглед
        $listLayout = "
            [#ListTable#]
            [#ListSummary#]
            [#ListToolbar#]
        ";
        
        if ($this->listStyles) {
            $listLayout = "\n<style>\n" . $this->listStyles . "\n</style>\n" . $listLayout;
        }
        
        $listLayout = ht::createLayout($listLayout);
        
        return $listLayout;
    }
}