<?php



/**
 * Регистър за разпределяне на разходи
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_CostAllocations extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Отнасяне на разходи';
    
    
    /**
     * Еденично заглавие
     */
    public $singleTitle = 'разход';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_SaveAndNew';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'ceo, acc, purchase';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo, acc, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, acc, purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'admin,debug';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory,silent,input=hidden,remember');
    	$this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory,silent,input=hidden,remember');
    	$this->FLD('productId', 'int', 'caption=Артикул,mandatory,silent,input=hidden,remember');
    	$this->FLD('quantity', 'double(Min=0,smartRound)', 'caption=Количество,mandatory,smartCenter');
    	$this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'after=quantity,silent,mandatory,caption=Разход за,removeAndRefreshForm=allocationBy');
    	$this->FLD('allocationBy', 'enum(no=Няма,value=По стойност,quantity=По количество,weight=По тегло,volume=По обем)', 'caption=Разпределяне,input=none');
    	$this->FLD('containerId', 'key(mvc=doc_Containers)', 'mandatory,caption=Ориджин,silent,input=hidden');
    	
    	$this->setDbIndex('detailClassId,detailRecId');
	}
	
	
	/**
	 * След подготовката на заглавието на формата
	 */
	protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
	{
		// По-хубаво заглавие на формата
		$rec = $data->form->rec;
		if(isset($rec->containerId)){
			$origin = doc_Containers::getDocument($rec->containerId);
			$data->form->title = core_Detail::getEditTitle($origin->getClassId(), $origin->that, $mvc->singleTitle, $rec->id);
		}
	}
	
	
	/**
	 * Връща количеството разпределено за реда в документа
	 *
	 * @param int $allocationId - ид на документа
	 * @param int $originRecId  - кой ред от оригиналния документ отговаря
	 * @param int $id           - ид на записа ако има
	 * @return double $allocatedQuantity - разпределеното досега количество
	 */
	public static function getAllocatedInDocument($detailClassId, $detailRecId, $id = NULL)
	{
		$query = static::getQuery();
		 
		// Сумиране на разпределените количества към реда
		$query->where("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId}");
		if(isset($id)){
			$query->where("#id != {$id}");
		}
		 
		$query->XPR('allocatedQuantity', 'double', 'sum(#quantity)');
		$query->show('allocatedQuantity');
		 
		$allocatedQuantity = $query->fetch()->allocatedQuantity;
		$allocatedQuantity = ($allocatedQuantity) ? $allocatedQuantity : 0;
		 
		return $allocatedQuantity;
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
		$rec = $data->form->rec;
		
		// Какво к-во се очаква да се разпредели
		$maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
		$maxQuantityVerbal = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($maxQuantity);
		$uomId = key(cat_Products::getPacks($rec->productId));
		$shortUom = cat_UoM::getShortName($uomId);
		
		$productName = cat_Products::getHyperlink($rec->productId, TRUE);
		
		$form->info = "<b>{$productName}</b>, ";
		$form->info .= tr("|Общо к-во|* <b>{$maxQuantityVerbal}</b> {$shortUom}<br>");
		
		// Колко има още за разпределяне
		$allocatedQuantity = self::getAllocatedInDocument($rec->detailClassId, $rec->detailRecId, $rec->id);
		$toAllocate = $maxQuantity - $allocatedQuantity;
		$form->setDefault('quantity', $toAllocate);
		
		// Показване на к-то
		if($maxQuantity == 1 && $uomId == cat_UoM::fetchBySinonim('pcs')->id){
			$form->setFieldType('quantity', core_Type::getByName('percent'));
			$allocatedQuantity = cls::get('type_Percent')->toVerbal($allocatedQuantity);
			$form->setField('quantity', "unit=|Разпределено|*: <b>{$allocatedQuantity}</b>");
		} else {
			$allocatedQuantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($allocatedQuantity);
			$form->setField('quantity', "unit=|Разпределено|*: <b>{$allocatedQuantity}</b> {$shortUom}");
		}
		
		// Ако има избрано разходно перо, и то е на покупка/продажба, показва се и полето за разпределяне
		if(isset($rec->expenseItemId)){
			$itemClassId = acc_Items::fetchField($rec->expenseItemId, 'classId');
			if($itemClassId == sales_Sales::getClassId() || $itemClassId == purchase_Purchases::getClassId()){
				$form->setField('allocationBy', 'input');
				$form->setDefault('allocationBy', 'no');
			}
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	protected static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		 
		if($form->isSubmitted()){
	
			// Колко ще бъде разпределено след записа
			$allocatedQuantity = self::getAllocatedInDocument($rec->detailClassId, $rec->detailRecId, $rec->id);
			$allocatedQuantity += $rec->quantity;
			$uomId = key(cat_Products::getPacks($rec->productId));
			
			// Проверка дали ще се разпределя повече от допустимото количество
			$maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
			if($allocatedQuantity > $maxQuantity){
				$maxQuantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($maxQuantity);
				$shortUom = cat_UoM::getShortName($uomId);
				$form->setError('quantity', "Разпределяне над допустимото количество от|* <b>{$maxQuantity}</b> {$shortUom}");
			}
			
			if(!$form->gotErrors()){
				
				// Проверяване дали е въведено допустимо к-во само ако реда не е за 1 брой
				if(!($maxQuantity == 1 && $uomId == cat_UoM::fetchBySinonim('pcs')->id)){
					
					// Проверка дали въведеното к-во е допустимо
					$roundQuantity = cat_UoM::round($rec->quantity, $rec->productId);
					if($roundQuantity == 0){
						$form->setError('quantity', 'Не може да бъде въведено количество, което след закръглянето указано в|* <b>|Артикули|* » |Каталог|* » |Мерки/Опаковки|*</b> |ще стане|* 0');
						return;
					}
						
					if(trim($roundQuantity) != trim($rec->quantity)){
						$form->setWarning('quantity', 'Количеството ще бъде закръглено до указаното в |*<b>|Артикули » Каталог » Мерки/Опаковки|*</b>|');
							
						if(!$form->gotErrors()){
							$rec->quantity = $roundQuantity;
						}
					}
				}
			}
			
			// Ако к-то е достигнато се подсигуряваме, че няма да има 'Запис и нов'
			if(!$form->gotErrors()){
				if($allocatedQuantity >= $maxQuantity){
					$form->cmd = 'save';
				}
			}
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$uomId = key(cat_Products::getPacks($rec->productId));
		
		// Ако к-то е в  1 брой показва се като процент
		$isPercent = FALSE;
		if($uomId == cat_UoM::fetchBySinonim('pcs')->id){
			$maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
			if($maxQuantity == 1){
				$row->quantity = cls::get('type_Percent')->toVerbal($rec->quantity);
				$isPercent = TRUE;
			}
		}
		
		if($isPercent === FALSE){
			$row->uomId = tr(cat_UoM::getShortName($uomId));
		}
		
		// Линк към обекта на перото
		$eItem = acc_Items::getVerbal($rec->expenseItemId, 'titleNum');
		$iRec = acc_Items::fetch($rec->expenseItemId);
		if(method_exists(cls::get($iRec->classId), 'getSingleUrlArray') && !Mode::isReadOnly()){
			$eItem = ht::createLink($eItem, cls::get($iRec->classId)->getSingleUrlArray($iRec->objectId));
		}
		
		// Допълнителна проверка дали артикула е нескладируем, невложим и не ДМА
		$pInfo = cat_Products::getProductInfo($rec->productId);
		$hint = isset($pInfo->meta['fixedAsset']) ? 'Артикулът вече е ДА и не може да бъде разпределян като разход' : (isset($pInfo->meta['canConvert']) ? 'Артикулът вече е вложим и не може да бъде разпределян като разход' : NULL);
		
		$row->expenseItemId = "<b class='quiet'>" . tr("Разход за") . "</b>: {$eItem}";
		if($hint){
			$row->expenseItemId = ht::createHint($row->expenseItemId, $hint, 'warning', FALSE)->getContent();
			$row->expenseItemId = "<span style='color:red !important'>{$row->expenseItemId}</span>";
		}
	}
	
	
	/**
	 * Подготовка на разходи
	 * 
	 * @param stdClass $data
	 */
	private static function prepareAllocatedExpenses(&$data)
	{
		$rec = &$data->rec;
		$data->recs = $data->rows = array();
		
		// Какви разходи са отчетени към реда
		$query = self::getQuery();
		$query->where("#detailClassId = {$rec->detailClassId} AND #detailRecId = {$rec->detailRecId}");
		while($dRec = $query->fetch()){
			$data->recs[$dRec->id] = $dRec;
			$data->rows[$dRec->id] = self::recToVerbal($dRec);
		}
		
		// Можели да се добавя нова разбивка на разходите
		if(self::haveRightFor('add', (object)$rec) && !Mode::isReadOnly()){
			$url = array('acc_CostAllocations', 'add') + (array)$rec;
			$url['ret_url'] = TRUE;
				
			core_Request::setProtected('detailClassId,detailRecId,containerId,productId');
			$data->addBtn = ht::createLink('Отнасяне на разходи', toUrl($url), FALSE, 'title=Отнасяне към разходен обект');
			core_Request::removeProtected('detailClassId,detailRecId,containerId,productId');
		}
	}
	
	
	/**
	 * Рендиране на разпределените разходи към ред
	 * 
	 * @param stdClass $data - данни
	 * @return core_ET $tpl  - рендиран шаблон
	 */
	private static function renderAllocatedExpenses(&$data)
	{
		$tpl = getTplFromFile('acc/tpl/CostAllocation.shtml');
		$clone = $tpl->getBlock('ROW');
		
		foreach ($data->rows as $id => $row){
			core_RowToolbar::createIfNotExists($row->_rowTools);
			$row->tools = $row->_rowTools->renderHtml();
			
			$dTpl = clone $clone;
			$dTpl->placeObject($row);
			$dTpl->removeBlocks();
			$dTpl->append2Master();
		}
		
		if(isset($data->addBtn)){
			$tpl->append($data->addBtn, 'buttons');
		}
		
		
		return $tpl;
	}
	
	
	/**
	 * Връща представянето на разпределените разходи към даден ред от документ за купуване на разходи
	 * 
	 * @param core_Detail $Detail     - Детайл на документ
	 * @param int $id                 - ид на ред от детайл на документ
	 * @param int $containerId        - ид на контейнера на документа
	 * @param int $productId          - ид на артикул
	 * @param int $packagingId        - ид на опаковка/мярка
	 * @param double $quantityInPack  - к-во в опаковка
	 * @return string $res            - направените разходи към реда
	 */
	public static function getAllocatedExpenses(core_Detail $Detail, $id, $containerId, $productId, $packagingId, $quantityInPack)
	{
		$res = '';
		
		// Подготвяне на данните
		$Detail = cls::get($Detail);
		$arr = array('detailClassId' => $Detail->getClassId(), 'detailRecId' => $id, 'containerId' => $containerId, 'productId' => $productId);
		$data = (object)array('rec' => (object)$arr);
		
		// Подготвяне на разпределените разходи
		static::prepareAllocatedExpenses($data);
		
		// Рендиране на разпределените разходи
		$res = static::renderAllocatedExpenses($data)->getContent();
		
		// Връщане на рендираните разпределени разходи
		return $res;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
			
			if(empty($rec->detailClassId) || empty($rec->detailRecId) || empty($rec->containerId) || empty($rec->productId)){
				$requiredRoles = 'no_one';
				return;
			}
			
			// Артикулът трябва да е нескладируем, невложим и не-ДМА
			$pInfo = cat_Products::getProductInfo($rec->productId);
			if(isset($pInfo->meta['canStore']) || isset($pInfo->meta['canConvert']) || isset($pInfo->meta['fixedAsset'])){
				$requiredRoles = 'no_one';
				return;
			}
			
			$origin = doc_Containers::getDocument($rec->containerId);
			
			//... и да е активен
			$state = $origin->fetchField('state');
			
			if($state != 'active' && $state != 'draft'){
				$requiredRoles = 'no_one';
				return;
			}
			
			//... и да е в отворен период
			if(acc_Periods::isClosed($origin->fetchField($origin->valiorFld))){
				$requiredRoles = 'no_one';
				return;
			}
			
			//... и потребителя да има достъп до него
			if(!$origin->haveRightFor('single')){
				$requiredRoles = 'no_one';
				return;
			}
		}
		
		if($action == 'add' && isset($rec)){
			
			// Ако е разпределено очакването, не може да се разпределят разходи
			if(isset($rec->detailClassId) && isset($rec->detailRecId)){
				$maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
				$allocatedByFar = self::getAllocatedInDocument($rec->detailClassId, $rec->detailRecId);
				if($allocatedByFar >= $maxQuantity){
					$requiredRoles = 'no_one';
					return;
				}
			}
		}
	}
}