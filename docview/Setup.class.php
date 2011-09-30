<?php


/**
 * Клас 'docview_Setup' - За разглеждане на файлове
 *
 * @category   Experta Framework
 * @package    docview
 * @author	   Yusein Yuseinov
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n 
 * @since      v 0.1
 */
class docview_Setup {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'docview_Viewer';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Он-лайн разглеждане на документи";
    
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'docview_Viewer',
        	'fconv_Processes'
        );
        
        // Роля за power-user на този модул
        $role = 'every_one';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        //Инсталиране на пакета Fileman
        $packs = "fileman";
        
        set_time_limit(120);
        
        $Packs = cls::get('core_Packs');
        
    	foreach( arr::make($packs) as $p) {
            if(cls::load("{$p}_Setup", TRUE)) {
                $html .= $Packs->setupPack($p);
            }
        }
        
        //Добавяме кофа
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Docview', 'Разглеждане на документи', 'pdf,png,jpg,svg,tiff', NULL, 'every_one', 'every_one');
        
        
        
    	$instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
    	        
        return "";
    }
}
