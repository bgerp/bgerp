<?php



/**
 * Технологии - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'techno_Specifications';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Технологии";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
        	'techno_Specifications',
        	'techno_GeneralProducts',
        );
        
        // Роля за power-user на този модул
        $role = 'techno';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('techno_GeneralProductsImages', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '10MB', 'user', 'every_one');
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3.1, 'Производство', 'Технологии', 'techno_Specifications', 'default', "{$role}, admin");
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}
