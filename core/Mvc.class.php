<?php


/**
 * По подразбиране, няма префикс преди името на таблицата
 */
defIfNot('EF_DB_TABLE_PREFIX', '');


/**
 * Дължина на контролната сума, която се добавя към id-тата
 */
defIfNot('EF_ID_CHECKSUM_LEN', 3);


/**
 * Дължина на контролната сума, която се добавя към id-тата
 */
defIfNot('CORE_MAX_SQL_QUERY', 16000000);


/**
 * Клас 'core_Mvc' - Манипулации на модела (таблица в db)
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 *
 * @method static int   save(object &$rec, NULL|string|array $fields = NULL, NULL|string $mode = NULL)
 * @method static bool  haveRightFor(string $action, NULL|int|object $id = NULL, int|NULL $userId = NULL)
 */
class core_Mvc extends core_FieldSet
{
    /**
     * Дължината на защитната контролна сума за id-тата на този модел
     */
    public $idChecksumLen = EF_ID_CHECKSUM_LEN;
    
    
    /**
     * По подразбиране типа на id полето е int
     */
    protected $idType = 'int';
    
    
    /**
     * Името на класа, case sensitive
     */
    public $className;
    
    
    /**
     * Масив за кеширане на извлечените чрез fetch() записи
     */
    public $_cachedRecords;
    
    
    /**
     * Списък с полета, които трябва да се извлекат преди операция 'изтриване'
     * за записите, които ще бъдат изтрити. Помага за да се поддържа информация
     * в други модели, която е зависима от изтритите полета
     */
    public $fetchFieldsBeforeDelete;
    
    
    /**
     * Дали id-тата на този модел да са защитени?
     */
    public $protectId = true;
    
    
    /**
     * Инстанция на връзката с базата данни
     */
    public $db;
    
    
    /**
     * Име на съответстващата таблица в базата данни
     */
    public $dbTableName;
    
    
    /**
     * Индекси в базата данни
     */
    public $dbIndexes;
    
    
    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    public function _Singleton()
    {
    }
    
    
    /**
     * Енджина за таблицата в DB
     */
    public $dbEngine;
    
    
    /**
     *  Колация за символите в DB
     */
    protected $dbCollation;
    
    
    /**
     * Какъв да е минималния брой за кеширане при подготовката на MakeArray4Select
     */
    public $makeArray4selectLimit4Cache = 500;
    
    
    /**
     * Кеш за резултатите от makeArray4select(), когато са по-малко от $makeArray4selectLimit4Cache
     */
    public $makeArray4selectCache = array();
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone;
    
    
    /**
     * Дали за този модел ще се прави репликация на SQL заявките
     */
    public $doReplication = true;
    
    
    /**
     * Дали класът да бъде добавен автоматично в регистъра на класове (core_Classes)
     */
    public $automaticRegisterClass = true;
    
    
    /**
     * Конструктора на таблицата. По подразбиране работи със singleton
     * адаптор за база данни на име "db". Разчита, че адапторът
     * е вече свързан към базата.
     */
    public function init($params = array())
    {
        // Сетваме параметрите по родителския начин
        parent::init($params);
        
        // Задаваме името на класа
        if (!$this->className) {
            $this->className = cls::getClassName($this);
        }
        
        // Задаваме базата данни по подразбиране, ако в description() тя не е установена
        $this->db = & cls::get('core_Db');
        
        // Ако имаме описание на модел (т.е. метода $this->description() )
        if (cls::existsMethod($this, 'description')) {
            $class = $this->className;
            
            // Намираме, кой е най-стария пра-родител на този клас, с 'description'
            do {
                $descrClass = $class;
            } while (method_exists($class = get_parent_class($class), 'description'));
            
            // Задаваме таблицата по подразбиране
            $this->dbTableName = EF_DB_TABLE_PREFIX . str::phpToMysqlName($descrClass);
            
            $this->FLD('id', $this->idType, 'input=hidden,silent,caption=№,unsigned,notNull');
            
            // Създаваме описанието на таблицата
            $this->description();
        }
        
        // Зареждаме мениджърите и плъгините
        $this->load($this->loadList);
        
        // Изпращаме събитие, че създаването на класа е приключило
        $this->invoke('AfterDescription');
    }
    
    
    /**
     * Връща инстанция към този клас
     */
    public static function getSingleton()
    {
        $me = cls::get(get_called_class());
        
        return $me;
    }
    
    
    /**
     * Начално установяване на модела чрез http заявка (само в Debug)
     */
    public function act_SetupMVC()
    {
        if (!isDebug()) {
            error('@SETUP може да се прави само в DEBUG режим');
        }
        
        // Форсираме системния потребител
        core_Users::forceSystemUser();
        
        $res = $this->setupMVC();
        
        // Де-форсираме системния потребител
        core_Users::cancelSystemUser();
        
        return $res;
    }
    
    
    /**
     * Задава списък с полета или връзки, които в комбинация са уникални
     */
    public function setDbUnique($fieldsList, $indexName = null)
    {
        return $this->setDbIndex($fieldsList, $indexName, 'UNIQUE');
    }
    
    
    /**
     * Задава индекс върху списък от полета или връзки
     */
    public function setDbIndex($fieldsList, $indexName = null, $type = 'INDEX')
    {
        $rec = new stdClass();
        $rec->fields = $fieldsList;
        $rec->type = $type;
        
        if (!$indexName) {
            $indexName = str::convertToFixedKey(str::phpToMysqlName(implode('_', arr::make($fieldsList))));
        }
        
        if (isset($this->dbIndexes[$indexName])) {
            error('@Дублирано име за индекс в базата данни', $indexName, $this->dbIndexes);
        }
        
        $this->dbIndexes[$indexName] = $rec;
    }
    
    
    /**
     * Връща един запис от модела. Ако конд е цяло число, то cond се смята за #id
     */
    public static function fetch($cond, $fields = '*', $cache = true)
    {
        expect($cond !== null && (is_int($cond) || is_string($cond) || is_array($cond)), $cond);
        
        $me = cls::get(get_called_class());
        
        $query = $me->getQuery();
        
        if (is_array($cond)) {
            $cond = $query->substituteArray($cond);
        }
        
        // Ако имаме кеширане, пробваме се да извлечем стойността от кеша
        if ($cache) {
            expect(!is_object($cond), $cond);
            $cacheKey = $cond . '|' . $fields;
            
            if (isset($me->_cachedRecords[$cacheKey])) {
                if (is_object($me->_cachedRecords[$cacheKey])) {
                    
                    return clone ($me->_cachedRecords[$cacheKey]);
                }
                
                return $me->_cachedRecords[$cacheKey];
            }
        }
        
        if ($cache === 'only') {
            
            return;
        }
        
        if ($fields != '*') {
            $query->show($fields);
        }
        
        // Лимитираме само до 1 резултат
        $query->limit(1);
        
        $rec = $query->fetch($cond);
        
        // Ако е необходимо, записваме в кеша
        if ($cache) {
            if (is_object($rec)) {
                $cacheData = clone($rec);
            } else {
                $cacheData = $rec;
            }
            $me->_cachedRecords[$cacheKey] = $cacheData;
        }
        
        return $rec;
    }
    
    
    /**
     * Малко по-гъвкава вариация на fetch()
     *
     * Ако първия аргумент е запис, просто го връща. В противен случай вика fetch()
     *
     * @param mixed $id     ст-ст на първичен ключ, SQL условие или обект
     * @param mixed $fields @see self::fetch()
     * @param bool  $cache  @see self::fetch()
     *
     * @return stdClass
     */
    public static function fetchRec($id, $fields = '*', $cache = true)
    {
        $rec = $id;
        if (!is_object($rec)) {
            $rec = static::fetch($id, $fields, $cache);
        } elseif (!$cache) {
            $rec = static::fetch($id->id, $fields, $cache);
        }
        
        return $rec;
    }
    
    
    /**
     * Връща поле от посочен запис от модела. Ако конд е цяло число, то cond се смята за #id
     */
    public static function fetchField($cond, $field = 'id', $cache = true)
    {
        expect($field);
        
        $rec = static::fetch($cond, '*', 'only');
        
        if (!$rec) {
            $rec = static::fetch($cond, $field, $cache);
        }
        
        return $rec->{$field};
    }
    
    
    /**
     * Записва редът (записа) в таблицата
     */
    public function save_(&$rec, $fields = null, $mode = null)
    {
        $fields = $this->prepareSaveFields($fields, $rec);
        
        $table = $this->dbTableName;
        
        $exRec = null;
        
        if ($rec->id) {
            $exRec = $this->_cachedRecords[$rec->id .'|*'];
            if ($exRec === null && $this->lastFetchedRec && $this->lastFetchedRec == $rec->id) {
                $exRec = $this->lastFetchedRec;
            }
        }
        
        $query = '';
        
        foreach ($fields as $name => $dummy) {
            if ($name == 'id' && !$mode) {
                continue;
            }
            
            $value = $rec->{$name};
            
            $field = $this->getField($name);
            
            // Правим MySQL представяне на стойността
            $value = $field->type->toMysql($value, $this->db, isset($field->notNull) ? isset($field->notNull) : null, $field->value);
            
            // Предотвратява двойното записване
            if ($exRec && property_exists($exRec, $name) && is_scalar($exRec->{$name})) {
                $exValue = $field->type->toMysql($exRec->{$name}, $this->db, isset($field->notNull) ? isset($field->notNull) : null, $field->value);
                if ($exValue === $value) {
                    continue;
                }
            }
            
            // Ако няма mySQL представяне на тази стойност, то тя не участва в записа
            if ($value === null) {
                continue;
            }
            
            $mysqlField = str::phpToMysqlName($name);
            $query .= ($query ? ",\n " : "\n") . "`{$mysqlField}` = {$value}";
        }
        
        if ($query == '') {
            
            return $rec->id;
        }
        
        $mode = str_replace(' ', '_', strtolower($mode));
        
        if ($rec->id > 0 && $mode != 'replace') {
            switch ($mode) {
                case 'low_priority':
                    $query = "UPDATE LOW_PRIORITY `{$table}` SET {$query} WHERE id = {$rec->id}";
                    break;
                
                case 'ignore':
                    $query = "UPDATE IGNORE `{$table}` SET {$query} WHERE id = {$rec->id}";
                    break;
                case '':
                case 'update':
                    $query = "UPDATE `{$table}` SET {$query} WHERE id = {$rec->id}";
                    break;
                
                default:
                    error('Неподдържан режим на запис', $mode, $rec);
            }
            $timer = "{$table} UPDATE";
        } else {
            switch ($mode) {
                case 'replace':
                    $query = "REPLACE `{$table}` SET {$query}";
                    $timer = "{$table} REPLACE";
                    break;
                
                case 'ignore':
                    $query = "INSERT IGNORE `{$table}` SET {$query}";
                    $timer = "{$table} INSERT";
                    break;
                
                case 'delayed':
                    $query = "INSERT DELAYED `{$table}` SET {$query}";
                    $timer = "{$table} INSERT";
                    break;
                
                case '':
                case 'update':
                    $query = "INSERT INTO `{$table}` SET {$query}";
                    $timer = "{$table} INSERT";
                    break;
                
                default:
                    error('Неподдържан режим на запис', $mode, $rec);
            }
        }
        
        DEBUG::startTimer($timer);
        $res = $this->db->query($query, false, $this->doReplication);
        DEBUG::stopTimer($timer);
        
        if (!$res) {
            
            return false;
        }
        
        $this->dbTableUpdated();
        
        if (!$rec->id) {
            $rec->id = $this->db->insertId();
            $this->invoke('afterCreate', array($rec, $fields, $mode));
        } else {
            $this->invoke('afterUpdate', array($rec, $fields, $mode));
        }
        
        return $rec->id;
    }
    
    
    /**
     * Записва няколко записа от модела с една заявка, ако има дуплицирани, обновява ги
     */
    public function saveArray_($recs, $fields = null)
    {
        // Ако нямаме какво да записваме - връщаме TRUE, в знак, че операцията е завършила успешно
        if (!$recs || !countR($recs)) {
            
            return true;
        }
        
        // Гарантираме си, че $fields са масив
        $fields = arr::make($fields, true);
        
        // Определяме полетата, които ще записваме
        $fieldsArr = array();
        $fieldsMvc = $this->selectFields('FLD');
        foreach ($fieldsMvc as $name => $fld) {
            if ($fld->kind == 'FLD' && (!countR($fields) || $fields[$name])) {
                $fieldsArr[$name] = $fld;
                $mysqlName = str::phpToMysqlName($name);
                $insertFields .= "`${mysqlName}`,";
                $updateFields .= "`{$mysqlName}`=VALUES(`{$mysqlName}`),";
            }
        }
        
        // Очакваме, че имаме поне едно поле, което да записваме
        expect(countR($fieldsArr));
        
        // Композираме началото и края на заявката към db
        $queryBegin = "INSERT INTO `{$this->dbTableName}` (" . rtrim($insertFields, ',') . ') VALUES ';
        $queryEnd = ' ON DUPLICATE KEY UPDATE ' . rtrim($updateFields, ',');
        
        // Изчисляваме, колко байта не трябва да превишава стринга със стойностите
        $maxLen = CORE_MAX_SQL_QUERY - strlen($queryBegin) - strlen($queryEnd);
        
        // Конвертираме всеки запис към стойности в db заявката
        $query = '';
        $timer = "{$this->dbTableName} INSERT ARRAY";
        foreach ($recs as $rec) {
            $row = '(';
            foreach ($fieldsArr as $key => $field) {
                $value = $field->type->toMysql($rec->{$key}, $this->db, $field->notNull, $field->value);
                $row .= $value . ',';
            }
            $row = rtrim($row, ',') . '),';
            
            // Ако надвишаваме максималната заявка или сме изчерпали записите - записваме всичко до сега
            if (strlen($row) + strlen($query) >= $maxLen) {
                // Изпълняваме заявката
                DEBUG::startTimer($timer);
                $res = $this->db->query($queryBegin . rtrim($query, ',') . $queryEnd, false, $this->doReplication);
                DEBUG::stopTimer($timer);
                if (!$res) {
                    
                    return false;
                }
                $query = '';
            }
            $query .= $row;
        }
        
        // Ако имаме някакви натрупани стойности - записваме ги и тях
        if ($query) {
            DEBUG::startTimer($timer);
            $res = $this->db->query($queryBegin . rtrim($query, ',') . $queryEnd, false, $this->doReplication);
            DEBUG::stopTimer($timer);
            
            if (!$res) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Изчиства записите в модела
     */
    public static function truncate()
    {
        $self = cls::get(get_called_class());
        $self->db->query("TRUNCATE TABLE `{$self->dbTableName}`", false, $self->doReplication);
    }
    
    
    /**
     * Подготвя като масив полетата за записване
     */
    public function prepareSaveFields($fields, $rec)
    {
        if ($fields === null) {
            $recFields = get_object_vars($rec);
            
            foreach ($recFields as $name => $dummy) {
                if ($this->fields[$name]->kind == 'FLD') {
                    $fields[$name] = true;
                }
            }
        } else {
            $fields = arr::make($fields, true);
        }
        
        return $fields;
    }
    
    
    /**
     * Изтрива записи отговарящи на условието
     * Максималния брой на изтритите записи се задава в $limit
     * Връща реалния брой на изтрити записи
     */
    public static function delete($cond, $limit = null, $orderBy = null)
    {
        expect($cond !== null);
        
        $me = cls::get(get_called_class());
        
        $query = $me->getQuery();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        if ($orderBy) {
            $query->orderBy($orderBy);
        }
        
        $deletedRecsCnt = $query->delete($cond);
        
        return $deletedRecsCnt;
    }
    
    
    /**
     * Преброява всички записи отговарящи на условието
     */
    public static function count($cond = '1=1', $limit = null)
    {
        $me = cls::get(get_called_class());
        
        $query = $me->getQuery();
        
        $cnt = $query->count($cond, $limit);
        
        return $cnt;
    }
    
    
    /**
     * Връща времето на последната модификация на MySQL-ската таблица на модела
     */
    public static function getDbTableUpdateTime()
    {
        $me = cls::get(get_called_class());
        
        if (empty($me->lastUpdateTime)) {
            $dbRes = $me->db->query("SELECT UPDATE_TIME\n" .
                "FROM   information_schema.tables\n" .
                "WHERE  TABLE_SCHEMA = '{$me->db->dbName}'\n" .
                "   AND TABLE_NAME = '{$me->dbTableName}'");
            $dbObj = $me->db->fetchObject($dbRes);
            
            $me->lastUpdateTime = $dbObj->UPDATE_TIME;
        }
        
        return $me->lastUpdateTime;
    }
    
    
    /**
     * Извиква се след като е променяна MySQL-ската таблица
     */
    public function dbTableUpdated_()
    {
        $this->_cachedRecords = array();
        $this->lastUpdateTime = DT::verbal2mysql();
    }
    
    
    public static function getSelectArr_($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $query = self::getQuery();
        
        if (isset($params['orderBy'])) {
            $query->orderBy($params['orderBy']);
        } elseif (isset($params['titleFld'])) {
            $query->orderBy($params['titleFld']);
        }
        
        if (is_array($onlyIds)) {
            if (!countR($onlyIds)) {
                
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $query->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $query->where("#id = ${onlyIds}");
        } elseif (preg_match("/^[0-9\,]+$/", $onlyIds)) {
            $query->where("#id IN (${onlyIds})");
        }
        
        $titleFld = $params['titleFld'];
        $query->XPR('searchFieldXpr', 'text', "LOWER(CONCAT(' ', #{$titleFld}))");
        
        if ($q) {
            if ($q[0] == '"') {
                $strict = true;
            }
            
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            
            $q = mb_strtolower($q);
            
            if ($strict) {
                $qArr = array(str_replace(' ', '.*', $q));
            } else {
                $qArr = explode(' ', $q);
            }
            
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $query->where(array("#searchFieldXpr REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $query->show('id,' . $titleFld);
        
        $res = array();
        
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec->{$titleFld};
        }
        
        return $res;
    }
    
    
    /**
     * Функция, която връща подготвен масив за СЕЛЕКТ от елементи (ид, поле)
     * на $class отговарящи на условието where
     */
    public function makeArray4Select_($fields = null, $where = '', $index = 'id', $orderBy = null)
    {
        $query = $this->getQuery();
        
        $arrFields = arr::make($fields, true);
        
        if ($fields) {
            $query->show($fields);
            $query->show($index);
            $query->orderBy($orderBy ? $orderBy : $fields);
        }
        
        $res = null;
        
        $handler = md5("{$fields} . {$where} . {$index} . {$orderBy} . {$this->className}");
        
        $res = $this->makeArray4selectCache[$handler];
        
        if ($res === null) {
            // Колко записа биха влезли в масива?
            $cnt = $query->count($where, $this->makeArray4selectLimit4Cache);
            if ($cnt >= $this->makeArray4selectLimit4Cache) {
                $res = core_Cache::get('makeArray4Select', $handler, 20, array($this));
            }
        }
        
        if (!is_array($res)) {
            $res = array();
            
            while ($rec = $query->fetch($where)) {
                $id = $rec->id;
                
                $res[$rec->{$index}] = '';
                
                if ($fields) {
                    foreach ($arrFields as $fld) {
                        $res[$rec->{$index}] .= ($res[$rec->{$index}] ? ' ' : '') . $this->getVerbal($rec, $fld);
                    }
                } else {
                    $res[$rec->{$index}] = $this->getRecTitle($rec);
                }
                
                $res[$rec->{$index}] = strip_tags($res[$rec->{$index}]);
                
                $res[$rec->{$index}] = str_replace(array('&lt;', '&amp;'), array('<', '&'), $res[$rec->{$index}]);
            }
            
            if ($cnt >= $this->makeArray4selectLimit4Cache) {
                core_Cache::set('makeArray4Select', $handler, $res, 20, array($this));
            } else {
                $this->makeArray4selectCache[$handler] = $res;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        $me = cls::get(get_called_class());
        
        $modelFields = $me->selectFields('');
        
        if ($fields === '*') {
            $fields = $modelFields;
        } else {
            $fields = arr::make($fields, true);
        }
        
        $row = new stdClass();
        
        if (countR($fields) > 0) {
            foreach ($fields as $name => $caption) {
                expect($name);
                if (!$row->{$name} && $modelFields[$name]) {
                    //DEBUG::startTimer("GetVerbal");
                    $row->{$name} = $me->getVerbal($rec, $name);
                    
                    //DEBUG::stopTimer("GetVerbal");
                }
            }
        }
        
        return $row;
    }
    
    
    /**
     * Превръща стойността на посоченото поле във вербална
     */
    public static function getVerbal_($rec, $fieldName)
    {
        $me = cls::get(get_called_class());
        
        if (is_numeric($rec) && ($rec > 0)) {
            $rec = $me->fetch($rec);
        }
        
        if (!is_object($rec)) {
            
            return '?????';
        }
        
        expect(is_scalar($fieldName));
        
        expect($me->fields[$fieldName], 'Не съществуващо поле: ' . $fieldName);
        
        $value = $rec->{$fieldName};
        
        if (isset($me->fields[$fieldName]->options) && is_array($me->fields[$fieldName]->options)) {
            $res = $me->fields[$fieldName]->options[$value];
        } else {
            $res = $me->fields[$fieldName]->type->toVerbal($value);
        }
        
        return $res;
    }
    
    
    /**
     * Връща шаблона на заглавието
     * 
     * @param stdClass $rec
     * @return mixed
     */
    public function getRecTitleTpl($rec)
    {
        return $this->recTitleTpl;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        expect($rec);
        $cRec = clone $rec;
        $me = cls::get(get_called_class());
        
        if (!$tpl = $me->getRecTitleTpl($rec)) {
            $titleFields = array(
                'title',
                'name',
                'nick',
                'id'
            );
            
            foreach ($titleFields as $fieldName) {
                if (isset($cRec->{$fieldName})) {
                    $tpl = new ET("[#{$fieldName}#]");
                    break;
                }
            }
        }
        
        if ($tpl) {
            $tpl = new ET($tpl);
            
            //Ескейпваме всички записи, които имат шаблони преди да ги заместим
            if ($escaped) {
                $places = $tpl->getPlaceholders();
                
                foreach ($places as $place) {
                    $cRec->{$place} = type_Varchar::escape($rec->{$place});
                }
            }
            
            if ($fieldName == 'id' && isset($me->singleTitle)) {
                $cRec->id = tr("|{$me->singleTitle}|* №" . $cRec->id);
            }
            
            $tpl->translate();
            $tpl->placeObject($cRec);
            
            $value = (string) $tpl;
            
            return $value;
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на ключа
     */
    public static function getTitleById($id, $escaped = true)
    {
        $me = cls::get(get_called_class());
        
        $rec = new stdClass();
        
        try {
            $rec = $me->fetchRec($id);
        } catch (ErrorException $e) {
        }
        
        if (!$rec) {
            
            return '??????????????';
        }
        
        return $me->getRecTitle($rec, $escaped);
    }
    
    
    /**
     *
     *
     * @param int  $id
     * @param bool $escaped
     */
    public static function getTitleForId_($id, $escaped = true)
    {
        return self::getTitleById($id);
    }
    
    
    /**
     * Връща линк към подадения обект
     *
     * @param int $objId
     *
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        $me = get_called_class();
        $inst = cls::get($me);
        
        if ($objId) {
            $title = $inst->getTitleForId($objId);
        } else {
            $title = $inst->className;
        }
        
        $link = $title;
        
        return $link;
    }
    
    
    /**
     * Проверява дали посочения запис не влиза в конфликт с някой уникален
     *
     * @param: $rec stdClass записа, който ще се проверява
     * @param: $fields array|string полетата, които не уникални.
     * @return: bool
     */
    public function isUnique($rec, &$fields = array(), &$exRec = null)
    {
        $fields = arr::make($fields);
        
        $checkFields = array();
        
        if (countR($fields)) {
            $checkFields[] = $fields;
        } else {
            if (countR($this->dbIndexes)) {
                foreach ($this->dbIndexes as $indRec) {
                    if ($indRec->type == 'UNIQUE') {
                        $checkFields[] = arr::make($indRec->fields);
                    }
                }
            } else {
                $fields = false;
                
                return true;
            }
        }
        
        foreach ($checkFields as $fArr) {
            $fieldSetFlag = true;
            $cond = $rec->id ? "#id != {$rec->id}" : '';
            
            foreach ($fArr as $fName) {
                if (!isset($rec->{$fName})) {
                    $fieldSetFlag = false;
                    break;
                }
                
                $field = $this->getField($fName);
                
                $value = $field->type->toMysql($rec->{$fName}, $this->db, $field->notNull, $field->value);
                
                $cond .= ($cond ? ' AND ' : '') . "#{$fName} = {$value}";
            }
            
            // Ако всички полета от множеството са сетнати, правим проверка, дали подобен запис съществува
            if ($fieldSetFlag && ($exRec = $this->fetch($cond))) {
                $fields = $fArr;
                
                return false;
            }
        }
        
        $fields = false;
        
        return true;
    }
    
    
    /**
     * Начално установяване на таблицата в базата данни,
     * без да губим данните от предишни установявания
     */
    public function setupMVC()
    {
        $html = '<li>Създаване на модела <b>' . $this->className . "</b></li><ul style='margin-bottom:10px;'>";
        
        // Запалваме събитието on_BeforeSetup
        if ($this->invoke('BeforeSetupMVC', array(&$html)) === false) {
            $html .= '<li>Пропускаме началното установяване на модела</li>';
            
            return "${html}</ul>";
        }
        
        if ($this->oldClassName) {
            $oldTableName = EF_DB_TABLE_PREFIX . str::phpToMysqlName($this->oldClassName);
            
            $newTableName = $this->dbTableName;
            
            if (!$this->db->tableExists($newTableName)) {
                if ($this->db->tableExists($oldTableName)) {
                    $this->db->query("RENAME TABLE {$oldTableName} TO {$newTableName}", false, true);
                    $html .= "<li class='debug-new'>Преименувана е таблицата <b>{$oldTableName}</b> => <b>{$newTableName}</b></li>";
                }
            }
        }
        
        // Какви физически полета има таблицата?
        $fields = $this->selectFields("#kind == 'FLD'");
        
        if ($this->dbTableName && countR($fields)) {
            $tableName = $this->dbTableName;
            
            $db = $this->db;     // За краткост
            
            // Параметри на таблицата
            $tableParams = array('ENGINE' => $this->dbEngine,
                'CHARACTER' => $this->dbCharacter,
                'COLLATION' => $this->dbCollation);
            
            // Създаваме таблицата, ако не е създадена
            $action = $db->forceTable($tableName, $tableParams, $debugLog) ?
            '<li class="debug-new">Създаване на таблица:  ' :
            '<li class="debug-info">Съществуваща от преди таблица:  ';
            
            $html .= "{$action}<b>{$this->dbTableName}</b></li>" . $debugLog;
            
            foreach ($fields as $name => $field) {
                
                // Нулираме флаговете за промяна
                $updateName = $updateType = $updateOptions = $updateSize =
                $updateNotNull = $updateSigned = $updateDefault = $updateCollation = false;
                
                // Пропускаме PRI полето
                if ($name == 'id' && $this->idType == 'int') {
                    continue;
                }
                
                // Името на полето, така, както трябва да е в таблицата
                $name = str::phpToMysqlName($name);
                
                // Първи в списъка за проверка, попада полето с име, както е в модела
                $fieldsCheckList = $name;
                
                // Ако има стари полета, и те влизат в списъка за проверка
                if ($field->oldFieldName) {
                    $fieldsCheckList = $fieldsCheckList . '|' . $field->oldFieldName;
                }
                
                foreach (explode('|', $fieldsCheckList) as $fn) {
                    
                    // Не бива в модела, да има поле като старото
                    if ($this->fields[$fn] && ($fn != $name)) {
                        error('@Дублиране на старо име на поле и съществуващо поле', "'{$fn}'");
                    }
                    
                    $fn = str::phpToMysqlName($fn);
                    
                    $dfAttr = $db->getFieldAttr($tableName, $fn);
                    
                    // Ако поле с такова име съществува, работим върху него
                    if ($dfAttr) {
                        break;
                    }
                }
                
                // Установяваме mfArrt с параметрите на модела
                $mfAttr = $field->type->getMysqlAttr();
                
                if ($mfAttr->collation == 'ci') {
                    $mfAttr->collation = $this->db->dbCharset . '_general_ci';
                }
                
                $mfAttr->field = $dfAttr->field;
                
                $mfAttr->notNull = $field->notNull ? true : false;
                
                if (isset($field->value)) {
                    $mfAttr->default = $field->value;
                }
                
                $mfAttr->unsigned = ($mfAttr->unsigned || $field->unsigned) ? true : false;
                
                $mfAttr->name = $name;
                
                $green = " style='color:#007733;'";     // Стил за маркиране
                $info = '';     // Тук ще записваме текущия ред с информация какво правим
                // Дали ще създаваме или променяме името на полето
                if ($mfAttr->name != $mfAttr->field) {
                    $updateName = true;     // Ще се прави UPDATE на името
                }
                
                // Обновяване на типа
                $updateType = ($mfAttr->type != $dfAttr->type);
                $style = $updateType ? $green : '';
                $_tt = $mfAttr->type;
                if ($updateType) {
                    $_tt .= " ({$dfAttr->type})";
                }
                $info .= "<span{$style}>${_tt}</span>";
                
                // Обновяване на опциите
                if ($this->db->isType($mfAttr->type, 'have_options')) {
                    $info .= '(';
                    
                    if (countR($mfAttr->options)) {
                        $comma = '';
                        
                        foreach ($mfAttr->options as $opt) {
                            if (is_array($dfAttr->options) && in_array($opt, $dfAttr->options)) {
                                $info .= $comma . str_replace("'", "''", $opt);
                            } else {
                                $updateOptions = true;
                                $info .= $comma . "<span{$green}>" .
                                str_replace("'", "''", $opt) . '</span>';
                            }
                            
                            $comma = ', ';
                        }
                    }
                    
                    $info .= ') ';
                }
                
                // Ще обновяваме ли размера
                if ($this->db->isType($mfAttr->type, 'have_len')) {
                    $updateSize = $mfAttr->size != $dfAttr->size;
                    $style = $updateSize ? $green : '';
                    $info .= "(<span{$style}>{$mfAttr->size}</span>)";
                }
                
                // Ще обновяваме ли notNull
                $updateNotNull = ($mfAttr->notNull != $dfAttr->notNull);
                $style = $updateNotNull ? $green : '';
                $info .= ", <span{$style}>" . ($mfAttr->notNull ?
                    'NOT NULL' : 'NULL') . '</span>';
                
                // Ще обновяваме ли default?
                $updateDefault = ($mfAttr->default != $dfAttr->default);
                $style = $updateDefault ? $green : '';
                
                if ($mfAttr->default) {
                    $info .= ", <span{$style}>{$mfAttr->default}</span>";
                } elseif ($updateDefault) {
                    if ($mfAttr->notNull) {
                        $info .= ", <span{$style}>''</span>";
                    } else {
                        $info .= ", <span{$style}>NULL</span>";
                    }
                }
                
                // Ще обновяваме ли с/без знак?
                if ($this->db->isType($mfAttr->type, 'can_be_unsigned')) {
                    $updateUnsigned = $mfAttr->unsigned != $dfAttr->unsigned;
                    $style = $updateUnsigned ? $green : '';
                    $info .= ", <span{$style}>" .
                    ($mfAttr->unsigned ? 'UNSIGNED' : 'SIGNED') . '</span>';
                }
                
                // Ще обновяваме ли колацията?
                if ($this->db->isType($mfAttr->type, 'have_collation')) {
                    setIfNot($mfAttr->collation, $field->collation, $this->db->dbCollation);
                    $mfAttr->collation = strtolower($mfAttr->collation);
                    $updateCollation = $mfAttr->collation != $dfAttr->collation;
                    $style = $updateCollation ? $green : '';
                    $info .= ", <span{$style}>" .
                    ($mfAttr->collation) . '</span>';
                }
                
                // Трябва ли да извършим обновяване/създаване на полето
                if ($updateName || $updateType || $updateOptions || $updateSize ||
                    $updateNotNull || $updateUnsigned || $updateDefault || $updateCollation) {
                    try {
                        if ($this->db->forceField($tableName, $mfAttr)) {
                            // Преименуване или създаване на полето?
                            if ($dfAttr->field) {
                                if ($mfAttr->field != $mfAttr->name) {
                                    $title = "<span{$green}>Преименуване <b>{$mfAttr->field}</b> => <b>{$mfAttr->name}</b></span>";
                                } else {
                                    $title = "<span>Обновяване на поле <b>{$mfAttr->name}</b></span>";
                                }
                            } else {
                                $title = "<span{$green}>Създаване на поле <b>{$mfAttr->name}</b></span>";
                            }
                        }
                    } catch (core_exception_Expect $e) {
                        reportException($e);
                        
                        if ($mfAttr->field) {
                            $html .= "<li class='debug-error'>Проблем при обновяване на поле '<b>{$mfAttr->field}</b>', {$e->getMessage()}</li>";
                        } else {
                            $html .= "<li class='debug-error'>Проблем при добавяне на поле '<b>{$mfAttr->field}</b>', {$e->getMessage()}</li>";
                        }
                        
                        continue;
                    }
                } else {
                    $title = "Съществуващо поле <b>{$mfAttr->name}</b>";
                }
                
                if (strpos($info, $green)) {
                    $liClass = ' class="debug-new"';
                } else {
                    $liClass = '';
                }
                $html .= "<li{$liClass}>" . $title . ': ' . $info . '</li>';
            }
            
            $indexes = $this->db->getIndexes($this->dbTableName);
            
            unset($indexes['PRIMARY']);
            
            // Добавяме индексите
            if (is_array($this->dbIndexes)) {
                foreach ($this->dbIndexes as $name => $indRec) {
                    
                    // За varchar добавяме ограничение за уникалност на първите 100 символа
                    $fArr = explode(',', $indRec->fields);
                    $fieldsList = '';
                    foreach ($fArr as $fName) {
                        list($fName, ) = explode('(', $fName);
                        $fName = trim($fName);
                        
                        expect($fType = $this->getFieldType($fName), $this, $fName);
                        
                        $mySqlAttr = $fType->getMysqlAttr();
                        
                        $dbFieldSize = $fType->getDbFieldSize();
                        
                        if ($fType->dbFieldType == 'varchar' && $dbFieldSize > $this->db->varcharIndexPrefix && ($dbFieldSize > $mySqlAttr->indexPrefix)) {
                            $indPref = null;
                            setIfNot($indPref, $mySqlAttr->indexPrefix, $this->db->varcharIndexPrefix);
                            $addLimit = '(' . $indPref . ')';
                        } else {
                            $addLimit = '';
                        }
                        $fieldsList .= ($fieldsList ? ',' : '') . $fName . $addLimit;
                    }
                    
                    if ($indexes[$name]) {
                        $exFields = $indexes[$name][$indRec->type];
                        $exFieldsList = '';
                        if (is_array($exFields)) {
                            foreach ($exFields as $exField => $true) {
                                if ($true) {
                                    $exFieldsList .= ($exFieldsList ? ',' : '') . $exField;
                                    if ($true > 1) {
                                        $exFieldsList .= "({$true})";
                                    }
                                }
                            }
                        }
                        
                        // За да не бъде премахнат този индекс по-нататък
                        unset($indexes[$name]);
                        
                        $fieldsList = str_replace(' ', '', $fieldsList);
                        
                        // Ако полетата на съществуващия индекс са същите като на зададения, не се прави нищо
                        if (strtolower($exFieldsList) == strtolower($fieldsList)) {
                            $html .= "<li>Съществуващ индекс '<b>{$indRec->type}</b>' '<b>{$name}</b>' на полетата '<b>{$fieldsList}</b>'</li>";
                            continue;
                        }
                        
                        $act = 'Обновен';
                        $cssClass = 'debug-update';
                    } else {
                        $act = 'Добавен';
                        $cssClass = 'debug-new';
                    }
                    
                    try {
                        if ($this->db->forceIndex($this->dbTableName, $fieldsList, $indRec->type, $name)) {
                            $html .= "<li class=\"{$cssClass}\">{$act} индекс '<b>{$indRec->type}</b>' '<b>{$name}</b>' на полетата '<b>{$fieldsList}</b>'</li>";
                        }
                    } catch (core_exception_Expect $e) {
                        reportException($e);
                        
                        $html .= "<li class='debug-error'>Проблем при {$act} индекс '<b>{$indRec->type}</b>' '<b>{$name}</b>' на полетата '<b>{$fieldsList}</b>', {$e->getMessage()}</li>";
                    }
                }
            }
            
            if (countR($indexes)) {
                foreach ($indexes as $name => $dummy) {
                    $this->db->forceIndex($this->dbTableName, '', 'DROP', $name);
                    $html .= "<li class='debug-notice'>Премахнат е индекс '<b>{$name}</b>'</li>";
                }
            }
        } else {
            $html .= "<li class='debug-info'>" . ('Без установяване на DB таблици, защото липсва модел') . '</li>';
        }
        
        // Добавяме в списъка с интерфейсните класове
        if ($this->automaticRegisterClass) {
            $html .= core_Classes::add($this);
        }
        
        // Запалваме събитието on_afterSetup
        $this->invoke('afterSetupMVC', array(&$html));
        
        return "${html}</ul>";
    }
    
    
    /**
     * Връща асоциирана db-заявка към MVC-обекта
     *
     * @return core_Query
     */
    public function getQuery_($params = array())
    {
        $params = arr::make($params);
        setIfNot($params['mvc'], $this);
        $res = & cls::get('core_Query', $params);
        
        return $res;
    }
    
    
    /**
     * Връща асоциираната форма към MVC-обекта
     */
    public function getForm_($params = array())
    {
        $params = arr::make($params);
        setIfNot($params['mvc'], $this);
        $res = & cls::get('core_Form', $params);
        
        return $res;
    }
    
    
    /**
     * Магически метод, който прихваща извикванията на липсващи статични методи
     */
    public static function __callStatic($method, $args)
    {
        $class = get_called_class();
        
        $me = cls::get($class);
        
        //Debug::log("Start $class->{$method}");
        
        if ($me instanceof stdClass) {
            
            return ;
        }
        
        $res = $me->__call($method, $args);
        
        //Debug::log("Finish $class->{$method}");
        
        return $res;
    }
    
    
    /**
     * Името на ДБ таблицата, в която се пазят данните на този модел
     *
     * @return string
     */
    public static function getDbTableName()
    {
        return static::instance()->dbTableName;
    }
    
    
    /**
     * Статичен достъп до (единствения, понеже е singleton) обект от този клас
     *
     * Този метод позволява удобно извикване на съществуващи нестатичните методи на класа и
     * достъп до нестатичните му полета. Така се отваря възможността тези методи и полета да се
     * използват в други статични методи на singleton класовете, напр.:
     *
     * <code>
     * class Manager extends core_Manager
     * {
     *     static function foo() {
     *         // Получаваме информация за полето 'field' на Manager.
     *         $field = static::instance()->getField('field');
     *     }
     * }
     * </code>
     *
     * @return core_Mvc инстанция на обект от класа, през който метода е извикан статично
     */
    public static function instance()
    {
        return cls::get(get_called_class());
    }
    
    
    /**
     * Добавя контролна сума към ID параметър
     */
    public function protectId($id)
    {
        if (!$this->protectId) {
            
            return $id;
        }
        
        $hash = substr(base64_encode(md5(EF_SALT . $this->className . $id)), 0, $this->idChecksumLen);
        
        return $id . $hash;
    }
    
    
    /**
     * Проверява контролната сума към id-то, ако всичко е ОК - връща id, ако не е - FALSE
     */
    public function unprotectId_($id)
    {
        $id = $this->db->escape($id);
        
        if (!$this->protectId) {
            
            return $id;
        }
        
        $idStrip = substr($id, 0, strlen($id) - $this->idChecksumLen);
        $idProt = $this->protectId($idStrip);
        
        if ($id == $idProt) {
            
            return $idStrip;
        }
        sleep(2);
        Debug::log('Sleep 2 sec. in ' . __CLASS__);
        
        return false;
    }
    
    
    /**
     * Прави стандартна 'обвивка' на изгледа
     *
     * @todo: да се отдели като плъгин
     */
    public function renderWrapping_($tpl, $data = null)
    {
        return $tpl;
    }
    
    
    /**
     * Добавя alert запис в log_Data
     *
     * @param string $action
     * @param int    $objectId
     * @param int    $lifeDays
     */
    public static function logAlert($action, $objectId = null, $lifeDays = 14)
    {
        $className = get_called_class();
        log_System::add($className, $action, $objectId, 'alert', $lifeDays);
    }
    
    
    /**
     * Добавя err запис в log_Data
     *
     * @param string $action
     * @param int    $objectId
     * @param int    $lifeDays
     */
    public static function logErr($action, $objectId = null, $lifeDays = 10)
    {
        $className = get_called_class();
        log_System::add($className, $action, $objectId, 'err', $lifeDays);
    }
    
    
    /**
     * Добавя warning запис в log_Data
     *
     * @param string $action
     * @param int    $objectId
     * @param int    $lifeDays
     */
    public static function logWarning($action, $objectId = null, $lifeDays = 10)
    {
        $className = get_called_class();
        log_System::add($className, $action, $objectId, 'warning', $lifeDays);
    }
    
    
    /**
     * Добавя notice запис в log_Data
     *
     * @param string $action
     * @param int    $objectId
     * @param int    $lifeDays
     */
    public static function logNotice($action, $objectId = null, $lifeDays = 5)
    {
        $className = get_called_class();
        log_System::add($className, $action, $objectId, 'notice', $lifeDays);
    }
    
    
    /**
     * Добавя info запис в log_Data
     *
     * @param string $action
     * @param int    $objectId
     * @param int    $lifeDays
     */
    public static function logInfo($action, $objectId = null, $lifeDays = 7)
    {
        $className = get_called_class();
        log_System::add($className, $action, $objectId, 'info', $lifeDays);
    }
    
    
    /**
     * Добавя debug запис в log_Data
     *
     * @param string $action
     * @param int    $objectId
     * @param int    $lifeDays
     */
    public static function logDebug($action, $objectId = null, $lifeDays = 1)
    {
        $className = get_called_class();
        log_System::add($className, $action, $objectId, 'debug', $lifeDays);
    }
    
    
    /**
     * Добавя info запис в log_Data
     *
     * @param string   $action
     * @param int      $objectId
     * @param int      $lifeDays
     * @param int|null $cu
     */
    public static function logRead($action, $objectId = null, $lifeDays = 180, $cu = null)
    {
        if (core_Users::getCurrent() <= 0) {
            self::logInfo($action, $objectId);
        }
        
        $className = get_called_class();
        log_Data::add('read', $action, $className, $objectId, $lifeDays, $cu);
    }
    
    
    /**
     * Добавя info запис в log_Data
     *
     * @param string   $action
     * @param int      $objectId
     * @param int      $lifeDays
     * @param int|null $cu
     */
    public static function logWrite($action, $objectId = null, $lifeDays = 360, $cu = null)
    {
        if (core_Users::getCurrent() <= 0) {
            self::logInfo($action, $objectId);
        }
        
        $className = get_called_class();
        log_Data::add('write', $action, $className, $objectId, $lifeDays, $cu);
    }
    
    
    /**
     * Добавя info запис в log_Data
     *
     * @param string   $action
     * @param int      $objectId
     * @param int      $lifeDays
     * @param int|null $cu
     */
    public static function logLogin($action, $objectId = null, $lifeDays = 180, $cu = null)
    {
        $currUser = isset($cu) ? $cu : core_Users::getCurrent();
        
        if ($currUser <= 0) {
            self::logInfo($action, $objectId);
        }
        
        $className = get_called_class();
        log_Data::add('login', $action, $className, $objectId, $lifeDays, $cu);
    }
    
    
    /**
     * Оптимизиране на таблиците по крон
     *
     * @return string
     */
    public function cron_OptimizeTables()
    {
        // Временно спрян процеса по оптимизиране на таблиците
        return;
        
        $db = cls::get('core_Db');
        
        $dbName = $db->escape($db->dbName);
        $dbRes = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$dbName}'");
        if (!is_object($dbRes)) {
            
            return false;
        }
        
        $html = '';
        
        while ($resArr = $db->fetchArray($dbRes)) {
            $dbTable = cls::get('core_Db');
            $name = $dbTable->escape($resArr['TABLE_NAME']);
            
            if (!$name) {
                continue;
            }
            
            $dbTableRes = $dbTable->query("OPTIMIZE TABLE `{$name}`");
            
            if (!is_object($dbTableRes)) {
                continue;
            }
            
            $optRes = $dbTable->fetchArray($dbTableRes);
            $html .= '<li>' . implode(' ', $optRes) . '</li>';
            
            $dbTable->freeResult($dbTableRes);
        }
        
        $db->freeResult($dbRes);
        
        return $html;
    }
}
