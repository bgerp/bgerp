<?php



/**
 * Мениджър на отчети от Движение по сметки на  доставчици
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_reports_LeaveDaysPersons extends frame_BaseDriver
{
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, trz';


    /**
     * Заглавие
     */
    public $title = 'Персонал » Отсъствия';
    
    
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
    	$form->FLD('from', 'date', 'caption=От, mandatory');
    	$form->FLD('to', 'date', 'caption=До,mandatory');
    	$form->FLD('departments', 'key(mvc=hr_Departments, select=name,allowEmpty)', 'caption=Отдел');
    	$form->FLD('team', 'key(mvc=core_Roles, select=role)', 'caption=Екип');
    	
    	$this->invoke('AfterAddEmbeddedFields', array($form));
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
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_FieldSet &$form)
    {
        // Показваме всички екипи
        $teams = core_Roles::getRolesByType('team');
        $teams = keylist::toArray($teams);
        
        $options = array(""=>"");
        
        foreach($teams as $t) {
            // вербализираме екипа
            $tRole = core_Roles::getVerbal($t, 'role');
            $title = tr('Екип') . " \"" . $tRole . "\"";
            
            $options[$t] = $title;
        }

        $form->setOptions('team',$options);

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

    	$this->prepareListFields($data);
    	
    	$query = crm_Profiles::getQuery();
    	$query->where("(#stateDateFrom IS NOT NULL AND #stateDateTo IS NOT NULL AND #stateDateFrom <= '{$data->rec->from}' AND #stateDateTo >= '{$data->rec->from}')
                        OR
    	               (#stateDateFrom IS NOT NULL AND #stateDateTo IS NOT NULL AND #stateDateFrom <= '{$data->rec->to}' AND #stateDateTo >= '{$data->rec->to}')");

    	$recs = array();
    	while($rec = $query->fetch()){ 
            $index = $rec->userId;
    	    $recs[$index] =
    	        (object) array ('person' => $rec->personId,
    	            'userId' => $rec->userId,
    	            'info' => $rec->stateInfo,
    	            'from' => $rec->stateDateFrom,
    	            'to' => $rec->stateDateTo,
    	    
    	    );
    	}
    	
    	$qProfiles = crm_Profiles::getQuery();
    	$recProfiles = $qProfiles->fetchAll();

    	foreach($recProfiles as $id => $r){
    	    if($r->state != 'active') {
    	        unset($recProfiles[$id]);
    	    }
    	}
    	
    	foreach($recs as $rId => $re){
    	    if($data->rec->team){ 
    	        if(!haveRole("|".$data->rec->team."|", $rId)) { 
    	            unset($recs[$rId]);
    	        }
    	    }
    	    
    	    if($data->rec->departments){
    	        $eQuery = planning_Hr::getQuery();
    	        $eQuery->where("#personId = '{$re->person}'");

    	        $eRec = $eQuery->fetch();
    	        $departments = keylist::toArray($eRec->departments);
    	        if(!$departments[$data->rec->departments]){
    	           unset($recs[$rId]);
    	        }   
    	    }
    	}

    	if($data->rec->team) {
    	    $message = $data->rec->team;  
    	    $flag = 0;
    	} elseif($data->rec->departments) {
    	    $message = $data->rec->departments;
    	    $flag = 1;
    	} else {
    	    $message = 'Отсъстващи';
    	    $flag = -1;
    	}


    	$all = count($recProfiles);
    	$missing = count($recs);
   	
    	$data->recs[] = (object) array (
    	    'missing' => $message,
    	    'percent' => $missing / $all,
    	    'flag' => $flag);

        return $data;
    }

    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$res)
    {
    	// Подготвяме страницирането
    	$data = $res;
    	
    	if(!Mode::is('printing')){
            $pager = cls::get('core_Pager',  array('pageVar' => 'P_' .  $mvc->EmbedderRec->that,'itemsPerPage' => $mvc->listItemsPerPage));
            $pager->itemsCount = count($data->recs);
            $data->pager = $pager;
    	}
    	
        if(count($data->recs)){

            foreach ($data->recs as $id => $rec){
                if(!Mode::is('printing')){
      			   if(!$pager->isOnPage()) continue;
                }   
	            
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
    	$tpl = getTplFromFile('hr/tpl/LeaveDaysReportLayout.shtml');
    	
    	return $tpl;
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl,$data)
    {
    	if(empty($data)) return;
  
    	$tpl = $this->getReportLayout();
    	
    	$title = explode(" » ", $this->title);
    	
    	$tpl->replace($title[1], 'TITLE');
    
    	$this->prependStaticForm($tpl, 'FORM');

    	$tpl->placeObject($data->row);
    

    	$f = cls::get('core_FieldSet');

    	$f->FLD('missing', 'varchar');
    	$f->FLD('percent', 'percent');

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
                'missing' => 'Отсъстващи',
        		'percent' => 'Процент',
        		);
        
    }

       
    /**
     * Вербалното представяне на ред от таблицата
     */
    private function getVerbal($rec)
    {

		$Percent = cls::get('type_Percent');
		$Varchar = cls::get('type_Varchar');
		

        $row = new stdClass();

        if($rec->flag == 0) {
            // вербализираме екипа
            $tRole = core_Roles::getVerbal($rec->missing, 'role');
            $title = tr('Екип') . " \"" . $tRole . "\"";
            $row->missing = $title;
        } elseif($rec->flag == -1){
            $row->missing = $Varchar->toVerbal($rec->missing);
        } elseif($rec->flag == 1) { 
            $row->missing = hr_Departments::fetchField($rec->missing, 'name');   
        }
        
        $row->percent = $Percent->toVerbal($rec->percent);
		
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
    public function exportCsv()
    {

		/*$exportFields = $this->innerState->listFields;

        $conf = core_Packs::getConfig('core');

         if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
             redirect(array($this), FALSE, "|Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
         }

         $csv = "";

         foreach ($exportFields as $caption) {
             $header .= "," . $caption;
         }

         $currency = "," . 'ВАЛУТА'."," .$this->innerState->summary->currency;
         
         $costPrice = html2text_Converter::toRichText($this->innerState->summary->costPrice);
         $costPriceAll = html2text_Converter::toRichText($this->innerState->summary->costPriceAll);
         // escape
         if (preg_match('/\\r|\\n|,|"/', $costPrice)) {
         	$costPrice = '"' . str_replace('"', '""', $costPrice) . '"';
         }
         
         if (preg_match('/\\r|\\n|,|"/', $costPriceAll)) {
         	$costPrice = '"' . str_replace('"', '""', $costPriceAll) . '"';
         }
          
         $zerroRow = "," . 'ОБЩО'."," .",".",".$costPrice.",".$costPriceAll;
        
         if(count($this->innerState->recs)) {
			foreach ($this->innerState->recs as $productId => $rec) {
				//foreach($measure as $rec){ 

					$rCsv = $this->generateCsvRows($rec);
	
					$csv .= $rCsv;
					$csv .=  "\n";
				//}
		
			}

			$csv = $currency."\n" .$header. "\n" .$zerroRow. "\n" . $csv;
	    } 

        return $csv;*/
    }

    
    /**
	 * Ще направим row-овете в CSV формат
	 *
	 * @return string $rCsv
	 */
	protected function generateCsvRows_($rec)
	{
		
		/*$exportFields = $this->innerState->listFields;

		$rec = $this->getVerbal($rec);

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
					$rCsv .= "," . $value;

				} else {
					
					$rCsv .= "," . '';
				}
			}
		}

		return $rCsv;*/
	}
	
	
	/**
	 * Връща дефолт заглавието на репорта
	 */
	public function getReportTitle()
	{
	
	    $explodeTitle = explode(" » ", $this->title);
	
	    $title = tr("|{$explodeTitle[1]}|*");
	
	    return $title;
	}
}