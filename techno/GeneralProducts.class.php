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
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_SaveAndNew, plg_PrevAndNext, plg_Rejected, plg_State,
                     techno_Wrapper, plg_Sorting, plg_Printing, plg_Select';

	
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Нестандартен продукт";
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/wooden-box.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт,name,measureId,createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin,techno';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'admin,techno';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'admin,techno,broker';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,techno,broker';
    
    
    /**
     * Кой може да го разгледа?
     */
    var $canList = 'admin,techno,broker';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,techno';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin,techno';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'SingleLayoutGeneralProducts.shtml';
    
    
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
		$form->FNC('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,input');
		$form->FNC('price', 'double(decimals=2)', 'caption=Параметри->Цена,width=8em,mandatory,input');
		$form->FNC('discount', 'double(decimals=2)', 'caption=Параметри->Отстъпка,width=8em,input');
    	$form->FNC('image', 'fileman_FileType(bucket=techno_GeneralProductsImages)', 'caption=Изображение,input');
		$form->FNC('material', 'varchar(150)', 'caption=Параметри->Материал,width=8em,input');
    	$form->FNC('height', 'double(decimals=2)', 'caption=Параметри->Височина,width=8em,input');
		$form->FNC('width', 'double(decimals=2)', 'caption=Параметри->Ширина,width=8em,input');
		$form->FNC('weight', 'double(decimals=2)', 'caption=Параметри->Тегло,width=8em,input');
		$form->FNC('volume', 'double(decimals=2)', 'caption=Параметри->Обем,width=8em,input');
		$form->FNC('length', 'double(decimals=2)', 'caption=Параметри->Дължина,width=8em,input');
		$form->FNC('color', 'varchar(150)', 'caption=Параметри->Цвят,width=8em,input');
		$form->FNC('code', 'varchar(64)', 'caption=Параметри->Код,remember=info,width=15em,input');
        $form->FNC('eanCode', 'gs1_TypeEan', 'input,caption=Параметри->EAN,width=15em,input');
        
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
     * 
     * @param stdClass $data - Обект с данни от модела
     * @param boolean $short - Дали да е кратко представянето 
     * @return text/html - вербално представяне на изделието
     */
    public function getVerbal($data, $short = FALSE)
    {
        $data = unserialize($data);
    	if($data->image){
    		$file = fileman_Files::fetchByFh($data->image);
	        $data->image = thumbnail_Thumbnail::getImg($file->fileHnd, array(150));
    		
    	}
        $tpl = getTplFromFile("techno/tpl/" . $this->singleLayoutFile);
        $tpl->placeObject($data);
        return $tpl;
    }
}