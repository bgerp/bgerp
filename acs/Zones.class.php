<?php


/**
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 * 
 * @since     v 0.1
 */
class acs_Zones extends core_Master
{
    /**
     * Заглавие на мениджъра
     */
    public $title = '';
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    public $singleTitle = '';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting, plg_Created, acs_Wrapper, plg_State2, plg_RowTools';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'acs, admin';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'acs, admin';
    
    
    /**
     * Кой има права да синхронизира зоните от интерфейса
     */
    public $canSync = 'acs, admin';
    
    
    /**
     * @see plg_State2
     */
    public $canChangestate = 'no_one';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име');
        $this->FLD('classId', 'class(interface=acs_ZoneIntf)', 'caption=Клас');
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Локация');
        $this->FNC('nameLoc', 'varchar', 'single=none,column=none');
        
        $this->setDbUnique('name, classId, locationId');
    }
    
    /**
     * 
     * 
     * @param acs_Zones $mvc
     * @param stdObject $rec
     * 
     * @return string
     */
    public function on_CalcNameLoc($mvc, $rec)
    {
        $rec->nameLoc = crm_Locations::getVerbal($rec->locationId, 'title') . ' (' . $rec->name . ')';
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Бутона 'Нов запис' в листовия изглед, добавя винаги универсален артикул
        if ($mvc->haveRightFor('sync')) {
            $data->toolbar->addBtn('Синхронизиране', array($mvc, 'sync'), 'order=1,id=btnSync', 'ef_icon = img/16/drive_go.png,title=Синхронизране');
        }
    }
    
    
    /**
     * Екшън за синхронизиране
     */
    function act_Sync()
    {
        $retUrl = array($this, 'list');
        $this->requireRightFor('sync');
        
        $clsArr = core_Classes::getOptionsByInterface('acs_ZoneIntf');
        
        if (empty($clsArr)) {
            
            return new Redirect($retUrl, '|Няма класове, които да се използват за източник', 'error');
        }
        
        $ownCompany = crm_Companies::fetchOurCompany();
        $ourLocations = crm_Locations::getContragentOptions('crm_Companies', $ownCompany->id);
        
        if (empty($ourLocations)) {
            
            return new Redirect($retUrl, '|Няма добавена локация за "Моята фирма"', 'error');
        }
        
        $activeCnt = 0;
        $activeArr = array();
        
        foreach ($clsArr as $cId => $clsName) {
            $inst = cls::get($clsName);
            $cp = $inst->getCheckpoints();
            foreach ($cp as $cpNameArr) {
                $mustSave = false;
                
                $cpName = $cpNameArr['name'];
                $locationId = $cpNameArr['locationId'];
                if (!$locationId) {
                    $this->logErr("Не е подадена локация: {$clsName} - {$cpName}");
                    
                    continue;
                }
                
                if (!isset($ourLocations[$locationId])) {
                    $this->logErr("Локацията не е на 'Моята фирма': {$clsName} - {$cpName} - {$locationId}");
                    
                    continue;
                }
                
                $rec = $this->fetch(array("#name = '[#1#]' AND #classId = '[#2#]' AND #locationId = '[#3#]'", $cpName, $cId, $locationId));
                if (!$rec) {
                    $rec = new stdClass();
                    $rec->name = $cpName;
                    $rec->classId = $cId;
                    $rec->locationId = $locationId;
                    $mustSave = true;
                } else {
                    // Ако има запис, който не е активен
                    if ($rec->state != 'active') {
                        $rec->state = 'active';
                        $mustSave = true;
                    } else {
                        $activeArr[$rec->id] = $rec->id;
                    }
                }
                
                // Ако трябва да се обнови записа
                if ($mustSave) {
                    $this->save($rec);
                    
                    $activeCnt++;
                    
                    if ($rec->id) {
                        $activeArr[$rec->id] = $rec->id;
                    }
                }
            }
        }
        
        // Затваряме активните, които ги няма в този списък
        $query = $this->getQuery();
        $query->notIn('id', $activeArr);
        $query->where("#state = 'active'");
        
        $closedCnt = 0;
        while ($qRec = $query->fetch()) {
            $qRec->state = 'closed';
            
            $this->save($qRec, 'state');
            
            $closedCnt++;
        }
        
        return new Redirect($retUrl, "|Активирани|* {$activeCnt}<br>|Затворени|* {$closedCnt}");
    }
    
    
    /**
     * Синхронизира правата в дадената зона
     *
     * @param array $zonesIdsArr
     */
    public static function syncZonePermissions($zonesIdsArr = array(), $onlyActive = true)
    {
        $zonesArr = acs_Permissions::getRelationsMap('zone');
        ;
        $query = self::getQuery();
        
        if (!empty($zonesIdsArr)) {
            $zArr = $zonesIdsArr;
        } else {
            $zArr = array_keys($zonesArr);
        }
        $query->in('id', $zArr);
        
        if ($onlyActive) {
            $query->where("#state = 'active'");
        }
        
        while ($zRec = $query->fetch()) {
            try {
                $intf = cls::getInterface('acs_ZoneIntf', $zRec->classId);
                $intf->setPermissions($zRec->name, $zonesArr[$zRec->id]);
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }
    }
}
