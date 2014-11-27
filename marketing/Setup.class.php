<?php


/**
 * Имейл от който да се изпрати нотифициращ имейл че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_FROM_EMAIL', '');


/**
 * Имейл на който да се изпрати нотифициращ имейл че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_TO_EMAIL', '');


/**
 * Маркетинг - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   marketing
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class marketing_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'marketing_Inquiries';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Маркетинг и реклама";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'MARKETING_INQUIRE_FROM_EMAIL'  => array('key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Изпращане на запитването по имейл->Имейл \'От\''),
			'MARKETING_INQUIRE_TO_EMAIL'    => array('emails', 'caption=Изпращане на запитването по имейл->Имейл \'Към\''),
	);
	
	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'marketing_Inquiries',
    		'marketing_Inquiries2',
    		'migrate::migrateInquiries2'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'marketing';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Търговия', 'Маркетинг', 'marketing_Inquiries', 'default', "sales, ceo, marketing"),
        );

    
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
    	// Добавяне на кофа за файлове свързани със задаията
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('InquiryBucket', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '10MB', 'user', 'every_one');
        
        return $html;
    }
    
    
    /**
     * Миграция на старите запитвания към новите
     */
    public function migrateInquiries2()
    {
    	set_time_limit(600);
    	core_Classes::add('marketing_Inquiries2');
    	
    	core_Users::cancelSystemUser();
    	$Inq = cls::get('marketing_Inquiries2');
    	
    	$dId = cat_GeneralProductDriver::getClassId();
    	
    	$inqQuery = marketing_Inquiries::getQuery();
    	$inqQuery->where("#state = 'active'");
    	
    	while($oRec = $inqQuery->fetch()){
    		
    		if($oRec->createdBy){
    			core_Users::sudo($oRec->createdBy);
    		} else {
    			
    			// Ако документа е създаден от анонимен, форсираме го
    			core_Mode::push('currentUserRec', NULL);
    		}
    		
    		$nRec = new stdClass();
    		$nRec->innerClass = $dId;
    		
    		if(count($oRec->data)){
    			foreach ($oRec->data as $key => $value){
    				$oRec->$key = $value;
    			}
    		}
    		
    		foreach (array('description', 'state', 'folderId', 'modifiedOn', 'modifiedBy', 'searchKeywords', 'quantity1', 'quantity2', 'quantity3', 'name', 'country', 'email', 'company', 'tel', 'pCode', 'place', 'params', 'ip', 'browser', 'address', 'title', 'createdOn') as $fld){
    			$nRec->$fld = $oRec->$fld;
    		}
    		
    		$oRec->info = $oRec->description;
    		unset($oRec->description, $oRec->innerForm, $oRec->innerState);
    		
    		if(empty($clone->params['uom'])){
    			$nRec->measureId = cat_UoM::fetchBySysId('pcs')->id;
    		} else {
    			$nRec->measureId = cat_UoM::fetchBySinonim($clone->params['uom'])->id;
    		}
    		
    		$oRec->measureId = $nRec->measureId;
    		$clone = clone $oRec;
    		
    		$nRec->innerForm = $clone;
    		$nRec->innerState = $clone;
    		
    		$nRec->migrate = TRUE;
    		$nRec->oldCreatedOn = $nRec->createdOn;
    		
    		try{
    			$Inq->save($nRec);
    		} catch(Exception $e){
    			$Inq->log("Проблем при трансфера на запитване {$oRec->id}");
    		}
    		
    		core_Users::exitSudo();
    	}
    	
    	core_Users::forceSystemUser();
    }
}