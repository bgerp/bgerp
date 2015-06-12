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
	 * HTML клас за табовете на обвивката
	 */
	protected $htmlClass = 'foldertabs';

	
    /**
     * Описание на опаковката с табове
     */
    function description()
    {
    	$threadsUrl = $containersUrl = array();
        
        $threadId = Request::get('threadId', 'int');
        $folderId = Request::get('folderId', 'key(mvc=doc_Folders,select=title)');
        
        if(colab_Folders::count() != 1){
        	$this->TAB('colab_Folders', 'Папки', 'contractor');
        } else {
        	$query = colab_Folders::getQuery();
        	$folderId = $query->fetch()->id;
        }
        
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
    
    
    /**
     * Извиква се след изпълняването на екшън
     */
    public static function on_AfterAction(&$invoker, &$tpl, $act)
    {
    	if($tpl instanceof core_ET){
    		
    		// Обграждаме обвивката със div
    		$tpl->prepend("<div class = 'contractorExtHolder'>");
    		$tpl->append("</div>");
    	}
    }
}