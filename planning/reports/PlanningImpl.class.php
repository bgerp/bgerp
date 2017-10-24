<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата 
 * на справка за планиране на производството
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Gabriela Petrova <gab4eto@gmai.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_reports_PlanningImpl extends frame_BaseDriver
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'planning_PlanningReportImpl';
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'planning, ceo';
    
    
    /**
     * Заглавие
     */
    public $title = 'Планиране » Задания по продажби';
    
    
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
    	
        $data->rec = $this->innerForm;
       
        $queryProduct = cat_Products::getQuery();
        $queryProduct->where("#canManifacture = 'yes' ");
        
        $query = sales_Sales::getQuery();
        $queryJob = planning_Jobs::getQuery();
        
        $this->prepareListFields($data);
        
        if ($data->rec->time == 0) {
        	$time = dt::today();
        } else {
        	$time = dt::timestamp2Mysql(dt::mysql2timestamp(dt::now())+$data->rec->time);
        }
        
        if (!isset($data->rec->time)) {
        	$query->where("#state = 'active'");
        } else {
        	$query->where("#deliveryTime <= '{$time}' AND #state = 'active'");
        	$query->orWhere("#valior <= '{$time}' AND #state = 'active'");
        }
        
        $queryJob->where("#state = 'active' OR #state = 'stopped' OR #state = 'wakeup'");

        $dates = array();
        
	    // за всеки един активен договор за продажба
	    while($rec = $query->fetch()) {
	        
	    	//$origin = doc_Threads::getFirstDocument($rec->threadId);
	        // взимаме информация за сделките
	        //$dealInfo = $origin->getAggregateDealInfo();

	        if ($rec->deliveryTime) {
	        	$date = $rec->deliveryTime;
	        } else {
	        	$date = $rec->valior;
	        }
	        	
	        if (sales_SalesDetails::fetch("#saleId = '{$rec->id}'") !== FALSE) {
	        		
	        		
	        	$p = sales_SalesDetails::fetch("#saleId = '{$rec->id}'");
	            $productId = $p->productId;
	       
	            $productInfo = cat_Products::getProductInfo($productId);
	       
	            if ($productInfo->meta['canManifacture'] == TRUE) {
	            	$products[] = sales_SalesDetails::fetch("#saleId = '{$rec->id}' AND #productId = '{$productId}'");
	                $dates[$productId][$rec->id] = $date;
	            } else {
	                continue;
	            }

	        } else {
	        		continue;
	        }
	    }

	    $dateSale = array();
	    
	    foreach ($dates as $prd => $sal) {
	    	if(count($sal) > 1) {
	        	$dateSale[$prd] = min($sal);
	        	$dateSale[$prd] = dt::mysql2timestamp($dateSale[$prd]);
	        } else {
	        	foreach ($sal as $d){
	        		$dateSale[$prd] = dt::mysql2timestamp($d);
	        	}
	        }
	        	
	    }
	   
	    // за всеки един продукт
	    if(is_array($products)){
			foreach($products as $product) {
		    	// правим индекс "класа на продукта|ид на продукта"
		        $index = $product->productId;
		        		
		        if($product->deliveryTime) {
		        	$date = $product->deliveryTime;
		        } else {
		        	$date = $rec->valior;
		        }
		        	
		        if ($product->quantityDelivered >= $product->quantity) continue;
		        
		        if (isset($data->rec->store)) { 
		        	$storeId = $data->rec->store;
		        }
		        
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
				        		(object) array ('id' => $product->productId,
						        				'quantity'	=> $product->quantity,
						        				'quantityDelivered' => $product->quantityDelivered,
				        				        'quantityToDelivered' => abs($product->quantityDelivered - $product->quantity),
				        						'dateSale' => $dateSale[$product->productId],
						        				'sales' => array($product->saleId),
				        		                'store' => $store );
			        		
			      // в противен случай го ъпдейтваме
			    } else {
			        		
					$obj = &$data->recs[$index];
				    $obj->quantity += $product->quantity;
				    $obj->quantityDelivered += $product->quantityDelivered;
				    $obj->quantityToDelivered += abs($product->quantityDelivered - $product->quantity);
				    $obj->dateSale = $dateSale[$product->productId];
				    $obj->sales = array($product->saleId);
				    $obj->store = array($store);
			        		
			    }
			}
	    }
	    
	    if(is_array($store)){
		    foreach($store as $productId => $quantity){
		    	$data->recs[$productId]->store = $quantity;
		    }
	    }

	    $dateJob = array();
	    
	    while ($recJobs = $queryJob->fetch()) {
	    	$indexJ = $recJobs->productId;
	        $dateJob[$recJobs->productId][$recJobs->id] = $recJobs->dueDate;
	       
	        if ($recJobs->quantityProduced >= $recJobs->quantity) continue;
	        
	        // ако нямаме такъв запис,
	        // го добавяме в масив
	        if(!array_key_exists($indexJ, $data->recs)){
	        	
	        	if(isset($data->rec->store)) { 
	        		$storeJob = store_Products::fetchField("#productId = {$recJobs->productId}  AND #storeId = {$data->rec->store}", 'quantity');
	        	} else {
	        		$storeJobQuery = store_Products::getQuery();
	        		$storeJobQuery->where("#productId = {$recJobs->productId}");
	        		
	        		while ($storeJobRec = $storeJobQuery->fetch()){
	        			$storeJob[$storeJobRec->productId] += $storeJobRec->quantity;
	        		}
	        	}
	        	
	        	$data->recs[$indexJ] =
	        		(object) array ('id' => $recJobs->productId,
	        				'quantityJob'	=> $recJobs->quantity,
	        				'quantityProduced' => $recJobs->quantityProduced,
	        				'quantityToProduced'=> abs($recJobs->quantityProduced - $recJobs->quantity),
	        				'date' => $recJobs->dueDate,
	        				'jobs' => array($recJobs->id),
	        				'store' => array($storeJob)
	        				);
	
	        // в противен случай го ъпдейтваме
	        } else {
	
	        	$obj = &$data->recs[$indexJ];
	        	$obj->quantityJob += $recJobs->quantity;
	        	$obj->quantityProduced += $recJobs->quantityProduced;
	        	$obj->quantityToProduced += abs($recJobs->quantityProduced - $recJobs->quantity);
	        	$obj->date =  $recJobs->dueDate;
	        	$obj->jobs = array($recJobs->id);
	        	$obj->store = array($storeJob);
	
	        }
	    }

	    if(is_array($storeJob)){
		    foreach($storeJob as $productJobId => $quantityJob){
		    	$data->recs[$productJobId]->store = $quantityJob;
		    }
	    }

	    foreach($data->recs as $id => $rec) {
	    	if (isset($dateJob[$id])) {
	    		if (count($dateJob[$id]) > 1) {
		    		$rec->date = min($dateJob[$id]);
		    		$rec->date  = dt::mysql2timestamp($rec->date);
	    		} else {
	    			$rec->date  = dt::mysql2timestamp($rec->date);
	    		}
	    	}
	    	
	    }

        arr::order($data->recs, 'date');
        arr::order($data->recs, 'dateSale');
        
        foreach ($data->recs as $id => $recs) {
        	if ($recs->date) {
        		$recs->date = dt::timestamp2Mysql($recs->date);
        	}
        	
        	if ($recs->dateSale) {
        		$recs->dateSale = dt::timestamp2Mysql($recs->dateSale);
        	}
        	
        	if (($recs->quantityToProduced < $recs->store) || ($recs->quantityToDelivered < $recs->store)) {
        		unset($data->recs[$id]);
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
    
                if ($rec->sales) { 
                	foreach($rec->sales as $sale) {
                		$idS = 'sales=' . $sale;
                	}
                } elseif($rec->jobs){
                	foreach ($rec->jobs as $job) { 
                		$idS = 'job='. $job;
                	}
                	
                } 

                $data->rows[$id]->ordered = $row->quantity . "<br><span style='color:#0066FF'>{$row->quantityJob}</span>";
                $data->rows[$id]->delivered = $row->quantityDelivered . "<br><span style='color:#0066FF'>{$row->quantityProduced}</span>";
                $data->rows[$id]->dt = $row->dateSale . "<br><span style='color:#0066FF'>{$row->date}</span>";
                
                // Задаваме уникален номер на контейнера в който ще се реплейсва туултипа
                $data->rec->id ++;
                $unique = $data->rec->id;

				$arrowImg = ht::createElement("img", array("src" => sbf("img/16/anchor-image.png", "")));
                $arrow = ht::createElement("span", array('class' => 'anchor-arrow tooltip-arrow-link'), $arrowImg, TRUE);
   
                if (isset($data->rows[$id]->quantityToDeliver) && isset($data->rows[$id]->quantityToProduced)) {
                	$data->rows[$id]->toDelivered = "{$arrow}&nbsp;" . $data->rows[$id]->quantityToDeliver . "<br>{$arrow}&nbsp;<span style='color:#0066FF'>{$data->rows[$id]->quantityToProduced}</span>";
                } elseif (isset($data->rows[$id]->quantityToDeliver)) {
                	$data->rows[$id]->toDelivered = "{$arrow}&nbsp;" . $data->rows[$id]->quantityToDeliver;
                } else {
                	$data->rows[$id]->toDelivered = "<br>{$arrow}&nbsp;<span style='color:#0066FF'>{$data->rows[$id]->quantityToProduced}</span>";
                }
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
    	$tpl = getTplFromFile('planning/tpl/PlanningReportLayout.shtml');
    	
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
    	$f->FLD('ordered', 'double');
    	$f->FLD('delivered', 'double');
    	$f->FLD('toDelivered', 'double');
    	$f->FLD('dt', 'date');
    	$f->FLD('inStore', 'double');

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
        		'ordered' => 'Продажба / Производство->|*<small>Поръчано</small>',
        		'delivered' => "Продажба / Производство->|*<small>Доставено<br><span style='color:#0066FF'>Произведено</small></span>",
        		'toDelivered' => "Продажба / Производство->|*<small>За доставяне<br><span style='color:#0066FF'>За производство</small><span>",
        		'dt' => 'Продажба / Производство->|*<small>Дата</small>',
        		'inStore' => 'На склад',
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

        $row = new stdClass();
        
        $row->id = cat_Products::getShortHyperlink($rec->id);
    	$row->quantity = $Int->toVerbal($rec->quantity);
    	$row->quantityDelivered = $Int->toVerbal($rec->quantityDelivered);
    	$row->quantityToDeliver = $Int->toVerbal($rec->quantityToDelivered);
    	
    	$row->dateSale = $Date->toVerbal($rec->dateSale);
    		
    	for($i = 0; $i <= count($rec->sales)-1; $i++) {
    		
    		$row->sales .= "#".sales_Sales::getHandle($rec->sales[$i]) .",";
    		$row->salesCsv .= sales_Sales::getShortHyperlink($rec->sales[$i]) .",";
    	}
    	$row->sales = $RichtextType->toVerbal(substr($row->sales, 0, -1));
    		
    	$row->quantityJob = $Int->toVerbal($rec->quantityJob);
    	$row->quantityProduced = $Int->toVerbal($rec->quantityProduced);
    	$row->quantityToProduced = $Int->toVerbal($rec->quantityToProduced);
    	$row->date = $Date->toVerbal($rec->date);
    		
    	for($j = 0; $j <= count($rec->jobs)-1; $j++) { 

    		$row->jobs .= "#".planning_Jobs::getHandle($rec->jobs[$j]) .","; 
    		$row->jobsCsv .= planning_Jobs::getShortHyperlink($rec->jobs[$j]) .",";
    		
    	}
		$row->jobs = $RichtextType->toVerbal(substr($row->jobs, 0, -1));

		$row->inStore = $Int->toVerbal($rec->store);
		
		
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
             redirect(array($this), FALSE, "|Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
         }

         $csv = "";

         foreach ($exportFields as $caption) {
             $header .= $caption. ",";
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

        $exportFields['id']  = "Име (код)";
        $exportFields['quantity']  = "Поръчано";
        $exportFields['quantityJob']  = "За пороизводство";
        $exportFields['quantityDelivered']  = "Доставено";
        $exportFields['quantityProduced']  = "Произведено";
        $exportFields['quantityToDelivered']  = "За доставяне";
        $exportFields['quantityToProduced']  = "За произвeждане";
        $exportFields['dateSale']  = "Продажба-Дата";
        $exportFields['date']  = "Производство-Дата";
        $exportFields['sales'] = "Продажба";
		$exportFields['jobs']  = "Производство";
		$exportFields['store']  = "На склад";

        
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
		$rec = frame_CsvLib::prepareCsvRows($rec);

		$res = new stdClass();
		$rCsv = '';
		foreach ($rec as $field => $value) {
			$rCsv = '';
			
			$res->id = self::getVerbal($rec)->id;
			$res->quantity = $rec->quantity;
			$res->quantityJob = $rec->quantityJob;
			$res->quantityDelivered = $rec->quantityDelivered;
			$res->quantityProduced = $rec->quantityProduced;
			$res->quantityToDelivered = $rec->quantityToDelivered;
			$res->quantityToProduced = $rec->quantityToProduced;
			$res->dateSale = $rec->dateSale;
			$res->date = $rec->date;
			$res->sales = substr(self::getVerbal($rec)->salesCsv, 0, strlen(self::getVerbal($rec)->salesCsv)-1);
			$res->jobs = substr(self::getVerbal($rec)->jobsCsv, 0, strlen(self::getVerbal($rec)->jobsCsv)-1);
			$res->store = $rec->store;
	
			foreach ($exportFields as $fld => $caption) {
			
				if ($res->{$fld}) {
		
					$value = $res->{$fld};
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