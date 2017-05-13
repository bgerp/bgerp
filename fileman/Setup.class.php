<?php


/**
 * Минималната големина на файла в байтове, за който ще се показва размера на файла след името му
 * в narrow режим. По подразбиране е 100KB
 */
defIfNot('LINK_NARROW_MIN_FILELEN_SHOW', 102400);


/**
 * Широчината на preview' то
 */
defIfNot('FILEMAN_PREVIEW_WIDTH', 848);


/**
 * Височината на preview' то
 */
defIfNot('FILEMAN_PREVIEW_HEIGHT', 1000);


/**
 * Широчината на preview' то в мобилен режим
 */
defIfNot('FILEMAN_PREVIEW_WIDTH_NARROW', 547);


/**
 * Височината на preview' то в мобилен режим
 */
defIfNot('FILEMAN_PREVIEW_HEIGHT_NARROW', 700);


/**
 * Максималната разрешена памет за използване
 */
defIfNot('FILEMAN_DRIVER_MAX_ALLOWED_MEMORY_CONTENT', '1024M');


/**
 * Път до gnu командата 'file'
 */
defIfNot('FILEMAN_FILE_COMMAND', core_Os::isWindows() ? '"C:/Program Files (x86)/GnuWin32/bin/file.exe"' : 'file');


/**
 * Минималната големина на файла, до която ще се търси баркод
 * 1kB
 */
defIfNot('FILEINFO_MIN_FILE_LEN_BARCODE', 1024);


/**
 * Максималната големина на файла, до която ще се търси баркод
 * 3 mB
 */
defIfNot('FILEINFO_MAX_FILE_LEN_BARCODE', 3145728);


/**
 * Максималната големина на архивите, за които ще се визуализира информация
 * 100 mB
 */
defIfNot('FILEINFO_MAX_ARCHIVE_LEN', 104857600);


/**
 * Максимален брой на страниците при показване на превю
 */
defIfNot('FILEINFO_MAX_PREVIEW_PAGES', 20);


/**
 * Пътя до gs файла
 */
defIfNot('FILEMAN_GHOSTSCRIPT_PATH', 'gs');


/**
 * Път до програмата Inkscape
 */
defIfNot('FILEMAN_INKSCAPE_PATH', defined('INKSCAPE_PATH') ? INKSCAPE_PATH : 'inkscape');


/**
 * След колко време да се изтрие от индекса, записа (грешката) за съответния тип на файла
 */
defIfNot('FILEMAN_WEBDRV_ERROR_CLEAN', 300);


/**
 * Коя програма да се използва за OCR обработка
 */
defIfNot('FILEMAN_OCR', '');


/**
 * Директория, в която ще се държат екстрактнатите файлове
 */
defIfNot('FILEMAN_TEMP_PATH', EF_TEMP_PATH . '/fileman');


