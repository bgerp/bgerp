<?php



/**
 * Клас 'plg_Clone' - Добавя бутон в едит формата за клониране
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
class plg_Clone extends core_Plugin
{
     
    /**
     * Добавяне на бутони за 'Предишен' и 'Следващ'
     *
     * @param unknown_type $mvc
     * @param unknown_type $res
     * @param unknown_type $data
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
         $data->form->toolbar->addSbBtn('Клониране', 'clone', 'warning=Наистина ли искате да клонирате записа?,class=fright noicon');
    }


    /**
     * P
     */
    function on_BeforeAction($mvc, $res, $action)
    { 
        $cmd = Request::get('Cmd');
 
        if(($action == 'save') && $cmd['clone']) {
            Request::push(array('id' => FALSE));
        }
    }

}