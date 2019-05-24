<?php 

/**
 *
 *
 * @category  bgerp
 * @package   logs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class log_Ips extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'logs_Ips';
    
    
    /**
     * Заглавие
     */
    public $title = 'IP-та';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'debug';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'debug';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'debug';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, log_Wrapper, plg_Sorting,plg_RowTools2';
    
    
    public static $ipsArr = array();
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('ip', 'ip', 'caption=IP');
        $this->FLD('country2', 'varchar(2)', 'caption=Държава');
        $this->FLD('host', 'varchar(64)', 'mandatory,caption=Host');
        $this->FLD('users', 'varchar(128)', 'caption=Потребители');
        $this->FLD('createdOn', 'datetime', 'mandatory,caption=Създаване');
        
        $this->setDbUnique('ip');
    }
    
    
    /**
     * Връща id за съответния запис на IP
     *
     * @param string $ip
     *
     * @return int
     */
    public static function getIpId($ip = null)
    {
        $haveSession = false;
        $Session = cls::get('core_Session');
        if ($Session->isStarted()) {
            $haveSession = true;
        }
        
        if (!$ip) {
            $ip = core_Users::getRealIpAddr();
        }
        
        if (!self::$ipsArr) {
            if ($haveSession) {
                self::$ipsArr = (array) Mode::get('ipsArr');
            } else {
                self::$ipsArr = array();
            }
        }
        
        // Ако в сесията нямада id-то на IP-то, определяме го, записваме в модела и в сесията
        if (!isset(self::$ipsArr[$ip])) {
            if (!($id = self::fetchField(array("#ip = '[#1#]'", $ip), 'id'))) {
                $rec = new stdClass();
                $rec->ip = $ip;
                $rec->country2 = self::getCountry2($ip);
                $rec->createdOn = dt::now();
                
                $id = self::save($rec, null, 'IGNORE');
                if (!$id) {
                    $id = self::fetchField(array("#ip = '[#1#]'", $ip), 'id');
                }
            }
            
            if ($id) {
                self::$ipsArr[$ip] = $id;
            }
            
            if ($haveSession) {
                Mode::setPermanent('ipsArr', self::$ipsArr);
            }
        }
        
        return self::$ipsArr[$ip];
    }
    
    
    /**
     * Добавя информация, че от това IP се е логвал този потребител
     */
    public static function addUser($nick, $ip = null)
    {
        $nick = str_replace('&amp;', '&', $nick);
        
        if (!$ip) {
            $ip = core_Users::getRealIpAddr();
        }
        
        $rec = self::fetch(array("#id = '[#1#]'", $ip));
        if (!$rec) {
            $id = self::getIpId($ip);
            $rec = self::fetch($id);
        }
        
        $mustSave = false;
        
        if (stripos(',' . $rec->users . ',', ',' . $nick . ',') === false) {
            $rec->users = trim($nick . ',' . $rec->users, ', ');
            while (strlen($rec->users) > 128) {
                $userArr = explode(',', $rec->users);
                array_pop($userArr);
                $rec->users = implode(',', $rec->users);
            }
            $mustSave = true;
        }
        
        if ($mustSave) {
            self::save($rec, 'users');
        }
    }
    
    
    /**
     * Добавя префикс за държава и поставя оцветяване на IP
     *
     * @param string $ip
     *
     * @return string
     */
    public function decorateIp($ip, $coloring = false, $cnt = 0, $old = 0)
    {
        static $calls = 0;
        
        $mustSave = false;
        $rec = self::fetch(array("#id = '[#1#]'", $ip));
        if (!$rec) {
            $id = self::getIpId($ip);
            $rec = self::fetch($id);
        }
        
        // $host
        if (!$rec->host) {
            if ($calls < 6) {
                $rec->host = self::getHost($ip);
                $calls++;
                if ($rec->host != $ip) {
                    $mustSave = true;
                }
            }
        }
        $host = $rec->host ? $rec->host : $ip;
        if ($coloring) {
            $host = str::coloring($host, $ip);
        }
        
        // $title
        $title = '';
        if ($host != $ip) {
            $title = $ip;
        }
        if ($rec->users) {
            $title .= ': ' . $rec->users;
        }
        $title = ht::escapeAttr($title);
        
        // $country
        if (!$rec->country2) {
            $rec->country2 = self::getCountry2($ip);
            $mustSave = true;
        }
        
        if ($mustSave) {
            self::save($rec, 'host,country2', 'IGNORE');
        }
        
        $country = $rec->country2;
        $countryName = null;
        if ($rec->country2 == 'p') {
            $country = '⒫';
            $countryName = 'Private Network';
        } elseif (($rec->country2 == 'u') || !$rec->country2) {
            $country = '??';
        } else {
            $countryName = drdata_Countries::fetchField("#letterCode2 = '" . strtoupper($country2) . "'", 'commonName' . (core_Lg::getCurrent() == 'bg' ? 'Bg' : ''));
        }
        if (!$countryName) {
            $countryName = 'Unknown Country';
        }
        
        $country = ht::createLink($country, $country2 != '⒫' ? 'http://bgwhois.com/?query=' . $ip : null, null, array('target' => '_blank', 'class' => 'vislog-country', 'title' => $countryName));
        
        // $count
        $count = '';
        if ($cnt) {
            if ($old) {
                $style = 'color:#' . sprintf('%02X%02X%02X', min(($old / $cnt) * ($old / $cnt) * ($old / $cnt) * 255, 255), 0, 0) . ';';
                $titleCnt = "{$old}/{$cnt}";
            } else {
                $style = '';
                $titleCnt = "{$cnt}";
            }
            if (vislog_History::haveRightFor('list')) {
                $count = ht::createLink(
                    $titleCnt,
                            array('vislog_History', 'ip' => $ip),
                            null,
                            array('class' => 'vislog-cnt', 'style' => $style)
                );
            } else {
                $count = $titleCnt;
            }
        }
        if ($count) {
            $res = new ET("<div class='vislog'>[#1#]&nbsp;<span class='vislog-ip' title='[#2#]'>[#3#]</span>&nbsp;[#4#]</div>", $country, $title, $host, $count);
        } else {
            $res = new ET("<div class='vislog'>[#1#]&nbsp;<span class='vislog-ip' title='[#2#]'>[#3#]</span></div>", $country, $title, $host);
        }
        
        return $res;
    }
    
    
    /**
     * Връща двубиквено означение на страната на IP-хоста, 'p' - за частна мрежа и 'u' - за непозната страна
     */
    public static function getCountry2($ip)
    {
        if (type_Ip::isPrivate($ip)) {
            $res = 'p';
        } else {
            $res = drdata_IpToCountry::get($ip);
        }
        if (!$res) {
            $res = 'u'; // Непозната страна
        }
        
        return $res;
    }
    
    
    /**
     * Връща името на хоста (със съкращения) по зададено ip
     */
    public static function getHost($ip)
    {
        $hostName = @gethostbyaddr($ip);
        if (!$hostName) {
            $hostName = $ip;
        } elseif ($hostName != $ip) {
            $domainArr = array_slice(explode('.', ($hostName)), -3, 3);
            if (count($domainArr) == 3 && preg_match('/[0-9]{1,3}[^0-9]+[0-9]{1,3}[^0-9]+[0-9]{1,3}[^0-9]+[0-9]{1,3}/', $domainArr[0]) ||
                strlen($domainArr[0]) > 12 && strlen($domainArr[1]) > 3) {
                unset($domainArr[0]);
            }
            $hostName = implode('.', $domainArr);
            if (strlen($hostName) > 24) {
                unset($domainArr[0]);
                $hostName = implode('.', $domainArr);
            }
            if (strlen($hostName) > 24 || strlen($hostName) < 6) {
                $hostName = $ip;
            }
        }
        
        return $hostName;
    }
}
