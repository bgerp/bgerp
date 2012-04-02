<?php



/**
 * Клас 'plg_Chart' - Показва графики, вместо таблични данни
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_Chart extends core_Plugin
{
    
    
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListTitle($mvc, $data)
    {
        // Намираме полетата, дефинирани като оста Х
        $xFieldArr = $mvc->selectFields("#chart == 'ax'");
        
        // Очакваме да има само едно такова поле
        expect(count($xFieldArr) == 1);
        
        // Вземаме полето
        $xField = current($xFieldArr);
        
        $colRec = new stdClass();
        $colRec->type = 'string';
        $colRec->title = $data->listFields[$xField->name];
        $colRec->name = $xField->name;
        
        $data->chartColumns[0] = $colRec;
        
        // Намираме полетата, дефинирани като оста У
        $yFieldArr = $mvc->selectFields("#chart == 'ay'");
        
        // Намираме полетата дефиниращи серии
        $sFieldArr = $mvc->selectFields("#chart == 'series'");
        
        // Серии могат да се дефинират по точно един от следните два начина:
        // 
        // 1. Да има поле с chart==series. Тогава различните стойности на 
        //    това поле се явяват различните серии
        //
        // 2. Да има няколко полета, дефинирани като chart=ay. Тези ралични 
        //    полета се явяват различните серии
        
        if(count($sFieldArr) && count($data)>1) {
            
            $yField = current($yFieldArr);
            expect(count($yFieldArr) == 1);
            expect(($yField->type instanceof type_Int) || ($yField->type instanceof type_Double));
            
            $sField = current($sFieldArr);
            expect(count($sFieldArr) == 1);
            
            $sName = $data->chartSeriesField = $sField->name;
            
            // Правим масив с различните серии
            foreach($data->recs as $id => $rec)
            {
                $sVal[$rec->{$sName}] = $data->rows[$id]->{$sName};
            }
            
            foreach($sVal as $series => $caption) {
                $colRec = new stdClass();
                $colRec->type = 'number';
                $colRec->title = (string) $caption;
                $colRec->name = $yField->name;
                $colRec->seriesKey = $series;
                
                $data->chartColumns[] = $colRec;
            }
        } else {
            
            expect(count($yFieldArr));
            
            foreach($yFieldArr as $yField) {
                $colRec = new stdClass();
                $colRec->type = 'number';
                expect(($yField->type instanceof type_Int) || ($yField->type instanceof type_Double));
                $colRec->title = $data->listFields[$yField->name];
                $colRec->name = $yField->name;
                $data->chartColumns[] = $colRec;
            }
        }
        
        // Намираме полетата, дефинирани като разграничаващи различните графики
        $diffFieldArr = $mvc->selectFields("#chart == 'diff'");
        
        if(count($diffFieldArr) && count($data->rows)) {
            expect(count($diffFieldArr) == 1);
            
            $diffField = current($diffFieldArr);
            
            $diffField = $data->chartField = $diffField->name;
            
            foreach($data->rows as $row) {
                
                $data->charts[$row->{$diffField}] = $row->{$diffField};
            }
        }
        
        // Подготвяме видовете графики
        $chartTypes = array(
            'LineChart' => 'Линии',
            'AreaChart' => 'Площ',
            'PieChart' => 'Торта',
        );
        
        $data->chartTypes = $chartTypes;
        
        //bp($data->chartColumns);
    
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getChart($data, $chartName, $chartType, $chartCaption, $chartField = NULL)
    {
        $chart = ht::createElement('div', array('id' => $chartName), NULL, TRUE);
        
        $chart->appendOnce("\n <script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>", "HEAD");
        
        // Добавяме началото на функцията
        $chart->appendOnce("\n google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});", "SCRIPTS");
        
        $chart->append("\n google.setOnLoadCallback(draw{$chartName});" .
            "\n function draw{$chartName}() {" .
            "\n     var data = new google.visualization.DataTable();",
            "SCRIPTS");
        
        // Масив в който слагаме различните стойности по оста X
        $rows = array();
        
        $dataTpl = new ET();
        
        $usedCols = array();
        
        // Добавяме данните
        foreach($data->recs as $id => $rec) {
            foreach($data->chartColumns as $col => $colRec) {
                
                if($chartField) {
                    if($data->rows[$id]->{$chartField} != $chartCaption) {
                        continue;
                    }
                }
                
                $field = $colRec->name;
                
                if($colRec->type == 'number') {
                    $value = (float) $rec->{$field};
                } else {
                    $value = "'" . $data->rows[$id]->{$field} . "'";
                }
                
                if($col == 0) {
                    if($rows[$value]) continue;
                    $rows[$value] = TRUE;
                } else {
                    // Ако имаме серии, то ако стойността на полето, определящо сериите
                    // е различна от стойността на ключът за сериите на текущата колонка
                    // пропускаме да добавим данните
                    if($sField = $data->chartSeriesField) {
                        if($rec->{$sField} != $colRec->seriesKey) {
                            
                            continue;
                        }
                    }
                }
                
                $row = count($rows)-1;
                
                if($usedCols[$col]) {
                    $colNumb = $usedCols[$col]->colNumb;
                } else {
                    $colNumb = count($usedCols);
                    $colRec->colNumb = $colNumb;
                    $usedCols[$col] = $colRec;
                }
                
                $dataTpl->append("\n     data.setValue({$row}, {$colNumb}, {$value});", 'SCRIPTS');
            }
        }
        
        // Добавяме колоните
        foreach($usedCols as $colRec) {
            $chart->append("\n     data.addColumn('{$colRec->type}', '{$colRec->title}');", 'SCRIPTS');
        }
        
        // Добавяме редовете
        $rowsCnt = count($rows);
        $chart->append("\n     data.addRows({$rowsCnt});", 'SCRIPTS');
        
        // Добавяме данните
        $chart->append($dataTpl);
        
        // Добавяме завършека на функцията
        $chart->append("\n     var chart = new google.visualization.{$chartType}(document.getElementById('{$chartName}'));" .
            "\n     chart.draw(data, " . json_encode(array('width' => 800, 'height' => 480, 'title' => $chartCaption)) . ");" .
            "\n }", 'SCRIPTS');
        
        return $chart;
    }
    
    
    /**
     * Извиква се след рендирането на таблицата от табличния изглед
     */
    function on_BeforeRenderListTable($mvc, &$table, $data)
    {
        if($chartType = Request::get('Chart')) {
            
            $chartId = 0;
            
            if(count($data->charts)) {
                
                $table = new ET();
                $chartField = $data->chartField;
                
                foreach($data->charts as $chartCaption) {
                    $chartName = 'Chart' . $chartId++;
                    $table->append($this->getChart($data, $chartName, $chartType, $chartCaption, $chartField));
                }
            } else {
                $chartName = 'Chart' . $chartId;
                $chartCaption = '';
                
                $table = $this->getChart($data, $chartName, $chartType, $chartCaption);
            }
            
            return FALSE;
        }
    }
    
    
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterRenderListTitle($mvc, &$title, $data)
    {
        if(count($data->chartTypes)) {
            $title = new ET('[#1#]', $title);
            
            // $title->prepend("<div style='float:left;'>");
            // $title->append("</div>");
            
            $title->append('<div style="margin-top:5px;margin-bottom:5px;font-size:0.80em;font-family:arial;" id="chartMenu">', 'ListSummary');
            
            $chartType = Request::get('Chart');
            
            if($chartType) {
                $url = getCurrentUrl();
                unset($url['Chart']);
                $title->append(ht::createLink(tr('Tаблица'), $url) , 'ListSummary');
            } else {
                $title->append(tr('Tаблица') , 'ListSummary');
            }
            
            foreach($data->chartTypes as $type => $caption)
            {
                $title->append("&nbsp;|&nbsp;", 'ListSummary');
                
                $url = getCurrentUrl();
                
                if($url['Chart'] != $type) {
                    $url['Chart'] = $type;
                    $title->append(ht::createLink(tr($caption), $url) , 'ListSummary');
                } else {
                    $title->append(tr($caption), 'ListSummary');
                }
            }
            
            $title->append('</div>', 'ListSummary');
        }
    }
}