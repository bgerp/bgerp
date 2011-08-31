<?php

/**
 * Плъгин за Регистрите, който им добавя възможност обекти от регистрите да влизат като пера
 */
class acc_plg_Registry extends core_Plugin
{

	/**
	 * Извиква се след описанието на модела
	 */
	function on_AfterDescription($mvc)
	{
		$mvc->interfaces = arr::make($mvc->interfaces);
		$mvc->interfaces['acc_RegisterIntf'] = 'acc_RegisterIntf';
	}


	function on_AfterPrepareSingleToolbar($mvc, $data)
	{
		if (static::getSelectableLists($mvc)) {
			$data->toolbar->addBtn('Номенклатури', 
				array(
					'acc_Lists', 'lists', 'classId'=>$mvc->className, 'objectId' => $data->rec->id, 'ret_url' => TRUE
				), 
				'id=btnLists,class=btn-lists'
			);
		}
	}
	
	
	function on_AfterPrepareEditForm($mvc, $data)
	{
		if ($suggestions = static::getSelectableLists($mvc)) {
			$data->form->FNC('lists', 'keylist(mvc=acc_Lists,select=name)', 'caption=Номенклатури->Избор,input');
			$data->form->setSuggestions('lists', $suggestions);
			if ($data->form->rec->id) {
				$data->form->setDefault('lists', 
					type_Keylist::fromArray(acc_Lists::getItemLists($mvc, $data->form->rec->id)));
			}
		}
	}


	/**
	 * След промяна на обект от регистър
	 * 
	 * Нотифицира номенклатурите за промяната.
	 * 
	 * @param core_Manager $mvc
	 * @param int $id
	 * @param stdClass $rec
	 */
	function on_AfterSave($mvc, &$id, &$rec)
	{
		if (!empty($mvc->autoList)) {
			// Автоматично добавяне към номенклатурата $autoList
			expect($autoListId = acc_Lists::fetchField(array("#systemId = '[#1#]'", $mvc->autoList), 'id'));
			$rec->lists = type_Keylist::addKey($rec->lists, $autoListId);
		}
		
		acc_Lists::updateItem($mvc, $rec->id, $rec->lists);
	}
	
	/**
	 * Реализация по поразбиране на метода acc_RegisterIntf::isDimensional()
	 * 
	 * Регистрите, които нямат собствена имплементация на `isDimensional()` ще получат тази тук.
	 *
	 * @return boolean
	 */
	function on_BeforeIsDimensional($mvc, $res)
	{
		// Всички регистри по подразбиране са безразмерни.
        $res = FALSE;

		return FALSE;
	}
	
	
	/**
	 * Допустимите номенклатури минус евентуално $autoList номенклатурата.
	 *
	 * @param core_Mvc $mvc
	 * @return array
	 */
	private static function getSelectableLists($mvc)
	{
		if ($suggestions = acc_Lists::getPossibleLists($mvc)) {
			if (!empty($mvc->autoList)) {
				$autoListId = acc_Lists::fetchField(array("#systemId = '[#1#]'", $mvc->autoList), 'id');
				if (isset($suggestions[$autoListId])) {
					unset($suggestions[$autoListId]);
				}
			}
		}
		
		return $suggestions;
	}
}