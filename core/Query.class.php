<?php



/**
 * Клас 'core_Query' - Заявки към таблица от db
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Query extends core_FieldSet
{
    
    
    /**
     * Място за MVC класа, към който се отнася заявката
     */
    var $mvc;
    
    
    /**
     * Масив от изрази, именувани с полета
     */
    var $expr = array();
    
    
    /**
     * Масив, съдържащ полетата, които ще се покажат, при SELECT
     */
    var $show = array();
    
    
    /**
     * Масив, съдържащ таблиците от които ще се избира
     */
    var $tables = array();
    
    
    /**
     * Кои 'XPR' (изрази) полета са използвани
     */
    var $exprShow = array();
    

    /**
     * Кои полета са използвани за даден израз
     */
    private $usedFields = array();
    

    /**
     * Масив, където съхраняваме WHERE и HAVE условията
     */
    var $where = array();
    
    
    /**
     * Масив, където съхраняваме GROUP BY условията
     */
    var $groupBy = array();
    
    
    /**
     * Масив, където съхраняваме ORDER BY условията
     */
    var $orderBy = array();
    
    
    /**
     * Число, което показва колко най-много резултата да извлечем
     */
    var $limit;
    
    
    /**
     * Число, което показва от кой резултат да започнем извличането
     */
    var $start;


    /**
     * Дали SELECT заявката да е приоритетна
     */
    var $highPriority = FALSE;


    /**
     * Масив за хинтове на индекси
     */
    public $indexes = array();


    /**
     * Флаг дали заявката е изпълнена
     */
    private $executed = FALSE;
    

    /**
     * Масив за съхранение на виртуалните полета
     */
    private $virtualFields = array();


    /**
     * Условия към отделните завявки, които композират UNION
     */
    private $unions = array();


    /**
     * Данните на записите, които ще бъдат изтрити. Инициализира се преди всяко изтриване.
     *
     * @var array
     * @see getDeletedRecs()
     */
    private $deletedRecs = array();
    
    /**
     * Масив от опции на SQL SELECT заявки
     *
     * @see http://dev.mysql.com/doc/refman/5.0/en/select.html
     *
     * @var array
     */
    protected $_selectOptions = array();
    
    
    /**
     * Инициализира обекта с указател към mvc класа
     */
    function init($params = array())
    {
        parent::init($params);
        
        $this->fields = $this->mvc->fields;
    }
    
    
    /**
     * Показва дадени полета от модела
     */
    function show($fields)
    {
        $this->show = arr::combine($this->show, $fields);
        
        return $this;
    }
    
    
    /**
     * Добавя с 'AND' ново условие във WHERE клаузата
     */
    function where($cond, $or = FALSE)
    {
        if (is_array($cond)) {
            $cond = $this->substituteArray($cond);
        }
        
        if ($cond !== NULL && $cond !== FALSE && $cond !== "") {
            
            if (is_int($cond) || (intval($cond) . '' == $cond)) {
                
                $cond = "#id = {$cond}";
            }
            
            $lastCondKey = count($this->where)-1;
            
            if($or && ($lastCondKey >= 0)) {
                $lastCond = & $this->where[$lastCondKey];
                
                if(!isset($this->areBracketsPlaced[$lastCondKey])) {
                    $lastCond = "({$lastCond})";
                    $this->areBracketsPlaced = TRUE;
                }
                
                $lastCond .= " OR ({$cond})";
            } else {
                $this->where[] = $cond;
            }
        }
        
        return $this;
    }
    
    
    /**
     * Добавя с 'OR' ново условие към последното условие, добавено с AND
     */
    function orWhere($cond)
    {
        return $this->where($cond, TRUE);
    }
    
    
    /**
     * Добавя с 'AND' и/или с 'OR' ново условие за масива
     * 
     * @param string $field - Името на полето
     * @param array $arr - Масив с всички данни
     * @param boolean $or - Дали да е 'OR' 
     * @param boolean $orToPrevious - Дали да се залепи с 'OR' към предишния where
     */
    function whereArr($field, $condArr, $or = FALSE, $orToPrevious=FALSE)
    {
        // Ако е масив
        if (is_array($condArr)) {
            
            // Дали за първи път обхождаме масива
            $first = TRUE;
            
            // Обхождаме масива
            foreach ($condArr as $cond) {
                
                // Ако за първи път
                if (($first || !$or) && !$orToPrevious) {
                    
                    // Добавяме във where
                    $this->where(array("#{$field} = '[#1#]'", $cond));
                    
                } else {

                    // Добавяме в orWhere
                    $this->orWhere(array("#{$field} = '[#1#]'", $cond));   
                }
                
                // Отбелязваме, че вече сме влезли за първи път
                $first = FALSE;
            }
        }
    }
    
    
    /**
     * Добавя с 'OR' ново условие за WHERE
     * 
     * @param string $field - Името на полето
     * @param array $arr - Масив с всички данни
     * @param boolean $orToPrevious - Дали да се залепи с 'OR' към предишния where
     */
    function orWhereArr($field, $condArr, $orToPrevious = FALSE)
    {
        $this->whereArr($field, $condArr, TRUE, $orToPrevious);
    }


    /**
     * Добавя с OR условие, посоченото поле да съдържа поне един от ключовете в keylist
     */
    function orLikeKeylist($field, $keylist)
    {
        return $this->likeKeylist($field, $keylist, TRUE);
    }


    /**
     * Добавя с AND условие, посоченото поле да съдържа поне един от ключовете в keylist
     */
    function likeKeylist($field, $keylist, $or = FALSE)
    {
        $keylistArr = keylist::toArray($keylist);
        
        // Не споделяме с анонимния и системния потребител
        if(stripos($field, 'shared') !== FALSE) {
            unset($keylistArr[-1], $keylistArr[0]);
        }

        $isFirst = TRUE;

        if(count($keylistArr)) {
            foreach($keylistArr as $key => $value) {

                $cond = "LOCATE('|{$key}|', #{$field})";

                if($or === TRUE) {
                    $this->orWhere($cond);
                } else {
                    $this->where($cond);
                }

                $or = TRUE;
            }
        }

        return $this;
    }

    
    /**
     * Добавя с AND условие, посоченото поле да съдържа поне един от ключовете в keylist
     * Алтернативна функция с Regexp
     */
    function likeKeylist1($field, $keylist, $or = FALSE)
    {
        $keylistArr = keylist::toArray($keylist);

        $isFirst = TRUE;

        if(count($keylistArr)) {
            $regExp = implode('|', $keylistArr);
            $this->where("#{$field} REGEXP '\\\|({$regExp})\\\|'", $or);
        }

        return $this;
    } 
    

    /**
     * Добавя ново условие с LIKE във WHERE клаузата
     *
     * @param string $field - Името на полето
     * @param string $val - Стойността
     * @param boolean $like - Дали да е LIKE или NOT LIKE
     * @param boolean $or - Дали да се добавя с OR
     */
    function like($field, $val, $like=TRUE, $or=FALSE)
    {
        if ($like) {
            $like = 'LIKE';
        } else {
            $like = "NOT LIKE";
        }

        $cond = "#{$field} {$like} '%[#1#]%'";

        if($or === TRUE) {
            $this->orWhere(array($cond, $val));
        } else {
            $this->where(array($cond, $val));
        }

        return $this;
    }


	/**
     * Добавя новоусловие с OR и LIKE във WHERE клаузата
     * 
     * @param string $field - Името на полето
     * @param string $val - Стойността
     * @param boolean $like - Дали да е LIKE или NOT LIKE
     */
    function orLike($field, $val, $like=TRUE)
    {
        
        return $this->like($field, $val, $like, TRUE);
    }
    
    
    /**
     * Добавя полета, по които ще се групира
     */
    function groupBy($fields)
    {
        $fields = arr::make($fields);
        
        foreach ($fields as $f) {
            if (!empty($f)) {
                $this->groupBy[$f] = TRUE;
            }
        }
        
        return $this;
    }
    
    
    /**
     * Връща 'GROUP BY' клаузата
     */
    function getGroupBy()
    {
        if (count($this->groupBy) > 0) {
            foreach ($this->groupBy as $f => $true) {
                $groupBy .= ($groupBy ? ", " : "") . $f;
            }
            
            return "\nGROUP BY {$groupBy}";
        }
    }
    
    
    /**
     * Поставя условие поле да се съдържа в даден масив
     * 
     * @param string $field - поле
     * @param mixed $values - масив или стринг от стойности
     * @param boolean $not - Дали да се съдържа или не в масива
     */
    public function in($field, $values, $not = FALSE, $or = FALSE)
    {
    	$values = arr::make($values);
    	if (!$values) return ;
    	
    	// Ескейпване на стойности
    	array_walk($values, function (&$a) {$a = "'" . $a . "'";});
    	
    	// Обръщане на масива в стринг
    	$values = implode(',', $values);
    	
    	if(!$not){
    		$this->where("#{$field} IN ({$values})", $or);
    	} else {
    		$this->where("#{$field} NOT IN ({$values})", $or);
    	}
    }
    
    
    /**
     * Поставя условие полето да е между две стойностти
     * 
     * @param string $field - поле
     * @param mixed $from - от
     * @param mixed $to - до
     */
    public function between($field, $from, $to)
    {
    	$this->where(array("#{$field} BETWEEN '[#1#]' AND '[#2#]'", $from, $to));
    }
    
    
    /**
     * Поставя условие поле да не се съдържа в даден масив
     * 
     * @param string $field - поле
     * @param mixed $values - масив или стринг от стойности
     */
    public function notIn($field, $values, $or = FALSE)
    {
    	return $this->in($field, $values, TRUE, $or);
    }
    
    
    /**
     * Добавя полета, по които ще се сортира. Приоритетните се добавят отпред
     */
    function orderBy($fields, $direction = '', $priority = FALSE)
    {
        $fields = arr::make($fields);
        
        foreach ($fields as $f => $d) {
            
            $order = new stdClass();
            
            if (is_int($f)) {
                $order->field = $d;
                $order->direction = $direction;
            } else {
                $order->field = $f;
                $order->direction = $d;
            }
            
            if($order->field{0} != '#') {
                $order->field = '#' . $order->field;
            }
            
            $fieldObj = $this->getField($order->field);
            
            // Ако полето е функционално и има атрибут 'orderAs', то в
            // сортирането се използва името на полето записано в orderAs
            // иначе сортиране не се прави
            if($fieldObj->kind == 'FNC') {
                if($fieldObj->orderAs) {
                    $order->field = $fieldObj->orderAs;
                } else {
                    
                    continue;
                }
            }
            
            if($priority) {
                array_unshift($this->orderBy, $order);
            } else {
                $this->orderBy[] = $order;
            }
        }
        
        return $this;
    }
    
    
    /**
     * Връща 'ORDER BY' клаузата
     * 
     * @param boolean $useAlias - дали полето за подредба да е с пълното си име или с alias-а си
     */
    function getOrderBy($useAlias = FALSE)
    {
        if (count($this->orderBy) > 0) {
            foreach ($this->orderBy as $order) {
            	$fldName = ($useAlias === FALSE) ? $this->expr2mysql($order->field) : str_replace("#", '', $order->field);
            	
                $orderBy .= ($orderBy ? ", " : "") . $fldName .
                " " . strtoupper($order->direction);
            }
           
            return "\nORDER BY {$orderBy}" ;
        }
    }
    
    
    /**
     * Добавя максимален брой на редовете в резултата. По подразбиране е без лимит
     */
    function limit($l)
    {
        $this->limit = $l;
        
        return $this;
    }
    
    
    /**
     * Задава начален индекс на редовете в резултата. По подразбиране е 0
     */
    function startFrom($s)
    {
        $this->start = $s;
        
        return $this;
    }
    
    
    /**
     * Връща 'LIMIT' клаузата
     */
    function getLimit()
    {
        if ($this->limit === NULL && $this->start === NULL) {
            return "";
        }
        
        if ($this->limit >= 0 && $this->start === NULL) {
            return "\nLIMIT {$this->limit}";
        }
        
        if ($this->limit === NULL) {
            $this->limit = "18446744073709551615";
        }
        
        return "\nLIMIT {$this->start},{$this->limit}";
    }
    
    
    /**
     * Изпълнява SELECT заявка, като ако е зададено условие добавя го като AND във WHERE
     */
    function select()
    {
        if($this->mvc->invoke('BeforeSelect', array(&$numRows, &$this)) === FALSE) {
            
            return $numRows;
        }
        
        $query = $this->buildQuery();
        
        $db = $this->mvc->db;
        
        DEBUG::startTimer(cls::getClassName($this->mvc) . ' SELECT ');
        
        $this->dbRes = $db->query($query);
        
        DEBUG::stopTimer(cls::getClassName($this->mvc) . ' SELECT ');
        
        $this->executed = TRUE;
        
        return $this->numRec();
    }
    
    
    /**
     * SQL кода, отговарящ на този обект-заявка.
     *
     * @return string
     */
    function buildQuery()
    {
        if(count($this->unions)) {
        	$count = count($this->unions);
        	
            foreach($this->unions as $cond) {
                $q = clone($this);
                $q->unions = NULL;
                $q->orderBy = NULL;
                $q->limit = NULL;
                $q->start = NULL;
                $q->where($cond);
                
                $string = ($count > 1) ? "(" . $q->buildQuery() . ")" : $q->buildQuery();
                $query .= ($query ? "\nUNION\n" : '') . $string;
            }
           
            $query .= $this->getOrderBy(TRUE);
            $query .= $this->getLimit();
        } else {
            $wh = $this->getWhereAndHaving();
            $query = "SELECT ";

            if(($this->mvc->highPriority && $this->limit == 1) || $this->highPriority) {
                $query .= " HIGH_PRIORITY ";
            }
            
            if (!empty($this->_selectOptions)) {
                $query .= implode(' ', $this->_selectOptions) . ' ';
            }
            
            $query .= $this->getShowFields();
            $query .= "\nFROM ";
            
            $query .= $this->getTables();

            $query .= $wh->w;
            $query .= $this->getGroupBy();
            $query .= $wh->h;
            
            $query .= $this->getOrderBy();
            $query .= $this->getLimit();
        }
        
        return $query;
    }
    
    
    /**
     * Преброява записите, които отговарят на условието, което се добавя като AND във WHERE
     */
    function count($cond = NULL, $limit = 0)
    {
        if($this->mvc->invoke('BeforeCount', array(&$res, &$this, &$cond)) === FALSE) {
            
            return $res;
        }
        
        $temp = clone($this);
        
        $temp->where($cond);

        if($limit) {
            $temp->limit($limit);
        }
        
        $wh = $temp->getWhereAndHaving();
        
        $options = '';
        
        if (!empty($this->_selectOptions)) {
            $options = implode(' ', $this->_selectOptions);
        }
        
        $query = "SELECT {$options}\n   count(*) AS `_count`";
        if(count($this->selectFields("#kind == 'XPR' || #kind == 'EXT'"))) {
            $fields = $temp->getShowFields();
            $query .= ($fields ? ',' : '') . $fields;
        }
        
        $query .= "\nFROM ";
        $query .= $temp->getTables();

        $query .= $wh->w;
        $query .= $wh->h;
        $query .= $temp->getGroupBy();
        $query .= $temp->getLimit();

        if ($temp->useHaving || $temp->getGroupBy() || ($temp->limit)) {
            $query =  str_replace("count(*) AS `_count`", "1 AS `fix_val`", $query);
            $query = "SELECT COUNT(*) AS `_count` FROM ({$query}) as COUNT_TABLE";
        }

        $db = $temp->mvc->db;
        
        DEBUG::startTimer(cls::getClassName($this->mvc) . ' COUNT ');
        
        $dbRes = $db->query($query);
        
        DEBUG::stopTimer(cls::getClassName($this->mvc) . ' COUNT ');
        
        $r = $db->fetchObject($dbRes);
        
        // Освобождаваме MySQL резултата
        $db->freeResult($dbRes);
        
        // Връщаме брояча на редовете
        return $r->_count;
    }
    
    
    /**
     * Изпълнява DELETE заявка, като ако е зададено условие добавя го като AND във WHERE
     */
    function delete($cond = NULL)
    {
 
        if($this->mvc->invoke('BeforeDelete', array(&$numRows, &$this, $cond)) === FALSE) {
            
            return $numRows;
        }
        // Запазваме "важните" данни на записите, които ще бъдат изтрити, за да бъдат те 
        // достъпни след реалното им изтриване (напр в @see on_AfterDelete())
        if($this->mvc->fetchFieldsBeforeDelete) {
            $this->deletedRecs = $this->fetchAll($cond, $this->mvc->fetchFieldsBeforeDelete);
        }
        
        $this->where($cond);
        
        $wh = $this->getWhereAndHaving(FALSE, TRUE);
        
        $this->getShowFields(TRUE);
        
        
        $orderBy = $this->getOrderBy();
        $limit   = $this->getLimit();
        
         
        $query = "DELETE FROM";
        $query .= $this->getTables();

        $query .= $wh->w;
        $query .= $wh->h;
        $query .= $orderBy;
        $query .= $limit;

        $db = $this->mvc->db;
        
        DEBUG::startTimer(cls::getClassName($this->mvc) . ' DELETE ');
        
        $db->query($query);
        
        DEBUG::stopTimer(cls::getClassName($this->mvc) . ' DELETE ');
        
        $affectedRows = $db->affectedRows();
        $this->mvc->invoke('AfterDelete', array(&$affectedRows, &$this, $cond));
        
        $this->mvc->dbTableUpdated();
        
        return $affectedRows;
    }
    
    
    /**
     * Записите, които са били изтрити при последното @link core_Query::delete() извикване.
     *
     * Във всеки запис са налични само "важните" полета, т.е. полетата, определени от
     * @link core_Query::getKeyFields().
     *
     * @return array масив от stdClass
     */
    function getDeletedRecs()
    {
        return $this->deletedRecs;
    }
    
    
    /**
     * Връща поредния запис от заявката
     */
    function fetch($cond = NULL)
    {
        if (!$this->executed) {
            $this->where($cond);
            $this->select();
        }
        
        $db = $this->mvc->db;
        
        if (is_object($this->dbRes)) {
            
            // Прочитаме реда от таблицата
            $arr = $db->fetchArray($this->dbRes);
            
            $rec = new stdClass();
            
            if ($arr) {
                if (count($arr) > 0) {
                    
                    foreach ($arr as $fld => $val) {
                        
                        if (is_object($this->fields[$fld]->type)) {
                            $rec->{$fld} = $this->fields[$fld]->type->fromMysql($val);
                        } else {
                            wp($this, $fld);
                        }
                    }
                }
                
                if (count($this->virtualFields) > 0) {
                    $virtualFields = array_intersect($this->virtualFields, array_keys($this->show));
                    
                    foreach ($virtualFields as $fld) {
                        $this->mvc->invoke('Calc' . $fld, array(&$rec));
                    }
                }
            } else {
            
                $db->freeResult($this->dbRes);
                
                $this->dbRes = NULL;

                return FALSE;
            }
            
            // Изпълняваме външни действия, указани за след четене
            $this->mvc->invoke('AfterRead', array(&$rec));
            
            return $rec;
        }
    }


    /**
     * Същия метод като ->fetch(), но с кеширане на резултата
     */
    public function fetchAndCache($cond = NULL)
    {
        $rec = $this->fetch($cond);
        if($rec) {
            $this->mvc->_cachedRecords[$rec->id . '|*'] =  clone $rec;
        }

        return $rec;
    }
    
    
    /**
     * Извлича всички записи на заявката.
     *
     * Не променя състоянието на оригиналния обект-заявка ($this), тъй като работи с негово
     * копие.
     *
     * @param $cond string|array условия на заявката
     * @param $fields array масив или стрингов списък ('поле1, поле2, ...') с имена на полета.
     * @param $params array масив с допълнителни параметри на заявката
     * @return array масив от записи (stdClass)
     */
    function fetchAll($cond = NULL, $fields = NULL, $params = array())
    {
        $copy = clone($this);
        
        if (isset($cond)) {
            $copy->where($cond);
        }
        
        if (isset($fields)) {
            $copy->show($fields);
        }
        
        if (isset($params['orderBy'])) {
            $copy->orderBy($params['orderBy']);
        }
        
        if (isset($params['groupBy'])) {
            $copy->orderBy($params['groupBy']);
        }
        
        if (isset($params['groupBy'])) {
            $copy->orderBy($params['groupBy']);
        }
        
        if (isset($params['startFrom'])) {
            $copy->startFrom($params['startFrom']);
        }
        
        if (isset($params['limit'])) {
            $copy->limit($params['limit']);
        }
        
        $recs = array();
        
        while ($rec = $copy->fetch()) {
            $recs[$rec->id] = $rec;
        }
        
        return $recs;
    }
    
    
    /**
     * Връща селектираните записи при последната заявка SELECT
     */
    function numRec()
    {
        if (is_object($this->dbRes) && $this->executed) {
            
            return $this->dbRes->num_rows;
        }
    }
    
    
    /**
     * Връща WHERE и HAVING клаузите
     * 
     * @param boolean $pureClause - Дали да добави ключовите думи пред клаузите
     */
    function getWhereAndHaving($pureClause=FALSE, $isDelete = FALSE)
    {
        $this->useHaving = FALSE;
        
        $clause = new stdClass();
        $clause->w = $clause->h = $where = $having = '';
        
        // Начало на добавка
        // Добавка за връзване по външен ключ
        if (count($external = $this->selectFields("#kind == 'EXT'"))) {
            foreach ($external as $name => $fieldRec) {
                $externalFieldName = $fieldRec->externalFieldName ? $fieldRec->externalFieldName : 'id';
                $externalFieldName = str::phpToMysqlName($externalFieldName);
                
                if ($fieldRec->externalKey && !$isDelete) {
                    $mvc = cls::get($fieldRec->externalClass);
                    $this->where("#{$fieldRec->externalKey} = `{$mvc->dbTableName}`.`{$externalFieldName}`");
                    $this->tables[$mvc->dbTableName] = TRUE;
                } elseif(isset($fieldRec->remoteKey) && !$isDelete) {
                	$mvc = cls::get($fieldRec->externalClass);
                	$remoteKey = str::phpToMysqlName($fieldRec->remoteKey);
                	$this->where("`{$mvc->dbTableName}`.`{$remoteKey}` = `{$this->mvc->dbTableName}`.`{$externalFieldName}`");
                	$this->tables[$mvc->dbTableName] = TRUE;
                }
            }
        }
        
        if (count($this->where) > 0) {
            
            if(count($this->where) > 1) {
                foreach($this->where as $cl) {
                    $nw[$cl] = (stripos($cl, 'locate(') !== FALSE) + (stripos($cl, 'search_keywords') !== FALSE) + (stripos($cl, 'in (') !== FALSE);
                }            
                arsort($nw);
                $this->where = array_keys($nw);
            }

            foreach ($this->where as $expr) {
                if(stripos($expr, '#id in (') !== FALSE) {
                    $expr = $this->expr2mysql($expr);
                    if ($this->useExpr) {
                        $having = "({$expr})" . ($having ? " AND\n   " : "   ") . $having;
                        $this->exprShow = arr::combine($this->exprShow, $this->usedFields);
                    } else {
                        $where = "({$expr})" . ($where ? " AND\n   " : "   ") . $where;
                    }
                } else {
                    $expr = $this->expr2mysql($expr);

                    if ($this->useExpr) {
                        $having .= ($having ? " AND\n   " : "   ") . "({$expr})";
                        $this->exprShow = arr::combine($this->exprShow, $this->usedFields);
                    } else {
                        $where .= ($where ? " AND\n   " : "   ") . "({$expr})";
                    }
                }
            }
            
            if ($where) {
                if ($pureClause) {
                    $clause->w = "\n{$where}";
                } else {
                    $clause->w = "\nWHERE \n{$where}";
                }
            }
            
            if ($having) {
                
                $this->useHaving = TRUE;
                
                if ($pureClause) {
                    $clause->h = "\n{$having}";
                } else {
                    $clause->h = "\nHAVING \n{$having}";
                }
            }
        }
        
        return $clause;
    }
    
    
    /**
     * Връща полетата, които трябва да се показват
     */
    function getShowFields($isDelete = FALSE)
    {
        // Ако нямаме зададени полета, слагаме всички от модела,
        // без виртуалните и чуждестранните
        if (!count($this->show) || $this->show['*']) {
            $this->show = $this->selectFields("");
        }
        
        // Добавяме използваните полета - изрази
        $this->show = arr::combine($this->show, $this->exprShow);
        
        if(count($this->orderBy)) {
            foreach($this->orderBy as $ordRec) {
                $fld = $this->fields[ltrim($ordRec->field, '#')];
                if($fld->kind == 'XPR' || $fld->kind == 'EXT') {
                    $this->show[$fld->name] = TRUE;
                }
            }
        }

        // Задължително показваме полето id
        if($this->fields['id']) {
            $this->show['id'] = TRUE;
        }
        
        foreach ($this->show as $name => $dummy) {
            $f = $this->getField($name);
            
            if ($f->kind == "FNC") {
                $depends = $f->dependFromFields ? $f->dependFromFields : NULL;
                
                if(is_string($depends)) $depends = str_replace('|', ',', $depends);
                $show = arr::combine($show, $this->selectFields("#kind == 'FLD'", $depends));
                $this->virtualFields[] = $name;
            } else {
                $show[$name] = $name;
            }
        }
 
        $fields = '';
        
        foreach ($show as $name => $dummy) {
            $f = $this->getField($name);
            
            $this->realFields[] = $name;
            
            $fields .= $fields ? ",\n   " : "\n   ";
            
            switch ($f->kind) {
                case "FLD" :
                    $tableName = $this->mvc->dbTableName;
                    $mysqlName = str::phpToMysqlName($name);
                    $fields .= "`{$tableName}`.`{$mysqlName}`";
                    break;
                case "EXT" :
                	if($isDelete) break;
                    $mvc = cls::get($f->externalClass);
                    $tableName = $mvc->dbTableName;
                    $this->tables[$tableName] = TRUE;
                    $mysqlName = str::phpToMysqlName($f->externalName);
                    $fields .= "`{$tableName}`.`{$mysqlName}`";
                    break;
                case "XPR" :
                    if($isDelete) break;
                    $fields .= $this->expr2mysql($f->expression);
                    break;
                default :
                error("@Непознат вид на полето",  $f->kind, $name);
            }
            
            $fields .= " AS `{$name}` ";
        }
        
        return $fields;
    }
    
  
    
    
    
    /**
     * Връща таблиците които трябва да се обединят
     * @todo Joint Left
     */
    function getTables()
    {
        $tables = "\n   `" . $this->mvc->dbTableName . "`";
        
        $tables .= ' ' . $this->getIndexes() . ' ';

        foreach ($this->tables as $name => $true) {
            $tables .= ",\n   `{$name}`";
        }
        
        return $tables . " ";
    }
    
    
    /**
     * Конвертира израз с полета започващи с '#' към MySQL израз
     */
    function expr2mysql($expr)
    {
        $this->useExpr = FALSE;
        $this->usedFields = array();
        $res = str::prepareExpression($expr, array(
                &$this,
                'getMysqlField'
            ));
        
        return $res;
    }
    
    
    /**
     * Връща пълното MySQL име на полето
     */
    function getMysqlField($name)
    {
        $field = $this->getField($name);
        
        // Проверка за грешки
        if (!is_object($field)) {
            error("Несъществуващо поле", "'{$name}'");
        }
        
        if ($field->kind === 'FNC') {
            error("@Функционалните полета не могат да се използват в SQL изрази", $name);
        }
        
        if ($field->kind == 'FLD') {
            $mysqlName = str::phpToMysqlName($name);
            $tableName = $this->mvc->dbTableName;
        } elseif ($field->kind === 'EXT') {
            $extMvc = & cls::get($field->externalClass);
            $tableName = $extMvc->dbTableName;
            $this->tables[$tableName] = TRUE;
            $mysqlName = str::phpToMysqlName($field->externalName);
        } elseif ($field->kind == 'XPR') {
            $this->exprShow[$name] = TRUE;
            $this->useExpr = TRUE;
            
            return "`" . $name . "`";
        } else {
            // Непознат тип поле ($field->kind)
            error($field);
        }
        
        $res = "`{$tableName}`.`{$mysqlName}`";
        
        $this->usedFields[$name] = $name;

        return $res;
    }
    
    
    /**
     * Връща хеш на заявката за търсене
     * Ако $excludeStartAndLimit = TRUE, не се вземат в предвид
     */
    function getHash($excludeStartAndLimit = FALSE)
    {
        $q = clone($this);
        if($excludeStartAndLimit) {
            $q->startFrom(NULL);
            $q->limit(NULL);
        }

        $res = md5($q->buildQuery());

        return $res;
    }
    
    
    /**
     * Прави субституция в нулевия елемент на масива
     * със стойностите, които са указани в следващите елементи на масива
     * N-тия елемент на масива се слага на място означено като [#N#]
     *
     * @param array $arr
     * @return string
     */
    function substituteArray($arr)
    {
        $key = Mode::getProcessKey();
        
        $exp = $arr[0];
        
        $cntArr = count($arr);
        for ($i = 1; $i < $cntArr; $i++) {
            $a[] = "[#{$i}#]";
            $b[] = "[#{$i}{$key}#]";
            $c[] = $this->mvc->db->escape($arr[$i]);
        }
        
        $exp = str_replace($a, $b, $exp);
        $exp = str_replace($b, $c, $exp);
        
        return $exp;
    }
    
    
    /**
     * Генерира SQL WHERE клауза от масив
     *
     * Метода "слепя" елементите на масива използвайки зададената логическа операция (AND или OR).
     * Възможно е всеки елемент на масива да бъде също масив. В този случай метода първо (рекусивно)
     * слепя неговите елементи. Ако ключа на елемент-масив е нечислов, той се приема за логическа
     * операция при слепването. Ако е числов - операцията за слепване е AND.
     *
     * Примери:
     *
     * buildConditions(
     * array(
     * array(
     * 'OR' => array('T1', 'T2')
     * ),
     * array(
     * 'OR' => array('T3', 'T4')
     * )
     * )
     * );
     *
     * ще върне
     *
     * ((Т1 OR T2) AND (T3 OR T4))
     *
     * -----------------------------------------------------------------------------------------
     *
     * buildConditions(
     * array(
     * 'T1',
     * 'OR' => array(
     * 'AND' => array(
     * 'T2', 'T3'
     * ),
     * 'T4'
     * ),
     * array('T5', 'T6')
     * )
     * );
     *
     * ще върне
     *
     * (T1 AND ((T2 AND T3) OR T4) AND (T5 AND T6))
     *
     * -----------------------------------------------------------------------------------------
     *
     * @param array $conditions
     * @param string $op AND или OR
     */
    static function buildConditions($conditions, $op = 'AND')
    {
        if (is_array($conditions)) {
            foreach ($conditions as $i=>$terms) {
                switch(strtolower(trim($i))) {
                    case 'or' :
                    case 'and' :
                        $conditions[$i] = static::buildConditions($terms, $i);
                        break;
                    default :
                    $conditions[$i] = static::buildConditions($terms);
                }
            }
            
            if (count($conditions) > 1) {
                $conditions = '(' . implode(") {$op} (", $conditions) . ')';
            } else {
                $conditions = reset($conditions);
            }
        }
        
        return $conditions;
    }
    
    
    /**
     * Добавя MySQL SELECT опция преди изпълнение на заявката
     *
     * @link http://dev.mysql.com/doc/refman/5.0/en/select.html
     *
     * Използването на SELECT опции може да ускори някои SQL заявки.
     *
     * @param string $option
     */
    public function addOption($option)
    {
        static $optionPos = array(
            'ALL'                 => 0,
            'DISTINCT'            => 0,
            'DISTINCTROW'         => 0,
            'HIGH_PRIORITY'       => 1,
            'STRAIGHT_JOIN'       => 2,
            'SQL_SMALL_RESULT'    => 3,
            'SQL_BIG_RESULT'      => 3,
            'SQL_BUFFER_RESULT'   => 4,
            'SQL_CACHE'           => 5,
            'SQL_NO_CACHE'        => 5,
            'SQL_CALC_FOUND_ROWS' => 6
        );
        
        $option = strtoupper($option);
        
        if (isset($optionPos[$option])) {
            $this->_selectOptions[$optionPos[$option]] = $option;
        }
    }


    /**
     * Задава условно обединиение на записите
     * При изграждането на текста на заявката, ще се направи обединение на заявки, 
     * Които са същите като оригиналната, но с добавено условието $cond 
     */
    public function setUnion($cond)
    {
        $this->unions[] = $cond;
    }


    /**
     * Добавя индекс, който се форсира за използване
     */
    public function useIndex($index)
    {
        $this->indexes[$index] = TRUE;
    }


    /**
     * Добавя индекс, който се форсира за използване
     */
    public function getIndexes()
    {
        $res = '';

        if(count($this->indexes)) {
            $res = "\nUSE INDEX(" . implode(',', array_keys($this->indexes)) . ")";
        }

        return  $res;
    }


}