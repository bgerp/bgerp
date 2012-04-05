<?php



/**
 * Клас 'plg_Select' - Добавя селектор на ред от таблица
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_Select extends core_Plugin
{
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    function on_AfterPrepareListFields($mvc, &$res, $data)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing')) return;
        
        $data->listFields = arr::combine(array("_checkboxes" =>
                "|*<input type='checkbox' onclick=\"return toggleAllCheckboxes();\" name='toggle'  class='checkbox'>"), $data->listFields);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing')) return;
        
        if(!count($data->rows)) {
            unset($data->listFields['_checkboxes']);
            
            return;
        }
        
        $checkboxField = '_checkboxes';
        $inputName = plg_Select::getInputName($mvc);
        
        foreach($data->rows as $id => $row) {
            $row->ROW_ATTR['id'] = 'lr_' . $id;
            $row->{$checkboxField} .= "<input type='checkbox' onclick=\"chRwClSb('{$id}');\" name='row[{$id}]' id='cb_{$id}' class='checkbox'>";
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    function on_BeforeAction($mvc, &$res, $act)
    {
        if($act == 'dowithselected') {
            
            $mvc->requireRightFor('list');
            
            $row = Request::get('row');
            
            // bp($row, count($row), !count($row));
            
            if(!count($row)) {
                $res = new Redirect(getRetUrl(), 'Моля, изберете поне един ред');
                
                return FALSE;
            }
            
            $actArr = arr::make($mvc->doWithSelected, TRUE);
            
            // Сумираме броя на редовете, които позволяват всяко едно от посочените действия
            foreach($row as $id => $on) {
                
                $list .= ($list ? ',' : '') . $id;
                
                if(count($actArr)) {
                    foreach($actArr as $action => $caption) {
                        if($mvc->haveRightFor($action, $id)) {
                            $cnt[$action]++;
                        }
                    }
                }
            }
            
            // Махаме действията, които не са достъпни за нито един избран ред
            if(count($actArr)) {
                foreach($actArr as $action => $caption) {
                    if(!$cnt[$action]) {
                        unset($actArr[$action]);
                    }
                }
            }
            
            if(!count($actArr)) {
                
                $res = new Redirect(getRetUrl(), 'За избраните редове не са достъпни никакви операции');
                
                return FALSE;
            }
            
            $res = new ET();
            
            $res->append("\n<h3>" . tr('Действия с избраните редове') . ":</h3>");
            $res->append("\n<table>");

            foreach($actArr as $action => $caption) {
                
                $res->append("\n<tr><td>");
                $res->append(ht::createBtn($caption . '|* (' . $cnt[$action] . ')', array(
                            $mvc,
                            $action,
                            'Selected' => $list,
                            'ret_url' => Request::get('ret_url')),
                        NULL,
                        NULL,
                        "class=btn-$action,style=float:none !important;width:100%;text-align:left;"));
                 $res->append("</td></tr>");

            }
            
            $res->append("\n</table>");

            $res = $mvc->renderWrapping($res);
            
            return FALSE;
        }
    }
    
    
    /**
     * Реализация по подразбиране на метода, който връща информация, какви действия са
     * възможни с избраните записи
     */
    function on_AfterGetWithSelectedActions($mvc, &$res, $id)
    {
        // Нищо не правим, връщаме НУЛЛ
        
        $res = array('test' => 'test');
    }
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing')) return;
        
        // Ако няма никакви редове не правим нищо
        if(!count($data->rows)) return;
        
        $url = toUrl(array($mvc, 'DoWithSelected'));
        
        $tpl->prepend("\n<form action='{$url}' 1method='post'>\n");
        
        $data->toolbar->addSbBtn('С избраните ...', 'with_selected', 'class=btn-checked,id=with_selected');
    }
    
    
    /**
     * Извиква се след рендиране на Toolbar-а
     */
    function on_AfterRenderListToolbar($mvc, &$tpl, $data)
    {
        if(!count($data->rows)) return;
        
        // Ако се намираме в режим "печат", не показваме бутони
        if(Mode::is('printing')) return;
        
        // Ако няма никакви редове не правим нищо
        if(!count($data->rows)) return;
        
        $tpl = new ET($tpl);
        
        $retUrl = toUrl(getCurrentUrl(), 'local');
        
        $tpl->append("<input type='hidden' name='ret_url' value='{$retUrl}'>");
        
        $tpl->append('</form>');
        
        foreach($data->rows as $id => $row) {
            $js .= "chRwCl('{$id}');";
        }
        
        $js .= 'SetWithCheckedButton();';
        
        $tpl->appendOnce($js, 'ON_LOAD');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getInputName($mvc)
    {
        return "cb_" . cls::getClassName($mvc);
    }
}
