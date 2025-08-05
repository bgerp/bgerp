<?php

defIfNot('EF_SESS_ID_LEN', 32);       // ДЪЛЖИНА на суровия sessId в cookie (хекс)
defIfNot('EF_SESS_KEY_LEN', 64);
defIfNot('EF_SESS_MAX_DATA_LEN', 4000);

defIfNot('EF_USERS_SESS_TIMEOUT', 3600 );
defIfNot('EF_USERS_SESS_LIFETIME', 10 * 3600);

class core_DbSess extends core_Manager
{
    public $title = 'DB Сесии';
    public $singleTitle = 'Сесийна променлива';

    public $canList   = 'admin';
    public $canAdd    = 'no_one';
    public $canEdit   = 'no_one';
    public $canDelete = 'admin';

    public $doReplication = false;
    public static $stopCaching = false;

    public $loadList = 'plg_Sorting,plg_RowTools';

    public $vars = array();
    protected $loaded = false;

    protected $regenerateKeys = array('currentUserRec');

    protected $sessId = null;          // суровото ID, държи се само в памет/cookie
    protected $sessName = 'bgERPSESSID';

    protected $secure = false;
    protected $httpOnly = true;
    protected $sameSite = 'Lax';

    protected $maxLifetime = EF_USERS_SESS_LIFETIME;
    protected $maxInactiveTime = EF_USERS_SESS_TIMEOUT;

    /** Алгоритъмът за хеширане на sessId в БД: 'md5', 'sha256', ... */
    protected $sessDbHashAlgo = 'md5';

    public function description()
    {
        // Дължината се взема според алгоритъма (md5=32, sha256=64 и т.н.)
        $sessHashLen = $this->getSessDbHashLen();

        $this->FLD('sessId', 'varchar(' . $sessHashLen . ')', 'caption=Сесия(хеш),notNull');
        $this->FLD('key',    'varchar(' . EF_SESS_KEY_LEN . ')', 'caption=Ключ,notNull');
        $this->FLD('type',   'enum(integer, double, string, boolean, serialized, compressed)', 'caption=Тип,notNull');
        $this->FLD('value',  'varbinary(' . EF_SESS_MAX_DATA_LEN . ')', 'caption=Данни');

        $this->setDbUnique('sessId,key');
        $this->setDbIndex('key');
        $this->dbEngine = 'memory';
    }


    /**
     * Задава променлива в сесията. Ако липсва сесия - стартира я.
     */
    public function set($key, $value)
    {
        // Пренебрегваме записа, ако сесията е мютната
        if(core_Session::$mute) {
            return;
        }
        $this->ensureSessionId(true);
        $this->ensureLoaded();

        if (in_array($key, $this->regenerateKeys, true)) {
            $this->regenerateSessionId();
        }

        $this->setDbVar($key, $value);
    }


    /**
     * Проверява дали сесията е стартирана
     */
    public function isStarted()
    {
        return count($this->vars) > 0;
    }


    /** 
     * Унищожава текущата сесия (ако има) и изчиства cookie. 
     */
    public function destroy(): void
    {
        $this->expireCookie();
        $this->vars = array();
        if(isset($this->sessId)) {
            $sessHash = $this->hashSessId($this->sessId);
            $this->delete("#sessId = '{$sessHash}'");
        }
    }
    

    /**
     * Връща масив с всички променливи записани в сесията
     */
    public function getAll($includeSystem = false)
    {
        if (!$this->ensureSessionId(false)) return array();

        $this->ensureLoaded();

        if ($includeSystem) return $this->vars;

        $out = array();
        foreach ($this->vars as $k => $v) {
            if (strncmp($k, '__', 2) !== 0) $out[$k] = $v;
        }
        return $out;
    }



    /** Чете всички променливи за дадената сесия (по хеш). Връща броя им. */
    public function readDb()
    {
        expect(isset($this->sessId));

        $sessHash = $this->hashSessId($this->sessId);

        $query = self::getQuery();
        $query->show('key,type,value');

        while ($rec = $query->fetch(array("#sessId = '[#1#]'", $sessHash), true)) {
            switch ($rec->type) {
                case 'integer':    $value = (int) $rec->value; break;
                case 'double':     $value = (float) $rec->value; break;
                case 'boolean':    $value = (bool) $rec->value; break;
                case 'serialized': $value = unserialize($rec->value); break;
                case 'compressed': $value = unserialize(gzuncompress($rec->value)); break;
                default:           $value = $rec->value;
            }
            $this->vars[$rec->key] = $value;
        }

        if((isset($this->vars['__startOn']) && ($this->vars['__startOn'] + $this->maxLifetime < time())) || 
            (isset($this->vars['__activeOn']) && ($this->vars['__activeOn'] + $this->maxInactiveTime < time()))) {
            
            $this->vars = array();
        }

        $res = count($this->vars);
        if ($res) $this->setDbVar('__activeOn', time());
 
        return $res;
    }

