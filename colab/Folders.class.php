<?php


/**
 * Прокси на 'colab_Folders' позволяващ на потребител с роля 'partner' да вижда споделените му папки
 *
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class colab_Folders extends core_Manager
{
	
	
	/**
	 * Заглавие на мениджъра
	 */
	var $title = "Споделени папки";
	
	
	/**
	 * Наименование на единичния обект
	 */
	var $singleTitle = "Папка";
	
	
	/**
	 * 10 секунди време за опресняване на нишката
	 */
	public $refreshRowsTime = 10000;
	
	
	/**
	 * Плъгини и MVC класове, които се зареждат при инициализация
	 */
	var $loadList = 'cms_ExternalWrapper,Folders=doc_Folders,plg_RowNumbering,plg_Search, plg_RefreshRows';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	var $listFields = 'RowNumb=№,title=Заглавие,type=Тип,last=Последно';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	var $searchFields = 'title';
	
	
	/**
	 * Кой има право да чете?
	 */
	var $canRead = 'partner';
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		// Задаваме за полета на проксито, полетата на оригинала
		$mvc->fields = cls::get('doc_Folders')->selectFields();
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
	 * 
	 * 
	 * @see core_Manager::act_List()
	 */
	function act_List()
	{
	    if (core_Users::isPowerUser()) {
	        if (doc_Folders::haveRightFor('list')) {
	            
	            return new Redirect(array('doc_Folders', 'list'));
	        }
	    }
	    
	    return parent::act_List();
	}
	
	
	/**
	 * Подготвя редовете във вербална форма
	 */
	function prepareListRows_(&$data)
	{
		if(count($data->recs)) {
			foreach($data->recs as $id => $rec) {
				
				$title = $this->Folders->getVerbal($rec, 'title');
				$row = $this->Folders->recToVerbal($rec, $this->listFields);
				$row->title = $title;
				
				if(colab_Threads::haveRightFor('list', (object)array('folderId' => $rec->id))){
					$row->title = ht::createLink($row->title, array('colab_Threads', 'list', 'folderId' => $rec->id), FALSE, 'ef_icon=img/16/folder-icon.png');
				}
				
				$data->rows[$id] = $row;
			}
		}
	
		return $data;
	}
	
	
	/**
	 * Малко манипулации след подготвянето на формата за филтриране
	 */
	protected static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->showFields = 'search';
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
	}
	
	
	/**
	 * Връща асоциирана db-заявка към MVC-обекта
	 *
	 * @return core_Query
	 */
	function getQuery_($params = array())
	{
		$res = $this->Folders->getQuery($params);
		$sharedFolders = self::getSharedFolders();
		
		$res->in('id', $sharedFolders);
		
		return $res;
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
		
		if($action == 'list'){
			$sharedFolders = self::getSharedFolders($userId);
			
			if(count($sharedFolders) <= 1){
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
	 * Връща всички споделени папки до този контрактор
	 */
	public static function getSharedFolders($cu = NULL)
	{
		if(!$cu){
			$cu = core_Users::getCurrent();
		}
		
		$sharedFolders = array();
		
		if (!$cu) return $sharedFolders;
		
		$sharedQuery = colab_FolderToPartners::getQuery();
		$sharedQuery->EXT('state', 'doc_Folders', 'externalName=state,externalKey=folderId');
		$sharedQuery->where("#contractorId = {$cu}");
		$sharedQuery->where("#state != 'rejected'");
		$sharedQuery->show('folderId');
		$sharedQuery->groupBy('folderId');
		while($fRec = $sharedQuery->fetch()){
			$sharedFolders[$fRec->folderId] = $fRec->folderId;
		}
		
		return $sharedFolders;
	}
	
	
	/**
	 * Връща споделените партнори в посочената папка
	 * 
	 * @param int $folderId - папка
	 * @return array $users - споделени партньори
	 */
	public static function getSharedUsers($folderId)
	{
		$users = array();
		
		// Намиране на всички потребители споделени в посочените папки
		$query = colab_FolderToPartners::getQuery();
		$query->where(array("#folderId = [#1#]", $folderId));
		$query->show('contractorId');
		
		$all = $query->fetchAll();
		$users = arr::extractValuesFromArray($all, 'contractorId');
		
		return $users;
	}
	
	
	/**
	 * Броя на споделените папки на потребителя
	 */
	public static function getSharedFoldersCount()
	{
		$cu = core_Users::getCurrent('id');
		
		// Това е броя на споделените папки към контрактора
		$sharedFolders = static::getSharedFolders($cu);
		$count = count($sharedFolders);
		
		return $count;
	}
	
	
	/**
	 * Изпълнява се след подготовката на листовия изглед
	 */
	protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
	{
		$cuRec = core_Users::fetch(core_Users::getCurrent());
		$names = core_Users::getVerbal($cuRec, 'names');
		$nick = core_Users::getVerbal($cuRec, 'nick');
		
		$data->title = "|Папките на|* <span style='color:green'>{$names} ({$nick})</span>";
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
        doc_Folders::getContentHash_($status);
    }
}
