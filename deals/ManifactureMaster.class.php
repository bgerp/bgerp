<?php



/**
 * Абстрактен клас за наследяване на протоколи свързани с производството
*
*
* @category  bgerp
* @package   deals
* @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
* @copyright 2006 - 2014 Experta OOD
* @license   GPL 3
* @since     v 0.1
*/
abstract class deals_ManifactureMaster extends core_Master
{
	
	
	/**
	 * Опашка от записи за записване в on_Shutdown
	 */
	protected $updated = array();


	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'activityCenterId, storeId, note, folderId';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'tools=Пулт, valior, title=Документ, activityCenterId, storeId, folderId, deadline, createdOn, createdBy';
	
	
   /**
	* Кои са задължителните полета за модела
	*/
	protected static function setDocumentFields($mvc)
	{
		$mvc->FLD('valior', 'date', 'caption=Вальор, mandatory');
		$mvc->FLD('activityCenterId', 'key(mvc=hr_Departments,select=name)', 'caption=Център на дейност, mandatory');
		$mvc->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад, mandatory');
		$mvc->FLD('deadline', 'datetime', 'caption=Срок до');
		$mvc->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
		$mvc->FLD('state',
				'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)',
				'caption=Статус, input=none'
		);
	}
	
	
	/**
	 * След рендиране на сингъла
	 */
	public static function on_AfterRenderSingle($mvc, $tpl, $data)
	{
		if(Mode::is('printing') || Mode::is('text', 'xhtml')){
			$tpl->removeBlock('header');
		}
	}

	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if($fields['-single']){
			
			$storeLocation = store_Stores::fetchField($rec->storeId, 'locationId');
			if($storeLocation){
				$row->storeLocation = crm_Locations::getAddress($storeLocation);
			}
			
			$actLocation = hr_Departments::fetchField($rec->activityCenterId, 'locationId');
			if($actLocation){
				$row->centerLocation = crm_Locations::getAddress($actLocation);
			}
			
			$row->baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);
		}
		 
		if($fields['-list']){
			$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
			$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
			$row->activityCenterId = hr_Departments::getHyperlink($rec->activityCenterId, TRUE);
			$row->title = $mvc->getLink($rec->id, 0);
		}
	}
	

	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$data->form->setDefault('valior', dt::now());
		$data->form->setReadOnly('storeId', doc_Folders::getCover($data->form->rec->folderId)->that);
		$data->form->setDefault('activityCenterId', hr_Departments::fetchField("#systemId = 'myOrganisation'", 'id'));
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
	 * Връща разбираемо за човека заглавие, отговарящо на записа
	 */
	public static function getRecTitle($rec, $escaped = TRUE)
	{
		$self = cls::get(get_called_class());
		
		return tr("|{$self->singleTitle}|* №") . $rec->id;
	}
	
	
	/**
	 * Връща масив от използваните нестандартни артикули в протоколa
	 * @param int $id - ид на протоколa
	 * @return param $res - масив с използваните документи
	 * 					['class'] - инстанция на документа
	 * 					['id'] - ид на документа
	 */
	public function getUsedDocs_($id)
	{
		$res = array();
		$Detail = $this->mainDetail;
		$dQuery = $this->$Detail->getQuery();
		$dQuery->EXT('state', $this->className, "externalKey={$this->$Detail->masterKey}");
		$dQuery->where("#{$this->$Detail->masterKey} = '{$id}'");
		$dQuery->groupBy('productId,classId');
		while($dRec = $dQuery->fetch()){
			$productMan = cls::get($dRec->classId);
			if(cls::haveInterface('doc_DocumentIntf', $productMan)){
				$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
			}
		}
		 
		return $res;
	}
	
	
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('store_AccRegIntf');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return cls::haveInterface('store_AccRegIntf', $folderClass);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$threadRec = doc_Threads::fetch($threadId);
    	$coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
    
    	return cls::haveInterface('store_AccRegIntf', $coverClass);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'activate' && empty($rec->id)){
    		$requiredRoles = 'no_one';
    	}
    }
    
    
    /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
    	// Запомняне кои документи трябва да се обновят
    	$mvc->updated[$id] = $id;
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
    	if(count($mvc->updated)){
    		foreach ($mvc->updated as $id) {
    			$mvc->updateMaster($id);
    		}
    	}
    }
    
    
    /**
     * Обновява информацията на документа
     * @param int $id - ид на документа
     */
    public function updateMaster($id)
    {
    	// Обновяваме класа за всеки случай
    	$rec = $this->fetchRec($id);
    	$this->save($rec);
    }
}