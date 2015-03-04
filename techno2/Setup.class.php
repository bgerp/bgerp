<?php



/**
 * Технологии - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'techno2_SpecificationFolders';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Технологии";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
    		'techno2_SpecificationFolders',
    		'techno2_SpecificationDoc',
    		'migrate::copyOldTechnoDocuments8'
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'techno';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.11, 'Производство', 'Технологии', 'techno2_SpecificationFolders', 'default', "techno, ceo"),
        );
    
    
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'techno/tpl/GeneralProductsStyles.css';
   
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('techno_GeneralProductsImages', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '10MB', 'user', 'every_one');

        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Миграция на старите универсални продукти към новите спецификации
     */
    public function copyOldTechnoDocuments8()
    {
    	core_Users::cancelSystemUser();
    	
    	core_Classes::add('techno2_SpecificationDoc');
    	$technoDriverId = cat_GeneralProductDriver::getClassId();
    	$technoDriverServiceId = cat_GeneralServiceDriver::getClassId();
    	$NewClass = cls::get('techno2_SpecificationDoc');
    	
    	$gpQuery = techno_GeneralProducts::getQuery();
    	$gpQuery->where("#state = 'active'");
    	
    	// За всеки универсален продукт, създаваме нова спецификация
    	while($oldRec = $gpQuery->fetch()){
    		$meta = arr::make($oldRec->meta, TRUE);
    		$newRec = new stdClass();
    		
    		core_Users::sudo($oldRec->createdBy);
    		foreach (array('state', 'folderId', 'createdOn', 'modifiedOn', 'modifiedBy', 'searchKeywords', 'sharedUsers', 'sharedUsers', 'meta', 'title') as $fld){
    			$newRec->$fld = $oldRec->$fld;
    		}
    		
    		if(isset($meta['canStore'])){
    			$newRec->innerClass = $technoDriverId;
    		} else {
    			$newRec->innerClass = $technoDriverServiceId;
    		}
    		
    		$info = $oldRec->description;
    		$clone = clone $oldRec;
    		$clone->info = $info;
    		
    		$newRec->innerForm = $clone;
    		$newRec->innerState = $clone;
    		
    		if(doc_Folders::fetchCoverClassName($newRec->folderId) == 'doc_UnsortedFolders'){
    			$newRec->isPublic = 'yes';
    		} else {
    			$newRec->isPublic = 'no';
    		}
    		
    		try{
    			$NewClass->save($newRec, NULL, 'REPLACE');
    		} catch(Exception $e){
    			techno2_SpecificationDoc::log("Проблем с трансфер на спецификация: {$e->getMessage()}");
    		}
    		
    		core_Users::exitSudo();
    	}
    	
    	$this->updateExDocuments();
    	
    	core_Users::forceSystemUser();
    }
    
    
    /**
     * Прехвърля всички връзки в документите и счетоводството от старите спецификации към новите
     */
    public function updateExDocuments()
    {
    	$newClass = techno2_SpecificationDoc::getClassId();
    	$oldClass = techno_Specifications::getClassId();
    	$gpClass = techno_GeneralProducts::getClassId();
    	
    	$docsArr = array('sales_SalesDetails', 'sales_InvoiceDetails', 'store_ShipmentOrderDetails', 'store_ReceiptDetails', 'sales_ServicesDetails', 'purchase_InvoiceDetails', 'purchase_PurchasesDetails', 'purchase_ServicesDetails', 'sales_QuotationsDetails');
    	
    	$nQuery = techno2_SpecificationDoc::getQuery();
    	$nQuery->where("#state = 'active'");
    	while ($rec = $nQuery->fetch()){
    		$oldId = $rec->innerForm->id;
    		
    		try{
    			$specId = techno_Specifications::fetchByDoc($gpClass, $oldId)->id;
    		} catch(Exception $e){
    			continue;
    		}
    		
    		if(is_null($specId)) continue;
    		
    		if($itemRec = acc_Items::fetchItem($oldClass, $specId)){
    			$itemRec->classId = $newClass;
    			$itemRec->objectId = $rec->id;
    			acc_Items::save($itemRec);
    		}
    		
    		foreach ($docsArr as $manName){
    			$dQuery = $manName::getQuery();
    			$dQuery->where("#classId = {$oldClass} AND #productId = {$specId}");
    			while($dRec = $dQuery->fetch()){
    				
    				$dRec->classId = $newClass;
    				$dRec->productId = $rec->id;
    				$manName::save($dRec);
    			}
    		}
    	}
    }
}