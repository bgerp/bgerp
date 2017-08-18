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
    public $listFields = 'itemId,classId,value';
    
    
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
    	$this->FLD('classId', 'class(interface=batch_BatchTypeIntf,select=title)', 'caption=Клас,mandatory');
    	$this->FLD('value', 'varchar(128)', 'mandatory,caption=Стойност');
    	
    	$this->setDbUnique('itemId,classId,value');
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
    	$itemRec = batch_Items::fetch($itemId);
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
    		$obj = (object)array('itemId' => $itemRec->id, 'classId' => $featObj->classId, 'value' => $featObj->value);
    		
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
}