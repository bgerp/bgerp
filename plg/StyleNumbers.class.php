<?php



/**
 * Подравняване на десетични числа, според зададени в типа type_Doubleна минималния и максималния брой цифри след запетаята
 *
 * Този плъгин е предназначен за прикачане към core_Mvc (или неговите наследници).
 * Инспектира `double` полетата на приемника си и ги форматира вербалните им стойности така,
 * че броят на десетичните цифри да е между предварително зададени минимална и максимална
 * стойност, като при нужда допълва с нули или прави закръгляване (чрез @see round()).
 * При все това, плъгина се грижи броят на десетичните цифри на всяко поле да е един и същ за
 * всички записи от
 * Тези мин. и макс. стойности се задават като параметри на типа `double`:
 * $this->FLD('fieldname', 'double(minDecimals=2, maxDecimals=4)', ...);
 *
 *
 * @category  ef
 * @package   plg
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link      https://github.com/bgerp/ef/issues/6
 */
class plg_StyleNumbers extends core_Plugin
{
    
    
	/**
	 * Преди рендиране на таблицата
	 */
	public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		$rows = &$data->rows;
		
		if(!count($data->recs)) return;
		
		foreach ($mvc->selectFields() as $name => $field) {
			if (is_a($field->type, 'type_Double')) {
				foreach ($data->recs as $i => $rec) {
					$rows[$i]->{$name} = ht::styleIfNegative($rows[$i]->{$name}, round($rec->{$name}, 4));
				}
			}
		}
	}
}