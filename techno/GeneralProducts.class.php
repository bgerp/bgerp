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
    var $title = "Универсален продукт";
    
    
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
		$form->FNC('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input,mandatory');
    	$form->FNC('image', 'fileman_FileType(bucket=techno_GeneralProductsImages)', 'caption=Параметри->Изображение,input');
		$form->FNC('code', 'varchar(64)', 'caption=Параметри->Код,remember=info,width=15em,input');
        $form->FNC('eanCode', 'gs1_TypeEan', 'input,caption=Параметри->EAN,width=15em,input');
		$form->FNC('meta', 'set(canSell=Продаваем,
        						canBuy=Купуваем,
        						canStore=Складируем,
        						canConvert=Вложим,
        						fixedAsset=Дма,
        						canManifacture=Производим)', 'caption=Свойства->Списък,input,columns=2');
        if($data->data){
        	
        	// При вече въведени характеристики, слагаме ги за дефолт
        	$form->rec = unserialize($data->data);
        }
        
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
     * Връща информация за ед цена на продукта, отстъпката и таксите
     * @param stdClass $data - дата от модела
     * @param int $packagingId - ид на опаковка
     * @param double quantity - количество
     * @param datetime $datetime - дата
     * @return stdClass $priceInfo - информация за цената на продукта
     * 				[price]- начална цена
     * 				[discount]  - отстъпка
     * 				[tax]     - нач. такса
     */
    public function getPrice($data, $packagingId = NULL, $quantity = 1, $datetime = NULL)
    {
    	$data = unserialize($data);
    	$obj = new stdClass();
    	$obj->price = $obj->tax = 0;
    	if(count($data->components->recs)){
    		foreach ($data->components->recs as $comp){
    			$obj->price += $comp->amount;
    			$obj->tax += $comp->bTaxes;
    		}
    	}
    		
    	return $obj;
    }
    
    
	/**
     * Връща вербалното представяне на даденото изделие (HTML, може с картинка)
     * @param stdClass $data - Обект с данни от модела
     * @param int $sId - ид на спецификацията
     * @param string $state - състояние на спецификацията
     * @param boolean $short - Дали да е кратко представянето 
     * @return core_ET $tpl - вербално представяне на изделието
     */
    public function getVerbal($data, $sId, $state, $short = FALSE)
    {
        expect($data = unserialize($data));
        $row = $this->toVerbal($data, $sId);
        
        if($short){
    		if($data->image){
    			$size = array(130, 130);
    			$file = fileman_Files::fetchByFh($data->image);
    			$row->image = thumbnail_Thumbnail::getImg($file->fileHnd, $size);
    		}
    		
	        // Добавяне на линк за сингъла на спецификацията
	    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && techno_Specifications::haveRightFor('read', $sId)){
	    		$row->title = ht::createLinkRef($row->title, array('techno_Specifications', 'single', $sId), NULL, 'title=Към спецификацията');
    		}
    	} else {
    		if($data->image){
    			$size = array(200, 350);
	    		$Fancybox = cls::get('fancybox_Fancybox');
				$row->image = $Fancybox->getImage($data->image, $size, array(550, 550));
    		}
    		
    		if(techno_Specifications::haveRightFor('configure', $sId) && !Mode::is('printing')){
    			$img = sbf('img/16/add.png', '');
    			
    			if(count(techno_Parameters::getRemainingOptions($data->components->recs))) {
	    			$addUrl = array('techno_Parameters', 'configure', $sId, 'ret_url' => TRUE);
		    		$addBtn = ht::createLink(' ', $addUrl, NULL, array('style' => "background-image:url({$img});", 'class' => 'spec-add-btn', 'title' => 'Добавяне на нов параметър')); 
    			}
    			
    			if(count(techno_Components::getRemainingOptions($data->components->recs))){
	    			$compUrl = array('techno_Components', 'configure', $sId, 'ret_url' => TRUE);
	    			$compBtn = ht::createLink(' ', $compUrl, NULL, array('style' => "background-image:url({$img});", 'class' => 'spec-add-btn', 'title' => 'Добавяне на нов компонент')); 
	    		}
	    	}
	    }
    	
	    $tpl = $this->getTpl($row, $data->params, $state, $short);
	    $tpl->replace($addBtn, 'addBtn');
    	$tpl->replace($compBtn, 'addBtnComp');
        $tpl->push('techno/tpl/GeneralProductsStyles.css', 'CSS');
        
        return $tpl;
    }
    
    
    /**
     * Връща шаблона с добавени плейсхолдъри за параметрите
     * @param stdClass $row - Вербален запис
     * @param array $params - параметрите на продукта
     * @param string $state - състояние на спецификацията
     * @param bool $short - дали изгледа е кратак 
     * @return core_ET $tpl - шаблон за показване
     */
    private function getTpl($row, $params = array(), $state, $short)
    {
    	$tpl = (!$short) ? getTplFromFile($this->singleLayoutFile) : getTplFromFile($this->singleShortLayoutFile);
    	techno_Parameters::renderParameters($row->params, $tpl, $short);
    	$tpl->append(techno_Components::renderComponents($row->components, $state, $short), 'COMPONENTS');
    	
    	$tpl->placeObject($row);
    	
    	return $tpl;
    }
    
    
    /**
     * Помощна функция за привеждането на записа в вербален вид
     * @param stdClass $data - не сериализирания запис
     * @param int $sId - ид на спецификацията
     * @return stdClass $row - вербалното представяне на данните
     */
    private function toVerbal($data, $sId)
    {
    	// Преобразуваме записа във вербален вид
    	$row = new stdClass();
        $fields = $this->getEditForm($data)->selectFields("");
    	foreach($fields as $name => $fld){
    		if($name == 'image') continue;
    		$row->$name = $fld->type->toVerbal($data->$name);
    	}
    	
    	// Вербално представяне на параметрите
    	techno_Parameters::getVerbal($data->params, $sId, $row->params);
    	
    	// Вербално представяне на компоненти, ако има
    	techno_Components::getVerbal($data->components->recs, $sId, $row->components);
    	
    	return $row;
    }
    
    
    /**
     * Информация за артикула
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return stdClass $rec
     */
    public function getProductInfo($data, $packagingId = NULL)
    {
    	$data = unserialize($data);
	    $res = new stdClass();
	    $res->productRec = $data;
	    
	    if($data->meta){
	    	$meta = explode(',', $data->meta);
	    	$newArr = array();
	    	foreach($meta as $value){
	    		$res->meta[$value] = TRUE;
	    	}
	    } else {
	    	$res->meta = FALSE;
	    }
	    
	    (!$packagingId) ? $res->packagings = array() : $res = NULL;
	   	return $res;
    }
    
    
    /**
     * Връща ддс-то на продукта
     * @param int $data - сериализираната информация от драйвъра
     * @param datetime $date - към дата
     */
    public function getVat($data, $date = NULL)
    {
    	$data = unserialize($data);
    	$vatId = cat_Params::fetchIdBySysId('vat');
    	if($vat = $data->params[$vatId]){
    		return $vat;
    	}
    	
    	// Връщаме ДДС-то от периода
    	$period = acc_Periods::fetchByDate($date);
    	return $period->vatRate;
    }
    
    
	/**
	 * Връща масив от използваните документи в даден документ (като цитат или
     * са включени в детайлите му)
     * @param int $data - сериализираната дата от документа
     * @return param $res - масив с използваните документи
     * 					[class] - инстанция на документа
     * 					[id] - ид на документа
     */
    function getUsedDocs($data)
    {
    	$data = unserialize($data);
    	if($usedDocs = doc_RichTextPlg::getAttachedDocs($data->description)) {
	    	foreach ($usedDocs as $doc){
	    		$res[] = (object)array('class' => $doc['mvc'], 'id' => $doc['rec']->id);
	    	}
    	} else {
    		$res = array();
    	}
    	
    	return $res;
    }
}