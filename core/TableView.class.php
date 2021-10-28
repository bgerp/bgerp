<?php


/**
 * Клас 'core_TableView' - Изглед за таблични данни
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_TableView extends core_BaseClass
{
    /**
     * ET шаблон за таблицата
     */
    public $tpl;
    
    
    /**
     * Инициализира се с информацията за MVC класа и шаблона
     */
    public function init($params = array())
    {
        parent::init($params);
        
        if (!$this->mvc) {
            $this->mvc = new core_Mvc();
        }
        
        $this->tpl = new ET($this->tpl);
    }
    
    
    /**
     * Връща масив с всички полета които имат плесйхолдър `COL_CLASS`
     *
     * @param array $rows
     *
     * @return array
     */
    protected function getColWithClass($rows)
    {
        return (array) $this->mvc->rowToolsColumn;
    }
    
    
    /**
     * Филтрира връща колоните, които трябва да се показват
     *
     * @param array $rows         - записи
     * @param mixed $fields       - масив или списък с колони, които ще се филтрират
     * @param mixed $filterFields - масив или списък с имена на колони, които могат да се скриват
     *
     * @return array $fields      - масив с филтрираните колони
     */
    public static function filterEmptyColumns($rows, $fields, $filterFields = '*')
    {
        // Имали колони в които ако няма данни да не се показват ?
        $fields = arr::make($fields, true);
        if ($filterFields == '*') {
            $filterFields = $fields;
        }
        
        $hideColumns = arr::make($filterFields, true);
        
        // За всяка колона, която може да се скрива
        foreach ($hideColumns as $name => $column) {
            $hide = true;
            
            // Ако има поне един запис със стойност за нея, не я скриваме
            if (is_array($rows)) {
                foreach ($rows as $id => $row) {
                    $row1 = (object) $row;

                    if (!empty($row1->{$name}) && !((is_object($row1->{$name}) && $row1->{$name} instanceof core_ET && !strlen($row1->{$name}->getContent())))) {
                        $hide = false;
                        break;
                    }
                }
            }
            
            // Ако не е намерен поне един запис със стойност за колоната, скриваме я
            if ($hide === true) {
                unset($fields[$name]);
            }
        }
        
        // Връщаме колоните, които ще се показват
        return $fields;
    }
    
    
    /**
     * Връща шаблон за таблицата
     */
    public function get($rows, $fields)
    {
        $fields = arr::make($fields, true);
        
        $header = array();
        $row = '<tr [#ROW_ATTR#]>';
        $addRows = '';
        $colspan = 0;
        $maxColHeaders = 1;
        $addRowArr = array();
        
        $i = 0;
        
        $fieldList = array();
        
        if (countR($fields)) {
            foreach ($fields as $name => $dummy) {
                if (!$dummy) {
                    unset($fields[$name]);
                    continue;
                }
                $fieldList[$name] = (float) $this->mvc->fields[$name]->column ? $this->mvc->fields[$name]->column : $i++;
                
                // Индикатор за сортиране
                if ($this->mvc->fields[$name]->sortable) {
                    $sortable[] = true;
                    $useSortingFlag = true;
                } else {
                    $sortable[] = false;
                }
            }
            
            if (countR($fieldList)) {
                asort($fieldList);
            }
        }
        
        if (countR($fieldList)) {
            foreach ($fieldList as $place => $columnOrder) {
                $colHeaders = $fields[$place];
                
                if (is_string($colHeaders)) {
                    $colHeaders = explode('->', $colHeaders);
                }
                
                $maxColHeaders = max(countR($colHeaders), $maxColHeaders);
                
                $fields[$place] = $colHeaders;
            }
            
            $colWithClass = $this->getColWithClass($rows);
            
            foreach ($fieldList as $place => $dummy) {
                $colHeaders = $fields[$place];
                $fieldObj = new stdClass();
                if(isset($this->mvc->fields[$place])) {
                    $fieldObj = $this->mvc->fields[$place];
                }
                if ($colHeaders[0][0] != '@' && !isset($fieldObj->singleRow)) {
                    
                    // Задаваме класа на колоната
                    $class = '';
                    
                    if (is_object($this->mvc->fields[$place]->type)) {
                        $tdClass = $class = $this->mvc->fields[$place]->type->getTdClass();
                        if ($this->mvc->fields[$place]->smartCenter) {
                            $tdClass = '';
                        }
                    } else {
                        $tdClass = '';
                    }
                    
                    if ($this->mvc->fields[$place]->tdClass) {
                        $class .= ' ' . $this->mvc->fields[$place]->tdClass;
                    }
                    
                    if ($colWithClass[$place]) {
                        $class .= " {$colWithClass[$place]}";
                    }
                    
                    if (($place[0] == '_')) {
                        $class .= ' centerCol';
                    }
                    
                    if ($class = trim($class)) {
                        $attr = " class=\"{$class}\"";
                    } else {
                        $attr = '';
                    }
                    
                    foreach ($colHeaders as $i => $name) {
                        $name = tr($name);
                        
                        if (($i < (countR($colHeaders) - 1)) || ($i == ($maxColHeaders - 1))) {
                            $rowspan = 1;
                        } else {
                            $rowspan = $maxColHeaders - $i;
                        }
                        
                        $last = countR($header[$i]) - 1;
                        
                        if ($header[$i][$last]->name == $name && $header[$i][$last]->rowspan == $rowspan) {
                            if (!$header[$i][$last]->colspan) {
                                if (!isset($header[$i][$last])) {
                                    $header[$i][$last] = new stdClass();
                                }
                                $header[$i][$last]->colspan = 1;
                            }
                            $header[$i][$last]->colspan = 1 + $header[$i][$last]->colspan;
                        } else {
                            if (!isset($header[$i][$last + 1])) {
                                $header[$i][$last + 1] = new stdClass();
                            }
                            $header[$i][$last + 1]->name = $name;
                            $header[$i][$last + 1]->rowspan = $rowspan;
                            $header[$i][$last + 1]->tdClass = $tdClass;
                        }
                    }
                    
                    // Шаблон за реда
                    
                    if ($this->mvc->fields[$place]->smartCenter) {
                        static $dataCol;
                        $dataCol++;
                        $row .= "<td{$attr}><span class='maxwidth' data-col='{$dataCol}'>[#{$place}#]</span></td>";
                    } else {
                        $row .= "<td{$attr}>[#{$place}#]</td>";
                    }
                    
                    $colspan++;
                } else {
                    $tdClass = $attr = '';
                    if ($this->mvc->fields[$place]->tdClass) {
                        $tdClass = $this->mvc->fields[$place]->tdClass;
                        $attr = " class=\"{$tdClass}\"";
                    }
                    
                    // Допълнителни цели редове, ако колоната няма заглавие
                    $addRows .= "<tr [#COMMON_ROW_ATTR#]><td {$attr} colspan=\"[#COLSPAN#]\">[#{$place}#]</td></tr>\n";
                    $addRowArr[$place] = "<tr [#COMMON_ROW_ATTR#]><td {$attr} colspan=\"[#COLSPAN#]\">[#{$place}#]</td></tr>\n";
                }
            }
        }
        
        
        $curTH = 0;
        
        if (countR($header)) {
            foreach ($header as $i => $headerRow) {
                if ($i == countR($header) - 1) {
                    $lastRowStart = $curTH;     // Започва последният хедър
                    $lastRowFlag = true;
                }
                
                $headerRowCnt = countR($headerRow);
                $j = 0;
                foreach ($headerRow as $h) {
                    $attr = array();
                    
                    if ($lastRowFlag) {
                        if ($h->tdClass) {
                            $attr['class'] = $h->tdClass;
                        }
                    }
                    
                    if ($h->rowspan > 1) {
                        $attr['rowspan'] = $h->rowspan;
                    }
                    
                    if ($h->colspan > 1) {
                        $attr['colspan'] = $h->colspan;
                    }
                    $th = ht::createElement('th', $attr, $h->name);
                    
                    $hr[$i] .= $th->getContent();
                    
                    $curTH++;
                }
            }
            
            foreach ($hr as $h) {
                $tableHeader .= "\n<tr>{$h}\n</tr>";
            }
        }
        
        $addRows = str_replace('[#COLSPAN#]', $colspan, $addRows);
        foreach ($addRowArr as &$addRow) {
            $addRow = str_replace('[#COLSPAN#]', $colspan, $addRow);
        }
        
        $this->colspan = $colspan;
        
        $row .= '</tr>';
        
        if (isset($this->mvc->tableRowTpl)) {
            $tableRowTpl = $this->mvc->tableRowTpl;
        } else {
            $tableRowTpl = "<tbody class='rowBlock'>[#ROW#][#ADD_ROWS#]</tbody>\n";
        }
        
        $row = str_replace(array('[#ROW#]', '[#ADD_ROWS#]'), array($row, $addRows), $tableRowTpl);
        
        $row = "\n<!--ET_BEGIN ROW-->{$row}<!--ET_END ROW-->";
        if (!$this->tableClass) {
            $this->tableClass = 'listTable';
        }
        
        $tableId = '';
        if (isset($this->tableId)) {
            $tableId = " id = \"{$this->tableId}\"";
        }
        
        $theadStyle = ($this->thHide === true) ? 'style="display:none"' : '';
        $tpl = new ET("\n<table [#TABLE_ATTR#] {$tableId} class=\"{$this->tableClass}\"><thead {$theadStyle}>{$tableHeader}</thead>[#ROW_BEFORE#]{$row}[#ROW_AFTER#]</table>\n");
        
        if (countR($rows)) {
            foreach ($rows as $r) {
                $rowTpl = $tpl->getBlock('ROW');
                
                if ($r instanceof core_Et) {
                    $rowTpl->replace($r);
                }
                
                if (is_object($r)) {
                    $r = get_object_vars($r);
                }
                
                foreach ($fieldList as $name => $dummy) {
                    $value = $r[$name];
                    
                    if (isset($addRowArr[$name]) && $value == '') {
                        $rowTpl->content = str_replace($addRowArr[$name], '', $rowTpl->content);
                    }
                    
                    if ($value === null) {
                        $value = '&nbsp;';
                    }
                    
                    $rowTpl->replace($value, $name);
                }
                
                // Добавяме атрибутите на реда от таблицата, ако има такива
                if (countR($r['ROW_ATTR'])) {
                    $attrs = $attrs1 = '';
                    
                    
                    foreach ($r['ROW_ATTR'] as $attrName => $attrValue) {
                        $attrs .= " ${attrName}=\"{$attrValue}\"";
                    }
                    
                    if ($this->mvc->commonRowClass) {
                        $r['ROW_ATTR']['class'] .= ' ' . $this->mvc->commonRowClass;
                    }
                    
                    foreach ($r['ROW_ATTR'] as $attrName => $attrValue) {
                        $attrs1 .= " ${attrName}=\"{$attrValue}\"";
                    }
                    
                    $rowTpl->replace($attrs, 'ROW_ATTR', false, false);
                    $rowTpl->replace($attrs1, 'COMMON_ROW_ATTR', false, false);
                } else {
                    $rowTpl->replace(" class='{$this->mvc->commonRowClass}'", 'COMMON_ROW_ATTR', false, false);
                }
                
                $rowTpl->append2Master();
            }
        } else {
            $rowTpl = $tpl->getBlock('ROW');
            $tpl->append(new ET('<!--ET_BEGIN NO_ROWS-->[#NO_ROWS#]<!--ET_END NO_ROWS-->'), 'ROW');
            $tpl->append('<tr><td colspan="' . $this->colspan . '"> ' . tr('Няма записи') . ' </td></tr>', 'NO_ROWS');
        }
        
        if ($this->rowBefore) {
            $rowBefore = new ET('<tr><td style="border:0px; padding-top:5px; " colspan="' . $this->colspan . '">[#1#]</td></tr>', $this->rowBefore);
            $tpl->replace($rowBefore, 'ROW_BEFORE');
        }
        
        if ($this->rowAfter) {
            $rowAfter = new ET('<tr><td style="border:0px; padding-top:5px; " colspan="' . $this->colspan . '">[#1#]</td></tr>', $this->rowAfter);
            $tpl->replace($rowAfter, 'ROW_AFTER');
        }
        
        return $tpl;
    }
}
