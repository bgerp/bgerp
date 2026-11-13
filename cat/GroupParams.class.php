<?php


/**
 * Клас 'cat_GroupParams' - параметри на групите на артикулите
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cat_GroupParams extends cat_products_ProtoParams
{
    /**
     * Заглавие
     */
    public $title = 'Параметри на групи';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'groupId';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,groupId, paramId, paramValue';


    /**
     * Кой може да вижда списъка
     */
    public $canList = 'cat,ceo';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'cat,ceo';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'cat,ceo';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'cat,ceo';


    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id, groupId, paramId';


    /**
     * Параметрите и родителите на групите, кеширани в паметта
     */
    protected static $map = null;


    /**
     * Сметнатите параметри на групите, заедно с наследените
     */
    protected static $effective = array();


    /**
     * Типовете параметри, които не се задават за групи - формулите и материалите в рецептите
     */
    protected static $notAllowedDrivers = array('cond_type_Formula', 'cond_type_Product');


    /**
     * Над колко артикула транспортът в черновите се преизчислява на заден план
     */
    protected static $maxTransportRecalcNow = 20;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група,input=hidden,silent,mandatory,removeAndRefreshForm=paramId|paramValue|paramValue[lP]|paramValue[rP]');
        $this->addParamFields();

        $this->setDbUnique('groupId,paramId');
    }


    /**
     * Групите не са документи, затова формата не минава през doc_Detail
     * При нов запис групата се избира във формата, за да може да се добавя и от списъка
     */
    public function prepareEditForm_($data)
    {
        setPartIfNot($data, 'singleTitle', $this->singleTitle);
        core_Manager::prepareEditForm_($data);

        $data->masterMvc = cls::get('cat_Groups');
        $data->masterKey = 'groupId';
        $rec = $data->form->rec;
        if (empty($rec->id)) {
            $data->form->setField('groupId', 'input');
        }

        // Без избрана група core_Manager::prepareRetUrl_() чете полето
        $rec->groupId = $rec->groupId ?? null;
        $data->masterId = $rec->groupId;
        if (!empty($data->masterId)) {
            expect($data->masterRec = cat_Groups::fetch($data->masterId));
        }

        return $data;
    }


    /**
     * Заглавие на формата - с групата, ако вече е избрана
     */
    public function prepareEditTitle_($data)
    {
        if (empty($data->masterId)) {
            return core_Manager::prepareEditTitle_($data);
        }

        return core_Detail::prepareEditTitle_($data);
    }


    /**
     * Бутон за нов запис и в списъка, където групата се избира във формата
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (empty($data->masterId) && $mvc->haveRightFor('add')) {
            $data->toolbar->addBtn('Нов запис', array($mvc, 'add', 'ret_url' => true), 'id=btnAdd', 'ef_icon = img/16/star_2.png,title=Създаване на нов запис');
        }
    }


    /**
     * В списъка се подреждат по дървото на групите - подгрупите след родителя си
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $groupIds = array_keys(static::getGroupPaths());
        if (countR($groupIds)) {
            $data->query->XPR('groupOrder', 'int', 'FIELD(#groupId, ' . implode(',', $groupIds) . ')');
            $data->query->orderBy('groupOrder', 'ASC');
        }
    }


    /**
     * Пълните пътища на групите, подредени като дърво
     *
     * @return array $res - път "Родител » Дете", с ключ ид на група
     */
    protected static function getGroupPaths()
    {
        $res = array();
        $query = cat_Groups::getQuery();
        $query->show('id');
        while ($gRec = $query->fetch()) {
            $res[(int) $gRec->id] = cat_Groups::getVerbal($gRec->id, 'name');
        }
        uasort($res, 'strnatcasecmp');

        return $res;
    }


    /**
     * В списъка групата е с пълния път и е линк
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list']) && !empty($rec->groupId)) {
            $attr = array('ef_icon' => cls::get('cat_Groups')->getSingleIcon($rec->groupId), 'class' => 'specialLink');
            $row->groupId = ht::createLink(cat_Groups::getVerbal($rec->groupId, 'name'), cat_Groups::getSingleUrlArray($rec->groupId), false, $attr);
        }
    }


    /**
     * Връща ид на класа на обекта, към който е параметърът
     */
    public function getDomainClassId($rec)
    {
        return cat_Groups::getClassId();
    }


    /**
     * Връща параметрите, които могат да се добавят към групата
     */
    public function getParamOptions($rec, &$warningMsg)
    {
        $groupId = (int) ($rec->groupId ?? 0);
        $query = self::getQuery();
        $query->where("#groupId = {$groupId}");
        $query->show('paramId');
        $notIn = arr::extractValuesFromArray($query->fetchAll(), 'paramId');

        $driverIds = array();
        foreach (static::$notAllowedDrivers as $driver) {
            $driverIds[] = core_Classes::getId($driver);
        }
        $where = '#driverClass NOT IN (' . implode(',', $driverIds) . ')';
        if (countR($notIn)) {
            $where .= ' AND #id NOT IN (' . implode(',', $notIn) . ')';
        }

        return cat_Params::makeArray4Select(null, $where);
    }


    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        if (!empty($rec->paramId) && cat_Params::haveDriver($rec->paramId, static::$notAllowedDrivers)) {
            $form->setError('paramId', 'Параметърът не може да се задава за групи|*!');
        }
    }


    /**
     * Връща параметрите на групата, заедно с наследените от родителите ѝ
     * При повтарящ се параметър важи стойността от най-близката група
     *
     * @param int $groupId - ид на група
     *
     * @return array $res  - записите, с ключ ид на параметъра
     */
    public static function getParamRecs($groupId)
    {
        $chain = static::getChain($groupId);
        $res = array();
        if (!countR($chain)) return $res;

        $depth = array_flip($chain);
        $query = static::getParamQuery();
        $query->in('groupId', $chain);

        while ($rec = $query->fetch()) {
            $exRec = $res[$rec->paramId] ?? null;
            if (!is_object($exRec) || $depth[$rec->groupId] < $depth[$exRec->groupId]) {
                $res[$rec->paramId] = $rec;
            }
        }

        return $res;
    }


    /**
     * Връща веригата от групата до най-горния ѝ родител
     *
     * @param int $groupId - ид на група
     *
     * @return array $chain - ид-та на групите, като най-близката е първа
     */
    public static function getChain($groupId)
    {
        $parents = static::getMap()['parents'];
        $chain = array();
        $parentId = $groupId;
        while (!empty($parentId) && !isset($chain[$parentId])) {
            $chain[$parentId] = $parentId;
            $parentId = $parents[$parentId] ?? null;
        }

        return array_values($chain);
    }


    /**
     * Брои параметрите на групите, заедно с наследените
     *
     * @param array $groupIds - ид-та на групи
     *
     * @return array $res     - брой параметри, с ключ ид на група
     */
    public static function countParams($groupIds)
    {
        $res = array();
        foreach ($groupIds as $groupId) {
            $res[$groupId] = countR(static::getEffective($groupId));
        }

        return $res;
    }


    /**
     * Параметрите на групите и родителите им - от кеша, който се обновява при промяна в групите и параметрите
     *
     * @return array - ['recs' => [група => [параметър => запис]], 'parents' => [група => родител],
     *                  'paramIds' => [параметър => параметър], 'sysIds' => [sysId => параметър]]
     */
    public static function getMap()
    {
        if (!isset(static::$map)) {
            $depends = array('cat_GroupParams', 'cat_Groups', 'cat_Params');
            $map = core_Cache::get('cat_GroupParams', 'map', 1440, $depends);
            if (!is_array($map) || !isset($map['paramIds'])) {
                $map = array('recs' => array(), 'parents' => array(), 'paramIds' => array(), 'sysIds' => array());

                // Преди setup-а на пакета таблицата я няма, а getParams() се вика навсякъде
                $me = cls::get(get_called_class());
                if (!$me->db->tableExists($me->dbTableName)) {
                    static::$map = $map;

                    return static::$map;
                }

                $query = static::getParamQuery();
                $query->EXT('sysId', 'cat_Params', 'externalName=sysId,externalKey=paramId');
                while ($rec = $query->fetch()) {
                    $map['recs'][$rec->groupId][$rec->paramId] = (object) array('id' => $rec->id, 'groupId' => $rec->groupId, 'paramId' => $rec->paramId, 'paramValue' => $rec->paramValue, 'group' => $rec->group, 'orderEx' => $rec->orderEx);
                    $map['paramIds'][$rec->paramId] = $rec->paramId;
                    if (!empty($rec->sysId)) {
                        $map['sysIds'][$rec->sysId] = $rec->paramId;
                    }
                }

                $gQuery = cat_Groups::getQuery();
                $gQuery->where('#parentId IS NOT NULL');
                $gQuery->show('id,parentId');
                while ($gRec = $gQuery->fetch()) {
                    $map['parents'][$gRec->id] = $gRec->parentId;
                }
                core_Cache::set('cat_GroupParams', 'map', $map, 1440, $depends);
            }
            static::$map = $map;
        }

        return static::$map;
    }


    /**
     * Изчиства кеша при промяна - и в паметта, и в core_Cache
     * Зависимостите се сравняват с точност до секунда, затова записът се маха изрично
     */
    public static function resetCache()
    {
        static::$map = null;
        static::$effective = array();
        core_Cache::remove('cat_GroupParams', 'map');
    }


    /**
     * Параметрите на групата, заедно с наследените, с дълбочината на групата, от която идват
     *
     * @param int $groupId - ид на група
     *
     * @return array $res  - записите, с ключ ид на параметъра
     */
    protected static function getEffective($groupId)
    {
        if (!isset(static::$effective[$groupId])) {
            $recs = static::getMap()['recs'];
            $chain = static::getChain($groupId);
            $total = countR($chain);
            $res = array();
            foreach ($chain as $i => $chainId) {
                foreach ($recs[$chainId] ?? array() as $paramId => $rec) {
                    if (!isset($res[$paramId])) {
                        $rec = clone $rec;
                        $rec->depth = $total - $i;
                        $res[$paramId] = $rec;
                    }
                }
            }
            static::$effective[$groupId] = $res;
        }

        return static::$effective[$groupId];
    }


    /**
     * Наследените от групите параметри на артикула
     *
     * @param int $productId - ид на артикул
     *
     * @return array         - записите, с ключ ид на параметъра
     */
    public static function getProductParamRecs($productId)
    {
        // Без параметри на групи артикулът не се чете
        if (!countR(static::getMap()['recs'])) return array();

        $pRec = cat_Products::fetch($productId, 'groups,groupsInput');
        if (!is_object($pRec)) return array();

        $groups = !empty($pRec->groupsInput) ? $pRec->groupsInput : ($pRec->groups ?? '');

        return static::getParamRecsByGroups(keylist::toArray($groups));
    }


    /**
     * Допълва параметрите на артикула с наследените от групите му, които той няма
     *
     * @param mixed       $productId - ид или запис на артикул
     * @param mixed       $params    - параметрите от драйвера
     * @param string|null $name      - конкретен параметър (ид или sysId)
     * @param bool        $verbal    - вербално представяне
     *
     * @return mixed
     */
    public static function supplementProductParams($productId, $params, $name = null, $verbal = false)
    {
        $productId = is_object($productId) ? ($productId->id ?? null) : $productId;
        if (empty($productId)) return $params;

        $productClassId = cat_Products::getClassId();
        if (isset($name)) {

            // Стойността на артикула винаги е с предимство
            if (static::hasValue($params)) return $params;

            // Ако параметърът не е в никоя група, артикулът не се чете
            $map = static::getMap();
            $paramId = is_numeric($name) ? (int) $name : ($map['sysIds'][$name] ?? null);
            if (!isset($paramId, $map['paramIds'][$paramId])) return $params;

            $rec = static::getProductParamRecs($productId)[$paramId] ?? null;
            if (!is_object($rec)) return $params;

            return $verbal ? cat_Params::toVerbal($paramId, $productClassId, $productId, $rec->paramValue) : $rec->paramValue;
        }

        $groupRecs = static::getProductParamRecs($productId);
        if (!countR($groupRecs)) return $params;

        $params = is_array($params) ? $params : array();
        foreach ($groupRecs as $paramId => $rec) {
            if (static::hasValue($params[$paramId] ?? null)) continue;
            $params[$paramId] = $verbal ? cat_Params::toVerbal($paramId, $productClassId, $productId, $rec->paramValue) : $rec->paramValue;
        }

        return $params;
    }


    /**
     * Наследените от групите параметри на артикула, които са счетоводни свойства и артикулът няма собствени
     *
     * @param int               $productId - ид на артикул
     * @param cat_ProductDriver $Driver    - драйвер на артикула
     *
     * @return array $res - вербалните стойности, с ключ името на параметъра
     */
    public static function getProductFeatures($productId, $Driver)
    {
        $res = array();
        $productClassId = cat_Products::getClassId();
        foreach (static::getProductParamRecs($productId) as $paramId => $gRec) {
            if (cat_Params::fetchField($paramId, 'isFeature') != 'yes') continue;
            if (static::hasValue($Driver->getParams($productClassId, $productId, $paramId))) continue;

            // Вербализира се като собствен параметър, за да е същото свойство
            $pRec = (object) array('productId' => $productId, 'classId' => $productClassId, 'paramId' => $paramId, 'paramValue' => $gRec->paramValue);
            $row = cat_products_Params::recToVerbal($pRec, 'paramId,paramValue');
            $res[$row->paramId] = $row->paramValue;
        }

        return $res;
    }


    /**
     * Дали артикулът има собствена стойност на параметъра - иначе се наследява от групите
     *
     * @param mixed $value - стойност на параметъра
     *
     * @return bool
     */
    public static function hasValue($value)
    {
        return isset($value) && $value !== false && $value !== '';
    }


    /**
     * Наследените от групите параметри
     * При повтарящ се параметър печели по-дълбоката група, а при еднаква дълбочина
     * и различни стойности параметърът се пропуска
     *
     * @param array $groupIds - ид-та на групи
     *
     * @return array $res     - записите, с ключ ид на параметъра
     */
    public static function getParamRecsByGroups($groupIds)
    {
        $candidates = array();
        foreach ($groupIds as $groupId) {
            foreach (static::getEffective($groupId) as $paramId => $rec) {
                $candidates[$paramId][$rec->depth][$rec->id] = $rec;
            }
        }

        $res = array();
        foreach ($candidates as $paramId => $byDepth) {
            krsort($byDepth);
            $recs = reset($byDepth);
            if (countR(arr::extractValuesFromArray($recs, 'paramValue')) == 1) {
                $res[$paramId] = reset($recs);
            }
        }

        return $res;
    }


    /**
     * Подготвя ред за наследен параметър - в синьо, с хинт от коя група идва
     *
     * @param stdClass   $rec         - запис на параметър на група
     * @param array|null $overrideUrl - адрес за предефиниране на стойността
     *
     * @return stdClass $row
     */
    public function prepareInheritedRow($rec, $overrideUrl = null)
    {
        $row = $this->prepareParamRow($rec);
        $row->_inheritedHint = '|Наследено от|*: ' . cat_Groups::getHyperlink($rec->groupId, true);

        // Наследеният параметър не се редактира, а само се предефинира
        $row->_rowTools = null;
        core_RowToolbar::createIfNotExists($row->_rowTools);
        if (isset($overrideUrl)) {
            $row->_rowTools->addLink('Предефиниране', $overrideUrl, 'ef_icon=img/16/edit.png,title=Предефиниране на стойността');
        }

        return $row;
    }


    /**
     * След запис - кешовете и индексът на артикулите от групата
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        static::onGroupParamsChanged(array($rec->groupId => array($rec->paramId => $rec->paramId)));
    }


    /**
     * След изтриване - кешовете и индексът на артикулите от групата
     */
    public static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        $changed = array();
        foreach ($query->getDeletedRecs() as $rec) {
            $changed[$rec->groupId][$rec->paramId] = $rec->paramId;
        }
        static::onGroupParamsChanged($changed);
    }


    /**
     * Обновява кешовете и маркира за индексиране артикулите от групите, ако параметърът е филтрируем
     *
     * @param array $changed - ид-та на параметри, с ключ ид на група
     *
     * @return void
     */
    protected static function onGroupParamsChanged($changed)
    {
        static::resetCache();

        // Параметрите може да се ползват във формули на рецепти
        cat_Boms::clearProductParamsCache();

        $filterable = cat_products_ParamIndex::getFilterableParams();
        $groupIds = array();
        foreach ($changed as $groupId => $paramIds) {
            if (countR(array_intersect_key($paramIds, $filterable))) {
                $groupIds[$groupId] = $groupId;
            }
        }
        cat_products_ParamIndexState::markByGroups($groupIds);

        // Транспортът в черновите се смята от транспортното тегло и обем
        $transportIds = static::getTransportParamIds();
        $transportGroupIds = array();
        foreach ($changed as $groupId => $paramIds) {
            if (countR(array_intersect_key($paramIds, $transportIds))) {
                $transportGroupIds[$groupId] = $groupId;
            }
        }
        static::recalcTransportByGroups($transportGroupIds);
    }


    /**
     * Ид-та на параметрите, от които се смята транспортът
     *
     * @return array - ид-та на параметрите, с ключ ид
     */
    public static function getTransportParamIds()
    {
        $res = array();
        foreach (array('transportWeight', 'transportVolume') as $sysId) {
            $paramId = cat_Params::fetchIdBySysId($sysId);
            if (!empty($paramId)) {
                $res[$paramId] = $paramId;
            }
        }

        return $res;
    }


    /**
     * Групите, които имат собствено транспортно тегло или обем
     *
     * @return array - ид-та на групи, с ключ ид
     */
    public static function getTransportGroupIds()
    {
        $transportIds = static::getTransportParamIds();
        $res = array();
        foreach (static::getMap()['recs'] as $groupId => $recs) {
            if (countR(array_intersect_key($recs, $transportIds))) {
                $res[$groupId] = $groupId;
            }
        }

        return $res;
    }


    /**
     * Преизчислява транспорта в черновите на офертите и продажбите с артикули от групите и подгрупите им
     * При много артикули преизчислението е на заден план
     *
     * @param array $groupIds - ид-та на групи
     *
     * @return void
     */
    public static function recalcTransportByGroups($groupIds)
    {
        $productIds = static::getDraftProductIds($groupIds);
        if (!countR($productIds)) return;

        if (countR($productIds) > static::$maxTransportRecalcNow) {
            core_CallOnTime::setCall(get_called_class(), 'recalcTransport', array_values($productIds), dt::addSecs(10));

            return;
        }
        static::callback_recalcTransport($productIds);
    }


    /**
     * Артикулите от групите и подгрупите им, които са в чернови оферти и продажби
     *
     * @param array $groupIds - ид-та на групи
     *
     * @return array $productIds - ид-та на артикули, с ключ ид
     */
    public static function getDraftProductIds($groupIds)
    {
        $productIds = array();
        $conds = array();
        foreach ($groupIds as $groupId) {
            $groupId = (int) $groupId;
            if ($groupId > 0) {
                $conds[] = "#productGroups LIKE '%|{$groupId}|%'";
            }
        }
        if (!countR($conds)) return $productIds;

        foreach (array('sales_QuotationsDetails', 'sales_SalesDetails') as $detail) {
            $Detail = cls::get($detail);
            $query = $Detail->getQuery();
            $query->EXT('state', $Detail->Master->className, "externalName=state,externalKey={$Detail->masterKey}");
            $query->EXT('productGroups', 'cat_Products', 'externalName=groups,externalKey=productId');
            $query->where("#state = 'draft'");
            $query->where('(' . implode(' OR ', $conds) . ')');
            $query->show('productId');
            $query->groupBy('productId');
            while ($rec = $query->fetch()) {
                $productIds[$rec->productId] = $rec->productId;
            }
        }

        return $productIds;
    }


    /**
     * Преизчислява транспорта в черновите на артикулите
     *
     * @param array $productIds - ид-та на артикули
     *
     * @return void
     */
    public static function callback_recalcTransport($productIds)
    {
        foreach ($productIds as $productId) {
            sales_TransportValues::recalcTransportByProductId((int) $productId);
        }
    }


    /**
     * Подготвя параметрите за сингъла на групата
     *
     * @param stdClass $groupRec - запис на група
     *
     * @return stdClass $data
     */
    public static function prepareGroupParams($groupRec)
    {
        $data = new stdClass();
        $data->masterId = $groupRec->id;
        $data->params = array();

        $me = cls::get(get_called_class());
        $canAdd = static::haveRightFor('add', (object) array('groupId' => $groupRec->id));
        foreach (static::getParamRecs($groupRec->id) as $rec) {
            if ($rec->groupId == $groupRec->id) {
                $data->params[$rec->id] = $me->prepareParamRow($rec);
            } else {
                $overrideRec = (object) array('groupId' => $groupRec->id, 'paramId' => $rec->paramId);
                $overrideUrl = static::haveRightFor('add', $overrideRec) ? array(get_called_class(), 'add', 'groupId' => $groupRec->id, 'paramId' => $rec->paramId, 'ret_url' => true) : null;
                $data->params[$rec->id] = $me->prepareInheritedRow($rec, $overrideUrl);
            }
        }

        if ($canAdd) {
            $data->addUrl = array(get_called_class(), 'add', 'groupId' => $groupRec->id, 'ret_url' => true);
        }

        return $data;
    }
}
