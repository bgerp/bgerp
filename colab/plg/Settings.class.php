<?php


/**
 * Плъгин, който оправя обвивката на настройване на профил
 * 
 * @category  bgerp
 * @package   colab
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class colab_plg_Settings extends core_Plugin
{

    

    /**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data=NULL)
    {
        if (!$data || !$data->cClass || (!($data->cClass instanceof crm_Profiles))) return ;
        
        // Ако текущия потребител не е контрактор
        if (!core_Users::isContractor()) return ;
        
        $cProfiles = cls::get('colab_Profiles');
        
        $cProfiles->currentTab = 'Профил';
        
        $res = $cProfiles->renderWrapping($tpl, $data);
        
        return FALSE;
    }
}
