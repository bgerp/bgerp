<?php



/**
 * Задава кодировката на базата данни по подразбиране
 */
defIfNot('EF_DB_CHARSET', 'utf8');


/**
 * Задава колацията на базата данни по подразбиране
 */
defIfNot('EF_DB_COLLATION', 'utf8_bin');


/**
 * Задава кодировката на клиента (PHP скрипта) за базата данни по подразбиране
 */
defIfNot('EF_DB_CHARSET_CLIENT', 'utf8');


/**
 * Клас 'core_Db' - Манипулиране на MySQL-ски бази данни
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Db extends core_BaseClass
{
    
    
    /**
     * Името на БД
     * @var string
     */
    var $dbName;
    
    
    /**
     * Потребителя към БД
     * @var string
     */
    var $dbUser;
    
    
    /**
     * Парола за БД
     * @var string
     */
    var $dbPass;
    
    
    /**
     * Сървър за БД
     * @var string
     */
    var $dbHost;
    
    
    /**
     * @var string
     * @access private
     */
    var $link;
    
    
    /**
     * @var mySQL result
     */
    var $lastRes;
    
    
    /**
     * Инициализиране на обекта
     * @param string $dbName
     * @param string $user
     * @param string $password
     * @param string $host
     */
    function init($params = array())
    {
        $this->dbName = EF_DB_NAME;
        $this->dbUser = EF_DB_USER;
        $this->dbPass = EF_DB_PASS;
        $this->dbHost = EF_DB_HOST;
        $this->dbCharset = EF_DB_CHARSET;
        $this->dbCollation = EF_DB_COLLATION;
        $this->dbCharsetClient = EF_DB_CHARSET_CLIENT;
        
        parent::init($params);
    }
    
    
    /**
     * Свързване със зададената база данни
     *
     * @return resource
     */
    function connect()
    {
        if (!isset($this->link)) {
            $link = @mysql_connect($this->dbHost, $this->dbUser, $this->dbPass) or
            error("Грешка при свързване с MySQL сървър", mysql_error(), 'ГРЕШКА В БАЗАТА ДАННИ');
            
            // След успешно осъществяване на връзката изтриваме паролата
            // с цел да не се появи случайно при някой забравен bp()
            unset($this->dbPass);
            
            // Запомняме връзката към MySQL сървъра за по-късна употреба
            $this->link = $link;
            
            // Задаваме настройките за символното кодиране на връзката
            mysql_query('set character_set_results=' . $this->dbCharset, $link);
            mysql_query('set collation_connection=' . $this->dbCollation, $link);
            mysql_query('set character_set_client=' . $this->dbCharsetClient, $link);
            
            // Избираме указаната база от данни на сървъра
            mysql_select_db($this->dbName);
        }
        
        return $this->link;
    }
    
    
    /**
     * Затваряне на връзката към базата данни и
     * освобождаване на всички заделени ресурси.
     */
    function disconnect()
    {
        mysql_close($this->link);
        unset($this->link);
    }
    
    
    /**
     * Изпълнение на SQL заявка.
     *
     * Не е необходимо извикването на {@link DB::connect()} преди това.
     * Ако няма осъществена връзка с базата данни, тази функция се опитва
     * първо да направи връзка и след това да изпълни SQL заявката.
     *
     * @param string $sqlQuery
     * @param bool $silent Ако е TRUE, функцията не прекъсва изпълнението на
     * скрипта и не отпечатва съобщението за грешка на MySQL.
     * В този случай извикващия трябва да провери стойностите на
     * {$link DB::errno()} и {@link DB::error()} и да реагира според тях.
     * @return resource
     */
    function query($sqlQuery, $silent = FALSE)
    {
        DEBUG::startTimer("DB::query()");
        DEBUG::log("$sqlQuery");
        
        $this->connect();
        $this->query = $sqlQuery;
        $res = mysql_query($sqlQuery, $this->link);
        
        $this->checkForErrors('изпълняване на заявка', $silent);
        
        $this->lastRes = $res;
        
        DEBUG::stopTimer('DB::query()');
        
        return $res;
    }
    
    
    /**
     * Връща броя записи, върнати от SELECT заявка.
     *
     * @param resource $handle резултат на функцията {@link DB::query()}, извикана със SELECT заявка.
     * @return int
     */
    function numRows($handle = NULL, $silent = FALSE)
    {
        if ($handle == NULL)
        $handle = $this->lastRes;
        $numRows = mysql_num_rows($handle);
        
        $this->checkForErrors('преброяване на резултата', $silent);
        
        return $numRows;
    }
    
    
    /**
     * Връща броя на засегнатите редове при последната UPDATE, DELETE, INSERT или REPLACE заявка
     *
     * @return int
     */
    function affectedRows()
    {
        return mysql_affected_rows($this->link);
    }
    
    
    /**
     * Връща id-то (Primary Key) на записа, които е бил последен вмъкнат чрез INSERT заявка.
     *
     * @param resource $handle резултат на функцията {@link DB::query()}, извикана с INSERT заявка.
     * @return mixed
     */
    function insertId($silent = NULL)
    {
        $insertId = mysql_insert_id($this->link);
        
        $this->checkForErrors('определяне индекса на последния вмъкнат ред', $silent);
        
        return $insertId;
    }
    
    
    /**
     * Връща един запис, под формата на обект
     *
     * @param resource $handle резултат на функцията {@link DB::query()}, извикана със SELECT заявка.
     * @return object
     */
    function fetchObject($handle = NULL, $silent = NULL)
    {
        if ($handle == NULL)
        $handle = $this->lastRes;
        $fetchObject = mysql_fetch_object($handle);
        
        $this->checkForErrors('извличане от резултата', $silent);
        
        return $fetchObject;
    }
    
    
    /**
     * Връща един запис, под формата на масив
     *
     * @param resource $handle резултат на функцията {@link DB::query()}, извикана със SELECT заявка.
     * @param int $resultType една от предефинираните константи MYSL_ASSOC или MYSQL_NUM
     * @return array В зависимост от $resultType, индексите на този масив са или цели числа (0, 1, ...) или стрингове
     */
    function fetchArray($handle = NULL, $resultType = MYSQL_ASSOC)
    {
        if ($handle == NULL)
        $handle = $this->lastRes;
        $r = mysql_fetch_array($handle, $resultType);
        
        return $r;
    }
    
    
    /**
     * Връща времето на последна промяна (Last Modified Time - LMT) на
     * таблица във формат UNIXTIMESTAMP
     *
     * @param string $table Таблицата, която изследваме
     */
    function getLMT($table)
    {
        $dbRes = $this->query("SHOW TABLE STATUS LIKE \"$table\"");
        $lmt = 0;
        
        if ($this->numRows($dbRes) == 1) {
            $lmt = mysql_result($dbRes, 0, 'Update_time');
            
            $year = $month = $day = $hour = $min = $sec = 0;
            
            if (sscanf($lmt, "%4d-%2d-%2d %2d:%2d:%2d", $year, $month, $day, $hour, $min, $sec) == 6) {
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
    function freeResult($handle = NULL)
    {
        if ($handle == NULL)
        $handle = $this->lastRes;
        @mysql_free_result($handle);
    }
    
    
    /**
     * Има ли такава таблица текущата БД?
     * @param string $tableName
     * @return bool
     */
    function tableExists($tableName)
    {
        $dbRes = $this->query("SHOW TABLES", TRUE);
        
        $numTables = $this->numRows($dbRes);
        
        $res = FALSE;
        
        for ($i = 0; $i < $numTables; $i++) {
            if (strtolower($tableName) == mysql_result($dbRes, $i, 0)) {
                $res = TRUE;
                break;
            }
        }
        
        $this->freeResult($dbRes);
        
        return $res;
    }
    
    
    /**
     * Има ли таблицата такова поле?
     */
    function isFieldExists($tableName, $fieldName)
    {
        $dbRes = mysql_list_fields($this->dbName, $tableName, $this->link);
        $numFields = mysql_num_fields($dbRes);
        $res = FALSE;
        
        for ($i = 0; $i < $numFields; $i++) {
            if (mysql_field_name($dbRes, $i) == $fieldName) {
                
                $res = TRUE;
            }
        }
        
        $this->freeResult($dbRes);
        
        return $res;
    }
    
    
    /**
     * Създава таблица в БД, ако тя вече не е създадена.
     */
    function forceTable($tableName, $params = array())
    {
        // Ако таблицата съществува, връщаме сигнал, че нищо не сме направили
        if ($this->tableExists($tableName)) {
            
            return FALSE;
        }
        
        // Установяване на параметрите по подразбиране
        setIfNot($params, array(
                'ENGINE' => 'MYISAM',
                'CHARACTER' => 'utf8',
                'COLLATE' => 'utf8_bin'
            ));
        
        // Правим допълнителните параметри към заявката
        $params = "ENGINE = " . $params['ENGINE'] . " CHARACTER SET =" . $params['CHARACTER'] . " COLLATE " . $params['COLLATE'] . ";";
        
        $this->query("CREATE TABLE `$tableName` (`id` INT UNSIGNED AUTO_INCREMENT, PRIMARY KEY(`id`)) {$params}");
        
        return TRUE;
    }
    
    
    /**
     * Връща атрибутите на посоченото поле от таблицата
     */
    function getFieldAttr($tableName, $fieldName)
    {
        $query = "SHOW FULL COLUMNS FROM `{$tableName}` LIKE '{$fieldName}'";
        
        $dbRes = $this->query($query);
        
        if(!is_resource($dbRes)) {
            
            return FALSE;
        }
        
        // Извличаме резултата
        $arr = $this->fetchArray();
        $this->freeResult($dbRes);
        
        // Ако няма атрибути - връщаме сигнал, че полето не съществува
        if (!$arr) return FALSE;
        
        $res = new stdClass();

        // Правим всички имена на атрибути с малки букви
        foreach($arr as $key => $val) {
            $key = strtolower($key);
            $res->{$key} = $val;
        }
        
        // Ако имаме скоба, значи имаме $options или $size
        if($bc = strpos($res->type, '(')) {
            
            // Отделяме това, което е между скобите
            $rest = substr($res->type, $bc);
            $rest = trim($rest, '()');
            
            // В частта до скобата имаме името на типа
            $res->type = strtoupper(substr($res->type, 0, $bc));
            
            // Ако типа е ENUM или SET то след скобите имаме options
            if($this->isType($res->type, 'have_options')) {
                // Три места
                // in, out, esc
                $part = 'out';
                $optInd = 0;
                $len = strlen($rest);
                
                for($i = 0; $i<$len; $i++) {
                    $c = $rest{$i};
                    
                    if($part == 'out') {
                        if($c == "'") {
                            $part = 'in';
                        } elseif ($c == ',') {
                            $optInd++;
                        }
                    } elseif ($part == 'in') {
                        if($c == "'") {
                            if($rest{$i + 1} == "'") {
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
                $rest = explode(")", $rest);
                
                $res->size = (int) $rest[0];
                
                if($rest[1]) {
                    $res->unsigned = (strpos(strtolower($rest[1]), 'unsigned') !== FALSE);
                }
            }
        }
        
        // Правим типа с главни букви
        $res->type = strtoupper($res->type);
        
        // Конвертираме Yes/No стойността на ->null към TRUE/FALSE
        $res->notNull = (strpos(strtolower($res->null), 'no') !== FALSE);
        
        return $res;
    }
    
    
    /**
     * Има ли типа 'unsigned' параметър?
     */
    function isType($type, $param)
    {
        $types['can_be_unsigned'] = arr::make('TINYINT,SMALLINT,MEDIUMINT,INT,INTEGER,BIGINT,FLOAT,DOUBLE,DOUBLE PRECISION,REAL,DECIMAL');
        $types['have_options'] = arr::make('ENUM,SET');
        $types['have_len'] = arr::make('CHAR,VARCHAR');
        $types['have_collation'] = arr::make('TINYTEXT,TEXT,MEDIUMTEXT,LONGTEXT,CHAR,VARCHAR,ENUM');
        
        expect($types[$param], 'Wrong param for isType', $param);
        
        return in_array($type, $types[$param]);
    }
    
    
    /**
     * Създава, актуализира поле с посочените параметри
     */
    function forceField($tableName, $field)
    {
        // всички параметри на полето, трябва да са с големи букви
        
        
        if ($this->isType($field->type, 'have_options')) {
            foreach ($field->options as $opt) {
                $typeInfo .= ($typeInfo ? ',' : '') . "'" . str_replace("'", "\\" . "'", $opt) . "'";
            }
            $typeInfo = "($typeInfo)";
        } elseif($this->isType($field->type, 'have_len')) {
            $typeInfo = "({$field->size})";
        }
        
        $default = $notNull = $unsigned = $collation = '';
        
        if($field->collation) {
            $collation = " COLLATE {$field->collation}";
        }
        
        if ($field->unsigned) {
            $unsigned = ' UNSIGNED';
        }
        
        if ($field->notNull) {
            $notNull = ' NOT NULL';
        }
        
        if ($field->default !== NULL) {
            $default = " DEFAULT '{$field->default}'";
        }
        
        if ($field->field) {
            return $this->query("ALTER TABLE `{$tableName}` CHANGE `{$field->field}` `{$field->name}` {$field->type}{$typeInfo}{$collation}{$unsigned}{$notNull}{$default}");
        } else {
            return $this->query("ALTER TABLE `{$tableName}` ADD `{$field->name}` {$field->type}{$typeInfo}{$collation}{$unsigned}{$notNull}{$default}");
        }
    }
    
    
    /**
     * Създава индекс, с указаното име, като преди това премахва евентуално индекс със същото име
     */
    function forceIndex($tableName, $fieldsList, $type = 'INDEX', $indexName = NULL)
    {
        $fieldsList = arr::make($fieldsList);
        
        if (!$indexName)
        $indexName = str::phpToMysqlName(current($fieldsList));
        
        // Ако вече имаме индекс с подобно име, дропим го
        $indexes = $this->getIndexes($tableName);
        
        if ($indexes[$indexName]) {
            $this->query("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
        }
        
        // Ако типът е DROP - не създаваме нов индекс
        if($type == 'DROP') return;
        
        if (count($fieldsList)) {
            foreach ($fieldsList as $f) {
                $f = str::phpToMysqlName($f);
                $fields .= ($fields ? "," : "") . "`{$f}`\n";
            }
            
            // Създаване на Индекса
            $this->query("ALTER TABLE `{$tableName}` ADD {$type} `{$indexName}` (\n{$fields})");
        }
    }
    
    
    /**
     * Връща полетата и типовете им в една таблица
     *
     *
     * @param string $tableName
     * @param string $fieldName
     * @param int    $fieldLength
     * @return int
     */
    function getFields($tableName)
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
     * @todo Чака за документация...
     */
    function getIndexes($tableName)
    {
        $indexes = array();
        
        $dbRes = $this->query("SHOW INDEXES FROM {$tableName}");
        
        if ($this->numRows($dbRes)) {
            while ($rec = $this->fetchObject($dbRes)) {
                $name = $rec->Key_name;
                
                if ($name == 'PRIMARY') {
                    $type = 'PRIMARY';
                } elseif ($rec->Non_unique) {
                    $type = 'INDEX';
                } else {
                    $type = 'UNIQUE';
                }
                
                $indexes[$name][$type][str::mysqlToPhpName($rec->Column_name)] = TRUE;
            }
        }
        
        return $indexes;
    }
    
    
    /**
     * Проверява за грешки при последната MySQL операция
     *
     * Реагира по следния начин:
     * Ако имаме липсваща таблица, проверява дали са инсталирани пакетите
     * на ядрото и на приложението. Ако не са инсталирани - стартира
     * процеса по инсталация
     *
     *
     * @return int нула означава липса на грешка.
     */
    function checkForErrors($action, $silent)
    {
        if (!$silent && mysql_errno($this->link) > 0) {
            
            static $flagSetup;
            
            if(!$flagSetup) {
                
                
                /**
                 * Липсваща таблица
                 */
                DEFINE('MYSQL_MISSING_TABLE', 1146);
                
                $errno = mysql_errno($this->link);
                $eeror = mysql_error($this->link);
                
                // Ако таблицата липсва, предлагаме на Pack->Setup да провери
                // да не би да трябва да се прави начално установяване
                if($errno == MYSQL_MISSING_TABLE) {
                    $Packs = cls::get('core_Packs');
                    $flagSetup = TRUE;
                    $Packs->checkSetup();
                } elseif(strpos($eeror, "Unknown column 'core_") !== FALSE) {
                    $Packs = cls::get('core_Packs');
                    $flagSetup = TRUE;
                    $res = $Packs->setupPack('core');
                    
                    redirect(array('core_Packs'), FALSE, "Пакета `core` беше обновен");
                }
            }
            
            error("Грешка в БД при " . $action, array(
                    "query" => $this->query,
                    "error" => $eeror
                ), 'ГРЕШКА В БАЗАТА ДАННИ');
        }
        
        return mysql_errno();
    }
    
    
    /**
     * Ескейпва служебните символи в MySQl стойности
     *
     * @param string $value
     * @return string
     */
    function escape($value)
    {
        if (!$this->link) {
            $this->connect();
        }
        expect(is_scalar($value), $value);
        
        return mysql_real_escape_string($value, $this->link);
    }
}