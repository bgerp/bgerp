<?php

/**
 * Плъгин за Регистрите, който им добавя възможност обекти от регистрите да влизат като пера
 */
class plg_AccRegistry extends core_Plugin
{

	/**
	 * Извиква се след описанието на модела
	 */
	function on_AfterDescription($mvc)
	{
		$mvc->interfaces = arr::make($mvc->interfaces);
		$mvc->interfaces['acc_RegiserIntf'] = 'acc_RegiserIntf';
	}


	function on_AfterPrepareSingleToolbar($mvc, $data)
	{
		$data->toolbar->addBtn('Номенклатури', 
			array(
				'acc_Lists', 'lists', 'classId'=>$mvc->className, 'objectId' => $data->rec->id, 'ret_url' => TRUE
			), 
			'id=btnLists,class=btn-lists'
		);
	}
	
	
	function on_AfterPrepareEditForm($mvc, $data)
	{
		$data->form->FNC('lists', 'keylist(mvc=acc_Lists,select=name)', 'caption=Номенклатури,input');
		$data->form->setSuggestions('lists', acc_Lists::getPossibleLists($mvc));
		if ($data->form->rec->id) {
			$data->form->setDefault('lists', 
				type_Keylist::fromArray(acc_Lists::getItemLists($mvc, $data->form->rec->id)));
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
		acc_Lists::updateItem($mvc, $id, $rec->lists);
	}
}