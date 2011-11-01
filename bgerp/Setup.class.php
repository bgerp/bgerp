<?php

/**
 *  class 'bgerp_Setup' - Начално установяване на 'bgerp'
 *
 *
 * @category   Experta Framework
 * @package    bgerp
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class bgerp_Setup {
    
    
    /**
     *  Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     *  Мениджър - входна точка в пакета
     */
    var $startCtr = 'bgerp_Menu';
    
    
    /**
     *  Екшън - входна точка в пакета
     */
    var $startAct;
    
    /**
     * Описание на модула
     */
    var $info = "Основно меню и портал на bgERP";
    

    /**
     *  Инсталиране на пакета
     */
    function install($Plugins = NULL)
    {
        $managers = array(
            'bgerp_Menu',
            'bgerp_Portal',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $packs = "core,fileman,drdata,editwatch,recently,thumbnail,keyboard,acc,currency,email,doc,cat,
                  catpr,crm,rfid,hr,trz,catering,lab,store,trans,cash,bank,budget,purchase,sales,accda,sens,cams";
        
        set_time_limit(120);
        
        $Packs = cls::get('core_Packs');
        
        foreach( arr::make($packs) as $p) {
            if(cls::load("{$p}_Setup", TRUE)) {
                $html .= $Packs->setupPack($p);
            }
        }
        
        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(1, 'Система', 'Администриране', 'core_Packs', 'default', 'admin');
        $html .= $Menu->addItem(1, 'Система', 'Данни', 'drdata_Countries', 'default', 'admin');

        return $html;
    }
}