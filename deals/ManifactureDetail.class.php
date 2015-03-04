<?php


/**
 * Клас 'deals_ManifactureDetail' - базов клас за детайли на производствени документи
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_ManifactureDetail extends doc_Detail
{
	
	
	/**
	 * Какви продукти да могат да се избират в детайла
	 * 
	 * @var enum(canManifacture=Производими,canConvert=Вложими)
	 */
	protected $defaultMeta;
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function setDetailFields($mvc)
	{
		$mvc->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
		$mvc->FLD('productId', 'int', 'caption=Продукт,notNull,mandatory', 'tdClass=large-field leftCol wrap,refreshForm');
		$mvc->FLD('quantity', 'double(Min=0)', 'caption=К-во,mandatory');
		$mvc->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory');
	}
	

	/**
	 * Преди подготвяне на едит формата
	 */
	public static function on_BeforePrepareEditForm($mvc, &$res, $data)
	{
		if($classId = Request::get('classId', 'class(interface=cat_ProductAccRegIntf)')){
			$data->ProductManager = cls::get($classId);
	
			$mvc->getField('productId')->type = cls::get('type_Key', array('params' => array('mvc' => $data->ProductManager->className, 'select' => 'name', 'maxSuggestions' => 1000000000)));
		}
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		
		$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($form->rec->classId);
		$products = $ProductManager->getByProperty($mvc->defaultMeta);
		 
		expect(count($products));
			
		if (empty($form->rec->id)) {
			$data->form->setOptions('productId', array('' => ' ') + $products);
		} else {
			$data->form->setOptions('productId', array($form->rec->productId => $products[$form->rec->productId]));
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 */
	public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
	{
		if($form->rec->productId){
			$form->setDefault('measureId', cls::get($form->rec->classId)->getProductInfo($form->rec->productId)->productRec->measureId);
			$shortName = cat_UoM::getShortName($form->rec->measureId);
			$form->setField('quantity', "unit={$shortName}");
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
			if($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft'){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if (!empty($data->toolbar->buttons['btnAdd'])) {
			$productManagers = core_Classes::getOptionsByInterface('cat_ProductAccRegIntf');
			$masterRec = $data->masterData->rec;
	
			foreach ($productManagers as $manId => $manName) {
				$productMan = cls::get($manId);
				$products = $productMan->getByProperty($mvc->defaultMeta);
	
				if(!count($products)){
					$error = "error=Няма {$productMan->title}";
				}
	
				$title = mb_strtolower($productMan->singleTitle);
				$data->toolbar->addBtn($productMan->singleTitle, array($mvc, 'add', $mvc->masterKey => $masterRec->id, 'classId' => $manId, 'ret_url' => TRUE),
						"id=btnAdd-{$manId},{$error},order=10,title=Добавяне на {$title}", 'ef_icon = img/16/shopping.png');
				unset($error);
			}
	
			unset($data->toolbar->buttons['btnAdd']);
		}
	}
	
	
	/**
	 * Преди подготвяне на едит формата
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$ProductMan = cls::get($rec->classId);
		$row->productId = $ProductMan->getTitleById($rec->productId);
		if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
			$row->productId = ht::createLinkRef($row->productId, array($ProductMan, 'single', $rec->productId));
		}
		
		$row->measureId = cat_UoM::getShortName($rec->measureId);
	}
}
 	