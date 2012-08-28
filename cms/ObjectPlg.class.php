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

    function on_AfterDescription($mvc)
    {
        $mvc->interfaces = arr::combine($mvc->interfaces, 'cms_ObjectSourceIntf');
    }
    
    /**
     * Прихваща рендирането на главната опаковка (страницата)
     */
    function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if(haveRole('cms,admin,ceo')) {
            $data->toolbar->addBtn('Публикуване', 
                    array('cms_Objects', 'add', 'sourceClass' => $mvc->className, 'type' => 'object', 'sourceId' => $data->rec->id),
                    'ef_icon=img/16/world_go.png,order=19');

            Request::setProtected('sourceClass,type,sourceId');
        }
    }


    // Реализация по подразбиране на интерфейсните методи

    /**
     *
     */
     function on_AfterPrepareCmsObject($mvc, &$res, &$data)
    {
        if($data->cmsType == 'object') { 
            $data->rec = $mvc->fetch($data->cmsObjectId);
            $mvc->prepareSingle($data);  
        }
    }
    
    
    /**
     *
     */
    function on_AfterRenderCmsObject($mvc, &$res, $data, $tpl)
    {
        if(!$res) {
            $res = $mvc->renderSingle($data, $tpl);
        }
    }
    
    
    /**
     *
     */
    function on_AfterGetDefaultCmsTpl($mvc, &$res, $data)
    {
       if(!$res) {
            $res = $mvc->renderSingleLayout($data);
       }
    }

}
