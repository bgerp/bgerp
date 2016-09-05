<?php


/**
 * Имейл от който да се изпрати нотифициращ имейл, че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_FROM_EMAIL', '');


/**
 * Имейл на който да се изпрати нотифициращ имейл, че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_TO_EMAIL', '');


/**
 * Маркетинг - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   marketing
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
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
    var $startCtr = 'marketing_Inquiries2';
    
    
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
    		'marketing_Inquiries2',
            'marketing_Bulletins',
            'marketing_BulletinSubscribers',
            'migrate::updateBulletinsRecs5',
    		'migrate::updateInquiries',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'marketing';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Търговия', 'Маркетинг', 'marketing_Inquiries2', 'default', "ceo, marketing"),
        );

    
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
    	// Добавяне на кофа за файлове свързани със задаията
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('InquiryBucket', 'Запитвания', '', '10MB', 'every_one', 'every_one');
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->forcePlugin('Бюлетин за външната част', 'marketing_BulletinPlg', 'cms_page_External', 'private');
        
        return $html;
    }
    
    
    /**
     * Миграция за обновява всички записи, за да се обнови кеша
     */
    static function updateBulletinsRecs5()
    {
        $query = marketing_Bulletins::getQuery();
        while ($rec = $query->fetch()) {
            marketing_Bulletins::save($rec);
        }
    }
    
    
    /**
     * Миграция на запитванията
     */
    public function updateInquiries()
    {
    	if(!marketing_Inquiries2::count()) return;
    	
    	$Inquiries = cls::get('marketing_Inquiries2');
    	$Inquiries->setupMvc();
    	
    	core_App::setTimeLimit(700);
    	
    	$query = $Inquiries->getQuery();
    	$query->FLD('innerForm', "blob(1000000, serialize, compress)", "caption=Филтър,input=none,column=none");
    	$query->orderBy('id', 'ASC');
    	
    	while($rec = $query->fetch()){
    		if(isset($rec->innerForm->info)){
    			$rec->info = $rec->innerForm->info;
    		}
    		
    		if(isset($rec->innerForm->measureId)){
    			$rec->measureId = $rec->innerForm->measureId;
    		}
    		
    		try{
    			$Inquiries->save_($rec);
    		} catch(core_exception_Expect $e){
    			
    		}
    	}
    }
}
