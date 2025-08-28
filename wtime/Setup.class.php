<?php


/**
 *  IP-та на всички наши офиси
 */
defIfNot('WTIME_SITE_IPS', '');


/**
 *  До колко минути назад за залепва с предходно събитие при четене
 */
defIfNot('WTIME_READ_STICK_MIN', '10');


/**
 *  До колко минути назад за залепва с предходно събитие при запис
 */
defIfNot('WTIME_WRITE_STICK_MIN', '20');


/**
 *  Колко време след вход се приема, че служителя все още не излязъл
 */
defIfNot('WTIME_MAX_IN_TIME', 16 * 60 * 60);


/**
 *  Колко минути да няма хит от фирмено ИП служителя, за да се приеме че работи отдалечено
 */
defIfNot('WTIME_EXCLUDE_LOCAL_MIN', '30');


/**
 * Клас 'wtime_OnSiteEntries'
 * Клас-мениджър за записи за вход/изход на място
 *
 * @category  bgerp
 * @package   wtime
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wtime_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'wtime_OnSiteEntries';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Описание на модула
     */
    public $info = 'Отчитане на работното време';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'wtime_OnSiteEntries',
        'wtime_Summary',
    );

    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'wtime_reports_TimeWorked';


    public $configDescription = array(
        'WTIME_SITE_IPS' => array('text(rows=4)', 'caption=IP-та на всички наши офиси'),
        'WTIME_READ_STICK_MIN' => array('int(min=0)', 'caption=До колко минути назад за залепва->С предходно събитие при четене'),
        'WTIME_WRITE_STICK_MIN' => array('int(min=0)', 'caption=До колко минути назад за залепва->С предходно събитие при запис'),
        'WTIME_MAX_IN_TIME' => array('time', array('caption' => 'Колко време след вход се приема че служителя все още не излязъл->Време')),
        'WTIME_EXCLUDE_LOCAL_MIN' => array('int(min=0)', array('caption' => 'Колко време да няма хит от фирмено IP служителя за да се приеме че работи отдалечено->Минути')),
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = array('noTrackonline', 'wtime');


    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.41, 'Счетоводство', 'Работно време', 'wtime_OnSiteEntries', 'default', 'ceo,wtime'),
    );


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Recalc Employees Working Time',
            'description' => 'Преизчисляване на работното време на служители',
            'controller' => 'wtime_Summary',
            'action' => 'Calc',
            'period' => 60,
            'offset' => 1,
            'timeLimit' => 150
        ),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Инсталиране на плъгин за превод на входящата поща
        $html .= core_Plugins::installPlugin('Working time', 'wtime_plugins_AfterLogin', 'core_Users', 'private');

        return $html;
    }
}
