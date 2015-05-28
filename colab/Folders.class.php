<?php


/**
 * Прокси на 'doc_Folders' позволяващ на външен потребител с роля 'user'
 * до споделени папки
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
	var $listFields = 'RowNumb=№,title=Заглавие,last=Последно';
	
	
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
		return Redirect(array($this, 'list'));
	}
	
	
	/**
	 * Подготвя редовете във вербална форма
	 */
	function prepareListRows_(&$data)
	{
		if(count($data->recs)) {
			foreach($data->recs as $id => $rec) {
				$row = new stdClass();
				$row->title = $this->Folders->getVerbal($rec, 'title');
				$row->last = $this->Folders->getVerbal($rec, 'last');
				
				if(colab_Threads::haveRightFor('list', (object)array('folderId' => $rec->id))){
					$row->title = ht::createLink($row->title, array('colab_Threads', 'list', 'folderId' => $rec->id), FALSE, 'ef_icon=img/16/folder-icon.png');
				}
				
				$row->ROW_ATTR['class'] .= " state-{$rec->state}";
				$row->STATE_CLASS .= " state-{$rec->state}";
				
				$data->rows[$id] = $row;
			}
		}
	
		return $data;
	}
	
	
	/**
	 * Малко манипулации след подготвянето на формата за филтриране
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
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
	
	
	public static function getSharedFolders($cu = NULL)
	{
		if(!$cu){
			$cu = core_Users::getCurrent('id');
		}
		
		$sharedFolders = array();
		$sharedQuery = doc_FolderToPartners::getQuery();
		$sharedQuery->where("#contractorId = {$cu}");
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
		return doc_FolderToPartners::count("#contractorId = {$cu}");
	}
}