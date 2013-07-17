<?php



/**
 * Помощен клас за менажиране на компонентите на спецификациите
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_Components extends core_Manager {
    
    
     /**
     * Кой може да го прочете?
     */
    var $canRead = 'no_one';
    
    
    /**
     * Кой може да променя?
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Продуктите от кои групи могат да са компоненти
     */
    static $allowedGroups = 'prefabrications';
    
    
    /**
     * Връща  форма за добавяне на нов компонент
     */
    public function getForm()
    {
    	$form = cls::get('core_Form');
    	$form->FNC('componentId', 'varchar(255)', 'mandatory,input,caption=Продукт,silent');
    	$form->FNC('quantity', 'double', 'caption=К-во,input');
    	$form->FNC('cPrice', 'double(decimals=2)', 'caption=Стойност,input');
    	$form->FNC('amount', 'double(decimals=2)', 'caption=Сума');
    	$form->FNC('cMeasureId', 'key(mvc=cat_UoM,select=shortName)', 'caption=Мярка');
    	$form->FNC('bTaxes', 'double(decimals=2)', 'caption=Такса,input');
    	$form->FNC('vat', 'percent', 'caption=ДДС');
    	
    	$products = array('-1' => tr('Основа')) + cat_Products::getByGroup(static::$allowedGroups);
    	
    	$form->fields['componentId']->type = cls::get("type_Enum", array('options' => $products));
    	
    	return $form;
    }
    
    
    /**
     * Екшън за конфигуриране на компоненти
     */
    function act_Configure()
    {
    	expect($id = Request::get('id', 'int'));
    	$Specifications = cls::get('techno_Specifications');
    	expect($rec = $Specifications->fetch($id));
    	$Specifications->requireRightFor('configure', $rec);
    	$data = unserialize($rec->data);
    	$retUrl = array('techno_Specifications', 'single', $id, "#" => "Sp{$id}");
    	$GeneralProduct = cls::get('techno_GeneralProducts');
    	$Policy = cls::get('price_ListToCustomers');
    	
    	if($componentId = Request::get('delete')){
    		unset($data->components->recs[$componentId]);
    		$rec->data = $GeneralProduct->serialize($data);
	        $Specifications->save($rec);
	        return Redirect($retUrl);
    	}
    	
    	$form = $this->getForm();
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
    	if(Request::get('edit')){
    		$componentId = Request::get('componentId');
    		$form->rec = $data->components->recs[$componentId];
    		if($form->rec->componentId == -1){
    			$form->rec->cPrice = $form->rec->amount;
    		}
    		$form->setReadOnly('componentId');
    		$action = tr('Редактиране');
    	} else {
    		$action = tr('Добавяне');
	    	$form->setOptions('componentId', $this->getRemainingOptions($data->components->recs));
    	}
    	
        $fRec = $form->input();
        
        if($form->isSubmitted()) {
        	if($Specifications->haveRightFor('configure', $rec)){
        		$this->validateForm($fRec, $form, $rec->folderId);
	        	
        		if(!$form->gotErrors()){
        			$data->components->recs[$fRec->componentId] = $fRec;
		        	$rec->data = $GeneralProduct->serialize($data);
			        $Specifications->save($rec);
			        return  Redirect(array($Specifications, 'single', $rec->id));
        		}
        	}
        }
        
        $form->title = "{$action} на компоненти към |*" . $Specifications->recToVerbal($rec, 'id,title,-list')->title;
    	return $Specifications->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Помощна функция за валидиране на формата с компоненти
     * @param stdClass $rec - запис на компонент
     * @param core_Form $form - формата за компоненти
     * @param int folderId - ид на папката на спецификацията
     */
    private function validateForm(&$rec, core_Form &$form, $folderId)
    {
    	if($rec->componentId != -1){
	        if(!$rec->quantity){
		        $form->setError('quantity', 'Моля задайте количество');
		    }
	        $rec->cMeasureId = cat_Products::fetchField($rec->componentId, 'measureId');
        	$rec->vat = cat_Products::getVat($rec->componentId);
        	if(!$rec->bTaxes){
        		$rec->bTaxes = cat_products_Params::fetchParamValue($rec->componentId, 'bTax');
        	}
        } else {
        	$rec->quantity = 1;
        }
        	if(!$rec->cPrice){
        		$Policy = cls::get('price_ListToCustomers');
		        $contClass = doc_Folders::fetchCoverClassId($folderId);
			    $contId = doc_Folders::fetchCoverId($folderId);
			    $rec->cPrice = $Policy->getPriceInfo($contClass, $contId, $rec->componentId, NULL, $rec->quantity, dt::now())->price;
			    if(!$rec->cPrice){
			        $form->setError('cPrice', 'Проблем при извличането на цената! Моля задайте ръчно');
			    }
	        }
	        $rec->amount = $rec->quantity * $rec->cPrice;
	        if($rec->componentId == -1){
	        	unset($rec->quantity, $rec->cPrice);
	        }
    }
    
    
    /**
     * Помощен метод за показване само на тези компоненти, които
     * не са добавени към спецификацията
     * @param stdClass $rec - компонентите от спецификацията
     * @return array $options - масив с опции
     */
    private function getRemainingOptions($recs)
    {
    	$products = array('-1' => tr('Основа')) + cat_Products::getByGroup(static::$allowedGroups);
    	foreach ($products as $id => $name){
    		if(isset($recs[$id])){
    			unset($products[$id]);
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * Помощен метод за превеждане на компонентите във вербален вид
     * @param array $components - масив с компоненти
     * @param int $specId - ид на спецификацията
     * @param array $res - масив в който ще се връщат вербалните данни
     */
    public static function getVerbal($components, $specId, &$res)
    {
    	if($components){
    		$i = 1;
    		$taxes = $total = 0;
    		$fields = static::getForm()->selectFields('');
    		foreach ($components as $component){
    			$res->rows[$component->componentId] = static::getRow($component, $fields);
    			$res->rows[$component->componentId]->num = $i;
    			$res->rows[$component->componentId]->tools = static::getParamTools($component->componentId, $specId);
    			$total += $component->amount;
    			$taxes += $component->bTaxes;
    			$i++;
    		}
    		
    		$Double = cls::get('type_Double');
	    	$Double->params['decimals'] = 2;
	    	$res->total = (object)array('totalAmount' => $Double->toVerbal($total), 'totalTaxes' => ($taxes) ? $Double->toVerbal($taxes) : NULL);
    	}
    }
    
    
	/**
     * Създаване на туулбара на компонентите
     * @param int $componentId - ид на компонент
     * @param int $specificationId - ид на спецификация
     * @return core_ET $tpl - туулбара за редакция
     */
    private static function getParamTools($componentId, $specificationId)
    {
    	if(techno_Specifications::haveRightFor('configure', $specificationId) && !Mode::is('printing')) {
    		
	        $editImg = "<img src=" . sbf('img/16/edit-icon.png') . " alt=\"" . tr('Редакция') . "\">";
			$deleteImg = "<img src=" . sbf('img/16/delete.png') . " alt=\"" . tr('Изтриване') . "\">";
	        
			$editUrl = array('techno_Components', 'configure', $specificationId, 'componentId' => $componentId, 'edit' => TRUE);
	        $deleteUrl = array('techno_Components', 'configure', $specificationId, 'delete' => $componentId, 'ret_url' => TRUE);

	        $editLink = ht::createLink($editImg, $editUrl, NULL, "id=edtS{$componentId}");
	        $deleteLink = ht::createLink($deleteImg, $deleteUrl, tr('Наистина ли желаете компонента да бъде изтрит?'), "id=delS{$componentId}");
    		
	        $tpl = new ET($editLink . " " . $deleteLink);
    	}
    	
        return $tpl;
    }
    
    
    /**
     * Помощна функция за вербализирането на един компонент
     * @param stdClass $rec - запис на компонент
     * @param array $fields - полетата от формата
     * @return stdClass $row - вербалното представяне
     */
    private static function getRow($rec, $fields)
    {
    	$row = new stdClass();
    	foreach($fields as $name => $fld){
    		if($name == 'quantity'){
    			$fld->type->params['decimals'] = strlen(substr(strrchr($rec->quantity, "."), 1));
    		}
    		$row->{$name} = $fld->type->toVerbal($rec->$name);
    		if($name == 'componentId' && !Mode::is('printing') && $rec->componentId != '-1'){
    			$row->componentId = ht::createLink($row->componentId, array('cat_Products', 'single', $rec->componentId));
    		}
    	}
    	
    	return $row;
    }
    
    
    /**
     * Рендиране на компонентите
     * @param array $data - масив с данни
     * @param bool $short - дали шаблона е за кратък изглед или не
     * @return core_ET $tpl
     */
    public static function renderComponents($data, $short)
    {
    	$tplFile = getTplFromFile('techno/tpl/Components.shtml');
    	if($short){
    		$paramBlock = $tplFile->getBlock('SHORT');
    		unset($data->rows['-1']);
    	} else {
    		$paramBlock = $tplFile->getBlock('LONG');
    	}
    	
    	if(count($data->rows)){
    		$paramBlock->replace(' ', 'TH');
    		
    		if($data->total){
    			$paramBlock->placeObject($data->total);
    		}
    		
    		foreach($data->rows as $id => $row){
    			$blockCl = clone($paramBlock->getBlock('COMPONENT'));
    			$blockCl->placeObject($row);
    			$blockCl->removeBlocks();
    			$paramBlock->append($blockCl, 'COMPONENT');
    		}
    	}
    	return $paramBlock;
    }
}