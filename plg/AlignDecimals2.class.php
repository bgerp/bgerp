<?php



/**
 * Плъгин подравняващ броя на десетичните символи, с помоща на новите функции в core_Math
 *
 *
 * @category  ef
 * @package   plg
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_AlignDecimals2 extends core_Plugin
{
	/**
     * Преди рендиране на таблицата
     */
    static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	
    	if(!count($recs)) return;
    	
    	// Намираме всички полета, които се показват от типа Double
    	$decFields = array();
    	foreach ($mvc->fields as $name => $field){
    		if($field->type instanceof type_Double && $filed->input != 'none'){
    			$decFields[] = $name;
    		}
    	}
    	
    	if(!count($decFields)) return;
    	
    	// тука определяме най-дългата дробна част, без да записваме числото 
		foreach ($recs as $id => $rec){
			foreach ($decFields as $fName){
				core_Math::roundNumber($rec->$fName, &${"{$fName}FracLen"});
			}
		}
		
		// Закръгляме сумата и я обръщаме във вербален вид
		$Double = cls::get('type_Double');
    	foreach ($recs as $id => &$rec){
			foreach ($decFields as $fName){
				$Double->params['decimals'] = ${"{$fName}FracLen"};
				$rec->$fName = core_Math::roundNumber($rec->$fName, &${"{$fName}FracLen"});
				$rows[$id]->$fName = $Double->toVerbal($rec->$fName);
			}
		}
    }
}