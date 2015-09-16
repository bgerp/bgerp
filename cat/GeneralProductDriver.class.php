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
 * @title     Универсален артикул
 */
class cat_GeneralProductDriver extends cat_ProductDriver
{
	

	/**
	 * Дефолт мета данни за всички продукти
	 */
	protected $defaultMetaData = 'canSell,canBuy';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		// Добавя полетата само ако ги няма във формата
		
		if(!$fieldset->getField('info', FALSE)){
			$fieldset->FLD('info', 'richtext(rows=6, bucket=Notes)', "caption=Описание,mandatory,formOrder=4");
		} else {
			$fieldset->setField('info', 'input');
		}
		
		if(!$fieldset->getField('measureId', FALSE)){
			$fieldset->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', "caption=Мярка,mandatory,formOrder=4");
		} else {
			$fieldset->setField('measureId', 'input');
		}
		
		if(!$fieldset->getField('photo', FALSE)){
			$fieldset->FLD('photo', 'fileman_FileType(bucket=pictures)', "caption=Изображение,formOrder=4");
		} else {
			$fieldset->setField('photo', 'input');
		}
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($Driver, &$data)
	{
		$form = &$data->form;
		
		if(cls::haveInterface('marketing_InquiryEmbedderIntf', $form->mvc)){
			$form->setField('photo', 'input=none');
			$form->setDefault('measureId', $this->getDriverUom());
			$form->setField('measureId', 'display=hidden');
		}
		
		if(isset($form->rec->folderId)){
			$Cover = doc_Folders::getCover($form->rec->folderId);
			
			// Ако корицата е категория и има позволени мерки, оставяме само тях
			if($Cover->getInstance() instanceof cat_Categories){
				$arr = keylist::toArray($Cover->fetchField('measures'));
				if(count($arr)){
					if(isset($form->rec->measureId)){
						$arr = array($form->rec->measureId) + $arr;
					}
					$options = array();
					foreach ($arr as $mId){
						$options[$mId] = cat_UoM::getTitleById($mId);
					}
					
					if($form->getFieldTypeParam('measureId', 'isReadOnly') !== TRUE){
						$form->setOptions('measureId', $options);
					}
				}
			}
		}
	}
	
