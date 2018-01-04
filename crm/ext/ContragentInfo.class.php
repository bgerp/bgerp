<?php


/**
 * Информация за контрагенти
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     0.12
 */
class crm_ext_ContragentInfo extends core_manager
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'epbags_CustomerSince';
	
	
	/**
     * Заглавие
     */
    public $title = 'Информация за контрагенти';

    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Информация за контрагента';
    
    
    /**
     * Кой може да редактира
     */
    public $canWrite = 'no_one';


    /**
     * Кой може да редактира
     */
    public $canList = 'debug';  
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'contragentId=Контрагент,customerSince=Първо задание';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
    	$this->FLD('contragentClassId', 'int', 'mandatory');
    	$this->FLD('contragentId', 'int', 'mandatory,tdClass=leftCol');
    	$this->FLD('customerSince', 'date');
    	
    	$this->setDbUnique('contragentClassId,contragentId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	try{
    		$row->contragentId = cls::get($rec->contragentClassId)->getHyperlink($rec->contragentId, TRUE);
    	} catch(core_exception_Expect $e){
    		$row->contragentId = "<span class='red'>" . tr('Проблем с показването') . "</span>";
    	}
    }
}