<?php



/**
 * Абстрактен клас за наследяване на протоколи свързани с производството
*
*
* @category  bgerp
* @package   deals
* @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
* @copyright 2006 - 2016 Experta OOD
* @license   GPL 3
* @since     v 0.1
*/
abstract class deals_ManifactureMaster extends core_Master
{
	

	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'storeId, note, folderId';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'valior, title=Документ, storeId, folderId, deadline, createdOn, createdBy';
	
	
	/**
	 * Дали в листовия изглед да се показва бутона за добавяне
	 */
	public $listAddBtn = FALSE;
	
	
	/**
	 * Дата на очакване
	 */
	public $termDateFld = 'deadline';
	
	
   /**
	* Кои са задължителните полета за модела
	*/
	protected static function setDocumentFields($mvc)
	{
		$mvc->FLD('valior', 'date', 'caption=Вальор');
		$mvc->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory,silent');
		$mvc->FLD('deadline', 'datetime', 'caption=Срок до');
		$mvc->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
		$mvc->FLD('state',
				'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно,pending=Заявка)',
				'caption=Статус, input=none'
		);
		
		$mvc->setDbIndex('valior');
	}
	
	
	/**
	 * След рендиране на сингъла
	 */
	protected static function on_AfterRenderSingle($mvc, $tpl, $data)
	{
		if(Mode::is('printing') || Mode::is('text', 'xhtml')){
			$tpl->removeBlock('header');
		}
	}

	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
	    if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')){
	    	if(isset($rec->storeId)){
	    		$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
	    	}
	    }
		
	    $row->valior = (isset($rec->valior)) ? $row->valior : ht::createHint('', 'Вальора ще бъде датата на контиране');
	    
		if($fields['-single']){
			if(isset($rec->storeId)){
				$storeLocation = store_Stores::fetchField($rec->storeId, 'locationId');
				if($storeLocation){
					$row->storeLocation = crm_Locations::getAddress($storeLocation);
				}
			}
		}
		
		if($fields['-list']){
			$row->title = $mvc->getLink($rec->id, 0);
		}
	}
	

	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$folderCover = doc_Folders::getCover($data->form->rec->folderId);
		if($folderCover->haveInterface('store_AccRegIntf')){
			$data->form->setDefault('storeId', $folderCover->that);
		}
	}
	
	
	/**
	 * @see doc_DocumentIntf::getDocumentRow()
	 */
	public function getDocumentRow($id)
	{
		expect($rec = $this->fetch($id));
		$title = $this->getRecTitle($rec);
	
		$row = (object)array(
				'title'    => $title,
				'authorId' => $rec->createdBy,
				'author'   => $this->getVerbal($rec, 'createdBy'),
				'state'    => $rec->state,
				'recTitle' => $title
		);
	
		return $row;
	}
	
	
	/**
	 * Връща масив от използваните нестандартни артикули в протоколa
	 * 
	 * @param int $id - ид на протоколa
	 * @return param $res - масив с използваните документи
	 * 					['class'] - инстанция на документа
	 * 					['id'] - ид на документа
	 */
	public function getUsedDocs_($id)
	{
		return deals_Helper::getUsedDocs($this, $id);
	}
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'activate' && empty($rec->id)){
    		$requiredRoles = 'no_one';
    	}
    	
    	if(!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'storeId')){
    		$requiredRoles = 'no_one';
    	}
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    function updateMaster_($id)
    {
    	// Записваме документа за да му се обновят полетата
    	$rec = $this->fetchRec($id);
    	if ($rec !== FALSE) {
    	    $this->save($rec);
    	}
    }
    
    
    /**
     * Може ли документа да се добави в посочената папка?
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	// Може да добавяме като начало на тред само в папка на склад
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    	
    	return ($folderClass == 'store_Stores' || $folderClass == 'planning_Centers');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	// Може да добавяме или към нишка в която има задание
    	if(planning_Jobs::fetchField("#threadId = {$threadId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')")){
    		return TRUE;
    	}
    	
    	return FALSE;
    }
}