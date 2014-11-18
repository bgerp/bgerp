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
    var $startCtr = 'techno2_SpecificationDoc';
    
    
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
    		'techno2_SpecificationDoc',
    		'techno2_SpecTplCache',
    		'migrate::copyOldTechnoDocuments2'
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'techno';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.11, 'Производство', 'Технологии2', 'techno2_SpecificationDoc', 'default', "techno, ceo"),
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
    public function copyOldTechnoDocuments2()
    {
    	core_Users::cancelSystemUser();
    	
    	core_Classes::add('techno2_SpecificationDoc');
    	$technoDriverId = cat_GeneralProductDriver::getClassId();
    	$technoDriverServiceId = cat_GeneralServiceDriver::getClassId();
    	$NewClass = cls::get('techno2_SpecificationDoc');
    	
    	$gpQuery = techno_GeneralProducts::getQuery();
    	$gpQuery->where("#state = 'active'");
    	while($oldRec = $gpQuery->fetch()){
    		$meta = arr::make($oldRec->meta, TRUE);
    		$newRec = new stdClass();
    		
    		core_Users::sudo($oldRec->createdBy);
    		foreach (array('state', 'folderId', 'createdOn', 'modifiedOn', 'modifiedBy', 'searchKeywords', 'sharedUsers', 'sharedUsers', 'meta', 'title') as $fld){
    			$newRec->$fld = $oldRec->$fld;
    			unset($oldRec->$fld);
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
    		
    		try{
    			$NewClass->save($newRec, NULL, 'IGNORE');
    		} catch(Exception $e){
    			techno2_SpecificationDoc::log("Проблем с трансфер на спецификация: {$e->getMessage()}");
    		}
    		
    		core_Users::exitSudo();
    	}
    	
    	core_Users::forceSystemUser();
    }
}