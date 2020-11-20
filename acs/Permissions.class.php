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
class acs_Permissions extends core_Master
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
    public $loadList = 'plg_Sorting, plg_Created, acs_Wrapper, plg_State, plg_RowTools2';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'acs, admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'acs, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'acs, admin';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'acs, admin';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'acs, admin';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('companyId', 'key2(mvc=crm_Companies, select=name, allowEmpty, allowEmpty)', 'caption=Фирма, refreshForm');
        // @todo - може и да има група
        $this->FLD('personId', 'key2(mvc=crm_Persons, select=name, allowEmpty, allowEmpty)', 'caption=Лице, refreshForm');
        
        // @todo - може да е наш тип наследник на varchar. Там може да е логиката за преобразуване на id'то на картата
        $this->FLD('cardId', 'varchar', 'caption=Карта, refreshForm');
        $this->FLD('cardType', 'enum(card=Карта, bracelet=Гривна, phone=Телефон, chip=Чип)', 'caption=Тип на карта');
        $this->FLD('zones', 'keylist(mvc=acs_Zones, select=nameLoc)', 'caption=Зони');
        $this->FLD('scheduleId', 'int', 'caption=График'); //@todo
        $this->FLD('duration', 'time(min=1)', 'caption=Продължителност');
        $this->FLD('expiredOn', 'datetime', 'caption=Изтича на');
        $this->FLD('activatedOn', 'datetime', 'caption=Активирано на, input=none');
        $this->FLD('state', 'enum(,pending=Заявка,active=Активен,closed=Закрит,rejected=Оттеглен)','caption=Състояние,column=none,input=none,smartCenter, refreshForm');
        
        $this->setDbIndex('personId');
        $this->setDbIndex('companyId');
        $this->setDbIndex('state, cardId');
        $this->setDbIndex('state');
        $this->setDbIndex('cardId');
    }
    
    
    /**
     * Временен тестов екшън
     * 
     * @todo - премахване
     */
    function act_Test()
    {
        requireRole('admin');
        requireRole('debug');
        
        $res = "";
        
        $query = self::getQuery();
        $query->groupBy('cardId');
        $query->orderBy('createdOn', 'DESC');
        $zones = '';
        $cArr = array();
        while ($rec = $query->fetch()) {
            $cArr[$rec->cardId] = $rec->cardId;
            $zones = type_Keylist::merge($zones, $rec->zones);
        }
        
        $zArr = type_Keylist::toArray($zones);
        foreach ($cArr as $cId) {
            $res .= "<li style='color: black;'>cardId: {$cId}</li>";
            foreach ($zArr as $zId) {
                $styleColor = 'red';
                
                if ($this->isCardHaveAccessToZone($cId, $zId)) {
                    $styleColor = 'green';
                }
                
                $zoneName = acs_Zones::getVerbal($zId, 'name');
                
                $res .= "<li style='color: {$styleColor};'>zoneId: {$zId}|{$zoneName} </li>";
            }
            $res .= "<hr>";
        }
        
        echo $res;
        
        echo "<pre>";
        var_dump($this->getRelationsMap('card'));
        var_dump($this->getRelationsMap('zone'));
        
        shutdown();
    }
    
    
    /**
     * Проверява дали за подадено време, съответната карта има достъп до зоната
     * 
     * @param string  $cardId
     * @param integer $zoneId
     * @param integer $timestamp
     * 
     * @return boolean
     */
    public static function isCardHaveAccessToZone($cardId, $zoneId, $timestamp = null)
    {
        $mArr = self::getAllowedZonesForCard($cardId);
        
        if (empty($mArr)) {
            
            return false;
        }
        
        if (!isset($mArr[$zoneId])) {
            
            return false;
        }
        
        $t = $mArr[$zoneId];
        
        if (!isset($timestamp)) {
            $timestamp = dt::mysql2timestamp();
        }
        
        if ($t >= $timestamp) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Връща зоните до които има достъп съответната карта
     * 
     * @param string $cardId
     * 
     * @return array
     */
    public static function getAllowedZonesForCard($cardId)
    {
        $mapArr = self::getRelationsMapForCards();
        
        return (array) $mapArr[$cardId];
    }
    
    
    /**
     * Връща каритите, които имат достъп до съответната зона
     * 
     * @param integer $zoneId
     * 
     * @return array
     */
    public static function getAllowedCardsForZone($zoneId)
    {
        $mapArr = self::getRelationsMapForZones();
        
        return (array)$mapArr[$zoneId];
    }
    
    
    /**
     * Връща масив с връзките между картите и зоните и времето им на валидност
     * 
     * @param string $for - card|zone
     * @return array
     */
    public static function getRelationsMap($for = 'card')
    {
        if ($for == 'card') {
            
            return self::getRelationsMapForCards();
        } elseif($for == 'zone') {
            
            return self::getRelationsMapForZones();
        } else {
            expect(false);
        }
    }
    
    
    /**
     * Обновява зоните - към старите зони, добавя и новата
     * 
     * @param integer $oZoneId
     * @param integer $newZoneId
     */
    public static function updateZoneId($oZoneId, $newZoneId)
    {
        $query = self::getQuery();
        $query->likeKeylist('zones', $oZoneId);
        while ($rec = $query->fetch()) {
            $cRec = clone $rec;
            $cRec->zones = type_Keylist::addKey($cRec->zones, $newZoneId);
            
            self::save($cRec, 'zones');
        }
    }
    
    
    /**
     * Връща масив с картите и зоните, които отключват и времето в което е валидно
     * 
     * @return boolean|array
     */
    public static function getRelationsMapForCards()
    {
        $cacheType = get_called_class();
        $cacheHandler = 'relationMap';
        $depends = array($cacheType, 'acs_Zones');
        
        // Ако го има в кеша - използваме го
        $resArr = core_Cache::get($cacheType, $cacheHandler, null, $depends);
        
        if (isset($resArr) && ($resArr !== false)) {
            
            return $resArr;
        }
        
        $query = self::getQuery();
        $query->where("#state = 'active'");
        
        $res = array();
        
        $persCompArr = $cardPersArr =  $cardCompArr = array();
        
        $nTimestamp = dt::mysql2timestamp();
        
        while ($rec = $query->fetch()) {
            $nActiveTime = self::getNextActiveTime($rec);
            
            if ($nTimestamp < $nActiveTime['activeUntil']) {
                // Времето за инвалидиране на кеша - най-малката стойност от масива
                setIfNot($minActiveTime, $nActiveTime['activeUntil']);
            }
            
            if ($nTimestamp < $nActiveTime['activeFrom']) {
                // Времето за инвалидиране на кеша - най-малката стойност от масива
                setIfNot($minActiveTime, $nActiveTime['activeFrom']);
            }
            
            if (isset($nActiveTime['activeUntil']) && ($nTimestamp < $nActiveTime['activeUntil'])) {
                $minActiveTime = min($minActiveTime, $nActiveTime['activeUntil']);
            }
            
            if (isset($nActiveTime['activeFrom']) && ($nTimestamp < $nActiveTime['activeFrom'])) {
                $minActiveTime = min($minActiveTime, $nActiveTime['activeFrom']);
            }
            
            $zArr = type_Keylist::toArray($rec->zones);
            
            // Пълним масива със стойности, като най-малкото е с по-голям приоритет, ако са попълнени повечето
            foreach (array('cardId', 'personId', 'companyId') as $cName) {
                if ($rec->{$cName}) {
                    foreach ($zArr as $zId) {
                        $res[$cName][$rec->{$cName}][$zId] = $nActiveTime['activeUntil'];
                    }
                    
                    break;
                }
            }
            
            // Връзка между лицата и фирмите
            if ($rec->personId && $rec->companyId) {
                $persCompArr[$rec->personId] = $rec->companyId;
            }
            
            // Връзка между картите и фирмите
            if ($rec->cardId && $rec->companyId) {
                $cardCompArr[$rec->cardId] = $rec->companyId;
            }
            
            // Връзка между картите и лицата
            if ($rec->cardId && $rec->personId) {
                $cardPersArr[$rec->cardId] = $rec->personId;
            }
        }
            
        // Вкарваме зоните на фирмите, към лицата
        foreach ($persCompArr as $pId => $cId) {
            
            // Вкарваме зоните от модела
            if ($res['companyId'][$cId]) {
                foreach ($res['companyId'][$cId] as $zId => $timestamp) {
                    $res['personId'][$pId][$zId] = max($res['personId'][$pId][$zId], $timestamp);
                }
            }
        }
        
        // Вкарваме зоните на фирмата към картите
        foreach ($cardCompArr as $cardId => $cId) {
            if ($res['companyId'][$cId]) {
                foreach ($res['companyId'][$cId] as $zId => $timestamp) {
                    $res['cardId'][$cardId][$zId] = max($res['cardId'][$cardId][$zId], $timestamp);
                }
            }
        }
        
        // Вкарваме зоните на лицата към картите
        foreach ($cardPersArr as $cardId => $pId) {
            if ($res['personId'][$pId]) {
                foreach ($res['personId'][$pId] as $zId => $timestamp) {
                    $res['cardId'][$cardId][$zId] = max($res['cardId'][$cardId][$zId], $timestamp);
                }
            }
        }
        
        // Резултатния масив го записваме в кеша
        $keepMinutes = 0;
        if ($minActiveTime) {
            $keepMinutes = $minActiveTime - $nTimestamp;
            if ($keepMinutes > 60) {
                $keepMinutes = intval($keepMinutes / 60);
                $keepMinutes--;
            }
        }
        
        $cardsArr = (array)$res['cardId'];
        
        core_Cache::set($cacheType, $cacheHandler, $cardsArr, $keepMinutes, $depends);
        
        return $cardsArr;
    }
    
    
    /**
     * Връща масив със зоните и картите, които ги отключват и времето в което е валидно
     * 
     * @return array
     */
    public static function getRelationsMapForZones()
    {
        static $zonesArr;
        
        if (isset($zonesArr)) {
            
            return $zonesArr;
        }
        
        $zonesArr = array();
        
        $cardsArr = self::getRelationsMapForCards();
        foreach ($cardsArr as $cId => $zArr) {
            foreach ($zArr as $zId => $time) {
                if (isset($zonesArr[$zId][$cId])) {
                    $zonesArr[$zId][$cId] = max($zonesArr[$zId][$cId], $time);
                }
                $zonesArr[$zId][$cId] = $time;
            }
        }
        
        return $zonesArr;
    }
    
    
    /**
     * Връща последния собственик на картата за съответното врме
     * 
     * @param string  $cardId
     * @param integer $timestamp
     */
    public static function getCardHolder($cardId, $timestamp = null)
    {
        if (!isset($timestamp)) {
            $timestamp = dt::mysql2timestamp();
        }
        
        $query = self::getQuery();
        $query->where(array("#cardId = '[#1#]'", $cardId));
        $dTime = dt::timestamp2Mysql($timestamp);
        $query->where(array("#activatedOn <= '[#1#]'", $dTime));
        $query->where(array("#createdOn <= '[#1#]'", $dTime));
        $query->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 ELSE 2 END)");
        $query->orderBy('#orderByState=ASC');
        
        $query->orderBy('activatedOn', 'DESC');
        
        $query->limit(1);
        
        $query->show('personId, companyId');
        
        $rec = $query->fetch();
        
        return array('companyId' => $rec->companyId, 'personId' => $rec->personId);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param acs_Permissions  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            $msg = 'Трябва да сте попълнили някои от полетата';
            
            if (!$rec->companyId && !$rec->personId && !$rec->cardId) {
                $form->setError('companyId, personId, cardId', $msg);
            }
            
            if ($rec->cardId) {
                if ((!$rec->companyId) && (!$rec->personId)) {
                    $form->setError('companyId, personId', $msg);
                }
            }
            
            if (($rec->companyId) && ($rec->personId)) {
                if (!$rec->zones) {
                    $form->setError('zones', $msg);
                }
            } elseif (($rec->companyId) || ($rec->personId)) {
                if (!$rec->cardId && !$rec->zones) {
                    $form->setError('cardId, zones', $msg);
                }
            }
            
            // @todo - времената?
        }
        
        if ($form->isSubmitted()) {
            $iArr = $mvc->getIntersectingRecs($rec);
            if (!empty($iArr)) {
                if ($iArr['card']) {
                    $w = 'Тази карта в момента се използва|*. |Ще бъде разкачена от|*:';
                    foreach ($iArr['card'] as $iRec) {
                        $w .= "<br>" . $mvc->getLinkTocard($iRec);
                    }
                    
                    $form->setWarning('cardId', $w);
                }
                
                if ($iArr['company']) {
                    $w = 'Тази потребител участва в друга фирма|*. |Ще бъде разкачен от|*:';
                    foreach ($iArr['company'] as $iRec) {
                        $w .= "<br>" . $mvc->getLinkTocard($iRec, false);
                    }
                    
                    $form->setWarning('personId, companyId', $w);
                }
            }
        }
        
        if ($form->isSubmitted() && !$form->rec->id) {
            $rec->state = 'active';
            $rec->activatedOn = dt::now();
        }
        
        if ($form->isSubmitted()) {
            if ((!$form->rec->duration && !$form->rec->expiredOn) || 
                ($form->rec->duration && $form->rec->expiredOn)) {
                $form->setError('duration, expiredOn', 'Трябва да попълните едно от полетата');
            }
        }
        
        if ($form->isSubmitted()) {
            if ($form->rec->duration) {
                if ($form->rec->duration <= 0) {
                    $form->setError('duration', 'Не може да е отрицателно време');
                }
            }
            
            if ($form->rec->expiredOn) {
                if ($form->rec->expiredOn <= dt::now()) {
                    $form->setError('expiredOn', 'Не може да е в миналото');
                }
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param acs_Permissions $mvc     Мениджър, в който възниква събитието
     * @param int             $id      Първичния ключ на направения запис
     * @param stdClass        $rec     Всички полета, които току-що са били записани
     * @param string|array    $fields  Имена на полетата, които sa записани
     * @param string          $mode    Режим на записа: replace, ignore
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        // Деактивираме другите активни карти
        if ($rec->state == 'active') {
            $iArr = $mvc->getIntersectingRecs($rec);
            if (!empty($iArr)) {
                $all = (array) $iArr['card'] + (array) $iArr['company'];
                
                foreach ($all as $aRec) {
                    $aRec->state = 'closed';
                    $mvc->save($aRec, 'state');
                    $mvc->logNotice('Деактивиране поради добавяне на нова дублираща', $aRec->id);
                }
            }
        }
    }
    
    
    /**
     * Връща връзка към сингъла на картата
     * 
     * @param stdClass $rec
     * @param boolean $showPersonName
     * 
     * @return core_ET
     */
    public static function getLinkToCard($rec, $showPersonName = true)
    {
        $url = array();
        if (self::haveRightFor('single', $rec)) {
            $url = array(get_called_class(), 'single', $rec->id);
        }
        
        $title = '';
        
        if ($rec->personId && ($showPersonName || !$rec->companyId)) {
            $title = crm_Persons::getVerbal($rec->personId, 'name');
        }
        
        if ($rec->companyId) {
            $cTitle = crm_Companies::getVerbal($rec->companyId, 'name');
            if ($title) {
                $title .= ' (' . $cTitle . ')';
            } else {
                $title = $cTitle;
            }
        }
        
        return ht::createLink($title, $url);
    }
    
    
    /**
     * Връща припокриващите се активни записи
     * 
     * @param stdClass $rec
     * 
     * @return array
     */
    public static function getIntersectingRecs($rec)
    {
        $resArr = array();
        $query = self::getQuery();
        $query->where("#state = 'active'");
        if ($rec->id) {
            $query->where(array("#id != '[#1#]'", $rec->id));
        }
        
        // Един човек може да участва само в една фирма
        if ($rec->personId && $rec->companyId) {
            $cQuery = clone $query;
            
            $cQuery->where("#companyId IS NOT NULL");
            $cQuery->where(array("#personId = '[#1#]'", $rec->personId));
            
            $resArr['company'] = $cQuery->fetchAll();
        }
        
        // Една карта може да е само към един човек или фирма
        if ($rec->cardId) {
            // @todo - може да има нужда от преобразуване на cardId
            
            // @todo - ако се добавя към същата фирма и/или лице дали пак да се инвалидира?
            
            $cQuery = clone $query;
            $cQuery->where(array("#cardId = '[#1#]'", $rec->cardId));
            
            $resArr['card'] = $cQuery->fetchAll();
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив с времето на активност на картата
     * 
     * @param stdClass $rec
     * @return NULL[]|number[]
     */
    public static function getNextActiveTime($rec)
    {
        $res = array();
        
        // @todo $rec->scheduleId
        
        if ($rec->expiredOn) {
            $timestamp = dt::mysql2timestamp($rec->expiredOn);
        }
        
        if ($rec->duration) {
            $timestamp = dt::mysql2timestamp($rec->activatedOn) + $rec->duration;
        }
        
        $res['activeUntil'] = $timestamp;
        $res['activeFrom'] = $timestamp;
        
        return $res;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FNC('zoneId', 'key(mvc=acs_Zones, select=name, allowEmpty)', 'caption=Зона, refreshForm');
        
        // Да се показва полето за търсене
        $data->listFilter->showFields = 'companyId, personId, cardId, zoneId, state';
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        $data->listFilter->view = 'vertical';
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input();
        
        $rec = $data->listFilter->rec;
        
        if ($rec->zoneId) {
            $data->query->likeKeylist('zones', $rec->zoneId);
        }
        
        foreach (array('companyId', 'personId', 'cardId', 'type', 'state') as $fName) {
            if ($rec->{$fName}) {
                $data->query->where(array("#{$fName} = '[#1#]'", $rec->{$fName}));
            }
        }
    }
    
    
    /**
     * 
     */
    public function cron_SyncPermissions()
    {
        acs_Zones::syncZonePermissions();
    }
}
