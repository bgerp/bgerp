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
    var $listFields = 'tools=Пулт,name,measureId';
    
    
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
    //var $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * 
     */
    var $canSingle = 'admin, techno';
	
	
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('name', 'varchar', 'caption=Наименование, mandatory,remember=info,width=100%');
		$this->FLD('description', 'richtext(rows=5)', 'caption=Описание');
		$this->FLD('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory');
		$this->FLD('image', 'fileman_FileType(bucket=techno_GeneralProductsImages)', 'caption=Изображение');
    	
		$this->FLD('price', 'double(decimals=2)', 'caption=Параметри->Цена,width=8em,mandatory');
    	$this->FLD('height', 'double(decimals=2)', 'caption=Параметри->Височина,width=8em');
		$this->FLD('width', 'double(decimals=2)', 'caption=Параметри->Ширина,width=8em');
		$this->FLD('weight', 'double(decimals=2)', 'caption=Параметри->Тегло,width=8em');
		$this->FLD('volume', 'double(decimals=2)', 'caption=Параметри->Обем,width=8em');
		$this->FLD('length', 'double(decimals=2)', 'caption=Параметри->Дължина,width=8em');
		
		$this->FLD('code', 'varchar(64)', 'caption=Параметри->Код,remember=info,width=15em');
        $this->FLD('eanCode', 'gs1_TypeEan', 'input,caption=Параметри->EAN,width=15em');
    }
    
    
    /*
     * РЕАЛИЗАЦИЯ НА techno_ProductsIntf
     */
    
    /**
     * Връща форма, с която могат да се въвеждат параметри на
     * определен клас не-стандартно изделие
     * @param stdClass $data - Обект с данни от модела 
     * @return core_Form $form - Формата на мениджъра
     */
    public function getEditForm($data)
    {
        $this->prepareEditForm($data);
        $this->prepareEditToolbar($data);
    	return $data->form->renderHtml();
    }
    
    
	/**
     * Връща сериализиран вариант на данните, които представят
     * дадено изделие или услуга
     * @param stdClass $data - Обект с данни от модела 
     * @return blob $serialized - сериализирани данни на обекта
     */
    public function serialize($data)
    {
        //@TODO
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
        //@TODO
    }
}