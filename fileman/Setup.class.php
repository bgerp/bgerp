<?php


/**
 * Минималната големина на файла в байтове, за който ще се показва размера на файла след името му
 * в narrow режим. По подразбиране е 100KB
 */
defIfNot('LINK_NARROW_MIN_FILELEN_SHOW', 102400);


/**
 * Широчината на preview' то
 */
defIfNot('FILEMAN_PREVIEW_WIDTH', 800);


/**
 * Височината на preview' то
 */
defIfNot('FILEMAN_PREVIEW_HEIGHT', 1000);


/**
 * Широчината на preview' то в мобилен режим
 */
defIfNot('FILEMAN_PREVIEW_WIDTH_NARROW', 600);


/**
 * Височината на preview' то в мобилен режим
 */
defIfNot('FILEMAN_PREVIEW_HEIGHT_NARROW', 800);


/**
 * Максималната разрешена памет за използване
 */
defIfNot('FILEMAN_DRIVER_MAX_ALLOWED_MEMORY_CONTENT', '200M');


/**
 * Път до gnu командата 'file'
 */
defIfNot('FILEMAN_FILE_COMMAND', core_Os::isWindows() ? '"C:/Program Files/GnuWin32/bin/file.exe"' : 'file');


/**
 * Разширения на файлове, от които е допустимо да се извлече съдържание
 */
defIfNot('FILEINFO_GET_CONTENT_EXT', 'pdf, rtf, odt');


/**
 * Разширения на файлове, от които  е допустимо да се създаде JPG файлове
 */
defIfNot('FILEINFO_CONVERT_JPG_EXT', 'pdf, rtf, odt');


/**
 * Разширения на файлове, от които е допустимо да се извлече баркод
 */
defIfNot('FILEINFO_GET_BARCODES_EXT', 'pdf');


/**
 * Минималната дължина на файла, до която ще се търси баркод
 * 15kB
 */
defIfNot(FILEINFO_MIN_FILE_LEN_BARCODE, 15360);


/**
 * Максималната дължина на файла, до която ще се търси баркод
 * 15kB
 */
defIfNot(FILEINFO_MAX_FILE_LEN_BARCODE, 1048576);


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
class fileman_Setup extends core_Manager {
    
    
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
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
               
       'LINK_NARROW_MIN_FILELEN_SHOW'   => array ('int'), 
       'FILEMAN_PREVIEW_WIDTH'   => array ('int'), 
       'FILEMAN_PREVIEW_HEIGHT'   => array ('int'), 
       'FILEMAN_PREVIEW_WIDTH_NARROW'   => array ('int'), 
       'FILEMAN_PREVIEW_HEIGHT_NARROW'   => array ('int'), 
       'FILEMAN_FILE_COMMAND'   => array ('varchar'),

       'FILEINFO_GET_CONTENT_EXT'   => array ('varchar'),
       'FILEINFO_CONVERT_JPG_EXT'   => array ('varchar'),
       'FILEINFO_GET_BARCODES_EXT'   => array ('varchar'),
       'FILEINFO_MIN_FILE_LEN_BARCODE'   => array ('int'),
       'FILEINFO_MAX_FILE_LEN_BARCODE'   => array ('int'),
    
    );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Установяваме папките;
        $Buckets = cls::get('fileman_Buckets');
        $html .= $Buckets->setupMVC();
        
        // Установяваме файловете;
        $Files = cls::get('fileman_Files');
        $html .= $Files->setupMVC();
        
        // Установяване на детайлите на файловете
        $FileDetails = cls::get('fileman_FileDetails');
        $html .= $FileDetails->setupMVC();
        
        // Установяваме версиите;
        $Versions = cls::get('fileman_Versions');
        $html .= $Versions->setupMVC();
        
        // Установяваме данните;
        $Data = cls::get('fileman_Data');
        $html .= $Data->setupMVC();
        
        // Установяваме свалянията;
        $Download = cls::get('fileman_Download');
        $html .= $Download->setupMVC();
        
        // Установяваме индексите на файловете
        $Info = cls::get('fileman_Indexes');
        $html .= $Info->setupMVC();
        
        // TODO remove
        $Info = cls::get('fileman_Info');
        $html .= $Info->setupMVC();
        
        // Установяваме вземанията от URL;
        // $Get = cls::get('fileman_Get');
        // $html .= $Get->setupMVC();
                
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
        
        //Инсталиране на плъгина за проверка на разширенията
        $setExtPlg = cls::get('fileman_SetExtensionPlg');
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        if(defined('EF_EXTENSION_FILE_PROGRAM')) {
            $html .= $Plugins->installPlugin('SetExtension', 'fileman_SetExtensionPlg', 'fileman_Files', 'private');
        }
        
        // Инсталираме плъгина за качване на файлове в RichEdit
        $html .= $Plugins->installPlugin('Files in RichEdit', 'fileman_RichTextPlg', 'type_Richtext', 'private');

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
}
