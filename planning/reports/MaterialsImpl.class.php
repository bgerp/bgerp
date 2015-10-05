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
	        
	        $materials = cat_Products::getMaterialsForProduction($productId,$rec->quantity);

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
	        	(object) array ('id' => $productId,
	        			'quantity'	=> $rec->quantity,
	        			'date' => $date,
	        			'job' => array($id),
	        			'store' => $store,
	        	        'materials' => $materials);
	        	} else {
	        		 
	        		$obj = &$data->recs[$index];
	        		$obj->quantity += $rec->quantity;
	        		$obj->date = $date;
	        		$obj->job[] = $id;
	        		$obj->store += $store;
	        		$obj->materials[] = $materials;
	        	}
	        	
	    }

	    foreach ($data->recs as $id => $recs) {
	    	
		    unset($data->recs[$id]->store);
		    
		    if(is_array($store)){
			    if(array_key_exists($id, $store)) {
			    	$recs->store = $store[$id];
			    }
		    }
		    
		    foreach($recs->materials as $material => $mRecs) {
		    	if ($mRecs[quantity] < $recs->store) {
		    		unset($data->recs[$id]);
		    	}
		    }
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
        
        if(count($data->recs)){
          
            foreach ($data->recs as $id => $rec){
				if(!$pager->isOnPage()) continue;
                
                $row = $mvc->getVerbal($rec);
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
    	
    	$f->FLD('id', 'varchar');
    	$f->FLD('quantity', 'double');
    	$f->FLD('date', 'date');
    	$f->FLD('job', 'double');
    	$f->FLD('materials', 'double');
    	$f->FLD('store', 'double');

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
        		'id' => 'Име (код)',
        		'quantity' => 'Задание за производство->Бройка',
        		'date' => "Задание за производство->Дата",
        		'job' => "Задание за производство->Задание",
        		'materials' => 'Материали->Име (код) / Брой',
        		'store' => 'На склад',
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
        
        $row->id = cat_Products::getShortHyperlink($rec->id);
    	$row->quantity = $Int->toVerbal($rec->quantity);
    	$row->date = $Date->toVerbal($rec->date);
    		
    	for($i = 0; $i <= count($rec->job)-1; $i++) {
    		
    		$row->job .= planning_Jobs::getHyperlink($rec->job[$i]) .",";
    	}
    	
    	$row->job = substr($row->job, 0, strlen($row->job)-1);
    	
    	foreach($rec->materials as $materialId => $mRec) { 
    		$cn = $Double->toVerbal($mRec[quantity]);
    		$row->materials .= cat_Products::getShortHyperlink($mRec[productId]). " / " . $cn . ",  <br>";
    	}
    	
    	$row->materials = substr($row->materials, 0, strlen($row->materials)-1);
    	
    	$row->store = $Double->toVerbal($rec->store);
    	
    	if($row->store < 0){
    		$row->store = "<span class='red'>$rec->store</span>";
    	}

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
    	
    	$activateOn = "{$time} 23:59:59";
      	  	
      	return $activateOn;
	}

	
     /**
      * Ако имаме в url-то export създаваме csv файл с данните
      *
      * @param core_Mvc $mvc
      * @param stdClass $rec
      */
     public function exportCsv()
     {

         $exportFields = $this->getExportFields();

         $conf = core_Packs::getConfig('core');

         if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
             redirect(array($this), FALSE, "Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
         }

         $csv = "";

         foreach ($exportFields as $caption) {
             $header .=  $caption. ',';
         }

         
         if(count($this->innerState->recs)) {
			foreach ($this->innerState->recs as $id => $rec) {
				
				$rCsv = $this->generateCsvRows($rec);

				
				$csv .= $rCsv;
				$csv .=  "\n";
		
			}

			$csv = $header . "\n" . $csv;
	    } 

        return $csv;
    }


    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    protected function getExportFields_()
    {

        $exportFields = $this->innerState->listFields;
    
        return $exportFields;
    }
    
    
    /**
	 * Ще направим row-овете в CSV формат
	 *
	 * @return string $rCsv
	 */
	protected function generateCsvRows_($rec)
	{
	
		$exportFields = $this->getExportFields();

		$rec = self::getVerbal($rec);

		$rCsv = '';

		foreach ($rec as $field => $value) {
			$rCsv = '';

		
			foreach ($exportFields as $field => $caption) {

				if ($rec->{$field}) {
		
					$value = $rec->{$field};
					$value = html2text_Converter::toRichText($value);
					// escape
					if (preg_match('/\\r|\\n|,|"/', $value)) {
						$value = '"' . str_replace('"', '""', $value) . '"';
					}
					$rCsv .=  $value . ",";
		
				} else {
					$rCsv .=  ''. ",";
				}
			}
		}
	
		return $rCsv;
	}

}