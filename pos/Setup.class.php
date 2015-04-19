<?php

/**
 *  Tемата по-подразбиране за пос терминала
 */
defIfNot('POS_PRODUCTS_DEFAULT_THEME', 'pos_DefaultTheme');


/**
 *  Параметри на продукти, които да се показват при търсене
 */
defIfNot('POS_RESULT_PRODUCT_PARAMS', '');


/**
 *  Колко цифри от края на бележката да се показват в номера и
 */
defIfNot('POS_SHOW_RECEIPT_DIGITS', 4);


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
class pos_Setup extends core_ProtoSetup
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
    var $info = "Управление на точки за продажба в магазин";
    
    
    /**
     * Описание на конфигурационните константи за този модул
     */
    var $configDescription = array(
    		'POS_PRODUCTS_DEFAULT_THEME' => array ('class(interface=pos_ThemeIntf,select=title)', 'caption=Tемата по-подразбиране за пос терминала->Тема'),
        	'POS_RESULT_PRODUCT_PARAMS'  => array("keylist(mvc=cat_Params,select=name)", 'caption=Параметри за показване търсене на продукт->Параметри,columns=2'),
    		'POS_SHOW_RECEIPT_DIGITS'    => array('double', 'caption=Брой цифри показващи се цифри от кода на бележката'),
    		
    );
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'pos_Points',
        	'pos_Receipts',
            'pos_ReceiptDetails',
        	'pos_Favourites',
        	'pos_FavouritesCategories',
        	'pos_Reports',
    		'pos_Stocks',
    		'pos_Cards',
    		'migrate::truncateStocks',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'pos';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Търговия', 'POS', 'pos_Points', 'default', "pos, ceo"),
        );
    
    
    /**
     * Път до js файла
     */
//    var $commonJS = 'pos/js/scripts.js';
    
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'pos/tpl/css/styles.css';
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
                                
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('pos_ProductsImages', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
         
        // Добавяме класа връщащ темата в core_Classes
        $html .= core_Classes::add('pos_DefaultTheme');
        
        // Добавяне на роля за старши пос
        $html .= core_Roles::addOnce('posMaster', 'pos');
        
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
    
    
    /**
     * Зануляваме кешираните складови наличностти
     */
    public function truncateStocks()
    {
    	pos_Stocks::truncate();
    }
}
