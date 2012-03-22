<?php



/**
 * Клас 'core_TableView' - Изглед за таблични данни
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_TableView extends core_BaseClass
{
    
    
    /**
     * ET шаблон за таблицата
     */
    var $tpl;
    
    
    /**
     * Инициализира се с информацията за MVC класа и шаблона
     */
    function init($params = array())
    {
        parent::init($params);
        
        if (!$this->mvc) {
            $this->mvc = new core_Mvc();
        }
        
        $this->tpl = new ET($this->tpl);
    }
    
    
    /**
     * Връща шаблон за таблицата
     */
    function get($rows, $fields)
    {
        $fields = arr::make($fields, TRUE);
        
        $row = "\n<!--ET_BEGIN ROW--><tr [#ROW_ATTR#]>";
        $addRows = "";
        $colspan = 0;
        $maxColHeaders = 1;
        
        $i = 0;
        
        if (count($fields)) {
            foreach ($fields as $name => $dummy) {
                if(!$dummy) {
                    unset($fields[$name]); continue;
                }
                $fieldList[$name] = (float) $this->mvc->fields[$name]->column ? $this->mvc->fields[$name]->column : $i++;
                
                // Индикатор за сортиране
                if ($this->mvc->fields[$name]->sortable) {
                    $sortable[] = TRUE;
                    $useSortingFlag = TRUE;
                } else {
                    $sortable[] = FALSE;
                }
            }
            
            if (count($fieldList)) {
                asort($fieldList);
            }
        }
        
        if(count($fieldList)) {
            foreach ($fieldList as $place => $columnOrder) {
                
                $colHeaders = $fields[$place];
                
                if (is_string($colHeaders)) {
                    $colHeaders = explode('->', $colHeaders);
                }
                
                $maxColHeaders = max(count($colHeaders), $maxColHeaders);
                
                $fields[$place] = $colHeaders;
            }
            
            foreach ($fieldList as $place => $dummy) {
                
                $colHeaders = $fields[$place];
                
                if ($colHeaders[0]{0} != '@') {
                    // Задаваме класа на колоната
                    if (is_object($this->mvc->fields[$place]->type)) {
                        $attr = " " . $this->mvc->fields[$place]->type->getCellAttr() . " ";
                    } else {
                        $attr = '';
                    }
                    
                    if($this->mvc->fields[$place]->tdClass) {
                        $attr .= ' class="' . $this->mvc->fields[$place]->tdClass . '" ';
                    }
                    
                    foreach ($colHeaders as $i => $name) {
                        $name = tr($name);
                        
                        if (($i < (count($colHeaders) - 1)) || ($i == ($maxColHeaders - 1))) {
                            $rowspan = 1;
                        } else {
                            $rowspan = $maxColHeaders - $i;
                        }
                        
                        $last = count($header[$i]) - 1;
                        
                        if ($header[$i][$last]->name == $name && $header[$i][$last]->rowspan == $rowspan) {
                            if (!$header[$i][$last]->colspan) {
                                if(!isset($header[$i][$last])) {
                                    $header[$i][$last] = new stdClass();
                                }
                                $header[$i][$last]->colspan = 1;
                            }
                            $header[$i][$last]->colspan = 1 + $header[$i][$last]->colspan;
                        } else {
                            if(!isset($header[$i][$last + 1])) {
                                $header[$i][$last + 1] = new stdClass();
                            }
                            $header[$i][$last + 1]->name = $name;
                            $header[$i][$last + 1]->rowspan = $rowspan;
                        }
                    }
                    
                    // Шаблон за реда
                    $row .= "<td{$attr}>[#{$place}#]</td>";
                    
                    $colspan++;
                } else {
                    // Допълнителни цели редове, ако колоната няма заглавие
                    $addRows .= "<tr><td colspan=\"[#COLSPAN#]\">[#{$place}#]</td></tr>\n";
                }
            }
        }
        
        $curTH = 0;
        
        if (count($header)) {
            foreach ($header as $i => $headerRow) {
                if ($i == count($header)) {
                    $lastRowStart = $curTH;    // Започва последният хедър
                }
                
                foreach ($headerRow as $h) {
                    $attr = array();
                    
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
        
        $this->colspan = $colspan;
        
        $row .= "</tr>\n{$addRows}<!--ET_END ROW-->";
        
        if (!$this->tableClass) {
            $this->tableClass = 'listTable';
        }
        
        $tpl = new ET("\n<table border=1 class=\"{$this->tableClass}\"  cellpadding=\"3\" cellspacing=\"0\" ><thead>[#ROW-BEFORE#]{$tableHeader}</thead>{$row}[#ROW-AFTER#]</table>\n");
        
        if (count($rows)) {
            foreach ($rows as $r) {
                $rowTpl = $tpl->getBlock("ROW");
                
                if (is_object($r))
                $r = get_object_vars($r);
                
                foreach ($fieldList as $name => $dummy) {
                    $value = $r[$name];
                    
                    if ($value === NULL) {
                        $value = '&nbsp;';
                    }
                    $rowTpl->replace($value, $name);
                }
                
                // Добавяме атрибутите на реда от таблицата, ако има такива
                if (count($r['ROW_ATTR'])) {
                    $attrs = '';
                    
                    foreach($r['ROW_ATTR'] as $attrName => $attrValue) {
                        $attrs .= " $attrName=\"{$attrValue}\"";
                    }
                    $rowTpl->replace($attrs, 'ROW_ATTR');
                }
                
                $rowTpl->append2Master();
            }
        } else {
            $rowTpl = $tpl->getBlock("ROW");
            $tpl->append("<tr><td colspan=\"" . $this->colspan . "\"> Няма записи </td></tr>", "ROW");
        }
        
        if ($this->rowBefore) {
            $rowBefore = new ET("<tr><td style=\"border:0px; padding-top:5px; \" colspan=\"" . $this->colspan . "\">[#1#]</td></tr>", $this->rowBefore);
            $tpl->replace($rowBefore, "ROW-BEFORE");
        } else {
            $tpl->replace('', "ROW-BEFORE");
        }
        
        if ($this->rowAfter) {
            $rowAfter = new ET("<tr><td style=\"border:0px; padding-top:5px; \" colspan=\"" . $this->colspan . "\">[#1#]</td></tr>", $this->rowAfter);
            $tpl->replace($rowAfter, "ROW-AFTER");
        } else {
            $tpl->replace('', "ROW-AFTER");
        }
        
        return $tpl;
    }
}