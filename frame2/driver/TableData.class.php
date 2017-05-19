<?php



/**
 * Базов драйвер за справки показващи стандартни таблични данни
 *
 *
 * @category  bgerp
 * @package   frame2
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class frame2_driver_TableData extends frame2_driver_Proto
{
	
	
	/**
	 * Брой записи на страница
	 *
	 * @var int
	 */
	protected $listItemsPerPage = 30;
	
	
	/**
	 * Полета от таблицата за скриване, ако са празни
	 *
	 * @var int
	 */
	protected $filterEmptyListFields;
	
	
	/**
	 * Полета за хеширане на таговете
	 * 
	 * @see uiext_Labels
	 * @var varchar
	 */
	protected $hashField;
	
	
	/**
	 * Кое поле от $data->recs да се следи, ако има нов във новата версия
	 *
	 * @var varchar
	 */
	protected $newFieldToCheck;
	
	
	/**
	 * Връща заглавието на отчета
	 *
	 * @param stdClass $rec - запис
	 * @return string|NULL  - заглавието или NULL, ако няма
	 */
	public function getTitle($rec)
	{
		$title = core_Classes::fetchField("#id = {$this->getClassId()}", 'title');
		$title = explode(" » ", $title);
		$title = (count($title) == 2) ? $title[1] : $title[0];
		
		return $title;
	}
	
	
	/**
	 * Подготвя данните на справката от нулата, които се записват в модела
	 *
	 * @param stdClass $rec        - запис на справката
	 * @return stdClass|NULL $data - подготвените данни
	 */
	public function prepareData($rec)
	{
		$data = new stdClass();
		$data->recs = $this->prepareRecs($rec);
		
		return $data;
	}
	
	
	/**
	 * Рендиране на данните на справката
	 *
	 * @param stdClass $rec - запис на справката
	 * @return core_ET      - рендирания шаблон
	 */
	public function renderData($rec)
	{
		$tpl = new core_ET("[#PAGER_TOP#][#TABLE#][#PAGER_BOTTOM#]");
	
		$data = $rec->data;
		$data->listFields = $this->getListFields($rec);
		$data->rows = array();
	
		// Подготовка на пейджъра
		if(!Mode::isReadOnly()){
			$data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $this->listItemsPerPage));
			$data->Pager->setPageVar('frame2_Reports', $rec->id);
			$data->Pager->itemsCount = count($data->recs);
		}
	
		// Вербализиране само на нужните записи
		if(is_array($data->recs)){
			foreach ($data->recs as $index => $dRec){
				if(isset($data->Pager) && !$data->Pager->isOnPage()) continue;
				$data->rows[$index] = $this->detailRecToVerbal($rec, $dRec);
			}
		}
		
		// Рендиране на пейджъра
		if(isset($data->Pager)){
			$tpl->append($data->Pager->getHtml(), 'PAGER_TOP');
			$tpl->append($data->Pager->getHtml(), 'PAGER_BOTTOM');
		}
	
		// Рендиране на лист таблицата
		$fld = $this->getTableFieldSet($rec);
		$table = cls::get('core_TableView', array('mvc' => $fld));
	
		// Показване на тагове
		if(core_Packs::isInstalled('uiext')){
			uiext_Labels::showLabels($this, $rec->containerId, $data->recs, $data->rows, $data->listFields, $this->hashField, 'Таг', $tpl, $fld);
		}

		$filterFields = arr::make($this->filterEmptyListFields, TRUE);
		$filterFields['_tagField'] = '_tagField';
		$data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, implode(',', $filterFields));
	
		$tpl->append($table->get($data->rows, $data->listFields), 'TABLE');
		$tpl->removeBlocks();
		$tpl->removePlaces();
	
		// Връщане на шаблона
		return $tpl;
	}
	
	
	/**
	 * Връща полетата за експортиране във csv
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportFieldset($rec)
	{
		$fld = $this->getTableFieldSet($rec, TRUE);
	
		return $fld;
	}
	
	
	/**
	 * Подготвя данните на справката от нулата, които се записват в модела
	 *
	 * @param stdClass $rec        - запис на справката
	 * @param boolean $export    - таблицата за експорт ли е
	 * @return stdClass|NULL $data - подготвените данни
	 */
	protected function getListFields($rec, $export = FALSE)
	{
		$listFields = array();
		
		$fieldset = $this->getTableFieldSet($rec, $export);
		$fields = $fieldset->selectFields();
		if(is_array($fields)){
			foreach ($fields as $name => $fld){
				$listFields[$name] = $fld->caption;
			}
		}
		
		return $listFields;
	}
	
	
	/**
	 * Връща редовете на CSV файл-а
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportRows($rec)
	{
		$dRecs = $rec->data->recs;
		$exportRows = array();
	
		Mode::push('text', 'plain');
		if(is_array($dRecs)){
			foreach ($dRecs as $key => $dRec){
				$exportRows[$key] = $this->detailRecToVerbal($rec, $dRec);
			}
		}
		Mode::pop('text');
	
		return $exportRows;
	}
	
	
	/**
	 * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
	 *
	 * @param stdClass $rec
	 * @return boolean $res
	 */
	public function canSendNotificationOnRefresh111($rec)
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
	
		$newContainerIds = $oldContainerIds = array();
		
		if(is_array($rec->data->recs)){
			$newContainerIds = arr::extractValuesFromArray($rec->data->recs, $this->newFieldToCheck);
		}
	
		if(is_array($oldRec->data->recs)){
			$oldContainerIds = arr::extractValuesFromArray($oldRec->data->recs, $this->newFieldToCheck);
		}
	
		// Ако има нови документи бие се нотификация
		$diff = array_diff_key($newContainerIds, $oldContainerIds);
		$res = (is_array($diff) && count($diff));
	
		return $res;
	}
	
	
	/**
	 * Кои записи ще се показват в таблицата
	 * 
	 * @param stdClass $rec
	 * @return array
	 */
	protected abstract function prepareRecs($rec);
	
	
	/**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec  - записа
	 * @param stdClass $dRec - чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected abstract function detailRecToVerbal($rec, &$dRec);
	
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec      - записа
	 * @param boolean $export    - таблицата за експорт ли е
	 * @return core_FieldSet     - полетата
	 */
	protected abstract function getTableFieldSet($rec, $export = FALSE);
}
