<?php



/**
 * Клас 'fileman_Buckets' - Определя еднородни по права за достъп хранилища за файлове
 *
 *
 * @category  bgerp
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Buckets extends core_Manager {


    /**
     * Заглавие на модула
     */
    var $title = 'Кофи за файлове';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Кофа за файлове';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
	
	
	/**
	 * 
	 */
    var $loadList = 'plg_Translate';
    
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Име на кофата
        $this->FLD("name", "varchar(255)", 'notNull,caption=Име');

        // Информация за кофата
        $this->FLD("info", "varchar", 'caption=Информация, translate');

        // Файлови разширения
        $this->FLD("extensions", "text", 'caption=Допустими разширения');

        // Максимален размер на файловете в папката
        $this->FLD("maxSize", "fileman_FileSize", 'caption=Макс. размер');

        // Потребители с какви роли могат да добавят в кофата?
        $this->FLD("rolesForAdding", "keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Роли->за добавяне');

        // Потребители с какви роли могат да свалят от кофата?
        $this->FLD("rolesForDownload", "keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Роли->за сваляне');

        // Колко време след последната си употреба, файла ще живее в кофата?
        $this->FLD("lifetime", "int", 'caption=Живот');

        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,plg_Modified,Files=fileman_Files,plg_RowTools,fileman_Wrapper');
    }


    /**
     * Връща id на кофата, според името и
     */
    static function fetchByName($bucketName, $part = 'id')
    {
        // Името да е в долен регистър
        $bucketName = mb_strtolower($bucketName);
        $rec = static::fetch(array("LOWER(#name) = '[#1#]'", $bucketName));

        if($part == '*') {

            return $rec;
        } else {

            return $rec->{$part};
        }
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$res, $rec)
    {
        $Roles = cls::get('core_Roles');

        if(!$rec->rolesForDownload) {
            $rec->rolesForDownload = '|' . $Roles->fetchByName('user') . '|';
        }

        if(!$rec->rolesForAdding) {

            $rec->rolesForAdding = '|' . $Roles->fetchByName('user') . '|';
        }

        if(!($rec->lifetime>0)) {
            $rec->lifetime = 1000000000;
        }
    }


    /**
     * @todo Чака за документация...
     */
    function getAddFileInfo($bucketId)
    {
        // Проверка дали текущия потребител има права над тази папка
        $rec = $this->fetch($bucketId);

        $row = $this->recToVerbal($rec);
        $info = new stdClass();

        if($row->extensions) {
            $extArr = explode(',', $row->extensions);
            
            foreach($extArr as $ext) {
                $ext = trim($ext);
                
                if (!$ext) continue;
                
                if(strpos($ext, '/')) {
                    $mimeExtArr = fileman_Mimes::getExtByMime($ext);
                    $mimeExtArr = arr::make($mimeExtArr, TRUE);
                    
                    $extStrArr = arr::make($info->extensions, TRUE);
                    $extStrArr = arr::combine($extStrArr, $mimeExtArr);
                    $info->extensions = implode(', ', $extStrArr);
                    
                    $info->accept .= ($info->accept ? ', ' : '') . mb_strtolower(trim($ext));
                } else {
                    
                    $extMimeArr = fileman_Mimes::getMimeByExt($ext);
                    $extMimeArr = arr::make($extMimeArr, TRUE);
                    
                    $acceptArr = arr::make($info->accept, TRUE);
                    $acceptArr = arr::combine($acceptArr, $extMimeArr);
                    $acceptArr['.' . $ext] = '.' . $ext;
                    $info->accept = implode(',', $acceptArr);
                    
                    $info->extensions .= ($info->extensions ? ', ' : '') . mb_strtolower(trim($ext));
                }
            }
        }
        
        // Попълване на информацията
        $info->title = tr("Добавяне на файл(ове)");

        $info->maxFileSize = $row->maxSize;
        
        return $info;
    }


    /**
     * Дали дадения файл отговаря на условията в посочената папка?
     */
    function isValid(&$err, $bucketId, $fileName, $filePath)
    {
        $rec = $this->fetch($bucketId);

        $row = $this->recToVerbal($rec);

        if(trim($rec->extensions)) {
            $extensions = arr::make($rec->extensions, TRUE);

            if(($dotPos = strrpos($fileName, '.')) !== FALSE) {
                $ext = strtolower(mb_substr($fileName, $dotPos + 1));

                if($ext && !$extensions[$ext]) {
                    $err[] = "Разширението на файла |* <b>{$ext}</b> | не е в допустимите|*: {$row->extensions}";
                }
            }
        }

        if(filesize($filePath) > $rec->maxSize) {
            $err[] = "Допустимия размер за файл в кофата е|*: <b>{$row->maxSize}</b>.";
        }

        if(!$err) return TRUE;
    }


    /**
     * Показва съобщението след като файлът е добавен
     */
    function getInfoAfterAddingFile($fh)
    {
        // Линк към сингъла на файла
        $link = fileman::getLinkToSingle($fh, FALSE, array('target' => '_blank'));
        
        return new ET("<div class='uploaded-title'> <b>{$link}</b> </div>");
    }


    /**
     * Създаване на 'Кофа'. Ако има съществуваща, със същото име, то тя се обновява
     */
    static function createBucket($name, $info = '', $extensions = '', $maxSize = NULL,
        $rolesForDownload = NULL, $rolesForAdding = NULL, $lifetime = NULL)
    {
        $rec = new stdClass();
        $rec->id = static::fetchField(array("#name = '[#1#]'", $name), 'id');

        if($rec->id) {
            $res = "<li> Съществуваща кофа за файлове \"{$name}\"</li>";
        } else {
            $res = "<li style='color:green;'> Създаване на кофа за файлове \"{$name}\"</li>";
        }

        $FileSize = cls::get('fileman_FileSize');
        $rec->name = $name;
        $rec->info = $info;
        $rec->maxSize = $FileSize->fromVerbal($maxSize);
        $rec->rolesForDownload = $rolesForDownload;
        $rec->rolesForAdding = $rolesForAdding;
        $rec->lifetime = $lifetime;
        $rec->extensions = $extensions;

        static::save($rec);

        return $res;
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->extensions = str_replace(",", ", ", $row->extensions);
    }
    
    
    /**
     * Връща масив с позволените разширения за съответния подаден стринг
     * 
     * @param string $extensions - Разширенията
     * 
     * @return array $res - Масив с разширенията
     */
    static function getAllowedExtensionArr($extensions)
    {
        // Масива, който ще връщаме
        $resArr = array();
        
        // Ако няма текст, връщаме празен масив
        if (!trim($extensions)) return $resArr;
        
        // Разделяме масива
        $extensionsArr = explode(',', $extensions);
        
        // Обхождаме резултатите
        foreach ((array)$extensionsArr as $extension) {
            
            // Тримваме разширението
            $extension = trim($extension);
            
            // Ако няма разширение, прескачаме
            if (!$extension) continue;
            
            // Ако разширението няма наклонена черта
            if (strpos($extension, '/') === FALSE) {
                
                // Вземаме разширението в долен регистър
                $extension = mb_strtolower($extension);
                
                // Добавяме в масива
                $resArr[$extension] = $extension;
            } else {
                
                // Ако разширението има наклонене черта, следователно е mime
                
                // Вземаме масива с раширенията от MIME
                $mimeExtArr = fileman_Mimes::getExtByMime($extension);
                
                // Обхождаме масива
                foreach ((array)$mimeExtArr as $mimeExt) {
                    
                    // Тримваме разширението
                    $mimeExt = trim($mimeExt);
                    
                    // Ако няма прескачаме
                    if (!$mimeExt) continue;
                    
                    // Вземаме разширението в долен регистър
                    $mimeExt = mb_strtolower($mimeExt);
                    
                    // Добавяме в масива
                    $resArr[$mimeExt] = $mimeExt;
                }
            }
        }
        
        return $resArr;
    }
}
