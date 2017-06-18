<?php



/**
 * Помощен детайл подготвящ и обединяващ заедно търговските
 * детайли на фирмите и лицата
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_CommerceDetails extends core_Manager
{
	
	
	/**
	 * Подготвя ценовата информация за артикула
	 */
	public function prepareCommerceDetails($data)
	{
		if(haveRole('sales,purchase,ceo')){
			$data->TabCaption = 'Търговия';
		}

		if($data->isCurrent === FALSE) return;
		
		$data->Lists = cls::get('price_ListToCustomers');
		$data->Conditions = cls::get('cond_ConditionsToCustomers');
		$data->Cards = cls::get('pos_Cards');
		
		$data->listData = clone $data;
		$data->condData = clone $data;
		$data->cardData = clone $data;
		
		// Подготвяме данни за ценовите листи
		$data->Lists->preparePricelists($data->listData);
		
		// Подготвяме търговските условия
		$data->Conditions->prepareCustomerSalecond($data->condData);
		
		// Подготвяме клиентските карти
		$data->Cards->prepareCards($data->cardData);
	}
	
	
	/**
	 * Рендира ценовата информация за артикула
	 */
	public function renderCommerceDetails($data)
	{
		if($data->prepareTab === FALSE || $data->renderTab === FALSE) return;
		
		// Взимаме шаблона
		$tpl = getTplFromFile('crm/tpl/CommerceDetails.shtml');
		$tpl->replace(tr('Търговия'), 'title');
		
		// Рендираме ценовата информация
		$listsTpl = $data->Lists->renderPricelists($data->listData);
		$listsTpl->removeBlocks();
		$tpl->append($listsTpl, 'LISTS');
		
		// Рендираме търговските условия
		$condTpl = $data->Conditions->renderCustomerSalecond($data->condData);
		$condTpl->removeBlocks();
		$tpl->append($condTpl, 'CONDITIONS');
		
		// Рендираме клиентските карти
		$cardTpl = $data->Cards->renderCards($data->cardData);
		$cardTpl->removeBlocks();
		$tpl->append($cardTpl, 'CARDS');
		
		return $tpl;
	}
}
