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
        
        //TODO Създаване на необходимите директории за работа
        
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