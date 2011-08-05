<?php

/**
 * Клас 'plg_ExportCsv' - Дава възможност за експорт към CSV на избрани полета от модела,
 * които имат атрибут 'export=Csv'
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_ExportCsv extends core_Plugin
{
	function on_BeforePrepareListFields($mvc, $res, $data)
	{
		/* Ако в url-то на заявката има Export=Csv */
		if (Request::get('Export') == 'csv') {
			// Масива с избраните полета за export
			$exportFields = $mvc->selectFields("#export");

			// Ако има избрани полета за export
			if (count($exportFields)) {
				foreach($exportFields as $name => $field) {
					$data->listFields[$name] = tr($field->caption);
				}
			}

			return FALSE;
		}
		/* END Ако в url-то на заявката има Export=Csv */
	}


	/**
	 * Добавяме бутон за експорт в Csv
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $res
	 * @param stdClass $data
	 */
	function on_AfterPrepareListToolbar($mvc, $res, $data)
	{
		// Ако има избрани полета за export
		if (count($mvc->selectFields("#export"))) {
			$url = getCurrentUrl();
			$url['Export'] = 'csv';
				
			$data->toolbar->addBtn('Експорт в CSV', $url);
		}
	}


	/**
	 * Игнорираме pager-а
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $res
	 * @param stdClass $data
	 */
	function on_BeforeListPager($mvc, $res, $data)
	{
		if (Request::get('Export') == 'csv') {
			return FALSE;
		}
	}


	/**
	 * Ако имаме в url-то Export=csv създаваме csv файл с данните
	 *
	 * @param core_Mvc $mvc
	 * @param core_Table $table
	 * @param stdClass $data
	 */
	function on_BeforeRenderListTable($mvc, $table, $data)
	{
		/* Ако в url-то на заявката има Export=Csv */
		if (Request::get('Export') == 'csv') {
			/* за всеки ред */
			foreach($data->recs as $rec) {
				// Всеки нов ред ва началото е празен
				$rCsv = '';

                /* за всяка колона */				
				foreach($data->listFields as $field => $caprion) {
					
                                        $value = $mvc->getVеrbal($rec, $field);

					// escape
					if (preg_match( '/\\r|\\n|,|"/', $value )) {
						$value = '"' . str_replace('"', '""', $value) . '"';
					}
						
					$rCsv .= ($rCsv ? "," : "") . $value;
				}
				/* END за всяка колона */

				$csv .= $rCsv . "\n";
			}
			/* END за всеки ред */

			/* Prepare CSV file */ 
			$fileName = str_replace(' ', '_', Str::utf2ascii($mvc->title));

			header("Content-type: application/csv");
			header("Content-Disposition: attachment; filename={$fileName}.csv");
			header("Pragma: no-cache");
			header("Expires: 0");

			echo $csv;
			/* END Prepare CSV file */
			
			exit;
		}
		/* END Ако в url-то на заявката има Export=Csv */
	}

}