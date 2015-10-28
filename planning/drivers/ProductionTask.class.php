<?php



/**
 * Драйвер за задачи за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Задача за производство
 */
class planning_drivers_ProductionTask extends tasks_BaseDriver
{
	
	
	/**
	 * Шаблон за обвивката този драйвер
	 */
	protected $singleLayoutFile = 'planning/tpl/SingleLayoutProductionTask.shtml';
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'planning,ceo';
	
	
	/**
	 * От кои класове може да се избира драйвера
	 */
	public $availableClasses = 'planning_Tasks';
	
	
	/**
	 * Какво да е дефолтното име на задача от драйвера
	 */
	protected $defaultTitle = 'Задача за производство';
	
	
	/**
	 * Кои детайли да се заредят динамично към мастъра
	 */
	protected $detail = 'planning_drivers_ProductionTaskDetails';
	
	
	/**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
		$fieldset->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Общо к-во');
		$fieldset->FLD('totalWeight', 'cat_type_Weight', 'caption=Общо тегло,input=none');
		$fieldset->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=code,makeLinks)', 'caption=Машини');
	}
	
	
	/**
	 * Преди рендиране на шаблона
	 * 
	 * @param tasks_BaseDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	protected static function on_AfterRenderSingleLayout(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$tpl, $data)
	{
		$tpl = getTplFromFile($Driver->singleLayoutFile);
	}
	
	
	/**
     * Обновяване на данните на мастъра
     * 
     * @param stdClass $rec - запис на ембедъра
     * @param void
     */
	public function updateEmbedder(&$rec)
	{
		 // Колко е общото к-во досега
		 $dQuery = planning_drivers_ProductionTaskDetails::getQuery();
		 $dQuery->where("#taskId = {$rec->id}");
		 $dQuery->where("#state != 'rejected'");
		 $dQuery->XPR('sumQuantity', 'double', 'SUM(#quantity)');
		 $dQuery->XPR('sumWeight', 'double', 'SUM(#weight)');
		 $dQuery->show('sumQuantity,sumWeight');
		 
		 $res = $dQuery->fetch();
		 $sumQuantity = $res->sumQuantity;
		 
		 // Преизчисляваме общото тегло
		 $rec->totalWeight = $res->sumWeight;
		      
		 // Изчисляваме колко % от зададеното количество е направено
		 $rec->progress = round($sumQuantity / $rec->totalQuantity, 2);
	}
	
	
	/**
     * Възможност за промяна след обръщането на данните във вербален вид
     *
     * @param tasks_TaskDetails $Detail
     * @param stdClass $row
     * @param stdClass $rec
     * @return void
     */
	public function recToVerbalDetail(tasks_TaskDetails $Detail, &$row, $rec)
	{
		if(isset($rec->operation)){
			$verbal = arr::make('start=Пускане,production=Произвеждане,waste=Отпадък,scrap=Бракуване,stop=Спиране');
			if(isset($verbal[$rec->operation])){
				$row->operation = $verbal[$rec->operation];
				$row->operation = "<div class='centered'>{$row->operation}</div>";
			}
		}
	}
	
	
	/**
     * Възможност за промяна след подготовката на формата на детайла
     *
     * @param tasks_TaskDetails $Detail
     * @param stdClass $data
     * @return void
     */
	public function prepareEditFormDetail(tasks_TaskDetails $Detail, &$data)
	{
		$form = &$data->form;
		$form->setFieldType('operation', 'enum(start=Пускане,production=Произвеждане,waste=Отпадък,scrap=Бракуване,stop=Спиране)');
		$form->setField('operation', 'input,mandatory');
		
		// Показваме полето за въвеждане на код само при операция "произвеждане"
		if($form->rec->operation == 'production'){
			$form->setField('code', 'input');
		}
	}
	
	
	/**
     * Възможност за промяна след рендирането на детайла
     * 
     * @param tasks_TaskDetails $Detail
     * @param core_ET $tpl
     * @param stdClass $data
     * @return void
     */
    public function renderDetail(tasks_TaskDetails $Detail, &$tpl, $data)
    {
    	// Добавяме бутон за добавяне на прогрес при нужда
    	if($Detail->haveRightFor('add', (object)array('taskId' => $data->masterId))){
    		if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    			$ht = ht::createLink('', array($Detail, 'add', 'taskId' => $data->masterId, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/add.png,title=Добавяне на прогрес към задачата');
    			$tpl->append($ht, 'ADD_BTN');
    		}
    	} 
    }
    
    
    /**
     * Възможност за промяна след подготовката на лист тулбара
     *
     * @param tasks_TaskDetails $Detail
     * @param stdClass $data
     * @return void
     */
    public function prepareListToolbarDetail(tasks_TaskDetails $Detail, &$data)
    {
    	// Премахваме стандартния бутон за добавяне
    	parent::prepareListToolbarDetail($Detail, $data);
    	$data->toolbar->removeBtn('btnAdd');
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     * 
     * @param tasks_BaseDriver $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $res
     * @param stdClass $rec
     */
    public static function on_AfterGetSearchKeywords(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$res, $rec)
    {
    	if(empty($rec->id)) return;
    	
    	$Detail = cls::get($Driver->getDetail());
    	
    	$dQuery = $Detail->getQuery();
    	$dQuery->where("#taskId = {$rec->id}");
    	 
    	$detailsKeywords = '';
    	while($dRec = $dQuery->fetch()){
    		 
    		// Добавяме данните от детайла към ключовите думи
    		$detailsKeywords .= " " . plg_Search::normalizeText($Detail->getVerbal($dRec, 'operation'));
    		if($dRec->code){
    			$detailsKeywords .= " " . plg_Search::normalizeText($Detail->getVerbal($dRec, 'code'));
    		}
    		 
    		if($dRec->fixedAsset){
    			$detailsKeywords .= " " . plg_Search::normalizeText($Detail->getVerbal($dRec, 'fixedAsset'));
    		}
    	}
    	 
    	// Добавяме новите ключови думи към старите
    	$res = " " . $res . " " . $detailsKeywords;
    }
    
    
    /**
     * Връща полетата, които ще се показват в антетката
     * 
     * @param stdObject $rec
     * @param stdObject $row
     * 
     * @return array
     */
    public static function prepareFieldLetterHeaded($rec, $row)
    {
        $resArr = array();
        
        if ($row->timeStart) {
            $resArr['timeStart'] =  array('name' => tr('Начало'), 'val' =>"[#timeStart#]");
        }
        
        if ($row->timeDuration) {
            $resArr['timeDuration'] =  array('name' => tr('Продължителност'), 'val' =>"[#timeDuration#]");
        }
        
        if ($row->timeEnd) {
            $resArr['timeEnd'] =  array('name' => tr('Краен срок'), 'val' =>"[#timeEnd#] [#remainingTime#]");
        }
        
        if ($row->expectedTimeStart) {
            $resArr['expectedTimeStart'] =  array('name' => tr('Очаквано начало'), 'val' =>"[#expectedTimeStart#]");
        }
        
        if ($row->expectedTimeEnd) {
            $resArr['expectedTimeEnd'] =  array('name' => tr('Очакван край'), 'val' =>"[#expectedTimeEnd#]");
        }
        
        $resArr['totalQuantity'] =  array('name' => tr('Общо к-во'), 'val' =>"[#totalQuantity#]");
        
        if ($row->totalWeight) {
            $resArr['totalWeight'] =  array('name' => tr('Общо тегло'), 'val' =>"[#totalWeight#]");
        }
        
        if ($row->fixedAssets) {
            $resArr['fixedAssets'] =  array('name' => tr('Машини'), 'val' =>"[#fixedAssets#]");
        }
        
        $resArr['progressBar'] =  array('name' => tr('Прогрес'), 'val' =>"[#progressBar#] [#progress#]");
        
        if ($row->originId) {
            $resArr['originId'] =  array('name' => tr('Към задание'), 'val' =>"[#originId#]");
        }
        
        return $resArr;
    }
}
