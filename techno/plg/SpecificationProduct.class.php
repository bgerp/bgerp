<?php



/**
 * Клас 'techno_plg_SpecificationProduct'
 *
 * Плъгин даващ възможност на даден документ да бъде спецификация
 * и да поражда оферта
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
    	$rec->title = $data->title = $newTitle;
    	
    	// Запис и редирект
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
    private function copyDetails(core_Mvc $mvc, $id, $newId)
    {
    	if(count($mvc->details)){
    		foreach ($mvc->details as $name => $class){
    			$Details = $mvc->{$name};
    			$query = $Details->getQuery();
    			$query->where("#{$Details->masterKey} = {$id}");
    			while($dRec = $query->fetch()){
    				$dRec->{$Details->masterKey} = $newId;
    				unset($dRec->id);
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
    		
    		// Промяна на спецификацията при възстановяване/оттегляне/активиране
    		$rec = $mvc->fetch(($rec->id) ? $rec->id : $rec);
    		techno_Specifications::forceRec($mvc, $rec);
    	}
    }
    
    
	/**
     * След подготовка на туулбара за единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('add') && $data->rec->state == 'active'){
    		$data->toolbar->addBtn("Копие", array($mvc, 'copy', $data->rec->id), 'ef_icon=img/16/page_2_copy.png,title=Копиране на спецификацията,warning=Сигурнили сте че искате да копирате документа ?');
    	}
    	
    	if(sales_Quotations::haveRightFor('add') && $data->rec->state == 'active'){
    		$coverClass = doc_Folders::fetchCoverClassName($data->rec->folderId);
    		if($coverClass != 'doc_UnsortedFolders'){
    			$qId = sales_Quotations::fetchField(("#originId = {$data->rec->containerId} AND #state='draft'"), 'id');
	    		if($qId){
	    			$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'edit', $qId), 'ef_icon=img/16/document_quote.png,title=Промяна на съществуваща оферта');
	    		} else {
	    			$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'add', 'originId' => $data->rec->containerId), 'ef_icon=img/16/document_quote.png,title=Създава оферта за спецификацията');
	    		}
    		}
    	}
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интефейси, които трябва да имат кориците
     */
    function on_AfterGetAllowedFolders($mvc, &$res)
    {
    	$res =  array('doc_ContragentDataIntf', 'techno_SpecificationFolderCoverIntf');
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'activate'){
    		if(!$rec->id){
    			$res = 'no_one';
    		}
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
     * Преди да се подготвят опциите на кориците, ако
     * тя е Продукти, ограничаваме само до тези, които
     * могат да се произвеждат (canManifacture)
     */
    function on_BeforeGetCoverOptions($mvc, &$res, $coverClass)
    {
    	if($coverClass instanceof cat_Products){
    		$res = $coverClass::makeArray4Select(NULL, "#state != 'rejected' AND #meta LIKE '%canManifacture%'");
    	}
    }
}    