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
class techno_Setup extends core_ProtoSetup
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
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        	'techno_Specifications',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'techno';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.11, 'Производство', 'Технологии', 'techno_Specifications', 'default', "techno, ceo"),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Добавяме Продуктовия драйвър в core_Classes
        core_Classes::add('techno_GeneralProducts');
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('techno_GeneralProductsImages', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '10MB', 'user', 'every_one');

        
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
