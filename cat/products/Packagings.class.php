<?php

/**
 * Клас 'cat_products_Packagings'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_products_Packagings extends doc_Detail
{
    
    
	/**
	 * Име на поле от модела, външен ключ към мастър записа
	 */
	var $masterKey = 'productId';
	
	
    /**
     * Заглавие
     */
    var $title = 'Опаковки';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Опаковка';
 
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'code=EAN, packagingId, quantity=К-во, netWeight=, tareWeight=, weight=Тегло, 
        sizeWidth=, sizeHeight=, sizeDepth=, dimention=Габарити, 
        eanCode=,tools=Пулт';
    
    
    /**
     * Поле за редактиране
     */
    var $rowToolsField = 'tools';

    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper, plg_RowTools, plg_SaveAndNew, plg_AlignDecimals2';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canDelete = 'ceo,cat';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden, silent');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'input,caption=Опаковка,mandatory,width=7em');
        $this->FLD('quantity', 'double(Min=0)', 'input,caption=Количество,mandatory');
        $this->FLD('isBase', 'enum(yes=Да,no=Не)', 'caption=Основна,mandatory,maxRadio=2');
        $this->FLD('netWeight', 'cat_type_Weight', 'caption=Тегло->Нето');
        $this->FLD('tareWeight', 'cat_type_Weight', 'caption=Тегло->Тара');
        $this->FLD('sizeWidth', 'cat_type_Size', 'caption=Габарит->Ширина');
        $this->FLD('sizeHeight', 'cat_type_Size', 'caption=Габарит->Височина');
        $this->FLD('sizeDepth', 'cat_type_Size', 'caption=Габарит->Дълбочина');
        $this->FLD('eanCode', 'gs1_TypeEan', 'caption=Код->EAN');
        
        $this->setDbUnique('productId,packagingId');
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		$baseMeasureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
    		
    		if($baseMeasureId == $rec->packagingId){
    			if($rec->quantity != 1){
    				$form->setError('quantity', 'Количеството не може да е различно от единица за избраната мярка/опаковка');
    			}
    		}
    		
    		if($rec->eanCode) {
    				
    			// Проверяваме дали има продукт с такъв код (като изключим текущия)
	    		$check = $mvc->Master->getByCode($rec->eanCode);
	    		if($check && ($check->productId != $rec->productId)
	    			|| ($check->productId == $rec->productId && $check->packagingId != $rec->packagingId)) {
	    			$form->setError('eanCode', 'Има вече продукт с такъв код!');
			    }
    		}
    			
    		// Ако за този продукт има друга основна опаковка, тя става не основна
    		if($rec->isBase == 'yes' && $packRec = static::fetch("#productId = {$rec->productId} AND #isBase = 'yes'")){
    			$packRec->isBase = 'no';
    			static::save($packRec);
    		}
    		
    		$roundQuantity = cat_UoM::round($rec->quantity, $rec->productId);
    		if($roundQuantity == 0){
    			$form->setError('packQuantity', 'Не може да бъде въведено количество, което след закръглянето указано в|* <b>|Артикули|* » |Каталог|* » |Мерки/Опаковки|*</b> |ще стане|* 0');
    			return;
    		}
    		
    		if($roundQuantity != $rec->quantity){
    			$form->setWarning('quantity', 'Количеството ще бъде закръглено до указаното в |*<b>|Артикули » Каталог » Мерки|*</b>|');
    			$rec->quantity = $roundQuantity;
    		}
    		
    		// Закръгляме к-то, така че да е в границите на допустимото от мярката
    		$rec->quantity = cat_UoM::round($rec->quantity, $rec->productId);
    	}
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
    	if($requiredRoles == 'no_one') return;
    	
        if($action == 'add' && isset($rec->productId)) {
        	if (!count($mvc::getRemainingOptions($rec->productId))) {
                $requiredRoles = 'no_one';
            } else {
            	$productInfo = $mvc->Master->getProductInfo($rec->productId);
            	if(empty($productInfo->meta['canStore'])){
            		$requiredRoles = 'no_one';
            	}
            } 
        }
        
        if(($action == 'add' ||  $action == 'delete') && isset($rec->productId)) {
        	$masterState = $mvc->Master->fetchField($rec->productId, 'state');
        	if($masterState != 'active' && $masterState != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
        
        // Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира опаковките
        if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec) && $requiredRoles != 'no_one'){
        	if(!cat_Products::haveRightFor('single', $rec->productId)){
        		$requiredRoles = 'no_one';
        	}
        }
        
        // Ако опаковката вече е използвана не може да се изтрива
        if($action == 'delete' && isset($rec)){
        	if(self::isUsed($rec->productId, $rec->packagingId)){
        		$requiredRoles = 'no_one';
        	}
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->toolbar->removeBtn('*');
        
        if ($mvc->haveRightFor('add', (object)array('productId' => $data->masterId)) && count($mvc::getRemainingOptions($data->masterId) > 0)) {
        	$data->addUrl = array(
                $mvc,
                'add',
                'productId' => $data->masterId,
                'ret_url' => getCurrentUrl() + array('#'=>get_class($mvc))
            );
        }
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    public static function on_AfterPrepareListFields($mvc, $data)
    {
        $data->query->orderBy('#id');
        
        if(isset($data->masterId)){
        	$measureId = cat_Products::getProductInfo($data->masterId)->productRec->measureId;
        	$shortMeasure = cat_UoM::getShortName($measureId);
        	
        	$data->listFields['quantity'] .= "|* ({$shortMeasure})";
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if(!(count($mvc::getRemainingOptions($data->form->rec->productId)) - 1)){
    		$data->form->toolbar->removeBtn('saveAndNew');
    	}
    }
    
    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     * @return array $options - опциите
     */
    public static function getRemainingOptions($productId, $id = NULL)
    {
        // Извличаме мерките и опаковките
    	$uomArr = cat_UoM::getUomOptions();
        $packArr = cat_UoM::getPackagingOptions();
        
        // Отсяваме тези, които вече са избрани за артикула
        $query = self::getQuery();
        if($id) {
            $query->where("#id != {$id}");
        }

        while($rec = $query->fetch("#productId = $productId")) {
           unset($uomArr[$rec->packagingId]);
           unset($packArr[$rec->packagingId]);
        }

        // Групираме опциите, ако има такива
        $options = array();
        if(count($uomArr)){
        	$options = array('u' => (object)array('group' => TRUE, 'title' => tr('Мерки'))) + $uomArr;
        }
        
        if(count($packArr)){
        	$options += array('p' => (object)array('group' => TRUE, 'title' => tr('Опаковки'))) + $packArr;
        }
        
        // Връщаме намерените опции
        return $options;
    }

    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
    	$options = self::getRemainingOptions($form->rec->productId, $form->rec->id);
    	
        if (empty($options)) {
        	
            // Няма повече недефинирани опаковки
            redirect(getRetUrl(), FALSE, 'Всички налични мерки/опаковки за артикула са вече избрани');
        }
		
    	if(!$form->rec->id){
        	$options = array('' => '') + $options;
        }
        
        $form->setDefault('isBase', 'no');
        $form->setOptions('packagingId', $options);
        
        $pInfo = cat_Products::getProductInfo($form->rec->productId);
        $unit = cat_UoM::getShortName($pInfo->productRec->measureId);
        $form->setField('quantity', "unit={$unit}");
        
        // Ако редактираме, но опаковката е използвана не може да се променя
        if(isset($form->rec->id)){
        	if(self::isUsed($form->rec->productId, $form->rec->packagingId)){
        		$form->setReadOnly('packagingId');
        	}
        }
    }
    
   
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if($rec->sizeWidth == 0) {
    		$row->sizeWidth = '-';
    	}
    	
    	if($rec->sizeHeight == 0) {
    		$row->sizeHeight = '-';
    	}
    	
    	if($rec->sizeDepth == 0) {
    		$row->sizeDepth = '-';
    	}
    	
    	$row->dimention = "{$row->sizeWidth} x {$row->sizeHeight} x {$row->sizeDepth}";
    	
    	if($rec->eanCode){
    		$row->code = $row->eanCode;
    	}
    	
    	if($rec->netWeight){
    		$row->weight = tr("|Нето|*: ") . $row->netWeight . "<br>";
    	}
    	
    	if($rec->tareWeight){
    		$row->weight .= tr("|Тара|*: {$row->tareWeight}");
    	}
    	
    	if($rec->isBase == 'yes'){
    		$row->packagingId = "<b>" . $row->packagingId . "</b>";
    	}
    }

    
    /**
     * След рендиране на детайла
     */
    public static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        $wrapTpl = getTplFromFile('cat/tpl/PackigingDetail.shtml');
        $title = tr('|Опаковки|* / |Мерки|*');
        if(cat_UoM::haveRightFor('list')){
        	$title = ht::createLink($title, array('cat_UoM', 'list', 'type' => 'packaging'));
        }
        $wrapTpl->append($title, 'TITLE');
        
        if ($data->addUrl) {
        	$addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, FALSE, 'title=Добавяне на нова опаковка/мярка');
        	$tpl->append($addBtn, 'TITLE');
        }
        
        $wrapTpl->append($tpl, 'CONTENT');
        
        $tpl = $wrapTpl;
    }
    
    
    /**
     * Подготвя опаковките на артикула
     * 
     * @param stdClass $data
     */
    public static function preparePackagings($data)
    {
    	// Ако мастъра не е складируем, няма смисъл да показваме опаковките му
    	$productInfo = $data->masterMvc->getProductInfo($data->masterId);
    	if(empty($productInfo->meta['canStore'])){
    		$data->hide = TRUE;
    		return;
    	}
    	
    	static::prepareDetail($data);
    }
    
    
    /**
     * Подготвя опаковките на артикула
     * 
     * @param stdClass $data
     */
    public function renderPackagings($data)
    {
    	if($data->hide === TRUE) return;
    	
        return static::renderDetail($data);
    }
    
    
    /**
     * Връща опаковката ако има такава
     * 
     * @param int $productId - ид на продукта
     * @param int $packagingId - ид на опаковката
     * @return stdClass
     */
    public static function getPack($productId, $packagingId)
    {
        return self::fetch("#productId = '{$productId}' AND #packagingId = '{$packagingId}'");
    }
    
    
    /**
     * Дали в бизнес документите е използван артикула с посочената опаковка
     * 
     * @param int $productId - ид на артикул
     * @param int $uomId - мярка
     * @return boolean
     */
    public static function isUsed($productId, $uomId = NULL)
    {
    	// Ако няма мярка, това е основната на артикула
    	if(!$uomId){
    		$pInfo = cat_Products::getProductInfo($productId);
    		$uomId = $pInfo->productRec->measureId;
    	}
    	
    	// Детайли в които ще проверяваме
    	$details = array('sales_SalesDetails', 
    					 'purchase_PurchasesDetails', 
    					 'store_ShipmentOrderDetails', 
    			         'store_ReceiptDetails', 
    			         'sales_QuotationsDetails', 
    			         'sales_InvoiceDetails', 
    			         'purchase_InvoiceDetails', 
    			         'planning_DirectProductNoteDetails', 
    			         'planning_ProductionNoteDetails', 
    			         'planning_ConsumptionNoteDetails', 
    			         'cat_BomDetails', 
    			         'sales_ProformaDetails', 
    			         'sales_ServicesDetails', 
    			         'purchase_ServicesDetails', 
    			         'store_ConsignmentProtocolDetailsReceived', 
    			         'store_ConsignmentProtocolDetailsSend');
    	
    	// За всеки от изброените документи проверяваме дали е избран артикула с мярката
    	$isUsed = FALSE;
    	foreach ($details as $Detail){
    		if($Detail == 'cat_BomDetails'){
    			if($rec = $Detail::fetch("#resourceId = {$productId} AND #packagingId = '{$uomId}'")){
    				$isUsed = TRUE;
    				break;
    			}
    		} else {
    			if($rec = $Detail::fetch("#productId = {$productId} AND #packagingId = '{$uomId}'")){
    				$isUsed = TRUE;
    				break;
    			}
    		}
    	}
    	
    	// Връщаме резултат
    	return $isUsed;
    }
}
