<?php



/**
 * Клас 'trans_plg_DocumentUnits'
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_plg_DocumentUnits extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		$mvc->FLD('transUnitId', 'key(mvc=trans_TransportUnits,select=name,allowEmpty)', 'caption=Логистична информация->Единици,forceField,autohide,after=volume');
		$mvc->FLD('transUnitQuantity', 'int', 'caption=-,autohide,inlineTo=transUnitId,forceField,unit=бр.');
	}
	
	
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		if(!empty($rec->transUnitId) && empty($rec->transUnitQuantity)){
    			$form->setError('transUnitId,transUnitQuantity', 'Трябва да е попълнено к-то на ЛЕ');
    		} elseif(empty($rec->transUnitId) && !empty($rec->transUnitQuantity)){
    			$form->setError('transUnitId,transUnitQuantity', 'Липсва логистична еденица');
    		}
    	}
    }
}