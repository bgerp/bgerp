<?php

class cat_products_Detail extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    public static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        $wrapTpl = new ET(getFileContent('cat/tpl/ProductDetail.shtml'));
        $wrapTpl->append($mvc->title, 'TITLE');
        $wrapTpl->append($tpl, 'CONTENT');
        $wrapTpl->replace(get_class($mvc), 'DetailName');
        
        $tpl = $wrapTpl;
    }
    
    public function fetchDetails($masterId)
    {
        /* @var $query core_Query */ 
        $query = static::getQuery();
        $query->where("#{$this->masterKey} = '{$masterId}'");
        
        $result = $query->fetchAll();
        
        return $result;
    }
}
