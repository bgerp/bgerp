<?php

/**
 * Тема по подразбиране
 */
defIfNot('CMS_THEME', 'cms/themes/default');


/**
 * Изображение което ще се показва в Ографа
 */
defIfNot('CMS_OGRAPH_IMAGE', '');


/**
 *  Код за споделяне на съдържание
 */
defIfNot('CMS_SHARE', '');


/**
 * class cms_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Setup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cms_Content';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Управление на публичното съдържание";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
	
			'CMS_THEME' => array ('varchar'),

            'CMS_SHARE' => array ('html'),
	
			'CMS_OGRAPH_IMAGE' => array ('fileman_FileType(bucket=pictures)'),
	
	);

    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'cms_Content',
            'cms_Objects',
            'cms_Articles',
        	'cms_Feeds',
         	'cms_GalleryGroups',
            'cms_GalleryImages',
         );
        
        // Роля за power-user на този модул
        $role = 'cms';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('gallery_Pictures', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
     
        // Инсталираме плъгина  
        $html .= $Plugins->forcePlugin('Публична страница', 'cms_PagePlg', 'page_Wrapper', 'private');
        $html .= $Plugins->forcePlugin('Показване на обекти', 'cms_ObjectsInRichtextPlg', 'type_RichText', 'private');

         // Замества абсолютните линкове с титлата на документа
        core_Plugins::installPlugin('Галерии и картинки в RichText', 'cms_plg_RichTextPlg', 'type_Richtext', 'private');
        $html .= "<li>Закачане на cms_plg_RichTextPlg към полетата за RichEdit - (Активно)";
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3, 'Сайт', 'CMS', 'cms_Content', 'default', "{$role}, admin");
        
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