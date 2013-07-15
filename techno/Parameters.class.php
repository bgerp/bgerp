<?php



/**
 * Помощен клас за менажиране на параметрите на спецификациите
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_Parameters extends core_Manager {
    
    
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
     * Връщане на форма за добавяне на нови параметри
     */
    public function getAddParamForm($data)
    {
    	$form = cls::get('core_Form');
    	$form->formAttr['id'] = 'addParamSpec';
    	$form->FLD('paramId', 'key(mvc=cat_Params,select=name,maxSuggestions=10000)', 'input,caption=Параметър,mandatory,silent');
        $form->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност,mandatory');
        return $form;
    }
    
    
	/**
     * Екшън за добавяне, изтриване и редактиране на параметри
     */
    function act_Configure()
    {
    	$Specifications = cls::get('techno_Specifications');
    	$GeneralProduct = cls::get('techno_GeneralProducts');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $Specifications->fetch($id));
    	$Specifications->requireRightFor('configure', $rec);
    	$data = unserialize($rec->data);
    	$retUrl = array('techno_Specifications', 'single', $id, "#" => "Sp{$id}");
    	
    	if($paramId = Request::get('delete')){
    		unset($data->params[$paramId]);
    		$rec->data = $GeneralProduct->serialize($data);
	        $Specifications->save($rec);
	        return Redirect($retUrl);
    	}
    	
    	$form = $this->getAddParamForm($data);
        
    	if(Request::get('edit')){
        	$paramId = Request::get('paramId');
        	$form->rec->paramValue = $data->params[$paramId];
        	$form->rec->paramId = $paramId;
        	$form->setReadOnly('paramId');
        	$action = tr('Редактиране');
        } else {
        	$form->addAttr('paramId', array('onchange' => "addCmdRefresh(this.form); document.forms['{$form->formAttr['id']}'].elements['paramValue'].value ='';this.form.submit();"));
        	$form->addAttr('paramId', array('onchange' => "addCmdRefresh(this.form); document.forms['addParamSpec'].elements['paramValue'].value ='';this.form.submit();"));
	    	$paramOptions = $this->getRemainingOptions($data);
	    	$form->setOptions('paramId', array('' => '') + $paramOptions);
        	$action = tr('Добавяне');
        }
        
        if($paramId = Request::get('paramId')){
        	$form->fields['paramValue']->type = cat_Params::getParamTypeClass($paramId, 'cat_Params');
        } else {
        	$form->setField('paramValue', 'input=hidden');
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        $fRec = $form->input();
        if($form->isSubmitted()) {
        	if($Specifications->haveRightFor('configure', $rec)){
        		
        		// Проверка дали въведените стойности за правилни
        		if(!$form->gotErrors()){
        			
        			// Записваме въведените данни в пропъртито data на река
		            $data->params[$fRec->paramId] = $fRec->paramValue;
	        		$rec->data = $GeneralProduct->serialize($data);
		            $Specifications->save($rec);
		            return  Redirect(array($Specifications, 'single', $rec->id));
        		}
        	}
        }
        
        $form->title = "{$action} на параметри към |*" . $Specifications->recToVerbal($rec, 'id,title,-list')->title;
    	return $Specifications->renderWrapping($form->renderHtml());
    }
    
    
	/**
     * Помощен метод за показване само на тези параметри, които
     * не са добавени към продукта
     * @param stdClass $data - десериализираната информация
     * @return array $options - масив с опции
     */
    private function getRemainingOptions($data)
    {
        $options = cat_Params::makeArray4Select();
        if(count($options)){
      	   foreach($options as $id => $value){
      		 if(isset($data->params[$id])){
      			unset($options[$id]);
      		 } 
      	   }
        }
      
      return $options;
    }
    
    
    /**
     * Помощен метод за превеждане на параметрите във вербален вид
     * @param array $params - масив с параметри
     * @param int $specId - ид на спецификацията
     * @param array $res - масив в който ще се връщат вербалните данни
     */
    public static function getVerbal($params, $specId, &$res)
    {
    	if($params){
    		$varchar = cls::get('type_Varchar');
    		$double = cls::get('type_Double');
    		$double->params['decimals'] = 2;
    		
	    	foreach($params as $paramId => $value){
	    		$arr['paramId'] = cat_Params::getTitleById($paramId);
	    		$arr['paramValue'] = (is_numeric($value)) ? $double->toVerbal($value) : $varchar->toVerbal($value);
	    		$suffix = $varchar->toVerbal(cat_Params::fetchField($paramId, 'suffix'));
	    		$arr['paramValue'] .= " &nbsp;{$suffix}";
	    		$arr['tools'] = static::getParamTools($paramId, $specId);
	        	$res[$paramId] = $arr;
	    	}
    	}
    }
    
    
	/**
     * Създаване на туулбара на параметрите
     * @param int $paramId - ид на параметър
     * @param int $specificationId - ид на спецификация
     * @return core_ET $tpl - туулбара за редакция
     */
    private static function getParamTools($paramId, $specificationId)
    {
    	if(techno_Specifications::haveRightFor('configure', $specificationId) && !Mode::is('printing')) {
    		
	        $editImg = "<img src=" . sbf('img/16/edit-icon.png') . " alt=\"" . tr('Редакция') . "\">";
			$deleteImg = "<img src=" . sbf('img/16/delete.png') . " alt=\"" . tr('Изтриване') . "\">";
	        
			$editUrl = array('techno_Parameters', 'configure', $specificationId, 'paramId' => $paramId, 'edit' => TRUE, 'ret_url' => TRUE);
	        $deleteUrl = array('techno_Parameters', 'configure', $specificationId, 'delete' => $paramId, 'ret_url' => TRUE);

	        $editLink = ht::createLink($editImg, $editUrl, NULL, "id=edtS{$paramId}");
	        $deleteLink = ht::createLink($deleteImg, $deleteUrl, tr('Наистина ли желаете параметърът да бъде изтрит?'), "id=delS{$paramId}");
    		
	        $tpl = new ET($editLink . " " . $deleteLink);
    	}
    	
        return $tpl;
    }
}