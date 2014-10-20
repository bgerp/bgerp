<?php
/**
 * Клас 'acc_VatGroups'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_VatGroups extends core_Manager
{
    /**
     * Заглавие в множествено число
     * 
     * @var string
     */
    public $title = 'Данъчни групи';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'acc_WrapperSettings,plg_RowTools,plg_Created,plg_Modified,plg_State2';
    
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'acc,ceo';
    
    
    /**
     * Активен таб на менюто
     *
     * @var string
     */
    public $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'acc,ceo';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'acc,ceo';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'acc,ceo';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,title,countryId,vat,createdOn,createdBy,modifiedOn,modifiedBy,state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     * 
     * @var string
     * @see plg_RowTools
     */
    public $rowToolsField = 'tools';
    

    /**
     * Заглавие в единствено число
     * 
     * @var string
     */
    public $singleTitle = 'Данъчна група';
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    function description()
    {
    	// Информация за нишката
    	$this->FLD('title', 'varchar(3)', 'caption=Заглавие,mandatory');
    	$this->FLD('countryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,class=contactData,mandatory');
    	$this->FLD('vat', 'percent', 'caption=ДДС,mandatory');
    	$this->FLD('sysId', 'varchar(3)', 'input=none');
    	
    	// Уникален индекс
    	$this->setDbUnique('title,countryId');
    }	
   
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setSuggestions('vat', array('' => '') + arr::make('0 %,7 %,9 %,20 %', TRUE));
    	$conf = core_Packs::getConfig('crm');
    	
    	// По подразбиране е държавата на "Моята фирма"
    	$ownCountryId = drdata_Countries::getIdByName($conf->BGERP_OWN_COMPANY_COUNTRY);
    	$form->setDefault('countryId', $ownCountryId);
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	if (isset($rec->csv_country)){
    		$rec->countryId = drdata_Countries::fetchField("#commonName = '{$rec->csv_country}'", 'id');
    	}
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
    	// Подготвяме пътя до файла с данните
    	$file = "acc/csv/VatGroups.csv";
    
    	// Кои колонки ще вкарваме
    	$fields = array(
    			0 => "title",
    			1 => "csv_country",
    			2 => "vat",
    			3 => "sysId",
    	);
    
    	// Импортираме данните от CSV файла.
    	// Ако той не е променян - няма да се импортират повторно
    	$cntObj = csv_Lib::importOnce($this, $file, $fields, NULL, NULL);
    	
    	// Записваме в лога вербалното представяне на резултата от импортирането
    	$res .= $cntObj->html;
    
    	return $res;
    }
}