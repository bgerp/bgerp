<?php
/**
 * Клас 'doc_Detail'
 *
 * абстрактен клас за детайл на документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class doc_Detail extends core_Detail
{
	
	/**
	 * Връща URL към единичния изглед на мастера
	 */
	public function getRetUrl($rec)
	{
		$master = $this->getMasterMvc($rec);
		$masterKey = $this->getMasterKey($rec);
		
		$url = array($master, 'single', $rec->{$masterKey});
		
		return $url;
	}
	
	
	/**
	 * Пренасочва URL за връщане след запис към сингъл изгледа
	 */
	public static function on_AfterPrepareRetUrl($mvc, $res, $data)
	{
		// Рет урл-то не сочи към мастъра само ако е натиснато 'Запис и Нов'
		if (isset($data->form) && ($data->form->cmd === 'save' || is_null($data->form->cmd))) {
			$master = $mvc->getMasterMvc($data->form->rec);
		
			// Променяма да сочи към single-a
			$data->retUrl = toUrl(array($master, 'single', $data->form->rec->{$mvc->masterKey}));
		} 
	}
}