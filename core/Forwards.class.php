<?php

defIfNot('CORE_FORWARD_SYSID_LEN', 8);

/**
 * Клас 'core_Forwards' - Криптирани линкове към вътрешни ресурси
 *
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Forwards extends core_Manager
{
    /**
     * Точен размер на системния идентификатор
     */
    const CORE_FORWARD_SYSID_LEN = CORE_FORWARD_SYSID_LEN;

    /**
     * Заглавие на мениджъра
     */
    public $title = 'Криптирани линкове за пренасочване';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Пренасочващ линк';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    //var $listFields = "id,title=Описание,parameters=Параметри,last=Последно,state";
    
    
    /**
     * Списък с плъгини, които се прикачат при конструиране на мениджъра
     */
    public $loadList = 'plg_SystemWrapper,plg_Created';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';



    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'admin';

    
        
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('hash', 'varchar(32)', 'caption=Хеш,notNull');
        $this->FLD('sysId', 'varchar(' . CORE_FORWARD_SYSID_LEN . ')', 'caption=Системен ID,notNull');
        $this->FLD('className', 'varchar(128)', 'caption=Клас');
        $this->FLD('methodName', 'varchar(64)', 'caption=Метод');
        $this->FLD('data', 'blob(serialize)', 'caption=Данни');
        $this->FLD('expiry', 'datetime', 'caption=Валидност');
 
        $this->setDbUnique('sysId');
    }
    

    /**
     * Преди извличането на записите за листовия изглед
     */
    protected function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#createdOn=DESC');
    }
    

    /**
     * Функция, която се извиква от core_Request в случай, че заявката е за криптирана връзка
     */
    public static function go($sysId)
    {
        $rec = self::fetch(array("#sysId = '[#1#]'", $sysId));

        if (!$rec) {
            redirect(array('Index'), false, '|Изтекла или липсваща връзка', 'error');
        }

        $callback = array($rec->className, 'callback_' . $rec->methodName);

        $res = call_user_func($callback, $rec->data);

        return $res;
    }


    /**
     * Функция която връща системо ID на криптирана връзка
     *
     * @param string|object $class  Клас на колбек функцията
     * @param string        $method Метод за колбек функцията
     * @param string        $data   Данни, които ще се предадат на колбек функцията
     * @param int           $expiry Колко секунди да е валиден записа
     *
     * @return string
     */
    public static function getSysId($classObj, $method, $data, $lifetime = 0)
    {
        $class = is_object($classObj) ? cls::getClassName($classObj) : $classObj;

        $expiry = $lifetime > 0 ? dt::addSecs($lifetime) : null;

        $hash = md5($class . $method . json_encode($data) . '/');

        if ($rec = self::fetch("#hash = '{$hash}'")) {
            $rec->expiry = $expiry;
        } else {
            $ptr = str_repeat('a', CORE_FORWARD_SYSID_LEN);

            do {
                $sysId = str::getRand($ptr);
            } while (self::fetch("#sysId = '${sysId}'"));

            $rec = (object) array(
                    'hash' => $hash,
                    'className' => cls::getClassName($class),
                    'methodName' => $method,
                    'data' => $data,
                    'expiry' => $expiry,
                    'sysId' => $sysId,
                );
        }

        self::save($rec);

        return $rec->sysId;
    }


    /**
     * Функция която връща URL на криптирана връзка
     *
     * @param string|object $class    Клас на колбек функцията
     * @param string        $method   Метод за колбек функцията
     * @param string        $data     Данни, които ще се предадат на колбек функцията
     * @param int           $lifetime Колко секунди да е валиден записа
     *
     * @return string
     */
    public static function getURL($class, $method, $data, $lifetime = 0)
    {
        $sysId = self::getSysId($class, $method, $data, $lifetime);

        return toUrl(array($sysId), 'absolute');
    }


    /**
     * Функция която изтрива криптираното URL
     *
     * @param string|object $class    Клас на колбек функцията
     * @param string        $method   Метод за колбек функцията
     * @param string        $data     Данни, които ще се предадат на колбек функцията
     * @param int           $lifetime Колко секунди да е валиден записа
     *
     * @return integer
     */
    public static function deleteUrl($class, $method, $data, $lifetime = 0)
    {
        $sysId = self::getSysId($class, $method, $data, $lifetime);
        
        $deleted = self::delete(array("#sysId = '[#1#]'", $sysId));
        
        return $deleted;
    }


    /**
     * Почистване на връзките с изтекъл срок
     *
     * @return string Статус
     */
    public function cron_DeleteExpiredLinks()
    {
        $now = dt::verbal2mysql();
        $cnt = $this->delete("#expiry <= '{$now}'");
        if ($cnt) {
            $res = "Бяха изтрити {$cnt} core_Forward връзки";
        } else {
            $res = 'Не бяха изтрити core_Forward връзки';
        }

        return $res;
    }
}
