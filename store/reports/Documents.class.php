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
		$fieldset->FLD('document', 'class(select=title)', 'caption=Документи,placeholder=Всички,after=storeId');
		$fieldset->FLD('horizon', 'time', 'caption=Хоризонт,after=document');
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
		
		$stores = self::getContableStores();
		$form->setOptions('storeId', array('' => '') + $stores);
		$documents = array('planning_ConsumptionNotes', 'planning_ReturnNotes', 'store_Transfers', 'store_ShipmentOrders', 'store_Receipts', 'planning_DirectProductionNote');
	
		$docOptions = array();
		foreach ($documents as $className){
			$classId = $className::getClassId();
			$docOptions[$classId] = core_Classes::getTitleById($classId, FALSE);
		}
		$form->setOptions('document', array('' => '') + $docOptions);
	}
	
	
	/**
	 * Връща складовете, в които може да контира потребителя
	 * @return array $res
	 */
	private static function getContableStores()
	{
		$res = array();
		
		$cu = core_Users::getCurrent();
		$sQuery = store_Stores::getQuery();
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
		$storeIds = isset($rec->storeId) ? array($rec->storeId => $rec->storeId) : array_keys(self::getContableStores());
		if(!count($storeIds)) return $recs;
		
		foreach (array('planning_ConsumptionNotes', 'planning_ReturnNotes') as $pDoc){
			if(empty($rec->document) || ($rec->document == $pDoc::getClassId())){
				$cQuery = $pDoc::getQuery();
				self::applyFilters($cQuery, $storeIds, $pDoc, $rec, 'deadline');
				while($cRec = $cQuery->fetch()){
					$recs[$cRec->containerId] = (object)array('containerId' => $cRec->containerId,
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
		
		foreach (array('store_ShipmentOrders', 'store_Receipts', 'store_Transfers') as $pDoc){
			if(empty($rec->document) || ($rec->document == $pDoc::getClassId())){
				$sQuery = $pDoc::getQuery();
				self::applyFilters($sQuery, $storeIds, $pDoc, $rec, 'deliveryTime');
				while($sRec = $sQuery->fetch()){
					$linked = $this->getLinkedDocuments($sRec->containerId);
					if(!empty($sRec->lineId)){
						$lineCid = trans_Lines::fetchField($sRec->lineId, 'containerId');
						$linked[$lineCid] = $lineCid;
					}
					
					$recs[$sRec->containerId] = (object)array('containerId' => $sRec->containerId,
													          'dueDate'     => $sRec->deliveryTime,
													  		  'weight'      => ($sRec->weightInput) ? $sRec->weightInput : $sRec->weight,
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
		
		if(empty($rec->document) || ($rec->document == planning_DirectProductionNote::getClassId())){
			$pQuery = planning_DirectProductionNote::getQuery();
			$pQuery->where("#deadline IS NOT NULL");
			self::applyFilters($pQuery, $storeIds, 'planning_DirectProductionNote', $rec, 'deadline');
			while($pRec = $pQuery->fetch()){
				$recs[$pRec->containerId] = (object)array('containerId' => $pRec->containerId,
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
	 * @return array $linked
	 */
	private function getLinkedDocuments($containerId)
	{
		$linked = array();
		
		$cQuery = cal_TaskDocuments::getQuery();
		$cQuery->EXT('taskContainerId', 'cal_Tasks', 'externalName=containerId,externalKey=taskId');
		$cQuery->EXT('taskState', 'cal_Tasks', 'externalName=state,externalKey=taskId');
		$cQuery->where("#containerId = {$containerId} AND #taskState != 'rejected'");
		$cQuery->show('taskId,taskContainerId');
		while($cRec = $cQuery->fetch()){
			$linked[$cRec->taskContainerId] = $cRec->taskContainerId;
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
			$horizon = dt::addSecs($rec->horizon, '2017-07-05 12:50:20');
			$query->where("#{$termDateField} IS NOT NULL");
			$query->where("ADDDATE(#{$termDateField}, INTERVAL {$rec->horizon} SECOND) > '{$horizon}'");
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
		
		// Линк към документа
		$singleUrl = $Document->getSingleUrlArray();
		$handle = $Document->getHandle();
		
		$row->document = "#{$handle}";
		if(!Mode::isReadOnly() && !$isPlain){
			$row->document = ht::createLink("#{$handle}", $singleUrl, FALSE, "ef_icon={$Document->singleIcon}");
		}
		
		if(!Mode::isReadOnly() && !$isPlain){
			$row->ROW_ATTR['class'] = "state-{$Document->fetchField('state')}";
		}
		
		foreach (array('dueDate', 'createdOn') as $dateFld){
			if(isset($dRec->{$dateFld})){
				if($isPlain){
					$row->{$dateFld} = frame_CsvLib::toCsvFormatData($dRec->{$dateFld});
				} else {
					$DeliveryDate = new DateTime($dRec->{$dateFld});
					$delYear = $DeliveryDate->format('Y');
					$curYear = date('Y');
					$mask = ($delYear == $curYear) ? 'd.M H:i:s' : 'd.M.y H:i:s';
					$row->{$dateFld} = dt::mysql2verbal($dRec->{$dateFld}, $mask);
				}
			}
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
			$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($dRec->folderId))->title;
		}
		
		if(is_array($dRec->linked) && count($dRec->linked)){
			$linked = array();
			foreach ($dRec->linked as $cId){
				$Document = doc_Containers::getDocument($cId);
				$link = ($isPlain) ? "#" .$Document->getHandle(): $Document->getLink(0);
				$linked[] = $link;
			}
			
			$row->linked = implode(', ', $linked);
		}
		
		return $row;
	}
	
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec      - записа
	 * @param boolean $export    - таблицата за експорт ли е
	 * @return core_FieldSet     - полетата
	*/
	protected function getTableFieldSet($rec, $export = FALSE)
	{
		$fld = cls::get('core_FieldSet');
		
		$fld->FLD('document', 'varchar', 'caption=Документ');
			
		if($export === FALSE){
			$fld->FLD('dueDate', 'varchar', 'tdClass=small centered,caption=Срок');
		} else {
			$fld->FLD('dueDate', 'varchar', 'caption=Срок');
		}
			
		$fld->FLD('weight', 'varchar', 'caption=Тегло,tdClass=small');
		$fld->FLD('pallets', 'varchar', 'caption=Палети');
		$fld->FLD('linked', 'varchar', 'caption=Задачи,tdClass=small');
		$fld->FLD('folderId', 'varchar', 'tdClass=small,caption=Папка');
		$fld->FLD('createdOn', 'datetime', 'caption=Създаване->На');
		$fld->FLD('createdBy', 'varchar', 'caption=Създаване->От');
		
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
		
		foreach ($dataRecsNew as $index => $new){
			$old = $dataRecsNew[$index];
			
			// Ако има нов документ - известяване
			if(!array_key_exists($index, $dataRecsOld)) return TRUE;
			
			// Ако има промяна в крайния срок - известяване
			if($new->dueDate != $old->dueDate) return TRUE;
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