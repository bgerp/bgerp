<?php


/**
 * Начален номер на фактурите
 */
defIfNot('HR_EC_MIN', '1');


/**
 * Краен номер на фактурите
 */
defIfNot('HR_EC_MAX', '10000');


/**
 * Краен номер на фактурите
 */
defIfNot('HR_EMAIL_TO_PERSON', '');

/**
 * Ip-та
 */
defIfNot('HR_COMPANIES_IP', '');

/**
 * разрешените дни за работа от вкъщи
 */
defIfNot('HR_DAYS_IN_HOMEOFFICE', '2');


/**
 * Начало на нощна смяна на текущия ден
 */
defIfNot('HR_NIGHT_SHIFT_CUR_DAY_START', '22:00');


/**
 * Край на нощна смяна на следващия ден
 */
defIfNot('HR_NIGHT_SHIFT_NEXT_DAY_END', '06:00');


/**
 * class hr_Setup
 *
 * Инсталиране/Деинсталиране на човешки ресурси
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class hr_Setup extends core_ProtoSetup
{
    /**
     * Колко често да се обновяват индикаторите
     */
    const INDICATORS_UPDATE_PERIOD = 60;
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'hr_EmployeeContracts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Управление на човешките ресурси';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'HR_EC_MIN' => array('int(min=0)', 'caption=Диапазон за номериране на трудовите договори->Долна граница'),
        'HR_EC_MAX' => array('int(min=0)', 'caption=Диапазон за номериране на трудовите договори->Горна граница'),
        'HR_EMAIL_TO_PERSON' => array('key(mvc=crm_Persons,select=name, allowEmpty, where=#state !\\= \\\'rejected\\\')', 'caption=Изпращане на имейл към->Лице'),
        'HR_COMPANIES_IP' => array('text(rows=4)', 'caption=Ip-та на фирмата->IP'),
        'HR_DAYS_IN_HOMEOFFICE' => array('int(min=0)', 'caption=Разрешените дни за работа от вкъщи->Дни,unit=&nbsp;|(за един месец)|*,customizeBy=hrMaster|ceo'),
        'HR_NIGHT_SHIFT_CUR_DAY_START' => array('hour', 'caption=Часове за нощна смяна->Начало: Днес'),
        'HR_NIGHT_SHIFT_NEXT_DAY_END' => array('hour', 'caption=Часове за нощна смяна->Край: Утре'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'hr_Departments',
        'hr_Positions',
        'hr_ContractTypes',
        'hr_EmployeeContracts',
        'hr_IndicatorNames',
        'hr_Indicators',
        'hr_Payroll',
        'hr_Leaves',
        'hr_Sickdays',
        'hr_Trips',
        'hr_HomeOffice',
        'hr_Bonuses',
        'hr_Deductions',
        'hr_FormCv',
        'hr_WorkPreff',
        'hr_WorkPreffDetails',
        'hr_ScheduleDetails',
        'hr_Schedules',
        'hr_IndicatorFormulas',
        'hr_Shifts',
        'migrate::changeAlternatePersonField',
        'migrate::updateTrips2548',
        'migrate::changeReasonId2605',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('hrSickdays'),
        array('hrLeaves'),
        array('hrTrips'),
        array('hrHomeOffice'),
        array('hr', 'hrSickdays, hrLeaves, hrTrips, hrHomeOffice'),
        array('hrMaster', 'hr'),
        array('hrAll'),
        array('hrAllGlobal', 'hrAll'),
        array('hrSchedules'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Update indicators',
            'description' => 'Обновяване на индикаторите за заплатите',
            'controller' => 'hr_Indicators',
            'action' => 'update',
            'period' => self::INDICATORS_UPDATE_PERIOD,
            'offset' => 7,
            'timeLimit' => 200
        ),
        
        array(
            'systemId' => 'collectDaysType',
            'description' => 'Събиране на информацията за отсъствията в профила',
            'controller' => 'hr_EmployeeContracts',
            'action' => 'SetPersonDayType',
            'period' => 100,
            'offset' => 0,
            'timeLimit' => 200
        ));
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.31, 'Счетоводство', 'Персонал', 'hr_Leaves', 'default', 'ceo, hrLeaves, admin'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'hr_reports_LeaveDaysRep, hr_reports_IndicatorsRep, hr_reports_AbsencesPerEmployee,
                          hr_reports_TimeToWorkWithTheSystem, hr_WorkingCycleSens, hr_reports_WorkFromHomeAndAbsence';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('humanResources', 'Прикачени файлове в човешки ресурси', null, '1GB', 'user', 'powerUser');
        
        return $html;
    }


    /**
     * Миграция за преминаване от alternatePerson към alternatePersons
     */
    public static function changeAlternatePersonField()
    {
        $clsArr = array('hr_Leaves', 'hr_Sickdays', 'hr_Trips');
        foreach ($clsArr as $clsName) {
            $cls = cls::get($clsName);
            $cQuery = $cls->getQuery();
            $cQuery->where("#alternatePersons != ''");
            $cQuery->where("#alternatePersons IS NOT NULL");
            while ($cRec = $cQuery->fetch()) {
                $cRec->alternatePersons = type_Keylist::addKey(array(), $cRec->alternatePersons);
                $cls->save_($cRec, 'alternatePersons');
            }
        }
    }


    /**
     * Обновяване на командировките
     */
    public function updateTrips2548()
    {
        $Trips = cls::get('hr_Trips');
        $Trips->setupMvc();

        $currencyIdCol = str::phpToMysqlName('currencyId');
        $amountRoadCol = str::phpToMysqlName('amountRoad');
        $amountDailyCol = str::phpToMysqlName('amountDaily');
        $amountHouseCol = str::phpToMysqlName('amountHouse');
        $tbl = $Trips->dbTableName;

        $query = "UPDATE `{$tbl}` SET `{$currencyIdCol}` = 'BGN' WHERE (`{$amountRoadCol}` IS NOT NULL OR `{$amountDailyCol}` IS NOT NULL OR `{$amountHouseCol}` IS NOT NULL)";
        $Trips->db->query($query);
    }
    
    
    /**
     * Миграция за промяна на кодовете за причина на болничен лист
     */
    public static function changeReasonId2605()
    {
        $Sickdays = cls::get('hr_Sickdays');
        
        $query = $Sickdays->getQuery();
        $query->where("#createdOn <= '2025-12-31 23:59:59'");

        while ($rec = $query->fetch()) {
           
            if($rec->reason === null) { 
               
                continue; 
            }

            $rec->reason = '';
 
            $Sickdays->save_($rec, 'reason');
        }
    }
}
