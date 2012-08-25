<?php



/**
 * Клас 'cms_ObjectPlg' - Плъгин за публикуване на cms обекти
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_ObjectPlg extends core_Plugin
{
    
    /**
     * Прихваща рендирането на главната опаковка (страницата)
     */
    function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if(cls::haveInterface('cms_ObjectSourceIntf', $mvc) && haveRole('ceo,admin,cms')) {
            $data->toolbar->addBtn('Публикувай', 
                array('cms_Objects', 'add', 'sourceClass' => $mvc->className, 'type' => 'object', 'sourceId' => $data->rec->id),
                'ef_icon=img/16/control_play_blue.png');

            Request::setProtected('sourceClass,type,sourceId');
        }
    }
}
