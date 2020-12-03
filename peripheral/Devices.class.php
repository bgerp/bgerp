<?php


/**
 *
 *
 * @category  vendors
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_Devices extends embed_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Периферни устройства';
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    public $singleTitle = 'Периферно устройство';
    
    
    /**
     * Интерфейс на драйверите
     */
    public $driverInterface = 'peripheral_DeviceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting, plg_Created, plg_Modified, peripheral_Wrapper, plg_RowTools2, plg_Search';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin, peripheral';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'admin, peripheral';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, peripheral';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, peripheral';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, peripheral';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'admin, peripheral';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'admin, peripheral';
    
    
    public $searchFields = 'name, driverClass';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('data', 'blob(compress,serialize)', 'input=none, single=none, column=none');
        
        $this->setDbIndex('name');
        $this->setDbUnique('name, driverClass');
    }
    
    
    /**
     * Връща едно устройство
     *
     * @param string      $intfName
     * @param array|false $checkParams
     * @param array       $checkFieldArr
     *
     * @return false|stdClass
     */
    public static function getDevice($intfName, $checkParams = array(), $checkFieldArr = array())
    {
        $deviceArr = self::getDevices($intfName, $checkParams, $checkFieldArr, 1);
        
        $dRec = false;
        
        if (!empty($deviceArr)) {
            $dRec = reset($deviceArr);
        }
        
        return $dRec;
    }
    
    
    /**
     * Връща всички устройства
     *
     * @param string      $intfName
     * @param array       $checkFieldArr
     * @param array|false $checkParams
     * @param null|int    $limit
     *
     * @return array
     */
    public static function getDevices($intfName, $checkParams = array(), $checkFieldArr = array(), $limit = null)
    {
        static $cArr = array();
        
        $hash = md5($intfName . '|' . serialize($checkParams));
        
        if (!isset($cArr[$hash])) {
            
            $me = cls::get(get_called_class());
            $cArr[$hash] = array();
            
            $clsArr = core_Classes::getOptionsByInterface($intfName);
            
            if (empty($clsArr)) {
                 
                 return $cArr[$hash];
            }
            
            $clsArr = array_keys($clsArr);
            
            $query = self::getQuery();
            $query->in($me->driverClassField, $clsArr);
            
            $query->orderBy('createdOn', 'DESC');
            
            $allRecs = $query->fetchAll();
            
            foreach ($allRecs as $recId => $rec) {
                if (!cls::load($rec->{$me->driverClassField})) continue;
                $inst = cls::get($rec->{$me->driverClassField});
                
                if ($checkParams !== false) {
                    if (!$inst->checkDevice($rec, $checkParams)) {
                        
                        continue;
                    }
                }
                
                $cArr[$hash][$recId] = $rec;
            }
        }
        
        $resArr = $cArr[$hash];
        
        if (!empty($checkFieldArr)) {
            foreach ($checkFieldArr as $fName => $fVal) {
                $fVal = trim($fVal);
                $fVal = mb_strtolower($fVal);
                
                foreach ((array) $resArr as $id => $rec) {
                    if ($fVal != mb_strtolower($rec->{$fName})) {
                        unset($resArr[$id]);
                    }
                }
                
                if (empty($resArr)) {
                    break;
                }
            }
        }
        
        if ($limit && countR($resArr) > 1) {
            $resArr = array_slice($resArr, 0, $limit, true);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив с всички резултати - ключа е полето, а стойността е името
     *
     * @param string      $intfName
     * @param string      $fName
     * @param array|false $checkParams
     *
     * @return array
     */
    public static function getDevicesArrByField($intfName, $fName, $checkParams = array())
    {
        $allDevicesArr = self::getDevices($intfName, $checkParams);
        
        $resArr = array();
        
        foreach ($allDevicesArr as $dRec) {
            $resArr[$dRec->{$fName}] = $dRec->name;
        }
        
        return $resArr;
    }
    
    
    /**
     * 
     * @param string|null $intfName
     * @param string|null $clsName
     * @return array
     */
    public static function getDevicesArrObjVal($intfName = null, $clsName = null)
    {
        $query = self::getQuery();
        
        $resArr = array();
        
        if (isset($intfName)) {
            $clsArr = core_Classes::getOptionsByInterface($intfName);
            
            if (empty($clsArr)) {
                
                return $resArr;
            }
            $clsArr = array_keys($clsArr);
            
            $me = cls::get(get_called_class());
            
            $query->in($me->driverClassField, $clsArr);
        }
        
        while ($rec = $query->fetch()) {
            $data = $rec->data;
            if (!$data) continue;
            
            if (!$data['objVal']) continue;
            
            if ($data['clsName'] != $clsName) continue;
            
            $kArr = type_Keylist::toArray($data['objVal']);
            
            $kArr = arr::make($kArr, true);
            
            $resArr += $kArr;
        }
        
        return $resArr;
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('modifiedOn', 'DESC');
        
        $data->listFilter->showFields = 'search, ' . $mvc->driverClassField;
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        if ($data->listFilter->rec->{$mvc->driverClassField}) {
            $data->query->where(array("#{$mvc->driverClassField} = '[#1#]'", $data->listFilter->rec->{$mvc->driverClassField}));
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Първичния ключ на направения запис
     * @param stdClass     $rec    Всички полета, които току-що са били записани
     * @param string|array $fields Имена на полетата, които sa записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
    }
}
