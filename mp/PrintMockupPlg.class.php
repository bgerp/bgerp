<?php


/**
 * 
 *
 * @category  vendors
 * @package   google
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_PrintMockupPlg extends core_Plugin
{
    
    
    /**
     * 
     * @param core_Manager $mvc
     * @param stdObject $res
     * @param stdObject $data
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        $retUrl = array($mvc, 'single', $data->rec->id);
        
        // Добавяме бутон за тестово отпечатване в bluetooth принтер
        if (isset($data->rec->id) && $mvc->haveRightFor('single', $data->rec) && ($data->rec->state != 'rejected') && ($data->rec->state != 'draft')) {
            $data->toolbar->addBtn('MP', 
                            'bgerp://print/' . $mvc->protectId($data->rec->id),
                            "id=mp{$data->rec->containerId},class=fright,row=2, order=39,title=" . "Тестов печат чрез bluetoot принтер",  'ef_icon = img/16/print_go.png');
        }
    }
}
