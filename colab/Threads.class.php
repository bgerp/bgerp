<?php


/**
 * Прокси на 'doc_Threads' позволяващ на външен потребител с роля 'user'
 * до нишките от споделените папки
 *
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class colab_Threads extends core_Manager
{
	
	
	/**
	 * Заглавие на мениджъра
	 */
	var $title = "Споделени нишки";
	
	
	/**
	 * Наименование на единичния обект
	 */
	var $singleTitle = "Нишка";
	
	
	/**
	 * Плъгини и MVC класове, които се зареждат при инициализация
	 */
	var $loadList = 'colab_Wrapper,Threads=doc_Threads,plg_RowNumbering';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'RowNumb=№,title=Заглавие,author=Автор,last=Последно,hnd=Номер,allDocCnt=Документи,createdOn=Създаване';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	var $searchFields = 'title';
	
	
	/**
	 * Кой  може да пише?
	 */
	//var $canWrite = 'no_one';
	
	
	/**
	 * Кой има право да чете?
	 */
	var $canRead = 'contractor';
	
	
	/**
	 * Кой има право да чете?
	 */
	var $canBrowse = 'contractor';
	
	
	/**
	 * Кой има право да листва всички профили?
	 */
	var $canList = 'contractor';
	
	
	/**
	 * Екшън по подразбиране е Single
	 */
	function act_Default()
	{
		// Изискваме да е логнат потребител
		requireRole('user');
	
		// Редиректваме
		return Redirect(array($this, 'browse'));
	}
	
	
	function act_Browse()
	{
		$this->haveRightFor('browse');
		expect($folderId = Request::get('folderId', 'key(mvc=doc_Folders)'));
		
		$data = new stdClass();
		$data->rec = new stdClass();
		$data->rec->folderId = $folderId; 
		$data->rec->folderState = doc_Folders::fetchField($folderId, 'state');
		$this->requireRightFor('browse', $data->rec);
		bp($folderId,$data->rec);
	}
	
	
	/**
	 * Подготвя редовете във вербална форма
	 */
	function prepareListRows_(&$data)
	{
		if(count($data->recs)) {
			foreach($data->recs as $id => $rec) {
				$row = $this->Threads->recToVerbal($rec);
				$data->rows[$id] = $row;
			}
		}
	}
	
	
	/**
	 * Подготвя формата за филтриране
	 */
	function prepareListFilter_($data)
	{
		parent::prepareListFilter_($data);
		$data->listFilter->FNC('search', 'varchar', 'caption=Ключови думи,input,silent,recently');
		$data->listFilter->FNC('folderId', 'key(mvc=doc_Folders)', 'input=hidden,silent');
		$data->listFilter->FNC('order', 'enum(open=Първо отворените, recent=По последно, create=По създаване, numdocs=По брой документи)',
				'allowEmpty,caption=Подредба,input,silent,refreshForm');
		
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->showFields = 'folderId,search,order';
		
		$data->listFilter->input(NULL, 'silent');
		
		doc_Threads::applyFilter($data->listFilter->rec, $data->query);
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'list' && isset($rec->folderId)){
			
			$sharedFolders = colab_Folders::getSharedFolders($userId);
			
			if(!in_array($rec->folderId, $sharedFolders)){
				$requiredRoles = 'no_one';
			}
				
			if($rec->folderState == 'rejected'){
				$requiredRoles = 'no_one';
			}
			
			if($requiredRoles != 'no_one'){
				//$firstContainerId = $mvc->Threads->fetchField($rec->folderId, 'firstContainerId');
				//$container = doc_Containers::fetch($firstContainerId);
				//if(!$container->visibleForPartners){
					//$requiredRoles = 'no_one';
				//}
			}
		}
		
		if($action == 'list'){
			$folderId = ($rec->folderId) ? $rec->folderId : Request::get('folderId', 'key(mvc=doc_Folders)');
			
			$tQuery = $mvc->getQuery(array('folderId' => $folderId));
			if(!$tQuery->count()){
				$requiredRoles = 'no_one';
			}
		}
		
		if(core_Users::haveRole('powerUser', $userId)){
			$requiredRoles = 'no_one';
		}
	}
	
	
	/**
	 * Връща асоциирана db-заявка към MVC-обекта
	 *
	 * @return core_Query
	 */
	function getQuery_($params = array())
	{
		if(empty($params['folderId'])){
			expect($folderId = Request::get('folderId', 'key(mvc=doc_Folders)'));
		} else {
			$folderId = $params['folderId'];
		}
		
		$sharedFolders = cls::get('colab_Folders')->getSharedFolders();
		
		$params['where'][] = "#folderId = {$folderId}";
		$res = $this->Threads->getQuery($params);
		$res->EXT('visibleForPartners', 'doc_Containers', 'externalName=visibleForPartners,externalKey=firstContainerId');
		$res->where("#visibleForPartners = 'yes'");
		$res->in('folderId', $sharedFolders);
	
		return $res;
	}
}