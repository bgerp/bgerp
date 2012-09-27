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
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'bgerp_Wrapper, plg_RowTools, plg_Printing, plg_Created';


//    /**
//     * Описание на модела
//     */
//    function description()
//    {
//    }
    
    
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
        expect(($rec = log_Documents::getActionRecForMid($mid)) && ($rec->containerId), 'Няма информация.');

        // Вземаме документа
        $doc = doc_Containers::getDocument($rec->containerId);
        
        // Вземаме линкнатите файлове в документите
        $linkedFiles = $doc->getLinkedFiles();
        
        // Ако файла съществува в масива
        expect($fh = array_search($name, $linkedFiles), 'Няма такъв файл.');
                
        // Записваме, ако не е записоно, че файла е отворено от ip
        log_Documents::opened($rec);
        
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
        
        // Редиректваме към линка
        redirect($url);    
    }
}
