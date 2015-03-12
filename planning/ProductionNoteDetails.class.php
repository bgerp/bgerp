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
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_RowNumbering, plg_AlignDecimals';
    
    
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
    public $listFields = 'productId, jobId, bomId, measureId, quantity, selfValue, amount';
    
        
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
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_ProductionNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
        
        $this->FLD('jobId', 'key(mvc=planning_Jobs)', 'input=none,caption=Задание');
        $this->FLD('bomId', 'key(mvc=cat_Boms)', 'input=none,caption=Рецепта');
        
        $this->FLD('selfValue', 'double', 'caption=С-ст,input=hidden');
        $this->FNC('amount', 'double', 'caption=Сума');
        
        $this->setDbUnique('noteId,productId,classId');
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public static function on_CalcAmount($mvc, $rec)
    {
    	if(empty($rec->quantity) || empty($rec->selfValue)) return;
    	
    	$rec->amount = $rec->quantity * $rec->selfValue;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	
    	// Да се показвали полети за себестойност
    	$showSelfvalue = TRUE;
    	
    	if($rec->productId){
    		$ProductMan = cls::getClassName($rec->classId);
    		
    		// Имали активно задание за артикула ?
    		if($jobId = $ProductMan::getLastActiveJob($rec->productId)->id){
    			$rec->jobId = $jobId;
    		} else {
    			$rec->jobId = NULL;
    		}
    			
    		// Имали активна рецепта за артикула ?
    		if($bomRec = $ProductMan::getLastActiveBom($rec->productId)){
    			$rec->bomId = $bomRec->id;
    		} else {
    			$rec->bomId = NULL;
    		}
    			
    		// Не показваме полето за себестойност ако има активна рецепта и задание
    		if(isset($rec->jobId) && isset($rec->bomId)){
    			$showSelfvalue = FALSE;
    		}
    		
    		// Себестойността е във основната валута за периода
    		$masterValior = $mvc->Master->fetchField($form->rec->noteId, 'valior');
    		$form->setField('selfValue', "unit=" . acc_Periods::getBaseCurrencyCode($masterValior));
    		
    		// Скриваме полето за себестойност при нужда
    		if($showSelfvalue === FALSE){
    			$form->setField('selfValue', 'input=none');
    		} else {
    			$form->setField('selfValue', 'input,mandatory');
    		}
    	}
    	
    	if($form->isSubmitted()){
    		
    		// Ако трябва да показваме с-та, но не е попълнена сетваме грешка
    		if(empty($rec->selfValue) && $showSelfvalue === TRUE){
    			$form->setError('selfValue', 'Непопълнено задължително поле|* <b>С-ст</b>');
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
    		$row->jobId = "#" . cls::get('planning_Jobs')->getHandle($rec->jobId);
    		if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    			$row->jobId = ht::createLink($row->jobId, array('planning_Jobs', 'single', $rec->jobId));
    		}
    	}
    	
    	if(isset($rec->bomId)){
    		$row->bomId = "#" . cls::get('cat_Boms')->getHandle($rec->bomId);
    		if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    			$row->bomId = ht::createLink($row->bomId, array('cat_Boms', 'single', $rec->bomId));
    		}
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
    
    
    /**
     * Можели артикула да бъде въведен от производство. Може ако:
     * 
     * 1. Вложим е, има ресурс и има дебитно салдо този ресурс
     * 2. Има рецепта и задание
     */
    public static function canContoRec($rec, $masterRec)
    {
    	$entry = planning_transaction_ProductionNote::getDirectEntry($rec, $masterRec);
    	
    	if(!count($entry)){
    		if(isset($rec->bomId) && isset($rec->jobId)){
    			
    			return TRUE;
    		}
    		
    		return FALSE;
    	}
    	
    	return TRUE;
    }
}