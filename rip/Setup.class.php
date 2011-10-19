<?php
/**
 *  Задания за клишета - инсталиране / деинсталиране
 *
 * @category   BGERP
 * @package    rip
 * @author     Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class rip_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'rip_Directory';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
  
   /**
     * Описание на модула
     */
    var $info = "Задания за клишета";


    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
        	'rip_Directory',
        	'rip_Files',
        	'rip_Process',
        	'fconv_Processes',
        );
        
        // Роля за power-user на този модул
        $role = 'rip';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }

        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(3, 'Производство', 'Клишета', 'rip_Directory', 'default', "{$role}, admin");
        
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Rip', 'Файлове за клишета', NULL, '104857600', 'every_one', 'every_one');
		
    	//Инсталиране на пакета Fileman
        $packs = "fileman";
        
        set_time_limit(120);
        
        $Packs = cls::get('core_Packs');
        
    	foreach( arr::make($packs) as $p) {
            if(cls::load("{$p}_Setup", TRUE)) {
                $html .= $Packs->setupPack($p);
            }
        }
        //core_Classes::add('rip_OneBitTiff');
        core_Classes::add('rip_TiffCrop');
        core_Classes::add('rip_Embossing');
        core_Classes::add('rip_EmbossingOld');
        core_Classes::add('rip_TiffCropEmbossing');
        core_Classes::add('rip_TiffCropEmbossingOld');
        
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