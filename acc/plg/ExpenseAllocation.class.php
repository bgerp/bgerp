<?php



/**
 * Плъгин за документи към, които може да се разпределят разходи
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_ExpenseAllocation extends core_Plugin
{
	
	
	/**
	 * Кои са допустимите класове, към които може да се прикача
	 * 
	 * @param array
	 */
	private static $allowedClasses = array('purchase_Services', 'sales_Services', 'purchase_Purchases', 'findeals_AdvanceReports');
	
	
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		$mvc->declareInterface('acc_ExpenseAllocatableIntf');
		
		// Мениджъра трябва да е в допустимите класове
		expect(in_array($mvc->className, self::$allowedClasses));
		
		// Дефолтни имена на полетата от модела
		setIfNot($mvc->expenseItemIdFld, 'expenseItemId');
		setIfNot($mvc->discountFld, 'discount');
		setIfNot($mvc->packPriceFld, 'packPrice');
		setIfNot($mvc->productIdFld, 'productId');
		setIfNot($mvc->packagingIdFld, 'packagingId');
		setIfNot($mvc->quantityInPackFld, 'quantityInPack');
		setIfNot($mvc->quantityFld, 'quantity');
	}
	
	
	/**
	 * След подготовка на тулбара на единичен изглед
	 */
	public static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		// Ако може да се добавя разпределение на разход, се показва бутона
		if(acc_ExpenseAllocations::haveRightFor('add', (object)array('originId' => $data->rec->containerId))){
			$data->toolbar->addBtn('Разходи', array('acc_ExpenseAllocations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), "ef_icon = img/16/star_2.png,title=Разпределение на разходи към документа,order=15");
		}
		
		// Ако към документа има вече друг неоотеглен документ за разпределение на разход, се показва бутон-линк към него
		if($allocationId = acc_ExpenseAllocations::fetchField("#originId = {$data->rec->containerId} AND #id != '{$rec->id}' AND #state != 'rejected'")){
			$arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
			
			$data->toolbar->addBtn("Разходи|* {$arrow}", array('acc_ExpenseAllocations', 'single', $allocationId, 'ret_url' => TRUE), "ef_icon = img/16/view.png,title=Към документа за разпределяне на разходи,order=15");
		}
	}
	
	
	/**
	 * Връща нескладируемите артикули върху, които
	 * не са разпределени разходи от документа
	 *
	 * @param int $id       - ид
	 * @param string $limit - брой записи, NULL за всички
	 * @return array $res   - масив със всички записи
	 * 
	 * 				o originRecId    - към кой ред от детайла е записа
	 * 				o productId      - ид на артикула
	 * 				o packagingId    - ид на опаковката/мярката
	 * 				o quantityInPack - к-во в опаковка, ако е основната е 1
	 * 				o quantity       - чисто количество (брой в опаковка по брой опаковки)
	 * 				o discount       - отстъпката върху цената
	 * 				o packPrice      - цената във валутата с която се показва в документа 
	 */
	public static function on_AfterGetRecsForAllocation($mvc, &$res, $id, $limit = NULL)
	{
		if(isset($res)) return;
		
		$rec = $mvc->fetchRec($id);
		$res = array();
		
		$Detail = cls::get($mvc->mainDetail);
		
		// Пресяват се само редовете с нескладируеми артикули, които не са ДМА и са неразпределени
		$query = $Detail->getQuery();
		$query->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey={$mvc->productIdFld}");
		$query->EXT('fixedAsset', 'cat_Products', "externalName=fixedAsset,externalKey={$mvc->productIdFld}");
		$query->EXT('canConvert', 'cat_Products', "externalName=canConvert,externalKey={$mvc->productIdFld}");
		$query->where("#{$Detail->masterKey} = {$id}");
		$query->where("#{$mvc->expenseItemIdFld} IS NULL");
		$query->where("#canStore = 'no' AND #fixedAsset = 'no' AND #canConvert = 'no'");
		$query->orderBy('id', 'ASC');
		
		if(isset($limit)){
			$query->limit($limit);
		}
		 
		// За всеки запис
		while($dRec = $query->fetch()){
			$r = new stdClass();
			$r->originRecId = $dRec->id;
			$r->packPrice = deals_Helper::getDisplayPrice($dRec->{$mvc->packPriceFld}, 0.2, $rec->currencyRate, $rec->chargeVat);
			$r->discount = $dRec->{$mvc->discountFld};
	
			foreach (array('productId', 'packagingId', 'quantityInPack', 'quantity') as $fld){
				$r->{$fld} = $dRec->{$mvc->{"{$fld}Fld"}};
			}
	
			$res[$dRec->id] = $r;
		}
		
		// Намерените редове за разпределяне
		return $res;
	}
}