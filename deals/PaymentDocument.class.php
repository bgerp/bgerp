<?php


/**
 * Базов документ за наследяване на платежни документи
 * 
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_PaymentDocument extends core_Master {
	
	
	/**
	 * Функция, която се извиква след активирането на документа
	 */
	public static function on_AfterActivation($mvc, &$rec)
	{
		// Обновяваме автоматично изчисления метод на плащане на всички фактури в нишката на документа
		$threadId = ($rec->threadId) ? $rec->threadId : $mvc->fetchField($rec->id, 'threadId');
		sales_Invoices::updateAutoPaymentTypeInThread($threadId);
	}
	
	
	/**
	 * След оттегляне на документа
	 */
	public static function on_AfterReject(core_Mvc $mvc, &$res, $rec)
	{
		$id = (is_object($rec)) ? $rec->id : $rec;
		if($rec->brState == 'active'){
			
			// Обновяваме автоматично изчисления метод на плащане на всички фактури в нишката на документа
			$threadId = ($rec->threadId) ? $rec->threadId : $mvc->fetchField($id, 'threadId');
			sales_Invoices::updateAutoPaymentTypeInThread($threadId);
		}
	}
	
	
	/**
	 *  Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, $data)
	{
		if(!Request::get('Rejected', 'int')){
			$data->listFilter->FNC('dState', 'enum(all=Всички, pending=Заявка, draft=Чернова, active=Контиран)', 'caption=Състояние,input,silent');
			$data->listFilter->showFields .= ',dState';
			$data->listFilter->input();
			$data->listFilter->setDefault('dState', 'all');
			 
			if($rec = $data->listFilter->rec){
	
				// Филтър по състояние
				if($rec->dState){
					if($rec->dState != 'all'){
						$data->query->where("#state = '{$rec->dState}'");
					}
				}
			}
		}
	}
}