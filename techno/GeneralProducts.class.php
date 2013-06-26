<?php



/**
 * Универсален драйвър за нестандартни продукти
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_GeneralProducts extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'techno_ProductsIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Универсален драйвър";
    
    
    /**
     * Шаблон за показване на кратката версия на изделието
     */
    var $singleShortLayoutFile = 'techno/tpl/SingleLayoutGeneralProductsShort.shtml';
    
    
    /**
     * Шаблон за показване на нормалната версия на изделието
     */
    var $singleLayoutFile = 'techno/tpl/SingleLayoutGeneralProducts.shtml';
    
    
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
    
    
    /*
     * РЕАЛИЗАЦИЯ НА techno_ProductsIntf
     */
    
    
    /**
     * Връща форма, с която могат да се въвеждат параметри на
     * определен клас нестандартно изделие
     * @param int $folderId - ид на папката
     * @return core_Form $form - Формата на мениджъра
     */
    public function getEditForm($data)
    {
    	$form = cls::get('core_Form');
    	$form->FNC('title', 'varchar', 'caption=Заглавие, mandatory,remember=info,width=100%,input');
    	$form->FNC('description', 'richtext(rows=5, bucket=Notes)', 'caption=Описание,input,mandatory,width=100%');
		$form->FNC('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input');
    	$form->FNC('price', 'double(decimals=2)', 'caption=Цени->Ед. Себестойност,width=8em,mandatory,input');
		$form->FNC('bTaxes', 'double(decimals=2)', 'caption=Цени->Нач. такси,width=8em,input');
		$form->FNC('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Цени->Валута,width=8em,input');
    	$form->FNC('discount', 'percent(decimals=2)', 'caption=Цени->Отстъпка,width=8em,input,unit=%');
		$form->FNC('image', 'fileman_FileType(bucket=techno_GeneralProductsImages)', 'caption=Параметри->Изображение,input');
		$form->FNC('code', 'varchar(64)', 'caption=Параметри->Код,remember=info,width=15em,input');
        $form->FNC('eanCode', 'gs1_TypeEan', 'input,caption=Параметри->EAN,width=15em,input');
		
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
        if($data->data){
        	
        	// При вече въведени характеристики, слагаме ги за дефолт
        	$form->rec = unserialize($data->data);
        }
        
        return $form;
    }
    
    
    /**
     * Връщане на форма за добавяне на нови параметри
     */
    public function getAddParamForm($data)
    {
    	$form = cls::get('core_Form');
    	$form->FLD('paramId', 'key(mvc=cat_Params,select=name)', 'input,caption=Параметър,mandatory');
        $form->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност,mandatory');
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png');
    	
        return $form;
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
     * Връща сериализиран вариант на данните, които представят
     * дадено изделие или услуга
     * @param stdClass $data - Обект с данни от модела 
     * @return blob $serialized - сериализирани данни на обекта
     */
    public function serialize($data)
    {
       return serialize($data);
    }
    
    
    /**
     * Изчислява цената на продукта по формулата:
     * ([Начални такси] * (1 + НадценкаМакс) + [Количество] * 
     *  [Единична себестойност] *(1 + НадценкаМин)) / [Количество]
     * @param int $customerClass - контрагент клас
     * @param int $customerId - контрагент Ид
     * @param stdClass $data - дата от модела
     * @param int $packagingId - ид на опаковка
     * @param double quantity - количество
     * @param datetime $datetime - дата
     * @return stdClass $price - цена и отстъпка на продукта
     */
    public function getPrice($customerClass, $customerId, $data, $packagingId = NULL, $quantity = 1, $datetime = NULL)
    {
    	if($data){
    		if(is_string($data)){
    			$data = unserialize($data);
    		}
    		
    		if($data->price){
    			$price = new stdClass();
    			if($data->price){
    				$price->price = $data->price;
    			}
    			if($data->discount){
    				$price->discount = $data->discount;
    			}
    			
    			if($price->price) {
    				$minCharge =  salecond_Parameters::getParameter($customerClass, $customerId, 'minSurplusCharge');
    				$maxCharge = salecond_Parameters::getParameter($customerClass, $customerId, 'maxSurplusCharge');
    				
    				if(empty($data->bTaxes)) {
    					$data->bTaxes = 0;
    				}
    				
    				$calcPrice = ($data->bTaxes * (1 + $maxCharge / 100) 
    					+ $quantity * $data->price * (1 + $minCharge / 100)) / $quantity;
    				$price->price = currency_CurrencyRates::convertAmount($calcPrice, NULL, $data->currencyId, NULL);
    				return $price;
    			}
    		}
    	}
    	
    	return FALSE;
    }
    
    
	/**
     * Връща вербалното представяне на даденото изделие (HTML, може с картинка)
     * @param stdClass $data - Обект с данни от модела
     * @param boolean $short - Дали да е кратко представянето 
     * @return core_ET $tpl - вербално представяне на изделието
     */
    public function getVerbal($data, $short = FALSE)
    {
        expect($data = unserialize($data));
        $row = $this->toVerbal($data);
        
        if($short){
    		if($data->image){
    			$size = array(130, 130);
    			$file = fileman_Files::fetchByFh($data->image);
    			$row->image = thumbnail_Thumbnail::getImg($file->fileHnd, $size);
    		}
    	} else {
    		if($data->image){
    			$size = array(200, 350);
	    		$Fancybox = cls::get('fancybox_Fancybox');
				$row->image = $Fancybox->getImage($data->image, $size, array(550, 550));
    		}
    		
    		if(techno_Specifications::haveRightFor('configure', $data->specificationId) && !Mode::is('printing')){
    			$img = sbf('img/16/add.png');
    			$addUrl = array($this, 'configure', $data->specificationId, 'ret_url' => TRUE);
	    		$addBtn = ht::createLink(' ', $addUrl, NULL, array('style' => "background-image:url({$img});display:inline-block;height:16px;", 'class' => 'linkWithIcon')); 
    		}
	    }
    	
    	$tpl = $this->getTpl($row, $data->params, $short);
    	if($addBtn){
    		$tpl->replace($addBtn, 'addBtn');
    	}
    	
        $tpl->push('techno/tpl/GeneralProductsStyles.css', 'CSS');
        return $tpl;
    }
    
    
    /**
     * Връща шаблона с добавени плейсхолдъри за параметрите
     * @param stdClass $row - Вербален запис
     * @param array $params - параметрите на продукта
     * @param bool $short - дали изгледа е кратак 
     * @return core_ET $tpl - шаблон за показване
     */
    private function getTpl($row, $params = array(), $short)
    {
    	$tpl = (!$short) ? getTplFromFile($this->singleLayoutFile) : getTplFromFile($this->singleShortLayoutFile);
    	if(count($row->params)){
    		$paramBlock = $tpl->getBlock('PARAMS');
    		foreach($row->params as $id => $arr){
    			$blockCl = clone($paramBlock);
    			$blockCl->replace($arr['paramId'], 'paramId');
    			$blockCl->replace($arr['paramValue'], 'paramValue');
    			if(!$short){
    				$blockCl->replace($arr['tools'], 'tools');
    			}
    			$blockCl->removeBlocks();
    			$tpl->append($blockCl, 'PARAMS');
    		}
    	}
    	$tpl->placeObject($row);
    	
    	return $tpl;
    }
    
    
    /**
     * Помощна функция за привеждането на записа в вербален вид
     * @param stdClass $data - не сериализирания запис
     * @return stdClass $row - вербалното представяне на данните
     */
    private function toVerbal($data)
    {
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	
    	// Преобразуваме записа във вербален вид
    	$row = new stdClass();
        $fields = $this->getEditForm($data)->selectFields("");
    	foreach($fields as $name => $fld){
    		if($name == 'image') continue;
    		$row->$name = $fld->type->toVerbal($data->$name);
    	}
    	
    	if($data->params){
    		foreach($data->params as $paramId => $value){
    			$arr['paramId'] = cat_Params::getTitleById($paramId);
    			$arr['paramValue'] = (is_numeric($value)) ? $double->toVerbal($value) : $varchar->toVerbal($value);
    			$suffix = $varchar->toVerbal(cat_Params::fetchField($paramId, 'suffix'));
    			$arr['paramValue'] .= " &nbsp;{$suffix}";
    			$arr['tools'] = $this->getParamTools($paramId, $data->specificationId);
        		$row->params[$paramId] = $arr;
    		}
    	}
    	return $row;
    }
    
    
    /**
     * Създаване на туулбара на параметрите
     * @param int $paramId - ид на параметър
     * @param int $specificationId - ид на спецификация
     * @return core_ET $tpl - туулбара за редакция
     */
    private function getParamTools($paramId, $specificationId)
    {
    	if(techno_Specifications::haveRightFor('configure', $specificationId) && !Mode::is('printing')) {
    		
	        $editImg = "<img src=" . sbf('img/16/edit-icon.png') . " alt=\"" . tr('Редакция') . "\">";
			$deleteImg = "<img src=" . sbf('img/16/delete-icon.png') . " alt=\"" . tr('Изтриване') . "\">";
	        
			$editUrl = array($this, 'configure', $specificationId, 'edit' => $paramId,'ret_url' => TRUE);
	        $deleteUrl = array($this, 'configure', $specificationId, 'delete' => $paramId,'ret_url' => TRUE);

	        $editLink = ht::createLink($editImg, $editUrl, NULL, "id=edtS{$paramId}");
	        $deleteLink = ht::createLink($deleteImg, $deleteUrl, tr('Наистина ли желаете параметърът да бъде изтрит?'), "id=delS{$paramId}");
    		
	        $tpl = new ET($editLink . " " . $deleteLink);
    	}
    	
        return $tpl;
    }
    
    
    /**
     * Информация за продукта
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return stdClass $rec
     */
    public function getProductInfo($data, $packagingId = NULL)
    {
    	$data = unserialize($data);
	    $res = new stdClass();
	    $res->productRec = $data;
	    if(!$packagingId) {
	    	$res->packagings = array();
	    } else {
	    	return NULL;
	    }
	    	
	    return $res;
    }
    
    
    /**
     * Връща ддс-то на продукта
     * @param int $id - ид на продукт
     * @param datetime $date - към дата
     */
    public function getVat($data, $date = NULL)
    {
    	if(empty($data)) return NULL;
    	if(is_string($data)){
    		$data = unserialize($data);
    	}
    	
    	$vatId = cat_Params::fetchIdBySysId('vat');
    	if($vat = $data->params[$vatId]){
    		return $vat / 100;
    	}
    	
    	// Връщаме ДДС-то от периода
    	$period = acc_Periods::fetchByDate($date);
    	return $period->vatRate;
    }
    
    
    /**
     * Екшън за добавяне, изтриване и редактиране на параметри
     */
    function act_Configure()
    {
    	$Specifications = cls::get('techno_Specifications');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $Specifications->fetch($id));
    	$Specifications->requireRightFor('configure', $rec);
    	$data = unserialize($rec->data);
    	
    	if($paramId = Request::get('delete')){
    		unset($data->params[$paramId]);
    		$rec->data = $this->serialize($data);
	        $Specifications->save($rec);
	        return followRetUrl();
    	}
    	
    	$form = $this->getAddParamForm($data);
        $fRec = $form->input();
        if($form->isSubmitted()) {
        	if($Specifications->haveRightFor('configure', $rec)){
        		
        		// Проверка дали въведените стойности за правилни
        		cat_products_Params::isValueValid($form);
        		if(!$form->gotErrors()){
        			
        			// Записваме въведените данни в пропъртито data на река
		            $data->params[$fRec->paramId] = $fRec->paramValue;
	        		$rec->data = $this->serialize($data);
		            $Specifications->save($rec);
		            return  Redirect(array($Specifications, 'single', $rec->id));
        		}
        	}
        }
        
    	if($paramId = Request::get('edit')){
        	$form->rec->paramValue = $data->params[$paramId];
        	$form->rec->paramId = $paramId;	
    		$form->setReadOnly('paramId');
    		$action = tr('Редактиране');
    	} else {
    		$paramOptions = $this->getRemainingOptions($data);
    		$form->setOptions('paramId', $paramOptions);
    		$action = tr('Дoбавяне');
    	}
        
        $form->title = "{$action} на параметри към |*" . $Specifications->recToVerbal($rec, 'id,title,-list')->title;
    	return $Specifications->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Помага за генериране на последваща оферта
     * Връща масив от вида [име_на_поле] => [количество]
     * Първия елемент е задължително [currencyId] - валута в която
     * е цената на артикула
     * За всяка една от тези стойностти в генерираната оферта
     * се добавя по един ред
     * @return array
     */
    public function getFollowingQuoteInfo($data)
    {
    	$data = unserialize($data);
    	$array = array('currencyId' => $data->currencyId);
    	foreach(range(1, 3) as $n){
	    	if($q = $data->{"quantity{$n}"}){
	    		$array["quantity{$n}"] = $q;
	    	}
    	}
    	
    	return $array;
    }
}