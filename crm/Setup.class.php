<?php



/**
 * Име на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('BGERP_OWN_COMPANY_NAME', 'Моята Фирма ООД');


/**
 * Държавата на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('BGERP_OWN_COMPANY_COUNTRY', 'Bulgaria');


/**
 * ID на нашата фирма
 */
defIfNot('BGERP_OWN_COMPANY_ID', 1);


/**
 * Клас 'crm_Setup' -
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class crm_Setup extends core_ProtoSetup
{
    
    
    /**
     * ID на нашата фирма
     */
    const BGERP_OWN_COMPANY_ID = BGERP_OWN_COMPANY_ID;
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'crm_Companies';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1, callcenter=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Визитник и управление на контактите";
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
        array('title' => 'Ключови думи', 'url' => array ('crm_Persons', 'repairKeywords', 'ret_url' => TRUE), 'params' => array('title' => 'Ре-индексиране на визитките'))
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'crm_Groups',
            'crm_Persons',
            'crm_Companies',
            'crm_ext_IdCards',
            'crm_Personalization',
            'crm_ext_CourtReg',
    		'crm_ext_Employees',
            'crm_Profiles',
            'crm_Locations',
            'crm_Formatter',
            'migrate::movePersonalizationData',
            'migrate::addCountryToCompaniesAndPersons',
            'migrate::updateSettingsKey',
            'migrate::updateGroupFoldersToUnsorted',
            'migrate::updateLocationType',
            'migrate::addCountryIn2LgPersons',
            'migrate::addCountryIn2LgCompanies',
    		'migrate::updateEmployeeCodes',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'crm';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.32, 'Указател', 'Визитник', 'crm_Companies', 'default', "crm, user"),
        );

             
    /**
     * Скрипт за инсталиране
     */
    function install()
    {
        
        $html = parent::install();
                
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('pictures', 'Снимки', 'jpg,jpeg,image/jpeg,png', '3MB', 'user', 'every_one');
        
         // Кофа за снимки
        $html .= $Bucket->createBucket('location_Images', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        // Кофа за crm файлове
        $html .= $Bucket->createBucket('crmFiles', 'CRM Файлове', NULL, '300 MB', 'user', 'user');
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме на плъгина за превръщане на никовете в оцветени линкове
        $html .= $Plugins->forcePlugin('NickToLink', 'crm_ProfilesPlg', 'core_Manager', 'family');
        
        $html .= $Plugins->forcePlugin('Линкове в статусите след логване', 'crm_UsersLoginStatusPlg', 'core_Users', 'private');
        
        $html .= $Plugins->forcePlugin('Персонални настройки на системата', 'crm_PersonalConfigPlg', 'core_ObjectConfiguration', 'private');

        // Нагласяване на Cron        
        $rec = new stdClass();
        $rec->systemId    = 'PersonsToCalendarEvents';
        $rec->description = "Обновяване на събитията за хората";
        $rec->controller  = 'crm_Persons';
        $rec->action      = 'UpdateCalendarEvents';
        $rec->period      = 24*60*60;
        $rec->offset      = 16;
        $rec->delay       = 0;
        $html .= core_Cron::addOnce($rec);
        
        return $html;
    }
    
    
    /**
     * Деинсталиране
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Фунцкия за миграция
     * Премества персонализационните данни за потребителя от crm_Personalization в core_Users
     */
    static function movePersonalizationData()
    {
        $query = crm_Personalization::getQuery();
        $query->where('1=1');
        while($rec = $query->fetch()) {
            try {
                
                $nArr = array();
                
                $userId = crm_Profiles::fetchField($rec->profileId, 'userId');
                
                $oldConfigData = core_Users::fetchField($userId, 'configData');
                
                if ($rec->inbox) {
                    if (!isset($oldConfigData['EMAIL_DEFAULT_SENT_INBOX'])) {
                        $nArr['EMAIL_DEFAULT_SENT_INBOX'] = $rec->inbox;
                    }
                }
                
                if ($rec->header) {
                    if (!isset($oldConfigData['EMAIL_OUTGOING_HEADER_TEXT'])) {
                        $nArr['EMAIL_OUTGOING_HEADER_TEXT'] = $rec->header;
                    }
                }
                
                if ($rec->signature) {
                    if (!isset($oldConfigData['EMAIL_OUTGOING_FOOTER_TEXT'])) {
                        $nArr['EMAIL_OUTGOING_FOOTER_TEXT'] = $rec->signature;
                    }
                }
                
                if ($rec->logo) {
                    if (!isset($oldConfigData['BGERP_COMPANY_LOGO'])) {
                        $nArr['BGERP_COMPANY_LOGO'] = $rec->logo;
                    }
                }
                
                if ($rec->logoEn) {
                    if (!isset($oldConfigData['BGERP_COMPANY_LOGO_EN'])) {
                        $nArr['BGERP_COMPANY_LOGO_EN'] = $rec->logoEn;
                    }
                }
                
                if ($nArr) {
                    $nArr = (array)$nArr + (array)$oldConfigData;
                    $nRec = new stdClass();
                    $nRec->id = $userId;
                    $nRec->configData = $nArr;
                    core_Users::save($nRec, 'configData');
                }
            } catch (core_exception_Expect $e) { }
        }
    }
    
    
    /**
     * Миграция, за добавяне на държава към потребителите и лицата
     */
    public static function addCountryToCompaniesAndPersons()
    {
        try {
            $conf = core_Packs::getConfig('crm');
            $coutryId = drdata_Countries::fetchField("#commonName = '" . $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id');
            
            if (!$coutryId) return ;
            
            foreach (array('crm_Persons', 'crm_Companies') as $clsName) {
                $query = $clsName::getQuery();
                $query->where("#country IS NULL");
                $query->orWhere("#country = ''");
                while ($rec = $query->fetch()) {
                    $rec->country = $coutryId;
                    $clsName::save($rec, 'country');
                }
            }
        } catch (core_exception_Expect $e) {
            
            return ;
        }
    }
    
    
    /**
     * Обновява ключовете
     */
    public static function updateSettingsKey()
    {
        $newKey = crm_Profiles::getSettingsKey();
        $query = core_Settings::getQuery();
        $query->where("#key LIKE 'core_Users::%'");
        while ($rec = $query->fetch()) {
            
            try {
                $cRec = clone $rec;
                
                core_Settings::delete($cRec->id);
                
                $cRec->key = $newKey;
                core_Settings::save($cRec, NULL, 'IGNORE');
            } catch(ErrorException $e) {
                
                continue;
            }
        }
    }
    
    
    /**
     * Променя типа на папките от група в проект
     */
    public static function updateGroupFoldersToUnsorted()
    {
        try {
            $groupClassId = core_Classes::getId('crm_Groups');
        } catch (core_exception_Expect $e) {
            
            return ;
        }
        
        if (!$groupClassId) return ;
        
        try {
            $unsortedClassId = core_Classes::getId('doc_UnsortedFolders');
        } catch (core_exception_Expect $e) {
            
            return ;
        }
        
        $Unsorted = cls::get('doc_UnsortedFolders');
        $Unsorted->autoCreateFolder = NULL;
        
        $dQuery = doc_Folders::getQuery();
        $dQuery->where("#coverClass = {$groupClassId}");
        while ($rec = $dQuery->fetch()) {
            
            $unsortedRec = clone $rec;
            unset($unsortedRec->id);
            unset($unsortedRec->title);
            unset($unsortedRec->state);
            unset($unsortedRec->searchKeywords);
            unset($unsortedRec->exState);
            
            $unsortedRec->name = $rec->title;
            $i = 0;
            while($Unsorted->fetch(array("#name = '[#1#]'", $unsortedRec->name))) {
                $unsortedRec->name .= '_' . ++$i;
            }
            $rec->coverId = $Unsorted->save($unsortedRec);
            
            $rec->coverClass = $unsortedClassId;
            doc_Folders::save($rec);
            
            $unsortedRec->folderId = $rec->id;
            $Unsorted->save($unsortedRec, 'folderId');
        }
    }


    /**
     * Миграция за обновяване типа на локациите
     */
    public static function updateLocationType()
    {
        $types = array( 'correspondence' => 'За кореспонденция',
                        'headquoter' => 'Главна квартира',
                        'shipping' => 'За получаване на пратки',
                        'office' => 'Офис',
                        'shop' => 'Магазин',
                        'storage' => 'Склад',
                        'factory' => 'Фабрика',
                        'other' => 'Друг');

        $query = crm_Locations::getQuery();
        while($rec = $query->fetch()) {
            if($type = $types[$rec->type]) {
                $rec->type = $type;
                crm_Locations::save($rec, 'type');
                $upd++;
            }
        }

        return "Обновени са {$upd} типа на локации";
    }

	/**
     * Добавя държавата на два езика в лицата
     */
    public static function addCountryIn2LgPersons()
    {
        $countryId = drdata_Countries::getIdByName('България');
        
        $mvcInst = cls::get('crm_Persons');
        $query = $mvcInst->getQuery();
                    
        Mode::push('text', 'plain');
        Mode::push('htmlEntity', 'none');
        
        while($rec = $query->fetchAndCache()) {
            // Прескачаме България, защото в ключовите думи ще е по-един и същи начин
            if ($rec->country == $countryId || !$rec->country) continue;
            $rec->searchKeywords = $mvcInst->getSearchKeywords($rec);
            $mvcInst->save_($rec, 'searchKeywords');
        }

        Mode::pop('htmlEntity');
        Mode::pop('text');
    }


	/**
     * Добавя държавата на два езика в лицата
     */
    public static function addCountryIn2LgCompanies()
    {
        $countryId = drdata_Countries::getIdByName('България');
        
        $mvcInst = cls::get('crm_Companies');
        $query = $mvcInst->getQuery();
                    
        Mode::push('text', 'plain');
        Mode::push('htmlEntity', 'none');
        
        while($rec = $query->fetchAndCache()) {
            // Прескачаме България, защото в ключовите думи ще е по-един и същи начин
            if ($rec->country == $countryId || !$rec->country) continue;
            $rec->searchKeywords = $mvcInst->getSearchKeywords($rec);
            $mvcInst->save_($rec, 'searchKeywords');
        }

        Mode::pop('htmlEntity');
        Mode::pop('text');
    }


    /**
     * Ъпдейт на служителите без код
     */
	function updateEmployeeCodes()
	{
		$Employees = cls::get('crm_ext_Employees');
		$Employees->setupMvc();
		
		$query = crm_ext_Employees::getQuery();
		$query->where("#code IS NULL");
		while($rec = $query->fetch()){
			$rec->code = crm_ext_Employees::getDefaultCode($rec->personId);
			crm_ext_Employees::save($rec, 'code');
		}
	}
}
