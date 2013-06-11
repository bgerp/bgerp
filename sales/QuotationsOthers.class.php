<?php

/**
 * Клас 'sales_QuotationsOthers'
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class sales_QuotationsOthers extends core_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'quotationId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Други условия';
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Друго условие';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'otherId, otherValue, tools=Пулт';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'sales_Wrapper,plg_RowTools';
    
    
    /**
     * Поле за показване лентата с инструменти
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'sales_Quotations';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('quotationId', 'key(mvc=sales_Quotations)', 'input=hidden');
        $this->FLD('otherId', 'key(mvc=salecond_Others,select=name)', 'input,caption=Параметър,mandatory');
        $this->FLD('otherValue', 'varchar(255)', 'input,caption=Стойност,mandatory');
        //$this->setDbUnique('productId,paramId');
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderDetail_($data)
    {
        $tpl = getTplFromFile('salecond/tpl/QuotationOthers.shtml');
        
        if($data->changeBtn){
        	$tpl->append($data->changeBtn, 'addOther');
        }
        
        foreach((array)$data->rows as $row) {
            $block = $tpl->getBlock('otherValue');
            $block->placeObject($row);
            $block->append2Master();
        }
            
        return $tpl;
    }
    

    /**
     * Подготвя данните за екстеншъна с условия на офертата
     */
    public static function prepareOthers($data)
    {
        static::prepareDetail($data);
	}
    

    /**
     * Рендира екстеншъна с условия на офертата
     */
    public static function renderOthers($data)
    {
      	if(sales_QuotationsOthers::haveRightFor('write', $data->masterData->rec) && !Mode::is('printing')){
      		$addUrl = array('sales_QuotationsOthers', 'add', "quotationId" => $data->masterId, 'ret_url' => TRUE);
    		$data->changeBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . ">", $addUrl , FALSE, array('id' => 'add-others'));
      	}
      	
    	return static::renderDetail($data);
    }
    
    
    /**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if($action == 'write' && $rec->quotationId){
    		
    		// Ако офертата е активирана или отказана неможем да модифицираме
    		$quoteState = sales_Quotations::fetchField($rec->quotationId, 'state');
    		if($quoteState != 'draft'){
    			$res = 'no_one';
    		}
    	}
    }
}