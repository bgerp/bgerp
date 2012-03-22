<?php



/**
 * Клас 'plg_Current' - Прави текущ за сесията избран запис от модела
 *
 *
 * @category  all
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_Current extends core_Plugin
{
    
    
    /**
     * Връща указаната част (по подразбиране - id-то) на текущия за сесията запис
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @return FALSE
     */
    function on_AfterGetCurrent($mvc, &$res, $part = 'id')
    {
        if(!$res) {
            $res = Mode::get('currentPlg_' . $mvc->className)->{$part};
            
            if((!$res) && ($mvc->className != Request::get('Ctr'))) {
                redirect(array($mvc), FALSE, "Моля, изберете текущ/а {$mvc->singleTitle}");
            }
        }
    }
    
    
    /**
     * Слага id-to на даден мениджър в сесия
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param string $action
     * @return boolean
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'setcurrent') {
            
            $id = Request::get('id');
            
            expect($rec = $mvc->fetch($id));
            
            $mvc->requireRightFor('edit', $rec);
            
            Mode::setPermanent('currentPlg_' . $mvc->className, $rec);
            
            if(!Request::get('ret_url')) {
                $res = new Redirect(array($mvc));
            } else {
                $res = new Redirect(getRetUrl());
            }
            
            return FALSE;
        }
    }
    
    
    /**
     * Добавя функционално поле 'currentPlg'
     *
     * @param $mvc
     */
    function on_AfterPrepareListFields($mvc, &$res, $data)
    {
        $data->listFields['currentPlg'] = "Текущ";
    }
    
    
    /**
     * Слага съдържание на полето 'currentPlg'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $currentId = $mvc->getCurrent();
        
        if ($rec->id == $currentId) {
            $row->currentPlg = ht::createElement('img', array('src' => sbf('img/16/accept.png', ''), 'style' => 'margin-left:20px;', 'width' => '16px', 'height' => '16px'));
            $row->ROW_ATTR['class'] .= ' state-active';
        } elseif($mvc->haveRightFor('write', $rec)) {
            $row->currentPlg = ht::createBtn('Избор', array($mvc, 'SetCurrent', $rec->id), NULL, NULL, array('class' => 'btn-select'));
            $row->ROW_ATTR['class'] .= ' state-closed';
        }
    }
}
