<?php


/**
 * Помощен модел за лесна работа с баланс, в който участват само определени пера и сметки
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_tpl_InvoiceAccView extends doc_TplScript 
{
	
	
	/**
	 * Метод който подава данните на детайла на мастъра, за обработка на скрипта
	 *
	 * @param core_Mvc $detail - Детайл на документа
	 * @param stdClass $data - данни
	 * @return void
	 */
	public function modifyDetailData(core_Mvc $detail, &$data)
	{
		if(!count($data->rows)) return;
		$exportParamId = acc_Setup::get('INVOICE_MANDATORY_EXPORT_PARAM');
		if(!$exportParamId) return;
		$exportParamName = cat_Params::getTitleById($exportParamId);
		foreach ($data->rows as $id => $row){
			$rec = $data->recs[$id];
			
			$value = cat_Products::getParams($rec->productId, $exportParamId, TRUE);
			if(!empty($value)){
				$oldProductId = (is_object($row->productId)) ? $row->productId->getContent() : $row->productId;
				$oldProductId = strip_tags($oldProductId);
				$row->productId = (!Mode::isReadOnly()) ? ht::createLinkRef($value, cat_Products::getSingleUrlArray($rec->productId)) : $value;
				$row->productId .= "<div class='small'>{$oldProductId}</div>";
			} elseif(!Mode::isReadOnly()){
				$row->productId = ht::createHint($row->productId, "Артикулът няма парамертър|* '{$exportParamName}'", 'warning', FALSE);
			}
		}
	}
	
}