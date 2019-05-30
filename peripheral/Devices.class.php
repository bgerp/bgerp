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
    
    
    public $searchFields = 'name, brid, ip, driverClass';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('brid', 'text(rows=2)', 'caption=Компютър->Браузър');
        $this->FLD('ip', 'text(rows=2)', 'caption=Компютър->IP');
        $this->FLD('data', 'blob(compress,serialize)', 'input=none, single=none, column=none');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Връща едно устройство към този BRID и/или IP
     *
     * @param string      $intfName
     * @param null|string $brid
     * @param null|string $ip
     * @param array       $checkFieldArr
     *
     * @return false|stdClass
     */
    public static function getDevice($intfName, $brid = null, $ip = null, $checkFieldArr = array())
    {
        $deviceArr = self::getDevices($intfName, $brid, $ip, $checkFieldArr, 1);
        
        $dRec = false;
        
        if (!empty($deviceArr)) {
            $dRec = reset($deviceArr);
        }
        
        return $dRec;
    }
    
    
    /**
     * Връща всички устройства към този BRID и/или IP
     *
     * @param string      $intfName
     * @param null|string $brid
     * @param null|string $ip
     * @param array       $checkFieldArr
     * @param null|int    $limit
     *
     * @return array
     */
    public static function getDevices($intfName, $brid = null, $ip = null, $checkFieldArr = array(), $limit = null)
    {
        static $cArr = array();
        
        $hash = md5($intfName . '|' . $brid . '|' . $ip);
        
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
                
                if (!self::checkExist($brid, $rec->brid)) continue;
                
                if (!self::checkExist($ip, $rec->ip, '*')) continue;
                
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
        
        if ($limit && count($resArr) > 1) {
            $resArr = array_slice($resArr, 0, $limit, true);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив с всички резултати - ключа е полето, а стойността е името
     *
     * @param string      $intfName
     * @param string      $fName
     * @param null|string $brid
     * @param null|string $ip
     *
     * @return array
     */
    public static function getDevicesArrByField($intfName, $fName, $brid = null, $ip = null)
    {
        $allDevicesArr = self::getDevices($intfName, $brid, $ip);
        
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
     * Помощна фунцкия за проверка дали пододана стойност я има в стинг
     *
     * @param string $val
     * @param string $str
     * @param string|null $matchStr
     *
     * @return boolean
     */
    private static function checkExist($val, $str, $matchStr = null)
    {
        $str = str_replace(array(',', ';'), ' ', $str);
        
        $val = trim($val);
        $str = trim($str);
        $exist = false;
        if ($val) {
            if ($str) {
                $valArr = explode(' ', $str);
                foreach ($valArr as $valStr) {
                    $valStr = trim($valStr);
                    if ($valStr == $val) {
                        $exist = true;
                        break;
                    } else {
                        
                        // Ако има символ, за заместване на израз
                        if (isset($matchStr) && (stripos($valStr, $matchStr) !== false)) {
                            $pattern = preg_quote($valStr, '/');
                            
                            $pattern = str_replace(preg_quote($matchStr, '/'), '.*', $pattern);
                            
                            $pattern = "/^{$pattern}$/";
                            if (preg_match($pattern, $val)) {
                                $exist = true;
                                break;
                            }
                        }
                    }
                }
            } else {
                $exist = true;
            }
        } else {
            $exist = true;
        }
        
        return $exist;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $time = dt::mysql2timestamp(dt::subtractSecs(3600));
        $dQuery = log_Data::getQuery();
        $dQuery->where("#type = 'login'");
        $dQuery->where(array("#classCrc = '[#1#]'"), log_Classes::getClassCrc('core_Users'));
        $dQuery->where(array("#time >= '[#1#]'", $time));
        
        $dQuery->EXT('bridStr', 'log_Browsers', 'externalName=brid,externalKey=brId');
        $dQuery->EXT('ipStr', 'log_Ips', 'externalName=ip,externalKey=ipId');
        $dQuery->EXT('roles', 'core_Users', 'externalName=roles,externalKey=objectId');
        
        $pu = core_Roles::fetchByName('powerUser');
        
        $dQuery->like("roles", type_Keylist::fromArray(array($pu => $pu)));
        
        $dQuery->orderBy('time', 'DESC');
        
        $bridAr = array();
        $ipArr = array();
        while ($dRec = $dQuery->fetch()) {
            $nick = core_Users::getNick($dRec->objectId);
            $names = core_Users::fetchField($dRec->objectId, 'names');
            $names = core_Users::prepareUserNames($names);
            
            if (!$bridArr[$dRec->bridStr]) {
                $template = "{$nick} <span class='autocomplete-name'>{$names} ({$dRec->bridStr})</span>";
                $bridArr[$dRec->bridStr] = array('val' => $dRec->bridStr, 'template' => $template, 'search' => $dRec->bridStr . ' ' . $nick . ' ' . $names);
            }
            
            if (!$ipArr[$dRec->ipStr]) {
                $template = "{$nick} <span class='autocomplete-name'>{$names} ({$dRec->ipStr})</span>";
                $ipArr[$dRec->ipStr] = array('val' => $dRec->ipStr, 'template' => $template, 'search' => $dRec->ipStr . ' ' . $nick . ' ' . $names);
            }
        }
        
        $brid = log_Browsers::getBrid();
        $data->form->setSuggestions('brid', $bridArr);
        
        $ip = core_Users::getRealIpAddr();
        $data->form->setSuggestions('ip', $ipArr);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if (!$form->rec->brid && !$form->rec->ip) {
                $form->setError('brid, ip', 'Непопълнено задължително поле');
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn);
        $row->brid = log_Browsers::getLink($rec->brid);
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
        
        $data->listFilter->showFields = 'search';
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
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
