<?php


/**
 * Роля за основен екип
 */
defIfNot('BGERP_ROLE_HEADQUARTER', 'Headquarter');


/**
 * Кой пакет да използваме за генериране на графики ?
 */
defIfNot('DOC_CHART_ADAPTER', 'googlecharts_Adapter');


/**
 * Кой пакет да използваме за генериране на PDF от HTML ?
 */
defIfNot('BGERP_PDF_GENERATOR', 'webkittopdf_Converter');


/**
 * Начално време на нотифициране за незавършени действия с документи
 */
defIfNot('DOC_NOTIFY_FOR_INCOMPLETE_FROM', '7200');


/**
 * Крайно време на нотифициране за незавършени действия с бизнес документи
 */
defIfNot('DOC_NOTIFY_FOR_INCOMPLETE_TO', '3600');


/**
 * Крайно време на нотифициране за незавършени действия с бизнес документи
 */
defIfNot('DOC_NOTIFY_FOR_INCOMPLETE_BUSINESS_DOC', 2678400);

/**
 * Колко папки от последно отворените да се показват при търсене
 */
defIfNot('DOC_SEARCH_FOLDER_CNT', 5);


/**
 * Колко колко документа максимално да се показват
 */
defIfNot('DOC_SEARCH_LIMIT', 1000);


/**
 * Време на отклонения за поправка на документ (в секунди)
 * Докумените създадени преди това време ще се проверяват за поправка
 */
defIfNot('DOC_REPAIR_DELAY', 120);


/**
 * Дали да се проверяват всички документи за поправка
 */
defIfNot('DOC_REPAIR_ALL', 'yes');


/**
 * Задължително показване на документи -> В началото на нишката
 */
defIfNot('DOC_SHOW_DOCUMENTS_BEGIN', 3);


/**
 * Нотификация за добавен документ в нишка
 */
defIfNot('DOC_NOTIFY_FOR_NEW_DOC', 'default');


/**
 * Известяване на споделените потребители на папка
 */
defIfNot('DOC_NOTIFY_FOLDERS_SHARED_USERS', 'default');


/**
 * Нотификация за създадени чакащи документи
 */
defIfNot('DOC_NOTIFY_PENDING_DOC', 'default');


/**
 * Известяване при нов документ->Задължително
 */
defIfNot('DOC_NOTIFY_NEW_DOC_TYPE', '');


/**
 * Известяване при нов документ->Никога
 */
defIfNot('DOC_STOP_NOTIFY_NEW_DOC_TYPE', '');


/**
 * Задължително показване на документи -> В края на нишката
 */
defIfNot('DOC_SHOW_DOCUMENTS_END', 3);


/**
 * Задължително показване на документи -> По-нови от
 */
defIfNot('DOC_SHOW_DOCUMENTS_LAST_ON', 259200); // 3 дни


/**
 * След колко символа да не се показва текста
 */
defIfNot('DOC_HIDE_TEXT_AFTER_LENGTH', 20000);


/**
 * Колко секунди в кеша максимално да живеят документите
 */
defIfNot('DOC_CACHE_LIFETIME', 5*60);


/**
 * Стрингове, които да се замества с точка при повторение
 */
defIfNot('DOC_STRING_FOR_REDUCE', 'За,Отн,Относно,回复,转发,SV,VS,VS,VL,RE,FW,FRW,TR,AW,WG,ΑΠ,ΣΧΕΤ,ΠΡΘ,R,RIF,I,SV,FS,SV,VB,RE,RV,RES,ENC,Odp,PD,YNT,İLT');


/**
 * Потребители, които ще се нотифицират за отворени теми в папки на оттеглени потребители
 * По-подразбиране са всички администратори
 */
defIfNot('DOC_NOTIFY_FOR_OPEN_IN_REJECTED_USERS', '');


/**
 * След колко време да се изтриват оттеглените нишки
 */
