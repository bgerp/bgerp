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
        $this->FLD('query', 'varchar(16000,collate=ascii_bin)', 'caption=Заявка');
        $this->FLD('time', 'float', 'caption=Време->Общо');
        $this->FLD('timeAvg', 'float', 'caption=Време->Средно');
        $this->FLD('timeMax', 'float', 'caption=Време->Макс.');
        $this->FLD('cnt', 'int', 'caption=Брой');
        $this->FLD('stack', 'varchar(128,collate=ascii_bin)', 'caption=Стек');


        $this->setDbUnique('crc');
    }


    /**
     * Добавя запис на прихваната заявка в буфера
     */
    public static function add($query, $time)
    {
        $crc = self::getCrc32($query);
        $query = substr($query, 0, 16000);
        if(isset(self::$buffer[$crc])) {
            self::$buffer[$crc]->time += $time;
            self::$buffer[$crc]->cnt++;
            if($time > self::$buffer[$crc]->timeMax) {
                self::$buffer[$crc]->timeMax = $time;
                self::$buffer[$crc]->query = $query;
            }
        } else {
            self::$buffer[$crc] = (object) array('crc' => $crc, 'query' => $query, 'time' => $time, 'timeMax' => $time, 'cnt' => 1, 'stack' => self::getStack());
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
                    if(!$exRec->stack) {
                        $exRec->stack = $rec->stack;
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
     * Връща класовете и местата в тах, които водят до тази заявка
     */
    private static function getStack()
    {
        static $fileCoreDb, $fileLogMysql, $fileCoreQuery;
        
        if(!$fileCoreDb) {
            $fileCoreDb = str_replace('/', DIRECTORY_SEPARATOR, getFullPath('core/Db.class.php'));
            $fileCoreQuery = str_replace('/', DIRECTORY_SEPARATOR, getFullPath('core/Query.class.php'));
            $fileLogMysql = str_replace('/', DIRECTORY_SEPARATOR, getFullPath('log/Mysql.class.php'));
        }

        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 7);

        $res = '';

        foreach($stack as $s) {
           if($s['file'] == $fileCoreDb || $s['file'] == $fileCoreQuery || $s['file'] == $fileLogMysql) continue;
            $res .=  $s['file'] . '|' . $s['line'] . ',';             
        }

        $res = substr($res, 0, 128);

        return $res;
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
        $rootPath = strlen(EF_ROOT_PATH)+1;
 
        if(substr($rec->query, 0, 6) == 'SELECT' && strlen($rec->query) < 16000) {
            $url = toUrl(array('log_Mysql', 'Explain', $rec->id));
            $row->query = "<a href='{$url}' target=_blank>SELECT</a>" . substr($rec->query, 6);
        }

        $stack = explode(',', $rec->stack);
 
        $row->stack = '';

        foreach($stack as $i => $s) {
            if($i == count($stack) - 1) break;
            list($file, $line) = explode('|', $s);
            $line = trim($line);
            $s = str_replace(array(DIRECTORY_SEPARATOR, '.class.php'), array('_', ''), substr($file, $rootPath)) . ':' . $line;
            $row->stack .= core_Debug::getEditLink($file, $line, $s) . ' ';
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
            $data->toolbar->addBtn('Системни променливи', array($mvc, 'SystemVariables', 'ret_url' => true));
            $data->toolbar->addBtn('Статистика по таблици', array($mvc, 'TableIoStats', 'ret_url' => true));
            $data->toolbar->addBtn('Кеш на заявките', array($mvc, 'QueryCacheStatus', 'ret_url' => true));
           
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
     * Explain на дадена заявка с допълнителна информация за таблицата
     */
    public function act_Explain()
    {
        requireRole('debug');

        $id = Request::get('id', 'int');

        $rec = $this->fetch($id);

        expect(substr($rec->query, 0, 6) == 'SELECT');

        $query = 'EXPLAIN ' . $rec->query;

        $dbRes = $this->db->query($query);

        $html = "<div style='padding:0.5em'><h1>Обясняване на избора на ключове</h1>

        <h3>Заявка:</h3>
        <code>{$query}</code>";

        $tables = array(); // Масив за съхранение на имената на таблиците

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
 
                // Събиране на имената на таблиците
                if (!empty($rec->table) && $rec->table != 'NULL' && !in_array($rec->table, $tables) && $rec->table[0] != '<') {
                    $tables[] = $rec->table;
                }
            }
            $html .= '</table>';
        } else {
            $html .= '<div>Няма резултати</div>';
        }

        // Добавяне на информация за всяка таблица
        foreach ($tables as $table) {
            $html .= "<h2>Информация за таблицата: {$table}</h2>";

            // Структура на таблицата
            $html .= "<h3>Структура на таблицата</h3>";
            $describeQuery = "DESCRIBE `{$table}`;";
            $describeRes = $this->db->query($describeQuery);
            if ($describeRes && $this->db->numRows($describeRes)) {
                $html .= "<table class='listTable' style='margin-top:1em'>
                              <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                              </tr>";
                while ($descRec = $this->db->fetchObject($describeRes)) {
                    $html .= "<tr>
                                <td>{$descRec->Field}</td>
                                <td>{$descRec->Type}</td>
                                <td>{$descRec->Null}</td>
                                <td>{$descRec->Key}</td>
                                <td>{$descRec->Default}</td>
                                <td>{$descRec->Extra}</td>
                              </tr>";
                }
                $html .= '</table>';
            } else {
                $html .= '<div>Няма информация за структурата</div>';
            }

            // Индекси и тяхната кардиналност
            $html .= "<h3>Индекси и тяхната кардиналност</h3>";
            $indexQuery = "SHOW INDEX FROM `{$table}`;";
            $indexRes = $this->db->query($indexQuery);
            if ($indexRes && $this->db->numRows($indexRes)) {
                $html .= "<table class='listTable' style='margin-top:1em'>
                              <tr>
                                <th>Key name</th>
                                <th>Non unique</th>
                                <th>Column name</th>
                                <th>Collation</th>
                                <th>Cardinality</th>
                                <th>Sub part</th>
                                <th>Packed</th>
                                <th>Null</th>
                                <th>Index type</th>
                                <th>Comment</th>
                              </tr>";
                while ($indexRec = $this->db->fetchObject($indexRes)) {
                    $html .= "<tr>
                                <td>{$indexRec->Key_name}</td>
                                <td>{$indexRec->Non_unique}</td>
                                <td>{$indexRec->Column_name}</td>
                                <td>{$indexRec->Collation}</td>
                                <td>{$indexRec->Cardinality}</td>
                                <td>{$indexRec->Sub_part}</td>
                                <td>{$indexRec->Packed}</td>
                                <td>{$indexRec->Null}</td>
                                <td>{$indexRec->Index_type}</td>
                                <td>{$indexRec->Comment}</td>
                              </tr>";
                }
                $html .= '</table>';
            } else {
                $html .= '<div>Няма информация за индексите</div>';
            }

            // Брой записи в таблицата
            $html .= "<h3>Брой записи в таблицата</h3>";
            $countQuery = "SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$this->db->dbName}' AND TABLE_NAME = '{$table}';";
            $countRes = $this->db->query($countQuery);
            if ($countRes && $this->db->numRows($countRes)) {
                $countRec = $this->db->fetchObject($countRes);
                $html .= "<div>Брой записи: {$countRec->TABLE_ROWS}</div>";
            } else {
                $html .= '<div>Няма информация за броя записи</div>';
            }

            // Брой четения и записвания
            $html .= "<h3>Брой четения и записвания от последното включване</h3>";
            $ioQuery = "SELECT COUNT_READ, COUNT_WRITE FROM performance_schema.table_io_waits_summary_by_table WHERE OBJECT_SCHEMA = '{$this->db->dbName}' AND OBJECT_NAME = '{$table}';";
            $ioRes = $this->db->query($ioQuery);
            if ($ioRes && $this->db->numRows($ioRes)) {
                $ioRec = $this->db->fetchObject($ioRes);
                $html .= "<div>Четения: {$ioRec->COUNT_READ}</div>";
                $html .= "<div>Записвания: {$ioRec->COUNT_WRITE}</div>";
            } else {
                $html .= '<div>Няма информация за броя четения и записвания</div>';
            }
        }

        $html .= '</div>';

        $html = $this->renderWrapping($html);

        return $html;
    }


    public function act_UnusedIndexes()
    {
        requireRole('debug');

        $dbName = $this->db->dbName;

        $query = "
        SELECT
            ps.object_name AS object_name,
            ps.index_name AS index_name
        FROM
            performance_schema.table_io_waits_summary_by_index_usage AS ps
        JOIN
            information_schema.STATISTICS AS stats
            ON ps.object_schema = stats.TABLE_SCHEMA
            AND ps.object_name = stats.TABLE_NAME
            AND ps.index_name = stats.INDEX_NAME
        JOIN
            information_schema.TABLES AS t
            ON ps.object_schema = t.TABLE_SCHEMA
            AND ps.object_name = t.TABLE_NAME
        JOIN
            performance_schema.table_io_waits_summary_by_table AS pstab
            ON ps.object_schema = pstab.OBJECT_SCHEMA
            AND ps.object_name = pstab.OBJECT_NAME
        WHERE
            ps.index_name IS NOT NULL
            AND ps.count_star = 0
            AND ps.OBJECT_SCHEMA = '{$dbName}'
            AND stats.INDEX_TYPE != 'FULLTEXT'
            AND t.TABLE_ROWS >= 1000
            AND pstab.COUNT_READ > 1000
        ORDER BY
            ps.object_schema, ps.object_name, ps.index_name;
        ";

        $dbRes = $this->db->query($query);

        $html = "<div style='padding:0.5em'><h1>Неизползвани индекси</h1>";

        if ($dbRes && $this->db->numRows($dbRes)) {
            $html .= "<table class='listTable' style='margin-top:1em'>
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
    

    public function act_TableIoStats()
    {
        requireRole('debug');

        $query = "SELECT
                    ps.OBJECT_NAME AS object_name,
                    ps.COUNT_READ AS count_read,
                    ps.COUNT_WRITE AS count_write
                  FROM performance_schema.table_io_waits_summary_by_table AS ps
                  WHERE ps.OBJECT_SCHEMA = '{$this->db->dbName}'
                  ORDER BY ps.OBJECT_NAME;";

        $dbRes = $this->db->query($query);

        $html = "<div style='padding:0.5em'><h1>Статистика на таблиците</h1>";

        if ($dbRes && $this->db->numRows($dbRes)) {
            $html .= "<table class='listTable' style='margin-top:1em'>
                          <tr style='background-color:#aaa; color:white;'>
                            <th><strong>Таблица</strong></th>
                            <th><strong>Брой четения</strong></th>
                            <th><strong>Брой записвания</strong></th>
                          </tr>";
            while ($rec = $this->db->fetchObject($dbRes)) {
                $html .= "<tr>
                            <td>{$rec->object_name}</td>
                            <td>{$rec->count_read}</td>
                            <td>{$rec->count_write}</td>
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

    public function act_QueryCacheStatus()
    {
        requireRole('debug');

        $query = "SHOW STATUS LIKE 'Qcache%';";
        $dbRes = $this->db->query($query);

        $html = "<div style='padding:0.5em'><h1>Състояние на MariaDB Query Cache</h1>";

        if ($dbRes && $this->db->numRows($dbRes)) {
            $html .= "<table class='listTable' style='margin-top:1em'>
                          <tr style='background-color:#aaa; color:white;'>
                            <th><strong>Параметър</strong></th>
                            <th><strong>Стойност</strong></th>
                          </tr>";
            while ($rec = $this->db->fetchObject($dbRes)) {
                $html .= "<tr>
                            <td>{$rec->Variable_name}</td>
                            <td>{$rec->Value}</td>
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

    public function act_SystemVariables()
    {
        requireRole('debug');

        $query = "SHOW VARIABLES;";
        $dbRes = $this->db->query($query);

        $html = "<div style='padding:0.5em'><h1>Системни променливи</h1>";

        if ($dbRes && $this->db->numRows($dbRes)) {
            $html .= "<table class='listTable' style='margin-top:1em'>
                          <tr style='background-color:#aaa; color:white;'>
                            <th><strong>Променлива</strong></th>
                            <th><strong>Стойност</strong></th>
                          </tr>";
            while ($rec = $this->db->fetchObject($dbRes)) {
                
                $html .= "<tr>
                            <td>{$rec->Variable_name}</td>
                            <td style='max-width:30em'>{$rec->Value}</td>
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
    

    /**
     * Извлича имена за таблици от заявка
     */
    function extractTableNames($query)
    {
        $tables = array();

        // Премахване на нови редове и допълнителни интервали
        $query = preg_replace('/\s+/', ' ', $query);

        // Регулярен израз за извличане на FROM клауза
        if (preg_match('/\bFROM\b\s+(.*?)\s*(\bWHERE\b|\bGROUP BY\b|\bORDER BY\b|\bHAVING\b|\bLIMIT\b|$)/i', $query, $matches)) {
            $from_clause = $matches[1];

            // Разделяне на таблиците по запетая, игнорирайки запетаи в скоби
            $pattern = '/,(?![^(]*\))/';
            $table_parts = preg_split($pattern, $from_clause);

            foreach ($table_parts as $part) {
                $part = trim($part);

                // Премахване на JOIN клаузи и всичко след тях
                $part = preg_replace('/\b(JOIN|LEFT JOIN|RIGHT JOIN|INNER JOIN|OUTER JOIN|FULL JOIN|CROSS JOIN|NATURAL JOIN)\b.*$/i', '', $part);

                // Премахване на алиаси
                $part = preg_split('/\s+AS\s+/i', $part);
                $part = $part[0];

                $part = preg_split('/\s+/', $part);
                $table_name = $part[0];

                // Премахване на кавички и бектикове
                $table_name = str_replace(array('`', '"', "'"), '', $table_name);

                // Ако има точка (schema.table), взимаме втората част
                $table_parts = explode('.', $table_name);
                if (count($table_parts) > 1) {
                    $table_name = $table_parts[1];
                } else {
                    $table_name = $table_parts[0];
                }

                if (!empty($table_name)) {
                    $tables[] = $table_name;
                }
            }

            // Премахване на дублиращи се имена
            $tables = array_unique($tables);
        }

        return $tables;
    }
    
}
