<?php

/**
 * Драйвър за универсален артикул
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
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
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(cat_ProductDriver $Driver, embed_Manager $Embedder, &$data)
	{
		$form = &$data->form;
		
		if(cls::haveInterface('marketing_InquiryEmbedderIntf', $Embedder)){
			$form->setField('photo', 'input=none');
			$form->setDefault('measureId', $Driver->getDefaultUom($data->driverParams['measureId']));
			$form->setField('measureId', 'display=hidden');
		}
		
		if($form->rec->folderId && empty($form->rec->id)){
			$cover = doc_Folders::getCover($form->rec->folderId);
			if($cover->haveInterface('cat_ProductFolderCoverIntf')){
				
				// Всеки дефолтен параметър, добавяме го като поле във формата за по лесно добавяне
				// Въведените стойностти след запис ще се запишат в детайла на продуктовите параметри
				$defaultParams = $cover->getDefaultProductParams();
					
				foreach ($defaultParams as $id){
					$paramRec = cat_Params::fetch($id);
					$form->FLD("paramcat{$id}", 'double', "caption=Параметри|*->{$paramRec->name},formOrder=100000002,categoryParams");
					$form->setFieldType("paramcat{$id}", cat_Params::getParamTypeClass($id, 'cat_Params'));
				}
			}
		}
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param int $id
	 * @param stdClass $rec
	 */
	public static function on_AfterSave(cat_ProductDriver $Driver, embed_Manager $Embedder, &$id, $rec)
	{
		$arr = (array)$rec;
		
		// За всеко поле от записа 
		foreach ($arr as $key => $value){
			
			// Ако името му съдържа ключова дума
			if(strpos($key, 'paramcat') !== FALSE){
				$paramId = substr($key, 8);
				
				// Има стойност и е разпознато ид на параметър
				if(cat_Params::fetch($paramId) && !empty($value)){
					$dRec = (object)array('productId'  => $rec->id,
										  'paramId'    => $paramId,
										  'paramValue' => $value);
					
					// Записваме продуктовия параметър с въведената стойност
					if(!cls::get('cat_products_Params')->isUnique($dRec, $fields, $exRec)){
						$dRec->id = $exRec->id;
					}
					
					cat_products_Params::save($dRec);
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
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $res
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareSingle(cat_ProductDriver $Driver, embed_Manager $Embedder, &$res, &$data)
	{
		if($data->rec->photo){
			$size = array(280, 150);
			$Fancybox = cls::get('fancybox_Fancybox');
			$data->row->image = $Fancybox->getImage($data->rec->photo, $size, array(550, 550));
		}
		
		$data->prepareForPublicDocument = $Driver->prepareForPublicDocument;
		$data->masterId = $data->rec->id;
		$data->masterClassId = cat_Products::getClassId();
		
		// Рендираме параметрите, само ако не е към запитване
		if(!cls::haveInterface('marketing_InquiryEmbedderIntf', $Embedder)){
			cat_products_Params::prepareParams($data);
		}
		
		return $data;
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 * 
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	public static function on_AfterRenderSingle(cat_ProductDriver $Driver, embed_Manager $Embedder, &$tpl, $data)
	{
		// Ако не е зададен шаблон, взимаме дефолтния
		$nTpl = (empty($data->tpl)) ? getTplFromFile('cat/tpl/SingleLayoutBaseDriver.shtml') : $data->tpl;
		$nTpl->placeObject($data->row);
	
		// Ако ембедъра няма интерфейса за артикул, то към него немогат да се променят параметрите
		if(!cls::haveInterface('cat_ProductAccRegIntf', $Embedder)){
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
	 * Връща стойността на параметъра с това име
	 * 
	 * @param string $id   - ид на записа
	 * @param string $name - име на параметъра
	 * @return mixed - стойност или FALSE ако няма
	 */
	public function getParamValue($id, $name)
	{
		return cat_products_Params::fetchParamValue($id, $name);
	}
	
	
	/**
	 * Подготвя данните за показване на описанието на драйвера
	 *
	 * @param stdClass $rec - запис
	 * @param enum(public,internal) $documentType - публичен или външен е документа за който ще се кешира изгледа
	 * @return stdClass - подготвените данни за описанието
	 */
	public function prepareProductDescription($rec, $documentType = 'public')
	{
		$data = new stdClass();
		$data->rec = $rec;
		$data->row = cat_Products::recToVerbal($data->rec);
		
		if($documentType == 'public'){
			$this->prepareForPublicDocument = TRUE;
		}
		
		$this->invoke('AfterPrepareSingle', array(cls::get('cat_Products'), &$data, &$data));
		$data->tpl = getTplFromFile('cat/tpl/SingleLayoutBaseDriverShort.shtml');
	
		return $data;
	}
	
	
	/**
	 * Рендира данните за показване на артикула
	 * 
	 * @param stdClass $data
	 * @return core_ET $tpl
	 */
	public function renderProductDescription($data)
	{
		$data->noChange = TRUE;
		$tpl = new ET("[#innerState#]");
		
		$this->invoke('AfterRenderSingle', array(cls::get('cat_Products'), &$tpl, $data));
		$title = cat_Products::getShortHyperlink($data->masterId);
		$tpl->replace($title, "TITLE");
	
		$tpl->push(('cat/tpl/css/GeneralProductStyles.css'), 'CSS');
	
		$wrapTpl = new ET("<div class='general-product-description'>[#paramBody#]</div>");
		$wrapTpl->append($tpl, 'paramBody');
	
		return $wrapTpl;
	}
	
	
	/**
	 * Добавя ключови думи за пълнотекстово търсене
	 * 
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $res
	 * @param stdClass $rec
	 */
	public static function on_AfterGetSearchKeywords(cat_ProductDriver $Driver, embed_Manager $Embedder, &$res, $rec)
	{
		$RichText = cls::get('type_Richtext');
		$info = strip_tags($RichText->toVerbal($rec->info));
		$res .= " " . plg_Search::normalizeText($info);
	}
	
	
	/**
	 * Връща хендлъра на изображението представящо артикула, ако има такова
	 *
	 * @param mixed $id - ид или запис
	 * @return fileman_FileType $hnd - файлов хендлър на изображението
	 */
	public function getProductImage($rec)
	{
		return $rec->photo;
	}
	
	
	/**
	 * Връща дефолтната основна мярка, специфична за технолога
	 *
	 * @param int $measureId - мярка
	 * @return int - ид на мярката
	 */
	public function getDefaultUom($measureId = NULL)
	{
		if(!isset($measureId)){
			$defMeasure = core_Packs::getConfigValue('cat', 'CAT_DEFAULT_MEASURE_ID');
			$defMeasure = (!empty($defMeasure)) ? $defMeasure : NULL;
			
			// Ако не е подадена мярка, връща дефолтната за универсалния артикул
			return $defMeasure;
		}
	
		return $measureId;
	}
}