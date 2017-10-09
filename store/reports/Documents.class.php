<?php


/**
 * Драйвер за складови документи
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Логистика » Складови документи
 */
class store_reports_Documents extends frame2_driver_TableData
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'ceo, store';
	
	
	/**
	 * Полета за хеширане на таговете
	 *
	 * @see uiext_Labels
	 * @var varchar
	 */
	protected $hashField = 'containerId';
	
	
	/**
	 * Полета от таблицата за скриване, ако са празни
	 *
	 * @var int
	 */
	protected $filterEmptyListFields = 'pallets,lineId';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholder=Всички,after=title');
		$fieldset->FLD('documentType', 'class(select=title)', 'caption=Документи,placeholder=Всички,after=storeId');
		$fieldset->FLD('horizon', 'time', 'caption=Хоризонт,after=documentType');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
	{
		$form = &$data->form;
		
		$stores = self::getContableStores($form->rec);
		$form->setOptions('storeId', array('' => '') + $stores);
		$documents = array('planning_ConsumptionNotes', 'planning_ReturnNotes', 'store_Transfers', 'store_ShipmentOrders', 'store_Receipts', 'planning_DirectProductionNote', 'store_ConsignmentProtocols');
	
		$docOptions = array();
		foreach ($documents as $className){
			$classId = $className::getClassId();
			$docOptions[$classId] = core_Classes::getTitleById($classId, FALSE);
		}
		$form->setOptions('documentType', array('' => '') + $docOptions);
	}
	
	
	/**
	 * Връща складовете, в които може да контира потребителя
	 * @return array $res
	 */
	public static function getContableStores($rec)
	{
		$res = array();
		$cu = (!empty($rec->createdBy)) ? $rec->createdBy : core_Users::getCurrent();
		
		$sQuery = store_Stores::getQuery();
		$sQuery->where("#state != 'rejected'");
		while($sRec = $sQuery->fetch()){
			if(bgerp_plg_FLB::canUse('store_Stores', $sRec, $cu)){
				$res[$sRec->id] = store_Stores::getTitleById($sRec->id, FALSE);
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Кои записи ще се показват в таблицата
	 *
	 * @param stdClass $rec
	 * @param stdClass $data
	 * @return array
	 */
	protected function prepareRecs($rec, &$data = NULL)
	{
		$recs = array();
		$storeIds = isset($rec->storeId) ? array($rec->storeId => $rec->storeId) : array_keys(self::getContableStores($rec));
		if(!count($storeIds)) return $recs;
		$documentFld = ($rec->documentType) ? 'documentType' : 'document';
		
		foreach (array('planning_ConsumptionNotes', 'planning_ReturnNotes') as $pDoc){
			if(empty($rec->{$documentFld}) || ($rec->{$documentFld} == $pDoc::getClassId())){
				$cQuery = $pDoc::getQuery();
				self::applyFilters($cQuery, $storeIds, $pDoc, $rec, 'deadline');
				while($cRec = $cQuery->fetch()){
					$recs[$cRec->containerId] = (object)array('containerId' => $cRec->containerId,
															  'stores'      => array($cRec->storeId), 
													          'dueDate'     => $cRec->deadline,
													          'weight'      => NULL,
													          'pallets'     => NULL,
													          'linked'      => $this->getLinkedDocuments($cRec->containerId),
													          'folderId'    => $cRec->folderId,
													          'createdOn'   => $cRec->createdOn,
													          'createdBy'   => $cRec->createdBy,
													          'modifiedOn'  => $cRec->modifiedOn,
					);
				}
			}
		}
		
		foreach (array('store_ShipmentOrders', 'store_Receipts', 'store_Transfers', 'store_ConsignmentProtocols') as $pDoc){
			if(empty($rec->{$documentFld}) || ($rec->{$documentFld} == $pDoc::getClassId())){
				$Document = cls::get($pDoc);
				$deadlineFld = ($pDoc != 'store_ConsignmentProtocols') ? 'deliveryTime' : 'valior';
				
				$sQuery = $Document->getQuery();
				self::applyFilters($sQuery, $storeIds, $pDoc, $rec, $deadlineFld);
				while($sRec = $sQuery->fetch()){
					$linked = $this->getLinkedDocuments($sRec->containerId);
					if(!empty($sRec->lineId)){
						$lineCid = trans_Lines::fetchField($sRec->lineId, 'containerId');
						$linked[$lineCid] = $lineCid;
					}
					$stores = ($pDoc != 'store_Transfers') ? array($sRec->storeId) : array($sRec->fromStore, $sRec->toStore);
					
					$measures = $Document->getTotalTransportInfo($sRec->id);
					setIfNot($sRec->{$Document->totalWeightFieldName}, $measures->weight); 
					$sRec->{$Document->totalWeightFieldName} = ($sRec->weightInput) ? $sRec->weightInput : $sRec->{$Document->totalWeightFieldName};
					
					$recs[$sRec->containerId] = (object)array('containerId' => $sRec->containerId,
														      'stores'      => $stores,
													          'dueDate'     => $sRec->deliveryTime,
													  		  'weight'      => $sRec->{$Document->totalWeightFieldName},
													  		  'pallets'     => NULL, // @TODO
													  		  'linked'      => $linked,
													  		  'folderId'    => $sRec->folderId,
													 		  'createdOn'   => $sRec->createdOn,
													  		  'createdBy'   => $sRec->createdBy,
													 		  'modifiedOn'  => $sRec->modifiedOn,
					);
				}
			}
		}
		
		if(empty($rec->{$documentFld}) || ($rec->{$documentFld} == planning_DirectProductionNote::getClassId())){
			$pQuery = planning_DirectProductionNote::getQuery();
			$pQuery->where("#deadline IS NOT NULL");
			self::applyFilters($pQuery, $storeIds, 'planning_DirectProductionNote', $rec, 'deadline');
			while($pRec = $pQuery->fetch()){
				$recs[$pRec->containerId] = (object)array('containerId' => $pRec->containerId,
													      'stores'      => array($pRec->storeId),
												  		  'dueDate'     => $pRec->deadline,
												  		  'weight'      => NULL,
											      		  'pallets'     => NULL,
												  		  'linked'      => $this->getLinkedDocuments($pRec->containerId),
											      		  'folderId'    => $pRec->folderId,
												  		  'createdOn'   => $pRec->createdOn,
												  		  'createdBy'   => $pRec->createdBy,
												  		  'modifiedOn'  => $pRec->modifiedOn,
				);
			}
		}
		
		if(count($recs)){
			$dueDateArr = array_filter($recs, function($a) {return !empty($a->dueDate);});
			$noDueDateArr = array_filter($recs, function($a) {return empty($a->dueDate);});
			
			uasort($dueDateArr, function($a, $b) {
				if($a->dueDate === $b->dueDate){
					return ($a->modifiedOn < $b->modifiedOn) ? -1 : 1;
				}
			
				return ($a->dueDate < $b->dueDate) ? -1 : 1;
			
			});
		}
		
		$recs = $dueDateArr + $noDueDateArr;
		
		return $recs;
	}
	
	
	/**
	 * Връща линкнатите документи към контейнера
	 * 
	 * @param int $containerId
	 * 
	 * @return array $linked
	 */
	private function getLinkedDocuments($containerId)
	{
		$linked = array();
        
		$cQuery = doc_Linked::getQuery();
		$cQuery->where(array("#outVal = '[#1#]'", $containerId));
		$cQuery->where("#outType = 'doc'");
		$cQuery->where("#inType = 'doc'");
		
		$cQuery->where("#state != 'rejected'");
		
		$cQuery->EXT('cState', 'doc_Containers', 'externalName=state,externalKey=inVal');
		$cQuery->where("#cState != 'rejected'");
		
		$cQuery->show('inVal');
		
		$cQuery->orderBy('createdOn', 'DESC');
		
		while($cRec = $cQuery->fetch()){
		    $linked[$cRec->inVal] = $cRec->inVal;
		}
		
		return $linked;
	}
	
	
	/**
	 * Добавя филтър към зааявката на документите
	 */
	protected function applyFilters(&$query, $storeIds, $mvc, $rec, $termDateField)
	{
		if($mvc == 'store_Transfers'){
			$storeIds = implode(',', $storeIds);
			$query->where("#fromStore IN ($storeIds) OR #toStore IN ($storeIds)");
		} else {
			$query->in('storeId', $storeIds);
		}
		$query->where("#state = 'pending'");	
		
		if(!empty($rec->horizon)){
			$horizon = dt::addSecs($rec->horizon, dt::today(), FALSE);
			$query->where("(#{$termDateField} IS NOT NULL AND #{$termDateField} <= '{$horizon} 23:59:59') OR #{$termDateField} IS NULL");
		}
	}
	
	
	/**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec  - записа
	 * @param stdClass $dRec - чистия запис
	 * @return stdClass $row - вербалния запис
	*/
	protected function detailRecToVerbal($rec, &$dRec)
	{
		$isPlain = Mode::is('text', 'plain');
		$row = new stdClass();
		
		$Document = doc_Containers::getDocument($dRec->containerId);
		$row->createdBy = crm_Profiles::createLink($dRec->createdBy);
		if($isPlain){
			$row->createdBy = strip_tags(($row->createdBy instanceof core_ET) ? $row->createdBy->getContent() : $row->createdBy);
		}
		
		$handle = $Document->getHandle();
		$row->documentType = "#{$handle}";
		
		if(!Mode::isReadOnly() && !$isPlain){
			$singleUrl = $Document->getSingleUrlArray();
			if (empty($url) && frame2_Reports::haveRightFor('single', $rec->id) && $rec->state != 'rejected') {
				$singleUrl = $Document->getUrlWithAccess($Document->getInstance(), $Document->that);
			}
			
			$row->documentType = ht::createLink("#{$handle}", $singleUrl, FALSE, "ef_icon={$Document->singleIcon}");
		}
		
		if(!Mode::isReadOnly() && !$isPlain){
			$row->ROW_ATTR['class'] = "state-{$Document->fetchField('state')}";
		}
		
		if($isPlain){
			if(!empty($dRec->dueDate)){
				$row->dueDate = frame_CsvLib::toCsvFormatData($dRec->dueDate);
			}
			$row->createdOn = frame_CsvLib::toCsvFormatData($dRec->createdOn);
		} else {
			if(!empty($dRec->dueDate)){
				$DeliveryDate = new DateTime($dRec->dueDate);
				$delYear = $DeliveryDate->format('Y');
				$curYear = date('Y');
				$mask = ($delYear == $curYear) ? 'd.M H:i' : 'd.M.y H:i';
				$row->dueDate = dt::mysql2verbal($dRec->dueDate, $mask);
			}
			
			$row->createdOn = core_Type::getByName('datetime(format=smartTime)')->toVerbal($dRec->createdOn);
		}
		
		if(!empty($dRec->weight)){
			$row->weight = core_Type::getByName('cat_type_Weight')->toVerbal($dRec->weight);
		}
		
		if(!empty($dRec->pallets)){
			$row->pallets = core_Type::getByName('double(smartRound)')->toVerbal($dRec->pallets);
		}
		
		if(!empty($dRec->lineId)){
			if($isPlain){
				$row->lineId = trans_Lines::getTitleById($dRec->lineId);
			} else {
				$row->lineId = trans_Lines::getHyperlink($dRec->lineId);
			}
		}
		
		if($isPlain){
			$row->folderId = doc_Folders::getTitleById($dRec->folderId, FALSE);
		} else {
			$row->created = "{$row->createdOn} " . tr('от') . " {$row->createdBy}";
			$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($dRec->folderId))->title;
		}
		
		if(is_array($dRec->linked) && count($dRec->linked)){
			$linked = "<table class='small no-border'>";
			foreach ($dRec->linked as $cId){
				$Document = doc_Containers::getDocument($cId);
				$link = ($isPlain) ? "#" .$Document->getHandle(): $Document->getLink(0);
				$linked .= "<tr><td>{$link}<td></tr>";
			}
			$linked .= "</table>";
			$row->linked = $linked;
		}
		
		if(is_array($dRec->stores)){
			$stores = array();
			foreach ($dRec->stores as $storeId){
				$link = store_Stores::getHyperlink($storeId, TRUE);
				$stores[] = ($isPlain) ? store_Stores::getTitleById($storeId) : store_Stores::getHyperlink($storeId, TRUE);
			}
			$row->stores = implode(' » ', $stores);
		}
		
		return $row;
	}
	
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec   - записа
	 * @param boolean $export - таблицата за експорт ли е
	 * @return core_FieldSet  - полетата
	 */
	protected function getTableFieldSet($rec, $export = FALSE)
	{
		$fld = cls::get('core_FieldSet');
		
		$fld->FLD('documentType', 'varchar', 'caption=Документ');
		if(empty($rec->storeId)){
			$fld->FLD('stores', 'varchar', 'caption=Склад,tdClass=small');
		}
		
		if($export === FALSE){
			$fld->FLD('dueDate', 'varchar', 'tdClass=small nowrap,caption=Срок');
			$fld->FLD('weight', 'varchar', 'caption=Тегло,tdClass=small nowrap');
			$fld->FLD('pallets', 'varchar', 'caption=Палети');
			$fld->FLD('linked', 'varchar', 'caption=Задачи');
			$fld->FLD('folderId', 'varchar', 'tdClass=small,caption=Папка');
			$fld->FLD('created', 'datetime', 'caption=Създаване,tdClass=small nowrap');
		} else {
			$fld->FLD('dueDate', 'varchar', 'caption=Срок');
			$fld->FLD('weight', 'varchar', 'caption=Тегло');
			$fld->FLD('pallets', 'varchar', 'caption=Палети');
			$fld->FLD('linked', 'varchar', 'caption=Задачи');
			$fld->FLD('folderId', 'varchar', 'tdClass=small,caption=Папка');
			$fld->FLD('createdOn', 'datetime', 'caption=Създаване->На');
			$fld->FLD('createdBy', 'varchar', 'caption=Създаване->От');
		}
		
		return $fld;
	}
	
	
	/**
	 * След вербализирането на данните
	 *
	 * @param frame2_driver_Proto $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $row
	 * @param stdClass $rec
	 * @param array $fields
	 */
	protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
	{
		if(isset($rec->storeId)){
			$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
		}
	}
	
	
	/**
	 * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
	 *
	 * @param stdClass $rec
	 * @return boolean $res
	 */
	public function canSendNotificationOnRefresh($rec)
	{
		// Намира се последните две версии
		$query = frame2_ReportVersions::getQuery();
		$query->where("#reportId = {$rec->id}");
		$query->orderBy('id', 'DESC');
		$query->limit(2);
		
		// Маха се последната
		$all = $query->fetchAll();
		unset($all[key($all)]);
		
		// Ако няма предпоследна, бие се нотификация
		if(!count($all)) return TRUE;
		$oldRec = $all[key($all)]->oldRec;
		$dataRecsNew = $rec->data->recs;
		$dataRecsOld = $oldRec->data->recs;
		
		if(!is_array($dataRecsOld)) return TRUE;
		
		if(is_array($dataRecsNew)){
			foreach ($dataRecsNew as $index => $new){
				$old = $dataRecsNew[$index];
					
				// Ако има нов документ - известяване
				if(!array_key_exists($index, $dataRecsOld)) return TRUE;
					
				// Ако има промяна в крайния срок - известяване
				if($new->dueDate != $old->dueDate) return TRUE;
			}
		}
		
		return FALSE;
	}
	
	
	/**
	 * Връща следващите три дати, когато да се актуализира справката
	 *
	 * @param stdClass $rec - запис
	 * @return array|FALSE  - масив с три дати или FALSE ако не може да се обновява
	 */
	public function getNextRefreshDates($rec)
	{
		$date = new DateTime(dt::now());
		$date->add(new DateInterval('P0DT0H5M0S'));
		$d1 = $date->format('Y-m-d H:i:s');
		$date->add(new DateInterval('P0DT0H5M0S'));
		$d2 = $date->format('Y-m-d H:i:s');
		$date->add(new DateInterval('P0DT0H5M0S'));
		$d3 = $date->format('Y-m-d H:i:s');
		
		return array($d1, $d2, $d3);
	}
}