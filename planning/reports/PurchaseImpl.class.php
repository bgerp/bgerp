<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата 
 * на справка за планиране на покупки на стоки
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Gabriela Petrova <gab4eto@gmai.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_reports_PurchaseImpl extends frame_BaseDriver
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'planning_PurchaseReportImpl';
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'planning, ceo';
    
    
    /**
     * Заглавие
     */
    public $title = 'Планиране » Покупки на стоки';
    
    
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
    	
        $data->rec = $this->innerForm;
        
        $query = sales_Sales::getQuery();
        
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

	    // за всеки един активен договор за продажба
	    while($rec = $query->fetch()) {

	        if ($rec->deliveryTime) {
	        	$date = $rec->deliveryTime;
	        } else {
	        	$date = $rec->valior;
	        }
	        	
	        $id = $rec->id;
	        	
	        if (sales_SalesDetails::fetch("#saleId = $id") !== FALSE) {
	        		
	        	$p = sales_SalesDetails::fetch("#saleId = $rec->id");
	            $productId = $p->productId;
	           
	            $productInfo = cat_Products::getProductInfo($productId);
	       
	            if ($productInfo->meta['canBuy'] == TRUE && $productInfo->meta['canStore'] == TRUE) {
	            	$products[] = sales_SalesDetails::fetch("#saleId = $id AND #productId = $productId");
	                $dates[$productId][$id] = $date;
	            } else {
	                continue;
	            }

	        } else {
	        		continue;
	        }
	    }
	     
	    if (is_array($dates)) {   
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
		        $store = "";
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
				        						'quantityТоDelivered' => abs($product->quantityDelivered - $product->quantity),
				        						'dateSale' => $dateSale[$product->productId],
						        				'sales' => array($product->saleId),
				        		                'store' => array($store));
			        		
			      // в противен случай го ъпдейтваме
			    } else {
			        		
					$obj = &$data->recs[$index];
				    $obj->quantity += $product->quantity;
				    $obj->quantityDelivered += $product->quantityDelivered;
				    $obj->quantityToDelivered += abs($product->quantityDelivered - $product->quantity);
				    $obj->dateSale = $dateSale[$product->productId];
				    $obj->sales[] = $product->saleId;
				    $obj->store[] = $store;
			        		
			    }
			}
	    }
	
	    if(is_array($store)){
	    	foreach($store as $productId => $quantity){
	    		$data->recs[$productId]->store = $quantity;
	    	}
	    }

        arr::order($data->recs, 'dateSale');

        for ($dt = 0; $dt <= count($data->recs); $dt++) {
        	
        	if ($data->recs[$dt]->dateSale) {
        		$data->recs[$dt]->dateSale = dt::timestamp2Mysql($data->recs[$dt]->dateSale);
        	}
        }
        
        foreach ($data->recs as $id => $recs) {
        	if ($recs->quantityToDelivered < $recs->store) {
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
                } 
                

                $data->rows[$id]->ordered = $row->quantity;
                $data->rows[$id]->delivered = $row->quantityDelivered;
                $data->rows[$id]->dt = $row->dateSale;
                
                // Задаваме уникален номер на контейнера в който ще се реплейсва туултипа
                $data->rec->threadId ++;
                $unique = $data->rec->threadId;

				$arrowImg = ht::createElement("img", array("src" => sbf("img/16/anchor-image.png", "")));
                $arrow = ht::createElement("span", array('class' => 'anchor-arrow tooltip-arrow-link'), $arrowImg, TRUE);
   
                if (isset($data->rows[$id]->quantityToDeliver)) {
                	$data->rows[$id]->toDelivered = "{$arrow}&nbsp;" . $data->rows[$id]->quantityToDeliver;
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
        		'ordered' => 'Продажба ->|*<small>Поръчано</small>',
        		'delivered' => "Продажба ->|*<small>Доставено<br>",
        		'toDelivered' => "Продажба ->|*<small>За доставяне<br>",
        		'dt' => 'Продажба ->|*<small>Дата</small>',
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
		
		if ($rec->quantityDelivered && $rec->quantity) {
			$toDeliver = abs($rec->quantityDelivered - $rec->quantity);
		} elseif ($rec->quantityDelivered !== 0 || $rec->quantityDelivered == NULL) {
			$toDeliver = $rec->quantity;
		} else {
			$toDeliver = '';
		}

        $row = new stdClass();
        
        $row->id = cat_Products::getShortHyperlink($rec->id);
    	$row->quantity = $Int->toVerbal($rec->quantity);
    	$row->quantityDelivered = $Int->toVerbal($rec->quantityDelivered);
    	$row->quantityToDeliver = $Int->toVerbal($toDeliver);
    	
    	$row->dateSale = $Date->toVerbal($rec->dateSale);
    		
    	for($i = 0; $i <= count($rec->sales)-1; $i++) {
    		
    		$row->sales .= "#".sales_Sales::getHandle($rec->sales[$i]) .",";
    	}
    	$row->sales = $RichtextType->toVerbal(substr($row->sales, 0, -1));
		
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
        
        foreach ($exportFields as $field => $caption) {
        	if(strpos($caption, "<br>")) {
        		$caption = str::crop($caption, "<small>", "<br>");
        	} elseif (strpos($caption, "<small>")) {
        		$caption = str::crop($caption, "<small>", "</small>");
        	} else {
        		
        	}
        	
        	$exportFields[$field] = $caption;
        }
     
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

		$rCsv = '';

		$res = new stdClass();
		foreach ($rec as $field => $value) {
			$rCsv = '';
			
			$res->id = self::getVerbal($rec)->id;
			$res->ordered = self::getVerbal($rec)->quantity;
			$res->delivered = $rec->quantityDelivered;
			$res->toDelivered = self::getVerbal($rec)->quantityToDeliver;
			$res->dt = $rec->dateSale;
			$res->inStore = $rec->store;
		
			foreach ($exportFields as $field => $caption) {

				if ($res->{$field}) {
		
					$value = $res->{$field};
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