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
        $this->FLD('query', 'varchar(4000)', 'caption=Заявка');
        $this->FLD('time', 'float', 'caption=Време->Общо');
        $this->FLD('timeAvg', 'float', 'caption=Време->Средно');
        $this->FLD('timeMax', 'float', 'caption=Време->Макс.');
        $this->FLD('cnt', 'int', 'caption=Брой');

        $this->setDbUnique('crc');
    }


    /**
     * Добавя запис на прихваната заявка в буфера
     */
    public static function add($query, $time)
    {
        $crc = self::getCrc32($query);
        $query = substr($query, 0, 4000);
        if(isset(self::$buffer[$crc])) {
            self::$buffer[$crc]->time += $time;
            self::$buffer[$crc]->cnt++;
            if($time > self::$buffer[$crc]->timeMax) {
                self::$buffer[$crc]->timeMax = $time;
                self::$buffer[$crc]->query = $query;
            }
        } else {
            self::$buffer[$crc] = (object) array('crc' => $crc, 'query' => $query, 'time' => $time, 'timeMax' => $time, 'cnt' => 1);
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
                    if($rec->timeMax > $exRec->timeMax) {
                        $exRec->timeMax = $rec->timeMax;
                        $exRec->query = $rec->query;
                    }
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
     * Изчислява CRC32 на изчистената от данните заявка
     */
    private static function getCrc32($query, $returnRowQuery = false)
    {
    
        // Замества числовите списъци в скоби, само ако не са част от имена на полета
        $query = preg_replace("/\\((?<![`])([0-9,]+)(?![`])\\)/is", '(*)', $query); 

        // Замества смесени списъци в скоби, които могат да съдържат числа и стрингове с ескейпнати кавички, без да засяга имената на колоните
        $query = preg_replace("/\\((?<![`'])('(?:\\\\'|[^'])*'|[0-9]+)(?:,\\s*(?<![`'])('(?:\\\\'|[^'])*'|[0-9]+))*\\)/is", '(*)', $query);

        // Замяна на единични стойности в кавички със символ (*), като допуска ескейпнати кавички
        $query = preg_replace("/(?<![`])(?:(?:\"(?:\\\\\"|[^\"])*\")|(?:'(?:\\\\'|[^'])+'))(?![`])/is", '*', $query);

        // Замяна на числа с или без десетична част и научна нотация със символ (*), като избягва числа в имена на полета
        $query = preg_replace("/(?<![`])(?<![a-zA-Z_])(-?[0-9]+(\\.[0-9]+)?([e][-+]?[0-9]+)?)(?![`])/is", '*', $query);

        // Замяна на NULL със символ (*)
        $query = preg_replace("/NULL/i", '*', $query); 

        // Замества всичко след VALUES със символ (*), за да премахне конкретните стойности
        $query = preg_replace("/VALUES\\s*(\\(.*?\\))(,\\s*\\(.*?\\))*/is", "VALUES (*)", $query);
 

        // Сведе всички бели интервали до единичен интервал
        $query = trim(preg_replace("/\\s+/", ' ', $query));

        if($returnRowQuery) {

            return $query;
        }

        return crc32($query);
    }


    public function act_Test()
    {

        $query = "UPDATE `acc_items` SET `lists` = '|37|44|', `uom_id` = NULL, `last_use_on` = '2024-11-05 09:12:26', `closed_on` = NULL WHERE id = 570033";

        bp( $this->getCrc32($query, true));
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    { 
        if(substr($rec->query, 0, 6) == 'SELECT' && strlen($rec->query) <= 4000) {
            $url = toUrl(array('log_Mysql', 'Explain', $rec->id));
            $row->query = "<a href='{$url}' target=_blank>SELECT</a>" . substr($rec->query, 6);
        }

        $row->query = "<div style='overflow:auto;max-height:240px;'>{$row->query}</div>";
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (haveRole('admin,debug')) {
            $data->toolbar->addBtn('Изтриване', array($mvc, 'Truncate', 'ret_url' => true), 'ef_icon=img/16/sport_shuttlecock.png, title=Премахване на всички записи,warning=Наистина ли искате да изтриете всички записи?');
            $data->toolbar->addBtn('Незиползвани индекси', array($mvc, 'UnusedIndexes', 'ret_url' => true));
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


    /**
     * Explain на дадена заявка
     */
    public function act_Explain()
    { 
        requireRole('debug');

        $id = Request::get('id', 'int');

        $rec = $this->fetch($id);

        expect(substr($rec->query, 0, 6) == 'SELECT');

        $query = 'EXPLAIN ' . $rec->query;
        
        // id 	select_type 	table 	type 	possible_keys 	key 	key_len 	ref 	rows 	Extra 

        $dbRes = $this->db->query($query);
        
        $html = "<div style='padding:0.5em'><h1>Обясняване на избора на ключове</h1>

        <h3>Заявка:</h3>
        <code>{$query}</code>";

        if ($dbRes && $this->db->numRows($dbRes)) {
            $html .= "<table class='listTable' style='margin-top:1em''>
                          <tr>
                            <td>id</td>
                            <td>select_type</td>
                            <td>table</td>
                            <td>type</td>
                            <td>possible_keys</td>
                            <td>key</td>
                            <td>key_len</td>
                            <td>ref</td>
                            <td>rows</td>
                            <td>Extra</td>
                          </tr>";
            while ($rec = $this->db->fetchObject($dbRes)) {
                $html .= "<tr>
                            <td>{$rec->id}</td>
                            <td>{$rec->select_type}</td>
                            <td>{$rec->table}</td>
                            <td>{$rec->type}</td>
                            <td>{$rec->possible_keys}</td>
                            <td>{$rec->key}</td>
                            <td>{$rec->key_len}</td>
                            <td>{$rec->ref}</td>
                            <td>{$rec->rows}</td>
                            <td>{$rec->Extra}</td>
                          </tr>";
            }
            $html .= '</table>';
        } else {
            $html .= '<div>Няма резултати</div>';
        }

        $html .= '</div>';
        
        $html = $this->renderWrapping($html);

        return $html;
    }


    public function act_UnusedIndexes()
    {
        requireRole('debug');
 
        $query =   "SELECT
                      ps.object_name AS object_name,
                      ps.index_name AS index_name
                    FROM performance_schema.table_io_waits_summary_by_index_usage AS ps
                    JOIN information_schema.STATISTICS AS stats
                      ON ps.object_schema = stats.TABLE_SCHEMA
                      AND ps.object_name = stats.TABLE_NAME
                      AND ps.index_name = stats.INDEX_NAME
                    WHERE ps.index_name IS NOT NULL
                      AND ps.count_star = 0
                      AND ps.OBJECT_SCHEMA = '{$this->db->dbName}'
                      AND stats.INDEX_TYPE != 'FULLTEXT'
                    ORDER BY ps.object_schema, ps.object_name, ps.index_name;";
        
        $dbRes = $this->db->query($query);

        $html = "<div style='padding:0.5em'><h1>Неизползвани индекси</h1>";

        if ($dbRes && $this->db->numRows($dbRes)) {
            $html .= "<table class='listTable' style='margin-top:1em''>
                          <tr style='background-color:#aaa; color:white;'>
                            <th><strong>Таблица</strong></th>
                            <th><strong>Индекс</strong></th>
                          </tr>";
            while ($rec = $this->db->fetchObject($dbRes)) {
                if($rec->index_name == NULL || $rec->index_name == 'PRIMARY') continue;

                $html .= "<tr>
                            <td>{$rec->object_name}</td>
                            <td>{$rec->index_name}</td>
                          </tr>";
            }
            $html .= '</table>';
        } else {
            $html .= '<div>Няма резултати</div>';
        }

        $html .= '</div>';

        $html = $this->renderWrapping($html);

        return $html;

    }
    
    
}
