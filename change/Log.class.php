<?php 

/**
 * Клас 'change_Log - Логове
 *
 * @category  vendors
 * @package   change
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'docClass, docId, field, value';
    
    
    /**
     * Заглавие
     */
    public $title = 'Логове';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'admin';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('docClass', 'class', 'caption=Документ->Клас');
        $this->FLD('docId', 'int', 'caption=Документ->Обект');
        $this->FLD('field', 'varchar', 'caption=Поле');
        $this->FLD('value', 'blob(1000000,compress,serialize)', 'caption=Стойности');
        
        $this->FNC('createdOn', 'datetime(format=smartTime)', 'caption=Създаване->На, input=none');
        $this->FNC('createdBy', 'key(mvc=core_Users)', 'caption=Създаване->От, input=none');

        $this->setDbIndex('docClass,docId');
    }
    
    
    /**
     * Създава запис в лога
     *
     * @param mixed  $docClass  - Името или id на класа
     * @param array  $fieldsArr - Масив с полетата, които ще се запишат
     * @param object $oldRec    - Стара стойност
     * @param object $newRec    - Нова стойност
     */
    public static function create($docClass, $fieldsArr, $oldRec, $newRec)
    {
        // Резултатния масив, който ще връщаме
        $recsArr = array();
        
        // Ако е id на клас
        if (is_numeric($docClass)) {
            
            // Използваме id' то
            $docClassId = $docClass;
        } else {
            
            // Вземаме id' то на класа
            $docClassId = core_Classes::getId($docClass);
        }
        
        // Обхождаме масива с полетата
        foreach ((array) $fieldsArr as $field) {
            
            // Ако changeModifiedOn
            if ($oldRec->changeModifiedOn) {
                
                // Използваме него
                $createdOn = $oldRec->changeModifiedOn;
            } elseif ($oldRec->modifiedOn) {
                
                // Използваме него
                $createdOn = $oldRec->modifiedOn;
            } else {
                
                // Използваме текущия
                $createdOn = dt::verbal2Mysql();
            }
            
            // Ако changeModifiedBy
            if ($oldRec->changeModifiedBy) {
                
                // Използваме него
                $createdBy = $oldRec->changeModifiedBy;
            } elseif ($oldRec->modifiedBy) {
                
                // Използваме него
                $createdBy = $oldRec->modifiedBy;
            } else {
                
                // Използваме текущото време
                $createdBy = Users::getCurrent();
            }
            
            // Ако няма версия
            if (!$oldRec->version) {
                
                // Да е нула по подразбиране
                $oldRec->version = 0;
            }
            
            // Ако няма подверсия
            if (!$oldRec->subVersion) {
                
                // Да е едно по поразбиране
                $oldRec->subVersion = 1;
            }
            
            // Обекта за value, който ще запишем
            $valueObj = (object) array('version' => $oldRec->version, 'subVersion' => $oldRec->subVersion, 'value' => $oldRec->$field, 'createdOn' => $createdOn, 'createdBy' => $createdBy);
            
            // Обекта, който ще записваме
            $rec = new stdClass();
            
            // Вземаме записа
            $sRec = static::getRec($docClassId, $oldRec->id, $field);
            
            // Ако има запис за съответното поле
            if ($sRec) {
                
                // Добавяме стойностите
                $rec->id = $sRec->id;
                $rec->docClass = $sRec->docClass;
                $rec->docId = $sRec->docId;
                $rec->field = $field;
                $rec->value = $sRec->value;
                $rec->value[] = $valueObj;
            } else {
                $rec->docClass = $docClassId;
                $rec->docId = $oldRec->id;
                $rec->field = $field;
                $rec->value = array($valueObj);
            }
            
            // Записваме
            static::save($rec);
            
            // Добавяме в масива
            $recsArr[] = $rec;
        }
        
        $versionArr = change_Log::getFirstAndLastVersion($docClassId, $oldRec->id);
        
        if ($versionArr['first']) {
            change_Log::addVersion($docClassId, $rec->docId, $rec->id);
        }
        
        return $recsArr;
    }
    
    
    /**
     * Подготвяме записите за лога във вербален вид
     *
     * @param mixed  $docClass - Името или id на класа
     * @param string $docId    - id' на документа
     *
     * @return array $res - Масив с данни
     */
    public static function prepareLogRow($docClass, $docId)
    {
        // Ако е id на клас
        if (is_numeric($docClass)) {
            
            // Използваме id' то
            $docClassId = $docClass;
        } else {
            
            // Вземаме id' то на класа
            $docClassId = core_Classes::getId($docClass);
        }
        
        // Масив с данните
        $res = array();
        
        // Инстанция на класа
        $class = cls::get($docClassId);
        
        // Вземаме записа
        $rec = static::getRec($docClassId, $docId);
        
        // Ако е масив
        if (is_array($rec->value)) {
            
            // Обхождаме масива
            foreach ((array) $rec->value as $key => $value) {
                
                // Данните, които ще се визуализрат
                $row = (object) array(
                    'createdOn' => $value->createdOn,
                    'createdBy' => $value->createdBy,
                );
                
                // Записите във вербален вид
                $row = static::recToVerbal($row, array_merge(array_keys(get_object_vars($row)), array('-single')));
                
                // Стринга на версията
                $versionStr = static::getVersionStr($value->version, $value->subVersion);
                
                // Идентификатор на версията
                $versionId = static::getVersionId($docClassId, $docId, $key);
                
                // Линк към версията
                $row->Version = static::getVersionLink($rec, $versionStr, $versionId);
                
                // Добавяме в масива
                $res[] = $row;
            }
        }
        
        // Опитваме се да вземем информация за документа
        // Вземаме последната версия
        $row = new stdClass();
        
        // Версията
        $row->Version = static::getVersionLink((object) array('docId' => $docId, 'docClass' => $docClassId), false, false, true);
        
        // Последната версия на записа
        $docRec = $class->fetch($docId);
        
        // Ако има дата и потребител
        if (isset($docRec->changeModifiedBy, $docRec->changeModifiedOn)) {
            
            // Вземаме вербалните им стойности
            $lastVerRow = $class->recToVerbal($docRec, 'changeModifiedBy, changeModifiedOn, -single');
            $row->createdBy = $lastVerRow->changeModifiedBy;
            $row->createdOn = $lastVerRow->changeModifiedOn;
        } elseif (isset($docRec->modifiedBy, $docRec->modifiedOn)) {
            
            // Вземаме вербалните им стойности
            $lastVerRow = $class->recToVerbal($docRec, 'modifiedBy, modifiedOn, -single');
            $row->createdBy = $lastVerRow->modifiedBy;
            $row->createdOn = $lastVerRow->modifiedOn;
        }
        
        // Добавяме към резултатите
        $res[] = $row;
        
        // Подреждаме в обратен ред
        $res = array_reverse($res);
        
        return $res;
    }
    
    
    /**
     * Екшън за избиране/отказване на съответната версия
     */
    public function act_logVersion()
    {
        // Изискваме да има права
        requireRole('user');
        
        // id на класа
        $classId = Request::get('docClass', 'int');
        
        // id документа
        $docId = Request::get('docId', 'int');
        
        // Името на таба
        $tab = Request::get('tab');
        
        // Съответния екшън
        $action = Request::get('action');
        
        // Версията
        $versionId = Request::get('versionId', 'varchar');
        
        // Инстанция на класа
        $class = cls::get($classId);
        
        // Вземаме данните за докуемнта
        $cRec = $class->fetch($docId);
        
        // Очакваме да имаме права до сингъла или до треда
        expect($class->haveRightFor('single', $docId) || doc_Threads::haveRightFor('single', $cRec->threadId));
        
        // Масив с всички избрани версии за съответния документ
        $dataArr = static::getSelectedVersionsArr($classId, $docId);
        
        // Ако екшъна е отказване
        if ($action == 'unselect') {
            
            // Ако има такава версия
            if ($dataArr[$versionId]) {
                
                // Премахваме от масива
                unset($dataArr[$versionId]);
            }
        } else {
            
            // Ако екшъна не е отказване
            
            // Добавяме в масива
            $dataArr[$versionId] = true;
        }
        
        // Обновяваме масива с версиите
        static::updateSelectedVersion($classId, $docId, $dataArr);
        
        // Линка, към който ще редиректнем
        $link = array(
            $class,
            'single',
            $cRec->id,
            'Cid' => $cRec->containerId,
            'Tab' => $tab,
        );
        
        return new Redirect($link);
    }
    
    
    /**
     * Връща вербалната стойност на данните за полетата
     *
     * @param int    $docClass   - id на класа
     * @param int    $docId      - id на документа
     * @param string $versionStr - Стринга на версията и подверсията
     * @param array  $fieldsArr  - Масив с полетата
     *
     * @return array $resArr - Масив с вербалните стойности на съответните полетата
     */
    public static function getVerbalValue($docClass, $docId, $versionStr, $fieldsArr)
    {
        // Вземаме записа
        $recArr = static::getRecForVersion($docClass, $docId, $versionStr, $fieldsArr);
        
        // Ако няма запис връщаме FALSE
        if (!$recArr) {
            
            return false;
        }
        
        // Инстанция на класа
        $class = cls::get($docClass);
        
        // Обхождаме записите
        foreach ((array) $recArr as $field => $rec) {
            
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
     * @param mixed  $docClassId - id на класа
     * @param string $docId      - id' на документа
     *
     * @return int - Броя на промените
     */
    public static function getCountOfChange($docClassId, $docId)
    {
        // Вземаме записа
        $rec = static::getRec($docClassId, $docId);
        
        // Ако има стойност
        if ($rec->value) {
            
            // Връщаме броя
            return count($rec->value);
        }
    }
    
    
    /**
     * Връща масив с последните подверсии на съответните версии за документа
     *
     * @param mixed $docClass - Инстанция или id на клас
     * @param int   $docId    - id на документ
     *
     * @return array $arr -
     */
    public static function getLastSubVersionsArr($docClass, $docId)
    {
        // Ако не е число
        if (!is_numeric($docClass)) {
            
            // Вземаме id' то на класа
            $docClassId = core_Classes::getId($docClass);
        } else {
            
            // Използваме id' то
            $docClassId = $docClass;
        }
        
        // Вземаме записа
        $rec = static::getRec($docClassId, $docId);
        
        // Обхождаме резултатите
        foreach ((array) $rec->value as $value) {
            
            // Ако подверсията е по - голяма от записаната в масива
            if ($value->subVersion > $arr[$value->version]) {
                
                // Добавяме нея
                $arr[$value->version] = $value->subVersion;
            }
        }
        
        return $arr;
    }
    
    
    /**
     * Връща линк към с версията
     *
     * @param object $rec
     * @param string $versionStr
     * @param string $versionId
     * @param bool   $lastVer
     *
     * @return string
     */
    public static function getVersionLink($rec, $versionStr = false, $versionId = false, $lastVer = false)
    {
        // Ако няма клас или документ, връщаме
        if (!$rec->docClass && !$rec->docId) {
            
            return ;
        }
        
        // Масив с избраните версии
        static $allDataArr = array();
        
        $str = $rec->docClass . '_' . $rec->docId;
        
        $dataArr = $allDataArr[$str];
        
        // Ако не е генериран
        if (!$dataArr) {
            
            // Вземаем избраните версии
            $dataArr = $allDataArr[$str] = static::getSelectedVersionsArr($rec->docClass, $rec->docId);
        }
        
        // Иконата за неизбрани версии
        $icon = 'img/16/checkbox_no.png';
        
        // Екшъна да сочи към избиране
        $action = 'select';
        
        // Ако линка е за последната версия
        if ($lastVer) {
            
            // Вземаме последната версия
            $versionStr = static::getLastVersionFromDoc($rec->docClass, $rec->docId);
            
            // Идентификатора за избраната версия
            $versionId = static::getLastVersionIdFromDoc($rec->docClass, $rec->docId);
        }
        
        // Ако има такъв масив
        if ($dataArr) {
            
            // Ако текущата версия е избрана
            if ($dataArr[$versionId]) {
                
                // Иконата за избрана версия
                $icon = 'img/16/checkbox_yes.png';
                
                // Екшъна да е отказване
                $action = 'unselect';
            }
        }
        
        // Ако няма избрана версия и генерираме за последната
        if (!count($dataArr) && $lastVer) {
            
            // Флаг, да маркираме последната
            $markLast = true;
        }
        
        // Ескейпваме стринга
        $versionStrRaw = static::escape($versionStr);
        
        // Задаваме линка
        $link = array('change_Log', 'logVersion', 'docClass' => $rec->docClass, 'docId' => $rec->docId, 'versionStr' => $versionStr, 'versionId' => $versionId, 'tab' => Request::get('Tab'), 'action' => $action);
        
        // Връщаме линка
        $linkEt = ht::createLink($versionStrRaw, $link, null, "ef_icon={$icon}");
        
        // Ако е избран или е вдигнат флага
        if ($markLast || static::isSelected($rec->docClass, $rec->docId, $versionId)) {
            
            // Добавяме класа
            $linkEt->append("class='change-selected-version'", 'ROW_ATTR');
        }
        
        return $linkEt;
    }
    
    
    /**
     * Връща масив с всички избрани версии
     *
     * @param id $classId - id на класа
     * @param id $docId   - id на документа
     *
     * @return array - Масив с избраните версии
     */
    public static function getSelectedVersionsArr($classId = null, $docId = null)
    {
        // Вземаме масива за версиите
        $versionArr = mode::get(static::PERMANENT_SAVE_NAME);
        
        // Ако няма клас или документ
        if (!$classId || !$docId) {
            
            // Връщаме целия масив
            return $versionArr;
        }
        
        // Ключа за версиите
        $versionKey = static::getVersionKey($classId, $docId);
        
        // Връщаме масива за съответния ключ
        return $versionArr[$versionKey];
    }
    
    
    /**
     * Връща ключа за версиите
     *
     * @param id $classId - id на класа
     * @param id $docId   - id на документа
     */
    public static function getVersionKey($classId, $docId)
    {
        return $classId . '_' . $docId;
    }
    
    
    /**
     * Записва в перманентните данни съответния масив
     *
     * @param mixed  $classId - Името или id на класа
     * @param string $docId   - id' на документа
     * @param array  $dataArr - Масива, който ще добавим
     */
    public static function updateSelectedVersion($classId, $docId, $dataArr)
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
     * Добавя подадената версия в избраните
     *
     * @param mixed      $classId    - Името или id на класа
     * @param string     $docId      - id' на документа
     * @param string     $version    - Версията
     * @param subVersion $subVersion - Подверсията
     */
    public static function addVersion($classId, $docId, $recId)
    {
        // Вземаме масива с избраните версии
        $dataArr = static::getSelectedVersionsArr($classId, $docId);
        
        // Идентификатор на версията
        $versionId = static::getLastVersionIdFromDoc($classId, $docId);
        
        // Добавяме в масива
        $dataArr[$versionId] = true;
        
        // Обновяваме масива с версиите
        static::updateSelectedVersion($classId, $docId, $dataArr);
    }
    
    
    /**
     * Събира версията и подверсията и връща един стринг
     *
     * @param string $version    - Версията
     * @param int    $subVersion - Подверсията
     *
     * @return string $versionStr
     */
    public static function getVersionStr($version, $subVersion)
    {
        if (is_null($version)) {
            $version = 0;
        }
        
        if (is_null($subVersion)) {
            $subVersion = 1;
        }
        
        // Събираме версията и подверсията
        $versionStr = $version . static::VERSION_DELIMITER . $subVersion;
        
        return $versionStr;
    }
    
    
    /**
     * Събира версията и подверсията и връща един стринг
     *
     * @param mixed $docClass - Името или id на класа
     * @param int   $docId    - id' на документа
     * @param int   $id       - id на версията
     *
     * @return string
     */
    public static function getVersionId($docClass, $docId, $id)
    {
        // Ако е id на клас
        if (is_numeric($docClass)) {
            
            // Използваме id' то
            $docClassId = $docClass;
        } else {
            
            // Вземаме id' то на класа
            $docClassId = core_Classes::getId($docClass);
        }
        
        // Събираме id-то на записа и ключа на версията
        $versionId = $docClassId . static::VERSION_DELIMITER . $docId . static::VERSION_DELIMITER . $id;
        
        return $versionId;
    }
    
    
    /**
     * Ескейпва подадения стринг
     *
     * @param string $string - Стринга, който ще се ескейпва
     *
     * @return string $string
     */
    public static function escape($string)
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
    public static function getVersionFromString($versionStr)
    {
        return explode(static::VERSION_DELIMITER, $versionStr);
    }
    
    
    /**
     * Връща масив с вербалната стойност на версията от ключа на версията и кога и от койт потребител е създаден
     *
     * @param core_Mvc $mvc
     * @param string   $key
     * @param bool     $key
     *
     * @return array
     */
    public static function getVersionAndDateFromKey($mvc, $key, $escape = true)
    {
        // Масив с данните за версията от ключа
        $versionArr = static::getVersionFromString($key);
        
        // Ако има всички необходими данни
        if (isset($versionArr[0], $versionArr[1], $versionArr[2])) {
            
            // Масив с всички промени
            $recsArr = static::getRec($versionArr[0], $versionArr[1], '*');
            
            // Обхождаме масива
            foreach ((array) $recsArr as $rec) {
                
                // Ако има добавена стойност
                if ($val = $rec->value[$versionArr[2]]) {
                    
                    // Извличаме версията и подверсията
                    $resArr['versionStr'] = static::getVersionStr($val->version, $val->subVersion);
                    $resArr['createdOn'] = $val->createdOn;
                    $resArr['createdBy'] = $val->createdBy;
                    
                    break;
                }
            }
        } else {
            
            // Ако може да се извлече запис от модела
            if ($mvc && is_numeric($versionArr[0])) {
                
                // Вземаме записа
                $rec = $mvc->fetch($versionArr[0]);
                
                // Определяме версията от записа в модела
                $resArr['versionStr'] = static::getVersionStr($rec->version, $rec->subVersion);
                $resArr['createdOn'] = $rec->changeModifiedOn ? $rec->changeModifiedOn : $rec->modifiedOn;
                $resArr['createdBy'] = $rec->changeModifiedBy ? $rec->changeModifiedBy : $rec->modifiedBy;
            }
        }
        
        // Ако е зададено да се ескейпва
        if ($escape) {
            
            // Ескейпваме
            $resArr['versionStr'] = static::escape($resArr['versionStr']);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща вербалната стойност на версията от ключа на версията
     *
     * @param core_Mvc $mvc
     * @param string   $key
     * @param bool     $key
     *
     * @return string
     */
    public static function getVersionStrFromKey($mvc, $key, $escape = true)
    {
        $versionStrArr = static::getVersionAndDateFromKey($mvc, $key, $escape = true);
        
        return $versionStrArr['versionStr'];
    }
    
    
    /**
     * Връща най - новата и най - старата версия, която сме избрали
     *
     * @param array $versionArr
     * @param int   $docClass
     * @param int   $docId
     *
     * @return array $res - Най - новата и най - старата версия
     *               $res['first'] - Първата версия
     *               $res['last'] - Последната версия
     */
    public static function getFirstAndLastVersion($docClass, $docId)
    {
        // Масива, който ще връщаме
        static $allRes = array();
        
        $str = $docClass . '_' . $docId;
        
        $res = (array) $allRes[$str];
        
        // Ако е генериран преди
        if ($res) {
            
            return $res;
        }
        
        // Всички избрани версии
        $versionArr = (array) static::getSelectedVersionsArr($docClass, $docId);
        
        // Броя на избраните версии
        $cntVers = count($versionArr);
        
        // Ако няма избрана версии, връщаме
        if (!$cntVers) {
            
            return $res;
        }
        
        // Ако има избрана една версия
        if ($cntVers == 1) {
            
            // Добавяме в масива
            $res['first'] = key($versionArr);
        } else {
            
            // Ако са избрани повече версии
            
            // Стринг за последната версия
            $lastVer = static::getLastVersionIdFromDoc($docClass, $docId);
            
            // Ако е избрана последна версия
            if ($versionArr[$lastVer]) {
                
                // Отбелязваме, че е избрана
                $haveLast = true;
                
                // Добавяме в масива
                $res['last'] = $lastVer;
            }
            
            $firstTime = null;
            $lastTime = null;
            
            // Обхождамва масива
            foreach ($versionArr as $keyVer => $dummy) {
                
                // Ако е последна версия, прескачаме
                if ((string) $keyVer === (string) $lastVer) {
                    continue;
                }
                
                // Вземаме записа
                $recArr = static::getRecForVersion($docClass, $docId, $keyVer);
                
                // Ако няма записи
                if ($recArr === false) {
                    continue;
                }
                
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
        
        $allRes[$str] = $res;
        
        return $res;
    }
    
    
    /**
     * Връща последната версия на документа, който е записан в модела на класа
     *
     * @param mixed $class - id или инстанция на класа
     * @param int   $docId - id на докуемнта
     *
     * @return mixed - Стринга на версията
     */
    public static function getLastVersionFromDoc($class, $docId)
    {
        try {
            
            // Инстанция на класа
            $class = cls::get($class);
            
            // Вземаме записа
            $rec = $class->fetch($docId);
            
            // Ако има версия и подверсия
            if (isset($rec->version, $rec->subVersion)) {
                
                // Връщаме стринга на версията и подверсията
                return static::getVersionStr($rec->version, $rec->subVersion);
            }
        } catch (core_exception_Expect $e) {
        }
        
        return static::LAST_VERSION_STRING;
    }
    
    
    /**
     *
     *
     * @param mixed $class - id или инстанция на класа
     * @param int   $docId - id на докуемнта
     *
     * @return mixed - Стринга на версията
     */
    public static function getLastVersionIdFromDoc($class, $docId)
    {
        return $docId;
    }
    
    
    /**
     * Проверява дали версията е между избраниете
     *
     * @param int    $docClass   - id на клас
     * @param int    $docId      - id на документ
     * @param string $versionStr - Версия
     *
     * @return bool
     */
    public static function isSelected($docClass, $docId, $versionId)
    {
        // Вземаме версиите между избраните
        $versionsBetweenArr = static::getSelectedVersionsBetween($docClass, $docId);
        
        // Ако е в избраните, връщаме TRUE
        if ($versionsBetweenArr[$versionId]) {
            
            return true;
        }
    }
    
    
    /**
     * Връща масив между избраните версии
     *
     * @param int $docClass - id на клас
     * @param int $docId    - id на документ
     *
     * @return array - Масив с версиите между избраните
     */
    public static function getSelectedVersionsBetween($docClass, $docId)
    {
        // Масива, който ще връщаме
        static $allVersionsArr = array();
        
        $str = $docClass . '_' . $docId;
        
        $arr = (array) $allVersionsArr[$str];
        
        // Ако е генерирано преди, връщаме
        if ($arr) {
            
            return $arr;
        }
        
        // Вземаме първата и последна версия
        $firstAndLastVerArr = static::getFirstAndLastVersion($docClass, $docId);
        
        // Ако има избрана първа версия
        if ($firstAndLastVerArr['first']) {
            
            // Вземаме масива със записа
            $firstRecArr = static::getRecForVersion($docClass, $docId, $firstAndLastVerArr['first']);
            
            // Вземаме първия запис
            $firstRec = $firstRecArr[0];
            
            // Добавяме в масива, който ще връщаме
            $arr[$firstAndLastVerArr['first']] = $firstAndLastVerArr['first'];
        }
        
        // Стойността по подразбиране
        $lastRecArr = false;
        
        // Ако име последна версия
        if ($firstAndLastVerArr['last']) {
            
            // Вземаме масива със записа
            $lastRecArr = static::getRecForVersion($docClass, $docId, $firstAndLastVerArr['last']);
            
            // Вземаме първия запис
            $lastRec = $lastRecArr[0];
            
            // Добавяме в масива, който ще връщаме
            $arr[$firstAndLastVerArr['last']] = $firstAndLastVerArr['last'];
        }
        
        // Ако има избрана версия и има израбрана последна версия
        if (($lastRecArr !== false) && $firstRec && ($firstCreatedOn = $firstRec->createdOn)) {
            
            // Вземаме записа
            $rec = static::getRec($docClass, $docId);
            
            // Обхождаме стойностите
            foreach ((array) $rec->value as $key => $value) {
                
                // Флаг, дали да се запише версията
                $getVersion = false;
                
                // Ако е избрана последната версия
                if ($lastRecArr === null) {
                    
                    // Ако е създадена след първия избран
                    if ($value->createdOn >= $firstCreatedOn) {
                        
                        // Вдигаме флага
                        $getVersion = true;
                    }
                } else {
                    
                    // Ако има дата на последно избраната версия
                    if ($lastCreatedOn = $lastRec->createdOn) {
                        
                        // Вземаме между първата и последната
                        if (($value->createdOn <= $lastCreatedOn) && ($value->createdOn >= $firstCreatedOn)) {
                            
                            // Вдигаме флага
                            $getVersion = true;
                        }
                    }
                }
                
                // Ако флага е вдигнат
                if ($getVersion) {
                    
                    // Вземаме версията
                    $versionId = static::getVersionId($docClass, $docId, $key);
                    
                    // Добавяме в масива
                    $arr[$versionId] = $versionId;
                }
            }
        }
        
        $allVersionsArr[$str] = $arr;
        
        return $arr;
    }
    
    
    /**
     * Връща за дадено поле
     *
     * @param int    $docClass
     * @param intege $docId
     * @param mixed  $field
     */
    public static function getRec($docClass, $docId, $field = false)
    {
        // Масива със записите
        static $allRecsArr = array();
        
        $str = $docClass . '_' . $docId;
        
        $recsArr = $allRecsArr[$str];
        
        // Ако не е сетнат
        if ($recsArr !== false) {
            
            // Вземаме всички записи за съответния клас и документ
            $query = static::getQuery();
            $query->where(array("#docClass = '[#1#]'", $docClass));
            $query->where(array("#docId = '[#1#]'", $docId));
            
            // Обхождаме резултата
            while ($rec = $query->fetch()) {
                
                // Добавяме в масива
                $recsArr[$rec->field] = $rec;
            }
            
            $allRecsArr[$str] = $recsArr;
            
            // Ако няма резултат връщаме FALSE
            if (!$recsArr) {
                $allRecsArr[$str] = false;
            }
        }
        
        if ($allRecsArr[$str] === false) {
            
            return false;
        }
        
        // Ако е зададено съответно поле
        if ($field) {
            
            // Ако са зададени всички полета
            if ($field == '*') {
                
                // Връщаме всички
                $resRecArr = $recsArr;
            } elseif (is_array($field)) {
                
                // Ако полетата са в масив
                
                // Обхождаме полетата
                foreach ($field as $f) {
                    
                    // Добавяме в резултата
                    $resRecArr[$f] = $recsArr[$f];
                }
            } else {
                
                // Ако е стринг, връщаме съответното поле
                $resRecArr = $recsArr[$field];
            }
        } else {
            
            // Вземаме първия запис
            $resRecArr = $recsArr[key($recsArr)];
        }
        
        return $resRecArr;
    }
    
    
    /**
     * Връща един запис със съответните данни
     *
     * @param int    $docClass   - id на класа
     * @param int    $docId      - id на документа
     * @param string $versionStr - Версията и подверсията
     * @param mixed  $field      - Името на полето или масив с полетата
     *
     * return array $recArr - Масив с откритите записи
     */
    public static function getRecForVersion($docClass, $docId, $versionStr, $field = false)
    {
        // Вземаме версията и подверсията от стринга
        $versionArr = static::getVersionFromString($versionStr);
        
        // Вземаме записа за всички полета
        $rec = static::getRec($docClass, $docId, '*');
        
        // Ако няма, връщаме FALSE
        if (!$rec) {
            
            return false;
        }
        
        // Обхождаме масива
        foreach ((array) $rec as $f => $r) {
            
            // Стойността за съответната версия
            $val = $r->value[$versionArr[2]];
            
            // Ако няма прескачаме
            if (!$val) {
                continue;
            }
            
            // Ако не е подадено поле
            if ($field === false) {
                
                // Добавяме в масива
                $recArr[] = $val;
            } else {
                
                // Ако подадено поле съществува
                if ($field[$f]) {
                    
                    // Добавяме в масива
                    $recArr[$f] = $val;
                }
            }
        }
        
        return $recArr;
    }
}
