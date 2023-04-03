<?php


/**
 * Мениджър на групи с артикули.
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_Groups extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Групи на артикулите';


    /**
     * Страница от менюто
     */
    public $pageMenu = 'Каталог';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cat_Wrapper, plg_Search, plg_TreeObject, core_UserTranslatePlg';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Наименование,productCnt,orderProductBy,createdOn,createdBy';


    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    public $searchFields = 'sysId, name, productCnt';


    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Група';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'cat,ceo';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'cat,ceo';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'cat,ceo';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'cat,ceo,sales,purchase';


    /**
     * Кой може да качва файлове
     */
    public $canWrite = 'cat,ceo';


    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'cat,ceo';


    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/grouping1.png';


    /**
     * Отделния ред в листовия изглед да е отгоре
     */
    public $tableRowTpl = "[#ROW#]";


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'cat,ceo';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';


    /**
     * Клас за елемента на обграждащия <div>
     */
    public $cssClass = 'folder-cover';


    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutGroup.shtml';


    /**
     * Кое поле е за името на английски?
     */
    public $nameFieldEn = 'nameEn';


    /**
     * Детайла, на модела
     */
    public $details = 'price_Updates';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64,ci)', 'caption=Наименование->Основно, mandatory, translate=field|tr|transliterate');
        $this->FLD('nameEn', 'varchar(64,ci,nullIfEmpty)', 'caption=Наименование->Международно');
        $this->FLD('sysId', 'varchar(32)', 'caption=System Id,oldFieldName=systemId,input=none,column=none');
        $this->FLD('productCnt', 'int', 'input=none,caption=Артикули');
        $this->FLD('orderProductBy', 'enum(name=Име,code=Код)', 'caption=Сортиране по,notNull,value=name,after=parentId');
        $this->FLD('defaultOverheadCostsPercent', 'percent(min=0)', 'input=none,caption=Настройки->Режийни разходи');

        // Свойства присъщи на продуктите в групата
        $this->FLD('meta', 'set(canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        						canManifacture=Производими,generic=Генерични)', 'caption=Свойства->Списък,columns=2,input=none');

        $this->setDbUnique('sysId');
        $this->setDbIndex('parentId');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        $form->setField('parentId', 'caption=Настройки->В състава на');
        $form->setField('orderProductBy', 'caption=Настройки->Сортиране по');
        $form->setField('parentId', 'silent,removeAndRefreshForm=defaultOverheadCostsPercent');

        // На системните групи само определени полета може да се променят
        if (isset($rec->sysId)) {
            foreach (array('name', 'nameEn', 'parentId') as $fld) {
                $form->setReadOnly($fld);
            }
            foreach (array('orderProductBy', 'meta', 'makeDescendantsFeatures') as $fld) {
                $form->setField($fld, 'input=hidden');
            }
        }

        // Ако групата и бащите ѝ са от допустимите за режийни разходи да се показва полето
        $groupsWithOverheadCosts = keylist::toArray(cat_Setup::get('GROUPS_WITH_OVERHEAD_COSTS'));
        $parentsArr = cls::get('cat_Groups')->getParentsArray($rec->parentId);
        $intersectedParents = array_intersect_key($groupsWithOverheadCosts, $parentsArr);
        if (array_key_exists($rec->id, $groupsWithOverheadCosts) || countR($intersectedParents)) {
            $form->setField('defaultOverheadCostsPercent', 'input');
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {
            $condition = "#name = '[#1#]' AND #id != '{$rec->id}' AND ";
            $condition .= isset($rec->parentId) ? "#parentId = {$rec->parentId}" : ' #parentId IS NULL';

            if ($mvc->fetchField(array($condition, $rec->name))) {
                $form->setError('name,parentId', 'Вече съществува запис със същите данни');
            }

            if (isset($rec->id)) {
                $exParentId = $mvc->fetchField($rec->id, 'parentId', false);
                if ($rec->parentId != $exParentId) {

                    // Група с правило не може да бъде преместена към група без правила
                    if (price_Updates::fetch("#type = 'group' AND #objectId = {$rec->id}")) {
                        $defaultGroups = keylist::toArray(cat_Setup::get('GROUPS_WITH_PRICE_UPDATE_RULES'));
                        $parentsArr = cls::get('cat_Groups')->getParentsArray($rec->parentId);
                        $intersectedParents = array_intersect_key($defaultGroups, $parentsArr);
                        if (!array_key_exists($rec->id, $defaultGroups) && !countR($intersectedParents)) {
                            $form->setError('parentId', 'Групата има зададено правило за обновяване на себестойностти и трябва да остане в състава на група, на чиите поднива може да се задават правила за обновяване|*!');
                        }
                    }
                }
            }
        }
    }


    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->view = 'horizontal';
        //$data->listFilter->FNC('product', 'key(mvc=cat_Products, select=name, allowEmpty=TRUE)', 'caption=Продукт');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'search';
        $data->listFilter->input(null, 'silent');

        $data->query->orderBy('#name');
        if ($data->listFilter->rec->product) {
            $groupList = cat_Products::fetchField($data->listFilter->rec->product, 'groups');
            $data->query->where("'{$groupList}' LIKE CONCAT('%|', #id, '|%')");
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (cat_Products::haveRightFor('list')) {
            if ($fields['-list']) {
                $row->productCnt = ht::createLinkRef($row->productCnt, array('cat_Products', 'list', 'groupId' => $rec->id), false, "title=Филтър на|* \"{$row->name}\"");
            }

            if ($fields['-single']) {
                $productCount = (isset($rec->productCnt)) ? $rec->productCnt : 0;
                $productCountVerbal = $mvc->getFieldType('productCnt')->toVerbal($productCount);
                $row->productCnt = ht::createLink($productCountVerbal, array('cat_Products', 'list', 'groupId' => $rec->id), false, "title=Филтър на|* \"{$row->name}\"");
            }
        }

        if ($fields['-single'] && !isset($rec->defaultOverheadCostsPercent)) {

            // Ако е намерена наследена стойност
            if ($overheadCostArr = $mvc->getDefaultOverheadCostFromParent($rec)) {
                if (!empty($overheadCostArr['overheadCost'])) {
                    $row->defaultOverheadCostsPercent = $mvc->getFieldType('defaultOverheadCostsPercent')->toVerbal($overheadCostArr['overheadCost']);
                    $row->defaultOverheadCostsPercent = "<span style='color:blue'>{$row->defaultOverheadCostsPercent}</span>";
                    $hint = "Наследено от|*: " . $mvc->getVerbal($overheadCostArr['groupId'], 'name');
                    $row->defaultOverheadCostsPercent = ht::createHint($row->defaultOverheadCostsPercent, $hint, 'notice', false);
                }
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Ако групата е системна или в нея има нещо записано - не позволяваме да я изтриваме
        if ($action == 'delete' && ($rec->sysId || $rec->productCnt)) {
            $requiredRoles = 'no_one';
        }
    }


    /**
     * Преди импорт на записи
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
        // Ако е зададен баща опитваме се да го намерим
        if (isset($rec->csv_parentId)) {
            if ($parentId = $mvc->fetchField(array("#name = '[#1#]'", $rec->csv_parentId), 'id')) {
                $rec->parentId = $parentId;
            }
        }
        $rec->productCnt = 0;
    }


    /**
     * След обновяване на модела
     */
    protected static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'cat/csv/Groups.csv';
        $fields = array(
            0 => 'name',
            1 => 'sysId',
            2 => 'csv_parentId',
            3 => 'nameEn',
        );

        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        $res .= $cntObj->html;

        return $res;
    }


    /**
     * Връща кейлист от систем ид-та на групите
     *
     * @param mixed $sysIds - масив със систем ид-та
     * @return string
     */
    public static function getKeylistBySysIds($sysIds)
    {
        $kList = '';
        $sysIds = arr::make($sysIds);

        if (!countR($sysIds)) {

            return $kList;
        }

        foreach ($sysIds as $grId) {
            $kList = keylist::addKey($kList, self::fetchField("#sysId = '{$grId}'", 'id'));
        }

        return $kList;
    }


    /**
     * Форсира група (маркер) от каталога
     *
     * @param string $name Име на групата. Съдържа целия път
     * @param int|null $parentId Id на родител
     * @param bool $force
     *
     * @return int|NULL id на групата
     */
    public static function forceGroup($name, $parentId = null, $force = true)
    {
        static $groups = array();

        $parentIdNumb = (int)$parentId;

        if (!($res = $groups[$parentIdNumb][$name])) {
            if (strpos($name, '»')) {
                $gArr = explode('»', $name);
                foreach ($gArr as $gName) {
                    $gName = trim($gName);
                    $parentId = self::forceGroup($gName, $parentId, $force);
                }

                $res = $parentId;
            } else {
                if ($parentId === null) {
                    $cond = 'AND #parentId IS NULL';
                } else {
                    expect(is_numeric($parentId), $parentId);

                    $cond = "AND #parentId = {$parentId}";
                }

                $gRec = cat_Groups::fetch(array("LOWER(#name) = LOWER('[#1#]'){$cond}", $name));

                if (isset($gRec->name)) {
                    $res = $gRec->id;
                } else {
                    if ($force) {
                        $gRec = (object)array('name' => $name, 'orderProductBy' => 'code', 'meta' => 'canSell,canBuy,canStore,canConvert,canManifacture', 'parentId' => $parentId);

                        cat_Groups::save($gRec);

                        $res = $gRec->id;
                    } else {
                        $res = null;
                    }
                }
            }

            $groups[$parentIdNumb][$name] = $res;
        }

        return $res;
    }


    /**
     * Връщане на списъка от групи като линк
     *
     * @param string $keylist - списък от групи
     * @param string $class - клас на линковете
     *
     * @return array $res     - масив от линкове
     */
    public static function getLinks($keylist, $class = 'group-link')
    {
        $res = array();
        $groups = (is_array($keylist)) ? $keylist : keylist::toArray($keylist);
        if (!countR($groups)) {

            return $res;
        }

        $makeLink = (cat_Products::haveRightFor('list') && !Mode::isReadOnly());
        foreach ($groups as $grId) {
            $groupTitle = self::getVerbal($grId, 'name');
            if ($makeLink === true) {
                $listUrl = array('cat_Products', 'list', 'groupId' => $grId);
                $classAttr = "class={$class}";
                $groupLink = ht::createLink($groupTitle, $listUrl, false, "{$classAttr},title=Филтриране на артикули по група|* '{$groupTitle}'");
                $groupTitle = $groupLink->getContent();
            }

            $res[] = $groupTitle;
        }

        return $res;
    }


    /**
     * Има ли в подадените групи, такива които са наследници на друга група от списъка
     *
     * @param mixed $groupList - масив или списък от групи
     *
     * @return bool
     */
    public static function checkForNestedGroups($groupList)
    {
        $groups = (is_array($groupList)) ? $groupList : keylist::toArray($groupList);
        if (!countR($groups)) {

            return false;
        }

        $notAllowed = array();
        foreach ($groups as $grId) {
            if (array_key_exists($grId, $notAllowed)) {

                return true;
            }

            // Иначе добавяме него и наследниците му към недопустимите групи
            $descendant = cat_Groups::getDescendantArray($grId);
            $notAllowed += $descendant;
        }

        return false;
    }


    /**
     * Обновяване броя артикули в група
     *
     * @param mixed $ids - ид-та на конкретни групи, null за всички
     * @return void
     */
    public static function updateGroupsCnt($ids = null)
    {
        // Ако има групи обръщат се в масив
        $groupArr = arr::make($ids, true);
        $groupArr = countR($groupArr) ? $groupArr : null;

        // Преброяване колко артикули са във всяка група
        $pQuery = cat_Products::getQuery();
        $gCntArr = $pQuery->countKeylist('groups', $groupArr);

        // Ще се обновява броя артикули в група, само ако има промяна
        $updateGroups = array();
        $query = cat_Groups::getQuery();
        if (countR($groupArr)) {
            $query->in('id', $groupArr);
        }

        while ($rec = $query->fetch()) {
            if ($gCntArr[$rec->id] != $rec->productCnt) {
                $rec->productCnt = $gCntArr[$rec->id];
                if (empty($rec->productCnt)) {
                    $rec->productCnt = 0;
                }
                $updateGroups[$rec->id] = $rec;
            }
        }

        // Обновяване на групите с промяна
        if (countR($updateGroups)) {
            cls::get('cat_Groups')->saveArray($updateGroups, 'id,productCnt');
        }
    }


    /**
     * Обновява броячите на групите по cron
     */
    public function cron_UpdateGroupsCnt()
    {
        self::updateGroupsCnt();
    }


    /**
     * Връща дефолтните режийни разходи според групите на артикула
     *
     * @param int $productId - ид на артикул
     * @return array|null    - информация за най-големия процент реж. разходи от групите
     *         * ['value'] double
     *         * ['groupId'] varchar
     */
    public static function getDefaultOverheadCostsByProductId($productId)
    {
        // Кои са въведените групи артикули
        $me = cls::get(get_called_class());
        $groupsInput = cat_Products::fetchField($productId, 'groupsInput');
        $productGroups = keylist::toArray($groupsInput);

        // Кои са дефолтния групи с режийни разходи
        $groupsToCheck = array();
        $groupsWithOverheadCosts = keylist::toArray(cat_Setup::get('GROUPS_WITH_OVERHEAD_COSTS'));
        foreach ($productGroups as $groupId) {

            // За всяка от ръчно въведените групи на артикула, ако някой от бащите ѝ е в избраните групи
            $parents = $me->getParentsArray($groupId);
            $intersected = array_intersect_key($groupsWithOverheadCosts, $parents);

            if (countR($intersected)) {

                // Ако в самата група има ръчно въведен процент - взима се той
                $groupRec = static::fetch("#id = {$groupId}", "id,parentId,defaultOverheadCostsPercent");
                if (isset($groupRec->defaultOverheadCostsPercent)) {
                    $groupsToCheck[$groupRec->id] = $groupRec->defaultOverheadCostsPercent;
                } else {
                    if ($overheadCostArr = $me->getDefaultOverheadCostFromParent($groupRec)) {
                        $groupsToCheck[$groupRec->id] = $overheadCostArr['overheadCost'];
                    }
                }
            }
        }

        // Ако има намерени - в;ръща се най-големия процент
        arsort($groupsToCheck);
        if (countR($groupsToCheck)) return array('groupId' => key($groupsToCheck), 'value' => $groupsToCheck[key($groupsToCheck)]);

        return null;
    }


    /**
     * Колко са очакваните режийни разходи от башата
     *
     * @param stdClass $rec
     * @return array|false
     *          ['groupId']      - от коя грипа е наследено
     *          ['overheadCost'] - процент наследени разходи
     */
    private function getDefaultOverheadCostFromParent($rec)
    {
        // Ако няма се търси в неговите бащи докато се намери процент
        $parent = $rec->parentId;
        $groupsWithOverheadCosts = keylist::toArray(cat_Setup::get('GROUPS_WITH_OVERHEAD_COSTS'));
        while ($parent && ($pRec = static::fetch("#id = {$parent}", "id,parentId,defaultOverheadCostsPercent"))) {
            if (!empty($pRec->defaultOverheadCostsPercent)) {
                $parentsArr = cls::get('cat_Groups')->getParentsArray($pRec->parentId);
                $intersectedParents = array_intersect_key($groupsWithOverheadCosts, $parentsArr);

                // Ако е намерен процент, все пак се проверява дали текущия баща или бащите му са в посочените
                if (array_key_exists($pRec->id, $groupsWithOverheadCosts) || countR($intersectedParents)) {
                    return array('groupId' => $rec->id, 'overheadCost' => $pRec->defaultOverheadCostsPercent);
                }
            }
            $parent = $pRec->parentId;
        }

        return false;
    }

    function act_Test()
    {
        if (!haveRole('admin')) {
            return "Недостатъчни права";
        }

        $gRecNO = cat_Groups::fetch("#name = 'Пликове за e-Commers с изрязани дръжки' AND #productCnt != 0");
        $gRecYES = cat_Groups::fetch("#name = 'Пликове за e-Commerce с изрязани дръжки'");

        if (!$gRecNO) {
            return 'Липсва Пликове за e-Commers с изрязани дръжки';
        }

        if (!$gRecYES) {
            return 'Пликове за e-Commerce с изрязани дръжки';
        }

        $q = cat_Products::getQuery();
        $q->where("#isPublic = 'no'");
        $q->like('groups', "|{$gRecNO->id}|");
        $q->show('id,name,groups,groupsInput');

        while ($pRec = $q->fetch()) {

            $sGrArr = keylist::toArray($pRec->groups);
            $sGrInputArr = keylist::toArray($pRec->groupsInput);
            unset($sGrArr[$gRecNO->id]);
            unset($sGrInputArr[$gRecNO->id]);
            if (!in_array($gRecYES->id, $sGrArr)) {
                $sGrArr[$gRecYES->id] = $gRecYES->id;
            }
            if (!in_array($gRecYES->id, $sGrInputArr)) {
                $sGrInputArr[$gRecYES->id] = $gRecYES->id;
            }

            $pRec->groups = type_Keylist::fromArray($sGrArr);
            $pRec->groupsInput = type_Keylist::fromArray($sGrInputArr);
            cls::get('cat_Products')->save_($pRec, 'groups,groupsInput');

        }

        return 'Изпразване на групата Пликове за e-Commers с изрязани дръжки';

        if (!$grRecOld = cat_Groups::fetch("#name = '03. Куриерски пликове' AND #productCnt != 0")) {
            return "Липсва стара група";
        }
        if ($grRecOld->productCnt == 0) {
            return "Липсват артикули в стара група";
        }

        $grRecNew = cat_Groups::fetch("#name = '03. Куриерски и онлайн пликове'");
        if (!$grRecNew && $grRecOld) {

            $grNewId = cat_Groups::forceGroup('03. Куриерски и онлайн пликове', $parentId = $grRecOld->parentId, $force = true);
            $grRecNew = cat_Groups::fetch($grNewId);

        }


        $q = cat_Products::getQuery();
        $q->where("#isPublic = 'no'");
        $q->like('groups', "|{$grRecOld->id}|");
        $q->show('id,name,groups,groupsInput');



        // bp($q->fetchAll(),$grRecOld,$grRecNew,$q->count());

        $logArr = array();

        while ($pRec = $q->fetch()) {

            $groupsArr = keylist::toArray($pRec->groups);
            $groupsInputArr = keylist::toArray($pRec->groupsInput);

            //Ако артикула го има в сарата и в новата група
            if (key_exists($grRecNew->id, $groupsArr)) {
                //Премахваме всички групи чийто баща е старата група от grous и groupsInput
                foreach ($groupsArr as $gr) {

                    if (cat_Groups::fetch($gr)->parentId == $grRecOld->id) {

                        unset($groupsArr[$gr]);
                        unset($groupsInputArr[$gr]);

                    }
                }

                //От groups премахваме старата група и остава само новата
                unset($groupsArr[$grRecOld->id]);

            } else {

                //Ако е само в старата група
                // В groupsInput трябва да останат само тези групи чиито баща е Новата група
                foreach ($groupsInputArr as $gr) {

                    //Проверяваме, дали вече има създадена група със същото име
                    $nameForCheck = cat_Groups::fetch($gr)->name;
                    $queryGr = cat_Groups::getQuery();
                    $queryGr->where("#name = '$nameForCheck' AND #id != '$gr' AND #parentId = $grRecNew->id");

                    if ($queryGr->count() > 1) {
                        return "Има повече от една група 03. Куриерски и онлайн пликове>>Куриерски пликове ";
                    }

                    if ($queryGr->count() > 0) {
                        $newInputGrId = $queryGr->fetch()->id;

                    } else {
                        $newInputGrId = null;
                    }

                    $recGr = cat_Groups::fetch($gr);

                    //Ако в groupsInput  има група , чийто баща е старата група и има вече създадена
                    //такава група със същото име
                    //Изтривам старата и вкарвам новата със същото име ако съществува такава
                    if ($recGr->parentId == $grRecOld->id) {

                        if ($newInputGrId) {
                            unset($groupsInputArr[$gr]);
                            unset($groupsArr[$gr]);
                            $groupsInputArr[$newInputGrId] = $newInputGrId;
                            $groupsArr[$newInputGrId] = $newInputGrId;

                        } else {

                            //Ако не съществува на старата само и сменяме parentId-то
                            $recGr->parentId = $grRecNew->id;

                            cls::get('cat_Groups')->save_($recGr, 'parentId');
                        }

                    }

                }

                unset($groupsArr[$grRecOld->id]);
                $groupsArr[$grRecNew->id] = $grRecNew->id;

            }

            $logArr[$pRec->id] = $pRec->name;
            $pRec->groups = type_Keylist::fromArray($groupsArr);
            $pRec->groupsInput = type_Keylist::fromArray($groupsInputArr);

            cls::get('cat_Products')->save_($pRec, 'groups,groupsInput');
        }

        if (!empty($logArr)) {
            wp('Артикули с коригирани групи', $logArr);
        }

        if ($grRecOld->id) {
            $queryGr = cat_Groups::getQuery();
            $queryGr->delete("#productCnt = 0 AND #parentId = $grRecOld->id");
        }


    }

}
