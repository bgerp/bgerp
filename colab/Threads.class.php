<?php


/**
 * Прокси на 'colab_Threads' позволяващ на партньор в роля 'contractor' да има достъп до нишките в споделените
 * му папки, ако първия документ в нишката е видим за партньори, и папката е спдоелена към партньора той може да
 * види нишката. При Отваряне на нишката вижда само тези документи, които са видими за партньори
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
	var $loadList = 'colab_Wrapper,Threads=doc_Threads,plg_RowNumbering,Containers=doc_Containers';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'RowNumb=№,title=Заглавие,author=Автор,last=Последно,hnd=Номер,partnerDocCnt=Документи,createdOn=Създаване';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	var $searchFields = 'title';
	
	
	/**
	 * Кой има право да чете?
	 */
	var $canRead = 'contractor';
	
	
	/**
	 * Кой има право да чете?
	 */
	var $canSingle = 'contractor';
	
	
	/**
	 * Кой има право да листва всички профили?
	 */
	var $canList = 'contractor';
	
	
	/**
	 * Извиква се преди изпълняването на екшън
	 */
	public static function on_BeforeAction($mvc, &$res, $action)
	{
		// Изискваме да е логнат потребител
		requireRole('user');
	}
	
	
	/**
	 * Екшън по подразбиране е Single
	 */
	function act_Default()
	{
		// Редиректваме
		return Redirect(array($this, 'list'));
	}
	
	
	/**
	 * Подготвя достъпа до еденичния изглед на една споделена нишка към контрактор
	 */
	function act_Single()
	{
		$this->requireRightFor('single');
		expect($id = Request::get('threadId', 'key(mvc=doc_Threads)'));
		
		$this->currentTab = 'Нишка';
		
		// Създаваме обекта $data
		$data = new stdClass();
		$data->listFields = 'created=Създаване,document=Документи';
		$data->threadId = $id;
		$data->threadRec = $this->Threads->fetch($id);
		$data->folderId = $data->threadRec->folderId;
		
		// Трябва да можем да гледаме сингъла на нишката:
		// Трябва папката и да е споделена на текущия потребител и документа начало на нишка да е видим
		$this->requireRightFor('single', $data->threadRec);
		
		// Показваме само неоттеглените документи, чиито контейнери са видими за партньори
		$data->query = $this->Containers->getQuery();
		$data->query->where("#threadId = {$id}");
		$data->query->where("#visibleForPartners = 'yes'");
		$data->query->where("#state != 'draft'");
		
		$this->prepareTitle($data);
		
		// Извличаме записите
		while ($rec = $data->query->fetch()) {
			$data->recs[$rec->id] = $rec;
		}
		
		// Вербализираме записите
		if(count($data->recs)) {
			foreach($data->recs as $id => $rec) {
				$data->rows[$id] = $this->Containers->recToVerbal($rec, arr::combine($data->listFields, '-list'));
			}
		}
		
		$this->Containers->prepareListToolbar($data);
		
		// Рендираме лист изгледа на контейнера
		$tpl = $this->Containers->renderList_($data);
		
		// Опаковаме изгледа
		$tpl = $this->renderWrapping($tpl, $data);
		
		return $tpl;
	}
	
	
	/**
	 * Подготовка на заглавието на нишката
	 */
	public function prepareTitle(&$data)
	{
		$title = new ET("<div class='path-title'>[#folder#] ([#folderCover#])<!--ET_BEGIN threadTitle--> » [#threadTitle#]<!--ET_END threadTitle--></div>");
		
		$data->folderId = ($data->folderId) ? $data->folderId : Request::get('folderId', 'key(mvc=doc_Folders)'); 
		
		$folderTitle = doc_Folders::getVerbal($data->folderId, 'title');
		if(colab_Threads::haveRightFor('list', $data)){
			$folderTitle = ht::createLink($folderTitle, array('colab_Threads', 'list', 'folderId' => $data->folderId), FALSE, 'ef_icon=img/16/folder-icon.png');
		}
		$coverType = doc_Folders::recToVerbal(doc_Folders::fetch($data->folderId))->type;
		$title->replace($folderTitle, 'folder');
		$title->replace($coverType, 'folderCover');
		
		if($data->threadRec->firstContainerId){
			$document = $this->Containers->getDocument($data->threadRec->firstContainerId);
			$docRow = $document->getDocumentRow();
			$docTitle = str::limitLen($docRow->title, 70);
			$title->replace($docTitle, 'threadTitle');
		}
		
		$data->title = $title;
	}
	
	
	/**
	 * Подготвя редовете във вербална форма
	 */
	function prepareListRows_(&$data)
	{
		if(count($data->recs)) {
			foreach($data->recs as $id => $rec) {
				$row = $this->Threads->recToVerbal($rec);
				
				$docProxy = doc_Containers::getDocument($rec->firstContainerId);
				$docRow = $docProxy->getDocumentRow();
				
				$row->title = $docRow->title;
				if($this->haveRightFor('single', $rec)){
					$row->title = ht::createLink($docRow->title, array($this, 'single', 'threadId' => $id), FALSE, "ef_icon={$docProxy->getIcon()},title=Разглеждане на нишката");
				} 
				
				if($docRow->subTitle) {
           			$row->title .= "\n<div class='threadSubTitle'>{$docRow->subTitle}</div>";
        		}
        		
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
			if($rec->folderState == 'rejected'){
				$requiredRoles = 'no_one';
			}
		}
		
		if($action == 'list'){
			$folderId = setIfNot($rec->folderId, Request::get('folderId', 'key(mvc=doc_Folders)'), Mode::get('lastFolderId'));
			
			$sharedFolders = colab_Folders::getSharedFolders($userId);
				
			if(!in_array($folderId, $sharedFolders)){
				$requiredRoles = 'no_one';
			}
		}
		
		if($action == 'single' && isset($rec)){
			
			// Трябва папката на нишката да е споделена към текущия партньор
			$sharedFolders = colab_Folders::getSharedFolders($userId);
			if(!in_array($rec->folderId, $sharedFolders)){
				$requiredRoles = 'no_one';
			}
			
			// Трябва първия документ в нишката да е видим за партньори
			$firstDocumentIsVisible = doc_Containers::fetchField($rec->firstContainerId, 'visibleForPartners');
			if($firstDocumentIsVisible != 'yes'){
				$requiredRoles = 'no_one';
			} 
			
			$firstDocumentState = doc_Containers::fetchField($rec->firstContainerId, 'state');
			if($firstDocumentState == 'draft'){
				$requiredRoles = 'no_one';
			}
			
			// Ако треда е оттеглен, не може да се гледа от партньора
			if($rec->state == 'rejected'){
				$requiredRoles = 'no_one';
			}
		}
		
		if($requiredRoles != 'no_one'){
			
			// Ако потребителя няма роля партньор, не му е работата тук
			if(!core_Users::isContractor()){
				$requiredRoles = 'no_one';
			}
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
		$res->where("#state != 'rejected'");
		$res->EXT('visibleForPartners', 'doc_Containers', 'externalName=visibleForPartners,externalKey=firstContainerId');
		$res->EXT('firstDocumentState', 'doc_Containers', 'externalName=state,externalKey=firstContainerId');
		$res->where("#visibleForPartners = 'yes'");
		$res->where("#firstDocumentState != 'draft'");
		$res->in('folderId', $sharedFolders);
	
		return $res;
	}
	
	
	/**
	 * Изпълнява се след подготовката на листовия изглед
	 */
	protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
	{
		$mvc->prepareTitle($data);
	}
}