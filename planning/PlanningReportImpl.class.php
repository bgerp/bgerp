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
class planning_PlanningReportImpl extends frame_BaseDriver
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'planning, ceo';
    
    
    /**
     * Заглавие
     */
    public $title = 'Планиране » Планиране на производството';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf,bgerp_DealIntf';
    
    
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
    public function addEmbeddedFields(core_Form &$form)
    {
    	$form->FLD('from', 'date', 'caption=Начало');
    	$form->FLD('to', 'date', 'caption=Край');
    
    	//$form->FLD('orderField', "enum(,ent1Id=Перо 1,ent2Id=Перо 2,ent3Id=Перо 3,baseQuantity=К-во»Начално,baseAmount=Сума»Начална,debitQuantity=К-во»Дебит,debitAmount=Сума»Дебит,creditQuantity=К-во»Кредит,creditAmount=Сума»Кредит,blQuantity=К-во»Крайно,blAmount=Сума»Крайна)", 'caption=Подредба->По,formOrder=110000');
    	//$form->FLD('orderBy', 'enum(,asc=Въздходящ,desc=Низходящ)', 'caption=Подредба->Тип,formOrder=110001');
    
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
        	 
    	// Размяна, ако периодите са объркани
    	if(isset($form->rec->from) && isset($form->rec->to) && ($form->rec->from > $form->rec->to)) {
    		$mid = $form->rec->from;
    		$form->rec->from = $form->rec->to;
    		$form->rec->to = $mid;
    	}
    	
    	
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
    	$data = new stdClass();
    	$data->catCnt = array();
    	$data->jobCnt = array();
        $data->rec = $this->innerForm;
       
        $query = sales_Sales::getQuery();
        $queryJob = planning_Jobs::getQuery();
        
        $query->where("#state = 'active'");
        $queryJob->where("#state = 'active'");
        
        // за всеки един активен договор за продажба
        while($rec = $query->fetch()) {
        	//
        	//$origin = doc_Threads::getFirstDocument($rec->threadId);
        	// взимаме информация за сделките
        	//$dealInfo = $origin->getAggregateDealInfo();
        	//bp($rec);
        	if ($rec->deliveryTime) {
        		$date = $rec->deliveryTime;
        	} else {
        		$date = $rec->valior;
        	}
        	
        	$id = $rec->id;
        	$products[] = sales_SalesDetails::fetch("#saleId = $rec->id");
        	$dates[$id] = $date;

        }

        // за всеки един продукт
        if(is_array($products)){
	    	foreach($products as $product) {
	    		// правим индекс "класа на продукта|ид на продукта"
	        	//$index = "$product->classId" .'|'. "$product->productId";
	        	$index = $product->productId;
	        		
	        	if($product->deliveryTime) {
	        		$date = $product->deliveryTime;
	        	} else {
	        		$date = $rec->valior;
	        	}
		        	
	        	// ако нямаме такъв запис,
	        	// го добавяме в масив
		        if(!array_key_exists($index, $data->catCnt)){
		        		
			    	$data->catCnt[$index] = 
			        		(object) array ('id' => $product->productId,
					        				'quantity'	=> $product->quantity,
					        				'quantityDelivered' => $product->quantityDelivered,
			        						'dateSale' => $dates[$product->saleId],
					        				'sales' => array($product->saleId));
		        		
		        // в противен случай го ъпдейтваме
		        } else {
		        		
			    	$obj = &$data->catCnt[$index];
			        $obj->quantity += $product->quantity;
			        $obj->quantityDelivered += $product->quantityDelivered;
			        $obj->dateSale = $dates[$product->saleId];
			        $obj->sales[] = $product->saleId;
		        		
		        }
	        }
        }

        while ($recJobs = $queryJob->fetch()) {
        	$indexJ = $recJobs->productId;
        	 
        	// ако нямаме такъв запис,
        	// го добавяме в масив
        	if(!array_key_exists($indexJ, $data->catCnt)){
        		$data->catCnt[$indexJ] =
        		(object) array ('id' => $recJobs->productId,
        				'quantityJob'	=> $recJobs->quantity,
        				'quantityProduced' => $recJobs->quantityProduced,
        				'date' => $recJobs->dueDate,
        				'jobs' => array($recJobs->id));

        		// в противен случай го ъпдейтваме
        	} else {

        		$obj = &$data->catCnt[$indexJ];
        		$obj->quantityJob += $recJobs->quantity;
        		$obj->quantityProduced += $recJobs->quantityProduced;
        		$obj->date =  $recJobs->dueDate;
        		$obj->jobs[] = $recJobs->id;

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
    	/*$data = $res;
    	$pageVar = str::addHash("P", 5, "{$mvc->className}{$mvc->EmbedderRec->that}");
    	$Pager = cls::get('core_Pager', array('pageVar' => $pageVar, 'itemsPerPage' => $mvc->listItemsPerPage));
        $Pager->itemsCount = count($data->recs);
        $Pager->calc();
        $data->pager = $Pager;
        
        $start = $data->pager->rangeStart;
        $end = $data->pager->rangeEnd - 1;
        
        $data->summary = new stdClass();
        
        if(count($data->recs)){
            $count = 0;
            
            foreach ($data->recs as $id => $rec){
                
                // Показваме само тези редове, които са в диапазона на страницата
                if($count >= $start && $count <= $end){
                    $rec->id = $count + 1;
                    $row = $mvc->getVerbalDetail($rec);
                    $data->rows[$id] = $row;
                }
                
                // Сумираме всички суми и к-ва
                foreach (array('baseQuantity', 'baseAmount', 'debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blAmount', 'blQuantity') as $fld){
                    if(!is_null($rec->$fld)){
                        $data->summary->$fld += $rec->$fld;
                    }
                }
                
                $count++;
            }
        }
        
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        foreach ((array)$data->summary as $name => $num){
            $data->summary->$name  = $Double->toVerbal($num);
            if($num < 0){
            	$data->summary->$name  = "<span class='red'>{$data->summary->$name}</span>";
            }
        }
        
        $mvc->recToVerbal($data);
        
        $res = $data;*/
    }
    
    
    /**
     * Връща шаблона на репорта
     * 
     * @return core_ET $tpl - шаблона
     */
    /*public function getReportLayout_()
    {
    	$tpl = getTplFromFile('acc/tpl/ReportDetailedBalance.shtml');
    	
    	return $tpl;
    }*/
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {
    	$tpl = new ET("
            <h1>Планиране » Планиране на производството</h1>
            [#FORM#]
            
    		[#PAGER#]
            [#VISITS#]
        "
    	);
    
    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    
    	$form->rec = $data->fRec;
    	$form->class = 'simpleForm';
    
    	$tpl->prepend($form->renderStaticHtml(), 'FORM');
    
    	$tpl->placeObject($data->rec);
    
    	$pager = cls::get('core_Pager',  array('pageVar' => 'P_' .  $this->EmbedderRec->that,'itemsPerPage' => $this->listItemsPerPage));
    	$pager->itemsCount = count($data->цатCnt);

    	$f = cls::get('core_FieldSet');

    	$f->FLD('id', 'varchar', 'caption=Продукт->Име (код)');
    	$f->FLD('quantity', 'int', 'caption=Продажба->поръчано');
    	$f->FLD('quantityDelivered', 'int', 'caption=Продажба->доставено');
    	$f->FLD('quantityToDeliver', 'int', 'caption=Продажба->за доставяне');
    	$f->FLD('dateSale', 'date', 'caption=Продажба->дата');
    	$f->FLD('sales', 'richtext', 'caption=По продажба');
    	$f->FLD('quantityJob', 'int', 'caption=Производство->поръчано');
    	$f->FLD('quantityProduced', 'int', 'caption=Производство->произведено');
    	$f->FLD('quantityToProduced', 'int', 'caption=Производство->за производство');
    	$f->FLD('date', 'date', 'caption=Продажба->дата');
    	$f->FLD('jobs', 'richtext', 'caption=По задание');
    	
    	
    	$rows = array();

    	$ft = $f->fields;
        $varcharType = $ft['id']->type;
        $intType = $ft['quantityToDeliver']->type;
        $tichtextType = $ft['sales']->type;
        $dateType = $ft['date']->type;
        
    	foreach ($data->catCnt as $cat) {

    		//if(!$pager->isOnPage()) continue;
    		
    		if ($cat->quantityDelivered && $cat->quantity) {
    			$toDeliver = abs($cat->quantityDelivered - $cat->quantity);
    		} else {
    			$toDeliver = '';
    		}
    		
    		if ($cat->quantityProduced && $cat->quantityJob) {
    			$toProduced = abs($cat->quantityProduced - $cat->quantityJob);
    		} else {
    			$toProduced = '';
    		}
    		
    		$row = new stdClass();

    		$row->id = cat_Products::getShortHyperlink($cat->id);
    		$row->quantity = $intType->toVerbal($cat->quantity);
    		$row->quantityDelivered = $intType->toVerbal($cat->quantityDelivered);
    		$row->quantityToDeliver = $intType->toVerbal($toDeliver);
    		$row->dateSale = $dateType->toVerbal($cat->dateSale);
    		
    		for($i = 0; $i <= count($cat->sales)-1; $i++) {

    			$row->sales .= "#".sales_Sales::getHandle($cat->sales[$i]) .",";
    		}
    		$row->sales = $tichtextType->toVerbal(substr($row->sales, 0, -1));
    		
    		$row->quantityJob = $intType->toVerbal($cat->quantityJob);
    		$row->quantityProduced = $intType->toVerbal($cat->quantityProduced);
    		$row->quantityToProduced = $intType->toVerbal($toProduced);
    		$row->date = $dateType->toVerbal($cat->date);
    		
    		for($j = 0; $j <= count($cat->jobs)-1; $j++) { 

    			$row->jobs .= "#".planning_Jobs::getHandle($cat->jobs[$j]) .","; 
    		}
			$row->jobs = $tichtextType->toVerbal(substr($row->jobs, 0, -1));
    		
    		$rows[] = $row;

    	}

    	$table = cls::get('core_TableView', array('mvc' => $f));
    	$html = $table->get($rows, 'id=Име (код),quantity=Продажба->поръчано,quantityDelivered=Продажба->доставено,quantityToDeliver=Продажба->за доставяне,dateSale=Продажба->дата,sales=По продажба,
    											 quantityJob=Производство->поръчано,quantityProduced=Производство->произведено,quantityToProduced=Производство->за производство,date=Продажба->дата,jobs=По задание');
    
    	$tpl->append($html, 'VISITS');
        $tpl->append($pager->getHtml(), 'PAGER');
    
    	return  $tpl;
    }

    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    /*protected function prepareListFields_(&$data)
    {
    
         $data->accInfo = acc_Accounts::getAccountInfo($data->rec->accountId);
    
         $bShowQuantities = ($data->accInfo->isDimensional === TRUE) ? TRUE : FALSE;
        
    	 $data->bShowQuantities = $bShowQuantities;
         
         $data->listFields = array();
    		
         foreach ($data->accInfo->groups as $i => $list) {
         	$data->listFields["ent{$i}Id"] = "|*" . acc_Lists::getVerbal($list->rec, 'name');
         }
    
    	 if($data->bShowQuantities) {
            $data->listFields += array(
                'baseQuantity' => 'Начално салдо->ДК->К-во',
                'baseAmount' => 'Начално салдо->ДК->Сума',
                'debitQuantity' => 'Обороти->Дебит->К-во',
                'debitAmount' => 'Обороти->Дебит->Сума',
                'creditQuantity' => 'Обороти->Кредит->К-во',
                'creditAmount' => 'Обороти->Кредит->Сума',
                'blQuantity' => 'Крайно салдо->ДК->К-во',
                'blAmount' => 'Крайно салдо->ДК->Сума', );
        } else {
            $data->listFields += array(
                'baseAmount' => 'Салдо->Начално',
                'debitAmount' => 'Обороти->Дебит',
                'creditAmount' => 'Обороти->Кредит',
                'blAmount' => 'Салдо->Крайно',
            );
        }
        
    }*/
    
    
   /**
    * Вербалното представяне на записа
    */
   /*private function recToVerbal($data)
   {
   		$data->row = new stdClass();
    	//bp($data);
        foreach (range(1, 3) as $i){
       		if(!empty($data->rec->{"ent{$i}Id"})){
       			$data->row->{"ent{$i}Id"} = "<b>" . acc_Lists::getVerbal($data->accInfo->groups[$i]->rec, 'name') . "</b>: ";
       			$data->row->{"ent{$i}Id"} .= acc_Items::fetchField($data->rec->{"ent{$i}Id"}, 'titleLink');
       		}
        }
       
        if(!empty($data->rec->action)){
        	$data->row->action = ($data->rec->action == 'filter') ? tr('Филтриране по') : tr('Групиране по');
        	$data->row->groupBy = '';
        	
        	$Varchar = cls::get('type_Varchar');
        	foreach (range(1, 3) as $i){
        		if(!empty($data->rec->{"grouping{$i}"})){
        			$data->row->groupBy .= acc_Items::getVerbal($data->rec->{"grouping{$i}"}, 'title') . ", ";
        		} elseif(!empty($data->rec->{"feat{$i}"})){
        			$data->rec->{"feat{$i}"} = ($data->rec->{"feat{$i}"} == '*') ? $data->accInfo->groups[$i]->rec->name : $data->rec->{"feat{$i}"};
        			$data->row->groupBy .= $Varchar->toVerbal($data->rec->{"feat{$i}"}) . ", ";
        		}
        	}
        	
        	$data->row->groupBy = trim($data->row->groupBy, ', ');
        	
        	if($data->row->groupBy === ''){
        		unset($data->row->action);
        	}
        }
        
        //bp($data);
   }*/
     
     
     /**
      * Оставяме в записите само тези, които трябва да показваме
      */
     /*private function filterRecsByItems(&$data)
     {
     	$Balance = cls::get('acc_BalanceDetails');
     	
     	//
     	if(!empty($data->rec->action)){
         	$cmd = ($data->rec->action == 'filter') ? 'default' : 'group';
         	$Balance->doGrouping($data, (array)$data->rec, $cmd, $data->recs);
        }
         
         // Ако е посочено поле за сортиране, сортираме по него
         if($this->innerForm->orderField){
         	arr::order($data->recs, $this->innerForm->orderField, strtoupper($this->innerForm->orderBy));
         } else {
         	
         	// Ако не се сортира по номерата на перата
         	$Balance->canonizeSortRecs($data, $this->cache);
         }
      }*/
       
       
       /**
        * Вербалното представяне на ред от таблицата
        */
       /*private function getVerbalDetail($rec)
       {
           $Varchar = cls::get('type_Varchar');
           $Double = cls::get('type_Double');
           $Double->params['decimals'] = 2;

           $Int = cls::get('type_Int');

           $row = new stdClass();
           $row->id = $Int->toVerbal($rec->id);
       
           foreach (array('baseAmount', 'debitAmount', 'creditAmount', 'blAmount', 'baseQuantity', 'debitQuantity', 'creditQuantity', 'blQuantity') as $fld){
               $row->$fld = $Double->toVerbal($rec->$fld);
               $row->$fld = (($rec->$fld) < 0) ? "<span style='color:red'>{$row->$fld}</span>" : $row->$fld;
           }
       
           foreach (range(1, 3) as $i) {
           		if(isset($rec->{"grouping{$i}"})){
           			$row->{"ent{$i}Id"} = $rec->{"grouping{$i}"};
           
           			if($row->{"ent{$i}Id"} == 'others'){
           				$row->{"ent{$i}Id"} = "<i>" . tr('Други') . "</i>";
           			}
           		} else {
           			if(!empty($rec->{"ent{$i}Id"})){
           				$row->{"ent{$i}Id"} .= acc_Items::getVerbal($rec->{"ent{$i}Id"}, 'titleLink');
           			}
           		}
           }
       
           $row->ROW_ATTR['class'] = ($rec->id % 2 == 0) ? 'zebra0' : 'zebra1';
       
           return $row;
      }*/

      
	  /**
	   * Добавяме полета за търсене
	   * 
	   * @see frame_BaseDriver::alterSearchKeywords()
	   */
      /*public function alterSearchKeywords(&$searchKeywords)
      {
      	  if(!empty($this->innerForm)){
	      		$accVerbal = acc_Accounts::getVerbal($this->innerForm->accountId, 'title');
	      		$num = acc_Accounts::getVerbal($this->innerForm->accountId, 'num');
	      			
	      		$str = $accVerbal . " " . $num;
	      		$searchKeywords .= " " . plg_Search::normalizeText($str);
      	  }
      }*/
      
      
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
      	  $activateOn = "{$this->innerForm->to} 23:59:59";
      	  	
      	  return $activateOn;
      }


     /**
      * Ако имаме в url-то export създаваме csv файл с данните
      *
      * @param core_Mvc $mvc
      * @param stdClass $rec
      */
     /*public function exportCsv()
     {

         $exportFields = $this->getExportFields();

         $conf = core_Packs::getConfig('core');

         if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
             redirect(array($this), FALSE, "Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
         }

         $csv = "";

         foreach ($exportFields as $caption) {
             $header .= "," . $caption;
         }

         
         if(count($this->innerState->recs)) {
			foreach ($this->innerState->recs as $id => $rec) {

				if($this->innerState->bShowQuantities || $this->innerState->rec->groupBy){
					
					
					$baseQuantity += $rec->baseQuantity;
					$baseAmount += $rec->baseAmount;
					$debitQuantity += $rec->debitQuantity;
					$debitAmount += $rec->debitAmount;
					$creditQuantity += $rec->creditQuantity;
					$creditAmount += $rec->creditAmount;
					$blQuantity += $rec->blQuantity;
					$blAmount += $rec->blAmount;

				} 
				
				$rCsv = $this->generateCsvRows($rec);

				
				$csv .= $rCsv;
				$csv .=  "\n";
		
			}

			$row = new stdClass();
			
			$row->flag = TRUE;
			$row->baseQuantity = $baseQuantity;
			$row->baseAmount = $baseAmount;
			$row->debitQuantity = $debitQuantity;
			$row->debitAmount = $debitAmount;
			$row->creditQuantity = $creditQuantity;
			$row->creditAmount = $creditAmount;
			$row->blQuantity = $blQuantity;
			$row->blAmount = $blAmount;
			
			foreach ($row as $fld => $value) {
				$value = frame_CsvLib::toCsvFormatDouble($value);
				$row->{$fld} = $value;
			}
		
		
			$beforeRow = $this->generateCsvRows($row);

			$csv = $header . "\n" . $beforeRow. "\n" . $csv;
	    } 

        return $csv;
    }*/


    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    /*protected function getExportFields_()
    {

        $exportFields = $this->innerState->listFields;
        
        foreach ($exportFields as $field => $caption) {
        	$caption = str_replace('|*', '', $caption);
        	$caption = str_replace('->', ' - ', $caption);
        	
        	$exportFields[$field] = $caption;
        }
        
        return $exportFields;
    }*/
    
    
    /**
	 * Ще направим row-овете в CSV формат
	 *
	 * @return string $rCsv
	 */
	/*protected function generateCsvRows_($rec)
	{
	
		$exportFields = $this->getExportFields();

		$rec = frame_CsvLib::prepareCsvRows($rec);
	
		$rCsv = '';
		
		$res = count($exportFields); 
		
		foreach ($rec as $field => $value) {
			$rCsv = '';
			
			if ($res == 11) {
				$zeroRow = "," . 'ОБЩО' . "," .'' . "," .'';
			} elseif ($res == 10 || $res == 9 || $res == 8 || $res == 7) {
				$zeroRow = "," . 'ОБЩО' . "," .'';
			} elseif ($res <= 6) {
				$zeroRow = "," . 'ОБЩО';
			}
			
			foreach ($exportFields as $field => $caption) {
					
				if ($rec->{$field}) {
	
					$value = $rec->{$field};
					$value = html2text_Converter::toRichText($value);
					// escape
					if (preg_match('/\\r|\\n|,|"/', $value)) {
						$value = '"' . str_replace('"', '""', $value) . '"';
					}
					$rCsv .= "," . $value;
					
					if($rec->flag == TRUE) {
						
						$zeroRow .= "," . $value;
						$rCsv = $zeroRow;
					}
	
				} else {
					
					$rCsv .= "," . '';
				}
			}
		}
		
		return $rCsv;
	}*/

}