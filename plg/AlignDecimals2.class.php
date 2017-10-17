<?php



/**
 * Плъгин подравняващ броя на десетичните символи, с помоща на новите функции в core_Math
 *
 *
 * @category  bgerp
 * @package   plg
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_AlignDecimals2 extends core_Plugin
{
	
	
	/**
	 * Метод за подравняване на десетичните числа
	 * 
	 * @param core_FieldSet $mvc - модел
	 * @param array $recs - записите във вътрешен вид
	 * @param array $rows - записите във вербален вид
	 * @return void
	 */
	public static function alignDecimals(core_FieldSet $mvc, $recs, &$rows)
	{
		// Намираме всички полета, които се показват от типа Double
		$decFields = array();
		foreach ($mvc->fields as $name => $field){
			if($field->type instanceof type_Double && $filed->input != 'none' && !($field->type instanceof type_Percent)){
				$decFields[] = $name;
			}
		}
		 
		if(!arr::count($decFields) || !arr::count($recs)) return;
		 
		//$decFields = array(5 => 'packPrice');
		
		// тука определяме най-дългата дробна част, без да записваме числото
		foreach ($recs as $id => $rec){
			foreach ($decFields as $fName){
				core_Math::roundNumber($rec->{$fName}, ${"{$fName}FracLen"});
			}
		}
		
		$conf = core_Packs::getConfig('core');
		$decPoint = html_entity_decode($conf->EF_NUMBER_DEC_POINT);
		
		$minShowDigits = core_Setup::get('MIN_ALIGN_DIGITS');
		
		// Закръгляме сумата и я обръщаме във вербален вид
		foreach ($recs as $id => &$rec){
			
			foreach ($decFields as $col => $fName){
		
				if(isset($rows[$id]->{$fName})){
					$Type = clone $mvc->fields[$fName]->type;
					setIfNot($Type->params['minDecimals'], 0);
					setIfNot($Type->params['maxDecimals'], 6);
						
					$optDecimals = min(
							$Type->params['maxDecimals'],
							max($Type->params['minDecimals'], ${"{$fName}FracLen"})
					);
					
					$Type->params['smartRound'] = TRUE;
					
					// Вербализираме числово само ако наистина е число
					if(is_numeric($rec->{$fName})){
						$rows[$id]->{$fName} = $Type->toVerbal($rec->{$fName});
						$count = strlen(substr(strrchr($rows[$id]->{$fName}, $decPoint), 1));
						
						$padCount = $optDecimals - $count;
						
						if($count === 1){
							$rows[$id]->{$fName} .= "0";
							$padCount--;
						}
						
						$padString = '';
						if((strpos($rows[$id]->{$fName}, $decPoint) === FALSE) && $optDecimals != 0){
							$rows[$id]->{$fName} .= "{$decPoint}00";
							$padCount -= 2;
						}
						
						if($padCount < 0){
							$padCount = 0;
						}
						
						$repeatString = "0";
						if($padCount > 0){
							$count2 = strlen(substr(strrchr($rows[$id]->{$fName}, $decPoint), 1));
							$repeatString = ($count2 >= $minShowDigits) ? "<span style='visibility:hidden;'>{$repeatString}</span>" : "0";
						}
						
						if($padCount >= 0){
							$padString .= str_repeat($repeatString, $padCount);
						}
						
						$rows[$id]->{$fName} .= $padString;
						
						if($optDecimals != 4){
							// Хак за правилно показване
							$rows[$id]->{$fName} = str_replace("{$decPoint}0000", "{$decPoint}00", $rows[$id]->{$fName});
						}
					}
				}
			}
		}
	}
	
	
	/**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	
    	if(!count($recs)) return;
    	
    	self::alignDecimals($data->listTableMvc, $recs, $rows);
    }
}