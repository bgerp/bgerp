<?php

/**
 * Мениджира детайлите на тестовете (Details)
 *
 *
 * @category  bgerp
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 *            Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class lab_TestDetails extends core_Detail
{

    /**
     * Заглавие
     */
    public $title = 'Детайли на тест';

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_RowNumbering,
                          plg_Printing, lab_Wrapper, plg_Sorting, 
                          Tests=lab_Tests, Params=lab_Parameters, Methods=lab_Methods,plg_PrevAndNext, plg_SaveAndNew';

    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'testId';

    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'methodId,paramName,value,comment=@Коментар';

    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,masterLab';

    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'ceo,masterLab';

    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,masterLab';

    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    public $tabName = 'lab_Tests';

    /**
     * Роли, които могат да записват
     */
    public $canWrite = 'ceo,masterLab';

    /**
     * Преди подготовката на полетата за листовия изглед
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if (Request::get('Rejected', 'int')) {
            $data->listFields['state'] = 'Състояние';
        }
    }

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD(
            'testId',
            'key(mvc=lab_Tests, select=title)',
            'caption=Тест, input=hidden, silent,mandatory,smartCenter'
        );
        $this->FLD(
            'paramName',
            'key(mvc=lab_Parameters, select=name, allowEmpty)',
            'caption=Параметър, notSorting,smartCenter,silent,refreshForm'
        );
        $this->FLD(
            'methodId',
            'key(mvc=lab_Methods, select=name)',
            'caption=Метод, notSorting,mandatory,smartCenter,silent,refreshForm'
        );
        $this->FLD('value', 'varchar(64)', 'caption=Стойност, notSorting, input=none,smartCenter');
        $this->FLD('refValue', 'varchar(64)', 'caption=Реф.Стойност, notSorting, input=none,smartCenter');
        $this->FLD('error', 'percent(decimals=2)', 'caption=Отклонение, notSorting,input=none,smartCenter');
        $this->FLD('formula', 'text', 'caption=Формула,input=hidden');
        $this->FLD(
            'comment',
            'varchar',
            'caption=Коментари, notSorting,after=results, column=none,class=" w50, rows= 1"'
        );
        
        $this->FLD('better', 'enum(up=по-големия,down=по-малкия)', 'caption=По-добрия е,unit= резултат,after=title');
        
        $this->FLD(
        
            'results',
        
            'table(columns= value ,captions=Стойност,widths=8em)',
            'caption=Измервания||Additional,autohide,advanced,after=title,single=none'
        
        );
        
        $this->setDbUnique('testId, methodId');
    }

    /**
     * Променя заглавието и добавя стойност по default в селекта за избор на тест
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        $type = $form->getFieldType('results');
        
        if ($rec->methodId && lab_Methods::fetchField($rec->methodId, 'formula')) {
            if (! $rec->formula) {
                $rec->formula = lab_Methods::fetchField($rec->methodId, 'formula');
            }
            
            $formula = $rec->formula;
            
            if ($formula !== lab_Methods::fetchField($rec->methodId, 'formula')) {
                $form->setWarning('methodId', 'Има промяна във формулата, която няма бъде отчетена.');
            }
            
            $matches = array();
            
            preg_match_all('/\$[_a-z][a-z0-9_]*/i', $formula, $matches);
            
            foreach ($matches[0] as $var) {
                $params .= $var . '|';
                $widths .= '8em' . '|';
                $type->params['columns'] = trim($params, '|');
                $type->params['captions'] = trim($params, '|');
                $type->params['widths'] = trim($widths, '|');
            }
        }
        $paramsIdSelectArr = array(
            $data->form->rec->paramName => lab_Parameters::getTitleById($data->form->rec->paramName)
        );
        
        // allMethodsArr
        $Methods = cls::get('lab_Methods');
        $queryAllMethods = $Methods->getQuery();
        
        $allMethodsArr = array();
        
        if ($data->form->rec->paramName) {
            while ($mRec = $queryAllMethods->fetch("#paramId = {$data->form->rec->paramName}")) {
                $allMethodsArr[$mRec->id] = $mRec->name;
            }
        }
      
        $methodIdSelectArr = array();
        $data->allMethodsArr = $allMethodsArr;
        
        // $methodIdSelectArr
        foreach ($allMethodsArr as $k => $v) {
            if (! $mvc->fetchField("#testId = {$data->form->rec->testId} AND #methodId = {$k}", 'id')) {
                $methodIdSelectArr[$k] = $v;
            }
        }
        
        // Ако сме в режим 'добави' избираме метод, който не е използван
        // за текущия тест. Ако сме в режим 'редактирай' полето за избор на метод е скрито.
        
        if ($data->form->rec->id) {
            // Prepare array for methodId
            $data->form->setField('methodId', 'input=none');
        } else {
            if (! $data->form->rec->paramName) {
                $data->form->setField('methodId', 'input=none');
            }
            
            if (empty($methodIdSelectArr) && !empty($data->form->rec->paramName)) {
                $data->form->setError('paramName', 'За този параметър няма регистрирани методи.');
                $data->form->setField('methodId', 'input=none');
            } else {
                $data->form->setOptions('methodId', array(' ' => 'избери метод ') + $methodIdSelectArr);
            }
        }
    }

    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        
        // Заглавие
        $testHandler = lab_Tests::getHandle($data->masterId) . lab_Tests::fetchField($data->form->rec->testId, 'title');
        
        if ($data->form->rec->id) {
            $data->form->title = 'Редактиране за тест|* "' . $testHandler . '",';
            $data->form->title .= '|*<br/>|метод|* "' . $data->allMethodsArr[$data->form->rec->methodId] . '"';
        } else {
            $data->form->title = 'Добавяне на метод за тест|* "' . $testHandler . '"';
        }
    }

    /**
     * Обработка на Master детайлите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        
        // $row->value
        if (is_numeric($row->value)) {
            // $row->value = "<div style='float: right'>" . number_format($row->value, 2, ',', ' ') . "</div>";
            $row->value = core_Type::getByName('double(decimals=2)')->toVerbal($rec->value);
        } else {
            $row->value = cls::get('type_Text')->toVerbal($rec->results);
        }
        
        if (($rec->value == '---')) {
            $row->value = '---';
        }
        
        $paramId = $mvc->Methods->fetchField("#id = '{$rec->methodId}'", 'paramId');
        $paramRec = $mvc->Params->fetch($paramId);
        $row->paramName = $paramRec->name . ($paramRec->dimension ? ', ' : '') . $paramRec->dimension;
        
        $row->refValue = '---';
        $row->error = '---';
    }

    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        $rows = &$res->rows;
        $recs = &$res->recs;
        
        $compTest = Mode::get('testCompare_' . lab_Tests::getHandle($data->masterId));
        if ($compTest) {
            array(
                $data->listFields['refValue'] = 'Реф.Стойност'
            );
            array(
                $data->listFields['error'] = 'Отклонение'
            );
            
            $dQuery = lab_TestDetails::getQuery();
            $dQuery->where("#testId = {$compTest}");
            
            while ($testsDet = $dQuery->fetch()) {
                foreach ($rows as $key => $row) {
                    if ($compTest) {
                        if ($recs[$key]->methodId == $testsDet->methodId) {
                            $row->refValue = "<div style='float: right'>" . number_format($testsDet->value, 2, ',', ' ') .
                                 '</div>';
                            
                            
                            $recs[$key]->refValue = $testsDet->value;
                           
                            
                            if ($recs[$key]->refValue) {
                                $deviation = core_Type::getByName('percent')->toVerbal(
                                    ($recs[$key]->value - $recs[$key]->refValue) / $recs[$key]->refValue
                                );
                            } else {
                                $deviation = '---';
                            }
                        
                            if ($testsDet->better) {
                                $recs[$key]->better = $testsDet->better;
                                
                                if ($recs[$key]->better == 'up' && $recs[$key]->value >= $recs[$key]->refValue) {
                                    // $row->error = ht::styleIfNegative($deviation, $deviation);
                                    $row->error = "<div style='float: right;color: green'>" .
                                         number_format($deviation, 2, ',', ' ') . '%' . '</div>';
                                }
                                if ($recs[$key]->better == 'up' && $recs[$key]->value < $recs[$key]->refValue) {
                                    $row->error = "<div style='float: right;color: red'>" .
                                         number_format($deviation, 2, ',', ' ') . '%' . '</div>';
                                }
                                
                                
                                if ($recs[$key]->better == 'down' && $recs[$key]->value <= $recs[$key]->refValue) {
                                    $row->error = "<div style='float: right;color: green'>" .
                                         number_format($deviation, 2, ',', ' ') . '%' . '</div>';
                                }
                               
                                
                                if ($recs[$key]->better == 'down' && $recs[$key]->value > $recs[$key]->refValue) {
                                    $row->error = "<div style='float: right;color: red'>" .
                                         number_format($deviation, 2, ',', ' ') . '%' . '</div>';
                                }
                            } else {
                                $row->error = "<div style='float: right;color: black'>" .
                                     number_format($deviation, 2, ',', ' ') . '%' . '</div>';
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Създаване $rec->value, $rec->error и запис на lastChangeOn в 'lab_Tests'
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param stdClass $rec
     */
    public static function on_BeforeSave($mvc, &$id, $rec)
    {
        
        // Подготовка на масива за резултатите ($rec->results)
        $resultsArr = json_decode($rec->results);
        
       
        
        if ($rec->methodId && lab_Methods::fetchField($rec->methodId, 'formula')) {
            $resArr = $resultsArr;
            $resultsArr = array();
            
            $formula = $rec->formula;
            
            preg_match_all('/\$[_a-z][a-z0-9_]*/i', $formula, $matches);
            
            $check = $matches[0][0];
            $i = 0;
            
            do {
                $contex = array();
                
                foreach ($matches[0] as $v) {
                    $contex += array(
                        $v => $resArr->{$v}[$i]
                    );
                }
                
                if (($expr = str::prepareMathExpr($formula, $contex)) !== false) {
                    $value = str::calcMathExpr(str::prepareMathExpr($expr, $contex), $success);
                    
                    if ($success === false) {
                        $value = tr('Невъзможно изчисление');
                    }
                } else {
                    $value = tr('Некоректна формула');
                }
                
                $resultsArr[] = $value;
                
                $i ++;
            } while ($resArr->{$check}[$i]);
        } else {
            $resultsArr = $resultsArr->value;
        }
       
        // trim array elements
        if (is_array($resultsArr)) {
            foreach ($resultsArr as $k => $v) {
                $resultsArr[$k] = cls::get('type_Double')->fromVerbal($v);
            }
        }
        
        $methodsRec = $mvc->Methods->fetch($rec->methodId);
        $parametersRec = $mvc->Params->fetch($methodsRec->paramId);
        
        // BEGIN Обработки в зависимост от типа на параметъра
        if ($parametersRec->type == 'number') {
            // намираме средното аритметично
            $sum = 0;
            $totalResults = 0;
        
            $resCnt = count($resultsArr);
         
            for ($i = 0; $i < $resCnt; $i ++) {
                if (trim($resultsArr[$i])) {
                    $sum += trim($resultsArr[$i]);
                    $totalResults ++;
                }
            }
       
            $rec->value = 0;
            if (! empty($totalResults)) {
                $rec->value = $sum / $totalResults;
            } else {
                $rec->value = '---';
            }
        } elseif ($parametersRec->type == 'bool') {
            $rec->value = $resultsArr[0];
            $rec->error = null;
        } elseif ($parametersRec->type == 'text') {
            $rec->value = $resultsArr[0];
            $rec->error = null;
        }
        
        // END Обработки в зависимост от типа на параметъра
    }

    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, $data, $rec)
    {
        $options = array(
            '' => 'избери параметър'
        );
        
        $data->toolbar->removeBtn('btnPrint');
        
        $parameters = array();
        
        $parameters = keylist::toArray(lab_Tests::fetch($data->masterId)->parameters);
        
        foreach ($parameters as $key => $v) {
            $paramName = lab_Parameters::getTitleById($parameters[$v]);
            
            $url = toUrl(
                array(
                    $mvc,
                    'add',
                    'testId' => $data->masterId,
                    'paramName' => $v
                )
            
            );
            
            $options[$url] = $paramName;
        }
        
        if (core_Users::haveRole('masterLab,ceo')) {
            if ($data->masterData->rec->state == 'pending') {
                $data->toolbar->addSelectBtn($options);
            }
        }
        // Count all methods
        $allMethodsQuery = $mvc->Methods->getQuery();
        
        $allMethodsQuery->where('1=1');
        
        $methodsAllCounter = 0;
        
        while ($mRec = $allMethodsQuery->fetch()) {
            $methodsAllCounter ++;
        }
        
        // END Count all methods
        
        // Count methods for this test
        $methodsQuery = $mvc->getQuery();
        
        $methodsQuery->where("#testId = {$rec->masterId}");
        
        $methodsCounter = 0;
        
        while ($rec = $methodsQuery->fetch()) {
            $methodsCounter ++;
        }
        
        // END Count methods
    }

    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'edit' || $action == 'add') {
            if (is_object($rec) && $rec->testId) {
                $state = lab_Tests::fetchField("#id = {$rec->testId}", 'state');
                
                if ($state != 'pending') {
                    $requiredRoles = 'no_one';
                    
                    return;
                }
            }
        }
    }

    /**
     * Изчислява израза
     *
     * @param  $expr
     *            - формулата
     * @param  array  $params
     *                        - параметрите
     * @return string $res - изчисленото количество
     */
    public static function calcExpr($expr, $params)
    {
        $expr = lab_Methods::fetchField($rec->methodId, 'formula');
        
        $contex = array(
            $width => 1,
            $height => 1,
            $weight => 1
        );
        
        if (str::prepareMathExpr($expr) === false) {
            $res = self::CALC_ERROR;
        } else {
            $res = str::calcMathExpr($expr, $success);
            if ($success === false) {
                $res = self::CALC_ERROR;
            }
        }
        
        return $res;
    }
}
