<?php

/**
 * Клас 'techno_GeneralProductsParameters'
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class techno2_GeneralProductsParameters extends core_Manager
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno_GeneralProductsParameters';
	
	
    /**
     * Заглавие
     */
    var $title = 'Параметри на универсални продукти';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Параметър на универсален продукт';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'techno2_Wrapper, plg_RowTools';
    
    
    /**
     * Поле за показване лентата с инструменти
     */
    var $rowToolsField = 'tools';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'no_one';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'techno, ceo';
    
    
    /**
     * Кой да е активния таб
     */
    var $currentTab = "Универсални продукти";
    
    
    /**
	 * Мастър ключ към универсалния продукт
	 */
	var $masterKey = 'generalProductId';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('generalProductId', 'key(mvc=techno2_SpecificationDoc)', 'caption=Продукт,input=hidden,silent');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=name,allowEmpty)', 'input,caption=Параметър,mandatory,silent');
        $this->FLD('value', 'varchar(255)', 'caption=Стойност, mandatory');
        
        $this->setDbUnique('generalProductId,paramId');
    }
    
    
    /**
     * Извиква се след подготовка на формата
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$form = &$data->form;
        
    	if(!$form->rec->id){
    		$form->addAttr('paramId', array('onchange' => "addCmdRefresh(this.form); document.forms['{$form->formAttr['id']}'].elements['value'].value ='';this.form.submit();"));
	    	expect($productId = $form->rec->generalProductId);
			$options = static::getRemainingOptions($productId);
	        if(!$data->form->rec->id){
	        	$options = array('' => '') + $options;
	        }
	        $form->setOptions('paramId', $options);
    	} else {
    		$form->setReadOnly('paramId');
    	}
    	
        if($form->rec->paramId){
        	$form->getField('value')->type = cat_Params::getParamTypeClass($form->rec->paramId, 'cat_Params');
        } else {
        	$form->setField('value', 'input=hidden');
        }
    }
    
    
    /**
     * Помощен метод за показване само на тези компоненти, които
     * не са добавени към спецификацията
     */
    public static function getRemainingOptions($generalProductId)
    {
    	$params = cat_Params::makeArray4Select();
    	$query = static::getQuery();
        $query->where("#generalProductId = {$generalProductId}");
    	
        if(count($params)) {
        	while($rec = $query->fetch()) {
               unset($params[$rec->paramId]);
            }
        }
        
        return $params;
    }
    
    
    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    function prepareParams($data, $short = FALSE)
    {
        if(is_numeric($data)){
        	$data = (object)array('id' => $data);
        }
        
    	$productId = ($data->masterData->rec->id) ? $data->masterData->rec->id : $data->id;
    	$query = $this->getQuery();
        $query->where("#generalProductId = {$productId}");
        while($rec = $query->fetch()){
        	$data->params[$rec->id] = $this->recToverbal($rec);
        }
        
        if(!$short){
        	$remaining = static::getRemainingOptions($productId);
	        if(count($remaining) && $this->haveRightFor('add', (object)array('generalProductId' => $productId))){
	        	$data->addParamUrl = array($this, 'add', 'generalProductId' => $productId);
	        } 
        }
        
        return $data;
    }  

    
    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    function renderParams($data, $short = FALSE)
    {
    	$blockName = ($short) ? "SHORT" : "LONG";
    	$tpl = getTplFromFile('techno2/tpl/Parameters.shtml')->getBlock($blockName);
    	if($data->params){
    		foreach ($data->params as $row){
    			$block = clone $tpl->getBlock('PARAMS');
    			$block->placeObject($row);
    			$block->removeBlocks();
    			$block->append2master();
    		}
    	} elseif($short){
    		$tpl = new ET("");
    	}
    	
    	if($data->addParamUrl){
    		if(cat_Params::count()){
    			$btn = ht::createBtn('Нов параметър', $data->addParamUrl, NULL, NULL, 'ef_icon = img/16/star_2.png,title=Добавяне на нов параметър');
    		} else {
    			$btn = ht::createErrBtn('Нов параметър', 'Няма продуктови параметри');
    		}
    		$tpl->replace($btn, 'ADD');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
    	if($data->form->rec->generalProductId){
    		$data->retUrl = toUrl(array('techno2_SpecificationDoc', 'single', $data->form->rec->generalProductId));
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
       if (isset($rec->generalProductId)){
       		$masterState = techno2_SpecificationDoc::fetchField($rec->generalProductId, 'state');
       		if ($masterState == 'rejected'){
       			$res = 'no_one';
       		}
       } elseif ($action == 'add'){
       		$res = 'no_one';
       }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$paramRec = cat_Params::fetch($rec->paramId);
        if($paramRec->type != 'enum'){
               $Type = cls::get(cat_Params::$typeMap[$paramRec->type]);
               if($paramRec->type == 'double'){
               	   $Type->params['smartRound'] = 'smartRound';
               }
               $row->value = $Type->toVerbal($rec->value);
        }
           
        if($paramRec->type != 'percent'){
            $row->value .=  ' ' . cat_Params::getVerbal($paramRec, 'suffix');
        }
    }
}