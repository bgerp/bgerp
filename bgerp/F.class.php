<?php



/**
 * История на файловете
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_F extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Лог на файлове';
    
    
    /**
     * Екшън за показване на файловете, на нерегистрираните потребители
     */
    function act_S()
    {
        // MID' а на документа
        $mid = Request::get('id');
        
        // Името на файла
        $name = Request::get('n');

        // Очакваме да има изпратен документ с mid' а
        expect(($rec = log_Documents::getActionRecForMid($mid, FALSE)) && ($rec->containerId), 'Няма информация.');

        // Вземаме документа
        $doc = doc_Containers::getDocument($rec->containerId);
        
        // Вземаме линкнатите файлове в документите
        $linkedFiles = $doc->getLinkedFiles($rec);
        
        // Ако файла съществува в масива
        expect($fh = array_search($name, $linkedFiles), 'Няма такъв файл.');
                
        // Записваме, ако не е записоно, че файла е отворено от ip
        log_Documents::opened($rec->containerId, $mid);
        
        // Записите за файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        // Ако имаме права
        if (fileman_Files::haveRightFor('single', $fRec)) {
            
            // URL към single'а на файла
            $url = toUrl(array('fileman_Files', 'single', $fh), TRUE);
        } else {
            
            // URL за сваляне
            $url = toUrl(array('fileman_Download', 'Download', 'fh' => $fh, 'forceDownload' => TRUE), TRUE);    
        }
        
        // Записваме в лога за файлове, информация за свалянето
        log_Files::downloaded($fh, $rec->containerId);
        
        // Редиректваме към линка
        redirect($url);    
    }
}
