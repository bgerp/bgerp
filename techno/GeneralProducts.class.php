<?php



/**
 * Технологичен клас за въвеждане на нестандартни продукти
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
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    }
    
    
    /*
     * РЕАЛИЗАЦИЯ НА techno_ProductsIntf
     */
    
    
    /**
     * Връща форма, с която могат да се въвеждат параметри на
     * определен клас нестандартно изделие
     * @param stdClass $data - Обект с данни от модела 
     * @return core_Form $form - Формата на мениджъра
     */
    public function getEditForm()
    {
    	$form = cls::get('core_Form');
    	$form->FNC('title', 'varchar', 'caption=Заглавие, mandatory,remember=info,width=100%,input');
    	$form->FNC('description', 'richtext(rows=5)', 'caption=Описание,input,mandatory,width=100%');
		$form->FNC('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input');
    	$form->FNC('price', 'double(decimals=2)', 'caption=Цени->Ед. цена,width=8em,mandatory,input,unit=без ддс');
		$form->FNC('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Цени->Валута,width=8em,input');
    	$form->FNC('discount', 'percent(decimals=2)', 'caption=Цени->Отстъпка,width=8em,input,unit=%');
		$form->FNC('image', 'fileman_FileType(bucket=techno_GeneralProductsImages)', 'caption=Параметри->Изображение,input');
		$form->FNC('code', 'varchar(64)', 'caption=Параметри->Код,remember=info,width=15em,input');
        $form->FNC('eanCode', 'gs1_TypeEan', 'input,caption=Параметри->EAN,width=15em,input');
        $form->FNC('quantity1', 'int', 'caption=Ценови преглед->К-во 1,mandatory,width=4em,input');
    	$form->FNC('quantity2', 'int', 'caption=Ценови преглед->К-во 2,width=4em,input');
    	$form->FNC('quantity3', 'int', 'caption=Ценови преглед->К-во 3,width=4em,input');
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
        $form->setDefault('quantity1', '1');
        
        return $form;
    }
    
    
    /**
     * 
     * Enter description here ...
     */
    public function getAddParamForm()
    {
    	$form = cls::get('core_Form');
    	$form->FLD('paramId', 'key(mvc=cat_Params,select=name)', 'input,caption=Параметър,mandatory');
        $form->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност,mandatory');
    	
        return $form;
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
     * Изчислява цената на продукта
     * @param string $data
     * @param id $packagingId
     * @param int $quantity
     * @param datetime $datetime
     */
    public function getPrice($data, $packagingId = NULL, $quantity = NULL, $datetime = NULL)
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
    				$price->price = currency_CurrencyRates::convertAmount($price->price, NULL, $data->currencyId, NULL);
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
    		$img = sbf('img/16/add.png');
    		if(techno_Specifications::haveRightFor('edit', $data->specificationId)){
    			$addUrl = array('techno_Specifications', 'configure', $data->specificationId, 'ret_url' => TRUE);
	    		$addBtn = ht::createLink(' ', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addParams')); 
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
    			$blockCl->replace($arr['tools'], 'tools');
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
    	// Преобразуваме записа във вербален вид
    	$row = new stdClass();
        $fields = $this->getEditForm()->selectFields("");
    	foreach($fields as $name => $fld){
    		if($name == 'image') continue;
    		$row->$name = $fld->type->toVerbal($data->$name);
    	}
    	
    	if($data->params){
    		$fields = $this->getAddParamForm()->selectFields("");
    		foreach($data->params as $paramId => $value){
    			$arr['paramId'] = $fields['paramId']->type->toVerbal($paramId);
    			$arr['paramValue'] = $fields['paramValue']->type->toVerbal($value);
    			$suffix = $fields['paramValue']->type->toVerbal(cat_Params::fetchField($paramId, 'suffix'));
    			$arr['paramValue'] .= " &nbsp;{$suffix}";
    			$arr['tools'] = $this->getParamTools($paramId, $data->specificationId);
        		$row->params[$paramId] = $arr;
    		}
    	}
    	return $row;
    }
    
    
    /**
     * 
     * @param unknown_type $paramId
     * @param unknown_type $specificationId
     */
    private function getParamTools($paramId, $specificationId)
    {
    	if(techno_Specifications::haveRightFor('edit', $specificationId)) {
    		$editUrl = array('techno_Specifications', 'configure', $specificationId, 'edit' => $paramId,'ret_url' => TRUE);
	        $editImg = "<img src=" . sbf('img/16/edit-icon.png') . " alt=\"" . tr('Редакция') . "\">";
			$editLink = ht::createLink($editImg, $editUrl, NULL, "id=edt{$rec->id}");
	        $deleteImg = "<img src=" . sbf('img/16/delete-icon.png') . " alt=\"" . tr('Изтриване') . "\">";
	        $deleteUrl = array('techno_Specifications', 'configure', $specificationId, 'delete' => $paramId,'ret_url' => TRUE);
	        $deleteLink = ht::createLink($deleteImg, $deleteUrl,tr('Наистина ли желаете параметърът да бъде изтрит?'), "id=del{$rec->id}");
    		$tpl = new ET($editLink . " " . $deleteLink);
    	}
    	
        return $tpl;
    }
    
    
    /**
     * Информация за продукта
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     */
    public function getProductInfo($data, $packagingId = NULL)
    {
    	expect($data);
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
    		return $vat;
    	}
    	
    	// Връщаме ДДС-то от периода
    	$period = acc_Periods::fetchByDate($date);
    	return $period->vatRate;
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $data
     */
    public function preparePricePreview($data)
    {
    	$double = cls::get('type_Double');
	    $double->params['decimals'] = 2;
	    $int = cls::get('type_Int');
	    
	    $row = new stdClass();	
    	$data = unserialize($data);
    	$shortMeasureId = cat_UoM::fetchField($data->measureId, 'shortName');
    	$vat = $this->getVat($data);
    	
    	foreach(range(1,3) as $num){
		    $quantity = "quantity{$num}";
			 $priceFld = "price{$num}";
			 $discountFld = "discount{$num}";
		     if($data->$quantity){
		    	$priceRec = $this->getPrice($data, NULL, $data->$quantity);
			    $priceRec->price = currency_CurrencyRates::convertAmount($priceRec->price, NULL, NULL, $data->currencyId);
			    $price = $priceRec->price + ($priceRec->price * $vat);
			    $price = $data->$quantity * $price;
			    $row->$quantity = $int->toVerbal($data->$quantity);
			    $row->$discountFld = $double->toVerbal($price - ($price * $priceRec->discount));
			    $row->$priceFld = $double->toVerbal($price);
			    $row->$discountFld .= " &nbsp;<span class='cCode'>{$data->currencyId}</span>";
			    $row->$priceFld .= " &nbsp;<span class='cCode'>{$data->currencyId}</span>";
			    $row->$quantity .= " {$shortMeasureId}";
		    }
		}
		    
		return $row;
    }
    
    /*
    public function addParam(&$data, $fRec)
    {
    	$data->params[$fRec->paramId] = $fRec->paramValue;
    }
    
	public function deleteParam(&$data, $paramId)
    {
    	unset($data->params[$paramId]);
    }*/
}