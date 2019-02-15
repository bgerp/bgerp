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
    public $loadList = 'plg_Sorting, plg_Created, plg_Modified, peripheral_Wrapper, plg_RowTools2, plg_Search, plg_StructureAndOrder';
    
    
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
    
    public $saoTitleField = 'name';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('brid', 'varchar(8)', 'caption=Компютър->Браузър, removeAndRefreshForm=saoParentId|saoOrder|saoLevel');
        $this->FLD('ip', 'ip', 'caption=Компютър->IP, removeAndRefreshForm=saoParentId|saoOrder|saoLevel');
        $this->FLD('isDefault', 'enum(no=Не,yes=Да)', 'caption=По подразбиране, notNull');
        
        $this->setDbUnique('name, brid, ip');
    }
    
    
    /**
     * Връща едно устройство към този BRID и/или IP
     *
     * @param string      $intfName
     * @param null|string $brid
     * @param null|string $ip
     * @param null|int    $limit
     *
     * @return false|stdClass
     */
    public static function getDevice($intfName, $brid = null, $ip = null, $limit = null)
    {
        $deviceArr = self::getDevices($intfName, $brid, $ip, 1);
        
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
     * @param null|int    $limit
     *
     * @return array
     */
    public static function getDevices($intfName, $brid = null, $ip = null, $limit = null)
    {
        static $cArr = array();
        
        $hash = md5($intfName . '|' . $brid . '|' . $ip . '|' . $limit);
        
        if (isset($cArr[$hash])) {
            
            return $cArr[$hash];
        }
        
        $me = cls::get(get_called_class());
        $cArr[$hash] = array();
        
        $clsArr = core_Classes::getOptionsByInterface($intfName);
        
        if (empty($clsArr)) {
            
            return $cArr[$hash];
        }
        
        $clsArr = array_keys($clsArr);
        
        $query = self::getQuery();
        $query->in($me->driverClassField, $clsArr);
        
        if ($brid) {
            $query->where(array("#brid = '[#1#]'", $brid));
            $query->orWhere('#brid IS NULL');
        } else {
            $query->where('#brid IS NULL');
        }
        $query->orWhere("#brid = ''");
        
        if ($ip) {
            $query->where(array("#ip = '[#1#]'", $ip));
            $query->orWhere('#ip IS NULL');
        } else {
            $query->where('#ip IS NULL');
        }
        $query->orWhere("#ip = ''");
        
        $query->orderBy('isDefault', 'DESC');
        $query->orderBy('saoOrder');
        $query->orderBy('createdOn', 'DESC');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $cArr[$hash] = $query->fetchAll();
        
        return $cArr[$hash];
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $brid = log_Browsers::getBrid();
        $data->form->setSuggestions('brid', array('' => '', $brid => $brid));
        
        $ip = core_Users::getRealIpAddr();
        $data->form->setSuggestions('ip', array('' => '', $ip => $ip));
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
        
        if ($fields['-list']) {
            $urlArr = array();
            if ($rec->isDefault != 'yes' && $mvc->haveRightFor('single', $rec->id)) {
                $urlArr = array($mvc, 'setDefault', $rec->id, 'ret_url' => true);
            }
            
            if ($rec->isDefault == 'yes') {
                $row->ROW_ATTR['class'] = 'state-active';
            } else {
                $row->ROW_ATTR['class'] = 'state-closed';
            }
            
            $row->isDefault = ht::createBtn('Избор', $urlArr, null, null, 'ef_icon = img/16/hand-point.png, title=Избор по подразбиране');
        }
    }
    
    
    /**
     * Екшън за избор на устройство по подразбиране
     */
    public function act_SetDefault()
    {
        $id = Request::get('id', 'int');
        
        expect($id);
        
        $rec = $this->fetch($id);
        
        expect($rec);
        
        $this->requireRightFor('single', $rec);
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array($this, 'single', $id);
        }
        
        $rec->isDefault = 'yes';
        
        $this->save($rec, 'isDefault');
        
        return new Redirect($retUrl, '|Успешно избран като текущ');
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
        // След избор на текущ, другите текущи ги премахваме
        if ($rec->isDefault == 'yes' && $rec->driverClass) {
            $query = $mvc->getQuery();
            
            $query->where(array("#{$mvc->driverClassField} = '[#1#]'", $rec->{$mvc->driverClassField}));
            
            if ($rec->brid) {
                $query->where(array("#brid = '[#1#]'", $rec->brid));
                $query->orWhere('#brid IS NULL');
            } else {
                $query->where('#brid IS NULL');
            }
            $query->orWhere("#brid = ''");
            
            if ($rec->ip) {
                $query->where(array("#ip = '[#1#]'", $rec->ip));
                $query->orWhere('#ip IS NULL');
            } else {
                $query->where('#ip IS NULL');
            }
            $query->orWhere("#ip = ''");
            
            $query->where(array('#id != [#1#]', $rec->id));
            
            $query->where("#isDefault = 'yes'");
            
            while ($oRec = $query->fetch()) {
                $oRec->isDefault = 'no';
                $mvc->save($oRec, 'isDefault');
            }
        }
    }
    
    
    /**
     * Необходим метод за подреждането
     */
    public static function getSaoItems($rec)
    {
        $query = self::getQuery();
        
        if ($rec->brid) {
            $query->where(array("#brid = '[#1#]'", $rec->brid));
        }
        
        if ($rec->ip) {
            $query->where(array("#ip = '[#1#]'", $rec->ip));
        }
        
        if ($rec->driverClass) {
            $query->where(array("#driverClass = '[#1#]'", $rec->driverClass));
        }
        
        if ($rec->id) {
            $query->where(array("#id != '[#1#]'", $rec->id));
        }
        
        $res = array();
        while ($rRec = $query->fetch()) {
            $res[$rRec->id] = $rRec;
        }
        
        return $res;
    }
}
