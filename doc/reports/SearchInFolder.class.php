<?php


/**
 * Драйвер за справка търсеща думи в папка
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Документи » Търсене в папка
 */
class doc_reports_SearchInFolder extends frame2_driver_Proto
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'powerUser';
	
	
	/**
	 * Връща заглавието на отчета
	 *
	 * @param stdClass $rec - запис
	 * @return string|NULL  - заглавието или NULL, ако няма
	 */
	public function getTitle($rec)
	{
		return 'Търсене в папка';
	}
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('folder', 'key2(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Папка,mandatory,after=title');
		$fieldset->FLD('text', 'text(rows=5)', 'caption=Думи,mandatory,after=folder,placeholder=Всяка отделна дума на нов ред,single=none');
	}
	
	
	/**
	 * След изпращане на формата
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
	{
		$unique = array();
		$words = explode("\n", $form->rec->text);
		$duplicatedWords = array();
		
		// Проверка за повтарящи се думи
		foreach ($words as $word){
			$key = $Driver->normalizeString($word);
			if(in_array($key, $unique)){
				$duplicatedWords[] = trim($word);
			} else {
				$unique[] = $key;
			}
		}
		
		// Проверка за дуплицирани думи
		if(count($duplicatedWords)){
			$duplicatedWords = implode('<span style=font-weight:normal>,</span> ', $duplicatedWords);
			$form->setError('text', "Следните думи се повтарят|*: <b>{$duplicatedWords}</b>");
		}
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
		$data->recs = array();
		
		// За всяка дума
		$words = explode("\n", $rec->text);
		foreach ($words as $word){
			
			// Подготовка на заявка, намираща колко пъти се среща в документи в папка
			$cQuery = doc_Containers::getQuery();
			$cQuery->where("#folderId = {$rec->folder}");
			plg_Search::applySearch($word, $cQuery);
			
			// Нормализиране на думата
			$key = $this->normalizeString($word);
			$r = (object)array('string' => $word, 'count' => $cQuery->count(), 'index' => $key);
			
			$data->recs[$key] = $r;
		}
		
		// Подреждане по най-срещаните думи
		arr::order($data->recs, 'count', 'DESC');
		
		return $data;
	}
	
	
	/**
	 * Нормализиране на думите
	 * 
	 * @param string $string
	 * @return string
	 */
	private function normalizeString($string)
	{
		return str_replace(' ', '_', trim(plg_Search::normalizeText($string)));
	}
	
	
	/**
	 * Връща списъчните полета
	 *
	 * @param stdClass $rec  - запис
	 * @return array $fields - полета
	 */
	private function getListFields($rec)
	{
		$fields = array('num'    => "№", 'string' => 'Дума', 'diff'   => 'Нови', 'count'  => 'Резултат',);
	
		return $fields;
	}
	
	
	/**
	 * Рендиране на данните на справката
	 *
	 * @param stdClass $rec - запис на справката
	 * @return core_ET      - рендирания шаблон
	 */
	public function renderData($rec)
	{
		$data = $rec->data;
		$data->rows = array();
		$oldData = $this->getVersionBeforeData($rec);
		
		// Вербализиране на данните
		$count = 1;
		if(is_array($data->recs)){
			foreach ($data->recs as $index => $dRec){
				$dRec->num = $count;
				$data->rows[$index] = $this->detailRecToVerbal($rec, $dRec, $oldData);
				$count++;
			}
		}
		
		// Редниране на таблицата
		$fld = cls::get('core_FieldSet');
		$fld->FLD('num', 'int');
		$fld->FLD('string', 'varchar');
		$fld->FLD('count', 'int');
		$fld->FLD('diff', 'int');
		
		$data->listFields = $this->getListFields($rec);
		$table = cls::get('core_TableView', array('mvc' => $fld));
		$data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, 'diff');
		
		$tpl = new core_ET("");
		$tpl->append($table->get($data->rows, $data->listFields));
		$tpl->removeBlocks();
		$tpl->removePlaces();
		
		// Връщане на шаблона
		return $tpl;
	}
	
	
	/**
	 * Вербализиране на данните
	 *
	 * @param stdClass $rec  - запис на отчета
	 * @param stdClass $dRec - запис от детайла
	 * @param array $oldData - записа на предишната версия
	 * @return stdClass $row - вербалния запис
	 */
	private function detailRecToVerbal($rec, $dRec, $oldData)
	{
		$isPlain = Mode::is('text', 'plain');
		
		$row = new stdClass();
		$Int = cls::get('type_Int');
		$row->string = cls::get('type_Varchar')->toVerbal($dRec->string);
		if(!$isPlain){
			$row->string = "<span style='font-style:italic'>{$row->string}</span>";
		}
		
		$row->count  = $Int->toVerbal($dRec->count);
		if(!$isPlain){
			if(doc_Threads::haveRightFor('list', (object)array('folderId' => $rec->folder))){
				$row->count = ht::createLink($row->count, array('doc_Threads', 'list', 'folderId' => $rec->folder, 'search' => $dRec->string));
			}
		}
		
		$row->num = $Int->toVerbal($dRec->num);
		
		// Ако има промяна спрямо старата версия, показват се промените
		if(isset($oldData[$dRec->index])){
			$oldCount = $oldData[$dRec->index]->count;
			$diff = $dRec->count - $oldCount;
		} elseif(count($oldData)){
			$diff = $dRec->count;
		}
		
		if(!empty($diff)){
			$row->diff = $Int->toVerbal($diff);
			if(!$isPlain){
				$color = ($diff < 0) ? 'red' : 'darkgreen';
				$sign = ($diff < 0) ? '' : '+';
				$row->diff = "<span style='color:{$color}'>{$sign}{$row->diff}</span>";
			}
		}
		
		return $row;
	}
	
	
	/**
	 * Връща редовете на CSV файл-а
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportRows($rec)
	{
		$exportRows = array();
		$oldData = $this->getVersionBeforeData($rec);
		
		Mode::push('text', 'plain');
		if(is_array($rec->data->recs)){
			foreach ($rec->data->recs as $key => $dRec){
				$exportRows[$key] = $this->detailRecToVerbal($rec, $dRec, $oldData);
			}
		}
		Mode::pop('text');
	
		return $exportRows;
	}
	
	
	/**
	 * Връща полетата за експортиране във csv
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportFieldset($rec)
	{
		$fieldset = new core_FieldSet();
		$fieldset->FLD('string', 'varchar','caption=Дума');
		$fieldset->FLD('count', 'int','caption=Резултат');
		$fieldset->FLD('diff', 'int','caption=Нови');
	
		return $fieldset;
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param frame2_driver_Proto $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	public static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
	{
		$row->folder = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folder))->title;
	}
	
	
	/**
	 * Връща данните от предишната версия
	 * 
	 * @param stdClass $rec - записа на отчета
	 * @return array $versionBeforeData - данните от предишната версия
	 */
	private function getVersionBeforeData($rec)
	{
		$selectedVersionId = frame2_Reports::getSelectedVersionId($rec->id);
		
		// Ако няма избрана версия това е последната за справката
		if(!$selectedVersionId){
			$query = frame2_ReportVersions::getQuery();
			$query->where("#reportId = {$rec->id}");
			$query->orderBy('id', 'DESC');
			$query->show('versionBefore');
			
			$versionBeforeId = $query->fetch()->versionBefore;
		} else {
			$versionBeforeId = frame2_ReportVersions::fetchField($selectedVersionId, 'versionBefore');
		}
		
		$versionBeforeData = (isset($versionBeforeId)) ? frame2_ReportVersions::fetchField($versionBeforeId, 'oldRec')->data->recs : array();
		
		return $versionBeforeData;
	}
}