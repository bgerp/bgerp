<?php



/**
 * Клас 'frame_CsvLib' - Библиотечен клас за работа с CSV файлове
 *
 *
 * @category  bgerp
 * @package   frame
 * @author    Gabriela Petrova <gab4etp@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame_CsvLib
{
	
	
	/**
	 * Ще направим нови row-ове за експорта.
	 * Ще се обработват променливи от тип
	 * double, key, keylist, date
	 *
	 * @return std Class $rows
	 */
	public static function prepareCsvRows ($rec)
	{
	
	
		// новите ни ролове
		$rows = new stdClass();
	
	
		// за всеки един запис
		foreach ($rec as $field => $value) {
	
			// ако е doubele
			if (in_array($field ,array('baseQuantity', 'baseAmount', 'debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount'))) {
				 
				$value = frame_CsvLib::toCsvFormatDouble($value);
	
			}
				
			// ако е class
			try{
				$Class = cls::get($rec['docType']);
				$rows->docId = html2text_Converter::toRichText($Class->getShortHyperLink($rec['docId']));
				$rows->reason = html2text_Converter::toRichText($Class->getContoReason($rec['docId'], $rec['reasonCode']));
			} catch(core_exception_Expect $e){
				if(is_numeric($rec['docId'])){
					$rows->docId = "<span style='color:red'>" . tr("Проблем при показването") . "</span>";
				} else {
					$rows->docId = $rec['docId'];
				}
			}
				
			// ако е date
			if ($field == 'valior') {
				$value = frame_CsvLib::toCsvFormatData($rec['valior']);
			}
			
			$rows->{$field} = $value;
		}
		
		// ако имаме попълнено поле за контрагент или продукт
		// искаме то да илезе с вербалното си име
		foreach (range(1, 3) as $i) {
			if(!empty($rows->{"ent{$i}Id"})){
				$rows->{"ent{$i}Id"} = acc_Items::getVerbal($rec->{"ent{$i}Id"}, 'title');
			}
		}
		
		return $rows;
	}
	
	
	/**
	 * Форматиране на double за CSV
	 *
	 * @return double $rows
	 */
	public static function toCsvFormatDouble($value)
	{
		// ще вземем конфигурурания символ за разделител на стотинките
		$conf = core_Packs::getConfig('frame');
	
		//ще го закръгляме до 2 знака, след запетаята
		$decimals = 2;
		// няма да имаме разделител за хилядите
		$thousandsSep = '';
	
		$symbol = $conf->FRAME_TYPE_DECIMALS_SEP;
	
		if ($symbol == 'comma') {
			$decPoint = ',';
		} else {
			$decPoint = '.';
		}
	
		// Закръгляме до минимума от символи от десетичния знак или зададения брой десетични знака
		//$decimals = min(strlen(substr(strrchr($value, $decPoint), 1)), $decimals);
			
		// Закръгляме числото преди да го обърнем в нормален вид
		$value = round($value, $decimals);
	
		$value = number_format($value, $decimals, $decPoint, $thousandsSep);
			
		if(!Mode::is('text', 'plain')) {
			$value = str_replace(' ', '&nbsp;', $value);
		}
	
		return $value;
	}
	
	
	/**
	 * Форматиране на дата за CSV-то
	 *
	 * @return string $value
	 */
	public static function toCsvFormatData($value)
	{
		// ще вземем конфигурурания символ за разделител на стотинките
		$conf = core_Packs::getConfig('frame');
	
		$format = $conf->FRAME_FORMAT_DATE;
	
		if ($format == 'dot') {
			$value = dt::mysql2verbal($value, 'd.m.Y');
		} else {
			$value = dt::mysql2verbal($value, 'm/d/y');
		}
	
		return $value;
	}
}