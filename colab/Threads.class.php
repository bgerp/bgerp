<?php


/**
 * Прокси на 'colab_Threads' позволяващ на партньор в роля 'partner' да има достъп до нишките в споделените
 * му папки, ако първия документ в нишката е видим за партньори, и папката е спдоелена към партньора той може да
 * види нишката. При Отваряне на нишката вижда само тези документи, които са видими за партньори
 *
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class colab_Threads extends core_Manager
{
	
	
	/**
	 * Заглавие на мениджъра
	 */
	public $title = "Споделени нишки";
	
	
	/**
	 * Наименование на единичния обект
	 */
	public $singleTitle = "Нишка";
	
	
	/**
	 * 10 секунди време за опресняване на нишката
	 */
	public $refreshRowsTime = 10000;
	
	
	/**
	 * Плъгини и MVC класове, които се зареждат при инициализация
	 */
	public $loadList = 'cms_ExternalWrapper,Threads=doc_Threads,plg_RowNumbering,Containers=doc_Containers, doc_ThreadRefreshPlg, plg_RefreshRows';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'RowNumb=№,title=Заглавие,author=Автор,last=Последно,hnd=Номер,partnerDocCnt=Документи,createdOn=Създаване';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'title';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canRead = 'partner';

	
	/**
	 * Кой има право да чете?
	 */
	public $canSingle = 'partner';
	
	
	/**
	 * Кой има право да листва всички профили?
	 */
	public $canList = 'partner';
	
	
	/**
	 * Инстанция на doc_Threads
	 */
	public $Threads;
	
	
	/**
	 * Инстанция на doc_Threads
	 */
	public $Containers;
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		// Задаваме за полета на проксито, полетата на оригинала
		$mvc->fields = cls::get('doc_Threads')->selectFields();
	}
	
	
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
		return new Redirect(array($this, 'list'));
	}
	
	
	/**
	 * Подготвя достъпа до единичния изглед на една споделена нишка към контрактор
	 */
	function act_Single()
	{
	    expect($id = Request::get('threadId', 'key(mvc=doc_Threads)'));
	    
	    if (core_Users::isPowerUser()) {
	        if (doc_Threads::haveRightFor('single', $id)) {
	            
	            return new Redirect(array('doc_Containers', 'list', 'threadId' => $id));
	        }
	    }
	    
		$this->requireRightFor('single');
		
		$this->currentTab = 'Нишка';
		
		// Създаваме обекта $data
		$data = new stdClass();
		$data->action = 'single';
		$data->listFields = 'created=Създаване,document=Документи';
		$data->threadId = $id;
		$data->threadRec = $this->Threads->fetch($id);
		$data->folderId = $data->threadRec->folderId;
		
		// Трябва да можем да гледаме сингъла на нишката:
		// Трябва папката и да е споделена на текущия потребител и документа начало на нишка да е видим
		$this->requireRightFor('single', $data->threadRec);
		
		// Показваме само неоттеглените документи, чиито контейнери са видими за партньори
		$cu = core_Users::getCurrent();
		$sharedUsers = colab_Folders::getSharedUsers($data->folderId);
		$sharedUsers[$cu] = $cu;
		$sharedUsers = implode(',', $sharedUsers);
		
		$data->query = $this->Containers->getQuery();
		$data->query->where("#threadId = {$id}");
		$data->query->where("#visibleForPartners = 'yes' || #createdBy IN ({$sharedUsers})");
		$data->query->where("#state != 'draft' || (#state = 'draft' AND #createdBy  IN ({$sharedUsers}))");
		$data->query->orderBy('createdOn,id', 'ASC');
		
		$this->prepareTitle($data);
		
		if (!isset($data->recs)) {
		    $data->recs = array();
		}
		
		// Извличаме записите
		while ($rec = $data->query->fetch()) {
			$data->recs[$rec->id] = $rec;
		}
		
		// Вербализираме записите
		if(count($data->recs)) {
		    doc_HiddenContainers::prepareDocsForHide($data->recs);
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
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if(!Request::get('Rejected')) {
			$documents = colab_Setup::get('CREATABLE_DOCUMENTS_LIST');
			$documents = keylist::toArray($documents);
			if(is_array($documents)){
				foreach ($documents as $docId){
					$Doc = cls::get($docId);
			
					if($Doc->haveRightFor('add', (object)array('folderId' => $data->folderId))){
						$data->toolbar->addBtn($Doc->singleTitle, array($Doc, 'add', 'folderId' => $data->folderId, 'ret_url' => TRUE), "ef_icon={$Doc->singleIcon}");
					}
				}
			}
		}
		
		doc_Threads::addBinBtnToToolbar($data);
		
		if(Request::get('Rejected')) {
			$data->toolbar->removeBtn('*', 'with_selected');
			$data->toolbar->addBtn('Всички', array('colab_Threads', 'list', 'folderId' => $data->folderId), 'id=listBtn', 'ef_icon = img/16/application_view_list.png');
		}
	}
	
	
	/**
	 * 
	 * 
	 * @see core_Manager::act_List()
	 */
	function act_List()
	{
	    if (core_Users::isPowerUser()) {
	        $folderId = Request::get('folderId', 'int');
	        if ($folderId && doc_Folders::haveRightFor('single', $folderId)) {
	            
	            return new Redirect(array('doc_Threads', 'list', 'folderId' => $folderId));
	        }
	    }
	    
	    return parent::act_List();
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
        		
                $row->allDocCnt = $row->partnerDocCnt;

				$data->rows[$id] = $row;
			}
		}
	}
	
	
	/**
	 * Преди подготовка на данните за табличния изглед правим филтриране
	 * на записите, които са (или не са) оттеглени и сортираме от нови към стари
	 */
	public static function on_BeforePrepareListRecs($mvc, &$res, $data)
	{
		if(isset($data->query)) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->rejQuery->where("#state = 'rejected'");
         	    $data->query->where("#state != 'rejected' OR #state IS NULL");
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
		$data->listFilter->setField('folderId', 'input=hidden,silent');
		$data->listFilter->FNC('order', 'enum(' . doc_Threads::filterList . ')',
				'allowEmpty,caption=Подредба,input,silent,autoFilter');
		$data->listFilter->FNC('documentClassId', "class(interface=doc_DocumentIntf,select=title,allowEmpty)", 'caption=Вид документ,input,recently');
		
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->showFields = 'folderId,search,order,documentClassId';
		
		$data->listFilter->input(NULL, 'silent');
		
		$documentsInThreadOptions = doc_Threads::getDocumentTypesOptionsByFolder($data->listFilter->rec->folderId, TRUE);
		if(count($documentsInThreadOptions)) {
			$documentsInThreadOptions = array_map('tr', $documentsInThreadOptions);
			$data->listFilter->setOptions('documentClassId', $documentsInThreadOptions);
		} else {
			$data->listFilter->setReadOnly('documentClassId');
		}
		
		doc_Threads::applyFilter($data->listFilter->rec, $data->query);
		$data->rejQuery = clone($data->query);
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		// Ако пакета 'colab' не е инсталиран, никой не може нищо
		if(!core_Packs::isInstalled('colab')){
			$requiredRoles = 'no_one';
			return;
		}
		
		if($action == 'list' && isset($rec->folderId)) {
			if($rec->folderState == 'rejected'){
				$requiredRoles = 'no_one';
			}
		}
		
		if($action == 'list') {
			
    		if (is_null($userId)) {
    	        $requiredRoles = 'no_one';
    	    } else {
        	    $folderId = setIfNot($rec->folderId, Request::get('folderId', 'key(mvc=doc_Folders)'), Mode::get('lastFolderId'));
    			$sharedFolders = colab_Folders::getSharedFolders($userId);
    				
    			if(!in_array($folderId, $sharedFolders)){
    				$requiredRoles = 'no_one';
    			}
    	    }
		}
		
		if($action == 'single' && isset($rec)){
			
		    if (is_null($userId)) {
    	        $requiredRoles = 'no_one';
    	    } else {
        	    // Трябва папката на нишката да е споделена към текущия партньор
    			$sharedFolders = colab_Folders::getSharedFolders($userId);
    			if(!in_array($rec->folderId, $sharedFolders)){
    				$requiredRoles = 'no_one';
    			}
    	    }
			
			if ($rec->firstContainerId) {
				$sharedUsers = colab_Folders::getSharedUsers($rec->folderId);
				$sharedUsers[$userId] = $userId;
				
    			// Трябва първия документ в нишката да е видим за партньори
    			$firstDocumentIsVisible = doc_Containers::fetchField($rec->firstContainerId, 'visibleForPartners');
    			if($firstDocumentIsVisible != 'yes' && !in_array($rec->createdBy, $sharedUsers)){
    				$requiredRoles = 'no_one';
    			} 
    			
    			$firstDocumentState = doc_Containers::fetchField($rec->firstContainerId, 'state');
    			if($firstDocumentState == 'draft' && !in_array($rec->createdBy, $sharedUsers)){
    				$requiredRoles = 'no_one';
    			}
			} else {
			    $requiredRoles = 'no_one';
			}
		}
		
		if($requiredRoles != 'no_one'){
			
			// Ако потребителя няма роля партньор, не му е работата тук
			if(!core_Users::haveRole('partner', $userId)){
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
		
		$cu = core_Users::getCurrent();
		$sharedFolders = colab_Folders::getSharedFolders();
		$sharedUsers = colab_Folders::getSharedUsers($folderId);
		$sharedUsers[$cu] = $cu;
		$sharedUsers = implode(',', $sharedUsers);
		
		$params['where'][] = "#folderId = {$folderId}";
		$res = $this->Threads->getQuery($params);
		$res->EXT('visibleForPartners', 'doc_Containers', 'externalName=visibleForPartners,externalKey=firstContainerId');
		$res->EXT('firstDocumentState', 'doc_Containers', 'externalName=state,externalKey=firstContainerId');
		$res->where("#visibleForPartners = 'yes' || #createdBy IN ({$sharedUsers})");
		$res->where("#firstDocumentState != 'draft' || (#firstDocumentState = 'draft' AND #createdBy IN ({$sharedUsers}))");
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
    
    
    /**
     * Връща хеша за листовия изглед. Вика се от bgerp_RefreshRowsPlg
     *
     * @param string $status
     *
     * @return string
     * @see plg_RefreshRows
     */
    public static function getContentHash_(&$status)
    {
        doc_Threads::getContentHash_($status);
    }
}
