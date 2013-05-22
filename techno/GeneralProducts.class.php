<?php



/**
 * Нестандартни продукти
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
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
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт,name,measureId,createdOn,createdBy';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin,techno';
    
    
    /**
     * Шаблон за показване на кратката версия на изделието
     */
    var $singleShortLayoutFile = 'techno/tpl/SingleLayoutGeneralProductsShort.shtml';
    
    
    /**
     * Шаблон за показване на нормалната версия на изделието
     */
    var $singleLayoutFile = 'techno/tpl/SingleLayoutGeneralProducts.shtml';
    
    
    /*
     * РЕАЛИЗАЦИЯ НА techno_ProductsIntf
     */
    
    
    /**
     * Връща форма, с която могат да се въвеждат параметри на
     * определен клас не-стандартно изделие
     * @param stdClass $data - Обект с данни от модела 
     * @return core_Form $form - Формата на мениджъра
     */
    public function getEditForm()
    {
    	$form = cls::get('core_Form');
    	$form->FNC('title', 'richtext(rows=5)', 'caption=Описание,input=hidden');
    	$form->FNC('description', 'richtext(rows=5)', 'caption=Описание,input,mandatory');
		$form->FNC('price', 'double(decimals=2)', 'caption=Цени->Ед. цена,width=8em,mandatory,input');
		$form->FNC('discount', 'double(decimals=2)', 'caption=Цени->Отстъпка,width=8em,input');
		$form->FNC('vat', 'percent(decimals=2)', 'caption=Цени->ДДС,width=8em,input');
    	$form->FNC('image', 'fileman_FileType(bucket=techno_GeneralProductsImages)', 'caption=Параметри->Изображение,input');
		$form->FNC('material', 'varchar(150)', 'caption=Параметри->Материал,width=8em,input');
    	$form->FNC('height', 'double(decimals=2)', 'caption=Параметри->Височина,width=8em,input');
		$form->FNC('width', 'double(decimals=2)', 'caption=Параметри->Ширина,width=8em,input');
		$form->FNC('weight', 'double(decimals=2)', 'caption=Параметри->Тегло,width=8em,input');
		$form->FNC('thickness', 'double(decimals=2)', 'caption=Параметри->Дебелина,width=8em,input');
		$form->FNC('volume', 'double(decimals=2)', 'caption=Параметри->Обем,width=8em,input');
		$form->FNC('length', 'double(decimals=2)', 'caption=Параметри->Дължина,width=8em,input');
		$form->FNC('color', 'varchar(150)', 'caption=Други->Цвят,width=8em,input');
		$form->FNC('code', 'varchar(64)', 'caption=Други->Код,remember=info,width=15em,input');
        $form->FNC('eanCode', 'gs1_TypeEan', 'input,caption=Други->EAN,width=15em,input');
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', getRetUrl(), array('class' => 'btn-cancel'));
        
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
     * Връща вербалното представяне на даденото изделие (HTML, може с картинка)
     * @param stdClass $data - Обект с данни от модела
     * @param boolean $short - Дали да е кратко представянето 
     * @return core_ET $tpl - вербално представяне на изделието
     */
    public function getVerbal($data, $short = FALSE)
    {
        $data = unserialize($data);
        $row = new stdClass();
    	
        // Преобразуваме записа във вербален вид
    	$fields = $this->getEditForm()->selectFields("");
    	foreach($fields as $name => $fld){
    		$row->$name = $fld->type->toVerbal($data->$name);
    	}
        
    	// Обработваме изображението в thumbnail
    	if($data->image){
    		$file = fileman_Files::fetchByFh($data->image);
	        $row->image = thumbnail_Thumbnail::getImg($file->fileHnd, array(150));
    	}
    	
    	// Спрямо $short взимаме шаблона за кратко или дълго представяне
    	($short) ? $file = $this->singleShortLayoutFile: $file = $this->singleLayoutFile;
        $tpl = getTplFromFile($file);
        $tpl->placeObject($row);
        
        $tpl->push('techno/tpl/GeneralProductsStyles.css', 'CSS');
        return $tpl;
    }
}