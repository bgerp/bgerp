<?php
/**
 * 
 * Себестойности на продуктите от каталога
 * 
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class catpr_Costs extends core_Manager
{
	var $title = 'Себестойност';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools,
                     catpr_Wrapper, plg_AlignDecimals';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'productId, priceGroupId, valior, cost, baseDiscount, publicPrice, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,catpr';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,catpr,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,catpr,broker';
    
    var $canList = 'admin,catpr,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,catpr';
	
    
    function description()
	{
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input,caption=Продукт');
		$this->FLD('valior', 'date', 'input,caption=Вальор');
		$this->FLD('cost', 'double(minDecimals=2)', 'input,caption=Себестойност');
		$this->FLD('priceGroupId', 'key(mvc=catpr_Pricegroups,select=name)', 'input,caption=Група');
		
		$this->EXT('baseDiscount', 'catpr_Pricegroups', 'externalKey=priceGroupId,input=none,caption=Базова отстъпка');
		
		$this->FNC('publicPrice', 'double(decimals=2,minDecimals=2)', 'caption=Публична цена');
		
		$this->setDbUnique('productId, valior');
	}
	
	
	function on_CalcPublicPrice($mvc, &$rec)
	{
		$rec->publicPrice = self::getPublicPrice($rec);
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
		$data->query->orderBy('productId');
		$data->query->orderBy('valior', 'desc');
	}
	
	
	function on_AfterPrepareListRecs($mvc, $data)
	{
		$rows = &$data->rows;
		$recs = &$data->recs;
		
		$prevProductId = NULL;
		$prevGroupId   = NULL;
		
		foreach ($data->rows as $i=>&$row) {
			$rec = $recs[$i];
			if ($rec->productId == $prevProductId) {
				$row->productId = '';
				if ($rec->priceGroupId == $prevGroupId) {
					$row->priceGroupId = '';
				}
				$row->CSS_CLASS[] = 'quiet';
			} else {
				$row->productId = "<strong>{$row->productId}</strong>";
			}
			
			$prevProductId = $rec->productId;
			$prevGroupId   = $rec->priceGroupId;
		}
	}
	
	static function getPublicPrice($rec)
	{
		return (double)$rec->cost / (1 - (double)$rec->baseDiscount);
	}
	
	
	/**
	 * Себестойността на продукт към дата или историята на себестойностите на продукта.
	 *
	 * @param int $id key(mvc=cat_Product)
	 * @param string $date дата, към която да се изчисли себестойността или NULL за историята на 
	 * 						себестойностите.
	 * @return array масив от записи на този модел - catpr_Costs
	 */
	static function getProductCosts($id, $date = NULL)
	{
		$query = self::getQuery();
		
		$query->orderBy('valior', 'desc');
		$query->where("#productId = {$id}");
		if (isset($date)) {
			$query->where("#valior <= '{$date}'");
			$query->limit(1);
		}
		
		$result = array();
		
		while ($rec = $query->fetch()) {
			$result[] = $rec;
		}
		
		return $result;
	}
}