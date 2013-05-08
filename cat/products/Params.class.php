<?php

/**
 * Клас 'cat_products_Params'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class cat_products_Params extends cat_products_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Параметри';
    var $singleTitle = 'Параметър';
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'paramId, paramValue, tools=Пулт';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper,plg_RowTools';
    
    var $rowToolsField = 'tools';
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=name)', 'input,caption=Параметър,mandatory');
        $this->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност,mandatory');
        
        $this->setDbUnique('productId,paramId');
    }
    
     
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        $recs = &$data->recs;
        
        if ($recs) {
            $rows = &$data->rows;
            
            foreach ($recs as $i=>$rec) {
                
                $row = $rows[$i];
                
                $paramRec = cat_Params::fetch($rec->paramId);
                
                $row->paramValue .=  ' ' . cat_Params::getVerbal($paramRec, 'suffix');
            }
        }
      
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        
        expect($productId = $form->rec->productId);

        $options = self::getRemainingOptions($productId, $form->rec->id);

        expect(count($options));
        
        if(!$data->form->rec->id){
        	$options = array('' => '') + $options;
        }
		
        $form->setOptions('paramId', $options);
    }

    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     */
    static function getRemainingOptions($productId, $id = NULL)
    {
        $options = cat_Params::makeArray4Select();
        
        if(count($options)) {
            $query = self::getQuery();
            
            if($id) {
                $query->where("#id != {$id}");
            }

            while($rec = $query->fetch("#productId = $productId")) {
               unset($options[$rec->paramId]);
            }
        } else {
            $options = array();
        }

        return $options;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
             
            
             
        }
    }
    
    
    /**
     * Връща стойноста на даден параметър за даден продукт по негово sysId
     * @param int $productId - ид на продукт
     * @param int $sysId - sysId на параметъра
     * @return varchar $value - стойноста на параметъра
     */
    public static function fetchParamValue($productId, $sysId)
    {
     	$paramId = cat_Params::fetchIdBySysId($sysId);
     	if($paramId){
     		return static::fetchField("#productId = {$productId} AND #paramId = {$paramId}", 'paramValue');
     	}
     	
     	return NULL;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderDetail_($data)
    {
        $tpl = new ET(getFileContent('cat/tpl/products/Params.shtml'));
        
        $tpl->append($data->changeBtn, 'TITLE');
        
        foreach((array)$data->rows as $row) {
            $block = $tpl->getBlock('param');
            $block->placeObject($row);
            
            $block->append2Master();
        }
            
        return $tpl;
    }
    

    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    public static function prepareParams($data)
    {
        static::prepareDetail($data);
        
        if(count(self::getRemainingOptions($data->masterId))) {
            $data->addUrl = array('cat_products_Params', 'add', 'productId' => $data->masterId, 'ret_url' => TRUE);
        }

    }
    

    /**
     * Рендира екстеншъна с параметри на продукт
     */
    public static function renderParams($data)
    {
        if($data->addUrl) {
            $data->changeBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " valign=bottom style='margin-left:5px;'>", $data->addUrl);
        }

        return static::renderDetail($data);
    }
}