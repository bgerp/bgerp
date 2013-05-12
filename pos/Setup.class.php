<?php
/**
 * Отстъпка за периода
 */
defIfNot('POS_WAT_PERCENT', '20');

/**
 *  Константа за тема по-подразбиране на блога
 */
defIfNot('POS_PRODUCTS_DEFAULT_THEME', 'pos/themes/default');


/**
 * Модул "Точки на продажба" - инсталиране/деинсталиране
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'pos_Points';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Точки на Продажба";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'pos_Points',
        	'pos_Receipts',
            'pos_ReceiptDetails',
        	'pos_Favourites',
        	'pos_FavouritesCategories',
        	'pos_Reports',
        	'pos_Payments',
        );
        
        // Роля за power-user на този модул
        $role = 'pos';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('pos_ProductsImages', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3.1, 'Търговия', 'POS', 'pos_Points', 'default', "{$role}, admin");
        
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