defIfNot('DOC_DELETE_REJECTED_THREADS_PERIOD', core_DateTime::SECONDS_IN_MONTH);


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с DOC
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'doc_Folders';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Документи и папки";
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
        array('title' => 'Ключови думи', 'url' => array ('doc_Containers', 'repairKeywords', 'ret_url' => TRUE), 'params' => array('title' => 'Ре-индексиране на документите')),
        array('title' => 'Поправки', 'url' => array('doc_Containers', 'repair', 'ret_url' => TRUE), 'params' => array('title' => 'Поправка на развалени документи'))
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
        // Кой пакет да използваме за генериране на PDF от HTML ?
        'BGERP_PDF_GENERATOR' => array ('class(interface=doc_ConvertToPdfIntf,select=title)', 'mandatory, caption=Кой пакет да се използва за генериране на PDF?->Пакет'),
        'DOC_CHART_ADAPTER' => array ('class(interface=doc_chartAdapterIntf,select=title, allowEmpty)', 'caption=Кой пакет да се използва за показване на графики?->Пакет, placeholder=Автоматично'),
        'DOC_NOTIFY_FOR_INCOMPLETE_FROM' => array ('time', 'caption=Период за откриване на незавършени действия с документи->Начало,unit=преди проверката'),
        'DOC_NOTIFY_FOR_INCOMPLETE_TO' => array ('time', 'caption=Период за откриване на незавършени действия с документи->Край,unit=преди проверката'),
    	'DOC_NOTIFY_FOR_INCOMPLETE_BUSINESS_DOC' => array ('time', 'caption=Период за откриване на неконтирани бизнес документи->Край,unit=преди проверката'),
    	
        'DOC_REPAIR_ALL' => array ('enum(yes=Да (бавно), no=Не)', 'caption=Дали да се проверяват всички документи за поправка->Избор'),
        'DOC_SEARCH_FOLDER_CNT' => array ('int(Min=0)', 'caption=Колко папки от последно отворените да се показват при търсене->Брой'),
        'DOC_SEARCH_LIMIT' => array ('int(Min=0)', 'caption=Колко документ/нишки да се показват при търсене->Брой'),

        'DOC_NOTIFY_FOR_NEW_DOC' => array ('enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Нотификация за добавен документ в нишка->Избор, customizeBy=powerUser'),
        'DOC_NOTIFY_NEW_DOC_TYPE' => array ('keylist(mvc=core_Classes, select=title)', 'caption=Известяване при нов документ->Задължително, customizeBy=powerUser, optionsFunc=doc_Setup::getAllDocClassOptions'),
        'DOC_STOP_NOTIFY_NEW_DOC_TYPE' => array ('keylist(mvc=core_Classes, select=title)', 'caption=Известяване при нов документ->Никога, customizeBy=powerUser, optionsFunc=doc_Setup::getAllDocClassOptions'),
        'DOC_NOTIFY_FOLDERS_SHARED_USERS' => array ('enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известяване на споделените потребители на папка->Избор, customizeBy=powerUser'),
        'DOC_NOTIFY_PENDING_DOC' => array ('enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Нотификация за създадени чакащи документи->Избор, customizeBy=powerUser'),
        
        'DOC_SHOW_DOCUMENTS_BEGIN' => array ('int(Min=0)', 'caption=Задължително показване на документи в нишка->В началото, customizeBy=user'),
        'DOC_SHOW_DOCUMENTS_END' => array ('int(Min=0)', 'caption=Задължително показване на документи в нишка->В края, customizeBy=user'),
        'DOC_SHOW_DOCUMENTS_LAST_ON' => array ('time(suggestions=1 ден|3 дни|5 дни|1 седмица)', 'caption=Задължително показване на документи в нишка->По-нови от, customizeBy=user'),
        'DOC_HIDE_TEXT_AFTER_LENGTH' => array ('int(min=0)', 'caption=Брой символи над които текста ще е скрит->Брой, customizeBy=user'),
        'DOC_CACHE_LIFETIME' => array("time(suggestions=0 мин.|2 мин.|3 мин.|4 мин.|5 мин.|6 мин.|7 мин.|8 мин.|9 мин.)", "caption=Кеширане на документите->Време"),
        'DOC_NOTIFY_FOR_OPEN_IN_REJECTED_USERS' => array("userList", "caption=Нотификация за отворени теми в папки на оттеглени потребители->Потребители"),
        'DOC_DELETE_REJECTED_THREADS_PERIOD'  => array('time(suggestions=15 дни|1 месец|6 месеца|1 година)', 'caption=След колко време да се изтриват оттеглените нишки->Време'),
    );

    
    // Инсталиране на мениджърите
    var $managers = array(
        'migrate::addPartnerRole1',
        'doc_UnsortedFolders',
        'doc_Folders',
        'doc_Threads',
        'doc_Containers',
        'doc_Folders',
        'doc_Comments',
        'doc_Notes',
        'doc_PdfCreator',
        'doc_ThreadUsers',
        'doc_Files',
    	'doc_TplManager',
    	'doc_HiddenContainers',
    	'doc_DocumentCache',
    	'doc_Likes',
    	'doc_ExpensesSummary',
    	'doc_Prototypes',
    	'doc_UsedInDocs',
    	'doc_View',
        'migrate::repairBrokenFolderId',
        'migrate::repairLikeThread',
        'migrate::repairFoldersKeywords',
    	'migrate::migratePending1',
        'migrate::showFiles',
        'migrate::addCountryIn2LgFolders2',
        'migrate::addFirstDocClassAndId'
    );
	
    
    /**
     * Нагласяне на крон
     */
    var $cronSettings = array(
            array(
                    'systemId' => doc_Threads::DELETE_SYSTEM_ID,
                    'description' => 'Изтриване на оттеглени и документи нишки',
                    'controller' => 'doc_Threads',
                    'action' => 'DeleteThread',
                    'period' => 5,
                    'timeLimit' => 120,
            ),
            array(
                    'systemId' => 'deleteOldObject',
                    'description' => 'Изтриване на остарялите информации за обектите в документ',
                    'controller' => 'doc_UsedInDocs',
                    'action' => 'deleteOldObject',
                    'period' => 1440,
                    'offset' => 66,
                    'timeLimit' => 120,
            )
    );
	
    
    /**
     * Дефинирани класове, които имат интерфейси
    */
    var $defClasses = 'doc_reports_Docs,doc_reports_SearchInFolder';
        
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {   
        $html .= core_Roles::addOnce('powerUser', NULL, 'system');

        // Добавяне на ролите за Ранг
        $rangRoles = array(
                        
            // Изпълнителен член на екип. Достъпни са му само папките,
            // които са споделени или на които е собственик
            'executive',  
            
            // Старши член на екип. Достъпни са му всички общи и всички екипни папки, 
            // в допълнение към тези, на които е собственик или са му споделени
            'officer',
            
            // Ръководител на екип. Достъп до всички папки на екипа, без тези на 'ceo'
            'manager',   
            
            // Pъководител на организацията. Достъпни са му всички папки и документите в тях
            'ceo',       
        );
        
        foreach($rangRoles as $role) {
            $inherit = trim('powerUser,' . $lastRole, ',');
            $lastRole = $role;
            $html .= core_Roles::addOnce($role, $inherit, 'rang');
        }
        
        // Роля за външен член на екип. Достъпни са му само папките, 
        // които са споделени или на които е собственик
        $html .= core_Roles::addOnce('partner', NULL, 'rang');
        
        $html = parent::install();

        // Ако няма нито една роля за екип, добавяме екип за главна квартира
        $newTeam = FALSE;
        
        if(!core_Roles::fetch("#type = 'team'")) {
            $html .= core_Roles::addOnce(BGERP_ROLE_HEADQUARTER, NULL, 'team');
            $newTeam = TRUE;
        }
        
        // Ако няма потребител с роля 'ceo', добавяме я към всички администратори
        if(!count(core_Users::getByRole('ceo'))) {
            
            $admins = core_Users::getByRole('admin');
            
            if(count($admins)) {
                foreach($admins as $userId) {
                    $uTitle = core_Users::getTitleById($userId);
                    core_Users::addRole($userId, 'ceo');
                    $html .= "<li style='color:green'>На потребителя <b>{$uTitle}</b> e добавен ранг <b>ceo</b></li>";
                    
                    if($newTeam) {
                        core_Users::addRole($userId, BGERP_ROLE_HEADQUARTER);
                        $html .= "<li class=\"green\">Потребителя <b>{$uTitle}</b> e добавен в екипа <b>Headquarter</b></li>";
                    }
                }
            }
        }
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за работа с документи от системата
        // Замества handle' ите на документите с линк към документа
        $html .= $Plugins->installPlugin('Документи в RichEdit', 'doc_RichTextPlg', 'type_Richtext', 'private');
        
        // Закачане на плъгина за прехвърляне на собственотст на системни папки към core_Users
        $html .= $Plugins->installPlugin('Прехвърляне на собственост на папки', 'doc_plg_TransferOwnership', 'core_Users', 'private');
        
        // Замества абсолютните линкове с титлата на документа
        $html .= $Plugins->installPlugin('Вътрешни линкове в RichText', 'bgerp_plg_InternalLinkReplacement', 'type_Richtext', 'private');
        
        // Променя линка за сваляне на файла
        $html .= $Plugins->installPlugin('Линкове на файлове след изпращане', 'bgerp_plg_File', 'fileman_Files', 'private');
        
        // Променя линка към картинките в plain режим
        $html .= $Plugins->installPlugin('FancyBox линкове', 'bgerp_plg_Fancybox', 'fancybox_Fancybox', 'private');
        
        // Плъгин за работа с файлове в документите
        $html .= $Plugins->installPlugin('Файлове в документи', 'doc_FilesPlg', 'fileman_Files', 'private');
        
        // Добавяме елемент в менюто
        $html .= bgerp_Menu::addOnce(1.22, 'Документи', 'Всички', 'doc_Folders', 'default', "user");
        
        return $html;
    }
    
    /**
     * Роли за достъп до модула
     */
    var $roles = 'currency';
              
        
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * 
     * @param type_Keylist $type
     * @param array $otherParams
     * 
     * @return array
     */
    public static function getAllDocClassOptions($type, $otherParams = array())
    {
        
        return core_Classes::getOptionsByInterface('doc_DocumentIntf', 'title');
    }
    
    
    /**
     * Поправка на развалените folderId в doc_Containers
     * 
     * @return integer
     */
    public static function repairBrokenFolderId()
    {
        $tQuery = doc_Threads::getQuery();
        $tQuery->where("#createdOn > '2016-03-09 09:00:00'");
        $tQuery->EXT('cFolderId', 'doc_Containers', 'externalName=folderId');
        $tQuery->EXT('cThreadId', 'doc_Containers', 'externalName=threadId, externalKey=firstContainerId');
        
        $tQuery->where("#cFolderId != #folderId");
        
        $cnt = 0;
        
        while ($tRec = $tQuery->fetch()) {
            $cQuery = doc_Containers::getQuery();
            
            if (!$tRec->id || !$tRec->cFolderId) continue;
            
            $cQuery->where("#threadId = '{$tRec->id}'");
            $cQuery->where("#folderId = '{$tRec->cFolderId}'");
            
            while ($cRec = $cQuery->fetch()) {
                $before = $cRec->folderId;
                $cRec->folderId = $tRec->folderId;
                
                doc_Containers::logInfo("Променено 'folderId' от {$before} на {$tRec->folderId}", $cRec->id);
                
                doc_Containers::save($cRec, 'folderId');
                $cnt++;
            }
        }
        
        return $cnt;
    }
    
    
    /**
     * Добавяне на id на нишките в харесванията - за бързодействие
     */
    public static function repairLikeThread()
    {
        $query = doc_Likes::getQuery();
        $query->where("#threadId IS NULL OR #threadId = ''");
        
        while ($rec = $query->fetch()) {
            try {
                $rec->threadId = doc_Containers::fetchField($rec->containerId, 'threadId');
                
                doc_Likes::save($rec, 'threadId');
            } catch (ErrorException $e) {
                
                continue;
            }
        }
    }
    
    
    /**
     * Регенерира на ключовите думи на папките
     */
    public static function repairFoldersKeywords()
    {
        $query = doc_Folders::getQuery();
        
        while ($rec = $query->fetch()) {
            try {
                doc_Folders::save($rec, 'searchKeywords');
            } catch (ErrorException $e) {
                
                continue;
            }
        }
    }
    
    
    /**
     * Миграция към новото чакащо състояние
     */
    public function migratePending1()
    {
    	$arr = array('email_Outgoings', 'email_SendOnTime', 'blast_Emails', 'blast_EmailSend', 'cal_Tasks', 'planning_Tasks', 'pos_Receipts');
    	if(core_Packs::isInstalled('pallet')){
    		$arr[] = 'pallet_Movements';
    		$arr[] = 'pallet_Pallets';
    	}
    	
    	try{
    		foreach ($arr as $Cls){
    			$Cls = cls::get($Cls);
    			
    			$db = new core_Db();
    			if(!$db->tableExists($Cls->dbTableName)) return;
    			$Cls->setupMvc();
    
    			$query = $Cls->getQuery();
    			$query->where("#state = 'pending'");
    			$query->show('state');
    			while($rec = $query->fetch()){
    				$rec->state = 'waiting';
    				$Cls->save_($rec, 'state');
    			}
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }


    public function addPartnerRole1()
    {
        // Определяме най-високата роля за ранг и изтриваме другите
        // Ако потребителя има contractor, buyer или collabolator - задаваме му роля `partner`
        // Почистваме несъществуващите роли и експандваме за полето `roles`
        // Записваме двете полета за роли

        // Изтриваме ролите contractor, buyer и collabolator

        $uQuery = core_Users::getQuery();

        $rangs = array();
        $rangs[] = core_Roles::fetchByName('ceo');
        $rangs[] = core_Roles::fetchByName('manager');
        $rangs[] = core_Roles::fetchByName('officer');
        $rangs[] = core_Roles::fetchByName('executive');
        $rangs[] = $contractorR = core_Roles::fetchByName('contractor');
        $rangs[] = $buyerR = core_Roles::fetchByName('buyer');
        $rangs[] = $collaboratorR = core_Roles::fetchByName('collaborator');
      
        $roleTypes = core_Roles::getGroupedOptions();
        $allowedRolesForPartners = $roleTypes['rang'] + $roleTypes['external'];
        $allowedRolesForInsiders = $roleTypes['rang'] + $roleTypes['job'] + $roleTypes['team'] + $roleTypes['system'] + $roleTypes['position']; 

        if(!$contractorR) {

            return "<li>Миграцията addPartnerRole не е необходима</li>";
        }

        $partnerR = core_Roles::fetchByName('partner');
        
        expect($partnerR);

        $uMvc = cls::get('core_Users');

        // Минаваме по всички съществуващи потребители
        while($uRec = $uQuery->fetch()) {
            
            // Определяме най-голямата рола за партньор
            $kRoles = keylist::toArray($uRec->rolesInput);
            $rang = NULL;
            foreach($rangs as $r) {
                if(isset($kRoles[$r]) && !$rang) {
                    $rang = $r;
                }
                unset($kRoles[$r]);
            }

            // Конвертираме потребителите сбез роля за ранг или със стара роля за парньор към новата роля `partner`
            if(!$rang || ($rang == $contractorR) || ($rang == $buyerR) || ($rang == $collaboratorR)) {
                $rang = $partnerR;
            }

            // Задаваме най-голямата определена роля за ранг
            $kRoles[$rang] = $rang;

            // Премахваме несъществуващите роли
            foreach($kRoles as $roleId) {
                if(!core_Roles::fetchById($roleId)) {
                    unset($kRoles[$roleId]);
                }
            }

            // Филтрираме допустимите роли според ранга
            if($rang == $partnerR) {
                $allowed = $allowedRolesForPartners;
            } else {
                $allowed = $allowedRolesForInsiders;
            }
            

            // филтрираме само позволените роли за съответния ранг
            foreach($kRoles as $r) {
                if(!isset($allowed[$r])) {
                    unset($kRoles[$r]);
                }
            }

            $uRec->rolesInput = keylist::fromArray($kRoles);
            $uRec->roles = keylist::fromArray(core_Roles::expand($kRoles));

            $uMvc->save_($uRec, 'rolesInput,roles');
        }

        // Премахваме стартите роли за контрактор
        core_Roles::removeRoles(array($contractorR, $buyerR, $collaboratorR));
    }
    
    
    /**
     * Миграция, за показване/скирване на файловете в документите
     */
    public function showFiles()
    {
        $callOn = dt::addSecs(120);
        core_CallOnTime::setCall('doc_Setup', 'migrateShowFiles', NULL, $callOn);
    }
    
    
    /**
     * Постепенна миграция, която се вика от showFiles и се самонавива
     */
    public static function callback_migrateShowFiles()
    {
        core_App::setTimeLimit(100);
        $query = doc_Files::getQuery();
        $query->where("#show IS NULL");
        $query->where("#containerId IS NOT NULL");
        $query->where("#containerId != ''");
        
        $query->orderBy('id', 'DESC');
        
        $cnt = $query->count();
        
        $query->limit(100);
        $query->groupBy("containerId");
        $query->show('containerId');
        
        if ($cnt && !core_CallOnTime::fetch("#className = 'doc_Setup' AND #methodName = 'migrateShowFiles' AND #state = 'draft'", '*', FALSE)) {
            $callOn = dt::addSecs(120);
            core_CallOnTime::setCall('doc_Setup', 'migrateShowFiles', NULL, $callOn);
        } elseif (!$cnt) {
            doc_Files::logDebug("Няма повече файлове за миграция в документите");
            
            return ;
        }
        
        doc_Files::logDebug("Файлове за миграция в документите - " . $cnt);
        
        while ($rec = $query->fetch()) {
            doc_Files::recalcFiles($rec->containerId);
        }
    }


	/**
     * Добавя държавата на два езика в папките
     */
    public static function addCountryIn2LgFolders2()
    {
        try {
            $companiesId = core_Classes::getId('crm_Companies');
            $personsId = core_Classes::getId('crm_Persons');
        } catch (core_exception_Expect $e) {
            
            return ;
        }

        $mvcInst = cls::get('doc_Folders');
        $query = $mvcInst->getQuery();
                    
        Mode::push('text', 'plain');
        Mode::push('htmlEntity', 'none');
        
        while($rec = $query->fetchAndCache()) {
            
            if ($rec->coverClass != $companiesId && $rec->coverClass != $personsId)  continue;
            
            if (strpos($rec->searchKeywords, 'bulgaria')) continue;
            
            $rec->searchKeywords = $mvcInst->getSearchKeywords($rec);
            $mvcInst->save_($rec, 'searchKeywords');
        }
        
        Mode::pop('htmlEntity');
        Mode::pop('text');
    }
    
    
    /**
     * Миграция за попълване на firstDocClass и firstDocId в doc_Threads
     */
    public static function addFirstDocClassAndId()
    {
        $Threads = cls::get('doc_Threads');
        $query = $Threads->getQuery();
        $query->where("#firstDocClass IS NULL");
        $query->orWhere("#firstDocId IS NULL");
        
        $query->EXT('docId', 'doc_Containers', 'externalName=docId,externalKey=firstContainerId');
        $query->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=firstContainerId');
        
        $query->orderBy('id', 'DESC');
        
        while ($rec = $query->fetch()) {
            if (!$rec->firstContainerId) continue;
            
            $rec->firstDocClass = $rec->docClass;
            $rec->firstDocId = $rec->docId;
            
            $Threads->save_($rec, 'firstDocClass, firstDocId');
        }
    }
    
    
    /**
     * Миграция за попълване на началните настройки за опциите за нотифициране
     * за "Известяване при нов документ"
     * 
     * Задължително - Изходящ имейл
     * Никога - Напомняне
     */
    public static function addDefaultNotifyOptions()
    {
        // Вземаме конфига
        $conf = core_Packs::getConfig('doc');
        
        $data = array();
        
        if (!$conf->_data['DOC_NOTIFY_NEW_DOC_TYPE']) {
            $emailId = core_Classes::getId('email_Incomings');
            if ($emailId) {
                $data['DOC_NOTIFY_NEW_DOC_TYPE'] = "|{$emailId}|";
            }
        }
        
        // Ако няма запис в модела
        if (!$conf->_data['DOC_STOP_NOTIFY_NEW_DOC_TYPE']) {
            $reminderId = core_Classes::getId('cal_Reminders');
            if ($reminderId) {
                $data['DOC_STOP_NOTIFY_NEW_DOC_TYPE'] = "|{$reminderId}|";
            }
        }
        
        if (!empty($data)) {
            core_Packs::setConfig('doc', $data);
        }
    }
    
    
    /**
     * Миграция за попълване на visibleForPartners в нишките от първия документ
     */
    public static function threadsVisibleForPartners()
    {
        $threads = cls::get('doc_Threads');
        $tQuery = $threads->getQuery();
        
        $tQuery->where('#visibleForPartners IS NULL');
        
        $tQuery->EXT('cVisible', 'doc_Containers', 'externalName=visibleForPartners,externalKey=firstContainerId');
        $tQuery->EXT('cState', 'doc_Containers', 'externalName=state,externalKey=firstContainerId');
        $tQuery->EXT('cCreatedBy', 'doc_Containers', 'externalName=createdBy,externalKey=firstContainerId');
        
        $tQuery->orderBy('modifiedOn', 'DESC');
        
        while ($tRec = $tQuery->fetch()) {
            if ($tRec->cVisible === 'yes') {
                $tRec->visibleForPartners = 'yes';
            } else {
                $tRec->visibleForPartners = 'no';
            }
            
            // Ако е оттеглен документ|чернова и не е създаден от прартньори
            if ($tRec->visibleForPartners == 'yes') {
                if (($tRec->cState === 'rejected') || ($tRec->cState === 'draft')) {
                    if (!$tRec->cCreatedBy || ($tRec->cCreatedBy < 1) || !core_Users::haveRole('partner', $tRec->cCreatedBy)) {
                        $tRec->visibleForPartners = 'no';
                    }
                }
            }
            
            $threads->save_($tRec, 'visibleForPartners');
        }
    }
    
    
    /**
     * Зареждане на данни
     */
    function loadSetupData($itr = '')
    {
        
        return $this->callMigrate('threadsVisibleForPartners', 'doc');
        return $this->callMigrate('addDefaultNotifyOptions', 'doc');
    }
}
