<?php
/**
 * 
 * Мениджър на групи с продукти.
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class cat_Groups extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Групи на продуктите";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,name';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('info', 'text', 'caption=Инфо');
        $this->FLD('productCnt', 'int', 'input=none');
    }
    
    
    function on_AfterPrepareListRecs($mvc, $data)
    {
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
            	$rec = $data->recs[$i];
            	$row->productCnt = intval($rec->productCnt);
            	$row->name = $rec->name;
            	$row->name .= " ({$row->productCnt})";
            	$row->name .= "<div><small>{$rec->info}</small></div>";
            }
        }
    }
    
    
    static function updateProductCnt($id)
    {
    	$query = cat_Products::getQuery();
    	$productCnt = $query->count("#groups LIKE '%|{$id}|%'");
    	
    	return static::save((object)compact('id', 'productCnt'));
    }
}