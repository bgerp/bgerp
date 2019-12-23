<?php


/**
 * Колко секунди да се кешира съдържанието за не PowerUsers
 */
defIfNot('CMS_BROWSER_CACHE_EXPIRES', 3600);


/**
 * допълнителен текст при копиране
 */
defIfNot('CMS_COPY_DEFAULT_TEXT', 'Виж още на');


/**
 * При какъв брой символи да да се добавя текста
 */
defIfNot('CMS_COPY_ON_SYMBOL_COUNT', 200);


/**
 * Добавка при копиране изключване за определени роли
 */
defIfNot('CMS_COPY_DISABLE_FOR', '');


/**
 * Изображение което ще се показва в Ографа
 */
defIfNot('CMS_OGRAPH_IMAGE', '');


/**
 * Стандартна "кожа" за външната част
 */
defIfNot('CMS_PAGE_WRAPPER', 'cms_page_External');


/**
 * Синоними за СЕО оптимизация
 */
defIfNot('CMS_SEO_SYNONYMS', '');


/**
 * class cms_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'cms_Content';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Управление на един или няколко Интернет сайта';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'CMS_PAGE_WRAPPER' => array('class(interface=cms_page_WrapperIntf,select=title)', 'caption=Външен изглед->Страница'),
        
        'CMS_BROWSER_CACHE_EXPIRES' => array('time', 'caption=Кеширане в браузъра->Време'),
        
        'CMS_COPY_DEFAULT_TEXT' => array('text(rows=1)', 'caption=Добавка при копиране->Текст,width=100%'),
        
        'CMS_COPY_ON_SYMBOL_COUNT' => array('int', 'caption=Добавка при копиране->Брой символи,width=100%'),
        
        'CMS_COPY_DISABLE_FOR' => array('keylist(mvc=core_Roles,select=role,groupBy=type,orderBy=orderByRole)', 'caption=Добавка при копиране->Изключване за'),
        
        'CMS_OGRAPH_IMAGE' => array('fileman_FileType(bucket=pictures)', 'caption=Изображение за Фейсбук->Изображение'),
        
        'CMS_SEO_SYNONYMS' => array('table(columns=s1|s2|s3|s4|s5,captions=Синоним1|Синоним2|Синоним3|Синоним4|Синоним5,widths=8em|8em|8em|8em|8em)', 'caption=SEO синоним->Групи'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'cms_Content',
        'cms_Domains',
        'cms_Objects',
        'cms_Articles',
        'cms_Feeds',
        'cms_Includes',
        'cms_VerbalId',
        'cms_GalleryGroups',
        'cms_GalleryImages',
        'cms_Library',
        'migrate::domainFiles',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'cms';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.51, 'Сайт', 'CMS', 'cms_Content', 'default', 'cms, ceo, admin'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'UpdateSitemaps',
            'description' => 'Обновяване на sitemap.xml',
            'controller' => 'cms_Content',
            'action' => 'UpdateSitemap',
            'period' => 180,
            'offset' => 77,
            'timeLimit' => 20
        ),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Това е с цел да се в таблицата с класовете и да може да се избира по интерфейс
        $html .= core_Classes::add('cms_page_External');
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('gallery_Pictures', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png,ico', '6MB', 'user', 'every_one');
        
        $disableFor = keylist::addKey('', core_Roles::fetchByName('powerUser'));
        core_Packs::setConfig('cms', array('CMS_COPY_DISABLE_FOR' => $disableFor));
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина
        $html .= $Plugins->forcePlugin('Показване на обекти', 'cms_ObjectsInRichtextPlg', 'type_Richtext', 'private');
        $html .= $Plugins->forcePlugin('Показване на елементи', 'cms_LibraryRichTextPlg', 'type_Richtext', 'private');
        
        $html .= $Plugins->forcePlugin('Копиране с линк към страницата', 'cms_CopyTextPlg', 'cms_page_External', 'private');
        
        // Замества абсолютните линкове с титлата на документа
        $html .= $Plugins->installPlugin('Галерии и картинки в RichText', 'cms_GalleryRichTextPlg', 'type_Richtext', 'private');
        
        $html .= $Bucket->createBucket('cmsFiles', 'Прикачени файлове в CMS', null, '104857600', 'user', 'user');
        
        // Добавяме класа връщащ темата в core_Classes
        $html .= core_Classes::add('cms_DefaultTheme');
        $html .= core_Classes::add('cms_FancyTheme');

        return $html;
    }
    
    
    private static function getLocalhostDomain($lg)
    {
        static $domainIds = array();
        
        if (!$domainIds[$lg]) {
            $domainIds[$lg] = cms_Domains::fetch("#domain = 'localhost' AND #lang = '{$lg}'")->id;
        }
        
        if (!$domainIds[$lg]) {
            core_Classes::add('cms_DefaultTheme');
            $dRec = (object) array('domain' => 'localhost', 'theme' => core_Classes::getId('cms_DefaultTheme'), 'lang' => $lg);
            cms_Domains::save($dRec);
            $domainIds[$lg] = $dRec->id;
        }
        
        return $domainIds[$lg];
    }
    
    
    /**
     * Премахване на favicon от рута и миграция на домейните
     */
    public function domainFiles()
    {
        // Иконата
        $dest = EF_INDEX_PATH . '/favicon.ico';
        
        if (file_exists($dest)) {
            try {
                if (!cms_Domains::fetch("#domain = 'localhost' AND (#favicon OR #wrFiles)")) {
                    if ($dRec = cms_Domains::fetch("#domain = 'localhost'")) {
                        $dRec->favicon = fileman::absorb($dest, 'cmsFiles');
                        cms_Domains::save($dRec, 'favicon');
                    }
                }
                
                unlink($dest);
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }
        
        $dQuery = cms_Domains::getQuery();
        while ($dRec = $dQuery->fetch()) {
            cms_Domains::save($dRec);
        }
    }
}
