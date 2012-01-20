<?php



/**
 * Клас 'fileman_Buckets' - Определя еднородни по права за достъп хранилища за файлове
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Buckets extends core_Manager {
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'Кофи за файлове';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Име на кофата
                $this->FLD("name", "varchar(255)", 'notNull,caption=Име');
        
        // Информация за кофата
                $this->FLD("info", "varchar", 'caption=Информация');
        
        // Файлови разширения
                $this->FLD("extensions", "text", 'caption=Допустими разширения');
        
        // Максимален размер на файловете в папката
                $this->FLD("maxSize", "fileman_FileSize", 'caption=Макс. размер');
        
        // Потребители с какви роли могат да добавят в кофата?
                $this->FLD("rolesForAdding", "keylist(mvc=core_Roles,select=role)", 'caption=Роли->за добавяне');
        
        // Потребители с какви роли могат да свалят от кофата?
                $this->FLD("rolesForDownload", "keylist(mvc=core_Roles,select=role)", 'caption=Роли->за сваляне');
        
        // Колко време след последната си употреба, файла ще живее в кофата?
                $this->FLD("lifetime", "int", 'caption=Живот');
        
        // Плъгини за контрол на записа и модифицирането
                $this->load('plg_Created,plg_Modified,Files=fileman_Files,plg_RowTools,fileman_Wrapper');
    }
    
    
    /**
     * Връща id на кофата, според името и
     */
    function fetchByName($bucketName, $part = 'id')
    {
        $rec = $this->fetch(array("#name = '[#1#]'", $bucketName));
        
        if($part == '*') {
            
            return $rec;
        } else {
            
            return $rec->{$part};
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave($mvc, $res, $rec)
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
        
        if(!$row->extensions) {
            $row->extensions = 'All';
        }
        
        // Попълване на информацията
                $info->title = tr("Добавяне на файл в|* &quot;|$row->name|*&quot;");
        $info->extensions = $row->extensions;
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
                
                if(!$extensions[$ext]) {
                    $err[] = "File extension|*<b> {$ext} </b>|is not allowed";
                }
            }
        }
        
        if(filesize($filePath) > $rec->maxSize) {
            $err[] = "File size exceeded the maximum size of|*<b>{$row->maxSize}</b>.";
        }
        
        if(!$err) return TRUE;
    }
    
    
    /**
     * Показва съобщението след като файлът е добавен
     */
    function getInfoAfterAddingFile($fh)
    {
        $fileRec = $this->Files->fetch("#fileHnd = '{$fh}'");
        
        $bucketRec = $this->fetch($fileRec->bucketId);
        
        return new ET("<div style='padding:5px;border:solid 1px #ccc; " .
            "background-color:#ffe;margin-bottom:5px;'> <center> {$fileRec->name}<BR> " .
            tr("е добавен в") . " \"{$bucketRec->name}\" </center></div>");
    }
    
    
    /**
     * Създаване на 'Кофа'. Ако има съществуваща, със същото име, то тя се обновява
     */
    function createBucket($name, $info = '', $extensions = '', $maxSize = NULL,
        $rolesForDownload = NULL, $rolesForAdding = NULL, $lifetime = NULL)
    {
        
        $rec->id = $this->fetchField(array("#name = '[#1#]'", $name), 'id');
        
        if($rec->id) {
            $res = "<li> Обновяване на отпреди съществуващата кофа \"{$name}\"</li>";
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
        
        $this->save($rec);
        
        return $res;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->extensions = str_replace(",", ", ", $row->extensions);
    }
}