<?php



/**
 * Дефиниции на партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Defs extends embed_Manager {
    
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'batch_BatchTypeIntf';
	
	
    /**
     * Заглавие
     */
    public $title = 'Дефиниции на партиди';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, batch_Wrapper, plg_Modified';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'tools=Пулт,productId,driverClass=Тип,modifiedOn,modifiedBy';
    
    
    /**
     * Поле за показване на пулта за редакция
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Дефиниция на партидa";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'batch, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'batch,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'batch,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'batch, ceo';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,before=driverClass');
    
    	$this->setDbUnique('productId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	$storable = cat_Products::getByProperty('canStore');
    	$form->setOptions('productId', array('' => '') + $storable);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	$row->ROW_ATTR['class'] = 'state-active';
    }
    
    
    /**
     * Връща дефиницията на партидата за продукта, ако има
     * 
     * @param int $productId - ид на продукт
     * @return batch_drivers_Proto|FALSE $BatchClass - инстанцията на класа или FALSE ако няма
     */
    public static function getBatchDef($productId)
    {
    	// Намираме записа за артикула
    	$rec = self::fetch("#productId = '{$productId}'");
    	
    	// Опитваме се да върнем инстанцията
    	if(cls::load($rec->driverClass, TRUE)){
    		$BatchClass = cls::get($rec->driverClass);
    		$BatchClass->setRec($rec);
    		
    		return $BatchClass;
    	}
    	
    	// Ако не може да се намери
    	return FALSE;
    }
    
    
    /**
     * Добавя партидите към стринг
     * 
     * @param text $batch - партида или партиди
     * @param text $string - към кой стринг да се добавят
     * @return void
     */
    public static function appendBatch($batch, &$string = '')
    {
    	if(!empty($batch)){
    		$batch = explode("\n", str_replace("\r", '', $batch));
    		foreach ($batch as &$b){
    			$b = cls::get('type_Varchar')->toVerbal($b);
    			if(batch_Movements::haveRightFor('list')){
    				$b = ht::createLink($b, array('batch_Movements', 'list', 'batch' => $b))->getContent();
    			}
    		}
    		
    		$count = count($batch);
    		$batch = implode(', ', $batch);
    		$batch = "[html]{$batch}[/html]";
    		$string .= ($string) ? "\n" : '';
    		
    		$label = ($count == 1) ? 'lot' : 'serials';
    		$string .= "{$label}: {$batch}";
    	}
    }
}