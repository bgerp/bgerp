<?php 


/**
 * Мениджира детайлите на тестовете (Details)
 *
 *
 * @category  bgerp
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class lab_TestDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Детайли на тест";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, plg_RowNumbering,
                          plg_Printing, lab_Wrapper, plg_Sorting, 
                          Tests=lab_Tests, Params=lab_Parameters, Methods=lab_Methods,plg_PrevAndNext, plg_SaveAndNew';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'testId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'methodId,paramName,value,error';

    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = "lab_Tests";
    
    
    /**
     * Роли, които могат да записват
     */
    var $canWrite = 'lab,ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('testId', 'key(mvc=lab_Tests, select=title)', 'caption=Тест, input=hidden, silent,mandatory,smartCenter');
        $this->FNC('paramName', 'varchar(255)', 'caption=Параметър, notSorting,smartCenter');
        $this->FLD('methodId', 'key(mvc=lab_Methods, select=name)', 'caption=Метод, notSorting,mandatory,smartCenter');
        $this->FLD('value', 'varchar(64)', 'caption=Стойност, notSorting, input=none,smartCenter');
        $this->FLD('error', 'percent(decimals=2)', 'caption=Грешка, notSorting,input=none,smartCenter');
        $this->FLD('results', 'text', 'caption=Резултати, hint=На всеки отделен ред запишете по една стойност от измерване,notSorting, column=none');
        
        $this->setDbUnique('testId, methodId');
    }
    
    
    /**
     * Променя заглавието и добавя стойност по default в селекта за избор на тест
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        // allMethodsArr
        $Methods = cls::get('lab_Methods');
        $queryAllMethods = $Methods->getQuery();
        
        $allMethodsArr = array();
        
        while($mRec = $queryAllMethods->fetch("1=1")) {
            $allMethodsArr[$mRec->id] = $mRec->name;
        }
        $data->allMethodsArr = $allMethodsArr;
        
        // $methodIdSelectArr
        foreach ($allMethodsArr as $k => $v) {
            if (!$mvc->fetchField("#testId = {$data->form->rec->testId} AND #methodId = {$k}", 'id')) {
                $methodIdSelectArr[$k] = $v;
            }
        }
        
        // Ако сме в режим 'добави' избираме метод, който не е използван
        // за текущия тест. Ако сме в режим 'редактирай' полето за избор на метод е скрито.
        if ($data->form->rec->id) {
            // Prepare array for methodId 
            $data->form->setField('methodId', 'input=none');
        } else {
            $data->form->setOptions('methodId', $methodIdSelectArr);
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	// Заглавие
    	$testHandler = $mvc->Tests->fetchField($data->form->rec->testId, 'title');
    	
    	if ($data->form->rec->id) {
    		$data->form->title = "Редактиране за тест|* \"" . $testHandler . "\",";
    		$data->form->title .= "|*<br/>|метод|* \"" . $data->allMethodsArr[$data->form->rec->methodId] . "\"";
    	} else {
    		$data->form->title = "Добавяне на метод за тест|* \"" . $testHandler . "\"";
    	}
    }
    
    
    /**
     * Обработка на Master детайлите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        /*
        if ($rec->results != '') {
             $resultsArr = explode('<br>' , $row->results);

             // trim array elements
          foreach ($resultsArr as $k => $v) {
              $resultsArr[$k] = trim($v);
          }             
             
          foreach ($resultsArr as $element) {
              $resultsStyled .= $element . ", ";    
          }
          
          $resultsStyled = substr($resultsStyled, 0, strlen($resultsStyled) - 2);
          
          $row->results = $resultsStyled;
       }
       */
        
        // $row->value
        if(is_numeric($row->value)) {
            $row->value = "<div style='float: right'>" . number_format($row->value, 2, ',', ' ') . "</div>";
        } else {
            $row->value =  cls::get('type_Text')->toVerbal($rec->results);
        }
        
        // $row->parameterName
        $paramId = $mvc->Methods->fetchField("#id = '{$rec->methodId}'", 'paramId');
        $paramRec = $mvc->Params->fetch($paramId);
        $row->paramName = $paramRec->name . ($paramRec->dimension ? ', ' : '') . $paramRec->dimension;
        
        if($row->error) {
            $row->error = '±' . $row->error;
        }
        
        //$row->paramName = $paramName;
    }
    
    
    /**
     * Създаване $rec->value, $rec->error и запис на lastChangeOn в 'lab_Tests'
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        // Подготовка на масива за резултатите ($rec->results)
        $rec->results = str_replace("\r\n", "\n", $rec->results);
        $rec->results = str_replace("\n\r", "\n", $rec->results);
        $resultsArr = explode("\n", $rec->results);
        
        // trim array elements
        foreach ($resultsArr as $k => $v) {
            $resultsArr[$k] = cls::get('type_Double')->fromVerbal($v);
        }
        
        $methodsRec = $mvc->Methods->fetch($rec->methodId);
        $parametersRec = $mvc->Params->fetch($methodsRec->paramId);
        
        // BEGIN Обработки в зависимост от типа на параметъра
        if($parametersRec->type == 'number') {
            // намираме средното аритметично
            $sum = 0; $totalResults = 0;
            
            $resCnt = count($resultsArr);
            
            for($i = 0; $i < $resCnt; $i++) {
                if (trim($resultsArr[$i])) {
                    $sum += trim($resultsArr[$i]);
                    $totalResults++;
                }
            }
            
            $rec->value = 0;
            if (!empty($totalResults)) {
                $rec->value = $sum / $totalResults;
            }
            
            if ($resCnt > 1) {
                // Намираме грешката
                $dlt = 0;
                
                for($i = 0; $i < $resCnt; $i++) {
                    $dlt += ($resultsArr[$i] - $rec->value) * ($resultsArr[$i] - $rec->value);
                }
                
                $rec->error = sqrt($dlt) / sqrt((count($resultsArr) * (count($resultsArr)-1))) / $rec->value;
            } else {
                $rec->error = NULL;
            }
        } elseif ($parametersRec->type == 'bool') {
            $rec->value = $resultsArr[0] ;
            $rec->error = NULL;
        } elseif ($parametersRec->type == 'text') {
            $rec->value = $resultsArr[0] ;
            $rec->error = NULL;
        }
        
        // END Обработки в зависимост от типа на параметъра
        
        // Запис в 'lab_Tests'
        if($rec->testId) {
            $ltRec = new stdClass();
            $ltRec->lastChangedOn = DT::verbal2mysql();
            $ltRec->id = $rec->testId;
            $mvc->Tests->save($ltRec);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data, $rec)
    {
        $data->toolbar->removeBtn('btnPrint');
        
        // Count all methods
        $allMethodsQuery = $mvc->Methods->getQuery();
        
        $allMethodsQuery->where("1=1");
        
        $methodsAllCounter = 0;
        
        while($mRec = $allMethodsQuery->fetch()) {
            $methodsAllCounter++;
        }
        
        // END Count all methods
        
        // Count methods for this test
        $methodsQuery = $mvc->getQuery();
        
        $methodsQuery->where("#testId = {$rec->masterId}");
        
        $methodsCounter = 0;
        
        while($rec = $methodsQuery->fetch()) {
            $methodsCounter++;
        }
        
        // END Count methods        
    }
}