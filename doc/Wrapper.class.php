<?php



/**
 * Клас 'doc_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'doc'
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class doc_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на опаковката от табове
     */
    function description()
    {        
        $this->TAB('doc_Folders', 'Папки');
        
        // Зареждаме няколко променливи, определящи треда и папката от рекуеста
        $originId    = Request::get('originId', 'int');
        $containerId = Request::get('containerId', 'int');
        $threadId    = Request::get('threadId', 'int');
        $folderId    = Request::get('folderId', 'key(mvc=doc_Folders,select=title)');
        
        if(!$threadId) {
            $threadId = $invoker->threadId;
        }
        
        if($originId && !$threadId) {
            $threadId = doc_Containers::fetchField($originId, 'threadId');
        }
        
        // Ако е указан контейнера, опитваме се да определим нишката
        if($containerId && !$threadId) {
            $threadId = doc_Containers::fetchField($containerId, 'threadId');
        }
        
        // Определяме папката от треда
        if($threadId) {
            $folderId = doc_Threads::fetchField($threadId, 'folderId');
        }
        
        // Вадим или запомняме последния отворен тред в сесията
        if(!$threadId) {
            $threadId = Mode::get('lastThreadId');
        } else {
            Mode::setPermanent('lastThreadId', $threadId);
        }
        
        // Вадим или запомняме последната отворена папка в сесията
        if(!$folderId) {
            $folderId = Mode::get('lastfolderId');
        } else {
            Mode::setPermanent('lastfolderId', $folderId);
        }
        
        $threadsUrl = array();
        $filesUrl = array();
        
        if($folderId && (doc_Folders::haveRightFor('single', $folderId))) {
            $threadsUrl = array('doc_Threads', 'list', 'folderId' => $folderId);
            
            // Записите за папката
            $folderRec = doc_Folders::fetch($folderId);
            
            // Ако състоянито е отхвърлено
            if ($folderRec->state == 'rejected') {
                
                // Линка да сочи в коша
                $threadsUrl['Rejected'] = 1;
            }
            
            // Ако има достъпни файлове в папката, тогава да е линк
            if (doc_Files::getCountInFolder()) {
                $filesUrl = array('doc_Files', 'list', 'folderId' => $folderId);    
            }
        }
        
        $this->TAB($threadsUrl, 'Теми');
        
        $containersUrl = array();
        
        if($threadId) {
            if(doc_Threads::haveRightFor('single', $threadId)) {
                $folderId = Request::get('folderId', 'key(mvc=doc_Folders,select=title)');
                $containersUrl = array('doc_Containers', 'list', 'threadId' => $threadId, 'folderId' => $folderId);
            }
        }
        
        $this->TAB($containersUrl, 'Нишка');
        
        $this->TAB('doc_Search', 'Търсене');
        
        $this->TAB($filesUrl, 'Файлове');
        
        $this->TAB('doc_UnsortedFolders', 'Проекти');
        
        // Показва таба за Шаблони, само ако имаме права за листване
        $this->TAB('doc_TplManager', 'Шаблони', 'ceo,admin');
        
        // Показва таба за Бележки, само ако имаме права за листване
        $this->TAB('doc_Notes', 'Бележки', 'debug');
             
        // Показва таба за коментари, само ако имаме права за листване
        $this->TAB('doc_Comments', 'Коментари', 'debug');
        
        // Показва таба за коментари, само ако имаме права за листване
        $this->TAB('doc_Html', 'HTML', 'debug');

        // Показва таба генерирани PDF файлове, ако имаме права
        $this->TAB('doc_PdfCreator', 'PDF файлове', 'debug');
        
        // Показва таба генерирани PDF файлове, ако имаме права
        $this->TAB('doc_ThreadUsers', 'Отношения', 'debug');
    }
}
