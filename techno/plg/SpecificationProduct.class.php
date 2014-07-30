<?php



/**
 * Клас 'techno_plg_SpecificationProduct'
 *
 * Плъгин даващ възможност на даден документ да бъде
 * спецификация и да поражда оферта
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_plg_SpecificationProduct extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        expect(cls::haveInterface('doc_DocumentIntf', $mvc), 'Спецификациите трябва да са документи', get_class($mvc));
        expect(cls::haveInterface('techno_ProductsIntf', $mvc), 'Спецификациите трябва имат интерфейс за технолози', get_class($mvc));
    	if(!$mvc->fields['meta']){
    		$mvc->FLD('meta', 'set(canSell=Продаваем,canBuy=Купуваем,
        						canStore=Складируем,canConvert=Вложим,
        						fixedAsset=Дма,canManifacture=Производим,costs=Разходи)', 'before=sharedUsers,caption=Свойства->Списък,columns=2');
    	}
    	
    	// Добавяне на интерфейс за изпращане по имейл
    	$mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['email_DocumentIntf'], 'email_DocumentIntf');
        
        // Добавяне на плъгин за изпращане по имейл
        $mvc->load('doc_EmailCreatePlg');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	// По подразбиране всички спецификации, са продаваеми и складируеми
    	$data->form->setDefault('meta', 'canSell,canStore');
    }
    
    
	/**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	if($action != 'copy') return;
    	
    	$mvc->requireRightFor('add');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $mvc->fetch($id));
    	expect($rec->state == 'active');
    	$originId = $rec->containerId;
    	
    	// Копието е нов документ(чернова), в същата папка в нов тред
    	$parentId = $rec->id;
    	unset($rec->id, $rec->containerId, $rec->createdOn, $rec->modifiedOn, $rec->createdBy, $rec->modifiedBy);
    	$rec->state = 'draft';
    	$rec->originId = $originId;
    	
    	// Промяна на името на копието
    	$newTitle = $rec->title;
    	if(!str::increment($newTitle)){
    		$newTitle .= " v2";
    	}
    	
    	while($mvc->fetch("#title = '{$newTitle}'")){
    		$newTitle = str::increment($newTitle);
    	}
    	
    	// Запис и редирект
    	$rec->title = $newTitle;
    	$mvc->save($rec);
    	static::copyDetails($mvc, $parentId, $rec->id);
    	
    	return Redirect(array($mvc, 'single', $rec->id), FALSE, 'Спецификацията е успешно копирана');
    }
    
    
    /**
     * След копирането на Master частта, копира и всички детайли
     * @param core_Mvc - модела
     * @param $id - ид на продукта, който ще се копира
     * @param $newId - ид-то на копието на продукта
     */
    private static function copyDetails(core_Mvc $mvc, $id, $newId)
    {
		if (count ($mvc->details)) {
			foreach ($mvc->details as $name => $class) {
				$Details = $mvc->{$name};
				$query = $Details->getQuery();
				$query->where ("#{$Details->masterKey} = {$id}");
				while ($dRec = $query->fetch()) {
					$dRec->{$Details->masterKey} = $newId;
					unset ($dRec->id);
					$Details->save($dRec);
				}
			}
		}
	}
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->state != 'draft'){
    		$rec = $mvc->fetch(($rec->id) ? $rec->id : $rec);
    		if(strpos($rec->meta, 'canSell') !== false){
    			
    			// Промяна на спецификацията при възстановяване/оттегляне/активиране
    			techno_Specifications::forceRec($mvc, $rec);
    		}
    	}
    }
    
    
	/**
     * След подготовка на туулбара за единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	$hasPrice = $mvc->getPriceInfo($rec->id)->price;
    	
    	$coverClass = doc_Folders::fetchCoverClassName($rec->folderId);
    	$contragentFolder = cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    	
    	// Само активиран документ може да се копира
    	if($mvc->haveRightFor('add') && $rec->state == 'active'){
    		$data->toolbar->addBtn("Копие", array($mvc, 'copy', $rec->id), 'ef_icon=img/16/page_2_copy.png,title=Копиране на спецификацията,warning=Сигурнили сте че искате да копирате документа ?');
    	}
    	
    	// Само не оттеглените спецификации, които имат цена и са продаваеми
    	// могат да пораждат оферта директно
    	if(sales_Quotations::haveRightFor('add') && $rec->state != 'rejected' && strpos($rec->meta, 'canSell') !== false){
    		
    		// Ако офертата е в папка на контрагент и може да се изчисли цена
    		if($contragentFolder){
	    		$qId = sales_Quotations::fetchField(("#originId = {$rec->containerId} AND #state='draft'"), 'id');
		    	if($qId){
		    		$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'edit', $qId), 'ef_icon=img/16/document_quote.png,title=Промяна на съществуваща оферта');
		    	} else {
		    		$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'add', 'originId' => $rec->containerId), 'ef_icon=img/16/document_quote.png,title=Създава оферта за спецификацията');
		    	}
    		} 
    	}
    	
    	if($rec->state == 'active'){
    		//@TODO да махна изискването да има дебъг
    		if(haveRole('debug') && $hasPrice && !$contragentFolder){
    			$data->toolbar->addBtn('Задание', array('mp_Jobs', 'add', 'originClass' => $mvc->getClassId(), 'originDocId' => $rec->id), 'ef_icon=img/16/clipboard_text.png,title=Ново задание за производство');
    		}
    	}
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    function on_AfterGetAllowedFolders($mvc, &$res)
    {
    	$res = array('doc_ContragentDataIntf', 'techno_SpecificationFolderCoverIntf');
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'activate' && !$rec->id){
    		$res = 'no_one';
    	}
    }
    
    
	/**
     * Реализация по подразбиране на интерфейсния метод ::canAddToFolder()
     */
    function on_AfterCanAddToFolder($mvc, &$res, $folderId)
    {
        $allowedIntfs = $mvc->getAllowedFolders();
    	if(count($allowedIntfs)){
	    	$cover = doc_Folders::getCover($folderId);
	    	foreach ($allowedIntfs as $intf){
	    		if($cover->haveInterface($intf)){
	    			return $res = TRUE;
	    		}
	    	}
    	}
    	
    	return $res = FALSE;
    }
    
    
    /**
     * Връща стойноства на даден параметър на продукта, ако я има
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function on_AfterRenderJobView($mvc, &$res, $id)
    {
    	if(!$res){
    		$res = tr("Драйвера няма изглед за задание");
    	}
    }
    
    
    /**
     * Връща масив с допълнителни параметри, специфични за технолога. Те ще се използват в заданията
     * 
     * @return array[]   - масив от обекти от вида
     * 		rec->name - име на параметъра
     * 		rec->type - тип в системата
     */
    public function on_AfterGetAdditionalParams($mvc, &$res)
    {
    	if(!$res){
    		$res =  NULL;
    	}
    }
    
    
	/**
     * Добавя към формата на запитването, допълнителни полета
     */
    public function on_AfterFillInquiryForm($mvc, &$res)
    {
    	if(!$res){
    		$res = NULL;
    	}
    }
    
    
    /**
      * Връща параметрите които ще се подават на запитването
      */
    public function on_AfterGetInquiryParams()
    {
    	
    }
    
    
	/**
     * Връща основната мярка, специфична за технолога
     */
    public function on_AfterGetDriverUom($mvc, &$res, $params)
    {
    	if(!$res){
    		return NULL;
    	}
    }
    
    
    /**
     * Рендира допълнителните параметри
     * @param array $data  - масив от обекти от вида
     * 		rec->name  - име на параметъра
     * 		rec->type  - тип в системата
     * 		rec->value - стойност
     * @return core_ET $tpl - шаблона
     */
    public function on_AfterRenderAdditionalParams($mvc, &$res, $id, $data)
    {
    	if(!$res){
    		$res = new ET("");
    	}
    }
    
    
    /**
     * Връща в кои опаковки може да се добавя един продукт
     */
    function on_AfterGetPacks($mvc, &$res, $productId)
    {
    	if(empty($res)){
    		$pInfo = $mvc->getProductInfo($productId);
    		$measureId = cat_UoM::getTitleById($pInfo->productRec->measureId);
    		$res = array('' => $measureId);
    	}
    }
    
    
	/**
     * Връща стойноства на даден параметър на продукта, ако я има
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function on_AfterGetParam($mvc, &$res, $id, $sysId)
    {
    	if(!$res){
    		$res = NULL;
    	}
    }
    
    
    /**
     * Преди да се подготвят опциите на кориците, ако
     * тя е Продукти, ограничаваме само до тези, които
     * могат да се произвеждат (canManifacture)
     */
    function on_BeforeGetCoverOptions($mvc, &$res, $coverClass)
    {
    	if($coverClass instanceof cat_Products){
    		$res = cat_Products::getByProperty('canManifacture');
    	}
    }
    
    
	/**
	* Интерфейсен метод на doc_ContragentDataIntf
	* Връща тялото на имейл по подразбиране
	*/
    static function on_AfterGetDefaultEmailBody($mvc, $res, $id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата спецификация") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Дефолт метод за връщане на тегло
     */
    static function on_AfterGetWeight($mvc, $res, $id, $packagingId)
    {
    	if(!$res){
    		$res = $mvc->getParam($id, 'transportWeight');
    	}
    }
    
    
	/**
     * Дефолт метод за връщане на обем
     */
    static function on_AfterGetVolume($mvc, $res, $id, $packagingId)
    {
    	if(!$res){
    		$res = $mvc->getParam($id, 'transportVolume');
    	}
    }
    
    
	 /**
      * Връща прикачените файлове
      */
     public static function on_AfterGetAttachedFiles($mvc, &$res, $rec)
     {
     	if(!$res){
     		$res = array();
     		foreach ((array)$rec as $name => $value){
     			if($mvc->getFieldType($name) instanceof type_Richtext){
     				$files = fileman_RichTextPlg::getFiles($value);
     				$res = array_merge($res, $files);
     			}
     		}
     	}
     }
     
     
    /**
     * Връща информация за основната опаковка на артикула
     * 
     * @param int $productId - ид на продукт
     * @return stdClass - обект с информация
     * 				->name     - име на опаковката
     * 				->quantity - к-во на продукта в опаковката
     */
    public static function on_AfterGetBasePackInfo($mvc, &$res, $id)
    {
    	if(empty($res)){
    		
    		$res = (object)array('name' => NULL, 'quantity' => 1);
    	}
    }
    
    
    public static function on_AftergetProductTitle($mvc, &$res, $data)
    {
    	
    }
}    