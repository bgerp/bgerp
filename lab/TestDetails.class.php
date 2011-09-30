<?php 

/**
 * Менаджира детайлите на тестовете (Details)
 */
class lab_TestDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Детайли на тест";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, 
                          plg_Printing, lab_Wrapper, plg_Sorting, 
                          Tests=lab_Tests, Params=lab_Parameters, Methods=lab_Methods';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'testId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'tools=Ред,methodId,paramName,value,error';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $tabName = "lab_Tests";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('testId', 'key(mvc=lab_Tests, select=handler)', 'caption=Тест, input=hidden, silent');
        $this->FNC('paramName', 'varchar(255)', 'caption=Параметър, notSorting');
        $this->FLD('methodId', 'key(mvc=lab_Methods, select=name)', 'caption=Метод, notSorting');
        $this->FLD('value', 'varchar(64)', 'caption=Ср. стойност, notSorting, input=none');
        $this->FLD('error', 'double', 'caption=Грешка, notSorting,input=none');
        $this->FLD('results', 'text', 'caption=Резултати, notSorting, column=none');
        
        $this->setDbUnique('testId, methodId');
    }
    
    
    /**
     * Променя заглавието и добавя стойност по default в селекта за избор на тест
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        // allMethodsArr
        $Methods = cls::get('lab_Methods');
        $queryAllMethods = $Methods->getQuery();
        
        while($mRec = $queryAllMethods->fetch("1=1")) {
            $allMethodsArr[$mRec->id] = $mRec->name;
        }
        
        // $methodIdSelectArr
        foreach ($allMethodsArr as $k => $v) {
            if (!$this->fetchField("#testId = {$data->form->rec->testId} AND #methodId = {$k}", 'id')) {
                $methodIdSelectArr[$k] = $v;
            }
        }
        
        // Заглавие
        $testHandler = $mvc->Tests->fetchField($data->form->rec->testId, 'handler');
        
        if ($data->form->rec->id) {
            $data->form->title = "Редактиране за тест \"" . $testHandler . "\",";
            $data->form->title .= "<br/>метод \"" . $allMethodsArr[$data->form->rec->methodId]."\"";
        } else {
            $data->form->title = "Добавяне на метод за тест \"" . $testHandler . "\"";
        }
        // END Заглавие
        
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
     *  Обработка на Master детайлите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
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
        $row->value = "<div style='float: right'>" . number_format($row->value, 2, ',', ' ') . "</div>";
        
        // $row->parameterName
        $paramId = $mvc->Methods->fetchField("#id = '{$rec->methodId}'", 'paramId');
        $paramName = $mvc->Params->fetchField("#id = '{$paramId}'", 'name');
        $row->paramName = $paramName;
    }
    
    
    /**
     * Създаване $rec->value, $rec->error и запис на lastChangeOn в 'lab_Tests'
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_BeforeSave($mvc, &$id, $rec)
    {
        // Подготовка на масива за резултатите ($rec->results)
        $rec->results = str_replace("\r\n", "\n", $rec->results);
        $rec->results = str_replace("\n\r", "\n", $rec->results);
        $resultsArr = explode("\n", $rec->results);
        
        // trim array elements
        foreach ($resultsArr as $k => $v) {
            $resultsArr[$k] = trim($v);
        }
        
        $methodsRec = $mvc->Methods->fetch($rec->methodId);
        $parametersRec = $mvc->Params->fetch($methodsRec->paramId);
        
        // BEGIN Обработки в зависимост от типа на параметъра
        if($parametersRec->type == 'number') {
            // намираме средното аритметично
            $sum = 0; $totalResults = 0;
            
            for($i = 0; $i<count($resultsArr); $i++) {
                if (trim($resultsArr[$i])) {
                    $sum += trim($resultsArr[$i]);
                    $totalResults++;
                }
            }
            $rec->value = $sum/$totalResults;
            
            if(count($resultsArr)>1) {
                // Намираме грешката
                for($i = 0; $i<count($resultsArr); $i++) {
                    $dlt += ($resultsArr[$i] - $rec->value) * ($resultsArr[$i] - $rec->value);
                }
                
                $rec->error = sqrt($dlt)/sqrt((count($resultsArr)*(count($resultsArr)-1)));
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
            $ltRec->lastChangedOn = DT::verbal2mysql();
            $ltRec->id = $rec->testId;
            $mvc->Tests->save($ltRec);
        }
    }
    
    
    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, $data, $rec)
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
    
    /*
    function renderDetailLayout_($data)
    {
        $listLayout = "
            [#ListTable#]
            [#ListSummary#]
            [#ListToolbar#]
        ";
        
        if ($this->listStyles) {
            $listLayout = "\n<style>\n" . $this->listStyles . "\n</style>\n" . $listLayout;
        }

        $listLayout = ht::createLayout($listLayout);

        return $listLayout;
    }
    */

}