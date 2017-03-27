<?php



/**
 * Кеш на изгледа на частните артикули
 *
 *
 * @category  bgerp
 * @package   frame2
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame2_ReportVersions extends core_Detail
{
	
	
	/**
	 * Заглавие на мениджъра
	 */
	public $title = "История на промяната на репортите";
	
	
	/**
	 * Права за добавяне
	 */
	public $canAdd = 'no_one';
	
	
	/**
	 * Права за редактиране
	 */
	public $canEdit = 'no_one';
	
	
	/**
	 * Права за запис
	 */
	public $canDelete = 'no_one';
	
	
	/**
	 * Права за избор на версия
	 */
	public $canCheckout = 'powerUser';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'no_one';
	
	
	/**
	 * Необходими плъгини
	 */
	public $loadList = 'plg_Created';
	
	
	/**
	 * Име на поле от модела, външен ключ към мастър записа
	 */
	public $masterKey = 'reportId';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'createdBy=От,createdOn=Версия';
	
	
	/**
	 * Брой записи на страница
	 */
	public $listItemsPerPage = 5;
	
	
	/**
	 * Име на перманентните данни
	 */
	const PERMANENT_SAVE_NAME = 'reportVersions';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD("reportId", "key(mvc=frame2_Reports)", "caption=Репорт");
		$this->FLD("oldRec", "blob(serialize, compress)", "caption=Стар запис");
	}
	
	
	/**
	 * Записване на нова версия на отчета
	 *  
	 * @param int $reportId - ид на справка
	 * @param stdClass $rec - за записване
	 */
	public static function log($reportId, $rec)
	{
		// Записа на новата версия
		$logRec = (object)array('reportId' => $reportId, 'oldRec' => $rec);
		
		// Опит за намиране на последната записана версия
		$query = self::getQuery();
		$query->where("#reportId = {$reportId}");
		$query->orderBy('createdOn', 'DESC');
		
		// Ако има такава
		if($lastRec = $query->fetch()){
			
			// Сравнява се с новата
			$obj1 = self::getDataToCompare($lastRec->oldRec);
			$obj2 = self::getDataToCompare($rec);
			
			// Ако няма промяна на данните, не се записва нова версия
			if(serialize($obj1) == serialize($obj2)) return FALSE;
		}
		
		// Запис на новата версия
		$id = self::save($logRec);
		
		// Контрол на версиите
		self::keepInCheck($reportId);
		
		return $id;
	}
	
	
	/**
	 * Подготвя данните на справката във подходящ формат за сравнение
	 * 
	 * @param stdClass $rec  
	 * @return stdClass $obj
	 */
	private static function getDataToCompare($rec)
	{
		$obj = new stdClass();
		
		// Изчислената дата
		$obj->data = $rec->data;
		
		// И полетата от драйвера
		if($Driver = frame2_Reports::getDriver($rec)){
			$fields = frame2_Reports::getDriverFields($Driver);
			foreach ($fields as $name => $caption){
				$obj->{$name} = $rec->{$name};
			}
		}
		
		// Връщане на нормализирания обект
		return $obj;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$selectedId = frame2_Reports::getSelectedVersionId($rec->reportId);
		
		if($mvc->haveRightFor('checkout', $rec->id)){
			$url = array($mvc, 'checkout', $rec->id, 'ret_url' => frame2_Reports::getSingleUrlArray($rec->reportId));
			$icon = ($rec->id == $selectedId) ? 'img/16/checkbox_yes.png' : 'img/16/checkbox_no.png';
			$row->createdOn = ht::createLink($row->createdOn, $url, FALSE, "ef_icon={$icon},title=Избор на версия");
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'checkout' && isset($rec->id)){
			$requiredRoles = frame2_Reports::getRequiredRoles('single', $rec->reportId);
		}
	}
	
	
	/**
	 * Преди извличане на записите от БД
	 */
	public static function on_BeforePrepareListRecs($mvc, &$res, $data)
	{
		$data->query->orderBy('id', "DESC");
	}
    
    
	/**
	 * Рендиране на детайла
	 */
	public function renderDetail_($data)
	{
		// Не се рендира детайла, ако има само една версия или режима е само за показване
		if(count($data->recs) == 1 || Mode::isReadOnly()) return;
	
		return parent::renderDetail_($data);
	}
	
	
    /**
	 * Преди рендиране на таблицата
	 */
	public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		$data->listTableMvc->setField('createdBy', 'smartCenter');
	}
	
	
	/**
	 * Екшън за избор на текуща версия на справката
	 */
	function act_Checkout()
	{
		// Проверки
		$this->requireRightFor('checkout');
		expect($id = Request::get('id', 'int'));
		expect($rec = $this->fetch($id));
		$this->requireRightFor('checkout', $rec);
		
		// Добавяне на избраната версия в сесията
		$versionArr = Mode::get(static::PERMANENT_SAVE_NAME);
		$versionArr = is_array($versionArr) ? $versionArr : array();
		$versionArr[$rec->reportId] = $rec->id;
		
		Mode::setPermanent(static::PERMANENT_SAVE_NAME, $versionArr);
		
		// Редирект към спавката
		return followRetUrl();
	}
	
	
	/**
	 * Колко е максималния брой на версиите за пазене
	 * 
	 * @param int $reportId         - ид на справка
	 * @return int $maxKeepHistory  - максимален брой пазения
	 */
	private static function getMaxCount($reportId)
	{
		$maxKeepHistory = frame2_Reports::fetchField($reportId, 'maxKeepHistory');
		if(!isset($maxKeepHistory)){
			$maxKeepHistory = frame2_Reports::MAX_VERSION_HISTORT_COUNT;
		}
		
		return $maxKeepHistory;
	}
	
	
	/**
	 * Поддържа допустимия брой на версиите
	 * 
	 * @param int $reportId
	 * @return int|NULL
	 */
	private static function keepInCheck($reportId)
	{
		// максимален брой на изтриване
		$maxCount = self::getMaxCount($reportId);
		
		// Намиране на всички версии
		$query = self::getQuery();
		$query->where("#reportId = {$reportId}");
		$query->orderBy('id', 'ASC');
		$query->show('id');
		
		// Ако ограничението е надминато
		$count = $query->count();
		
		// Изтриване на стари версии
		if($count > $maxCount){
			$versionArr = Mode::get(self::PERMANENT_SAVE_NAME);
			
			while($rec = $query->fetch()){
				unset($versionArr[$rec->id]);
				self::delete($rec->id);
				$count--;
				
				if($count <= $maxCount) break;
			}
			
			Mode::setPermanent(self::PERMANENT_SAVE_NAME, $versionArr);
		}
	}
	
	
	/**
	 * Коя е последната версия на спавката
	 * 
	 * @param int $reportId - ид на справката
	 * @return int          - ид на последната версия
	 */
	public static function getLatestVersionId($reportId)
	{
		$query = self::getQuery();
		$query->where("#reportId = {$reportId}");
		$query->orderBy('id', 'DESC');
		$query->show('id');
		
		return $query->fetch()->id;
	}
}