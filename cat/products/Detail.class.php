<?php

class cat_products_Detail extends doc_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Намира всички опаковки на продукта
     * @param int $masterId - id на продукт
     * @return array $result -Всички опаковки на продукта
     */
    public function fetchDetails($masterId)
    {
        $query = static::getQuery();
        $query->where("#{$this->masterKey} = '{$masterId}'");
        $result = $query->fetchAll();
        
        return $result;
    }
    
    
    /**
     * Връща записа определен от даден продукт и опаковка
     * @param int $masterId - id на продукта
     * @param int $packagingId - id на опаковка
     * @return stdClass $result - записа на продуктовата опаковка
     */
    public function fetchPackaging($masterId, $packagingId)
    {
    	 $query = static::getQuery();
         $query->where("#{$this->masterKey} = '{$masterId}'");
         $query->where("#packagingId = {$packagingId}");
         $result = $query->fetch();
         
         return $result;
    }
    
    
    /**
     * Намира продуктова опаковка по зададен Код/Баркод
     * @param varchar $code - Код/Баркод на продукта
     * @return stcClass $result - намерения запис
     */
    public function fetchByCode($code)
    {
    	$query = static::getQuery();
    	$query->where(array("#eanCode = '[#1#]'", $code));
    	$result = $query->fetch();
         
        return $result;
    }
    
    
	/**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
        if (($action == 'add' || $action == 'delete') && isset($rec)) {
        	$productState = $mvc->Master->fetchField($rec->productId, 'state');
            
        	if ($productState == 'rejected') {
                $requiredRoles = 'no_one';
            } 
        }
    }
}
