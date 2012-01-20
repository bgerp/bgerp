<?php



/**
 * Клас 'docview_Setup' - За разглеждане на файлове
 *
 *
 * @category  vendors
 * @package   docview
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class docview_Setup {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'docview_Viewer';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Он-лайн разглеждане на документи";
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'fileman=0.1';
    
    
    /**
     * Инсталиране на пакета
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
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        
        return "";
    }
}
