<?php

/**
 * Драйвър за универсален артикул
 *
 *
 * @category  bgerp
 * @package   techno
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
	 * Добавя полетата на вътрешния обект
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addEmbeddedFields(core_Fieldset &$form)
	{
		// Добавя полетата само ако ги няма във формата
		
		if(!$form->getField('info', FALSE)){
			$form->FLD('info', 'richtext(rows=6, bucket=Notes)', "caption=Описание,mandatory");
		}
		
		if(!$form->getField('measureId', FALSE)){
			$form->FLD('measureId', 'key(mvc=cat_UoM, select=name)', "caption=Мярка,mandatory");
		}
		
		if(!$form->getField('image', FALSE)){
			$form->FLD('image', 'fileman_FileType(bucket=techno_GeneralProductsImages)', "caption=Параметри->Изображение");
		}
    	
		if(!$form->getField('code', FALSE)){
			$form->FLD('code', 'varchar(64)', "caption=Параметри->Код,remember=info");
		}
		
		if(!$form->getField('eanCode', FALSE)){
			$form->FLD('eanCode', 'gs1_TypeEan', "input,caption=Параметри->EAN");
		}
	}
	
	
	/**
	 * Рендира вградения обект
	 *
	 * @param stdClass $data
	 */
	public function renderEmbeddedData($data)
	{
		$tpl = getTplFromFile('techno2/tpl/SingleLayoutBaseDriver.shtml');
		
		$tpl->placeObject($data->row);
		$tpl->push('techno2/tpl/GeneralProductsStyles.css', 'CSS');
		
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
		$res->productRec->code = $innerState->code;
		$res->productRec->info = $innerState->info;
		$res->productRec->measureId = $innerState->measureId;
		 
		(!$packagingId) ? $res->packagings = array() : $res = NULL;
		
		return $res;
	}
	
	
	/**
	 * Кои опаковки поддържа продукта
	 */
	public function getPacks()
	{
		return $options = array('' => cat_UoM::getTitleById($this->innerState->measureId));
	}
	
	
	/**
	 * Как да се рендира изгледа в друг документ
	 * 
	 * @param stdClass $data - дата
	 * @return core_ET $tpl - шаблон
	 */
	public function renderDescription($data)
	{
		$tpl = $this->renderEmbeddedData($data);
		$this->renderParams($data->params, $tpl, TRUE);
		$tpl->removeBlock('INTERNAL');
		$tpl->push('techno2/tpl/GeneralProductsStyles.css', 'CSS');
		
		return $tpl;
	}
}