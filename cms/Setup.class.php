<?php

/**
 * Тема по подразбиране
 */
defIfNot('CMS_THEME', 'cms_DefaultTheme');


/**
 * Текущ език
 */
defIfNot('EF_DEFAULT_LANGUAGE', 'bg');

/**
 * Основен език на публичната част
 */
defIfNot('CMS_BASE_LANG', EF_DEFAULT_LANGUAGE);


/**
 * Допълнителни езици публичната част
 */
defIfNot('CMS_LANGS', '');


/**
 * Колко секунди да се кешира съдържанието за не PowerUsers
 */
defIfNot('CMS_BROWSER_CACHE_EXPIRES', 3600);

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
class cms_Setup extends core_ProtoSetup
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
            'CMS_BASE_LANG' => array ('customKey(mvc=drdata_Languages,select=languageName, key=code)', 'caption=Езици за публичното съдържание->Основен'),

            'CMS_LANGS' => array ('keylist(mvc=drdata_Languages,select=languageName)', 'caption=Езици за публичното съдържание->Допълнителни'),

			'CMS_THEME' => array ('class(interface=cms_ThemeIntf,select=title)', 'caption=Тема по подразбиране->Тема'),

            'CMS_BROWSER_CACHE_EXPIRES' => array ('time', 'caption=Кеширане в браузъра->Време'),
	);

	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'cms_Content',
            'cms_Objects',
            'cms_Articles',
        	'cms_Feeds',
         	'cms_GalleryGroups',
            'cms_GalleryImages',
            'cms_Includes',
            'cms_VerbalId',
         );

         
    /**
     * Роли за достъп до модула
     */
    var $roles = 'cms';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.5, 'Сайт', 'CMS', 'cms_Content', 'default', "cms, ceo, admin"),
        );
 
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
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

        // Добавяме класа връщащ темата в core_Classes
        core_Classes::add('cms_DefaultTheme');
        
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