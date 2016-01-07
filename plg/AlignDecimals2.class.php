<?php



/**
 * Плъгин подравняващ броя на десетичните символи, с помоща на новите функции в core_Math
 *
 *
 * @category  ef
 * @package   plg
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
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
		 
		if(!count::count($decFields)) return;
		 
		// тука определяме най-дългата дробна част, без да записваме числото
		foreach ($recs as $id => $rec){
			foreach ($decFields as $fName){
				core_Math::roundNumber($rec->$fName, ${"{$fName}FracLen"});
			}
		}
		
		// Закръгляме сумата и я обръщаме във вербален вид
		foreach ($recs as $id => &$rec){
			foreach ($decFields as $col => $fName){
		
				if(isset($rows[$id]->$fName)){
					$Type = $mvc->fields[$fName]->type;
					setIfNot($Type->params['minDecimals'], 0);
					setIfNot($Type->params['maxDecimals'], 6);
						
					$optDecimals = min(
							$Type->params['maxDecimals'],
							max($Type->params['minDecimals'], ${"{$fName}FracLen"})
					);
					$Type->params['decimals'] = $optDecimals;
						
					// Вербализираме числово само ако наистина е число
					if(is_numeric($rec->$fName)){
						$rows[$id]->$fName = $Type->toVerbal($rec->$fName);
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
    	
    	self::alignDecimals($mvc, $recs, $rows);
    }
}