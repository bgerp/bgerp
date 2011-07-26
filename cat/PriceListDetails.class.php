<?php 

/**
 * Менаджира детайлите на цените за дадена ценова листа
 */
class cat_PriceListDetails extends core_Manager
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
    var $loadList = 'plg_Created,  plg_RowTools, cat_Wrapper, plg_Sorting, plg_State2, Products=cat_Products, PriceLists=cat_PriceLists';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'tools=Ред, date=От дата, priceBgn=BGN, priceEur=EUR, comment=Коментар';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('priceListId', 'key(mvc=cat_PriceLists, select=title)', 'caption=Ценова листа, input=hidden, silent');
        $this->FLD('productId', 'key(mvc=cat_Products, select=title)', 'caption=Продукт, input=hidden, silent');
        $this->FLD('date', 'date', 'caption=От дата, mandatory');
        $this->FLD('priceBgn', 'double', 'caption=BGN, mandatory, notSorting');
        $this->FNC('priceEur', 'double', 'caption=EUR, notSorting');
        $this->FLD('comment', 'text', 'caption=Коментар, notSorting');
    }
    
    
    /**
     * Зарежда записа само за $productId-то и $priceListId-то от заявката
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $priceListId = Request::get('priceListId', 'int');
        $priceListTitle = $mvc->PriceLists->fetchField($priceListId, 'title');
        
        if ($priceListId > 0) {
            Mode::setPermanent('priceListId', $priceListId);
            Mode::setPermanent('priceListTitle', $priceListTitle);
        } else {
            $priceListId = Mode::get('priceListId');
            $priceListTitle = Mode::get('priceListTitle');
        }
        
        expect($priceListId);
        
        $productId = Request::get('productId', 'int');
        
        if ($productId > 0) {
            Mode::setPermanent('productId', $productId);
            Mode::setPermanent('productTitle', $mvc->Products->fetchField($productId, 'title'));
        } else {
            $productId = Mode::get('productId');
            $productTitle = Mode::get('productTitle');
        }
        
        expect($productId);
        
        $data->query->where("#priceListId = {$priceListId} AND #productId = {$productId}");
        $data->query->orderBy('date', 'DESC');
        
        $data->title = "<b>" . Mode::get('priceListTitle') . "</b> за продукт \"" . Mode::get('productTitle')."\"";
    }
    
    
    /**
     * Форматиране на цените
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
        $row->priceBgn = number_format($rec->priceBgn, 2);
        $row->priceEur = $rec->priceBgn / 1.9558;
        $row->priceEur = number_format($row->priceEur, 2);
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
        $priceListId = Mode::get('priceListId');
        $priceListTitle = Mode::get('priceListTitle');
        $productId = Mode::get('productId');
        $productTitle = Mode::get('productTitle');
        
        $data->form->title = "Добавяне на запис в {$priceListTitle} за продукт {$productTitle}";
        $data->form->setDefault('priceListId', $priceListId);
        $data->form->setDefault('productId', $productId);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getLastDate()
    {
        $rec = new stdClass();
        
        $priceListId = Mode::get('priceListId');
        $productId = Mode::get('productId');
        
        $query = $this->getQuery();
        
        $query->where("#productId = '{$productId}' AND #priceListId = '{$priceListId}'");
        $query->limit(1);
        $query->orderBy('date', 'DESC');
        
        $rec = $query->fetch();
        
        $lastDate = $rec->date;
        
        return $lastDate;
    }
    
    
    /**
     * Проверява дали дата е по-голяма от последно въведената дата
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        // проверка дали формата е submit-ната
        if (!$form->isSubmitted()){
            return;
        }
        
        $lastDate = $this->getLastDate();
    }
}