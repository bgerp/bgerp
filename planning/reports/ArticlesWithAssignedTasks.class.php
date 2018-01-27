<?php
/**
 * Мениджър на отчети относно 
 * задания за артикули с възложени задачи
 *
 * @category  bgerp
 * @package   planning
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задания » Задания за артикули с възложени задачи
 */
class planning_reports_ArticlesWithAssignedTasks extends frame2_driver_TableData
{
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'ceo';
	
	
	/**
	 * Полета от таблицата за скриване, ако са празни
	 *
	 * @var int
	 */
	protected $filterEmptyListFields ;
	
	
	/**
	 * Полета за хеширане на таговете
	 *
	 * @see uiext_Labels
	 * @var varchar
	 */
	protected $hashField ;
	
	
	/**
	 * Кое поле от $data->recs да се следи, ако има нов във новата версия
	 *
	 * @var varchar
	 */
	protected $newFieldToCheck ;
	
	/**
	 * По-кое поле да се групират листовите данни
	 */
	 protected $groupByField ;
	
	
	/**
	 * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
	 */
	protected $changeableFields = '';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD ( 'dealers', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Търговци,after=title' );
		
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
		$recs = array ();
		
	
		
		$productsForJobs = array ();
		
		
		$jobsQuery = planning_Jobs::getQuery ();
		
		$jobsQuery->where ( "#state = 'active' OR #state = 'wakeup'" );
		
		/*
		 * Масив с артикули по задания за производство
		 */
		while ( $jobses = $jobsQuery->fetch () ) {
			
			//bp($jobses);
				
			$jobsProdId = $jobses->productId;
				
			if (! array_key_exists ( $jobsProdId, $productsForJobs )) {
		
				$productsForJobs [$jobsProdId] =
		
				( object ) array (
		
						'productId' => $jobsProdId,
		
						'quantity' => $jobses->quantity
				);
			} else {
		
				$obj = &$productsForJobs [$jobses->productId];
		
				$obj->quantity += $jobses->quantity;
			}
		}
		
		
	//	bp($recs);
		
	return $recs;
	}
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec
	 *        	- записа
	 * @param boolean $export
	 *        	- таблицата за експорт ли е
	 * @return core_FieldSet - полетата
	 */
	protected function getTableFieldSet($rec, $export = FALSE) {
		$fld = cls::get ( 'core_FieldSet' );
		
		if ($export === FALSE) {
			
			$fld->FLD ( 'jobsId', 'varchar', 'caption=Задание,tdClass=centered' );
			$fld->FLD ( 'productId', 'varchar', 'caption=Артикул' );
			$fld->FLD ( 'state', 'double', 'caption=Статус,smartCenter' );
		} else {
			
		}
		
		return $fld;
	}
	
	/**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec-
	 *        	записа
	 * @param stdClass $dRec-
	 *        	чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected function detailRecToVerbal($rec, &$dRec) {
		$isPlain = Mode::is ( 'text', 'plain' );
		$Int = cls::get ( 'type_Int' );
		$Date = cls::get ( 'type_Date' );
		
		$row = new stdClass ();
		
		
	}




}