	/**
	 * Връща счетоводните свойства на обекта
	 */
	public function getFeatures($productId)
	{
		return cat_products_Params::getFeatures('cat_Products', $productId);
	}
	
	
	/**
	 * Подготовка за рендиране на единичния изглед
	 *
	 *
	 * @param cal_Reminders $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareSingle($Driver, $data)
	{
		if($data->rec->photo){
			$size = array(280, 150);
			$Fancybox = cls::get('fancybox_Fancybox');
			$data->row->image = $Fancybox->getImage($data->rec->photo, $size, array(550, 550));
		}
		
		$data->prepareForPublicDocument = $Driver->prepareForPublicDocument;
		$data->masterId = $data->rec->id;
		$data->masterClassId = cat_Products::getClassId();
		
		cat_products_Params::prepareParams($data);
		
		return $data;
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 */
	public static function on_AfterRenderSingle($Driver, &$tpl, $data)
	{
		// Ако не е зададен шаблон, взимаме дефолтния
		$nTpl = (empty($data->tpl)) ? getTplFromFile('cat/tpl/SingleLayoutBaseDriver.shtml') : $data->tpl;
		$nTpl->placeObject($data->row);
		
		// Ако ембедъра няма интерфейса за артикул, то към него немогат да се променят параметрите
		if(!cls::haveInterface('cat_ProductAccRegIntf', 'cat_Products')){
			$data->noChange = TRUE;
		}
		
		// Рендираме параметрите винаги ако сме към артикул или ако има записи
		if($data->noChange !== TRUE || count($data->params)){
			$paramTpl = cat_products_Params::renderParams($data);
			$nTpl->append($paramTpl, 'PARAMS');
		}
		$nTpl->removeBlocks();
		$nTpl->removePlaces();
		$tpl->append($nTpl, 'innerState');
	}
	
	
	/**
	 * Връща информацията за продукта от драйвера
	 *
	 * @param int $productId
	 */
	public function getProductInfo($productId)
	{
		$rec = cat_Products::fetch($productId);
		
		$res = new stdClass();
		$res->productRec = new stdClass();
	
		$res->productRec->name = ($rec->title) ? $rec->title : $rec->name;
		$res->productRec->info = $rec->info;
		$res->productRec->measureId = $rec->measureId;
	
		(!$packagingId) ? $res->packagings = array() : $res->packagingRec = new stdClass();
	
		return $res;
	}
	
	
	/**
	 * Връща параметрите на артикула
	 * @param mixed $productId - ид или запис на артикул
	 *
	 * @return array $res - параметрите на артикула
	 * 					['weight']          -  Тегло
	 * 					['width']           -  Широчина
	 * 					['volume']          -  Обем
	 * 					['thickness']       -  Дебелина
	 * 					['length']          -  Дължина
	 * 					['height']          -  Височина
	 * 					['tolerance']       -  Толеранс
	 * 					['transportWeight'] -  Транспортно тегло
	 * 					['transportVolume'] -  Транспортен обем
	 * 					['term']            -  Срок
	 */
	public function getParams($productId)
	{
		$res = array();
	
		foreach (array('weight', 'width', 'volume', 'thickness', 'length', 'height', 'tolerance', 'transportWeight', 'transportVolume', 'term') as $p){
				
			$res[$p] = cat_products_Params::fetchParamValue($productId, cat_Products::getClassId(), $p);
		}
	
		return $res;
	}
	
	
	/**
	 * Подготвя данните за показване на описанието на драйвера
	 *
	 * @param enum(public,internal) $documentType - публичен или външен е документа за който ще се кешира изгледа
	 */
	public function prepareProductDescription($productId, $documentType = 'public')
	{
		
		bp($this);
		
		
		
		if($documentType == 'public'){
			$this->prepareForPublicDocument = TRUE;
		}
		$data = $this->prepareEmbeddedData();
		unset($this->prepareForPublicDocument);
		$data->noChange = TRUE;
		$data->tpl = getTplFromFile('cat/tpl/SingleLayoutBaseDriverShort.shtml');
	
		return $data;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * Рендира вградения обект
	 *
	 * @param stdClass $data
	 */
	public function renderEmbeddedData(&$embedderTpl, $data)
	{
		if($this->innerState->photo){
			$size = array(280, 150);
			$Fancybox = cls::get('fancybox_Fancybox');
			
			$attr = array();
			if(Mode::is('text', 'xhtml') || Mode::is('text', 'plain') || Mode::is('pdf')){
				$attr['isAbsolute'] = TRUE;
			}
			
			$data->row->image = $Fancybox->getImage($this->innerState->photo, $size, array(550, 550), NULL, $attr);
		}
		
		// Ако не е зададен шаблон, взимаме дефолтния
		$tpl = (empty($data->tpl)) ? getTplFromFile('cat/tpl/SingleLayoutBaseDriver.shtml') : $data->tpl;
		$tpl->placeObject($data->row);
		
		// Ако ембедъра няма интерфейса за артикул, то към него немогат да се променят параметрите
		if(!$this->EmbedderRec->haveInterface('cat_ProductAccRegIntf')){
			$data->noChange = TRUE;
		} 
		
		// Рендираме параметрите винаги ако сме към артикул или ако има записи
		if($data->noChange !== TRUE || count($data->params)){
			$paramTpl = cat_products_Params::renderParams($data);
			$tpl->append($paramTpl, 'PARAMS');
		}
		
		$embedderTpl->append($tpl, 'innerState');
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
		
		if($innerState->photo){
			$size = array(280, 150);
			$Fancybox = cls::get('fancybox_Fancybox');
			$row->image = $Fancybox->getImage($innerState->photo, $size, array(550, 550));
		}
		
		$data->row = $row;
		$data->prepareForPublicDocument = $this->prepareForPublicDocument;
		$data->masterId = $this->EmbedderRec->rec()->id;
		$data->masterClassId = $this->EmbedderRec->getClassId();
		
		cat_products_Params::prepareParams($data);
		
		return $data;
	}
	
	
	
	
	
	/**
	 * Кои опаковки поддържа продукта
	 */
	public function getPacks()
	{
		return $options = array('' => cat_UoM::getTitleById($this->innerState->measureId));
	}
	
	
	
	
	
	/**
	 * Кои документи са използвани в полетата на драйвера
	 */
	public function getUsedDocs()
	{
		// Мъчим се да извлечем използваните документи от описанието (ако има такива)
		return doc_RichTextPlg::getAttachedDocs($this->innerState->info);
	}
	
	
	/**
	 * Променя ключовите думи от мениджъра
	 */
	public function alterSearchKeywords(&$searchKeywords)
	{
		$RichText = cls::get('type_Richtext');
		$info = strip_tags($RichText->toVerbal($this->innerForm->info));
		$searchKeywords .= " " . plg_Search::normalizeText($info);
	}
	
	
	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 *
	 * @param core_Form $form
	 */
	public function prepareEmbeddedForm(core_Form &$form)
	{
		if($this->EmbedderRec->haveInterface('marketing_InquiryEmbedderIntf')){
			$form->setField('photo', 'input=none');
			$form->setDefault('measureId', $this->getDriverUom());
			$form->setField('measureId', 'display=hidden');
		}
		
		if(isset($form->rec->folderId)){
			$Cover = doc_Folders::getCover($form->rec->folderId);
			
			// Ако корицата е категория и има позволени мерки, оставяме само тях
			if($Cover->getInstance() instanceof cat_Categories){
				$arr = keylist::toArray($Cover->fetchField('measures'));
				if(count($arr)){
					if(isset($form->rec->measureId)){
						$arr = array($form->rec->measureId) + $arr;
					}
					$options = array();
					foreach ($arr as $mId){
						$options[$mId] = cat_UoM::getTitleById($mId);
					}
					
					if($form->getFieldTypeParam('measureId', 'isReadOnly') !== TRUE){
						$form->setOptions('measureId', $options);
					}
				}
			}
		}
		
		// Викаме метода на бащата
		parent::prepareEmbeddedForm($form);
	}
	
	
	/**
	 * Изображението на артикула
	 */
	public function getProductImage()
	{
		return $this->innerState->photo;
	}
	
	
	
	
	
	
	
	
	/**
	 * Рендира данните за показване на артикула
	 */
	public function renderProductDescription($data)
	{
		$tpl = new ET("[#innerState#]");
		$this->renderEmbeddedData($tpl, $data);
		
		$title = $this->EmbedderRec->getShortHyperlink();
		$tpl->replace($title, "TITLE");
		
		$tpl->push(('cat/tpl/css/GeneralProductStyles.css'), 'CSS');
		
		$wrapTpl = new ET("<div class='general-product-description'>[#paramBody#]</div>");
		$wrapTpl->append($tpl, 'paramBody');
		
		return $wrapTpl;
	}
}