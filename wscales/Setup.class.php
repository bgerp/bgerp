<?php


/**
 *
 *
 * @category  vendors
 * @package   wscales
 *
 * @author    Yusein Yuseino <yyuusenov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wscales_Setup extends core_ProtoSetup
{
    
    /**
     * Необходими пакети
     */
    public $depends = 'peripheral=0.1';
    
    
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Везни';
    
    
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Четене на данни от електронна везна при производство', 'wscales_GetWeightFromScalePlg', 'planning_ProductionTaskDetails', 'private');
        cls::get('planning_Jobs')->setupMvc();
        
        return $html;
    }
}