    /** Записва променлива в сесията (в БД ключът е хеш на sessId) */
    private function setDbVar($key, $value)
    {
        expect(isset($this->sessId));
        expect(is_array($this->vars));

        $hadVars = (count($this->vars) > 0);

        $this->vars[$key] = $value;

        if (is_scalar($value)) {
            $type = gettype($value);
        } else {
            $type  = 'serialized';
            $value = serialize($value);
            if(strlen(strlen($value) >= EF_SESS_MAX_DATA_LEN)) {
                $value = gzcompress($value);
                $type = 'compressed';
            }
        }

        $rec = (object) array(
            'sessId' => $this->hashSessId($this->sessId), // записваме ХЕШ
            'key'    => $key,
            'type'   => $type,
            'value'  => $value,
        );

        $rec->id = $this->fetchField(array("#sessId = '[#1#]' AND #key = '[#2#]'", $rec->sessId, $rec->key), 'id');

        $res = $this->save_($rec);

        if ($res && !$hadVars) {
            $now = time();
            $this->setDbVar('__activeOn', $now);
            $this->setDbVar('__startOn',  $now);
        }
    }

    /**
     * Изчиства старите или не-активни сесии
     */
    public function cron_ClearSess()
    {
        if ($this->maxLifetime) {
            $lateStartOn = time() - $this->maxLifetime;
            $query = $this->getQuery();
            while ($rec = $query->fetch("#key = '__startOn' AND CAST(#value AS UNSIGNED) < {$lateStartOn}")) {
                $this->delete(array("#sessId = '[#1#]'", $rec->sessId));
            }
        }

        if ($this->maxInactiveTime) {
            $lateActiveOn = time() - $this->maxInactiveTime;
            $query = $this->getQuery();
            while ($rec = $query->fetch("#key = '__activeOn' AND CAST(#value AS UNSIGNED) < {$lateActiveOn}")) {
                $this->delete(array("#sessId = '[#1#]'", $rec->sessId));
            }
        }
    }

    /**
     * Зарежда ИД на сесия. Ако няма в куки - създава нова
     */
    private function ensureSessionId($createIfMissing = false)
    {
        if (!empty($this->sessId)) return true;

        if (!empty($_COOKIE[$this->sessName])) {
            $id = (string) $_COOKIE[$this->sessName];
            if (strlen($id) === EF_SESS_ID_LEN && ctype_xdigit($id)) {
                $this->sessId = $id;
                return true;
            }
        }

        if ($createIfMissing) {
            $this->startSession();
            return true;
        }

        return false;
    }
    

    /**
     * Стартира нова сесия
     */
    private function startSession()
    {
        if (headers_sent()) {

            return ;
        }

        $this->sessId = $this->generateSessionId((int) (EF_SESS_ID_LEN / 2));
        $this->vars   = array();
        $this->loaded = true;

        $this->sendCookie($this->sessName, $this->sessId);
    }
    
    /**
     * Продсигурява зареждането на променливите от сесията
     */
    private function ensureLoaded()
    {
        if (!$this->loaded) {
            $this->readDb();
            $this->loaded = true;
        }
    }

    /** 
     * Мигрира от стария хеш към новия при регенериране на sessId 
     */
    private function regenerateSessionId()
    {
        if (empty($this->sessId) || headers_sent()) return;

        $oldRaw  = $this->sessId;
        $oldHash = $this->hashSessId($oldRaw);

        $this->ensureLoaded();

        $this->sessId = $this->generateSessionId((int) (EF_SESS_ID_LEN / 2));
        $newHash = $this->hashSessId($this->sessId);

        $current = $this->vars;
        $this->vars = array();
        foreach ($current as $k => $v) {
            $this->setDbVar($k, $v); // пише под $newHash
        }

        $this->delete(array("#sessId = '[#1#]'", $oldHash));

        $this->sendCookie($this->sessName, $this->sessId);
    }

    /* ===================== ХЕЛПЪРИ ===================== */

    /** Хеш на суровия sessId за БД (hex) */
    private function hashSessId($rawId)
    {
        return hash($this->sessDbHashAlgo, $rawId, false); // hex
    }

    /** Дължината на hex-хеша за текущия алгоритъм */
    private function getSessDbHashLen()
    {
        return strlen(hash($this->sessDbHashAlgo, '', false));
    }

    private function generateSessionId($bytes)
    {
        if (function_exists('random_bytes')) {
            $raw = random_bytes($bytes);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $raw = openssl_random_pseudo_bytes($bytes);
        } else {
            $raw = '';
            for ($i = 0; $i < $bytes; $i++) $raw .= chr(mt_rand(0, 255));
        }
        return bin2hex($raw);
    }

    private function buildCookieParams()
    {
        $secure = $this->secure || (strcasecmp($this->sameSite, 'None') === 0);

        return array(
            'expires'  => $this->maxLifetime ? (time() + (int) $this->maxLifetime) : 0,
            'path'     => '/',
            'secure'   => (bool) $secure,
            'httponly' => (bool) $this->httpOnly,
            'samesite' => (string) $this->sameSite,
        );
    }

    private function sendCookie($name, $value)
    {
        $p = $this->buildCookieParams();

        if (PHP_VERSION_ID >= 70300) {
            setcookie($name, $value, array(
                'expires'  => $p['expires'],
                'path'     => $p['path'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ));
        } else {
            $path = $p['path'];
            if (!empty($p['samesite'])) $path .= '; samesite=' . $p['samesite'];
            setcookie($name, $value, $p['expires'], $path, '', $p['secure'], $p['httponly']);
        }
    }

    /** Изтича cookie-то. */
    protected function expireCookie(): void
    {
        @setcookie($this->sessionName, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite ?: 'Lax',
        ]);
        unset($_COOKIE[$this->sessionName]);
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if(substr($rec->key, 0, 2) == '__') {
            $row->value = 'Преди: ' .  (time() - ( (int) $rec->value)) . ' сек.';
        }
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'DESC');
    }
}
