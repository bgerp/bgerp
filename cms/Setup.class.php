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
 * class cms_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
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
    var $info = "Управление на един или няколко Интернет сайта";

    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(

            'CMS_PAGE_WRAPPER' => array ('class(interface=cms_page_WrapperIntf,select=title)', 'caption=Външен изглед->Страница'),

            'CMS_BROWSER_CACHE_EXPIRES' => array ('time', 'caption=Кеширане в браузъра->Време'),
			
            'CMS_COPY_DEFAULT_TEXT' => array ('text(rows=1)', 'caption=Добавка при копиране->Текст,width=100%'),

            'CMS_COPY_ON_SYMBOL_COUNT' => array ('int', 'caption=Добавка при копиране->Брой символи,width=100%'),
	
			'CMS_COPY_DISABLE_FOR' => array ('keylist(mvc=core_Roles,select=role,groupBy=type,orderBy=orderByRole)', 'caption=Добавка при копиране->Изключване за'),
			
			'CMS_OGRAPH_IMAGE' => array ('fileman_FileType(bucket=pictures)', 'caption=Изображение за Фейсбук->Изображение'),
	);

	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'cms_Domains',
            'cms_Content',
            'cms_Objects',
            'cms_Articles',
        	'cms_Feeds',
            'cms_Includes',
            'cms_VerbalId',
            'cms_GalleryGroups',
            'cms_GalleryImages',
            'migrate::contentOrder6',
            'migrate::updateSearchKeywords',
         );

         
    /**
     * Роли за достъп до модула
     */
    var $roles = 'cms';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.51, 'Сайт', 'CMS', 'cms_Content', 'default', "cms, ceo, admin"),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
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
        $html .= $Plugins->forcePlugin('Копиране с линк към страницата', 'cms_CopyTextPlg', 'cms_page_External', 'private');
        
        // Замества абсолютните линкове с титлата на документа
        $html .= $Plugins->installPlugin('Галерии и картинки в RichText', 'cms_GalleryRichTextPlg', 'type_Richtext', 'private');
        
        $html .= $Bucket->createBucket('cmsFiles', 'Прикачени файлове в CMS', NULL, '104857600', 'user', 'user');

        // Добавяме класа връщащ темата в core_Classes
        $html .= core_Classes::add('cms_DefaultTheme');
 
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    private static function getLocalhostDomain($lg)
    {
        static $domainIds = array();

        if(!$domainIds[$lg]) {
            $domainIds[$lg] = cms_Domains::fetch("#domain = 'localhost' AND #lang = '{$lg}'")->id;
        }

        if(!$domainIds[$lg]) {
            core_Classes::add('cms_DefaultTheme');
            $dRec = (object) array('domain' => 'localhost', 'theme' => core_Classes::getId('cms_DefaultTheme'), 'lang' => $lg);
            cms_Domains::save($dRec);
            $domainIds[$lg] = $dRec->id;
        }
        
        return $domainIds[$lg];
    }
    
    
    /**
     * Миграция към модела на домейните
     */
    static function contentOrder6()
    {
        // Добавяме domainId към cms_Content
        $max = 1;
        $query = cms_Content::getQuery();
        $typeOrder = cls::get('type_Order');

        while($rec = $query->fetch()) {
            
            list($n, $m) = explode(' ', $rec->menu, 2);
            
            $n = rtrim($n, '.');

            if ($m) {
                $rec->menu = $m;
            }
            
            if(!$rec->order) {
                if(is_numeric($n)) {
                    $rec->order = $n;
                } else {
                    $rec->order = $max +1;
                }
                $rec->order = $typeOrder->fromVerbal($rec->order);
            }
            
            $max = max($rec->order, $max);
            
            if (!$rec->domainId) {
                if (strlen($m) > 0 && (mb_strlen($m) == strlen($m))) {
                    $rec->domainId = self::getLocalhostDomain('en');
                } else {
                    $rec->domainId = self::getLocalhostDomain('bg');
                }
            }
            if(!$rec->url && !$rec->source) {
                $rec->source = core_Classes::getId('cms_Articles');
            }
            cms_Content::save($rec);
        }
        
        $bCat = cls::get('blogm_Categories');
        if($bCat->db->tableExists($bCat->dbTableName)) {
            
            if (!$bCat->db->isFieldExists($bCat->dbTableName, 'domain_id')) {
                $bCat->setupMVC();
            }
            
            $query = blogm_Categories::getQuery();
            while($rec = $query->fetch()) {
                if(!$rec->domainId) {
                    if(mb_strlen($rec->title) == strlen($rec->title)) {
                        $rec->domainId = self::getLocalhostDomain('en');
                    } else {
                        $rec->domainId = self::getLocalhostDomain('bg');
                    }
                }
                blogm_Categories::save($rec);
            }
        }

        $feeds = cls::get('cms_Feeds');
        if($feeds->db->tableExists($feeds->dbTableName)) {
            
            if (!$feeds->db->isFieldExists($feeds->dbTableName, 'domain_id')) {
                $feeds->setupMVC();
            }
            
            $query = cms_Feeds::getQuery();
            while($rec = $query->fetch()) {
                if(!$rec->domainId) {
                    if (mb_strlen($rec->title) == strlen($rec->title) && (mb_strlen($rec->description) == strlen($rec->description))) {
                        $rec->domainId = self::getLocalhostDomain('en');
                    } else {
                        $rec->domainId = self::getLocalhostDomain('bg');
                    }
                }
                cms_Feeds::save($rec);
            }
        }

        $newsbar = cls::get('newsbar_News');
        if($newsbar->db->tableExists($newsbar->dbTableName)) {
            
            if (!$newsbar->db->isFieldExists($newsbar->dbTableName, 'domain_id')) {
                $newsbar->setupMVC();
            }
            
            $query = newsbar_News::getQuery();
            $rt = cls::get('type_Richtext');
            while($rec = $query->fetch()) {
                if(!$rec->domainId) {
                    if(mb_strlen($rec->news) == strlen($rec->news)) {
                        $rec->domainId = self::getLocalhostDomain('en');
                    } else {
                        $rec->domainId = self::getLocalhostDomain('bg');
                    }
                }
                $newsbar->save($rec);
            }
        }
    }
    

    /**
     * Обновява (генерира наново) ключовите думи от външното съдържание
     */
    public function updateSearchKeywords()
    {   
        try {
        	$mvcArr = array('eshop_Products', 'cms_Articles', 'blogm_Articles');
        	
        	foreach($mvcArr as $mvc) {
        	
        		if (!cls::load($mvc, TRUE)) continue ;
        	
        		$Inst = cls::get($mvc);
        		$Inst->setupMvc();
        		
        		if (!$Inst->db->tableExists($Inst->dbTableName)) continue ;
        	
        		$query = $mvc::getQuery();
        		while($rec = $query->fetch()){
        			$mvc::save($rec, 'searchKeywords');
        		}
        	}
        } catch(core_exception_Expect $e){
        	reportException($e);
        }
    }
}
