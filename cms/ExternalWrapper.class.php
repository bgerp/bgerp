<?php



/**
 * Клас 'cms_ExternalWrapper'
 *
 * Обвивка за външни потребители
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_ExternalWrapper extends plg_ProtoWrapper
{
    
	
	/**
	 * HTML клас за табовете на обвивката
	 */
	protected $htmlClass = 'foldertabs';
	
	
    /**
     * Описание на табовете
     */
    function description()
    { 
    	if(core_Packs::isInstalled('colab')){
    		if(core_Users::haveRole('partner')){
    			$this->getContractorTabs();
    		}
    	}
    	
    	if(core_Packs::isInstalled('eshop')){
    		//@TODO кошница за уеб магазина
    	}
    	
    	$this->TAB(array('cms_Profiles', 'Single'), 'Профил', 'partner,powerUser');
    }
    
    
    /**
     * Какви табове да се добавят ако потребителя е контрактор
     */
    private function getContractorTabs()
    {
    	$threadsUrl = $containersUrl = array();
    	
    	$threadId = Request::get('threadId', 'int');
    	$folderId = Request::get('folderId', 'key(mvc=doc_Folders,select=title)');
    	
    	if(colab_Folders::getSharedFoldersCount() > 1){
    		$this->TAB('colab_Folders', 'Папки', 'partner');
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
    	
    	$this->TAB($threadsUrl, 'Теми', 'partner');
    	$this->TAB($containersUrl, 'Нишка', 'partner');
    }
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl, $blankTpl, $data = NULL)
    {
    	// Редниране на обвивката от бащата
    	parent::on_AfterRenderWrapping($invoker, $tpl, $blankTpl, $data);
    	
    	// Обграждаме обвивката със div
    	if($tpl instanceof core_ET){
    		$tpl->prepend("<div class = 'contractorExtHolder'>");
    		$tpl->append("</div>");
    	}
    }
}