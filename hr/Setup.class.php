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
    public $info = 'Човешки ресурси';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'HR_EC_MIN' => array('int(min=0)', 'caption=Диапазон за номериране на трудовите договори->Долна граница'),
        'HR_EC_MAX' => array('int(min=0)', 'caption=Диапазон за номериране на трудовите договори->Горна граница'),
        'HR_EMAIL_TO_PERSON' => array('key(mvc=crm_Persons,select=name, allowEmpty, where=#state !\\= \\\'rejected\\\')', 'caption=Изпращане на имейл към->Лице'),
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
        'hr_Bonuses',
        'hr_Deductions',
        'hr_FormCv',
        'hr_WorkPreff',
        'hr_WorkPreffDetails',
        'hr_ScheduleDetails',
        'hr_Schedules',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('hrSickdays'),
        array('hrLeaves'),
        array('hrTrips'),
        array('hr', 'hrSickdays, hrLeaves, hrTrips'),
        array('hrMaster', 'hr'),
        array('hrAll'),
        array('hrAllGlobal', 'hrAll'),
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
    public $defClasses = 'hr_reports_LeaveDaysRep, hr_reports_IndicatorsRep, hr_reports_AbsencesPerEmployee';
    
    
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
}
