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
}