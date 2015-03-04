<?php



/**
 * Декодира tnef файлове
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tnef_EmailPlg extends core_Plugin
{
    
    
    /**
     * Преди записване на файловете
     * 
     * @param email_Mime $mvc
     * @param NULL $res
     */
    function on_BeforeSaveFiles($mvc, &$res)
    {
        $allFiles = $mvc->files;
        
        foreach ((array)$mvc->files as $id => $fRec) {
            
            $ext = fileman_Files::getExt($fRec->name);
            
            if ($ext != 'dat' && $ext != 'tnef') continue;
            
            // Записваме файла, ако не е записан вече
            if (!$fileId = $fRec->fmId) {
                
                $Fileman = cls::get('fileman_Files');
                
                //Вкарваме файла във Fileman
                $fh = $Fileman->addNewFileFromString($fRec->data, tnef_Decode::$bucket, $fRec->name);
            } else {
                $fh = fileman_Files::fetchField($fileId, 'fileHnd');
            }
            
            // Извличаме файловете
            $decodedArr = tnef_Decode::decode($fh);
            
            if (!$decodedArr) continue;
            
            unset($allFiles[$id]);
            
            // Добавяме информация за файловете в масива
            foreach ($decodedArr as $fh) {
                
                $fRecN = fileman_Files::fetchByFh($fh);
                if (!$fh) continue;
                $nF = new stdClass();
                $nF->name = $fRecN->name;
                $nF->fmId = $fRecN->id;
                $allFiles[] = $nF;
            }
        }
        
        $mvc->files = $allFiles;
    }
}
