<?php 


/**
 * Оторизации от и към външни услуги
 *
 *
 * @category  bgerp
 * @package   remote
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 20165 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class remote_Authorizations extends embed_Manager
{
    
    /**
     * Заглавие
     */
    public $title = 'Оторизации на отдалечени системи';
    

    /**
     * Интерфейс на драйверите
     */
    public $driverInterface = 'remote_ServiceDriverIntf';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оторизация';

    
    /**
     * Разглеждане на листов изглед
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'crm_Wrapper, plg_Created, plg_State2, plg_RowTools2';
    

    /**
     * Текущ таб
     */
    public $currentTab = 'Профили';
       
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
        

    /**
     * Кой може да пише?
     */
    public $canWrite = 'powerUser';


    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'powerUser';


    /**
     * Колонки в листовия изглед
     */
    public $listFields = 'userId,url,auth=Оторизация,state,createdOn,createdBy';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('userId', 'user', 'caption=Потребител,mandatory,smartCenter');
        $this->FLD('url', 'url', 'caption=Услуга,mandatory,smartCenter');
        $this->FLD('data', 'blob(serialize,compress)', 'caption=Състояние,column=none,single=none,input=none');
        $this->FNC('auth', 'varchar', 'caption=Оторизация,smartCenter');
  
        $this->setDbUnique('url,userId');
    }


    /**
     * Class API
     * Връща заявка за модела remote_Authorizations, настоена да филтрира активните записи
     */
    public static function getFiltredQuery($classes = array(), $userId = null)
    {
        if (is_scalar($classes)) {
            $classes = array(core_Classes::getId($classes) => $classes);
        }

        $query = remote_Authorizations::getQuery();
        if (count($classes)) {
            $query->where('#driverClass IN (' . implode(',', array_keys($classes)) . ')');
        }
        $query->where('#state = "active"');
        if ($userId !== null) {
            $query->where('#userId = ' . (int) $userId);
        }

        return $query;
    }


    public static function on_BeforeSave($mvc, &$id, $rec)
    {
        $rec->url = self::canonizeUrl($rec->url);
    }
    

    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public static function on_AfterPrepareEditform($mvc, $res, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $form->setDefault('userId', core_Users::getCurrent());

        if (!haveRole('admin')) {
            $form->setReadonly('userId');
        }

        if (!$rec->driverClass) {
            $form->setField('url', 'input=none');
        }

        $form->setField('url', 'caption=URL');

        $form->setField('driverClass', 'removeAndRefreshForm=url');
    }


    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $data->form->title = 'Свързване на онлайн услуга';
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if (!haveRole('admin, ceo')) {
            $cu = core_Users::getCurrent();
            $data->query->where(array("#userId = '[#1#]'", $cu));
        }
    }
    
    
    /**
     * Връща канонично URL
     */
    public static function canonizeUrl($url)
    {
        return trim(strtolower(rtrim($url, '/')));
    }


    /**
     * Подготвя детайла с оторизациите в профила
     */
    public static function prepareAuthorizationsList($data)
    {
        $userId = $data->masterData->rec->userId;
        
        if ($userId != core_Users::getCurrent()) {
            if (!haveRole('admin, ceo')) {
                return ;
            }
        }
        
        $data->action = 'list';
        
        $mvc = cls::get(__CLASS__);

        // Създаваме заявката
        $data->query = $mvc->getQuery();
        $data->query->where("#userId = ${userId}");
        
        // Подготвяме полетата за показване
        $data->listFields = arr::make('url=Услуга,auth=Оторизация,state=Състояние');
        
        // Подготвяме навигацията по страници
        $mvc->prepareListPager($data);
        
        // Подготвяме записите за таблицата
        $mvc->prepareListRecs($data);
        
        // Подготвяме редовете на таблицата
        $mvc->prepareListRows($data);

        if (haveRole('powerUser') && (core_Users::getCurrent() == $data->masterData->rec->userId)) {
            $data->masterData->toolbar->addbtn(
                'Свързване',
                array('remote_Authorizations', 'add', 'ret_url' => true),
                'row=2,ef_icon=img/16/checked-blue.png,title=Свързване на онлайн услуги'
            );
        }
    }
    
    
    /**
     * Рендира детайла с оторизациите в профила
     */
    public static function renderAuthorizationsList($data)
    {
        if (arr::count($data->recs)) {
            $mvc = cls::get(__CLASS__);

            $tpl = $mvc->renderList($data);

            return $tpl;
        }
    }


    /**
     * Връща първата система, за която посочения потребител има оторизация и тя отговаря на критериите
     */
    public static function getSystemId($url = '', $driver = 'remote_BgerpDriver', $userId = null)
    {
        if (core_Packs::isInstalled('remote')) {
            if (!$userId) {
                $userId = core_Users::getCurrent();
            }

            if (!$userId) {
                return;
            }
            
            $query = self::getQuery();

            while ($rec = $query->fetch(array("#url LIKE '%[#1#]%' AND #userId = {$userId}", $url))) {
                if (is_object($rec->data) && $rec->data->lKeyCC) {
                    return $rec->id;
                }
            }
        }
    }


    /**
     * Връща URL към отдалечена машина, което изпълнява логване и след него - подаденото URL
     */
    public static function getRemoteUrl($systemId, $url)
    {
        $url = toUrl($url);

        $rec = self::fetch($systemId);
        if (strpos($url, EF_APP_NAME)) {
            list($p, $url) = explode(EF_APP_NAME, $url);
        }
        $url = rtrim($rec->url, '/') . '/' . ltrim($url, '/');
 
        $res = array('remote_BgerpDriver', 'Autologin', $systemId, 'url' => $url);

        return $res;
    }


    /**
     * Изпраща съобщения за непрочетени известия на потребителите
     */
    public function cron_AlertForNotifications()
    {
        // Имена за различните видове нотификации
        $mapNames = array('normal' => '', 'warning' => 'urgent', 'alert' => 'critical');

        // Извличаме, кои потребители имат гейтове за изпращане на съобщения
        $classes = core_Classes::getOptionsByInterface('remote_SendMessageIntf');
        if (!count($classes)) {
            return;
        }
        
        $userSenders = array();
        $query = self::getFiltredQuery($classes);
        while ($rec = $query->fetch()) {
            $userSenders[$rec->userId][] = $rec;
        }
        if (!count($userSenders)) {
            log_System::add('remote_Authorizations', 'Няма потребители с услуги за изпращане на съобщения', null, 'info');

            return;
        }

        $lastPortalSeen = array();
        
        // Махаме тези потребители, които са били активни допреди 2 минути
        $before2min = dt::addSecs(-180);
        foreach ($userSenders as $userId => $rec) {
            $uRec = core_Users::fetch($userId);
            if ($uRec->lastActivityTime > $before2min) {
                unset($userSenders[$userId]);
            } else {
                $lastPortalSeen[$userId] = bgerp_LastTouch::get('portal', $userId);
            }
        }
        if (!count($userSenders)) {
            log_System::add('remote_Authorizations', 'Няма потребители с услуги за изпращане на съобщения, които не са били активни в последните 3 минути.', null, 'info');

            return;
        }
 
        // Обикаля по всички известия от последните 48 часа, което не са затворени
        $ntfs = array();
        $nQuery = bgerp_Notifications::getQuery();
        $last48hours = dt::addSecs(-48 * 3600);
        $nQuery->where("#state = 'active' AND #activatedOn > '{$last48hours}' AND #userId IN (" . implode(',', array_keys($userSenders)) . ')');
        while ($nRec = $nQuery->fetch()) {
         
            // Прескачаме тези, които са по-стари от последното виждане на портала
            if ($lastPortalSeen[$nRec->userId] > $nRec->activatedOn) {
                continue;
            }

            // Правим масив по потребители, приоритети и време на най-старото известие
            if (!isset($ntfs[$nRec->userId][$nRec->priority])) {
                $ntfs[$nRec->userId][$nRec->priority] = $nRec->activatedOn;
            } else {
                $ntfs[$nRec->userId][$nRec->priority] = min($nRec->activatedOn, $ntfs[$nRec->userId][$nRec->priority]);
            }
        }
        if (!count($ntfs)) {
            log_System::add('remote_Authorizations', 'Няма невидени нотификации', null, 'info');

            return;
        }

        list($d, $t) = explode(' ', dt::now());

        if ($t > '22:00:00' || $t < '08:00:00') {
            $dayTime = 'night';
        } elseif ($t > '18:00:00' || $t < '09:00:00' || cal_Calendar::isDayType($d . ' 12:00:00', 'nonworking')) {
            $dayTime = 'nonworking';
        } else {
            $dayTime = 'working';
        }
 
        foreach ($ntfs as $userId => $nArr) {
            $lastSent['alert'] = bgerp_LastTouch::get('sent_alert', $userId);
            $lastSent['warning'] = bgerp_LastTouch::get('sent_warning', $userId);
            $lastSent['normal'] = bgerp_LastTouch::get('sent_normal', $userId);
            
            $alreadySent['alert'] = ($lastPortalSeen[$userId] < $lastSent['alert']);
            $alreadySent['warning'] = ($lastPortalSeen[$userId] < $lastSent['warning']) || $alreadySent['alert'];
            $alreadySent['normal'] = ($lastPortalSeen[$userId] < $lastSent['normal']) || $alreadySent['warning'];
 
            // За да изпратим известие на дадения потребител, искаме от последното му показване на портала, да няма
            // изпращане на известие за същия или по-малък приоритет налични известия
            foreach ($nArr as $priority => $time) {

                // Ако вече сме изпращали този тип известие и след това потребителят
                // все-още не е влизал в системата, пропускаме го
                if ($alreadySent[$priority]) {
                    // log_System::add('remote_Authorizations', "За нотификацията {$userId} {$time} {$priority} вече е изпращано съобщение", NULL, 'info');

                    continue;
                }
 
                // Поне колко трябва да е старо известието, за да се изпрати
                $sendIfOlderThan = dt::addSecs(-bgerp_Setup::get('NOTIFY_' . strtoupper($priority), false, $userId));
                
                if ($time < $sendIfOlderThan) {
                    $config = bgerp_Setup::get('BLOCK_' . strtoupper($priority), false, $userId);
                    if (in_array($dayTime, explode('|', $config))) {
                        log_System::add('remote_Authorizations', "За нотификацията {$userId} {$time} {$priority} не е подходящо времето {$config}", null, 'info');

                        continue;
                    }

                    $msg = 'There is ' . $mapNames[$priority] . ' notifications on ' . toUrl(array('bgerp_Portal', 'Show'), 'absolute');

                    // Изпращаме нотификацията
                    foreach ($userSenders[$userId] as $rec) {
                        $driver = self::getDriver($rec);
                        if ($driver->sendMessage($rec, $msg)) {
                            bgerp_LastTouch::set('sent_' . $priority, $userId);
                            break;
                        }
                        log_System::add('remote_Authorizations', "Неуспешно изпращане на нотификацията {$userId} {$time} {$priority}", null, 'info');
                    }
                }
            }
        }
    }
}
