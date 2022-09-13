<?php


/**
 * class Events
 *
 * Тук се съхраняват постъпващите събития.
 * Времето на събитието се взима от четеца.
 * Извършва се конвертиране към MySql формат на времето, ако е необходимо.
 * Запазва се и информация за притежателя на rfid номера към момента на събитието,
 * видът на събитието, както и евентуално други параметри ако е необходимо.
 *
 *
 * @category  bgerp
 * @package   rfid
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rfid_Events extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Събития';
    
    
    /**
     * Време за опресняване информацията при лист на събитията
     */
    public $refreshRowsTime = 5000;
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,admin,rfid';
    
    
    /**
     * Необходими плъгини и външни мениджъри
     */
    public $loadList = 'rfid_Tags,rfid_Readers,plg_RefreshRows,rfid_Wrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Обща информация
        $this->FLD('tag', 'varchar(64)', 'caption=Rfid номер');
        $this->FLD('readerId', 'key(mvc=rfid_Readers,select=title)', 'caption=Четец');
        $this->FLD('time', 'datetime', 'caption=Време');
        $this->FLD('addedOn', 'datetime', 'caption=Вкаран на');
        $this->FLD('params', 'varchar(32)', 'caption=Други');
        $this->FLD('remoteIp', 'Ip', 'caption=IP източник');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function on_AfterPrepareData($mvc, &$res, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
        $data->toolbar = null;
    }


    /**
     * Регистрира събитие през WEB
     */
    public  function act_add()
    {
        $conf = core_Packs::getConfig('rfid');
        
        // Ако получаваме данни от неоторизирано IP ги игнорираме
        $delimiters = [',', ';', '|'];
        $newStr = str_replace($delimiters, $delimiters[0], $conf->ALLOWED_ADDRESSES);
        
        file_put_contents('rfid_debug.txt', $_SERVER['REMOTE_ADDR'] . "\n", FILE_APPEND);
        
        $allowedIPArr = array_map('trim', explode($delimiters[0], $newStr));
        if (false === array_search($_SERVER['REMOTE_ADDR'], $allowedIPArr) ) {
            file_put_contents('rfid_debug.txt', "Невалидно ИП!" . "\n", FILE_APPEND);
            shutdown();
        }
        
        $card = Request::get('card', 'int');
        $stamp = Request::get('stamp', 'varchar');
        $term = Request::get('term', 'int');
        $secret = Request::get('secret', 'int');
        $readerId = Request::get('readerId', 'int');
        $readerId = is_int($readerId)?$readerId:0;
        $remoteIp = $_SERVER['REMOTE_ADDR'];
        
        clearstatcache('rfid_debug.txt');
        file_put_contents('rfid_debug.txt', $card . "|" . $stamp . "|" . $term . "|" . $readerId . "\n", FILE_APPEND);
        
        $Readers = cls::get('rfid_Readers');
        if (!$Readers->fetch($readerId)) $readerId = null;
        $rec = new stdClass();
        $rec->readerId = $readerId;
        $rec->tag = $card;
        $rec->time = $stamp;
        $rec->addedOn = date("Y-m-d H:i:s");
        $rec->params = $secret . " | " . $term;;
        $rec->remoteIp = $remoteIp;
        
        $this->save($rec);
        
        
        shutdown();
    }
    
}
