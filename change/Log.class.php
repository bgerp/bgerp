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
     * Име на перманентните данни
     */
    const PERMANENT_SAVE_NAME = 'versionLog';
    
    
    /**
     * Разделителя на версиите
     */
    const VERSION_DELIMITER = '.';
    
    
    /**
     * Ключа на последната версия
     */
    const LAST_VERSION_STRING = 'lastVer';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'docClass, docId, field, value';
    
    
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
        $this->FLD('value', 'blob(1000000,compress,serialize)', 'caption=Стойности');
        
        $this->FLD('version', 'varchar', 'caption=Версия,input=none');
        $this->FLD('subVersion', 'int', 'caption=Подверсия,input=none');
        
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
            
            // Обекта, който ще записваме
            $rec = new stdClass();
            $rec->docClass = $docClassId;
            $rec->docId = $oldRec->id;
            $rec->field = $field;
            $rec->value = $oldRec->$field;
            $rec->version = $oldRec->version;
            $rec->subVersion = $oldRec->subVersion;
            
            // Ако modifiedOn
            if ($oldRec->modifiedOn) {
                
                // Използваме него
                $rec->createdOn = $oldRec->modifiedOn;
            } else {
                
                // Използваме текущия
                $rec->createdOn = dt::verbal2Mysql();
            }
            
            // Ако modifiedBy
            if ($oldRec->modifiedBy) {
                
                // Използваме него
                $rec->createdBy = $oldRec->modifiedBy;
            } else {
                
                // Използваме текущото време
                $rec->createdBy = Users::getCurrent();
            }
            
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
        
        // Инстанция на класа
        $class = cls::get($docClassId);
        
        // Опитваме се да вземем информация за документа
        
        // Последната версия на записа
        $docRec = $class->fetch($docId);
        
        // Вземаме последната версия
        
        $row = new stdClass();
        
        // Версията
        $row->Version = static::getVersionLink((object)array('docId' => $docId, 'docClass' => $docClassId), TRUE);
        
        // Ако има дата и потребтел
        if (isset($docRec->modifiedBy) && isset($docRec->modifiedOn)) {
            
            // Вземаме вербалните им стойности
            $lastVerRow = $class->recToVerbal($docRec, 'modifiedBy, modifiedOn');
            $row->createdBy = $lastVerRow->modifiedBy;
            $row->createdOn = $lastVerRow->modifiedOn;
        }
        
        // Добавяме към резултатите
        $res[] = $row;
        
        // Вземаме всички записи от класа и документи групиране по версия и подверсия, и подредени по дата
        $query = static::getQuery();
        $query->where("#docClass = '{$docClassId}'");
        $query->where("#docId = '{$docId}'");
        $query->orderBy("createdOn", 'DESC');
        $query->groupBy('version, subVersion');
        
        // Обхождаме масива
        while ($rec = $query->fetch()) {
            
            // Вербалнате стойности
            $row = static::recToVerbal($rec, 'createdBy, createdOn');
            
            // Линк към версията
            $row->Version = static::getVersionLink($rec);
            
            // Инстанция на класа
//            $row->docClass = cls::get($rec->docClass);
            
            // Добавяме в масива
            $res[] = $row;
        }
        
        return $res;
    }
    
    
    /**
     * Екшън за избиране/отказване на съответната версия
     */
    function act_logVersion()
    {
        // Изискваме да има права
        requireRole('user');
        
        // id
        $id = Request::get('id', 'int');
        
        // Името на таба
        $tab = Request::get('tab');
        
        // Съответния екшън
        $action = Request::get('action');
        
        // Ако има id
        if ($id) {
            
            // Вземаме записа
            $rec = static::fetch($id);
            
            // Очакваме да има такъв запис
            expect($rec);
            
            // id на класа
            $classId = $rec->docClass;
            
            // id на документа
            $docId = $rec->docId;
            
            // Версията
            $version = $rec->version;
            
            // Подверсията
            $subVersion = $rec->subVersion;
        } else {
            
            // id на класа
            $classId = Request::get('docClass', 'int');
            
            // id документа
            $docId = Request::get('docId', 'int');
            
            // Последна версия
            $lastVer = TRUE;
        }
        
        // Инстанция на класа
        $class = cls::get($classId);
        
        // Вземаме данните за докуемнта
        $cRec = $class->fetch($docId);

        // Очакваме да имаме права до сингъла или до треда
        expect($class->haveRightFor('single', $docId) || doc_Threads::haveRightFor('single', $cRec->threadId));
        
        // Масив с всички избрани версии за съответния документ
        $dataArr = static::getSelectedVersionsArr($classId, $docId);
        
        // Ако е последна версия
        if ($lastVer) {
            
            // Стринга на версията
            $versionStr = static::getLastVersionFromDoc($classId, $docId);
        } else {
            
            // Вземаме стринга за версията и подверсията
            $versionStr = static::getVersionStr($version, $subVersion);
        }
        
        // Ако екшъна е отказване
        if ($action == 'unselect') {
            
            // Ако има такава версия
            if ($dataArr[$versionStr]) {
                
                // Премахваме от масива
                unset($dataArr[$versionStr]);
            }
            
            // Ако остане избрана само последната версия
            if (count($dataArr) == 1) {
                
                // Последната версия
                $lastVersion = static::getLastVersionFromDoc($classId, $docId);
                
                // Ако последната версия е избрана сама
                if ($dataArr[$lastVersion]) {
                    
                    // Премахваме я от масива
                    unset($dataArr[$lastVersion]);
                }
            }
        } else {
            
            // Ако екшъна не е отказване
            
            // Добавяме в масива
            $dataArr[$versionStr] = TRUE;
        }
        
        // Обновяваме масива с версиите
        static::addSelectedVersion($classId, $docId, $dataArr);
        
        // Линка, към който ще редиректнем
        $link = array(
	                 $class, 
	                 'single', 
	                 $cRec->id,
	                 'Cid' => $cRec->containerId, 
	                 'Tab' => $tab,
	                );

        return Redirect($link);
    }
    
    
    /**
     * Връща вербалната стойност на данните за полетата
     * 
     * @param int $docClass - id на класа
     * @param int $docId - id на документа
     * @param string $versionStr - Стринга на версията и подверсията
     * @param array $fieldsArr - Масив с полетата
     * 
     * @return array $resArr - Масив с вербалните стойности на съответните полетата
     */
    static function getVerbalValue($docClass, $docId, $versionStr, $fieldsArr)
    {
        // Вземаме записа
        $recArr = static::getRec($docClass, $docId, $versionStr, $fieldsArr);

        // Ако няма запис връщаме FALSE
        if (!$recArr) return FALSE;
        
        // Инстанция на класа
        $class = cls::get($docClass);
        
        // Обхождаме записите
        foreach ((array)$recArr as $field => $rec) {
            
            // Стойност
            $value = $rec->value;
            
            // Типа на полето
            $type = $class->fields[$field]->type;
            
            // Стойността във вербален вид
            $resArr[$field] = $type->toVerbal($value);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща броя на версиите
     * 
     * @param mixed $docClassId - id на класа
     * @param string $docId - id' на документа
     * 
     * @return int - Броя на промените
     */
    static function getCountOfChange($docClassId, $docId)
    {
        // Вземаме всички записи за документа групирани по версии и подверсии
        $query = static::getQuery();
        $query->where("#docClass = '{$docClassId}'");
        $query->where("#docId = '{$docId}'");
        $query->groupBy('version, subVersion');
        
        // Връщаме броя
        return $query->count();
    }
    
    
    /**
     * Връща масив с последните подверсии на съответните версии за документа
     * 
     * @param mixed $docClass - Инстанция или id на клас
     * @param int $docId - id на документ
     * 
     * @return array $arr - 
     */
    static function getLastSubVersionsArr($docClass, $docId)
    {
        // Ако не е число
        if (!is_numeric($docClass)) {
            
            // Вземаме id' то на класа
            $docClassId = core_Classes::getId($docClass);
        } else {
            
            // Използваме id' то
            $docClassId = $docClass;
        }
        
        // Вземаме всички записи за съответния клас
        $query = static::getQuery();
        $query->where(array("#docClass = '[#1#]'", $docClassId));
        $query->where(array("#docId = '[#1#]'", $docId));
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Ако подверсията е по - голяма от записаната в масива
            if ($rec->subVersion > $arr[$rec->version]) {
                
                // Добавяме нея
                $arr[$rec->version] = $rec->subVersion;
            }
        }
        
        return $arr;
    }
    
    
    /**
     * Връща линк към с версията 
     * 
     * @param object $rec
     * @param boolean $lastVer
     * 
     * @return string
     */
    static function getVersionLink($rec, $lastVer=FALSE)
    {
        // Ако няма клас или документ, връщаме
        if (!$rec->docClass && !$rec->docId) return ;
        
        // Масив с избраните версии
        static $dataArr;
        
        // Ако не е генериран
        if (!$dataArr) {
            
            // Вземаем избраните версии
            $dataArr = static::getSelectedVersionsArr($rec->docClass, $rec->docId);
        }
        
        // Иконата за неизбрани версии
        $icon = 'img/16/checkbox_no.png';
        
        // Екшъна да сочи към избиране
        $action = 'select';
        
        // Ако линка е за последната версия
        if ($lastVer) {
            
            // Вземаме последната версия
            $versionStr = static::getLastVersionFromDoc($rec->docClass, $rec->docId);            
        } else {
            
            // Вземаме стринга за версията
            $versionStr = static::getVersionStr($rec->version, $rec->subVersion);
        }
        
        // Ако има такъв масив
        if ($dataArr) {
            
            // Ако текущата версия е избрана
            if ($dataArr[$versionStr]) {
                
                // Иконата за избрана версия
                $icon = 'img/16/checkbox_yes.png';
                
                // Екшъна да е отказване
                $action = 'unselect';
            }
        }
        
        // Ако няма избрана версия и генерираме за последната
        if (!count($dataArr) && $lastVer) {
            
            // Иконата да е избрана
            $icon = 'img/16/checkbox_yes.png';
            
            // Да няма линк
            $noLink = TRUE;
        }
        
        // Аттрибутите на класа
        $attr['class'] = 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf($icon) . ');';
        
        // Ескейпваме стринга
        $versionStrRaw = static::escape($versionStr);
            
        // Ако е зададено да няма линк
        if ($noLink) {
            
            // Празен масив
            $link = array();
            
        } else {
           
            // Задаваме линка
            $link = array('change_Log', 'logVersion', $rec->id, 'tab' => Request::get('Tab'), 'action' => $action);
            
            // Ако е за последната версия
            if ($lastVer) {
                
                // Задаваме docId и docClass
                $link['docId'] = $rec->docId;
                $link['docClass'] = $rec->docClass;
            }
        }
        
        // Връщаме линка
        $linkEt = ht::createLink($versionStrRaw, $link, NULL, $attr);
        
        // Ако е избран
        if (static::isSelected($rec->docClass, $rec->docId, $versionStr)) {
            
            // Добавяме класа
            $linkEt->append("class='change-selected-version'", 'ROW_ATTR');
        }
        
        return $linkEt;
    }
    
    
    /**
     * Връща масив с всички избрани версии
     * 
     * @param id $classId - id на класа
     * @param id $docId - id на документа
     * 
     * @return array - Масив с избраните версии
     */
    static function getSelectedVersionsArr($classId=NULL, $docId=NULL)
    {
        // Вземаме масива за версиите
        $versionArr = mode::get(static::PERMANENT_SAVE_NAME);
        
        // Ако няма клас или документ
        if (!$classId || !$docId) {
            
            // Връщаме целия масив
            return $versionArr;
        } else {
            
            // Ключа за версиите
            $versionKey = static::getVersionKey($classId, $docId);
            
            // Връщаме масива за съответния ключ
            return $versionArr[$versionKey];
        }
    }
    
    
    /**
     * Връща ключа за версиите
     * 
     * @param id $classId - id на класа
     * @param id $docId - id на документа
     */
    static function getVersionKey($classId, $docId)
    {
        
        return $classId . '_' . $docId;
    }
    
    
    /**
     * Записва в перманентните данни съответния масив
     * 
     * @param mixed $classId - Името или id на класа
     * @param string $docId - id' на документа
     * @param array $dataArr - Масива, който ще добавим
     */
    static function addSelectedVersion($classId, $docId, $dataArr)
    {
        // Вземаме всички избрани версии за документите
        $allVersionArr = static::getSelectedVersionsArr();
        
        // Ключа за версиите
        $versionKey = static::getVersionKey($classId, $docId);
        
        // Добавяме масива
        $allVersionArr[$versionKey] = $dataArr;
        
        // Записваме
        Mode::setPermanent(static::PERMANENT_SAVE_NAME, $allVersionArr);
    }
    
    
    /**
     * Събира версията и подверсията и връща един стринг
     * 
     * @param string $version - Версията
     * @param int $subVersion - Подверсията
     * 
     * @return string $versionStr
     */
    static function getVersionStr($version, $subVersion)
    {
        // Сърираме версията и подверсията
        $versionStr = $version . static::VERSION_DELIMITER . $subVersion;
        
        return $versionStr;
    }
    
    
    /**
     * Ескейпва подадения стринг
     * 
     * @param string $string - Стринга, който ще се ескейпва
     * 
     * @return string $string
     */
    static function escape($string)
    {
        // Ескейпваме стринга
        $string = core_Type::escape($string);
        $string = core_ET::escape($string);
        
        return $string;
    }
    
    
    /**
     * Разделяме стринга на версия и подверсия
     * 
     * @param string $versionStr
     * 
     * @return array - Масив с версията и подверсията
     */
    static function getVersionFromString($versionStr)
    {
        
        return explode(static::VERSION_DELIMITER, $versionStr);
    }
    
    
    /**
     * Връща най - новата и най - старата версия, която сме избрали
     * 
     * @param array $versionArr
     * @param int $docClass
     * @param int $docId
     * 
     * @return array $res - Най - новата и най - старата версия
     * $res['first'] - Първата версия
     * $res['last'] - Последната версия
     */
    static function getFirstAndLastVersion($docClass, $docId)
    {
        // Масива, който ще връщаме
        static $res = array();
        
        // Ако е генериран преди
        if ($res) return $res;
        
        // Всички избрани версии
        $versionArr = (array)static::getSelectedVersionsArr($docClass, $docId);
        
        // Броя на избраните версии
        $cntVers = count($versionArr);
        
        // Ако няма избрана версии, връщаме
        if (!$cntVers) return $res;
        
        // Ако има избрана една версия
        if ($cntVers == 1) {
            
            // Добавяме в масива
            $res['first'] = key($versionArr);
        } else {
            
            // Ако са избрани повече версии
            
            // Стринг за последната версия
            $lastVer = static::getLastVersionFromDoc($docClass, $docId);
            
            // Ако е избрана последна версия
            if ($versionArr[$lastVer]) {
                
                // Отбелязваме, че е избрана
                $haveLast = TRUE;
                
                // Добавяме в масива
                $res['last'] = $lastVer;
            }
            
            // Обхождамва масива
            foreach ($versionArr as $keyVer => $dummy) {
                
                // Ако е последна версия, прескачаме
                if ($keyVer == $lastVer) continue;
                
                // Вземаме записа
                $recArr = static::getRec($docClass, $docId, $keyVer);
                
                // Ако няма записи
                if ($recArr === FALSE) continue;
                
                // Вземаме първия запис от масива
                $rec = $recArr[0];
                
                // Ако няма избран първа версия или е по старата
                if (!$firstTime || ($firstTime > $rec->createdOn)) {
                    
                    // Време на първата версия
                    $firstTime = $rec->createdOn;
                    
                    // Добавяме първата версия
                    $res['first'] = $keyVer;
                }
                
                // Ако няма последна версия и няма избрана последна версили или е по нова
                if ((!$haveLast) && (!$lastTime || ($lastTime < $rec->createdOn))) {
                    
                    // Време на последната версия
                    $lastTime = $rec->createdOn;
                    
                    // Добавяме последната версия
                    $res['last'] = $keyVer;
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща последната версия на документа, който е записан в модела на класа
     * 
     * @param mixed $class - id или инстанция на класа
     * @param int $docId - id на докуемнта
     * 
     * @return mixed - Стринга на версията
     */
    static function getLastVersionFromDoc($class, $docId)
    {
        try {
            
            // Инстанция на класа
            $class = cls::get($class);
            
            // Вземаме записа
            $rec = $class->fetch($docId);
            
            // Ако има версия и подверсия
            if (isset($rec->version) && isset($rec->subVersion)) {
                
                // Връщаме стринга на версията и подверсията
                return static::getVersionStr($rec->version, $rec->subVersion);
            }
        } catch (Exception $e) { }
        
        return static::LAST_VERSION_STRING;
    }
    
    
    /**
     * Проверява дали версията е между избраниете
     * 
     * @param int $docClass - id на клас
     * @param int $docId - id на документ
     * @param string $versionStr - Версия
     * 
     * @return boolean
     */
    static function isSelected($docClass, $docId, $versionStr)
    {
        // Вземаме версиите между избраните
        $versionsBetweenArr = static::getSelectedVersionsBetween($docClass, $docId);
        
        // Ако е в избраните, връщаме TRUE
        if ($versionsBetweenArr[$versionStr]) return TRUE;
    }
    
    
    /**
     * Връща масив между избраните версии
     * 
     * @param int $docClass - id на клас
     * @param int $docId - id на документ
     * 
     * @return array - Масив с версиите между избраните
     */
    static function getSelectedVersionsBetween($docClass, $docId)
    {
        // Масива, който ще връщаме
        static $arr = array();
        
        // Ако е генерирано преди, връщаме
        if ($arr) return $arr;
        
        // Вземаме първата и последна версия
        $firstAndLastVerArr = static::getFirstAndLastVersion($docClass, $docId);
        
        // Ако има избрана първа версия
        if ($firstAndLastVerArr['first']) {
            
            // Вземаме масива със записа
            $firstRecArr = static::getRec($docClass, $docId, $firstAndLastVerArr['first']);
            
            // Вземаме първия запис
            $firstRec = $firstRecArr[0];
            
            // Добавяме в масива, който ще връщаме
            $arr[$firstAndLastVerArr['first']] = $firstAndLastVerArr['first'];
        }
        
        // Ако име последна версия
        if ($firstAndLastVerArr['last']) {
            
            // Вземаме масива със записа
            $lastRecArr = static::getRec($docClass, $docId, $firstAndLastVerArr['last']);
            
            // Вземаме първия запис
            $lastRec = $lastRecArr[0];
            
            // Добавяме в масива, който ще връщаме
            $arr[$firstAndLastVerArr['last']] = $firstAndLastVerArr['last'];
        }
        
        // Ако има избрана версия
        if (($lastRecArr !== NULL) && $firstRec && ($firstCreatedOn = $firstRec->createdOn)) {
            
            // Запитване за да вземем записите
            $query = static::getQuery();
            $query->where(array("#docClass = '[#1#]'", $docClass));
            $query->where(array("#docId = '[#1#]'", $docId));
            $query->groupBy('version, subVersion');
            
            // Ако няма последна версия
            if (!$lastRecArr) {
                
                // Ако не може да се вземе последния запис
                if ($lastRecArr === FALSE) {
                    
                    // Добавяме време
                    $query->where(array("#createdOn >= '[#1#]'", $firstCreatedOn));
                }
            } else {
                
                // Ако има избрана последна версия
                
                // Вземаме времето
                if (($lastCreatedOn = $lastRec->createdOn)) {
                    
                    // Добавяме условието
                    $query->where(array("#createdOn <= '[#1#]'", $lastCreatedOn));
                    $query->where(array("#createdOn >= '[#1#]'", $firstCreatedOn));
                }
            }
            
            // Обхождаме резултата
            while ($rec = $query->fetch()) {
                
                // Вземаме стринга за версиите
                $versionStr = static::getVersionStr($rec->version, $rec->subVersion);
                
                // Добавяме в масива
                $arr[$versionStr] = $versionStr;
            }
        }
        
        return $arr;
    }
    
    
    /**
     * Връща един запис със съответните данни
     * 
     * @param int $docClass - id на класа
     * @param int $docId - id на документа
     * @param string $versionStr - Версията и подверсията
     * @param mixed $field - Името на полето или масив с полетата
     * 
     * return array $recArr - Масив с откритите записи
     */
    static function getRec($docClass, $docId, $versionStr, $field=FALSE)
    {
        // Вземаме версията и подверсията от стринга
        $versionArr = static::getVersionFromString($versionStr);
        
        // Добаваме условията
        $query = static::getQuery();
        $query->where(array("#version = '[#1#]'", $versionArr[0]));
        $query->where(array("#subVersion = '[#1#]'", $versionArr[1]));
        $query->where(array("#docClass = '[#1#]'", $docClass));
        $query->where(array("#docId = '[#1#]'", $docId));
        
        // Ако има подадено поле
        if ($field !== FALSE) {
            
            // Ако е масив
            if (is_array($field)) {
                
                // Добавяме условие за полетата
                $query->orWhereArr('field', $field);
            } else {
                
                // Добавяме условие за полето
                $query->where(array("#field = '[#1#]'", $field));
            }
        }
        
        // Вземаме един запис
        while ($rec = $query->fetch()) {
            
            // Ако няма подададено поле
            if ($field === FALSE) {
                
                // Добавямв към масива
                $recArr[] = $rec;
            } else {
                
                // Добавяме към масива с ключ името на полето
                $recArr[$rec->field] = $rec;
            }
        }
        
        // Ако няма запис връщаме FALSE
        if (!count($recArr)) return FALSE;
        
        return $recArr;
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     * 
     * Само за оправяне на старите полета, където всяка промяна се записваше в отделен ред
     * 
     * Нужно е да се стартира само веднъж. Не би трябвало да сработи при другите стартирания.
     * 
     * @todo Да се премахне
     * След премахването value може да се преименува на values
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Дали е зададено да се сераилизира
        $serailize = $mvc->fields['value']->type->params['serialize'];
        
        // Премахваме
        unset($mvc->fields['value']->type->params['serialize']);
        
        // Вземаме записите
        $query = static::getQuery();
        $query->orderBy('createdOn', 'ASC');
        
        // Обхождаме записите
        while ($rec = $query->fetch()) {
            
            // Ако няма клас и id прескачаме
            if (!$rec->docClass && !$rec->docId) continue;
            
            // Ако няма версия
            if (!$rec->version) {
                
                // Да е 0
                $rec->version = 0;
            }
            
            // Ако няма подверсия
            if (!$rec->subVersion) {
                
                // Да е 1
                $rec->subVersion = 1;
            }
            
            // Генерирам ключ
            $dKey = $rec->docClass . "|" . $rec->docId . "|" . $rec->field;
            
            // Ако е зададено да се сериализира и има стойност
            if ($serailize && $rec->value) {
                
                // Опитваме се да десериализираме
                $rValue = unserialize($rec->value);
                
                // Ако не е FALSE
                if ($rValue !== FALSE) {
                    
                    // Използваме сериализираната стойност
                    $rec->value = $rValue;
                }
            }
            
            // Ако е масив
            if (is_array($rec->value)) {
                
                // Вземаме стойността
                $valueCurr = $rec->value;
            } else {
                
                // Създавамем масива
                $valueCurr = array('version' => $rec->version, 'subVersion' => $rec->subVersion, 'value' => $rec->value);
                
                // Вдигаме флага
                $haveForSave = TRUE;
            }
            
            // Ако ключа го има в масива
            if ($arrKeys[$dKey]) {
                
                // Вдигаме флага
                $haveForSave = TRUE;
                
                // Стойността
                $value = $arrKeys[$dKey];
                
                // Ако масива не е обработен преди
                if (!$valueCurr[0]) {
                    
                    // Добавяме в масива
                    $arrKeys[$dKey][] =  $valueCurr;
                }
            } else {
                
                // Добавяме в масива
                $arrKeys[$dKey] = array($valueCurr);
            }
        }
        
        // Ако е зададено да се сериализира
        if ($serailize) {
            
            // Добавяме в класа
            $mvc->fields['value']->type->params['serialize'] = $serailize;
        }
        
        // Ако има нещо за записване
        if ($haveForSave) {
            
            // Обхождаме масива
            foreach ($arrKeys as $key => $val) {
                
                // Вземаме необходимите данни от ключа
                list($docClass, $docId, $field) = explode('|', $key);
                
                // Записа, който ще запишем
                $ssRec = new stdClass();
                $ssRec->docClass = $docClass;
                $ssRec->docId = $docId;
                $ssRec->field = $field;
                $ssRec->value = $val;
                
                // Броя на изтритите
                $cntDel +=$mvc->delete(array("#docClass = '[#1#]' AND #docId = '[#2#]' AND #field = '[#3#]'", $docClass, $docId, $field));
                
                // Ако се създаде
                if ($mvc->save($ssRec)) {
                    
                    // Увеличаваме броя на заетите
                    $cntSave++;
                }
            }
            
            // Ако има създадени
            if ($cntSave) {
                $res .= "<li>Добавени {$cntSave} записа";
            }
            
            // Ако има изтрити
            if ($cntDel) {
                $res .= "<li>Изтрити {$cntDel} записа";
            }
        }
    }
}
