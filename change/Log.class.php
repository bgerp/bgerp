<?php 


/**
 * Клас 'change_Log - Логове
 *
 * @category  vendors
 * @package   change
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class change_Log extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, change_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'docClass, docId, field, oldValue, newValue';
    
    
    /**
     * Заглавие
     */
    var $title = 'Логове';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('docClass' , 'class', 'caption=Документ->Клас');
        $this->FLD('docId' , 'int', 'caption=Документ->Обект');
        $this->FLD('field', 'varchar', 'caption=Поле');
        $this->FLD('oldValue', 'richtext', 'caption=Стара стойност');
        $this->FLD('newValue', 'richtext', 'caption=Нова стойност');
    }
    
    
    /**
     * Създава запис в лога
     * 
     * @param mixed $docClass - Името или id на класа
     * @param array $fieldsArr - Масив с полетата, които ще се запишат
     * @param object $oldRec - Стара стойност
     * @param object $newRec - Нова стойност
     */
    static function create($docClass, $fieldsArr, $oldRec, $newRec)
    {
        // Резултатния масив, който ще връщаме
        $recsArr = array();
        
        // Ако е id на клас
        if (is_numeric($docClass)) {
            
            // Използваме id' то
            $docClassId = $docClass;   
        } else {
            
            // Вземаме id' то на класа
            $docClassId = core_Classes::fetchIdByName($docClass);
        }
        
        // Обхождаме масива с полетата
        foreach ((array)$fieldsArr as $field) {
            
            // Ако няма промяна в полето
            if ($oldRec->$field == $newRec->$field) continue;
            
//            if (!isset($oldRec->$field)) continue ;
            
            // Обекта, който ще записваме
            $rec = new stdClass();
            $rec->docClass = $docClassId;
            $rec->docId = $oldRec->id;
            $rec->field = $field;
            $rec->oldValue = $oldRec->$field;
            $rec->newValue = $newRec->$field;
            
            // Записваме
            static::save($rec);
            
            // Добавяме в масива
            $recsArr[] = $rec;
        }
        
        return $recsArr;
    }
    
    
    /**
     * Подготвяме записите за лога във вербален вид
     * 
     * @param mixed $docClass - Името или id на класа
     * @param string $docId - id' на документа
     * 
     * @return array $res - Масив с данни
     */
    static function prepareLogRow($docClass, $docId)
    {
        // Ако е id на клас
        if (is_numeric($docClass)) {
            
            // Използваме id' то
            $docClassId = $docClass;   
        } else {
            
            // Вземаме id' то на класа
            $docClassId = core_Classes::fetchIdByName($docClass);
        }
        
        // Масив с данните
        $res = array();
        
        // Вземаме всички записи от класа и документи и ги подреждаме по дата
        $query = static::getQuery();
        $query->where("#docClass = '{$docClassId}'");
        $query->where("#docId = '{$docId}'");
//        $query->orderBy("field");
        $query->orderBy("createdOn", 'DESC');
        
        // Обхождаме масива
        while ($rec = $query->fetch()) {
            
            // Вербалнате стойности
            $row = static::recToVerbal($rec, 'createdBy, createdOn');
            
            // Вербални стойности на останалите полета
            $row->field = static::getFieldCaption($rec);
            $row->oldValue = static::getValue($rec, 'oldValue');
            $row->newValue = static::getValue($rec, 'newValue');
            $row->docClass = cls::get($rec->docClass);
            
            // Добавяме в масива
            $res[] = $row;
        }
        
        return $res;
    }
    
    
    /**
     * Връща заглавието на полето
     * 
     * @param object $rec - Записите
     */
    static function getFieldCaption($rec)
    {
        // Инстанция на класа
        $class = cls::get($rec->docClass);
        
        // Заглавието на полето
        $fieldCaption = $class->fields[$rec->field]->caption;
        
        return $fieldCaption;
    }
    
    
    /**
     * Връща вербалната стойност на данните в зададеното поле
     * 
     * @param object $rec - Записите
     * @param string $field - Полето
     * 
     * @return string $value - Стойността
     */
    static function getValue($rec, $field='oldValue')
    {
        // Старата стойност
        $value = $rec->{$field};
        
        // Инстанция на класа
        $class = cls::get($rec->docClass);
        
        // Типа на полето
        $type = $class->fields[$rec->field]->type;

        // Ако има стойност
        if (trim($value)) {
            
            // Ако типа е родител на наследник или от тип type_Key
            if (is_a($type, 'type_Key')) {
                
                // Стойността във вербален вид
                $value = $type->toVerbal($value);
            } else {
                
                // Тримваме и ескейпваме текста
                $value = core_Type::escape(str::limitLen($value, 70));
            }    
        } else {
            
            // Ако няма стойност
            $value = '';
        }
        
        return $value;
    }
    
    
    /*
     * НАЧАЛО
     * За премахване
     */
    /**
     * Подготвяме записите за лога
     * 
     * @param mixed $docClass - Името или id на класа
     * @param string $docId - id' на документа
     * 
     * @return array $res - Масив с данни
     */
    static function prepareLog($docClass, $docId)
    {
        // Ако е id на клас
        if (is_numeric($docClass)) {
            
            // Използваме id' то
            $docClassId = $docClass;   
        } else {
            
            // Вземаме id' то на класа
            $docClassId = core_Classes::fetchIdByName($docClass);
        }
        
        // Масив с данните
        $res = array();
        
        // Вземаме всички записи от класа и документи и ги подреждаме по дата
        $query = static::getQuery();
        $query->where("#docClass = '{$docClassId}'");
        $query->where("#docId = '{$docId}'");
//        $query->orderBy("field");
        $query->orderBy("createdOn", 'DESC');
        
        // Обхождаме масива
        while ($rec = $query->fetch()) {
            
            // Добавяме в масива
            $res[] = $rec;
        }
        
        return $res;
    }
    
    
    /**
     * Рендираме лога
     * 
     * @param array $logArr - Масив с данните за рендиране
     * 
     * @param string $htmlStr - HTML стринг
     */
    static function renderLog($logArr)
    {
        // Ако няма подадени данни
        if (!count($logArr)) return ;
        
        // Отваряме таблицата
        $htmlStr = '<table>';
        
        // Обхождаме масива
        foreach ((array)$logArr as $rec) {
            
            // Старата стойност
            $oldValue = $rec->oldValue;
            
            // Инстанция на класа
            $class = cls::get($rec->docClass);
            
            // Заглавието на полето
            $fieldCaption = $class->fields[$rec->field]->caption;
            
            // Вербалнате стойности
            $row = static::recToVerbal($rec, 'createdBy, createdOn');
            
            // Типа на полето
            $type = $class->fields[$rec->field]->type;

            // Ако има стара стойност
            if (trim($oldValue)) {
                
                // Ако типа е родител на наследник или от тип type_Key
                if (is_a($type, 'type_Key')) {
                    
                    // Стара стойност във вербален вид
                    $oldValue = $type->toVerbal($oldValue);
                } else {
                    
                    // Тримваме и ескейпваме текста
                    $oldValue = core_Type::escape(str::limitLen($oldValue, 70));
                }    
            } else {
                
                // Ако няма стара стойнос
                $oldValue = '[Няма стойност]';
            }
            
            // Създаваме линк
            $link = HT::createLink($oldValue, array('change_Log', 'showLog', $rec->id, 'ret_url' => TRUE));
            
            // Добавяме към резултата
            $htmlStr .= "<tr><td>{$row->createdBy}</td><td>{$row->createdOn}</td><td>{$fieldCaption}:</td> <td>{$link}</td></tr>";
        }

        // Затваряме таблицата        
        $htmlStr .= '</table>';

        return $htmlStr;
    }
    
    
    /**
     * Показва детайлна лог информация
     */
    function act_ShowLog()
    {
        // id'то на документа
        $id = Request::get('id', 'int');
        
        // Вземаме записа
        $rec = static::fetch($id);

        // Очакваме да има валиден запис
        expect($rec, 'Няма такъв запис');
        
        // Инстанция на класа
        $class = cls::get($rec->docClass);
        
        // Очакваме да имама права за single на съответния документ
        $class->requireRightFor('single', $rec->docId);
        
        // Шаблон
        $tpl = new ET("<div class='changeLog'> <div class='newValue'>[#newValue#] </div> <div class='changes'>[#changes#] </div></div>");
        
        // Подготвяме лога
        $logsArr = static::prepareLog($rec->docClass, $rec->docId);
        
        // Очакваме да има записи
        expect($logsArr);
            
        // Обхождаме масива
        foreach ((array)$logsArr as $rec) {
        
            // Ако няма такава стойност
            if (!$newArr[$rec->field]) {
                
                // Добавяме в масива
                $newArr[$rec->field] = $rec;    
            }
            
            // Старата стойност
            $oldValue = $rec->oldValue;
            
            // Заглавието на полето
            $fieldCaption = $class->fields[$rec->field]->caption;
            
            // Вербалнате стойности
            $row = static::recToVerbal($rec);
            
            // Типа на полето
            $type = $class->fields[$rec->field]->type;
            
            // Ако няма стойност
            if (!trim($oldValue)) {
                
                // Добавяме стринга
                $oldValue = '[Няма стойност]';
            } else {
                
                // Ако типа е родител на наследник или от тип type_Key
                if (is_a($type, 'type_Key')) {
                    
                    // Стара стойност във вербален вид
                    $oldValue = $type->toVerbal($oldValue);
                } else {
                    
                    // Вземаме вербалната стойност на полето
                    $oldValue = $row->oldValue;
                } 
            }
            
            // html за променените стойности
            $html = "<fieldset> <legend><i>{$fieldCaption}:</i> $row->createdOn от $row->createdBy</legend> {$oldValue} </fieldset>";
            
            // Добавяме към шаблона
            $tpl->append($html, 'changes');
        }
        
        // Обхождаме масива
        foreach ((array)$newArr as $recN) {
            
            // Вербалната стойност
            $newValue = $class->getVerbal($recN->docId, $recN->field);
            
            // Заглавието на полето
            $fieldCaption = $class->fields[$recN->field]->caption;
            
            // html на новата стойност
            $newHtml = "<fieldset style='border-color:green;'> <legend><i>{$fieldCaption}:</i></legend> {$newValue} </fieldset>";
            
            // Добавяме към шаблона
            $tpl->append($newHtml, 'newValue');
        }
        
        return static::renderWrapping($tpl);
    }
    /*
     * КРАЙ
     * За премахване
     */
}