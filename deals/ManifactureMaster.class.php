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
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'storeId, note, folderId';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'tools=Пулт, valior, title=Документ, storeId, folderId, deadline, createdOn, createdBy';
	
	
   /**
	* Кои са задължителните полета за модела
	*/
	protected static function setDocumentFields($mvc)
	{
		$mvc->FLD('valior', 'date', 'caption=Вальор, mandatory');
		$mvc->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory');
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
		$row->storeId = store_Stores::getHyperlink($rec->storeId);
		
		if($fields['-single']){
			
			$storeLocation = store_Stores::fetchField($rec->storeId, 'locationId');
			if($storeLocation){
				$row->storeLocation = crm_Locations::getAddress($storeLocation);
			}
			
			$row->baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);
		}
		 
		if($fields['-list']){
			$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
			$row->title = $mvc->getLink($rec->id, 0);
		}
	}
	

	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$data->form->setDefault('valior', dt::now());
		$folderCover = doc_Folders::getCover($data->form->rec->folderId);
		if($folderCover->haveInterface('store_AccRegIntf')){
			$data->form->setReadOnly('storeId', $folderCover->that);
		} else {
			$curStore = store_Stores::getCurrent('id', FALSE);
			$data->form->setDefault('storeId', $curStore);
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
			if(isset($dRec->classId) && isset($dRec->productId)){
				$productMan = cls::get($dRec->classId);
				if(cls::haveInterface('doc_DocumentIntf', $productMan)){
					$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
				}
			}
		}
		 
		return $res;
	}
	
	
	/**
	 * Добавя ключови думи за пълнотекстово търсене
	 */
	public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
	{
		if($rec->id){
			$detailsKeywords = '';
			
			$Detail = $mvc->mainDetail;
			$dQuery = $Detail::getQuery();
			$dQuery->where("#{$mvc->$Detail->masterKey} = '{$rec->id}'");
			$dQuery->show('productId');
			while($dRec = $dQuery->fetch()){
				$detailsKeywords .= " " . plg_Search::normalizeText(cat_Products::getTitleById($dRec->productId));
			}
			
			$res = " " . $res . " " . $detailsKeywords;
		}
	}
	
	
	/**
     * В кои корици може да се вкарва документа
     * 
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
		// Може да добавяме като начало на тред само в папка на склад
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
    	// Може да добавяме или към нишка с начало задание
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	if($firstDoc->getInstance() instanceof planning_Jobs){
    		
    		return TRUE;
    	} 
    	
    	$folderId = doc_Threads::fetchField($threadId, 'folderId');
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    
    	// или към нишка в папка на склад
    	return cls::haveInterface('store_AccRegIntf', $folderClass);
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
}