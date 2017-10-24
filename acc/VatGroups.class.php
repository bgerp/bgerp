<?php



/**
 * Клас 'acc_VatGroups'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
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
    public $loadList = 'acc_WrapperSettings,plg_RowTools2,plg_Created,plg_Modified,plg_State2';
    
    
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
    public $listFields = 'title,vat,createdOn,createdBy,modifiedOn,modifiedBy,state';
    

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
    	$this->FLD('vat', 'percent', 'caption=ДДС,mandatory');
    	$this->FLD('sysId', 'varchar(3)', 'caption=Систем ID, input=hidden');
    	
    	// Уникален индекс
    	$this->setDbUnique('title');
    }	
   
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setSuggestions('vat', array('' => '') + arr::make('0 %,7 %,9 %,20 %', TRUE));
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
    			1 => "vat",
    			2 => "sysId",
    	);
    
    	// Импортираме данните от CSV файла.
    	// Ако той не е променян - няма да се импортират повторно
    	$cntObj = csv_Lib::importOnce($this, $file, $fields, NULL, NULL);
    	
    	// Записваме в лога вербалното представяне на резултата от импортирането
    	$res = $cntObj->html;
    
    	return $res;
    }
    
    
    /**
     * Подготвя опциите за селектиране
     */
    public function makeArray4Select_($fields = NULL, $where = "", $index = 'id', $tpl = NULL)
    {
    	$query = self::getQuery();
    	$options = array();
    	while($rec = $query->fetch($where)){
    		$title = self::getVerbal($rec->id, 'title') . " - " . self::getVerbal($rec, 'vat');
    		$title = str_replace("&nbsp;", " ", $title);
    		
    		$options[$rec->{$index}] = $title;
    	}
    	
    	return $options;
    }
}