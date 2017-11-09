<?php



/**
 * Клас 'deals_ManifactureDetail' - базов клас за детайли на производствени документи
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
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
	 * Полета, които при клониране да не са попълнени
	 *
	 * @see plg_Clone
	 */
	public $fieldsNotToClone = 'createdBy,createdOn';
	
	
	/**
	 * Да се показва ли кода като в отделна колона
	 */
	public $showCodeColumn = TRUE;
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function setDetailFields($mvc)
	{
		$mvc->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,mandatory', 'tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=quantity|measureId|packagingId|packQuantity');
		$mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','tdClass=small-field nowrap,smartCenter,mandatory,input=hidden');
		$mvc->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input=input,mandatory,smartCenter');
		$mvc->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
		
		$mvc->FLD('quantity', 'double(Min=0)', 'caption=Количество,input=none,smartCenter');
		$mvc->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden');
	}
	

	/**
	 * Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->query->orderBy('id', 'ASC');
	}
	
	
	/**
	 * Изчисляване на количеството на реда в брой опаковки
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
	{
		if (empty($rec->quantity) || empty($rec->quantityInPack)) return;
		
		$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
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
		setIfNot($data->defaultMeta, $mvc->defaultMeta);
		if(!$data->defaultMeta) return;
		
		$products = cat_Products::getByProperty($data->defaultMeta);
		$data->form->setOptions('productId', array('' => ' ') + $products);
		
		if (isset($form->rec->id)) {
			$data->form->setReadOnly('productId');
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 */
	protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
	{
		$rec = &$form->rec;
		
		if($rec->productId){
			$form->setDefault('measureId', cat_Products::getProductInfo($rec->productId)->productRec->measureId);
			
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
			$form->setDefault('packagingId', key($packs));
			
			// Ако артикула не е складируем, скриваме полето за мярка
			$productInfo = cat_Products::getProductInfo($rec->productId);
			if(!isset($productInfo->meta['canStore'])){
				$measureShort = cat_UoM::getShortName($rec->packagingId);
				$form->setField('packQuantity', "unit={$measureShort}");
			} else {
    			$form->setField('packagingId', 'input');
    		}
		}
		
		if($form->isSubmitted()){
			$productInfo = cat_Products::getProductInfo($rec->productId);
			$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
			
			if($rec->productId){
				if($rec->productId){
					$rec->measureId = $productInfo->productRec->measureId;
				}
			}
			
			$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
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
		if (!empty($data->toolbar->buttons['btnAdd']) && isset($mvc->defaultMeta)) {
				unset($data->toolbar->buttons['btnAdd']);
				$products = cat_Products::getByProperty($mvc->defaultMeta, NULL, 1);
				
				if(!count($products)){
					$error = "error=Няма артикули, ";
				}
	
				$data->toolbar->addBtn('Артикул', array($mvc, 'add', $mvc->masterKey => $data->masterId, 'ret_url' => TRUE),
						"id=btnAdd,{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
		}
	}
	
	
	/**
	 * Преди подготвяне на едит формата
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$singleUrl = cat_Products::getSingleUrlArray($rec->productId);
		$row->productId = cat_Products::getVerbal($rec->productId, 'name');
		$row->productId = ht::createLinkRef($row->productId, $singleUrl);
		
		// Показваме подробната информация за опаковката при нужда
		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
	}
	
	
	/**
	 * Импортиране на вече подготвените записи за импорт
	 *
	 * @see import_DriverIntf
	 * @param array $recs
	 * 		o productId        - ид на артикула
     * 		o quantity         - к-во в основна мярка
     * 		o quantityInPack   - к-во в опаковка
     * 		o packagingId      - ид на опаковка
     * 		o batch            - дефолтна партида, ако може
     * 		o $this->masterKey - ид на мастър ключа
	 * 
	 * @return void
	 */
	public function importRecs($recs)
	{
		if(!is_array($recs)) return;
		foreach ($recs as $rec){
			expect($rec->productId, 'Липсва продукт ид');
			expect(cat_Products::fetchField($rec->productId), 'Няма такъв артикул');
			expect($rec->packagingId, 'Няма опаковка');
			expect(cat_UoM::fetchField($rec->packagingId), 'Несъществуваща опаковка');
			expect($rec->{$this->masterKey}, 'Няма мастър кей');
			expect($this->Master->fetch($rec->{$this->masterKey}), 'Няма такъв запис на мастъра');
			expect($this->haveRightFor('add', (object)array($this->masterKey => $rec->{$this->masterKey})), 'Към този мастър не може да се добавя артикул');
			
			if(!$this->isUnique($rec, $fields, $exRec)){
				core_Statuses::newStatus('Записа, не е импортиран защото им дублаж');
				continue;
			}
			
			$this->save($rec);
		}
	}
}
 	