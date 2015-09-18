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
		
		if(!$fieldset->getField('photo', FALSE)){
			$fieldset->FLD('photo', 'fileman_FileType(bucket=pictures)', "caption=Изображение,formOrder=4");
		} else {
			$fieldset->setField('photo', 'input');
		}
		
		if(!$fieldset->getField('measureId', FALSE)){
			$fieldset->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', "caption=Мярка,mandatory,formOrder=4");
		} else {
			$fieldset->setField('measureId', 'input');
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
		
		if(cls::haveInterface('marketing_InquiryEmbedderIntf', $Driver->Embedder)){
			$form->setField('photo', 'input=none');
			$form->setDefault('measureId', $Driver->getDefaultUom($data->driverParams['measureId']));
			$form->setField('measureId', 'display=hidden');
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
		
		if(!cls::haveInterface('marketing_InquiryEmbedderIntf', $Driver->Embedder)){
			cat_products_Params::prepareParams($data);
		}
		
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
		if(!cls::haveInterface('cat_ProductAccRegIntf', $Driver->Embedder)){
			$data->noChange = TRUE;
		}
		
		// Рендираме параметрите винаги ако сме към артикул или ако има записи
		if($data->noChange !== TRUE || count($data->params)){
			$paramTpl = cat_products_Params::renderParams($data);
			$nTpl->append($paramTpl, 'PARAMS');
		}
		
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
		$res->packagings = array();
	
		return $res;
	}
	
	
	/**
	 * Връща параметрите на артикула
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
	public function getParams()
	{
		$res = array();
		
		foreach (array('weight', 'width', 'volume', 'thickness', 'length', 'height', 'tolerance', 'transportWeight', 'transportVolume', 'term') as $p){
			$res[$p] = cat_products_Params::fetchParamValue($this->driverRec->id, $this->Embedder->getClassId(), $p);
		}
	
		return $res;
	}
	
	
	/**
	 * Подготвя данните за показване на описанието на драйвера
	 *
	 * @param enum(public,internal) $documentType - публичен или външен е документа за който ще се кешира изгледа
	 */
	public function prepareProductDescription($documentType = 'public')
	{
		$data = new stdClass();
		$data->rec = $this->driverRec;
		$data->row = $this->Embedder->recToVerbal($data->rec);
		
		if($documentType == 'public'){
			$this->prepareForPublicDocument = TRUE;
		}
		
		$this->invoke('AfterPrepareSingle', array(&$data));
		$data->tpl = getTplFromFile('cat/tpl/SingleLayoutBaseDriverShort.shtml');
	
		return $data;
	}
	
	
	/**
	 * Рендира данните за показване на артикула
	 */
	public function renderProductDescription($data)
	{
		$data->noChange = TRUE;
		$tpl = new ET("[#innerState#]");
		
		$this->invoke('AfterRenderSingle', array(&$tpl, $data));
		$title = $this->Embedder->getShortHyperlink($this->driverRec->id);
		$tpl->replace($title, "TITLE");
	
		$tpl->push(('cat/tpl/css/GeneralProductStyles.css'), 'CSS');
	
		$wrapTpl = new ET("<div class='general-product-description'>[#paramBody#]</div>");
		$wrapTpl->append($tpl, 'paramBody');
	
		return $wrapTpl;
	}
	
	
	/**
	 * Кои документи са използвани в полетата на драйвера
	 */
	public function getUsedDocs()
	{
		// Мъчим се да извлечем използваните документи от описанието (ако има такива)
		return doc_RichTextPlg::getAttachedDocs($this->driverRec->info);
	}
	
	
	/**
	 * Променя ключовите думи от мениджъра
	 */
	public function alterSearchKeywords(&$searchKeywords)
	{
		$RichText = cls::get('type_Richtext');
		$info = strip_tags($RichText->toVerbal($this->driverRec->info));
		$searchKeywords .= " " . plg_Search::normalizeText($info);
	}
	
	
	/**
	 * Изображението на артикула
	 */
	public function getProductImage()
	{
		return $this->driverRec->photo;
	}
}