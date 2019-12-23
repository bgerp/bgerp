<?php


/**
 * Дали да се сетват стойности при всяка заявка.
 * Ако е FALSE, трябва да  се сетнат преди това в настройките
 * SET CHARACTER_SET_RESULTS=utf8, COLLATION_CONNECTION=utf8_bin, CHARACTER_SET_CLIENT=utf8, SQL_MODE = '';"
 */
defIfNot('EF_DB_SET_PARAMS', true);


/**
 * SQL енджина по подразбиране
 * Ако се промени на `InnoDB` в `index.cfg.php` трябва да се сетне `set global innodb_flush_log_at_trx_commit = 0;`,
 * защото оптимизирането на таблиците по крон става много бавно. Или да се спре този процес (`OptimizeTables`).
 */
defIfNot('CORE_SQL_DEFAULT_ENGINE', 'MYISAM');


/**
 * Задава кодировката на базата данни по подразбиране
 */
defIfNot('EF_DB_CHARSET', 'utf8mb4');


/**
 * Задава колацията на базата данни по подразбиране
 */
defIfNot('EF_DB_COLLATION', EF_DB_CHARSET == 'utf8mb4' ? 'utf8mb4_bin' : 'utf8_bin');


/**
 * Задава кодировката на клиента (PHP скрипта) за базата данни по подразбиране
 */
defIfNot('EF_DB_CHARSET_CLIENT', EF_DB_CHARSET);


/**
 * С колко максимално символа да участват в индексите полетата varchar
 */
defIfNot('EF_DB_VARCHAR_INDEX_PREFIX', EF_DB_CHARSET == 'utf8mb4' ? 100 : 255);


/**
 * Път по подразбиране за създаване на репликация
 */
defIfNot('BGERP_SQL_LOG_PATH', false);


