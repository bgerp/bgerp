<?php


/**
 * Клас 'fileman_SetExtensionPlg' - Проверка и коригиране на разширението на файла
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_SetExtensionPlg extends core_Plugin
{
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public function on_BeforeSaveDataId($mvc, $rec)
    {
        $fileHnd = $rec->fileHnd;
        $name = $rec->name;
        $bucket = $rec->bucketId;
        
        if (!isset($fileHnd)) {
            return ;
        }
 
        if (!empty($rec->dataId)) {
            $dataId = $rec->dataId;
        } else {
            // stv: Не виждам как би могло да се стигне до тук но го оставям за всеки случай.
            
            $filemanFiles = cls::get('fileman_Files');
            $dataId = $filemanFiles->fetchByFh($fileHnd, 'dataId');
        }
        
        expect($dataId);
        
        $dataRec = fileman_Data::fetch(array("#id = '[#1#]'", $dataId));
     
        $fileMimeType = fileman::getMimeTypeFromFilePath($dataRec->path);

        $newName = fileman_Mimes::addCorrectFileExt($name, $fileMimeType);
        
        if ($newName != $name) {
            $newName = $mvc->getPossibleName($newName, $bucket);
        }

        $rec->name = $newName;
    }
}
