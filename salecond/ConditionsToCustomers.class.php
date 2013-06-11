<?php

/**
 * Клас 'salecond_ConditionsToCustomers'
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class salecond_ConditionsToCustomers extends core_Manager
{
    
    
    /**
     * Поле - ключ към мастера
     */
    //var $masterKey = 'cId';
    
    
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
   //var $listFields = 'otherId, otherValue, tools=Пулт';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools';
    
    
    /**
     * Поле за показване лентата с инструменти
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    //var $tabName = 'sales_Quotations';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('cClass', 'class(interface=doc_ContragentDataIntf)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Клиент->Обект,input=hidden,silent');
        $this->FLD('conditionId', 'key(mvc=salecond_Others,select=name)', 'input,caption=условие,mandatory');
        $this->FLD('value', 'varchar(255)', 'input,caption=Стойност,mandatory');
        //$this->setDbUnique('productId,paramId');
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderDetail_($data)
    {
        
    }
    

    /**
     * Подготвя данните за екстеншъна с условия на офертата
     */
    public static function prepareCustomerSalecond(&$data)
    {
        expect($data->cClass = core_Classes::fetchIdByName($data->masterMvc));
        expect($data->masterId);
        $query = static::getQuery();
        $query->where("#cClass = {$data->cClass} AND #cId = {$data->masterId}");
    	
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = static::recToVerbal($rec);
        }
        
        $data->TabCaption = 'Условия';
	}
    

    /**
     * Рендира екстеншъна с условия на офертата
     */
    public static function renderCustomerSalecond($data)
    {
      	$tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        $tpl->append(tr('Условия на продажба'), 'title');
        
        $img = sbf('img/16/add.png');
	    $addUrl = array('salecond_ConditionsToCustomers', 'add', 'cClass' => $data->cClass, 'cId' => $data->masterId, 'ret_url' => TRUE);
	    $addBtn = ht::createLink(' ', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addSalecond')); 
	    $tpl->append($addBtn, 'title');
        
	    if(count($data->rows)) {
			foreach($data->rows as $id => $row) {
				$tpl->append("<div style='white-space:normal;font-size:0.9em;'>", 'content');
				 $tpl->append($row->conditionId . " - " . $row->value . "<span style='position:relative;top:4px'>" . $row->tools . "</span>", 'content');
				 $tpl->append("</div>", 'content');
				
			}
	    } else {
	    	$tpl->append(tr("Все още няма условия"), 'content');
	    }
	    
	    return $tpl;
    }
   
    
    /**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	
    }
}