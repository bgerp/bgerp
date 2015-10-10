<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_reports_BalanceImpl extends frame_BaseDriver
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'acc_BalanceReportImpl';
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Оборотни ведомости';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
    	$form->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка,mandatory,silent,removeAndRefreshForm=action');
    	$form->FLD('from', 'date', 'caption=От,mandatory');
    	$form->FLD('to', 'date', 'caption=До,mandatory');
    	$form->FLD("action", 'varchar', "caption=Действие,width=330px,silent,input=hidden,removeAndRefreshForm=grouping1|grouping2|grouping3|feat1|feat2|feat3");
    	$form->setOptions('action', array('' => '', 'filter' => 'Филтриране по пера', 'group' => 'Групиране по пера'));
    
    	$form->FLD('orderField', "enum(,ent1Id=Перо 1,ent2Id=Перо 2,ent3Id=Перо 3,baseQuantity=К-во»Начално,baseAmount=Сума»Начална,debitQuantity=К-во»Дебит,debitAmount=Сума»Дебит,creditQuantity=К-во»Кредит,creditAmount=Сума»Кредит,blQuantity=К-во»Крайно,blAmount=Сума»Крайна)", 'caption=Подредба->По,formOrder=110000');
    	$form->FLD('orderBy', 'enum(,asc=Въздходящ,desc=Низходящ)', 'caption=Подредба->Тип,formOrder=110001');
    
    	$this->invoke('AfterAddEmbeddedFields', array($form));
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
    	// Ако е избрана сметка
    	if($form->rec->accountId){
    		$form->setField('action', 'input');
    		
    		if($form->rec->id){
    			
    			if(frame_Reports::fetchField($form->rec->id, 'filter')->accountId != $form->rec->accountId){
    				unset($form->rec->grouping1, $form->rec->grouping2, $form->rec->grouping3, $form->rec->feat1, $form->rec->feat2, $form->rec->feat3);
    				Request::push(array('grouping1' => NULL, 'grouping2' => NULL, 'grouping3' => NULL, 'feat1' => NULL, 'feat2' => NULL, 'feat3' => NULL, 'orderField' => NULL, 'orderBy' => NULL));
    			}
    		}
    		
    		// Ако е избрано действие филтриране или групиране
    		if($form->rec->action){
    			$accInfo = acc_Accounts::getAccountInfo($form->rec->accountId);
    			 
    			// Показваме номенкалтурите на сметката като предложения за селектиране
    			$options = array();
    			 
    			if(count($accInfo->groups)){
    				foreach ($accInfo->groups as $i => $gr){
    					$options["ent{$i}Id"] .= $gr->rec->name;
    				}
    			}
    			
    			$Items = cls::get('acc_Items');
    			
    			// За всяка позиция показваме поле за избор на перо и свойство
    			foreach (range(1, 3) as $i){
    				if(isset($accInfo->groups[$i])){
    					self::setFilterAndGroupFields($form, $accInfo->groups[$i]->rec->id, $i);
    				}
    			}
    		}
    	}
    	
    	$this->invoke('AfterPrepareEmbeddedForm', array($form));
    }
    
    
    /**
     * Рендира вътрешната форма като статична форма в подадения шаблон
     *
     * @param core_ET $tpl - шаблон
     * @param string $placeholder - плейсхолдър
     */
    protected function prependStaticForm(core_ET &$tpl, $placeholder = NULL)
    {
    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    	$form->rec = $this->innerForm;
    	$form->class = 'simpleForm';
    		
    	$tpl->prepend($form->renderStaticHtml(), $placeholder);
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    	if($form->isSubmitted()){
    		if($form->rec->to < $form->rec->from){
    		     $form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
    		}
    		
    		foreach (range(1, 3) as $i){
    			if($form->rec->{"grouping{$i}"} && $form->rec->{"feat{$i}"}){
    				$form->setError("grouping{$i},feat{$i}", "Не може да са избрани едновременно перо и свойтво за една позиция");
    			}
    		}
    		
    		if($form->rec->orderField == ''){
    			unset($form->rec->orderField);
    		}
    		
    		if($form->rec->orderBy == ''){
    			unset($form->rec->orderBy);
    		}
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
        $data->rec = $this->innerForm;
       
        $this->prepareListFields($data);
        
        $accSysId = acc_Accounts::fetchField($data->rec->accountId, 'systemId');
        $Balance = new acc_ActiveShortBalance(array('from' => $data->rec->from, 'to' => $data->rec->to, 'accs' => $accSysId, 'cacheBalance' => FALSE));
        $data->recs = $Balance->getBalance($accSysId);
        
        $this->filterRecsByItems($data);
        
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
        $pager->calc();
        $data->pager = $pager;
        
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
        
        $res = $data;
    }
    
    
    /**
     * Връща шаблона на репорта
     * 
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
    	$tpl = getTplFromFile('acc/tpl/ReportDetailedBalance.shtml');
    	
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

    	$explodeTitle = explode(" » ", $this->title);
    		
    	$title = tr("|{$explodeTitle[1]}|*");
    	
    	$tpl->replace($title, 'TITLE');
    	
    	$this->prependStaticForm($tpl, 'FORM');
    	
    	$tpl->placeObject($data->row);
    	
    	$tableMvc = new core_Mvc;
    	$tableMvc->FLD('ent1Id', 'varchar', 'tdClass=itemClass');
    	$tableMvc->FLD('ent2Id', 'varchar', 'tdClass=itemClass');
    	$tableMvc->FLD('ent3Id', 'varchar', 'tdClass=itemClass');
    	$tableMvc->FLD('baseQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('baseAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('debitQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('debitAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('creditQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('creditAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('blQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('blAmount', 'int', 'tdClass=accCell');
    	
    	$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    	
    	$tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');
    	
    	$data->summary->colspan = count($data->listFields);
    	
    	if(!$data->bShowQuantities || $data->rec->action === 'group'){
    	     $data->summary->colspan -= 4;
    	     if($data->summary->colspan != 0 && count($data->rows)){
    	     	$beforeRow = new core_ET("<tr style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#baseAmount#]</b></td><td style='text-align:right'><b>[#debitAmount#]</b></td><td style='text-align:right'><b>[#creditAmount#]</b></td><td style='text-align:right'><b>[#blAmount#]</b></td></tr>");
    	     }
    	} else{
    		if(count($data->rows)){
    			$data->summary->colspan -= 8;
    			$beforeRow = new core_ET("<tr  style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#baseQuantity#]</b></td><td style='text-align:right'><b>[#baseAmount#]</b></td><td style='text-align:right'><b>[#debitQuantity#]</b></td><td style='text-align:right'><b>[#debitAmount#]</b></td><td style='text-align:right'><b>[#creditQuantity#]</b></td><td style='text-align:right'><b>[#creditAmount#]</b></td><td style='text-align:right'><b>[#blQuantity#]</b></td><td style='text-align:right'><b>[#blAmount#]</b></td></tr>");
    		}
    	}
    	
    	if($beforeRow){
    		$beforeRow->placeObject($data->summary);
    		$tpl->append($beforeRow, 'ROW_BEFORE');
    	}
    	
    	if($data->pager){
    	     $tpl->append($data->pager->getHtml(), 'PAGER_BOTTOM');
    	     $tpl->append($data->pager->getHtml(), 'PAGER_TOP');
    	}
    	
    	$embedderTpl->append($tpl, 'data');
    }

    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    protected function prepareListFields_(&$data)
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
        
    }
    
    
   /**
    * Вербалното представяне на записа
    */
   private function recToVerbal($data)
   {
   		$data->row = new stdClass();

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
   }
     
     
     /**
      * Оставяме в записите само тези, които трябва да показваме
      */
     private function filterRecsByItems(&$data)
     {
     	if(!empty($data->rec->action)){
     		$cmd = ($data->rec->action == 'filter') ? 'default' : 'group';
     		$by = (array)$data->rec;
     		acc_BalanceDetails::modifyListFields($data->listFields, $cmd, $by['grouping1'], $by['grouping2'], $by['grouping3'], $by['feat1'], $by['feat2'], $by['feat3']);
     		 
     		if($cmd == 'default'){
     			acc_BalanceDetails::filterRecs($data->recs, $by['grouping1'], $by['grouping2'], $by['grouping3'], $by['feat1'], $by['feat2'], $by['feat3']);
     		} else {
     			acc_BalanceDetails::groupRecs($data->recs, $by['grouping1'], $by['grouping2'], $by['grouping3'], $by['feat1'], $by['feat2'], $by['feat3']);
     		}
        }
         
         // Ако е посочено поле за сортиране, сортираме по него
         if($this->innerForm->orderField){
         	arr::order($data->recs, $this->innerForm->orderField, strtoupper($this->innerForm->orderBy));
         } else {
         	acc_BalanceDetails::sortRecsByNum($data->recs, $data->listFields);
         }
      }
       
       
       /**
        * Вербалното представяне на ред от таблицата
        */
       protected function getVerbalDetail_($rec)
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
      }

      
	  /**
	   * Добавяме полета за търсене
	   * 
	   * @see frame_BaseDriver::alterSearchKeywords()
	   */
      public function alterSearchKeywords(&$searchKeywords)
      {
      	  if(!empty($this->innerForm)){
	      		$accVerbal = acc_Accounts::getVerbal($this->innerForm->accountId, 'title');
	      		$num = acc_Accounts::getVerbal($this->innerForm->accountId, 'num');
	      			
	      		$str = $accVerbal . " " . $num;
	      		$searchKeywords .= " " . plg_Search::normalizeText($str);
      	  }
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
      	  $activateOn = "{$this->innerForm->to} 23:59:59";
      	  	
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
             $header .= $caption . ",";
         }

         foreach ($this->innerState->recs as $innerId => $innerRec) {
         	if (!isset($innerRec->creditQuantity) || !isset($innerRec->creditAmount)){
         		unset($this->innerState->recs[$innerId]);
         	}
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
        	$caption = str_replace('|*', '', $caption);
        	$caption = str_replace('->', ' - ', $caption);
        	
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
		
		$res = count($exportFields); 
		
		foreach ($rec as $field => $value) {
			$rCsv = '';
			
			if ($res == 11) {
				$zeroRow = 'ОБЩО' . "," .'' . "," .''. ",";
			} elseif ($res == 10 || $res == 9 || $res == 8 || $res == 7) {
				$zeroRow = 'ОБЩО' . "," .'' . ",";
			} elseif ($res <= 6) {
				$zeroRow =  'ОБЩО' . ",";
			}
			
			foreach ($exportFields as $field => $caption) {
					
				if ($rec->{$field}) {
	
					$value = $rec->{$field};
					$value = html2text_Converter::toRichText($value);
					
					if (preg_match('/\\r|\\n|,|"/', $value)) {
						$value = '"' . str_replace('"', '""', $value) . '"';
					}
					$rCsv .=  $value . ",";
					
					if($rec->flag == TRUE) {
						
						$zeroRow .= $value. ",";
						$rCsv = $zeroRow;
					}
	
				} else {
					
					$rCsv .= '' . "," ;
				}
			}
		}
		
		return $rCsv;
	}

	
	public static function setFilterAndGroupFields(&$form, $listId, $i)
	{
		$caption = acc_Lists::getVerbal($listId, 'name');
		$form->FLD("grouping{$i}", "key(mvc=acc_Items, allowEmpty)", "caption=|*{$caption}->|Перо|*");
		
		$items = cls::get('acc_Items')->makeArray4Select('title', "#lists LIKE '%|{$listId}|%'", 'id');
		$form->setOptions("grouping{$i}", $items);
		 
		if(count($items)){
			$form->setOptions("grouping{$i}", $items);
		} else {
			$form->setReadOnly("grouping{$i}");
		}
		 
		$features = acc_Features::getFeatureOptions(array_keys($items));
		$features = array('' => '') + $features + array('*' => $caption);
		$form->FLD("feat{$i}", 'varchar', "caption=|*{$caption}->|Свойство|*,width=330px,input");
		$form->setOptions("feat{$i}", $features);
	}
}