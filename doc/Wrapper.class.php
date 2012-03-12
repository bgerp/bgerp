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
class doc_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('doc_Folders', 'Папки');
        
        // Зареждаме няколко променливи, определящи треда и папката от рекуеста
        $originId    = request::get('originId', 'int');
        $containerId = request::get('containerId', 'int');
        $threadId    = request::get('threadId', 'int');
        $folderId    = request::get('folderId', 'int');
        
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
        
        if($folderId) {
            $threadsUrl = array('doc_Threads', 'list', 'folderId' => $folderId);
        }
        
        $tabs->TAB('doc_Threads', 'Нишки', $threadsUrl);
        
        $containersUrl = array();
        
        if($threadId) {
            if(doc_Threads::haveRightFor('single', $threadId)) {
                $folderId = request::get('folderId', 'int');
                $containersUrl = array('doc_Containers', 'list', 'threadId' => $threadId, 'folderId' => $folderId);
            }
        }
        
        $tabs->TAB('doc_Containers', 'Документи', $containersUrl);
        
        $tabs->TAB('doc_Log', 'История');
        
        $tabs->TAB('doc_UnsortedFolders', 'Кюпове');
        
        $tabs->TAB('doc_Tasks', 'Задачи');
        
        //Показва таба за коментари, само ако имаме права за листване
        if (doc_Comments::haveRightFor('list', core_Users::getCurrent())) {
            $tabs->TAB('doc_Comments', 'Коментари');
        }
        
        $tpl = $tabs->renderHtml($tpl, $invoker->currentTab ? $invoker->currentTab : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » " , 'PAGE_TITLE');
    }
}