/**
 * Клас 'fileman_Setup' - Начално установяване на пакета 'fileman'
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Setup extends core_ProtoSetup 
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Контролер на връзката от менюто core_Packs
     */
    var $startCtr = 'fileman_Files';
    
    
    /**
     * Екшън на връзката от менюто core_Packs
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Мениджър на файлове: качване, съхранение и използване";
    
    
    /**
     * Дали пакета е системен
     */
    public $isSystem = TRUE;
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
               
       'FILEMAN_PREVIEW_WIDTH'   => array ('int', 'caption=Размер на изгледа в широк режим->Широчина,unit=pix'),
           
       'FILEMAN_PREVIEW_HEIGHT'   => array ('int', 'caption=Размер на изгледа в широк режим->Височина,unit=pix'), 

       'FILEMAN_PREVIEW_WIDTH_NARROW'   => array ('int', 'caption=Размер на изгледа в мобилен режим->Широчина,unit=pix'),

       'FILEMAN_PREVIEW_HEIGHT_NARROW'   => array ('int', 'caption=Размер на изгледа в мобилен режим->Височина,unit=pix'), 
       
       'LINK_NARROW_MIN_FILELEN_SHOW'   => array ('fileman_FileSize', 'caption=Показване размера на файла в мобилен режим при големина->Повече от, suggestions=50 KB|100 KB|200 KB|300 KB'), 

       'FILEINFO_MAX_ARCHIVE_LEN'   => array ('fileman_FileSize', 'caption=Максимален размер на архивите|*&comma;| за които ще се визуализира информация->Размер, suggestions=50 MB|100 MB|200 MB|300 MB'),

       'FILEINFO_MIN_FILE_LEN_BARCODE'   => array ('fileman_FileSize', 'caption=Размер на файловете|*&comma;| в който ще се търси баркод->Минимален, suggestions=5KB|15 KB|30 KB|50 KB'),

       'FILEINFO_MAX_FILE_LEN_BARCODE'   => array ('fileman_FileSize', 'caption=Размер на файловете|*&comma;| в който ще се търси баркод->Максимален, suggestions=500 KB|1 MB|2 MB|3 MB'),
       
       'FILEINFO_MAX_PREVIEW_PAGES'   => array ('int(min=1)', 'caption=Максимален брой на страниците|*&comma;| които ще се показват в изгледа->Брой'),

       'FILEMAN_WEBDRV_ERROR_CLEAN'   => array ('time(suggestions=1 мин.|5 мин.|10 мин.|30 мин.|1 час)', 'caption=Време за живот на грешка при интексиране на файл->Време'), 

       'FILEMAN_OCR' => array ('class(interface=fileman_OCRIntf,select=title, allowEmpty)', 'caption=Програма по подразбиране за OCR обработка->Програма'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
     		// Установяваме папките;
            'fileman_Buckets',
    
            // Установяваме файловете;
            'fileman_Files',
    
            // Установяване на детайлите на файловете
            'fileman_FileDetails',
    
    		// Установяваме версиите;
            'fileman_Versions',
    
		    // Установяваме данните;
		    'fileman_Data',
    
		    // Установяваме свалянията;
		    'fileman_Download',
    
		    // Установяваме индексите на файловете
		    'fileman_Indexes',
    
            // Установяваме модела за хранилища
            'fileman_Repositories',
    
            // Установяваме модела за последни файлове
            'fileman_Log',
            
            'migrate::addFileLen',
            'migrate::bucketRoles',
            'migrate::regenerateData1',
            'migrate::regenerateBarcodes'
        );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "fileman_reports_FileInfo";
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
            array('title' => 'Регенериране', 'url' => array ('fileman_Indexes', 'regenerate', 'ret_url' => TRUE), 'params' => array('title' => 'Регенериране на ключови думи и индексирани записи')),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
    	// Кофа 
        $Buckets = cls::get('fileman_Buckets');
        
        // Установяваме файловете;
        $Files = cls::get('fileman_Files');
        
        // Конвертира старите имена, които са на кирилица
        if(Request::get('Full')) {
            $query = $Files->getQuery();
            
            while($rec = $query->fetch()) {
                if(STR::utf2ascii($rec->name) != $rec->name) {
                    $rec->name = $Files->getPossibleName($rec->name, $rec->bucketId);
                    $Files->save($rec, 'name');
                }
            }
        }
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        $conf = core_Packs::getConfig('fileman');
        
        // Инсталираме
        if($conf->FILEMAN_FILE_COMMAND) {
            $html .= $Plugins->installPlugin('SetExtension', 'fileman_SetExtensionPlg', 'fileman_Files', 'private');
            $html .= $Plugins->installPlugin('SetExtension2', 'fileman_SetExtensionPlg2', 'fileman_Files', 'private');
        }
        
        // Инсталираме плъгина за качване на файлове в RichEdit
        $html .= $Plugins->installPlugin('Files in RichEdit', 'fileman_RichTextPlg', 'type_Richtext', 'private');
        
        // Кофа за файлове качени от архиви
        $html .= $Buckets->createBucket('archive', 'Качени от архив', '', '100MB', 'user', 'user');
        
        // Кофа за файлове качени от архиви
        $html .= $Buckets->createBucket('fileIndex', 'Генерирани от разглеждането на файловете', '', '100MB', 'user', 'user');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Keylist полета
        $Plugins->deinstallPlugin('fileman_SetExtensionPlg');
        
        // Деинсталираме плъгина от type_RichEdit
        $Plugins->deinstallPlugin('fileman_RichTextPlg');
        
        return "<h4>Пакета fileman е деинсталиран</h4>";
    }
    
    
    /**
     * Проверява дали са инсталирани необходимите пакети и дали версиите им са коректни
     * 
     * @see core_ProtoSetup
     */
    function checkConfig()
    {
        $conf = core_Packs::getConfig('fileman');
        
        // Ако не е инсталиране
        if (!static::isEnabled()) {
            
            return "GhostScript не се стартира с \"" . type_Varchar::escape($conf->FILEMAN_GHOSTSCRIPT_PATH) . "\"";
        }
        
        // Версиите на пакета
        $versionArr = static::getVersionAndSubVersion();
        
        if ($versionArr) {
            
            // Ако версията 8.71
            if (($versionArr['version'] == 8) && ($versionArr['subVersion'] == 71) || ($versionArr['version'] == 9) && ($versionArr['subVersion'] == 18)) {
                    
                // Добавяме съобщение
                return "Версията на GhostScript \"" . type_Varchar::escape($conf->FILEMAN_GHOSTSCRIPT_PATH) . "\" e {$versionArr['version']}.{$versionArr['subVersion']}. С тази версия има проблеми. Моля да я обновите.";
            }
        }
    }
    
    
    /**
     * Проверява дали програмата е инсталирана в сървъра
     * 
     * @return boolean
     */
    static function isEnabled()
    {
        $conf = core_Packs::getConfig('fileman');
        
        $gs = escapeshellcmd($conf->FILEMAN_GHOSTSCRIPT_PATH);
        
        // Опитваме се да стартираме програмата
        $res = exec($gs . ' --help', $output, $code);
        
        if ($code === 0) {
            
            return TRUE;
        } else if ($code === 127) {
            
            return FALSE;
        }
    }
    
    
    /**
     * Връща масив с версията и подверсията
     * 
     * @return array
     * ['version']
     * ['subVersion']
     */
    static function getVersionAndSubVersion()
    {
        // Вземаме конфига
        $confWebkit = core_Packs::getConfig('fileman');
       
        // Опитваме се да вземем версията на ghostscript
        exec(escapeshellarg($confWebkit->FILEMAN_GHOSTSCRIPT_PATH) . " --version", $resArr, $erroCode);
        
        $trimRes = trim($resArr[0]);
        
        if (!$trimRes) return ;
        
        // Вземаме версията и подверсията
        list($version, $subVersion) = explode(".", $trimRes);
        
        // Ако не може да се открие версията/подверсията
        if (!isset($version) || !isset($subVersion)) return ;
        
        $versionArr = array();
        $versionArr['version'] = $version;
        $versionArr['subVersion'] = $subVersion;
        
        return $versionArr;
    }
    
    
    /**
     * Миграция, за добавяне на размера на файловете
     */
    static function addFileLen()
    {
        $query = fileman_Files::getQuery();
        $query->where('#fileLen IS NULL');
        $query->where('#dataId IS NOT NULL');
        
        $query->EXT('dataSize', 'fileman_Data', 'externalName=fileLen,externalKey=dataId');
        
        while ($rec = $query->fetch()) {
            if (!$rec->dataId || ($rec->dataId < 0)) continue;
            
            $rec->fileLen = $rec->dataSize;
            fileman_Files::save($rec, 'fileLen');
        }
    }

    /**
     * Миграция към keylist на полето за ролите
     */
    static function bucketRoles()
    {
        $query = fileman_Buckets::getQuery();
        while($rec = $query->fetch()) {
            if(strlen($rec->rolesForDownload)) {
                $rec->rolesForDownload = core_Roles::getRolesAsKeylist($rec->rolesForDownload);
            }
            if(strlen($rec->rolesForAdding)) {
                $rec->rolesForAdding = core_Roles::getRolesAsKeylist($rec->rolesForAdding);
            }
            fileman_Buckets::save($rec, 'rolesForDownload,rolesForAdding');
        }
    }
    
    
    /**
     * Пускане на последните файлове
     */
    static function regenerateData1()
    {
        $dQuery = fileman_Data::getQuery();
        $dQuery->where("#processed = 'yes'");
        
        $dQuery->orderBy('lastUse', 'DESC');
        $dQuery->orderBy('createdOn', 'DESC');
        
        $dQuery->limit(10000);
        
        while ($dRec = $dQuery->fetch()) {
            $dRec->processed = 'no';
            fileman_Data::save($dRec, 'processed');
        }
    }
    
    
    /**
     * Изтриване на последно генерирани баркодове от системата
     */
    static function regenerateBarcodes()
    {
        $iQuery = fileman_Indexes::getQuery();
        $iQuery->where("#type = 'barcodes'");
        $iQuery->where("#createdBy < 1");
        
        $iQuery->orderBy('createdOn', 'DESC');
        
        $iQuery->limit(1000);
        
        $delArr = array();
        
        while ($iRec = $iQuery->fetch()) {
            
            fileman_Data::resetProcess($iRec->dataId);
            
            $delArr[$iRec->id] = $iRec->id;
        }
        
        if (!empty($delArr)) {
            $delImpl = implode(',', $delArr);
            $delCnt = fileman_Indexes::delete("#id IN ({$delImpl})");
            
            fileman_Indexes::logDebug("Изтрити баркодове: {$delCnt}");
        }
    }
}
