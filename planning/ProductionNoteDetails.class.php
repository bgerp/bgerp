<?php


/**
 * Клас 'planning_ProductionNormDetails'
 *
 * Детайли на мениджър на детайлите на протокола за производство
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ProductionNoteDetails extends deals_ManifactureDetail
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_ProductionNoteDetails';
	
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола от производство';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_RowNumbering, plg_AlignDecimals2,plg_PrevAndNext';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, jobId, bomId, packagingId, packQuantity';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Производство';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Какви продукти да могат да се избират в детайла
     *
     * @var enum(canManifacture=Производими,canConvert=Вложими)
     */
    protected $defaultMeta = 'canManifacture';
    
    
    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_ProductionNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
        
        $this->FLD('jobId', 'key(mvc=planning_Jobs)', 'input=none,caption=Задание');
        $this->FLD('bomId', 'key(mvc=cat_Boms)', 'input=none,caption=Рецепта');
        
        $this->setDbUnique('noteId,productId');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	
    	// Да се показвали полети за себестойност
    	$showSelfvalue = TRUE;
    	
    	if($rec->productId){
    			
    		if($bomRec = cat_Products::getLastActiveBom($rec->productId, 'production')){
    			$rec->bomId = $bomRec->id;
    		} elseif($bomRec = cat_Products::getLastActiveBom($rec->productId, 'sales')){
    			$rec->bomId = $bomRec->id;
    		} else {
    			$rec->bomId = NULL;
    		}
    			
    		// Не показваме полето за себестойност ако има активна рецепта и задание
    		if(isset($rec->jobId) && isset($rec->bomId)){
    			$showSelfvalue = FALSE;
    		}
    	}
    	
    	if($form->isSubmitted()){
    		
    		if(empty($rec->jobId)){
    			$form->setError('productId', 'Артикулът няма задание');
    		}
    		
    		if(empty($rec->bomId)){
    			$form->setError('productId', 'Артикулът няма рецепта');
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(isset($rec->jobId)){
    		$row->jobId= planning_Jobs::getLink($rec->jobId, 0);
    	}
    	
    	if(isset($rec->bomId)){
    		$row->bomId = cat_Boms::getLink($rec->bomId, 0);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$res)
    {
    	$recs = &$res->recs;
    
    	$hasBomFld = $hasJobFld = FALSE;
    	
    	if (count($recs)) {
    		foreach ($recs as $id => $rec) {
    			$hasJobFld = !empty($rec->jobId) ? TRUE : $hasJobFld;
    			$hasBomFld = !empty($rec->bomId) ? TRUE : $hasBomFld;
    		}
    		 
    		if($hasJobFld === FALSE){
    			unset($res->listFields['jobId']);
    		}
    		
    		if($hasBomFld === FALSE){
    			unset($res->listFields['bomId']);
    		}
    	}
    }
}
