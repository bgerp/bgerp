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
    var $title = "Нестандартни продукти";
    
    
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
    	$form->FNC('title', 'varchar(184)', 'caption=Описание,input=hidden');
    	$form->FNC('description', 'richtext(rows=5)', 'caption=Описание,input,mandatory');
		$form->FNC('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input');
    	$form->FNC('price', 'double(decimals=2)', 'caption=Цени->Ед. цена,width=8em,mandatory,input,unit=без ддс');
		$form->FNC('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Цени->Валута,width=8em,input');
    	$form->FNC('discount', 'percent(decimals=2)', 'caption=Цени->Отстъпка,width=8em,input,unit=%');
		$form->FNC('vat', 'percent(decimals=2)', 'caption=Цени->ДДС,width=8em,input,unit=%');
    	$form->FNC('image', 'fileman_FileType(bucket=techno_GeneralProductsImages)', 'caption=Параметри->Изображение,input');
		$form->FNC('height', 'double(decimals=2)', 'caption=Параметри->Височина,width=8em,input');
		$form->FNC('width', 'double(decimals=2)', 'caption=Параметри->Ширина,width=8em,input');
		$form->FNC('weight', 'double(decimals=2)', 'caption=Параметри->Тегло,width=8em,input');
		$form->FNC('thickness', 'double(decimals=2)', 'caption=Параметри->Дебелина,width=8em,input');
		$form->FNC('volume', 'double(decimals=2)', 'caption=Параметри->Обем,width=8em,input');
		$form->FNC('length', 'double(decimals=2)', 'caption=Параметри->Дължина,width=8em,input');
		$form->FNC('material', 'varchar(150)', 'caption=Други->Материал,width=8em,input');
		$form->FNC('color', 'varchar(150)', 'caption=Други->Цвят,width=8em,input');
		$form->FNC('code', 'varchar(64)', 'caption=Други->Код,remember=info,width=15em,input');
        $form->FNC('eanCode', 'gs1_TypeEan', 'input,caption=Други->EAN,width=15em,input');
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
        
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
    		$data = unserialize($data);
    		
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
        
        // Спрямо $short взимаме шаблона за кратко или дълго представяне
    	if($short){
    		$layout = $this->singleShortLayoutFile;
    		$size = array(130, 130);
    		if($data->image){
    			$file = fileman_Files::fetchByFh($data->image);
    			$row->image = thumbnail_Thumbnail::getImg($file->fileHnd, $size);
    		}
    	} else {
    		$layout = $this->singleLayoutFile;
    		$size = array(200, 350);
    		if($data->image){
	    		$Fancybox = cls::get('fancybox_Fancybox');
				$row->image = $Fancybox->getImage($data->image, $size, array(550, 550));
    		}
    	}
    	
        $tpl = getTplFromFile($layout);
        $tpl->push('techno/tpl/GeneralProductsStyles.css', 'CSS');
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
    		if(is_numeric($data->$name)){
    			$amount = floatval($data->$name);
    			$parts = explode('.', $amount);
    			$fld->type->params['decimals'] = count($parts[1]);
    			$row->$name = $data->$name;
    		}
    		$row->$name = $fld->type->toVerbal($data->$name);
    	}
    	return $row;
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
    	
    	$data = unserialize($data);
    	if($data->vat) return $data->vat;
    	
    	// Връщаме ДДС-то от периода
    	$period = acc_Periods::fetchByDate($date);
    	return $period->vatRate;
    }
}