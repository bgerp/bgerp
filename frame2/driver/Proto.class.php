<?php

/**
 * Базов драйвер за драйвер на артикул
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class frame2_driver_Proto extends core_BaseClass
{
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'frame2_ReportIntf';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	}
	
	
	/**
	 * Кой може да избере драйвера
	 */
	public function canSelectDriver($userId = NULL)
	{
		return core_Users::haveRole($this->canSelectDriver, $userId);
	}
	
	
	/**
	 * Връща заглавието на отчета
	 * 
	 * @param stdClass $rec - запис
	 * @return string|NULL  - заглавието или NULL, ако няма
	 */
	public function getTitle($rec)
	{
		return NULL;
	}
	
	
	/**
	 * Подготвя данните на справката от нулата, които се записват в модела
	 * 
	 * @param stdClass $rec        - запис на справката
	 * @return stdClass|NULL $data - подготвените данни
	 */
	public function prepareData($rec)
	{
		return NULL;
	}
	
	
	/**
	 * Рендиране на данните на справката
	 *
	 * @param stdClass $rec - запис на справката
	 * @return core_ET      - рендирания шаблон
	 */
	public function renderData($rec)
	{
		return new core_ET("");
	}
	
	
	/**
	 * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
	 * 
	 * @param stdClass $rec
	 * @return boolean
	 */
	public function canSendNotification($rec)
	{
		return TRUE;
	}
	
	
	/**
	 * Връща параметрите, които ще бъдат заместени в текста на нотификацията
	 * 
	 * @param stdClass $rec
	 * @return array
	 */
	public function getNotificationParams($rec)
	{
		$params = array();
		$params['handle'] = "#" . frame2_Reports::getHandle($rec->id);
		
		return $params;
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	public static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
	{
		$row = $data->row;
		$rec = $data->rec;
	
		$form = cls::get('core_Form');
		$Driver->addFields($form);
		$fields = (is_array($form->fields)) ? $form->fields : array();
		
		foreach ($fields as $name => $fld){
			if(isset($rec->{$name}) && $fld->single !== 'none'){
				$append = new core_ET(tr("|*<tr><td class='quiet'>[#caption#]</td><td>[#value#]</td></tr>"));
				$append->replace($fld->caption, 'caption');
				$append->replace($row->{$name}, 'value');
				$tpl->append($append, 'DRIVER_FIELDS');
			}
		}
	}
}