/**
 * Клас 'core_Db' - Манипулиране на MySQL-ски бази данни
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Db
{
    /**
     * Името на БД
     *
     * @var string
     */
    public $dbName;
    
    
    /**
     * Потребителя към БД
     *
     * @var string
     */
    public $dbUser;
    
    
    /**
     * Парола за БД
     *
     * @var string
     */
    public $dbPass;
    
    
    /**
     * Сървър за БД
     *
     * @var string
     */
    public $dbHost;
    
    
    /**
     * @var string
     * @access private
     */
    public $link;
    
    
    /**
     * Глобална константа за всички линкове
     */
    public static $links = array();
    
    
    /**
     * Разрешен ли е SQL лога?
     */
    public static $sqlLogEnebled = true;
    
    
    /**
     * Номер на mySQL код за грешка при липсваща таблица
     */
    const MYSQLI_MISSING_TABLE = 1146;
    
    
    /**
     * Номер на mySQL код за грешка при непозната колона в таблица
     */
    const MYSQLI_UNKNOWN_COLUMN = 1054;
    
    
    /**
     * Конструктор на класа
     */
    public function __construct()
    {
        $this->init();
    }
    
    
    /**
     * Инициализиране на обекта
     *
     * @param string $dbName
     * @param string $user
     * @param string $password
     * @param string $host
     */
    public function init($params = array())
    {
        $this->dbName = EF_DB_NAME;
        $this->dbUser = EF_DB_USER;
        $this->dbPass = EF_DB_PASS;
        $this->dbHost = EF_DB_HOST;
        $this->dbCharset = EF_DB_CHARSET;
        $this->dbCollation = EF_DB_COLLATION;
        $this->dbCharsetClient = EF_DB_CHARSET_CLIENT;
        $this->varcharIndexPrefix = EF_DB_VARCHAR_INDEX_PREFIX;
        
        $params = arr::make($params);
        
        foreach ($params as $name => $value) {
            $this->{$name} = $params[$name];
        }
    }
    
    
    /**
     * Свързване със зададената база данни
     *
     * @return resource
     */
    public function connect($forceDb = false)
    {
        if (!($link = self::$links[$this->dbHost][$this->dbUser][$this->dbName])) {
            if (strpos($this->dbHost, ':')) {
                list($host, $port) = explode(':', $this->dbHost);
                $link = new mysqli($host, $this->dbUser, $this->dbPass, '', $port);
            } else {
                $link = new mysqli($this->dbHost, $this->dbUser, $this->dbPass);
            }
            
            self::$links[$this->dbHost][$this->dbUser][$this->dbName] = $link;
            
            if ($err = mysqli_connect_errno()) {
                // Грешка при свързване с MySQL сървър
                error(500, $this->dbHost, $err);
            }
            
            // След успешно осъществяване на връзката изтриваме паролата
            // с цел да не се появи случайно при някой забравен bp()
            unset($this->dbPass);
            
            
            if ($forceDb) {
                $res = $link->query("SHOW DATABASES LIKE '{$this->dbName}'");
                if ($res->num_rows == 0) {
                    $res = $link->query("CREATE DATABASE `{$this->dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                }
            }
            
            if (defined('EF_DB_SET_PARAMS') && (EF_DB_SET_PARAMS !== false)) {
                $sqlMode = "SQL_MODE = ''";
                $link->query("SET CHARACTER_SET_RESULTS={$this->dbCharset}, COLLATION_CONNECTION={$this->dbCollation}, CHARACTER_SET_CLIENT={$this->dbCharsetClient}, {$sqlMode};");
            }
            
            // Избираме указаната база от данни на сървъра
            if (!$link->select_db("{$this->dbName}")) {
                // Грешка при избиране на база
                $dump = array('mysqlErrCode' => $this->link->error_list[0]['errno'], 'mysqlErrMsg' => $this->link->error_list[0]['error'], 'dbName' => $this->dbName, 'dbLink' => $this->link);
                throw new core_exception_Db('500 @Грешка при избиране на база', 'DB Грешка', $dump);
            }
        }
        
        return $link;
    }
    
    
    /**
     * Затваряне на връзката към базата данни и
     * освобождаване на всички заделени ресурси.
     */
    public function disconnect()
    {
        if ($link = self::$links[$this->dbHost][$this->dbUser][$this->dbName]) {
            $link->close();
            unset(self::$links[$this->dbHost][$this->dbUser][$this->dbName]);
        }
    }
    
    
    /**
     * Изпълнение на SQL заявка.
     *
     * Не е необходимо извикването на {@link DB::connect()} преди това.
     * Ако няма осъществена връзка с базата данни, тази функция се опитва
     * първо да направи връзка и след това да изпълни SQL заявката.
     *
     * @param string $sqlQuery
     * @param bool   $silent      Ако е TRUE, функцията не прекъсва изпълнението на
     *                            скрипта и не отпечатва съобщението за грешка на MySQL.
     *                            В този случай извикващия трябва да провери стойностите на
     *                            {$link DB::errno()} и {@link DB::error()} и да реагира според тях
     * @param bool   $replication дали да се записва заявката в лог файл
     *
     * @return resource
     */
    public function query($sqlQuery, $silent = false, $replication = false)
    {
        if (isDebug() && ($fnd = Request::get('_bp')) && stripos(preg_replace('!\\s+!', ' ', $sqlQuery), $fnd) !== false) {
            bp($sqlQuery);
        }
        
        DEBUG::startTimer('DB::query()');
        DEBUG::log("${sqlQuery}");
        
        $link = $this->connect();
        $this->query = $sqlQuery;
        $dbRes = $link->query($sqlQuery);
        
        $this->checkForErrors('изпълняване на заявка', $silent, $link);
        DEBUG::stopTimer('DB::query()');
        
        if ($replication && self::$sqlLogEnebled) {
            if ($link->affected_rows > 0 || stripos($sqlQuery, 'truncate') !== false) {
                core_Backup::addSqlLog($sqlQuery);
            }
        }
        
        return $dbRes;
    }
    
    
    /**
     * Изпълнява многоредова заявка
     */
    public function multyQuery($sqlQueries, $silent = false)
    {
        $res = array();
        $link = $this->connect();
        $this->query = $sqlQueries;
        if ($link->multi_query($sqlQueries)) {
            do {
                if ($result = $link->store_result()) {
                    while ($row = $result->fetch_row()) {
                        $res = $row;
                    }
                    $result->free();
                }
                if (!$link->more_results()) {
                    break;
                }
            } while ($link->next_result());
        }
        
        $this->checkForErrors('изпълняване на заявка', $silent, $link);
        
        return $res;
    }
    
    
    /**
     * Връща броя записи, върнати от SELECT заявка.
     *
     * @param resource $handle резултат на функцията {@link DB::query()}, извикана със SELECT заявка.
     *
     * @return int
     */
    public function numRows($dbRes)
    {
        $numRows = $dbRes->num_rows;
        
        return $numRows;
    }
    
    
    /**
     * Връща броя на засегнатите редове при последната UPDATE, DELETE, INSERT или REPLACE заявка
     *
     * @return int
     */
    public function affectedRows()
    {
        $link = $this->connect();
        
        return $link->affected_rows;
    }
    
    
    /**
     * Връща id-то (Primary Key) на записа, които е бил последен вмъкнат чрез INSERT заявка.
     *
     * @param resource $handle резултат на функцията {@link DB::query()}, извикана с INSERT заявка.
     *
     * @return mixed
     */
    public function insertId($silent = null)
    {
        $link = $this->connect();
        
        $insertId = $link->insert_id;
        
        $this->checkForErrors('определяне индекса на последния вмъкнат ред', $silent, $link);
        
        return $insertId;
    }
    
    
    /**
     * Връща един запис, под формата на обект
     *
     * @param resource $handle резултат на функцията {@link DB::query()}, извикана със SELECT заявка.
     *
     * @return object
     */
    public function fetchObject($dbRes)
    {
        if ($dbRes) {
            $res = $dbRes->fetch_object();
            $this->checkForErrors('извличане от резултата');
            
            return $res;
        }
    }
    
    
    /**
     * Връща един запис, под формата на масив
     *
     * @param resource $dbRes      резултат на функцията {@link DB::query()}, извикана със SELECT заявка.
     * @param int      $resultType една от предефинираните константи MYSQLI_ASSOC или MYSQLI_NUM
     *
     * @return array В зависимост от $resultType, индексите на този масив са или цели числа (0, 1, ...) или стрингове
     */
    public function fetchArray($dbRes, $resultType = MYSQLI_ASSOC)
    {
        if ($dbRes) {
            $res = $dbRes->fetch_array($resultType);
            $this->checkForErrors('извличане от резултата');
            
            return $res;
        }
    }
    
    
    /**
     * Връща времето на последна промяна (Last Modified Time - LMT) на
     * таблица във формат UNIXTIMESTAMP
     *
     * @param string $table Таблицата, която изследваме
     */
    public function getLMT($table)
    {
        $dbRes = $this->query("SHOW TABLE STATUS LIKE \"${table}\"");
        $lmt = 0;
        
        if ($this->numRows($dbRes) == 1) {
            $res = $dbRes->fetch_array(MYSQLI_ASSOC);
            $lmt = $res['Update_time'];
            
            $year = $month = $day = $hour = $min = $sec = 0;
            
            if (sscanf($lmt, '%4d-%2d-%2d %2d:%2d:%2d', $year, $month, $day, $hour, $min, $sec) == 6) {
                $lmt = mktime($hour, $min, $sec, $month, $day, $year);
            }
        }
        $this->freeResult($dbRes);
        
        return $lmt;
    }
    
    
    /**
     * Освобождава ресурсите, асоциирани с $handle
     *
     * @param resource $handle резултат на функцията {@link DB::query()}, извикана със SELECT заявка.
     */
    public function freeResult($dbRes)
    {
        if ($dbRes instanceof MYSQLI_RESULT) {
            $dbRes->free();
            $dbRes = null;
        }
    }
    
    
    /**
     * Има ли такава таблица текущата БД?
     *
     * @param string $tableName
     *
     * @return bool
     */
    public function tableExists($tableName)
    {
        $tableName = $this->escape($tableName);
        
        $dbRes = $this->query("SHOW TABLES LIKE '{$tableName}'", true);
        
        $res = $dbRes->num_rows > 0;
        
        $this->freeResult($dbRes);
        
        return $res;
    }
    
    
    /**
     * Има ли таблицата такова поле?
     */
    public function isFieldExists($tableName, $fieldName)
    {
        $tableName = $this->escape($tableName);
        $fieldName = $this->escape($fieldName);
        
        $dbRes = $this->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$this->dbName}' AND TABLE_NAME='{$tableName}' AND column_name='{$fieldName}'");
        
        $res = $dbRes->num_rows > 0;
        
        $this->freeResult($dbRes);
        
        return $res;
    }
    
    
    /**
     * Създава таблица в БД, ако тя вече не е създадена.
     */
    public function forceTable($tableName, $params = array(), &$debugLog = '')
    {
        // Установяване на параметрите по подразбиране
        setIfNot($params, array(
            'ENGINE' => CORE_SQL_DEFAULT_ENGINE,
            'CHARACTER' => $this->dbCharset,
            'COLLATION' => $this->dbCollation
        ));
        
        // Ако таблицата съществува, връщаме сигнал, че нищо не сме направили
        if ($res = $this->tableExists($tableName)) {
            $tableName = $this->escape($tableName);
            
            $dbRes = $this->query("SHOW TABLE STATUS LIKE '{$tableName}'", true);
            $tableParams = $dbRes->fetch_array(MYSQLI_ASSOC);
            expect(is_array($tableParams), $tableParams);
            foreach ($tableParams as $key => $value) {
                $key = strtoupper($key);
                if (isset($params[$key]) && strtoupper($params[$key]) != strtoupper($value)) {
                    if ($key == 'ENGINE') {
                        $dbRes = $this->query("ALTER TABLE `{$tableName}` ENGINE " . $params['ENGINE'] . ';', true, true);
                        $debugLog .= "<li class='debug-new'>Сменен DB ENGINE=" . strtoupper($params['ENGINE']) . '</li>';
                    }
                    if ($key == 'COLLATION') {
                        $dbRes = $this->query("ALTER TABLE `{$tableName}` COLLATE " . $params['COLLATION'] . ';', true, true);
                        $debugLog .= "<li class='debug-new'>Сменен COLLATE=" . strtoupper($params['COLLATION']) . '</li>';
                    }
                }
            }
            
            return false;
        }
        
        // Правим допълнителните параметри към заявката
        $params = 'ENGINE = ' . $params['ENGINE'] . ' CHARACTER SET =' . $params['CHARACTER'] . ' COLLATE ' . $params['COLLATION'] . ';';
        
        $dbRes = $this->query("CREATE TABLE `${tableName}` (`id` INT UNSIGNED AUTO_INCREMENT, PRIMARY KEY(`id`)) {$params}", false, true);
        
        return true;
    }
    
    
    /**
     * Връща посочената MySQL променлива
     */
    public function getVariable($name)
    {
        $query = "SHOW VARIABLES LIKE '{$name}'";
        
        $dbRes = $this->query($query);
        
        if (!$dbRes) {
            
            return false;
        }
        
        // Извличаме резултата
        $res = $this->fetchObject($dbRes);
        
        return $res->Value;
    }
    
    
    /**
     * Връща следващото id за дадения модел
     */
    public function getNextId($tableName)
    {
        $query = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$this->dbName}' AND TABLE_NAME = '{$tableName}'";
        
        $dbRes = $this->query($query);
        
        if (!$dbRes) {
            
            return false;
        }
        
        // Извличаме резултата
        $res = $this->fetchObject($dbRes);
        
        return $res->AUTO_INCREMENT;
    }
    
    
    /**
     * Връща атрибутите на посоченото поле от таблицата
     */
    public function getFieldAttr($tableName, $fieldName)
    {
        $query = "SHOW FULL COLUMNS FROM `{$tableName}` LIKE '{$fieldName}'";
        
        $dbRes = $this->query($query);
        
        if (!$dbRes) {
            
            return false;
        }
        
        // Извличаме резултата
        $arr = $this->fetchArray($dbRes);
        $this->freeResult($dbRes);
        
        // Ако няма атрибути - връщаме сигнал, че полето не съществува
        if (!$arr) {
            
            return false;
        }
        
        $res = new stdClass();
        
        // Правим всички имена на атрибути с малки букви
        foreach ($arr as $key => $val) {
            $key = strtolower($key);
            $res->{$key} = $val;
        }
        
        // Ако имаме скоба, значи имаме $options или $size
        if ($bc = strpos($res->type, '(')) {
            
            // Отделяме това, което е между скобите
            $rest = substr($res->type, $bc);
            $rest = trim($rest, '()');
            
            // В частта до скобата имаме името на типа
            $res->type = strtoupper(substr($res->type, 0, $bc));
            
            // Ако типа е ENUM или SET то след скобите имаме options
            if ($this->isType($res->type, 'have_options')) {
                // Три места
                // in, out, esc
                $part = 'out';
                $optInd = 0;
                $len = strlen($rest);
                
                for ($i = 0; $i < $len; $i++) {
                    $c = $rest{$i};
                    
                    if ($part == 'out') {
                        if ($c == "'") {
                            $part = 'in';
                        } elseif ($c == ',') {
                            $optInd++;
                        }
                    } elseif ($part == 'in') {
                        if ($c == "'") {
                            if ($rest{$i + 1} == "'") {
                                $i = $i + 1;
                                $res->options[$optInd] .= $c;
                            } else {
                                $res->options[$optInd] .= '';
                                $part = 'out';
                            }
                        } else {
                            $res->options[$optInd] .= $c;
                        }
                    }
                }
            } else {
                $rest = explode(')', $rest);
                
                $res->size = trim($rest[0]);
                
                if ($rest[1]) {
                    $res->unsigned = (strpos(strtolower($rest[1]), 'unsigned') !== false);
                }
            }
        }
        
        // Правим типа с главни букви
        $res->type = strtoupper($res->type);
        
        // Конвертираме Yes/No стойността на ->null към TRUE/FALSE
        $res->notNull = (strpos(strtolower($res->null), 'no') !== false);
        
        return $res;
    }
    
    
    /**
     * Има ли типа 'unsigned' параметър?
     */
    public function isType($type, $param)
    {
        $types['can_be_unsigned'] = arr::make('TINYINT,SMALLINT,MEDIUMINT,INT,INTEGER,BIGINT,FLOAT,DOUBLE,DOUBLE PRECISION,REAL,DECIMAL');
        $types['have_options'] = arr::make('ENUM,SET');
        $types['have_len'] = arr::make('CHAR,VARCHAR,DECIMAL,VARBINARY');
        $types['have_collation'] = arr::make('TINYTEXT,TEXT,MEDIUMTEXT,LONGTEXT,CHAR,VARCHAR,ENUM');
        
        expect($types[$param], 'Wrong param for isType', $param);
        
        return in_array($type, $types[$param]);
    }
    
    
    /**
     * Създава, актуализира поле с посочените параметри
     */
    public function forceField($tableName, $field)
    {
        // всички параметри на полето, трябва да са с големи букви
        
        
        if ($this->isType($field->type, 'have_options')) {
            foreach ($field->options as $opt) {
                $typeInfo .= ($typeInfo ? ',' : '') . "'" . str_replace("'", '\\' . "'", $opt) . "'";
            }
            $typeInfo = "(${typeInfo})";
        } elseif ($this->isType($field->type, 'have_len')) {
            $typeInfo = "({$field->size})";
        }
        
        $default = $notNull = $unsigned = $collation = '';
        
        if ($field->collation) {
            $collation = " COLLATE {$field->collation}";
        }
        
        if ($field->unsigned) {
            $unsigned = ' UNSIGNED';
        }
        
        
        if ($field->notNull) {
            $notNull = ' NOT NULL';
        }
        
        if ($field->default !== null) {
            $default = " DEFAULT '{$field->default}'";
        }
        
        if (strtolower($field->name) == 'id') {
            $autoIncrement = ' AUTO_INCREMENT';
            $default = '';
            $notNull = '';
        }
        
        if ($field->field) {
            
            return $this->query("ALTER TABLE `{$tableName}` CHANGE `{$field->field}` `{$field->name}` {$field->type}{$typeInfo}{$collation}{$unsigned}{$autoIncrement}{$notNull}{$default}", false, true);
        }
        
        return $this->query("ALTER TABLE `{$tableName}` ADD `{$field->name}` {$field->type}{$typeInfo}{$collation}{$unsigned}{$autoIncrement}{$notNull}{$default}", false, true);
    }
    
    
    /**
     * Създава индекс, с указаното име, като преди това премахва евентуално индекс със същото име
     */
    public function forceIndex($tableName, $fieldsList, $type = 'INDEX', $indexName = null)
    {
        $res = null;
        
        $fieldsList = arr::make($fieldsList);
        
        if (!$indexName) {
            $indexName = str::phpToMysqlName(current($fieldsList));
        }
        
        // Ако вече имаме индекс с подобно име, дропим го
        $indexes = $this->getIndexes($tableName);
        
        if ($indexes[$indexName]) {
            $this->query("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`", false, true);
            $res = true;
        }
        
        // Ако типът е DROP - не създаваме нов индекс
        if ($type == 'DROP') {
            
            return;
        }
        
        if (count($fieldsList)) {
            foreach ($fieldsList as $f) {
                list($name, $len) = explode('(', $f);
                
                $name = str::phpToMysqlName($name);
                
                if ($len) {
                    $fields .= ($fields ? ',' : '') . "`{$name}`({$len}\n";
                } else {
                    $fields .= ($fields ? ',' : '') . "`{$name}`\n";
                }
            }
            
            // Създаване на Индекса
            $this->query("ALTER TABLE `{$tableName}` ADD {$type} `{$indexName}` (\n{$fields})", false, true);
            $res = true;
        }
        
        return $res;
    }
    
    
    /**
     * Връща полетата и типовете им в една таблица
     *
     *
     * @param string $tableName
     *
     * @return array
     */
    public function getFields($tableName)
    {
        $fields = array();
        
        $dbRes = $this->query("SHOW FIELDS FROM {$tableName}");
        
        if ($this->numRows($dbRes)) {
            while ($rec = $this->fetchObject($dbRes)) {
                $fields[str::mysqlToPhpName($rec->Field)] = $rec;
            }
        }
        
        return $fields;
    }
    
    
    /**
     * Връща масив с индексите на таблицата
     */
    public function getIndexes($tableName)
    {
        $indexes = array();
        
        $dbRes = $this->query("SHOW INDEXES FROM {$tableName}");
        
        if ($this->numRows($dbRes)) {
            while ($rec = $this->fetchObject($dbRes)) {
                $name = $rec->Key_name;
                
                if ($name == 'PRIMARY') {
                    $type = 'PRIMARY';
                } elseif ($rec->Index_type == 'FULLTEXT') {
                    $type = 'FULLTEXT';
                } elseif ($rec->Non_unique) {
                    $type = 'INDEX';
                } else {
                    $type = 'UNIQUE';
                }
                
                $indexes[$name][$type][str::mysqlToPhpName($rec->Column_name)] = $rec->Sub_part ? $rec->Sub_part : true;
            }
        }
        
        return $indexes;
    }
    
    
    /**
     * Преброява редовете в една MySQL таблица
     */
    public function countRows($table)
    {
        $dbRes = $this->query("SELECT COUNT(*) AS cnt FROM `{$table}`");
        $res = $this->fetchObject($dbRes);
        $count = $res->cnt;
        
        return $count;
    }
    
    
    /**
     * Проверява за грешки при последната MySQL операция
     *
     * Реагира по следния начин:
     * Ако имаме липсваща таблица или липсваща колона
     * връща грешката
     * на ядрото и на приложението.
     *
     *
     * @return int нула означава липса на грешка.
     */
    public function checkForErrors($action, $silent = false, $link = null)
    {
        if (!$link) {
            $link = $this->connect();
        }
        
        if (is_array($link->error_list) && count($link->error_list) > 0) {
            if (!$link->errno) {
                $link->errno = $link->error_list[0]['errno'];
            }
            
            if (!$link->error) {
                $link->error = $link->error_list[0]['error'];
            }
        }
        
        if ($link->errno) {
            // Грешка в базата данни
            $dump = array('query' => $this->query, 'mysqlErrCode' => $link->errno, 'mysqlErrMsg' => $link->error, 'dbLink' => $link);
            throw new core_exception_Db("500 @Грешка при {$action}", 'DB Грешка', $dump);
        }
        
        return $link->errno;
    }
    
    
    /**
     * Ескейпва служебните символи в MySQl стойности
     *
     * @param string $value
     *
     * @return string
     */
    public function escape($value)
    {
        $link = $this->connect();
        
        expect(is_scalar($value) || !$value, $value);
        
        return $link->real_escape_string($value);
    }
    
    
    /**
     * Връща информация за посочената таблица
     */
    public function getTableInfo($tableName, $part = null)
    {
        $dbRes = $this->query("SELECT SQL_NO_CACHE *
                               FROM INFORMATION_SCHEMA.TABLES 
                                WHERE TABLE_SCHEMA = '" . $this->escape($this->dbName) ."' AND TABLE_NAME='{$tableName}'", true);
        
        if (!is_object($dbRes) || !$dbRes->num_rows) {
            
            return false;
        }
        
        $res = $this->fetchArray($dbRes);
        $this->freeResult($dbRes);
        
        $res = array_change_key_case($res, CASE_UPPER);
        
        if ($part) {
            $part = strtoupper($part);
            expect(array_key_exists($part, $res));
            
            $res = $res[$part];
        }
        
        return $res;
    }
    
    
    /**
     * Връща информация за таблиците в БД
     *
     * @param string $part Коя част от резултата да се върне. Ако не е посочена, връща масив с всички
     *
     * @return array|int|false
     *                         o ['Name'] - dbName
     *                         o ['Rows'] - брой редове
     *                         o ['Size'] - размер
     */
    public function getDBInfo($part = null)
    {
        $dbRes = $this->query("SELECT 
                                  `table_schema` as 'Name', 
                                   SUM(1) as `TABLES`, SUM(`TABLE_ROWS`) as 'ROWS',
        					       SUM(`DATA_LENGTH` + `INDEX_LENGTH`) as 'SIZE'
        					   FROM `information_schema`.`tables`
        					   WHERE `table_schema` = '" . $this->escape($this->dbName) ."'", true);
        
        if (!is_object($dbRes)) {
            
            return false;
        }
        
        $res = $this->fetchArray($dbRes);
        $this->freeResult($dbRes);
        
        $res = array_change_key_case($res, CASE_UPPER);
        
        if ($part) {
            $part = strtoupper($part);
            expect(array_key_exists($part, $res));
            
            $res = $res[$part];
        }
        
        return $res;
    }
}
