<?php



/**
 * Клас 'cond_DeliveryTerms' - Условия на доставка
 *
 * Набор от стандартните условия на доставка (FOB, DAP, ...)
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Countries extends core_Manager
{
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,cond';
	
	
	/**
	 * Кой може да пише
	 */
	public $canWrite = 'ceo,cond';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2,cond_Wrapper';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'country, conditionId, value';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Търговски условия по държави';
	
	
	/**
	 * Заглавие на единичния обект
	 */
	public $singleTitle = 'търговско условие за държава';
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	function description()
	{
		$this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,mandatory,silent');
		$this->FLD('conditionId', 'key(mvc=cond_Parameters,select=name,allowEmpty)', 'input,caption=Условие,mandatory,silent,removeAndRefreshForm=value');
		$this->FLD('value', 'varchar(255)', 'caption=Стойност, mandatory');
		
		$this->setDbUnique('country,conditionId');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = $form->rec;
		$myCompany = crm_Companies::fetchOurCompany();
		$form->setDefault('country', $myCompany->country);
		
		if($rec->conditionId){
			if($Driver = cond_Parameters::getDriver($rec->conditionId)){
				$form->setField('value', 'input');
				$pRec = cond_Parameters::fetch($rec->conditionId);
				if($Type = $Driver->getType($pRec)){
					$form->setFieldType('value', $Type);
				}
			} else {
				$form->setError('conditionId', 'Има проблем при зареждането на типа');
			}
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$paramRec = cond_Parameters::fetch($rec->conditionId);
		$row->conditionId = cond_Parameters::getVerbal($paramRec, 'typeExt');
		
		if($ParamType = cond_Parameters::getTypeInstance($paramRec)){
			$row->value = $ParamType->toVerbal(trim($rec->value));
		}
		
		$row->ROW_ATTR['class'] .= " state-active";
	}
}