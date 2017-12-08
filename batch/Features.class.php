<?php



/**
 * Свойства на партидите
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Features extends core_Manager {
    
	
    /**
     * Заглавие
     */
    public $title = 'Свойства на партидите';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'batch_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'itemId,name,classId,value';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Инвентаризация на партидност";
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('itemId', 'key(mvc=batch_Items)', 'mandatory,caption=Перо');
    	$this->FLD('name', 'varchar(128)', 'caption=Свойство,mandatory');
    	$this->FLD('classId', 'class(interface=batch_BatchTypeIntf,select=title)', 'caption=Клас,mandatory');
    	$this->FLD('value', 'varchar(128)', 'mandatory,caption=Стойност');
    	
    	$this->setDbUnique('itemId,name,value');
    }
    
    
    /**
     * Канонизира името на свойството
     * 
     * @param string $name
     * @return string
     */
    public static function canonize($name)
    {
    	return str::mbUcfirst($name);
    }
    
    
    /**
     * Синхронизиране на свойствата на перото
     * 
     * @param int $itemid - ид на перо
     * @return void
     */
    public static function sync($itemId)
    {
    	// Кое е перото и партидната дефиниция
    	$itemRec = batch_Items::fetchRec($itemId);
    	$Def = batch_Defs::getBatchDef($itemRec->productId);
    	if(!is_object($Def)) return;
    	
    	// Какви са свойствата
    	$features = $Def->getFeatures($itemRec->batch);
    	expect(is_array($features));
    	expect(count($features));
    	
    	// Подготовка на записите
    	$self = cls::get(get_called_class());
    	
    	$res = array();
    	foreach ($features as $class => $featObj){
    		$obj = (object)array('itemId'  => $itemRec->id, 
    							 'name'    => self::canonize($featObj->name), 
    							 'classId' => $featObj->classId,
    							 'value'   => $featObj->value,);
    		
    		if(!$self->isUnique($obj, $fields, $exRec)){
    			$obj->id = $exRec->id;
    		}
    		
    		$res[] = $obj;
    	}
    	
    	// Запис на свойствата
    	$self->saveArray($res);
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
    	$data->query->orderBy('id', "DESC");
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('admin,debug')){
    		$data->toolbar->addBtn('Синхронизиране', array($mvc, 'sync', 'ret_url' => TRUE), NULL, 'warning=Наистина ли искате да ресинхронизирате свойствата,ef_icon = img/16/arrow_refresh.png,title=Ресинхронизиране на свойствата на перата');
    	}
    }
    
    
    /**
     * Синхронизиране на таблицата със свойствата
     */
    public function act_Sync()
    {
    	requireRole('ceo,admin');
    	 
    	// Синхронизира всички свойства на перата
    	$this->syncAll();
    	 
    	// Записваме, че потребителя е разглеждал този списък
    	$this->logWrite("Синхронизиране на свойствата на партидите");
    	 
    	// Редирект към списъка на свойствата
    	return new Redirect(array($this, 'list'), 'Всички свойства са синхронизирани успешно');
    }
    
    
    /**
     * Обновяване на всички свойства
     * 
     * @return void
     */
    public static function syncAll()
    {
    	self::truncate();
    	 
    	$iQuery = batch_Items::getQuery();
    	while($iRec = $iQuery->fetch()){
    		try{
    			self::sync($iRec);
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
}