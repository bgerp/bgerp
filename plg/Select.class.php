<?php

/**
 * Клас 'plg_Select' - Добавя селектор на ред от таблица
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Милен Георгиев
 * @copyright  2006-2011 Експерта ООД
 * @license    GPL 3
 * @version    CVS: $Id:$
 * @since      v 0.1
 */
class plg_Select extends core_Plugin
{
        
    /**
     *
     */
    function on_AfterPrepareListFields($mvc, $res, $data)
    {   
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing')) return;

        $data->listFields = arr::combine( array("_checkboxes" => 
            "<input type='checkbox' onclick=\"return toggleAllCheckboxes();\" name='toggle'  class='checkbox'>"), $data->listFields );
    }

    
    /**
     *
     */
    function on_AfterPrepareListRows($mvc, $res, $data)
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
     * 
     */
    function on_BeforeAction($mvc, $res, $act) 
    { 
        if($act == 'dowithselected') {
            
            $mvc->requireRightFor('list');
            
            $data = new stdClass();

            $data->form = $mvc->getForm();

            $data->query = $mvc->getQuery();

            $row = Request::get('row');

            expect(count($row));
 
            foreach($row as $id => $on) {
                $idList .= ($idList? ',' : '') . round($id);
            }

            $data->query->where("#id IN ({$idList})");

            $mvc->prepareListFields($data);
            bp($mvc->fields);
            unset($data->listFields['_checkboxes']);

            $mvc->prepareListRecs($data);

            bp($data);

        }

        if($act == 'listdelete') {
            
            $row = Request::get('row' );
            
            unset($row['toggle']);
            
            $cntDeleted = 0;
            $cntNoDeleted = 0;
            
            foreach($row as $id => $dummy) {
                expect(is_int($id));
                expect($rec = $mvc->fetch($id));

                if( $mvc->haveRightFor('delete', $rec) ) {
                    $mvc->delete($id);
                    $cntDeleted++;
                } else {
                    $cntNoDeleted++;
                }
            }
            
            if($cntDeleted == 0) {
                $msg = "Не бяха изтрити записи";
            } elseif($cntDeleted == 1) {
                $msg = "Беше изтрит един запис";
            } else {
                $msg = "Бяха изтрити|* $cntDeleted |записа";
            }

            if($cntNoDeleted == 1) {
                $msg .= "|*, един запис не може да бъде изтрит.";
            } elseif($cntNoDeleted > 1) {
                $msg .= "|*, {$cntNoDeleted} записа не могат да бъдат изтрити";
            } else {
                $msg .= "|*.";
            }


            $res = new Redirect(array($mvc, 'list'), tr($msg));

            return FALSE;
        }

    }


    /**
     *
     */
    function on_AfterRenderListTable($mvc, $tpl, $data)
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
     *
     */
    function on_AfterRenderListToolbar($mvc, $tpl, $data)
    {
        if(!count($data->rows)) return;

        // Ако се намираме в режим "печат", не показваме бутони
        if(Mode::is('printing')) return;
        
        // Ако няма никакви редове не правим нищо
        if(!count($data->rows)) return;

        $tpl = new ET($tpl);

        $tpl->append('</form>');

        foreach($data->rows as $id => $row) {
            $js .= "chRwCl('{$id}');";
        }

        $js .= 'SetWithCheckedButton();';

        $tpl->appendOnce($js, 'ON_LOAD');
    }



    /**
     *
     */
    function getInputName($mvc)
    {
        return "cb_" . cls::getClassName($mvc);
    }
}
