<?php


/**
 * Прокси на 'colab_Folders' позволяващ на потребител с роля 'contractor' да вижда споделените му папки
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
	 * Плъгини и MVC класове, които се зареждат при инициализация
	 */
	var $loadList = 'colab_Wrapper,Folders=doc_Folders,plg_RowNumbering,plg_Search';
	
	
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
	var $canRead = 'contractor';
	
	
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
		$sharedFolders = self::getSharedFolders($cu);
		
		$res->in('id', $sharedFolders);
		
		return $res;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'list'){
			$sharedFolders = self::getSharedFolders($userId);
			
			if(count($sharedFolders) <= 1){
				$requiredRoles = 'no_one';
			}
		}
		
		if(core_Users::haveRole('powerUser', $userId)){
			$requiredRoles = 'no_one';
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
		$sharedQuery = doc_FolderToPartners::getQuery();
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
	 * Броя на записите
	 */
	public static function count($cond = '1=1')
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
}