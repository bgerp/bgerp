<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата 
 * на справка на баланса по определен период
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalancePeriodReportImpl extends frame_BaseDriver
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Балансов отчет по период';
    
    
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
    public function addEmbeddedFields(core_Form &$form)
    {
    	$form->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка,mandatory,silent,removeAndRefreshForm=action');
    	$form->FLD('from', 'key(mvc=acc_Periods,select=title, allowEmpty)', 'caption=От,mandatory');
    	$form->FLD('to', 'key(mvc=acc_Periods,select=title, allowEmpty)', 'caption=До,mandatory');
    	
    
    	$form->FLD('orderField', "enum(,debitAmount=Дебит,creditAmount=Кредит,blAmount=Крайнo салдо)", 'caption=Подредба,formOrder=110000');
    	$form->FLD('compare', 'enum(,yes=Да)', 'caption=Сравни,formOrder=110001,maxRadio=1');
    
    	$this->invoke('AfterAddEmbeddedFields', array($form));
    }
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_Form &$form)
    {

    	// Искаме всички счетоводни периоди за които
    	// има изчислени оборотни ведомости
    	$balanceQuery = acc_Balances::getQuery();
    	$balanceQuery->where("#periodId IS NOT NULL");
    	$balanceQuery->orderBy("#fromDate", "DESC");
    	
    	while ($bRec = $balanceQuery->fetch()) {
    	    $b = acc_Balances::recToVerbal($bRec, 'periodId');
    		$periods[$bRec->periodId] = $b->periodId;
    	}
    	
    	$form->setOptions('from', array('' => '') + $periods);
    	$form->setOptions('to', array('' => '') + $periods);
    	
    	// по подразбиране ще сложим последния период
    	// и един месец назад
    	$balanceCls = cls::get('acc_Balances');
    	$lastBalance = $balanceCls->getLastBalance();
    	$previousBalance = $balanceCls->getBalanceBefore($lastBalance->fromDate);
    	
    	$form->setDefault('from', $lastBalance->periodId);
    	$form->setDefault('to', $previousBalance->periodId);

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
    	if(isset($form->rec->from) && isset($form->rec->to) && ($form->rec->from > $form->rec->to)) { //bp($form->rec->from , $form->rec->to);
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
    	$data->recs = array();
    	$data->bData = array();
    	 
    	$data->rec = $this->innerForm;
    	$this->prepareListFields($data);
    	
    	$accSysId = acc_Accounts::fetchField($data->rec->accountId, 'systemId');
    	
    	$bDetails = cls::get('acc_BalanceDetails');
    	$bQuery = acc_BalanceDetails::getQuery();

    	for ($p = $data->rec->from; $p <= $data->rec->to; $p++) {
    		
    		$balanceId = acc_Balances::fetchField("#periodId = '{$p}'", 'id');
    		$bDetails->filterQuery($bQuery, $balanceId, $accSysId);

    		$data->recs[$p] = $bQuery->fetchAll();
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
        
        $pager = cls::get('core_Pager',  array('pageVar' => 'P_' .  $mvc->EmbedderRec->that,'itemsPerPage' => $mvc->listItemsPerPage));
       
        $pager->itemsCount = count($data->recs);
        $data->pager = $pager;
        
        if(count($data->recs)){
          
            foreach ($data->recs as $id => $rec){ 
				if(!$pager->isOnPage()) continue;
				
				$row = new stdClass();
			
				$row->periodId = $id;
				$row->amount = 0;
				
				foreach ($rec as $bRec) { 
		
					switch ($data->rec->orderField) {
						case 'debitAmount':
							$row->amount += $bRec->debitAmount;
							break;
						case 'creditAmount':
							$row->amount += $bRec->creditAmount;
							break;
						case 'blAmount':
							$row->amount += $bRec->blAmount;
							break;
					}
				}
				//bp($row);
				$row = $mvc->getVerbal($row);
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
    	$tpl = getTplFromFile('acc/tpl/BalancePeriodReportLayout.shtml');
    	
    	return $tpl;
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {
    	
    	if(empty($data)) return;
    	$chart = Request::get('Chart');
    	$id = Request::get('id', 'int');
    	
    	$tpl = $this->getReportLayout();
    
    	$title = explode(" » ", $this->title);
    	 
    	$tpl->replace($title[1], 'TITLE');
    	
    	$form = cls::get('core_Form');
    	
    	$this->addEmbeddedFields($form);
    	
    	$form->rec = $data->rec;
    	$form->class = 'simpleForm';
    	
    	$this->prependStaticForm($tpl, 'FORM');
    	
    	$tpl->placeObject($data->rec);
    	
    	// ако имаме записи има и смисъл да
    	// слагаме табове
    	// @todo да не се ползва threadId  за константа
    	if($data->recs) {
    		// слагаме бутони на къстам тулбара
    		$btnList = ht::createBtn('Таблица', array(
    				'doc_Containers',
    				'list',
    				'threadId' => Request::get('threadId', 'int'),
    	
    		), NULL, NULL,
    				'ef_icon = img/16/table.png');
    	
    		$tpl->replace($btnList, 'buttonList');
    	
    		$btnChart = ht::createBtn('Графика', array(
    				'doc_Containers',
    				'list',
    				'Chart' => 'bar'. $data->rec->containerId,
    				'threadId' => Request::get('threadId', 'int'),
    	
    		), NULL, NULL,
    				'ef_icon = img/16/chart_bar.png');
    	
    		$tpl->replace($btnChart, 'buttonChart');
    	}
    
    	$labels = array();
    	$values = array();
    	foreach ($data->rows as $row) {
    		
    		$labels[] = $row->periodId;
    		$values[mb_substr($row->periodId,0,19)] = array ($row->amount);
    		
    	}

    	if ($chart == 'bar'.$data->rec->containerId && $data->recs) { 
	    	$bar = array (
	    			'legendTitle' => "Балансов отчет по период",
	    			'labels' => $labels,
	    			'values' => $values
	    	);
	    	
	    	$coreConf = core_Packs::getConfig('doc');
	    	$chartAdapter = $coreConf->DOC_CHART_ADAPTER;
	    	$chartHtml = cls::get($chartAdapter);
	    	$chart =  $chartHtml::prepare($bar,'bar');
	    	$tpl->append($chart, 'CONTENT');
    	} else {
	
	    	$f = cls::get('core_FieldSet');
	    	
	    	$f->FLD('periodId', 'richtext');
	    	$f->FLD('amount', 'double');
	    	$f->FLD('amountPrevious', 'double');
	    	
	    	
	    	$table = cls::get('core_TableView', array('mvc' => $f));
	   
	    	$tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');
	    	 
	    	if($data->pager){
	    		$tpl->append($data->pager->getHtml(), 'PAGER');
	    	}
    	}
    	
    	return  $tpl;
    }

    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    protected function prepareListFields_(&$data)
    {
    	switch ($data->rec->orderField) {
			case 'debitAmount':
		        $data->listFields = array(
	    			'periodId' => 'Период',
	    			'amount' => 'Дебит',
	    		);
		        break;
		        
		    case 'creditAmount':
		        $data->listFields = array(
	    			'periodId' => 'Период',
	    			'amount' => 'Кредит',
	    		);
		        break;
		        
		    case 'blAmount':
		        $data->listFields = array(
	    			'periodId' => 'Период',
	    			'amount' => 'Крайно салдо',
	    		);
		        break;
		}

		if ($data->rec->compare == 'yes') {
			 $data->listFields['amountPrevious'] = 'Предходна година';
		}
    }
    
    /**
     * Вербалното представяне на записа
     */
    /*private function recToVerbal($data)
    {
    	$data->row = new stdClass();
    	
    	$RichtextType = cls::get('type_Richtext');
    	
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	//bp($data);
    	//$periodId = acc_Balances::fetchField("#id = '{$data->rec->balanceId}'", 'periodId');
    	$data->row->periodId = acc_Periods::getTitleById($data->rec->from);
    	$data->row->amount = 1000;
    }*/
   
   /**
    * Вербалното представяне на записа
    */
	private function getVerbal($rec)
   	{
   		$RichtextType = cls::get('type_Richtext');
        
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;
//bp($rec);

        $row = new stdClass();
        
        $row->periodId = acc_Periods::getTitleById($rec->periodId); //amountPrevious
        
        if ($rec->amount < 0) {
	    	$row->amount = "<span class='red'>{$Double->toVerbal($rec->amount)}</span>";
        } else {
        	$row->amount = $Double->toVerbal($rec->amount);
        }
        
        if ($rec->amountPrevious < 0) {
        	$row->amountPrevious = "<span class='red'>{$Double->toVerbal($rec->amountPrevious)}</span>";
        } else {
        	$row->amountPrevious = $Double->toVerbal($rec->amountPrevious);
        }
	       
        /*foreach (range(1, 3) as $i){
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
        }*/
        
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