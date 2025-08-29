<?php

// Игнориране на затварянето на модул "Help"
defIfNot('BGERP_DEMO_MODE', false);


/**
 * Подсистема за помощ - Логове
 *
 *
 * @category  bgerp
 * @package   help
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class help_Log extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Логове';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Лог';


    /**
     * Разглеждане на листов изглед
     */
    public $canSingle = 'no_one';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'help_Wrapper, plg_RowTools2';


    /**
     * Полета за листовия изглед
     */
    public $listFields = 'userId,infoId,seeOn,seeCnt,closedOn';


    /**
     * Кой има право да чете?
     */
    public $canRead = 'user';


    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
        $this->FLD('infoId', 'key(mvc=help_Info,select=title)', 'caption=За кой клас, hint=За кой клас се отнася информацията');
        $this->FLD('seeOn', 'datetime', 'caption=Видяно->На, hint=Кога за първи път е видяно');
        $this->FLD('seeCnt', 'int', 'caption=Видяно->Брой, hint=Колко пъти е видяно');
        $this->FLD('closedOn', 'datetime', 'caption=Затворено->На, hint=Кога е затворено');

        $this->setDbUnique('userId,infoId');
    }


    /**
     * Как да вижда текущият потребител тази помощна информация?
     */
    public static function getDisplayMode($infoId, $userId = null, $increasSeeCnt = true)
    {
        // Ако нямаме потребител, вземаме текущия
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }

        if (!$userId) {

            return 'none';
        }

        $nowDate = dt::now();
        $conf = core_Packs::getConfig('help');

        $rec = help_Log::fetch("#infoId = {$infoId} AND (#userId = {$userId})");
        if (!$rec) {
            $rec = new stdClass();
            $rec->infoId = $infoId;
            $rec->userId = $userId;
            $rec->seeOn = $nowDate;
            $rec->seeCnt = 0;
            $rec->closedOn = null;
        }

        if ((!$rec->closedOn) && $rec->seeCnt < max($conf->HELP_MAX_CLOSE_DISPLAY_CNT, $conf->HELP_MAX_OPEN_DISPLAY_CNT)) {
            if ($increasSeeCnt) {
                $rec->seeCnt++;
            }

            self::save($rec);
        }

        // Ако се в лимита за време/показвания за отворено показване и помощтта не е затворена ръчно
        // то връщаме режима за показване 'open'
        $untilOpenDate = dt::timestamp2mysql(dt::mysql2timestamp($rec->seeOn) + $conf->HELP_MAX_OPEN_DISPLAY_TIME);
        if (($untilOpenDate > $nowDate || $rec->seeCnt < $conf->HELP_MAX_OPEN_DISPLAY_CNT) && !$rec->closedOn) {

            return 'open';
        }

        /*
         * Ако и времето и брояча са под определените лимити за показване в затворено състояние, то
         * връщаме 'closed'
         */
        $untilCloseDate = dt::timestamp2mysql(dt::mysql2timestamp($rec->seeOn) + $conf->HELP_MAX_CLOSE_DISPLAY_TIME);
        if ($untilCloseDate > $nowDate || $rec->seeCnt < $conf->HELP_MAX_CLOSE_DISPLAY_CNT) {
            if (BGERP_DEMO_MODE === true) {

                return 'open';
            }

            return 'close';
        }

        // Ако сме решили, че искаме винаги да се показва, дори и ако е затворено ръчно
        if (BGERP_DEMO_MODE === true) {

            return 'open';
        }

        // Ако не трябва да показваме информацията нито в отворено, нито в затворено състояние
        // връщаме 'none'
        return 'none';
    }


    /**
     * Затворил ли е потребителя информацията собственоръчно?
     */
    public static function act_CloseInfo()
    {
        requireRole('debug');
        // За кой клас се отнася
        $id = core_Request::get('id', 'int');

        // днешната дата
        $nowDate = dt::now();

        $cu = core_Users::getCurrent();

        // Намираме  запис
        $rec = help_Log::fetch("#infoId = {$id} AND #userId = {$cu}");

        if ($rec) {

            // добавяме дата
            $rec->closedOn = $nowDate;

            // и я записваме
            self::save($rec, 'closedOn');
        }

        if (Request::get('ajax_mode')) {

            return array();
        }
        shutdown();
    }


    /**
     * Увеличава броя на вижданията
     */
    public static function act_See()
    {
        requireRole('debug');

        // За кой клас се отнася
        $id = core_Request::get('id', 'int');

        $cu = core_Users::getCurrent();

        // Намираме  запис
        $rec = help_Log::fetch("#infoId = {$id} AND #userId = {$cu}");

        if ($rec) {
            $rec->seeCnt++;

            self::save($rec, 'seeCnt');
        }

        if (Request::get('ajax_mode')) {

            return array();
        }
        shutdown();
    }

    public function act_LogDataAdd()
    {
        requireRole('debug');

        //Подготовка на ip
        $arr = array();
        $config = core_Packs::getConfig('hr');
        $arr = explode(',', $config->HR_COMPANIES_IP);

        if ((empty($arr) || (countR($arr) == 1 && $arr[0] == ''))) {

            foreach (array(1 => '88.88.88.88', 2 => '11.11.11.11', 3 => '22.22.22.22', 4 => '33.33.33.33') as $v) {
                if (!log_Ips::fetch("#ip = $v")) {
                    $ip = $v;
                    break;
                }
            }

            $rec = new stdClass();
            $rec->ip = $ip;
            $rec->country2 = 'BG';
            $rec->createdOn = dt::now();
            $rec->host = null;
            $rec->users = null;

            cls::get('log_Ips')->save_($rec);

        }

        if (!$ipId = log_Ips::fetchField(array("#ip = '[#1#]'", $arr[0]), 'id')) {
            $rec = new stdClass();
            $rec->ip = $arr[0];
            $rec->country2 = 'BG';
            $rec->createdOn = dt::now();
            $rec->host = null;
            $rec->users = null;

            cls::get('log_Ips')->save_($rec);
            $ipId = log_Ips::fetchField(array("#ip = '[#1#]'", $arr[0]), 'id');
        };

        $actDay = '2023-04-18 12:00:00';

        $time = dt::mysql2timestamp($actDay);

        $minAdd = 6;

        $counter = 4;
        for ($i = 1; $i <= $counter; $i++) {

            $objectId = dt::mysql2timestamp()+$i;

            $userId = null;

            $bridId = log_Browsers::getBridId();

            $rec = new stdClass();
            $rec->ipId = $ipId;
            $rec->brId = $bridId;
            $rec->userId = isset($userId) ? $userId : core_Users::getCurrent();
            $rec->actionCrc = log_Actions::getActionCrc('message');
            $rec->classCrc = log_Classes::getClassCrc('className');
            $rec->objectId = $objectId;
            $rec->time = $time;
            $rec->type = 'type';
            $rec->lifeTime = 180 * 86400;

            $time += $minAdd * 60 * $i;

            $LogData = cls::get('log_Data');
            $LogData->save_($rec);
        }
        return " Дата на отчета : $actDay,  Добавени минути:$minAdd, $counter пъти ";

    }


}
