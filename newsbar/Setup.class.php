<?php


/**
 * class newsbar_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с пакета за новини
 *
 *
 * @category  bgerp
 * @package   neswbar
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class newsbar_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'newsbar_News';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Лента с новини за сайта';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'newsbar_News',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'newsbar';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.99, 'Сайт', 'Нюзбар', 'newsbar_News', 'list', 'cms, newsbar, admin, ceo'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('newsBar', 'Прикачени файлове в новини', 'png,gif,ico,bmp,jpg,jpeg,image/*', '1MB', 'user', 'powerUser');
        
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Лента с Новини във външната част', 'newsbar_Plugin', 'cms_page_External', 'private');
        $html .= $Plugins->installPlugin('Лента с Новини в статиите', 'newsbar_Plugin', 'cms_Articles', 'private');
        $html .= $Plugins->installPlugin('Лента с Новини в менюто', 'newsbar_Plugin', 'cms_Content', 'private');
        $html .= $Plugins->installPlugin('Лента с Новини в онлайн магазин', 'newsbar_Plugin', 'eshop_Groups', 'private');
        $html .= $Plugins->installPlugin('Лента с Новини в продукти', 'newsbar_Plugin', 'eshop_Products', 'private');
        
        return $html;
    }
}
