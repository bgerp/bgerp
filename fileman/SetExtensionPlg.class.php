<?php


if(!core_Os::isWindows()) {
    
    /**
     * Задава командата за определяне на mime типа
     * Константата е дефинирана по подразбиране само в Линукс
     */
    defIfNot('EF_EXTENSION_FILE_PROGRAM', 'file');
}


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
    function on_BeforeSave($mvc, &$id, $rec)
    {
        $fileHnd = $rec->fileHnd;
        $name = $rec->name;
        $bucket = $rec->bucketId;
        
        if (!isset($fileHnd)) {
            
            return ;
        }
        
        $filePrg = EF_EXTENSION_FILE_PROGRAM;
        
        if (!empty($rec->dataId)) {
            $dataId = $rec->dataId;
        } else {
            // stv: Не виждам как би могло да се стигне до тук но го оставям за всеки случай.
            
            $filemanFiles = cls::get('fileman_Files');
            $dataId = $filemanFiles->fetchByFh($fileHnd, 'dataId');
        }
        
        expect($dataId);
        
        $dataRec = fileman_Data::fetch(array("#id = '[#1#]'", $dataId));
        $filePath = $dataRec->path;
        
        $fileType = exec("{$filePrg} --mime-type \"{$filePath}\"");
        $spacePos = mb_strrpos($fileType, ' ') + 1;
        $fileMimeType = mb_substr($fileType, $spacePos);
        
        if(($dotPos = mb_strrpos($name, '.')) !== FALSE) {
            $ext = mb_substr($name, $dotPos + 1);
        } else {
            $ext = '';
        }
        
        include(dirname(__FILE__) . '/data/mimes.inc.php');
        
        $correctExtensions = $mime2exts["{$fileMimeType}"];
        
        if ($correctExtensions) {
            
            $extArr = explode(' ', $correctExtensions);
            
            if (!in_array($ext, $extArr)) {
                $newName = $name . '.' . $extArr[0];
                
                $newName = $mvc->getPossibleName($newName, $bucket);
                
                $rec->name = $newName;
            }
        }
        
        return ;
    }
}
