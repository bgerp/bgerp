<?php

/**
 * По подразбиране, няма префикс преди името на таблицата
 */
defIfNot('EF_DB_TABLE_PREFIX', '');


/**
 * Клас 'core_Mvc' - Манипулаци на модела (таблица в db)
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_Mvc extends core_FieldSet
{
    /**
     * Името на класа, case sensitive
     */
    var $className;


    /**
     * Масив за кеширане на извлечените чрез fetch() записи
     */
    var $_cashedRecords;
    

    /**
     * Списък с полета, които трявба да се извлекат преди операция 'изтриване'
     * за записите, които ще бъдат изтрити. Помага за да се поддържа информация  
     * в други модели, която е зависима от изтритите полета
     */
    var $fetchFieldsBeforeDelete;


    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    function _Singleton() {}
    
    
    /**
     * Конструктора на таблицата. По подразбиране работи със singleton
     * адаптор за база данни на име "db". Разчита, че адапторът
     * е вече свъразн към базата.
     *
     */
    function init()
    {
        // Задаваме името на класа
        if (!$this->className) {
            $this->className =& cls::getClassName($this);
        }
        
        // Задаваме базата данни по подразбиране, ако в description() тя не е установена
        $this->db =& cls::get('core_Db');
        
        // Ако имаме описание на модел (т.е. метода $this->description() ) 
        if (method_exists($this, 'description')) {
            
            $class = $this->className;
            
            // Намираме, кой е най-стария пра-родител на този клас, с 'description'
            do { $descrClass = $class;
            }
            
            while(method_exists($class = get_parent_class($class), 'description'));
            
            // Задаваме таблицата по подразбиране
            $this->dbTableName = EF_DB_TABLE_PREFIX . str::phpToMysqlName($descrClass);
            
            $this->FLD("id", "int", 'input=hidden,silent,caption=№,unsigned,notNull');
            
            // Създаваме описанието на таблицата
            $this->description();
        }
        
        // Зареждаме мениджърите и плъчините
        $this->load($this->loadList);
        
        // Изпращаме събитие, че създаването на класа е приключило
        $this->invoke('AfterDescription');
    }
    
    
    /**
     * Начално установяване на модела чрез http заявка (само в Debug)
     */
    function act_SetupMVC()
    {
        if(!isDebug()) error('SETUP може да се прави само в DEBUG режим');
        
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
    function setDbUnique($fieldsList, $indexName = NULL)
    {
        return $this->setDbIndex($fieldsList, $indexName, 'UNIQUE');
    }


    /**
     * Задава индекс върхи списък от полета или връзки
     */
    function setDbIndex($fieldsList, $indexName = NULL, $type = 'INDEX')
    {
        $rec->fields = $fieldsList;
        $rec->type = $type;
        
        if (!$indexName) {
            $indexName = str::convertToFixedKey(str::phpToMysqlName(implode('_', arr::make($fieldsList))));
        }
        
        if ($this->dbIndexes[$indexName]) {
            error("Дублирано име за индекс в базата данни", array(
                $indexName,
                $this->dbIndexes
            ));
        }
        
        $this->dbIndexes[$indexName] = $rec;
    }
    
    
    /**
     * Връща един запис от модела. Ако конд е цяло число, то cond се смята за #id
     */
    static function fetch($cond, $fields = '*', $cache = TRUE)
    {
        expect($cond);
        
        $me = cls::get(get_called_class());

        $query = $me->getQuery();
        
        if (is_array($cond)) {
            $cond = $query->substituteArray($cond);
        }
        
        // Ако имаме кеширане, пробваме се да извлечем стойността от кеша
        if ($cache) {
            $casheKey = $cond . '|' . $fields;

            if ( is_object($me->_cashedRecords[$casheKey]) ) {

                return $me->_cashedRecords[$casheKey];
            }
        }
        
        if($fields != '*') {
            $query->show($fields);
        }
        
        // Лимитираме само до 1 резултат
        $query->limit(1);
        
        $rec = $query->fetch($cond);
        
        // Ако е необходимо, записваме в кеша
        if ($cache) {
            $me->_cashedRecords[$casheKey] = $rec;
        }
        
        return $rec;
    }
    
    
    /**
     * Връща поле от посочен запис от модела. Ако конд е цяло число, то cond се смята за #id
     */
    static function fetchField($cond, $field = 'id', $cache = TRUE)
    {
        expect($field);
        
        $me = cls::get(get_called_class());

        $rec = $me->fetch($cond, $field, $cache);

        return $rec->{$field};
    }
    
    
    /**
     * Записва редът (записа) в таблицата
     */
    function save_(&$rec, $fields = NULL, $mode = NULL)
    { 
        if ($fields === NULL) {
            $recFields = get_object_vars($rec);
            
            foreach ($recFields as $name => $dummy) {
                if ($this->fields[$name]->kind == 'FLD') {
                    $fields[$name] = TRUE;
                }
            }
        } else {
            $fields = arr::make($fields, TRUE);
        }
        
        $table = $this->dbTableName;
        
        foreach ($fields as $name => $dummy) {
            
            if ($name == "id" && !$mode) {
                continue;
            }
            
            $value = $rec->{$name};
            
            $field = $this->getField($name);
            
            // Правим MySQL представяне на стойността
            $value = $field->type->toMysql($value, $this->db, $field->notNull, $field->value);
            
            // Ако няма mySQL представяне на тази стойност, то тя не участва в записа
            if($value === NULL) {
                continue;
            }

            $mysqlField = str::phpToMysqlName($name);
            
            $query .= ($query ? ",\n " : "\n") . "`{$mysqlField}` = {$value}";
        }
      
        switch(strtolower($mode)) {
            case 'replace':
                $query = "REPLACE `$table` SET $query";
                break;
            
            case 'ignore':
                $query = "INSERT IGNORE `$table` SET $query";
                break;
            
            case 'delayed':
                $query = "INSERT DELAYED `$table` SET $query";
                break;
            
            default:
            if ($rec->id > 0) {
                $query = "UPDATE `$table` SET $query WHERE id = {$rec->id}";
            } else {
                $query = "INSERT  INTO `$table` SET $query";
            }
        }
          
        $this->dbTableUpdated();
        
        if (!$this->db->query($query)) return FALSE;
        
        if (!$rec->id) {
            $rec->id = $this->db->insertId();
        }
        
        return $rec->id;
    }
    
    
    /**
     * Изтрива записи отговарящи на условието
     * Максималния брой на изтритите записи се задава в $limit
     * Връща реалния брой на изтрити записи
     */
    static function delete($cond, $limit = NULL, $orderBy = NULL)
    {   
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
     * Връща времето на последната модификация на MySQL-ската таблица на модела
     */
    static function getDbTableUpdateTime()
    {
        $me = cls::get(get_called_class());
        
        if(empty($me->lastUpdateTime)) {

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
    function dbTableUpdated_()
    {
        $this->_cashedRecords = array();
        $me->lastUpdateTime   = DT::verbal2mysql();
    }

    
    /**
     * Функция, която връща подготвен масив за СЕЛЕКТ от елементи (ид, поле)
     * на $class отговарящи на условието where
     */
    function makeArray4Select_($fields = NULL, $where = "", $index = 'id')
    {
        $query = $this->getQuery();
        
        $arrFields = arr::make($fields, TRUE);
        
        if ($fields) {
            $query->show($fields);
            $query->show("id");
            $query->orderBy($fields);
        }

        if($query->count() > 500) {

            $handler = md5("{$fields} . {$where} . {$index} . {$this->className}");

            $res = core_Cache::get('makeArray4Select', $handler, 20, array($this));
        }
        
        if($res !== FALSE) {
            $res = array();

            while ($rec = $query->fetch($where)) {
                 
                $id = $rec->id;
                
                if($fields) {
                    foreach($arrFields as $fld) {
                        $res[$rec->{$index}] .= ($res[$rec->{$index}] ? " ": '') . $this->getVerbal($rec, $fld);
                    }
                } else {
                    $res[$rec->{$index}] = $this->getRecTitle($rec);
                }
            }

            if($handler) {
                core_Cache::set('makeArray4Select', $handler, $res, 20, array($this));
            }
        }
        
        return $res;
    }

    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    function recToVerbal_($rec, $fields = '*')
    {
        $modelFields = $this->selectFields("");
        
        if( $fields === '*') {
            $fields = $modelFields;
        } else {
            $fields = arr::make($fields, TRUE);
        }
        
        if (count($fields) > 0) {
            foreach ($fields as $name => $caption) {
                if (!$row->{$name} && $modelFields[$name]) {
                    $row->{$name} = $this->getVerbal($rec, $name);
                }
            }
        }
        
        return $row;
    }
    
    
    /**
     * Превръща стойността на посоченото поле във вербална
     */
    function getVerbal_($rec, $fieldName)
    {
        if(!is_object($rec)) return "?????";
        
        expect(is_scalar($fieldName));
        
        expect($this->fields[$fieldName], 'Не съществуващо поле: ' . $fieldName);
        
        $value = $rec->{$fieldName};
        
        if (is_array($this->fields[$fieldName]->options)) {
            $res = $this->fields[$fieldName]->options[$value];
        } else {
            $res = $this->fields[$fieldName]->type->toVerbal($value);
        }
        
        return $res;
    }
    
     
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle(&$rec)
    {
        $me = cls::get(get_called_class());

        if(!$tpl = $me->recTitleTpl) {
            $titleFields = array(
                'title',
                'name',
                'caption',
                'name',
                'number',
                'id'
            );
            
            foreach ($titleFields as $fieldName) {
                if ($rec->{$fieldName}) {
                    $tpl = new ET("[#{$fieldName}#]");
                    break;
                }
            }
        }
        
        if($tpl) {
            if( is_string($tpl) ) {
                $tpl = new ET($tpl);
            }
            $tpl->placeObject($rec);
            
            $value = $tpl->getContent();
            
            $value = type_Varchar::escape($value);
            
            return $value;
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на ключа
     */
    static function getTitleById($id)
    { 
        $me = cls::get(get_called_class());

        if ($id > 0) {
            $rec = $me->fetch($id);
        } else {
            $rec->id = $id;
        }

        
        return $me->getRecTitle($rec);
    }


    /**
     * Проверява дали посочения запис не влиза в конфликт с някой уникален
     * @param: $rec stdClass записа, който ще се проверява
     * @param: $fields array|string полетата, които не уникални.
     * @return: bool
     */
    function isUnique($rec, &$fields = array(), &$exRec = NULL)
    {
        $fields = arr::make($fields);
        
        if(count($fields)) {
            $checkFields[] = $fields;
        } else {
            if(count($this->dbIndexes)) {
                foreach($this->dbIndexes as $indRec) {
                    if($indRec->type == 'UNIQUE') {
                        $checkFields[] = arr::make($indRec->fields);
                    }
                }
            } else {
                
                $fields = FALSE;
                
                return TRUE;
            }
        }
        
        foreach($checkFields as $fArr) {
            
            $fieldSetFlag = TRUE;
            
            $cond = $rec->id ? "#id != $rec->id" : '';
            
            foreach($fArr as $fName) {
                if(!isset($rec->{$fName})) {
                    $fieldSetFlag = FALSE;
                    break;
                }
                
                $field = $this->getField($fName);
                
                $value = $field->type->toMysql($rec->{$fName}, $this->db, $field->notNull, $field->value);
                
                $cond .= ($cond ? " AND ":"") . "#{$fName} = {$value}";
            }

            // Ако всички полета от множеството са сетнати, правим проверка, дали подобен запис съществува
            if($fieldSetFlag && ($exRec = $this->fetch($cond))) {
                
                $fields = $fArr;
                
                return FALSE;
            }
        }
        
        $fields = FALSE;
        
        return TRUE;
    }
    
    
    /**
     * Начално установяване на таблицата в базата данни,
     * без да губим данните от предишни установявания
     */
    function setupMVC()
    {
        $html .= "<h3>" . ('Начално установяване на модела') .
        ": <i>" . $this->className . "</i></h3><ol style='margin-bottom:10px;'>";

        // Запалваме събитието on_BeforeSetup
        $this->invoke('BeforeSetupMVC', array(&$html));
        
        if($this->oldClassName) {

            $oldTableName = EF_DB_TABLE_PREFIX . str::phpToMysqlName($this->oldClassName);

            $newTableName = $this->dbTableName;

            if(!$this->db->tableExists($newTableName)) {
                if($this->db->tableExists($oldTableName)) {
                    $this->db->query("RENAME TABLE {$oldTableName} TO {$newTableName}");
                    $html .= "<li style='color:green'>Преименувана е таблицата {$oldTableName} => {$newTableName} </li>";
                }
            }
        }

        
        // Какви физически полета има таблицата?
        $fields = $this->selectFields("#kind == 'FLD'");
        
        if ($this->dbTableName && count($fields)) {
            
            $tableName = $this->dbTableName;
            
            $db = $this->db; // За краткост
            // Създаваме таблицата, ако не е създадена
            $action = $db->forceTable($tableName) ?
            '<li style="color:green">Създаване на таблица:  ' :
            '<li>Същесвуваща от преди таблица:  ';
            
            $html .= "{$action}<b>{$this->dbTableName}</b></li>";
            
            foreach ($fields as $name => $field) {
                
                // Нулираме флаговете за промяна
                $updateName = $updateType = $updateOptions = $updateSize =
                $updateNotNull = $updateSigned = $updateDefault = FALSE;
                
                // Пропускаме PRI полето
                if($name == 'id') continue;
                
                // Името на полето, така, както трябва да е в таблицата
                $name = str::phpToMysqlName($name);
                
                // Първи в списъка за проверка, попада полето с име, както е в модела
                $fieldsCheckList = $name;
                
                // Ако има стари полета, и те влизат в списъка за проверка
                if($field->oldFieldName) {
                    $fieldsCheckList = $fieldsCheckList . '|' . $field->oldFieldName;
                }
                
                foreach (explode('|', $fieldsCheckList) as $fn) {
                    
                    // Не бива в модела, да има поле като старото
                    if ($this->fields[$fn] && ($fn != $name)) {
                        error("Дублиране на старо име на поле и съществуващо поле", "'{$fn}'");
                    }
                    
                    $fn = str::phpToMysqlName($fn);
                    
                    $dfAttr = $db->getFieldAttr($tableName, $fn);
                    
                    // Ако поле с такова име съществува, работим върху него
                    if ($dfAttr) break;
                }
                
                // Установяваме mfArrt с параметрите на модела                
                $mfAttr = $field->type->getMysqlAttr();
                
                $mfAttr->field = $dfAttr->field;
                
                $mfAttr->notNull = $field->notNull ? TRUE : FALSE;
                
                $mfAttr->default = $field->value;
                
                $mfAttr->unsigned = ($mfAttr->unsigned || $field->unsigned) ? TRUE : FALSE;
                
                $mfAttr->name = $name;
                
                //bp($mfAttr, $dfAttr);
                
                $green = " style='color:green;'"; // Стил за маркиране
                $info = ''; // Тук ще записваме текъщия ред с инфо какво правим
                // Дали ще създаваме или променяме името на полето
                if ($mfAttr->name != $mfAttr->field) {
                    $updateName = TRUE; // Ще се прави UPDATE на името
                }
                
                // Обновяване на типа
                $updateType = ($mfAttr->type != $dfAttr->type);
                $style = $updateType ? $green : '';
                $info .= "<span{$style}>{$mfAttr->type}</span>";
                
                // Обновяване на опциите
                if($this->db->isType($mfAttr->type, 'have_options')) {
                    
                    $info .= "(";
                    
                    if(count($mfAttr->options)) {
                        
                        $comma = '';
                        
                        foreach($mfAttr->options as $opt) {
                            if(is_array($dfAttr->options) && in_array($opt, $dfAttr->options)) {
                                $info .= $comma . str_replace("'", "''", $opt);
                            } else {
                                $updateOptions = TRUE;
                                $info .= $comma . "<span{$green}>" .
                                str_replace("'", "''", $opt) . "</span>";
                            }
                            
                            $comma = ",";
                        }
                    }
                    
                    $info .= ") ";
                }
                
                // Ще обновяваме ли размера
                if($this->db->isType($mfAttr->type, 'have_len')) {
                    $updateSize = $mfAttr->size != $dfAttr->size;
                    $style = $updateSize ? $green : "";
                    $info .= "(<span{$style}>{$mfAttr->size}</span>)";
                }
                
                // Ще обновяваме ли notNull
                $updateNotNull = ($mfAttr->notNull != $dfAttr->notNull);
                $style = $updateNotNull ? $green : "";
                $info .= ", <span{$style}>" . ($mfAttr->notNull ?
                'NOT NULL' : 'NULL') . "</span>";
                
                // Ще обновяваме ли default?
                $updateDefault = ($mfAttr->default != $dfAttr->default);
                $style = $updateDefault ? $green : "";
                
                if($mfAttr->default) {
                    $info .= ", <span{$style}>{$mfAttr->default}</span>";
                } elseif($updateDefault) {
                    if($mfAttr->notNull) {
                        $info .= ", <span{$style}>''</span>";
                    } else {
                        $info .= ", <span{$style}>NULL</span>";
                    }
                }
                
                // Ще обновяваме ли с/без знак?
                if($this->db->isType($mfAttr->type, 'can_be_unsigned')) {
                    $updateUnsigned = $mfAttr->unsigned != $dfAttr->unsigned;
                    $style = $updateUnsigned ? $green : "";
                    $info .= ", <span{$style}>" .
                    ($mfAttr->unsigned ? 'UNSIGNED' : "SIGNED") . "</span>";
                }
                
                // Трябва ли да извършим обновяване/създаване на полето
                if ($updateName || $updateType || $updateOptions || $updateSize ||
                $updateNotNull || $updateSigned || $updateDefault) {
                    
                    $this->db->forceField($tableName, $mfAttr);
                    
                    // Преименуване или създаване на полето?
                    if($dfAttr->field) {
                        if ($mfAttr->field != $mfAttr->name) {
                            $title = "<span{$green}>Преименуване <b>{$mfAttr->field}</b> => <b>{$mfAttr->name}</b></span>";
                        } else {
                            $title = "<span{$green}>Обновяване на <b>{$mfAttr->name}</b></span>";
                        }
                    } else {
                        $title = "<span{$green}>Създаване на <b>{$mfAttr->name}</b></span>";
                    }
                } else {
                    $title = "Съществуващо поле <b>{$mfAttr->name}</b>";
                }
                
                $html .= "<li>" . $title . ": " . $info;
            }

            $indexes = $this->db->getIndexes($this->dbTableName);
            
            unset($indexes['PRIMARY']);

            // Добавяме индексите
            if (count($this->dbIndexes)) {
                foreach ($this->dbIndexes as $name => $indRec) {
                    unset($indexes[$name]);
                    $this->db->forceIndex($this->dbTableName, $indRec->fields, $indRec->type, $name);
                    $html .= "<li><font color='#660000'>Обновен индекс '<b>{$indRec->type}</b>' '<b>{$name}</b>' на полетата '<b>{$indRec->fields}</b>'</font></li>";
                }
            }

            if(count($indexes)) {
                foreach($indexes as $name => $dummy) {
                    $this->db->forceIndex($this->dbTableName, "", "DROP", $name);
                    $html .= "<li><font color='green'>Премахнат е индекс '<b>{$name}</b>'</font></li>";
                }
            }

        } else {
            $html .= "<li>" . ('Без установяване на DB таблици, защото липсва модел');
        }
        
        // Правим опит да добавик класа в списъка с устройства.
        // Той ще се появи там, само ако в него има описани някакви адаптери
        $html .= core_Classes::add($this);
        
        // Запалваме събитието on_afterSetup
        $this->invoke('afterSetupMVC', array(&$html));
        
        return "$html</ol>";
    }
    
    
    /**
     * Връща асоциирана db-заявка към MVC-обекта
     *
     * @return core_Query
     */
    function getQuery_($params = array())
    {
        $params = arr::make($params);
        setIfNot($params['mvc'], &$this);
        $res =& cls::get('core_Query', $params);
        
        return $res;
    }
    
    
    /**
     * Връща асоциираната форма към MVC-обекта
     */
    function getForm_($params = array())
    {
        $params = arr::make($params);
        setIfNot($params['mvc'], &$this);
        $res =& cls::get('core_Form', $params);
        
        return $res;
    }


    /**
     * Магически метод, който прихваща извикванията на липсващи статични методи
     */
    public static function __callStatic($method, $args)
    { 
        $class = get_called_class();

        $me = cls::get($class);

        Debug::log("Start $class->{$method}");

        $res = $me->__call($method, $args);
        
        Debug::log("Finish $class->{$method}");

        return $res;
    }
}