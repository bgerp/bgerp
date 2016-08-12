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
	 * Логика за определяне къде да се пренасочва потребителския интерфейс.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareRetUrl($mvc, $data)
	{
		//bp($data->form->rec, $mvc->haveR);
	}
	
	
	/**
	 * След подготовката на заглавието на формата
	 */
	protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
	{
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
		
		$maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
		$maxQuantityVerbal = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($maxQuantity);
		$uomId = key(cat_Products::getPacks($rec->productId));
		$shortUom = cat_UoM::getShortName($uomId);
		
		$productName = cat_Products::getHyperlink($rec->productId, TRUE);
		
		$form->info = "<b>{$productName}</b>, ";
		$form->info .= tr("|Общо к-во|* <b>{$maxQuantityVerbal}</b> {$shortUom}<br>");
		
		$allocatedQuantity = self::getAllocatedInDocument($rec->detailClassId, $rec->detailRecId, $rec->id);
		$toAllocate = $maxQuantity - $allocatedQuantity;
		$form->setDefault('quantity', $toAllocate);
		
		$allocatedQuantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($allocatedQuantity);
		$form->setField('quantity', "unit=|Разпределено|*: <b>{$allocatedQuantity}</b> {$shortUom}");
		
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
			
			// Проверка дали ще се разпределя повече от допустимото количество
			$maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
			if($allocatedQuantity > $maxQuantity){
				$maxQuantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($maxQuantity);
				$shortUom = cat_UoM::getShortName(key(cat_Products::getPacks($rec->productId)));
				$form->setError('quantity', "Разпределяне над допустимото количество от|* <b>{$maxQuantity}</b> {$shortUom}");
			}
			
			if(!$form->gotErrors()){
				 
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
		$row->uomId = tr(cat_UoM::getShortName($uomId));
		
		$eItem = acc_Items::getVerbal($rec->expenseItemId, 'titleNum');
		$iRec = acc_Items::fetch($rec->expenseItemId);
		if(method_exists(cls::get($iRec->classId), 'getSingleUrlArray') && !Mode::isReadOnly()){
			$eItem = ht::createLink($eItem, cls::get($iRec->classId)->getSingleUrlArray($iRec->objectId));
		}
		
		$pInfo = cat_Products::getProductInfo($rec->productId);
		$hint = isset($pInfo->meta['fixedAsset']) ? 'Артикулът вече е ДА и не може да бъде разпределян като разход' : (isset($pInfo->meta['canConvert']) ? 'Артикулът вече е вложим и не може да бъде разпределян като разход' : NULL);
		
		$row->expenseItemId = "<b class='quiet'>" . tr("Разход за") . "</b>: {$eItem}";
		if($hint){
			$row->expenseItemId = ht::createHint($row->expenseItemId, $hint, 'warning', FALSE)->getContent();
			$row->expenseItemId = "<span style='color:red !important'>{$row->expenseItemId}</span>";
		}
	}
	
	
	private static function prepareAllocatedExpenses(&$data)
	{
		$rec = &$data->rec;
		$data->recs = $data->rows = array();
		
		$query = self::getQuery();
		$query->where("#detailClassId = {$rec->detailClassId} AND #detailRecId = {$rec->detailRecId}");
		while($dRec = $query->fetch()){
			$data->recs[$dRec->id] = $dRec;
			$data->rows[$dRec->id] = self::recToVerbal($dRec);
		}
		
		if(self::haveRightFor('add', (object)$rec) && !Mode::isReadOnly()){
			$url = array('acc_CostAllocations', 'add') + (array)$rec;
			$url['ret_url'] = TRUE;
				
			core_Request::setProtected('detailClassId,detailRecId,containerId,productId');
			$data->addBtn = ht::createLink('Отнасяне на разходи', toUrl($url), FALSE, 'title=Отнасяне към разходен обект');
			core_Request::removeProtected('detailClassId,detailRecId,containerId,productId');
		}
	}
	
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
	
	public static function getAllocatedExpenses($Detail, $id, $containerId, $productId, $packagingId, $quantityInPack)
	{
		$res = '';
		
		$Detail = cls::get($Detail);
		$arr = array('detailClassId' => $Detail->getClassId(), 'detailRecId' => $id, 'containerId' => $containerId, 'productId' => $productId);
		$data = (object)array('rec' => (object)$arr);
		
		static::prepareAllocatedExpenses($data);
		$res = static::renderAllocatedExpenses($data)->getContent();
		
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
	
	
	/**
	 * След подготовка на туклбара на списъчния изглед
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if(haveRole('admin,debug,ceo')){
			$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искатели да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
		}
	}
	
	
	/**
	 * Изчиства записите в балансите
	 */
	public function act_Truncate()
	{
		requireRole('admin,debug,ceo');
			
		// Изчистваме записите от моделите
		self::truncate();
			
		// Записваме, че потребителя е разглеждал този списък
		$this->logWrite("Изтриване на кеша на изгледите на артикула");
	
		return new Redirect(array($this, 'list'), '|Записите са изчистени успешно');
	}
}