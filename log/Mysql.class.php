<?php 

/**
 *
 *
 * @category  bgerp
 * @package   mysql
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class log_Mysql extends core_Manager {
    
    
    /**
     * Заглавие
     */
    public $title = 'MySQL заявки';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'debug';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
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
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, log_Wrapper, plg_Sorting';
    

    /**
     * Db engine
     */
    public $dbEngine = 'MEMORY';
    

    /**
     * Буфер за прихванатите заявки
     */
    static $buffer = array();


    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('crc', 'bigint', 'caption=Код');
        $this->FLD('query', 'varchar(2048)', 'caption=Заявка');
        $this->FLD('time', 'float', 'caption=Време->Общо');
        $this->FLD('timeAvg', 'float', 'caption=Време->Средно');
        $this->FLD('cnt', 'int', 'caption=Брой');

        $this->setDbUnique('crc');
    }


    /**
     * Добавя запис на прихваната заявка в буфера
     */
    public function add($query, $time)
    {
        $query = substr(self::strip($query), 0, 2048);
        $crc = crc32($query);
        if(isset(self::$buffer[$crc])) {
            self::$buffer[$crc]->time += $time;
            self::$buffer[$crc]->cnt++;
        } else {
            self::$buffer[$crc] = (object) array('crc' => $crc, 'query' => $query, 'time' => $time, 'cnt' => 1);
        }
    }


    /**
     * Записва всички логвани заявки в хита в таблицата
     */
    public static function flush()
    {
        static $flag = false;

        if($flag) return;

        $flag = true;

        $buffer = self::$buffer;

        foreach($buffer as $rec) {
            try {
                $exRec = self::fetch("#crc = {$rec->crc}");
                if($exRec) {
                    $exRec->cnt += $rec->cnt;
                    $exRec->time += $rec->time;
                    $exRec->timeAvg = $exRec->time / $exRec->cnt;
                    self::save($exRec);
                } else {
                    $rec->timeAvg = $rec->time / $rec->cnt;
                    self::save($rec);
                }
            } catch ( \Exception $e ) {
            }
        }

        $flag = false;
    }


    /**
     * Поочиства заявката от данни
     */
    private function strip($query)
    {
        $query = preg_replace("/(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\\'|[^'])+'))/is", '*', $query);
        $query = preg_replace("/-?[0-9]+(\\.[0-9]+)?([e][-+]?[0-9]+)?/is", '*', $query);        
        $query = preg_replace("/NULL/i", '*', $query);        

        return $query;
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (haveRole('admin,debug')) {
            $data->toolbar->addBtn('Изтриване', array($mvc, 'Truncate', 'ret_url' => true), 'ef_icon=img/16/sport_shuttlecock.png, title=Премахване на всички записи,warning=Наистина ли искате да изтриете всички записи?');
        }
    }
    
    
    /**
     * Екшън за изтриване на всички кеширани цени
     */
    public function act_Truncate()
    {
        requireRole('admin,debug');
        
        self::truncate();
        core_Statuses::newStatus('Записите са изтрити');
        
        followRetUrl();
    }
    
    
}
