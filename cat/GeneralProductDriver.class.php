<?php

/**
 * Драйвър за универсален артикул
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Драйвър за универсален артикул
 */
class cat_GeneralProductDriver extends cat_ProductDriver
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_SpecificationBaseDriver';
	
	
	/**
	 * Инстанция на класа имплементиращ интерфейса
	 */
	public $class;
	
	
	/**
	 * Инстанция на 'cat_products_Params'
	 */
	protected $Params;
	
	
	/**
	 * Добавя полетата на вътрешния обект
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addEmbeddedFields(core_Fieldset &$form)
	{
		// Добавя полетата само ако ги няма във формата
		
		if(!$form->getField('info', FALSE)){
			$form->FLD('info', 'richtext(rows=6, bucket=Notes)', "caption=Описание,mandatory,formOrder=4");
		} else {
			$form->setField('info', 'input');
		}
		
		if(!$form->getField('measureId', FALSE)){
			$form->FLD('measureId', 'key(mvc=cat_UoM, select=name)', "caption=Мярка,mandatory,formOrder=4");
		} else {
			$form->setField('measureId', 'input');
		}
		
		if(!$form->getField('image', FALSE)){
			$form->FLD('image', 'fileman_FileType(bucket=pictures)', "caption=Изображение,formOrder=4");
		} else {
			$form->setField('image', 'input');
		}
	}
	
	
	/**
	 * Рендира вградения обект
	 *
	 * @param stdClass $data
	 */
	public function renderEmbeddedData($data)
	{
		$tpl = getTplFromFile('cat/tpl/SingleLayoutBaseDriver.shtml');
		
		$tpl->placeObject($data->row);
		
		$paramTpl = cat_products_Params::renderParams($data);
		$tpl->append($paramTpl, 'PARAMS');
		
		return $tpl;
	}
	
	
	/**
	 * Подготвя данните необходими за показването на вградения обект
	 *
	 * @param core_Form $innerForm
	 * @param stdClass $innerState
	 */
	public function prepareEmbeddedData()
	{
		$data = new stdClass();
		$innerForm = $this->innerForm;
		$innerState = $this->innerState;
		
		$fSet = new core_FieldSet;
		$this->addEmbeddedFields($fSet);
		$fields = $fSet->selectFields();
		
		$row = new stdClass();
		foreach ($fields as $name => $fld){
			$row->{$name} = $fld->type->toVerbal($innerState->{$name});
		}
		
		if($innerState->image){
			$size = array(280, 150);
			$Fancybox = cls::get('fancybox_Fancybox');
			$row->image = $Fancybox->getImage($innerState->image, $size, array(550, 550));
		}
		
		$data->row = $row;
		
		$data->masterId = $this->ProductRec->rec()->id;
		$data->masterClassId = $this->ProductRec->getClassId();
		cat_products_Params::prepareParams($data);
		
		return $data;
	}
	
	
	/**
	 * Връща информацията за продукта от драйвера
	 * 
	 * @param stdClass $innerState
	 * @param int $packagingId
	 * @return stdClass $res
	 */
	public function getProductInfo($packagingId = NULL)
	{
		$innerState = $this->innerState;
		$res = new stdClass();
		$res->productRec = new stdClass();
		
		$res->productRec->name = ($innerState->title) ? $innerState->title : $innerState->name;
		$res->productRec->info = $innerState->info;
		$res->productRec->measureId = $innerState->measureId;
		
		(!$packagingId) ? $res->packagings = array() : $res->packagingRec = new stdClass();
		
		return $res;
	}
	
	
	/**
	 * Връща стойността на продукта отговаряща на параметъра
	 * 
	 * @param string $sysId - систем ид на параметър (@see cat_Params)
	 * @return mixed - стойността на параметъра за продукта
	 */
	public function getParamValue($sysId)
	{
		return cat_products_Params::fetchParamValue($this->ProductRec->rec()->id, $this->ProductRec->getClassId(), $sysId);
	}
	
	
	/**
	 * Кои опаковки поддържа продукта
	 */
	public function getPacks()
	{
		return $options = array('' => cat_UoM::getTitleById($this->innerState->measureId));
	}
	
	
	/**
	 * Връща счетоводните свойства на обекта
	 */
	public function getFeatures()
	{
		return cat_products_Params::getFeatures($this->ProductRec->getClassId(), $this->ProductRec->rec()->id);
	}
	
	
	/**
	 * Връща описанието на артикула според драйвъра
	 */
	public function getProductDescription()
	{
		$data = $this->prepareEmbeddedData();
		$data->noChange = TRUE;
		
		$tpl = $this->renderEmbeddedData($data);
		$title = ht::createLinkRef($this->ProductRec->getTitleById(), array($this->ProductRec->instance, 'single', $id));
		$tpl->replace($title, "TITLE");
		
		// Ако няма параметри, премахваме блока им от шаблона
		if(!count($data->params)){
			$tpl->removeBlock('PARAMS');
		}
		
		return $tpl;
	}
}