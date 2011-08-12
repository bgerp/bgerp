<?php

/**
 * Плъгин за Регистрите, който им добавя възможност обекти от регистрите да влизат като пера
 */
class plg_AccRegistry extends core_Plugin
{
	
	var $loadList = 'Lists=acc_Lists';
	
	/**
	 * @var acc_Lists
	 */
	var $acc_Lists;


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
				'acc_Lists', 'lists', 'class'=>$mvc->className, 'objectId' => $data->rec->id, 'ret_url' => TRUE
			), 
			'id=btnLists,class=btn-lists'
		);
	}


	/**
	 * @param core_Manager $mvc
	 * @param int $id
	 * @param stdClass $rec
	 */
	function on_AfterSave($mvc, &$id, &$rec)
	{
		acc_Lists::updateItem($mvc, $id);
	}
}