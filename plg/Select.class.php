<?php


/**
 * Клас 'plg_Select' - Добавя селектор на ред от таблица
 *
 *
 * @category  ef
 * @package   plg
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class plg_Select extends core_Plugin
{
    /**
     * Изпълнява се след инициализиране на мениджъра
     */
    public function on_AfterDescription($mvc)
    {
        $actArr = arr::make($mvc->doWithSelected, true);
        $actArr['delete'] = '*Изтриване';
        $mvc->doWithSelected = $actArr;
        
        Request::setProtected('Selected');
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    public function on_AfterPrepareListFields($mvc, &$res, $data)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) {
            
            return;
        }
        
        $data->listFields = arr::combine(array('_checkboxes' =>
            "|*<input type='checkbox' onclick=\"return toggleAllCheckboxes(this);\" name='toggle'  class='checkbox'>"), $data->listFields);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) {
            
            return;
        }
        
        if (!countR($data->rows)) {
            unset($data->listFields['_checkboxes']);
            
            return;
        }

        $inputName = plg_Select::getInputName($mvc);

        foreach ($data->rows as $id => $row) {
            if (!isset($row->_checkboxes)) {
                $row->_checkboxes = '';
            }

            $row->ROW_ATTR['id'] = 'lr_' . $id;
            $row->_checkboxes .= "<input type='checkbox' onclick=\"chRwClSb('{$id}');\" name='R[{$id}]' id='cb_{$id}' class='checkbox'>";
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) {
            
            return;
        }
        
        if (!$data->listClass) {
            $data->listClass = 'listRows selectRows';
        } else {
            $data->listClass .= ' selectRows';
        }
        
        $mvc->FNC('_checkboxes', 'html', 'tdClass=centered');
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    public function on_BeforeAction($mvc, &$res, $act)
    {
        $actArr = arr::make($mvc->doWithSelected, true);
        
        if ($act == 'dowithselected') {
            $mvc->requireRightFor('list');
            
            $row = Request::get('R');
            
            if (!countR($row)) {
                $res = new Redirect(getRetUrl(), '|Моля, изберете поне един ред');
                
                return false;
            }
            
            
            // Сумираме броя на редовете, които позволяват всяко едно от посочените действия
            foreach ($row as $id => $on) {
                foreach ($actArr as $action => $caption) {
                    if ($mvc->haveRightFor($action, $id)) {
                        $cnt[$action]++;
                        $listArr[$action] .= ($listArr[$action] ? ',' : '') . $id;
                    }
                }
            }
            
            // Махаме действията, които не са достъпни за нито един избран ред
            foreach ($actArr as $action => $caption) {
                if (!$cnt[$action]) {
                    unset($actArr[$action]);
                }
            }
            
            if (!countR($actArr)) {
                $res = new Redirect(getRetUrl(), '|За избраните редове не са достъпни никакви операции');
                
                return false;
            }
            
            $res = new ET();
            
            $res->append("\n<h2>" . tr('Действия с избраните редове') . ':</h2>');
            $res->append("\n<table class='no-border-table'>");
            
            foreach ($actArr as $action => $caption) {
                $res->append("\n<tr><td>");
                $res->append(ht::createBtn(
                    ltrim($caption, '*') . '|* (' . $cnt[$action] . ')',
                    array(
                        $mvc,
                        $action,
                        'Selected' => $listArr[$action],
                        'ret_url' => Request::get('ret_url')),
                        null,
                        null,
                        "ef_icon=img/16/{$action}.png"
                ));
                $res->append('</td></tr>');
            }
            
            $res->append("\n</table>");
            
            $res = $mvc->renderWrapping($res);
            
            return false;
        } elseif ($actArr[$act][0] == '*') {
            if (Request::get('id')) {
                
                return;
            }
            
            $sel = Request::get('Selected');
            
            // Превръщаме в масив, списъка с избраниуте id-та
            $selArr = arr::make($sel);
            
            $processed = 0;
            
            foreach ($selArr as $id) {
                if ($mvc->haveRightFor($act, $id)) {
                    Request::push(array('id' => $id, 'Selected' => false, 'Cf' => core_Request::getSessHash($act . $id)));
                    Request::forward();
                    Request::pop();
                    $processed++;
                }
            }
            
            $caption = tr(mb_strtolower(ltrim($actArr[$act], '*')));
            
            if ($processed == 1) {
                $res = new Redirect(getRetUrl(), "|Беше направено|* {$caption} |на|* {$processed} |запис");
            } elseif ($processed > 1) {
                $res = new Redirect(getRetUrl(), "|Беше направено|* {$caption} |на|* {$processed} |записа");
            } else {
                $res = new Redirect(getRetUrl(), "|Не беше направено|* {$caption} |на нито един запис");
            }
            
            return false;
        }
    }
    
    
    /**
     * Реализация по подразбиране на метода, който връща информация, какви действия са
     * възможни с избраните записи
     */
    public function on_AfterGetWithSelectedActions($mvc, &$res, $id)
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
    public function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) {
            
            return;
        }
        
        // Ако няма никакви редове не правим нищо
        if (!countR($data->rows)) {
            
            return;
        }
        
        $url = toUrl(array($mvc, 'DoWithSelected'));
        
        $tpl->prepend("\n<form action='{$url}'>\n");
    }
    
    
    /**
     * Функция по подразбиране, за връщане на хеша на резултата
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param string   $status
     */
    public function on_BeforeGetContentHash($mvc, &$res, &$status)
    {
        $status = trim($status);
        
        $status = preg_replace('/^\<form action=.*?\>/i', '', $status, 1);
    }
    
    
    /**
     * Добавя бутон "С избраните"
     */
    public function on_AfterPrepareListToolbar($mvc, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) {
            
            return;
        }
        
        if (countR($data->rows)) {
            $data->toolbar->addSbBtn('С избраните ...', 'with_selected', 'class=btn-with-selected,id=with_selected', array('order' => 11, 'title' => 'Действия с избраните редове'));
        }
    }
    
    
    /**
     * Извиква се след рендиране на Toolbar-а
     */
    public function on_AfterRenderListToolbar($mvc, &$tpl, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) {
            
            return;
        }
        
        if (!countR($data->rows)) {
            
            return;
        }
        
        // Ако няма никакви редове не правим нищо
        if (!countR($data->rows)) {
            
            return;
        }
        
        $tpl = new ET($tpl);
        $urlArr = array('ret_url' => getCurrentUrl());
        core_Request::addUrlHash($urlArr);
        
        $tpl->append("<input type='hidden' name='ret_url' value='{$urlArr['ret_url']}'>");
        
        $tpl->append('</form>');
        
        foreach ($data->rows as $id => $row) {
            $js .= "chRwCl('{$id}');";
        }
        
        jquery_Jquery::run($tpl, 'SetWithCheckedButton();', true);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function getInputName($mvc)
    {
        return 'cb_' . cls::getClassName($mvc);
    }
}
