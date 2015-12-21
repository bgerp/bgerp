<?php



/**
 * Клас 'batch_plg_DocumentMovementDetail' - За генериране на партидни движения от документите
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo да се разработи
 */
class batch_plg_DocumentMovementDetail extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		$mvc->FLD('batch', 'varchar(128)', 'input=hidden,caption=Партиден №,after=productId,forceField');
		setIfNot($mvc->productFieldName, 'productId');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$data->form->setField('batch', 'input=hidden');
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		
		if(isset($rec->{$mvc->productFieldName})){
			$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
			if($BatchClass){
				$form->setField('batch', 'input');
				$form->setFieldType('batch', $BatchClass->getBatchClassType());
				$form->setDefault('batch', $BatchClass->getAutoValue($this, 1));
			} else {
				$form->setField('batch', 'input=none');
				unset($rec->batch);
			}
			
			if($form->isSubmitted()){
				if(is_object($BatchClass)){
					if(!$BatchClass->isValid($rec->batch, $msg)){
						$form->setError('batch', $msg);
					}
				}
			}
		}
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	public static function on_BeforeRenderListTable($mvc, &$res, $data)
	{
		if(!count($data->rows)) return;
		$recs = $data->recs;
		
		foreach ($data->rows as $id => &$row){
			if($recs[$id]->batch){
				$batch = $mvc->getFieldType('batch')->toVerbal($recs[$id]->batch);
				if(is_object($row->{$mvc->productFieldName})){
					$row->productId->append('Парт. №: ' . $batch);
				} else {
					$row->productId .= "<br><small>lot. №: {$batch}</small>";
				}
			}
		}
	}
}