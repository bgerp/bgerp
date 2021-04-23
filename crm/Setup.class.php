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
 * Да се показва ли полето Класификация на икономическите дейности (НКИД)
 */
defIfNot('CRM_VISIBLE_NKID', 'none');


/**
 * Използване на регистър VIES за търсене на фирми
 */
defIfNot('CRM_REGISTRY_USE_VIES', 'yes');


/**
 * Използване на Търговския регистър за търсене на фирми
 */
defIfNot('CRM_REGISTRY_USE_BRRA', 'yes');

/**
 * Вид на визитника
 */
defIfNot('CRM_ALPHABET_FILTER', 'standart');


/**
 * "Свързани" фирми
 */
defIfNot('CRM_CONNECTED_COMPANIES', '');


/**
 * Клас 'crm_Setup' -
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
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
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'crm_Companies';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'drdata=0.1, callcenter=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Визитник и управление на контактите';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CRM_VISIBLE_NKID' => array('enum(none=Не показвай, yes=Покажи)', 'caption=Класификация на икономическите дейности->НКИД'),
        'CRM_REGISTRY_USE_BRRA' => array('enum(yes=Включено,ne=Изключено)', 'caption=Извличане и попълване на данни->Търговски регистър'),
        'CRM_REGISTRY_USE_VIES' => array('enum(yes=Включено,ne=Изключено)', 'caption=Извличане и попълване на данни->VIES'),
        'CRM_ALPHABET_FILTER' => array('enum(none=Без,standart=Стандартен,twoRows=Двоен)', 'caption=Вид на азбучника->Избор, customizeBy=powerUser'),
        'CRM_CONNECTED_COMPANIES' => array('keylist(mvc=crm_Companies,select=name)', 'caption="Свързани" фирми'),
        );
    
    
    /**
     * Описание на системните действия
     */
    public $systemActions = array(
        array('title' => 'Ключови думи', 'url' => array('crm_Persons', 'repairKeywords', 'ret_url' => true), 'params' => array('title' => 'Ре-индексиране на визитките')),
        array('title' => 'Промяна на условия', 'url' => array('cond_ConditionsToCustomers', 'update', 'ret_url' => true), 'params' => array('title' => 'Промяна на търговските условия на контрагентите', 'ef_icon' => 'img/16/arrow_refresh.png'))
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Gather_contragent_info',
            'description' => 'Събиране на информация за контрагентите',
            'controller' => 'crm_ext_ContragentInfo',
            'action' => 'GatherInfo',
            'period' => 720,
            'offset' => 70,
            'timeLimit' => 300
        ),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'crm_Groups',
        'crm_Persons',
        'crm_Companies',
        'crm_ext_IdCards',
        'crm_Personalization',
        'crm_ext_CourtReg',
        'crm_Profiles',
        'crm_Locations',
        'crm_Formatter',
        'crm_ext_ContragentInfo',
        'crm_ext_Cards',
        'migrate::updateUics',
        'migrate::updateGroupsCountry2106',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'crm';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.32, 'Указател', 'Визитник', 'crm_Companies', 'default', 'powerUser'),
    );


    /**
     * Менижиране на формата формата за настройките
     *
     * @param core_Form $configForm
     * @return void
     */
    public function manageConfigDescriptionForm(&$configForm)
    {
        $companyOptions = array();
        $companyQuery = crm_Companies::getQuery();
        $groupId = crm_Groups::getIdFromSysId('related');
        $companyQuery->where("LOCATE('|{$groupId}|', #groupList)");
        while($cRec = $companyQuery->fetch()){
            $companyOptions[$cRec->id] = crm_Companies::getRecTitle($cRec, false);
        }

        $configForm->setSuggestions('CRM_CONNECTED_COMPANIES', $companyOptions);
    }


    /**
     * Скрипт за инсталиране
     */
    public function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('pictures', 'Снимки', 'jpg,jpeg,image/jpeg,png', '3MB', 'user', 'every_one');
        
        // Кофа за снимки
        $html .= $Bucket->createBucket('location_Images', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        // Кофа за crm файлове
        $html .= $Bucket->createBucket('crmFiles', 'CRM Файлове', null, '300 MB', 'user', 'user');
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме на плъгина за превръщане на никовете в оцветени линкове
        $html .= $Plugins->forcePlugin('NickToLink', 'crm_ProfilesPlg', 'core_Manager', 'family');
        
        $html .= $Plugins->forcePlugin('Линкове в статусите след логване', 'crm_UsersLoginStatusPlg', 'core_Users', 'private');
        
        $html .= $Plugins->forcePlugin('Персонални настройки на системата', 'crm_PersonalConfigPlg', 'core_ObjectConfiguration', 'private');
        
        // Нагласяване на Cron
        $rec = new stdClass();
        $rec->systemId = 'PersonsToCalendarEvents';
        $rec->description = 'Обновяване на събитията за хората';
        $rec->controller = 'crm_Persons';
        $rec->action = 'UpdateCalendarEvents';
        $rec->period = 24 * 60 * 60;
        $rec->offset = 16;
        $rec->delay = 0;
        $html .= core_Cron::addOnce($rec);

        return $html;
    }


    /**
     * Обновява националните номера
     */
    public function updateUics()
    {
        $Canonized = cls::get('drdata_CanonizedStrings');
        $Canonized->setupMvc();

        // Всички фирми с национални номера
        $Companies = cls::get('crm_Companies');
        $query = $Companies->getQuery();
        $query->where("#uicId IS NOT NULL AND #uicId != ''");
        $query->show('id,uicId');
        $count =  $query->count();
        core_App::setTimeLimit($count * 0.6, false,300);

        // Нормализиране на националния номер според държавата на контрагента
        $save = $update = array();
        while($rec = $query->fetch()){

            // Канонизиране на номера
            $canonized = drdata_CanonizedStrings::canonize($rec->uicId, 'uic', false);

            // Подмяна на нац. номер с канонизирания
            if(!empty($canonized)){
                if(!array_key_exists($rec->uicId, $save)){
                    $save[$rec->uicId] = (object)array('string' => $rec->uicId, 'canonized' => $canonized, 'type' => 'uic');
                }

                $rec->uicId = $canonized;
                $update[$rec->id] = $rec;
            }
        }

        // Канонизация на номерата
        if(countR($save)) {
            $eQuery = $Canonized->getQuery();
            $all = $eQuery->fetchAll();

            $res = arr::syncArrays($save, $all, 'string,type', 'string,canonized');
            if(countR($res['insert'])){
                $Canonized->saveArray($res['insert']);
            }

            if(countR($res['update'])){
                $Canonized->saveArray($res['update'], 'id,string,canonized');
            }
        }

        // Ъпдейт на фирмите
        if(countR($update)) {
            $Companies->saveArray($update, 'id,uicId');
        }
    }


    /**
     * Миграция за добавяне на групи за държави
     */
    public function updateGroupsCountry2106()
    {
        $gIdArr = crm_ContragentGroupsPlg::getGroupsId();

        foreach (array('crm_Companies', 'crm_Persons') as $clsName) {
            $clsInst = cls::get($clsName);
            $query = $clsInst->getQuery();

            $query->show('groupListInput, groupList, country');
            while ($rec = $query->fetch()) {
                if (!$rec->country) {

                    continue;
                }

                $gForAdd = drdata_CountryGroups::getGroupsArr($rec->country);

                foreach ($gForAdd as $id => $gRec) {
                    $gId = $gIdArr[$id];

                    $rec->groupListInput = type_Keylist::addKey($rec->groupListInput, $gId);
                }

                // Вземаме всички въведени от потребителя стойност
                $inputArr = type_Keylist::toArray($rec->groupListInput);

                // Намираме всички свъразани
                $resArr = $clsInst->expandInput($inputArr);

                $rec->groupList = type_Keylist::fromArray($resArr);

                $clsInst->save_($rec, 'groupListInput, groupList');
            }
        }

        crm_Groups::updateGroupsCnt('crm_Companies', 'companiesCnt');
        crm_Groups::updateGroupsCnt('crm_Persons', 'personsCnt');
    }
}
