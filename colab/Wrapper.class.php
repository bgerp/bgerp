<?php



/**
 * Клас 'colab_Wrapper'
 *
 * Опаковка на пакета colab: за достъп на външни потребители до определени
 * модели от системата
 *
 *
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class colab_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на опаковката с табове
     */
    function description()
    {
        $this->TAB('colab_Folders', 'Папки', 'contractor');
     
        $threadsUrl = $containersUrl = array();
        
        $threadId = Request::get('threadId', 'int');
        $folderId = Request::get('folderId', 'key(mvc=doc_Folders,select=title)');
        
        if(!$folderId) {
        	$folderId = Mode::get('lastFolderId');
        } else {
        	Mode::setPermanent('lastFolderId', $folderId);
        }
        
        if($folderId && colab_Threads::haveRightFor('list', (object)array('folderId' => $folderId))){
        	$threadsUrl = array('colab_Threads', 'list', 'folderId' => $folderId);
        }
        
        if(!$threadId) {
        	$threadId = Mode::get('lastThreadId');
        } else {
        	Mode::setPermanent('lastThreadId', $threadId);
        }
        
        if($threadId){
        	$threadRec = doc_Threads::fetch($threadId);
        	if(colab_Threads::haveRightFor('single', $threadRec)){
        		$containersUrl = array('colab_Threads', 'single', 'threadId' => $threadId);
        	}
        }
        
        $this->TAB($threadsUrl, 'Теми', 'contractor');
        $this->TAB($containersUrl, 'Нишка', 'contractor');
        $this->TAB(array('colab_Profiles', 'Single'), 'Профил', 'contractor');
    }
}