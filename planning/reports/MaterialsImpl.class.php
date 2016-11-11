<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата 
 * на справка за планиране на покупки на материали
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Gabriela Petrova <gab4eto@gmai.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_reports_MaterialsImpl extends frame_BaseDriver
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'planning, ceo';
    
    
    /**
     * Заглавие
     */
    public $title = 'Планиране » Покупки на материали';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();

    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
	public function addEmbeddedFields(core_FieldSet &$form)
    {
    	$form->FLD('time', 'time(suggestions=на момента|1 седмица|2 седмица|3 седмица|4 седмиц|)', 'caption=Хоризонт');
    	$form->FLD('store', 'key(mvc=store_Stores, select=name, allowEmpty)', 'caption=Склад');
    	
    	$this->invoke('AfterAddEmbeddedFields', array($form));
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
	public function prepareEmbeddedForm(core_Form &$form)
    {
    	$form->setDefault('time', 'на момента');
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
	public function checkEmbeddedForm(core_Form &$form)
    {    
  	
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
    	$data = new stdClass();
    	$data->recs = array();
    	$materials = "";
    	
        $data->rec = $this->innerForm;
        
        $query = planning_Jobs::getQuery();
        
        $this->prepareListFields($data);
        
        if ($data->rec->time == 0) {
        	$time = dt::today();
        } else {
        	$time = dt::timestamp2Mysql(dt::mysql2timestamp(dt::now())+$data->rec->time);
        }
        
        if (!isset($data->rec->time)) {
        	$query->where("#state = 'active'");
        } else {
        	$query->where("#deliveryDate <= '{$time}' AND #state = 'active'");
        	$query->orWhere("#dueDate <= '{$time}' AND #state = 'active'");
        }
        $store = "";
        $materials = array();
        
	    // за всеки един активен договор за продажба
	    while($rec = $query->fetch()) {

	        if (isset($rec->deliveryDate)) { 
	        	$date = $rec->deliveryDate;
	        } else {
	        	$date = $rec->dueDate;
	        }
	        	
	        $id = $rec->id;
	        
	        $productId = $rec->productId;
	        $index = $productId;
	           
	        $productInfo = cat_Products::getProductInfo($productId);
	        
	        if (isset($data->rec->store)) {
	        	$storeId = $data->rec->store;
	        }
	        
	        $materials[] = cat_Products::getMaterialsForProduction($productId,$rec->quantity);
	    }
	    
	    $mArr = array ();
	    foreach ($materials as $material) {
	    	foreach ($material as $product => $productRec) {
	    		$mArr[$product] += $productRec['quantity'];
	    	}
	    }
	    
	    foreach($mArr as $pId => $quantity) {
	    	$index = $pId;

	        // ако нямаме такъв запис,
	        // го добавяме в масив
	        if(!array_key_exists($index, $data->recs)){ 
	        
	        	if(isset($storeId)) {
	        		$store = store_Products::fetchField("#productId = {$index} AND #storeId = {$storeId}", 'quantity');
	        	} else {
	        		$storeQuery = store_Products::getQuery();
	        		$storeQuery->where("#productId = {$index}");
	        		while ($storeRec = $storeQuery->fetch()){
	        			$store[$storeRec->productId] += $storeRec->quantity;
	        		}
	        	}

	        	$data->recs[$index] =
	        	(object) array ('id' => $pId,
	        			'quantity'	=> $quantity,
	        			'store' => $store,);
	        	} else {
	        		 
	        		$obj = &$data->recs[$index];
	        		$obj->quantity += $quantity;
	        		$obj->store[] = $store;
	        	}
	        	
	    }

	    foreach ($data->recs as $id => $recs) { 
	    	
		    unset($data->recs[$id]->store);
		    
		    if(is_array($store)){
			    if(array_key_exists($id, $store)) {
			    	$recs->store = $store[$id];
			    }
		    }

		    if ($recs->quantity < $recs->store) {
		    	unset($data->recs[$id]);
		    }
		    
		    $recs->res = abs($recs->store - $recs->quantity);
	    }

        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$res)
    {
    	// Подготвяме страницирането
    	$data = $res;
        
        $pager = cls::get('core_Pager',  array('itemsPerPage' => $mvc->listItemsPerPage));
        $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
        $pager->addToUrl = array('#' => $mvc->EmbedderRec->instance->getHandle($mvc->EmbedderRec->that));

        $pager->itemsCount = count($data->recs);
        $data->pager = $pager;
        
        $recs = array();
        foreach($data->recs as $rec){
            $recs[] = $rec;
        }

        if(count($recs)){
     
            foreach ($recs as $id => $r){ 
                
                $r->num = $id +1;

				if(!$pager->isOnPage()) continue;
				
                $row = $mvc->getVerbal($r);
                $data->rows[$id] = $row;
                
            }
        }

        $res = $data;
    }
    

    /**
     * Връща шаблона на репорта
     * 
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
    	$tpl = getTplFromFile('planning/tpl/PurchaseReportLayout.shtml');
    	
    	return $tpl;
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
		if(empty($data)) return;
    	 
    	$tpl = $this->getReportLayout();
    	
    	$title = explode(" » ", $this->title);
    	
    	$tpl->replace($title[1], 'TITLE');
    
    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    
    	$form->rec = $data->rec;
    	$form->class = 'simpleForm';
    
    	$tpl->prepend($form->renderStaticHtml(), 'FORM');
    
    	$tpl->placeObject($data->rec);

    	$f = cls::get('core_FieldSet');
    	
    	$f->FLD('num', 'int', 'tdClass=accItemClass');
    	$f->FLD('id', 'varchar', 'tdClass=accItemClass');
    	$f->FLD('quantity', 'double', 'tdClass=accItemClass,smartCenter');
    	$f->FLD('store', 'double', 'tdClass=accItemClass,smartCenter');
    	$f->FLD('res', 'double', 'tdClass=accItemClass,smartCenter');

    	$table = cls::get('core_TableView', array('mvc' => $f));

    	$tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');

    	if($data->pager){
    	     $tpl->append($data->pager->getHtml(), 'PAGER');
    	}
    
    	$embedderTpl->append($tpl, 'data');
    }

    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    protected function prepareListFields_(&$data)
    {
    
        $data->listFields = array(
                'num' => '№',
        		'id' => 'Материали (код)',
        		'quantity' => 'Необходимо к-во по задания',
        		'store' => 'Налично к-во на склад',
                'res' => 'Необходимо к-во за закупуване',
        		);
    }

       
    /**
     * Вербалното представяне на ред от таблицата
     */
   private function getVerbal($rec)
    {
    	$RichtextType = cls::get('type_Richtext');
        $Date = cls::get('type_Date');
		$Int = cls::get('type_Int');
		$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));

        $row = new stdClass();
        
        $row->num = $Int->toVerbal($rec->num);
        $row->id = cat_Products::getShortHyperlink($rec->id);
    	$row->quantity = $Double->toVerbal($rec->quantity);
    	
    	$row->store = $Double->toVerbal($rec->store);
    	
    	if($row->store < 0){
    		$row->store = "<span class='red'>$rec->store</span>";
    	}
    	
    	$row->res = $Double->toVerbal($rec->res);

        return $row;
    }
      
      
	/**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
	public function hidePriceFields()
    {
    	$innerState = &$this->innerState;
      		
      	unset($innerState->recs);
    }
      
      
	/**
     * Коя е най-ранната дата на която може да се активира документа
     */
	public function getEarlyActivation()
    {
    	if ($this->innerForm->time == 0 || !isset($this->innerForm->time)) {
    		$time = dt::today();
    	} else {
    		$time = dt::timestamp2Mysql(dt::mysql2timestamp(dt::now())+$this->innerForm->time);
    	}

    	$activateOn = "{$time} 13:59:59";
      	  	
      	return $activateOn;
	}

	
	/**
	 * Ще се експортирват полетата, които се
	 * показват в табличния изглед
	 *
	 * @return array
	 * @todo да се замести в кода по-горе
	 */
	protected function getFields_()
	{
	    // Кои полета ще се показват
	    $f = new core_FieldSet;
	
	    $f->FLD('id', 'key(mvc=cat_Products,select=name)');
	    $f->FLD('quantity', 'double');
	    $f->FLD('store', 'double');
	    $f->FLD('res', 'double');
	    
	    return $f;
	}
	
	
     /**
      * Ако имаме в url-то export създаваме csv файл с данните
      *
      * @param core_Mvc $mvc
      * @param stdClass $rec
      */
     public function exportCsv()
     {

        $exportFields = $this->innerState->listFields;
        $fields = $this->getFields();

        $dataRecs = array();
        if (is_array($this->innerState->recs)) {
            
            $csv = csv_Lib::createCsv($this->innerState->recs, $fields, $exportFields);
        }
         
        return $csv;
    }
}