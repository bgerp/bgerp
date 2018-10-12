<?php


/**
 * Време под което чакащите задачи ще се преместят над останалите в портала
 */
defIfNot('CAL_WAITING_SHOW_TOP_TIME', '86400');


/**
 * Типове събития, които да се показват в календара
 */
defIfNot('CAL_SHOW_HOLIDAY_TYPE', '');


/**
 * Клас 'cal_Setup' - Инаталиране на пакета "Календар"
 *
 *
 * @category  bgerp
 * @package   cal
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cal_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'cal_Calendar';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Календар за задачи, събития, напомняния и празници';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'cal_Calendar',
        'cal_Tasks',
        'cal_Holidays',
        'cal_Reminders',
        'cal_ReminderSnoozes',
        'cal_TaskConditions',
        'cal_LinkedPostponed',
        'migrate::repairSerchKeywords',
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'cal_TaskType, cal_Progresses';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CAL_WAITING_SHOW_TOP_TIME' => array('time(suggestions=12 часа|1 ден|2 дена)', 'caption=Време под което чакащите задачи ще се преместят над останалите в портала->Време'),
        'CAL_SHOW_HOLIDAY_TYPE' => array('set', 'caption=Типове събития|*&#44; |*които да се показват в календара->Избор, customizeBy=powerUser, optionsFunc=cal_Setup::getHolidayTypeOptions, autohide'),
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'user';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.33, 'Указател', 'Календар', 'cal_Calendar', 'default', 'powerUser, admin'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'StartReminders',
            'description' => 'Известяване за стартирани напомняния',
            'controller' => 'cal_Reminders',
            'action' => 'SendNotifications',
            'period' => 1,
            'offset' => 0,
        ),
        
        array(
            'systemId' => 'UpdateRemindersToCal',
            'description' => 'Обновяване на напомнянията в календара',
            'controller' => 'cal_Reminders',
            'action' => 'UpdateCalendarEvents',
            'period' => 90,
            'offset' => 0,
        )
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на напомнянията
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('calReminders', 'Прикачени файлове в напомнянията', null, '104857600', 'user', 'user');
        
        return $html;
    }
    
    
    /**
     * Деинсталиране
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    public function reCalcNextStart()
    {
        $query = cal_Reminders::getQuery();
        $next12months = dt::addMonths(12, dt::today());
        $now = dt::now();
        $query->where("#state = 'active' AND (#nextStartTime <= '{$now}' OR  #nextStartTime IS NULL OR #nextStartTime >= '{$next12months}') AND #notifySent = 'no'");
        
        $class = cls::get('cal_Reminders');
        while ($rec = $query->fetch()) {
            $rec->nextStartTime = $class->calcNextStartTime($rec);
            
            // Ако изчисленото ново време, не е по-голямо от сега или от началната дата,
            // то продължаваме да го търсим
            while (dt::mysql2timestamp($rec->nextStartTime) < dt::mysql2timestamp(dt::now())) {
                $rec->timeStart = $rec->nextStartTime;
                $rec->nextStartTime = $class->calcNextStartTime($rec);
            }
            
            cal_Reminders::save($rec, 'nextStartTime');
        }
    }
    
    
    /**
     * Връща възможните опции за избор на тип празници, които да се показват
     *
     * @return array
     */
    public static function getHolidayTypeOptions()
    {
        $type = 'holidayTypeOptions';
        $handler = 'holidaysOptions';
        $keepMinutes = 10000;
        $depends = 'cal_Calendar';
        
        $resArr = core_Cache::get($type, $handler, $keepMinutes, $depends);
        
        if ($resArr) {
            
            return $resArr;
        }
        
        $query = cal_Calendar::getQuery();
        
        $query->XPR('typeLen', 'int', 'CHAR_LENGTH(#type)');
        $query->orderBy('typeLen', 'DESC');
        
        $query->orderBy('type', 'ASC');
        
        $query->groupBy('type');
        
        $res = array();
        
        while ($rec = $query->fetch()) {
            if (!$rec->type) {
                continue;
            }
            
            $tVerbal = '';
            
            if (strlen($rec->type) == 2) {
                $tVerbal = drdata_Countries::getCountryName($rec->type);
            }
            
            if (!$tVerbal) {
                switch ($rec->type) {
                    case 'international':
                        $tVerbal = tr('Международни');
                        break;
                    case 'alarm_clock':
                        $tVerbal = tr('Напомняния');
                        break;
                    case 'non-working':
                        $tVerbal = tr('Почивни дни');
                        break;
                    case 'birthday':
                        $tVerbal = tr('Рождени дни');
                        break;
                    case 'end-date':
                        $tVerbal = tr('Краен срок');
                        break;
                    case 'orthodox':
                        $tVerbal = tr('Християнски');
                        break;
                    case 'holiday':
                        $tVerbal = tr('Празници');
                        break;
                    case 'sick':
                        $tVerbal = tr('Болнични');
                        break;
                    case 'leaves':
                        $tVerbal = tr('Отпуски');
                        break;
                    case 'working-travel':
                        $tVerbal = tr('Командировки');
                        break;
                    case 'workday':
                        $tVerbal = tr('Отработвания');
                        break;
                    case 'muslim':
                        $tVerbal = tr('Мюсюлмански');
                        break;
                    case 'task':
                        $tVerbal = tr('Задачи');
                        break;
                    default:
                        $tVerbal = $rec->type;
                        
                        wp($tVerbal);
                    break;
                }
            }
            
            $res[$rec->type] = $tVerbal;
        }
        
        core_Cache::set($type, $handler, $res, $keepMinutes, $depends);
        
        return $res;
    }
    
    
    /**
     * Миграция за регенериране на ключовите думи
     */
    public static function repairSerchKeywords()
    {
        $callOn = dt::addSecs(1200);
        core_CallOnTime::setCall('plg_Search', 'repairSerchKeywords', 'cal_Tasks', $callOn);
    }
}
