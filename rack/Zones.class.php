<?php


/**
 * Модел за "Зони"
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Zones extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Зони';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Зона';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'rack_Wrapper,plg_Sorting,plg_Created,plg_State2,plg_RowTools2,plg_RefreshRows,plg_Printing,plg_SaveAndNew';


    /**
     * Кой може да добавя?
     */
    public $canAdd = 'ceo,rackMaster';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo,rackMaster';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,rack';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,rackMaster';


    /**
     * Кой може да генерира нагласяния?
     */
    public $canOrderpickup = 'ceo,rack';


    /**
     * Работен кеш
     */
    protected static $movementCache = array();


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rackSee';


    /**
     * Полета в листовия изглед
     */
    public $listFields = 'num=Зона,containerId,defaultUserId,readiness,folderId=Папка,lineId=Линия,pendingHtml=@';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     * @var string
     */
    public $hideListFieldsIfEmpty = 'pendingHtml,folderId,lineId';


    /**
     * Детайла, на модела
     */
    public $details = 'rack_ZoneDetails';


    /**
     * Кой може да селектира документа
     */
    public $canSelectdocument = 'ceo,rackZoneSelect';


    /**
     * Кой може ръчно да премахва документ от зона
     */
    public $canManualclearzone = 'ceo,rackZoneSelect';


    /**
     * Кой може да променя състоянието
     */
    public $canChangestate = 'ceo,rackMaster';


    /**
     * Кой може да премахва докумнета от зоната
     */
    public $canRemovedocument = 'ceo,rack';


    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'rack/tpl/SingleLayoutZone.shtml';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'num';


    /**
     * На колко време да се рефрешва лист изгледа
     */
    public $refreshRowsTime = 7000;


    /**
     * Шаблон за реда в листовия изглед
     */
    public $tableRowTpl = "[#ROW#][#ADD_ROWS#]\n";


    /**
     * Кои линии да се обновят на шътдаун
     */
    protected $syncLinesOnShutdown = array();


    /**
     * Работен кеш
     */
    protected static $cache = array();


    /**
     * Работен кеш
     */
    protected static $cachedRacksByGroup = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('num', 'int(max=99999)', 'caption=Номер,mandatory,focus');
        $this->FLD('color', 'color_Type', 'caption=Цвят,remember');
        $this->FLD('description', 'text(rows=2)', 'caption=Описание');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,mandatory,remember,input=hidden');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Документ,input=none');
        $this->FLD('defaultUserId', 'key(mvc=core_Users,select=nick)', 'caption=Изпълнител,input=none');
        $this->FLD('readiness', 'percent', 'caption=Готовност,input=none');
        $this->FLD('groupId', 'key(mvc=rack_ZoneGroups,select=name,allowEmpty)', 'caption=Група,placeholder=Без групиране,remember');

        $this->setDbUnique('num,storeId');
        $this->setDbIndex('storeId');
        $this->setDbIndex('containerId');
    }


    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
        if ($part == 'num') {
            $num = "Z-{$rec->num}";
        } elseif($part == 'readiness'){
            if(empty($rec->readiness)){
                $num = core_Type::getByName('percent')->toVerbal(0);
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $isTerminal = Request::get('terminal', 'int');
        $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        if (isset($rec->containerId)) {
            $Document = doc_Containers::getDocument($rec->containerId);
            if($isTerminal){
                $singleUrl = $Document->getSingleUrlArray();
                $row->containerId = ht::createLink("#{$Document->abbr}{$Document->that}", $singleUrl);
            } else {
                if(!Mode::is('printing')) {
                    $row->containerId = $Document->getLink(0);
                } else {
                    $row->containerId = $Document->getHandle();
                }
            }

            if(!Mode::is('printing')){
                $row->containerId = "<span class='document-handler state-{$Document->fetchField('state')}'>{$row->containerId}</span>";
            }
        }

        if($isTerminal) {
            $row->ROW_ATTR['class'] = $row->ROW_ATTR['class'] . " rack-zone-head";
        }

        if (isset($rec->containerId)) {
            $document = doc_Containers::getDocument($rec->containerId);
            $documentRec = $document->fetch();
            if($isTerminal) {
                $row->folderId = doc_Folders::getTitleById($documentRec->folderId);
            } else {
                $row->folderId = doc_Folders::getFolderTitle($documentRec->folderId);
            }

            if (isset($documentRec->{$document->lineFieldName})) {
                $lineAttr = array();
                if($isTerminal) {
                    $lineAttr = array('ef_icon' => false);
                }
                $lineState = trans_Lines::fetchField($documentRec->{$document->lineFieldName}, 'state');
                $row->lineId = trans_Lines::getLink($documentRec->{$document->lineFieldName}, 0, $lineAttr);
                $row->lineId = "<span class='document-handler state-{$lineState}'>{$row->lineId}</span>";
            }
        }

        $row->readiness = "<div class='block-readiness'>{$row->readiness}</div>";
        $row->num = $mvc->getHyperlink($rec->id);
        if (isset($fields['-list'])) {
            $rec->_isSingle = false;

            if($isTerminal) {
                $additional = Request::get('additional', 'varchar');
                $pendingHtml = rack_ZoneDetails::renderInlineDetail($rec, $mvc, $additional);
                if (!empty($pendingHtml)) {
                    $row->pendingHtml = $pendingHtml;
                }
            }

            if ($mvc->haveRightFor('removedocument', $rec->id)) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Премахване', array($mvc, 'removeDocument', $rec->id, 'ret_url' => true), "id=remove{$rec->id},ef_icon=img/16/gray-close.png,title=Премахване на документа от зоната,warning=Наистина ли искате да премахнете документа и свързаните движения|*?");
            }

            $id = self::getRecTitle($rec); 
            $terminalLink = ($isTerminal) ? 'single' : 'terminal';
            $num = rack_Zones::getDisplayZone($rec->id, true, $terminalLink);             
            $row->num = ht::createElement("div", array('id' => $id), $num, true);
        }

        if (isset($fields['-single'])) {
            $row->num = rack_Zones::getRecTitle($rec->id);
            if(empty($rec->color)){
                $row->color = $mvc->getFieldType('color')->toVerbal(rack_Setup::get('DEFAULT_ZONE_COLORS'));
            }
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('removedocument', $data->rec->id)) {
            $data->toolbar->addBtn('Премахване', array($mvc, 'removeDocument', $data->rec->id, 'ret_url' => true), 'ef_icon=img/16/gray-close.png,title=Премахване на документа от зоната,warning=Наистина ли искате да премахнете документа и свързаните движения|*?');
        }
    }


    /**
     * Връща зоните към подадения склад
     *
     * @param int|NULL $storeId - ид на склад
     * @param boolean $onlyFree - само наличното или цялото количество
     *
     * @param boolean|NULL $groupable - Дали да се групират, null за всички
     * @param boolean|NULL $groupId - конкретна група, по която да се филтрират
     *
     * @return array $options
     */
    public static function getZones($storeId = null, $onlyFree = false, $groupable = null, $groupId = null)
    {
        $options = array();
        $query = self::getQuery();
        $query->where("#state != 'closed'");
        if ($onlyFree === true) {
            $query->where('#containerId IS NULL');
        }
        if (isset($storeId)) {
            $query->where("#storeId = {$storeId}");
        }

        if (isset($groupable)) {
            expect(is_bool($groupable));
            if (isset($groupId)) {
                $query->where("#groupId = {$groupId}");
            } else {
                $query->where("#groupId IS NULL");
            }
        }
        $query->orderBy('num', 'ASC');

        while ($rec = $query->fetch()) {
            $options[$rec->id] = self::getRecTitle($rec, false);
        }

        return $options;
    }


    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = self::fetchRec($rec);
        $num = self::getVerbal($rec, 'num');
        $groupName = (is_null($rec->groupId)) ? tr('Без група') : rack_ZoneGroups::getVerbal($rec->groupId, 'name');
        $title = "{$num} ({$groupName})";

        if ($escaped) {
            $title = type_Varchar::escape($title);
        }

        return $title;
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        $form->setDefault('storeId', store_Stores::getCurrent('id', $rec ? $rec->storeId : null));

        // Ако има работен запис към зоната не може да се сменя склада
        if (isset($rec->containerId)) {
            $form->setReadOnly('storeId');
        }

        $form->setDefault('num', $mvc->getNextNumber($rec->storeId));
    }


    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        if($data->isTerminal) {
            $tpl->push('rack/css/style.css', 'CSS');
            $tpl->push('rack/js/ZoneScripts.js', 'JS');
            jquery_Jquery::run($tpl, 'zoneActions();');
        }
    }


    /**
     * След рендиране на singyla
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if($data->isTerminal) {
            $tpl->push('rack/css/style.css', 'CSS');
        }
    }


    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        // По-хубаво заглавие на формата
        $rec = $data->form->rec;
        $data->form->title = core_Detail::getEditTitle('store_Stores', $rec->storeId, 'зона', $rec->id, tr('в склад'));
    }


    /**
     * Добавя филтър към перата
     *
     * @param acc_Items $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->isTerminal = Request::get('terminal', 'int');
        if($data->isTerminal){
            $mvc->currentTab = 'Зони->Терминал';
            unset($data->listFields['lineId']);
            unset($data->listFields['defaultUserId']);
            arr::placeInAssocArray($data->listFields, array('lineId' => 'Линия'), 'readiness');
        } else {
            $mvc->currentTab = 'Зони->Списък';
        }

        $storeId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$storeId}");
        $data->title = 'Зони в склад|* <b style="color:green">' . store_Stores::getHyperlink($storeId, true) . '</b>';
        $data->query->orderBy('num', 'asc');

        // Добавяне на филтър по артикулите
        $data->listFilter->FLD('productId', "key2(mvc=cat_Products,storeId={$storeId},select=name,allowEmpty,selectSource=rack_Zones::getProductsInZones)", 'caption=Артикул,autoFilter,silent');
        $data->listFilter->FNC('terminal', "int", 'caption=Артикул,silent,input=hidden');
        if($data->isTerminal) {
            $data->listFilter->FLD('additional', 'enum(onlyMine=Моите,pendingAndMine=Свободни+Мои,pending=Свободни,yes=С движения,all=Всички)', 'autoFilter,silent');
        }
        $data->listFilter->FLD('grouping', "varchar", 'caption=Всички,autoFilter,silent');
        $groupingOptions = array('' => '', 'no' => tr('Без групиране'), 'free' => tr('Свободни'), 'notfree' => tr('С документи'));

        // Добавяне на групите, както и самостоятелните зони
        $gQuery = rack_ZoneGroups::getQuery();
        while ($gRec = $gQuery->fetch()) {
            $groupingOptions[$gRec->id] = $gRec->name;
        }
        $singleZones = rack_Zones::getZones($storeId, false, false);
        foreach ($singleZones as $z1 => $zoneName) {
            $groupingOptions["s{$z1}"] = $zoneName;
        }

        $data->listFilter->setOptions('grouping', $groupingOptions);
        if($data->isTerminal) {
            $data->listFilter->setDefault('additional', 'pendingAndMine');
        }

        $data->listFilter->input(null, 'silent');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        if($data->isTerminal) {
            $data->listFilter->showFields = 'productId,grouping,additional';
            $data->listFilter->input('productId,grouping,terminal,additional');
        } else {
            $data->listFilter->showFields = 'productId,grouping';
            $data->listFilter->input('productId,grouping,terminal');
        }
        $data->listFilter->view = 'horizontal';

        // Ако се филтрира по артикул
        if ($filter = $data->listFilter->rec) {
            if (isset($filter->productId)) {

                // Оставят се само тези зони където се среща артикула
                $dQuery = rack_ZoneDetails::getQuery();
                $dQuery->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
                $dQuery->where("#productId={$filter->productId} AND #storeId = {$storeId}");

                $zoneIdsWithProduct = arr::extractValuesFromArray($dQuery->fetchAll(), 'zoneId');
                if (countR($zoneIdsWithProduct)) {
                    $data->query->in('id', $zoneIdsWithProduct);
                } else {
                    $data->query->where("1=2");
                }
            }

            if (!empty($filter->grouping)) {
                switch ($filter->grouping) {
                    case 'no':
                        $data->query->where("#groupId IS NULL");
                        break;
                    case is_numeric($filter->grouping):
                        $data->query->where("#groupId = {$filter->grouping}");
                        break;
                    case strpos($filter->grouping, 's'):
                        $id = trim($filter->grouping, 's');
                        $data->query->where("#id = {$id}");
                        break;
                    case 'notfree':
                        $data->query->where("#containerId IS NOT NULL");
                        break;
                    case 'free':
                        $data->query->where("#containerId IS NULL");
                        break;
                }
            }

            if ($data->isTerminal && $filter->additional != 'all') {

                // Ако е избран филтър само за зони с движения да се показват те
                $mQuery = rack_Movements::getQuery();
                $mQuery->where("#storeId = {$storeId} AND #zoneList != '' AND #state != 'closed'");
                if ($filter->additional == 'pendingAndMine') {
                    $mQuery->where("#workerId IS NULL OR #state = 'pending' OR #workerId=" . core_Users::getCurrent());
                }elseif ($filter->additional == 'pending') {
                    $mQuery->where("#workerId IS NULL OR #state = 'pending'");
                }elseif ($filter->additional == 'onlyMine') {
                    $mQuery->where("#workerId = " . core_Users::getCurrent());
                }

                $zonesWithMovements = array();
                $zoneKeylistsArr = arr::extractValuesFromArray($mQuery->fetchAll(), 'zoneList');
                foreach ($zoneKeylistsArr as $zKeylist) {
                    $zonesWithMovements += keylist::toArray($zKeylist);
                }
                if (countR($zonesWithMovements)) {
                    $data->query->in("id", $zonesWithMovements);
                } else {
                    $data->query->where("1=2");
                }
            }
        }
    }


    /**
     * Филтър по артикулите в зоните
     */
    public static function getProductsInZones($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
        $dQuery->EXT('searchKeywords', 'cat_Products', 'externalName=searchKeywords,externalKey=productId');
        $dQuery->where("#storeId = {$params['storeId']}");
        $dQuery->groupBy('productId');

        if (is_array($onlyIds)) {
            if (!countR($onlyIds)) return array();

            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);

            $dQuery->where("#productId IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $dQuery->where("#productId = ${onlyIds}");
        }

        if ($q) {
            if ($q[0] == '"') {
                $strict = true;
            }
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            $q = mb_strtolower($q);

            if ($strict) {
                $qArr = array(str_replace(' ', '.*', $q));
            } else {
                $qArr = explode(' ', $q);
            }

            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $dQuery->where(array("#searchKeywords REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }

        if ($limit) {
            $dQuery->limit($limit);
        }

        $dQuery->show('productId');

        $res = array();
        while ($rec = $dQuery->fetch()) {
            $res[$rec->productId] = cat_Products::getTitleById($rec->productId, false);
        }

        return $res;
    }


    /**
     * Избор на зона в документ
     *
     * @return void|core_ET
     */
    public function act_Selectdocument()
    {
        // Проверка на права
        $this->requireRightFor('selectdocument');
        expect($containerId = Request::get('containerId', 'int'));
        expect($document = doc_Containers::getDocument($containerId));
        $documentRec = $document->fetch();
        $this->requireRightFor('selectdocument', (object)array('containerId' => $containerId, 'storeId' => $documentRec->{$document->storeFieldName}));
        $storeId = $documentRec->{$document->storeFieldName};

        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Събиране на редовете на|* ' . $document->getFormTitleLink();
        $form->info = tr('Склад|*: ') . store_Stores::getHyperlink($storeId, true);
        $form->FLD('zoneId', 'key(mvc=rack_Zones,select=name)', 'caption=Зона');
        $form->FLD('defaultUserId', 'user(roles=rack|ceo, allowEmpty)', 'caption=Изпълнител,placeholder=Няма');
        $zoneOptions = rack_Zones::getZones($storeId, true);
        $zoneRec = rack_Zones::fetch("#containerId = {$containerId}");

        if (!empty($zoneRec->id) && !array_key_exists($zoneRec->id, $zoneOptions)) {
            $zoneOptions[$zoneRec->id] = $this->getRecTitle($zoneRec->id);
        }
        $form->setOptions('zoneId', array('' => '') + $zoneOptions);
        if($form->cmd != 'refresh'){
            $form->setDefault('zoneId', $zoneRec->id);
            $form->setDefault('zoneId', key($zoneOptions));
            $form->setDefault('defaultUserId', $zoneRec->defaultUserId);
        }
        $form->input();

        // Изпращане на формата
        if ($form->isSubmitted()) {

            $fRec = $form->rec;
            if(isset($zoneRec->id)){
                if($fRec->zoneId != $zoneRec->id){
                    if(!$this->haveRightFor('removedocument', $zoneRec->id)){
                        $form->setError('zoneId', "Нямате права да премахнете документа от Зона:|*" . rack_Zones::getRecTitle($zoneRec->id, false));
                    } elseif(rack_Movements::fetch("LOCATE('|{$zoneRec->id}|', #zoneList) AND (#state = 'waiting' OR #state = 'active')")){
                        $form->setError('zoneId', "Не може да премахнете документа от зона|* <b>" . rack_Zones::getDisplayZone($zoneRec->id) . "</b>, |защото има вече запазени или започнати движения. Документът може да бъде премахнат след отказването им|*!");
                    }
                } elseif($fRec->defaultUserId == $zoneRec->defaultUserId) {
                    $form->setError('zoneId,defaultUserId', "Зоната и изпълнителя са същите");
                } elseif(rack_Movements::fetch("LOCATE('|{$zoneRec->id}|', #zoneList) AND #state = 'waiting' AND #workerId = '{$zoneRec->defaultUserId}'")){
                    $form->setError('defaultUserId', "За да смените изпълнителя, трябва първо да откажете запазените движения от предишния");
                }
            }

            if(!$form->gotErrors()){
                $msg = null;

                // Ако е сменена зоната, документа се премахва от старата и се регенерират движенията за нея и групата
                if ($zoneRec->id != $fRec->zoneId && isset($zoneRec->id)) {
                    static::updateZone($zoneRec->id, $containerId, true);
                }

                // Ако е избрана нова зона се регенерират движенията за нея и групата ѝ
                if (isset($fRec->zoneId)) {
                    if(empty($zoneRec->id)){
                        static::updateZone($fRec->zoneId, $containerId, false, $fRec->defaultUserId);
                        $document->getInstance()->logWrite('Задаване на нова зона', $document->that);
                        $msg = 'Зоната е успешно зададена|*!';
                    } elseif($zoneRec->id != $fRec->zoneId) {
                        static::updateZone($fRec->zoneId, $containerId, false, $fRec->defaultUserId);
                        $document->getInstance()->logWrite('Промяна на зона', $document->that);
                        $msg = 'Зоната е успешно променена|*!';
                    } elseif($zoneRec->defaultUserId != $fRec->defaultUserId){
                        static::updateZone($fRec->zoneId, $containerId, false, $fRec->defaultUserId);
                        $document->getInstance()->logWrite('Промяна на дефолтен работник', $document->that);
                        $msg = 'Дефолтния работник е променен успешно|*!';
                    }

                    if(haveRole('ceo,rackSee') && store_Stores::haveRightFor('select', $zoneRec->storeId)){

                        $redirectUrl = self::getUrlArr($fRec->zoneId);
                        if(isset($fRec->defaultUserId)){
                            $redirectUrl['additional'] = 'yes';
                        }
                    } else {
                        $redirectUrl = $document->getSingleUrlArray();
                    }

                    redirect($redirectUrl, false, $msg);
                } elseif(isset($zoneRec->id)) {
                    $document->getInstance()->logWrite('Премахване от зона', $document->that);
                    $msg = 'Документът е премахнат от зоната|*!';
                }

                followRetUrl(null, $msg);
            }
        }

        // Добавяне на бутони
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/move.png, title = Запис на действието');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        // Записваме, че потребителя е разглеждал този списък
        $document->logInfo('Избор на зона');
        $tpl = $document->getInstance()->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        return $tpl;
    }


    /**
     * Обновява и регенерира информацията за зоната
     *
     * @param int $zoneId           - ид на зона
     * @param int|null $containerId - ид на контейнер
     * @param bool $remove          - да се добави или да се премахне документът от зоната
     */
    public static function updateZone($zoneId, $containerId, $remove = false, $defaultUserId = null)
    {
        // Запис на документа към зоната
        $zoneRec = static::fetch($zoneId);
        $zoneRec->containerId = ($remove) ? null : $containerId;
        $zoneRec->defaultUserId = ($remove) ? null : $defaultUserId;
        static::save($zoneRec);

        // Синхронизиране с детайла на зоната
        rack_ZoneDetails::syncWithDoc($zoneRec->id, $zoneRec->containerId);

        if($remove){

            // Ако документа се премахва от зоната, изтриват се чакащите движения към тях
            rack_Movements::delete("LOCATE('|{$zoneId}|', #zoneList) AND #state = 'pending'");
            rack_Movements::logDebug("RACK DELETE PENDING '{$zoneId}'");
        }

        // Обновяване на информацията в зоната
        cls::get(get_called_class())->updateMaster($zoneRec);

        // Ако групата е с групиране, се извличат всички зони от същата група
        $selectedZones = $zoneRec->id;
        if (isset($zoneRec->groupId)) {
            $selectedZones = self::getZones($zoneRec->storeId, false, true, $zoneRec->groupId);
            $selectedZones = arr::make(array_keys($selectedZones), true);
        }

        // Генериране нови движения след отразяване на промяната
        self::pickupOrder($zoneRec->storeId, $selectedZones, $defaultUserId);
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'selectdocument' && isset($rec)) {
            if (empty($rec->containerId) || empty($rec->storeId)) {
                $requiredRoles = 'no_one';
            } else {
                $document = doc_Containers::getDocument($rec->containerId);
                if (!rack_Zones::fetchField("#storeId = {$rec->storeId} AND #state != 'closed'")) {
                    $requiredRoles = 'no_one';
                } else {
                    $documentRec = $document->fetch("state,{$document->storeFieldName}");
                    if (!$document->haveRightFor('single') || !in_array($documentRec->state, array('draft', 'pending'))) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }

        // Ако в зоната има редове или е използвана в движение, не може да се изтрива
        if ($action == 'delete' && isset($rec)) {
            if (rack_ZoneDetails::fetch("#zoneId = {$rec->id}")) {
                $requiredRoles = 'no_one';
            } elseif(rack_Movements::fetchField("LOCATE('|{$rec->id}|', #zoneList)") || rack_OldMovements::fetchField("LOCATE('|{$rec->id}|', #zoneList)")){
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'changestate' && isset($rec)) {
            if(isset($rec->containerId) || rack_ZoneDetails::fetch("#zoneId = {$rec->id} AND (#documentQuantity IS NOT NULL OR #movementQuantity IS NOT NULL)")){
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'removedocument' && isset($rec->id)) {
            if (empty($rec->containerId)) {
                $requiredRoles = 'no_one';
            } else {
                if (rack_ZoneDetails::fetchField("#zoneId = {$rec->id} AND (#movementQuantity IS NOT NULL AND #movementQuantity != 0)")) {
                   $requiredRoles = 'no_one';
                }
            }
        }

        if($action == 'manualclearzone' && isset($rec->containerId)){
            if(doc_Containers::fetchField($rec->containerId, 'state') != 'active'){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Изчистване на зоната към която е закачен документа
     *
     * @param int $containerId
     */
    public static function clearZone($containerId)
    {
        // Към коя зона е в момента закачен документа
        $zoneRec = self::fetch("#containerId = {$containerId}");
        if (empty($zoneRec)) return;

        // Затваря движенията към зоната
        rack_Movements::closeByZoneId($zoneRec->id);

        // Рекалкулира к-та по зони на артикула
        $productArr = array();
        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->where("#zoneId = {$zoneRec->id}");
        while ($dRec = $dQuery->fetch()) {
            rack_ZoneDetails::delete($dRec->id);
            $productArr[$dRec->productId] = $dRec->productId;
        }

        rack_Products::recalcQuantityOnZones($productArr, $zoneRec->storeId);

        $zoneRec->defaultUserId = null;
        $zoneRec->containerId = null;
        self::save($zoneRec);
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        $ready = $count = 0;

        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->where("#zoneId = {$rec->id}");
        while ($dRec = $dQuery->fetch()) {
            if (!empty($dRec->documentQuantity) && round($dRec->documentQuantity, 4) == round($dRec->movementQuantity, 4)) {
                $ready++;
            }

            if (!empty(round($dRec->documentQuantity, 4)) || !empty(round($dRec->movementQuantity, 4))) {
                $count++;
            }
        }

        // Запомняне на старата готовност и изчисляване на новата
        $oldReadiness = $rec->readiness;
        $rec->readiness = ($count) ? $ready / $count : null;
        $this->save($rec, 'readiness');

        // Ако готовността е току що станала на 100% или от 100% е паднала
        if(isset($rec->containerId)){
            $Document = doc_Containers::getDocument($rec->containerId);
            if(($oldReadiness == 1 && $rec->readiness != 1) || ($rec->readiness == 1 && $oldReadiness != 1)){

                // Ако документа в зоната е закачен към транспортна линия - тя се маркира да се обнови
                if($Document->getInstance()->hasPlugin('trans_plg_LinesPlugin')){
                    if($documentLineId = $Document->fetchField($Document->lineFieldName)){
                        $this->syncLinesOnShutdown[] = $documentLineId;
                    }
                }
            }
        }
    }


    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_AfterSessionClose($mvc)
    {
        // Заопашените за обновяване линии да се обновят след терминиране на скрипта
        if (is_array($mvc->syncLinesOnShutdown)) {
            $Lines = cls::get('trans_Lines');
            foreach ($mvc->syncLinesOnShutdown as $lineId) {
                $Lines->updateMaster($lineId);
            }
        }
    }


    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if(Request::get('terminal')){
            unset($data->toolbar->buttons['btnAdd']);
            $storeId = store_Stores::getCurrent();
            if ($mvc->haveRightFor('orderpickup', (object)array('storeId' => $storeId))) {
                $data->toolbar->addBtn('Генериране на движения', array($mvc, 'orderpickup', 'storeId' => $storeId, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png,title=Бързо нагласяне');
            }
        }
    }


    /**
     * Кои са текущите движения в зоната
     *
     * @param int $zoneId
     * @param string $filter
     * @return array $res
     */
    public static function getCurrentMovementRecs($zoneId, $filter)
    {
        if (!isset(self::$movementCache[$zoneId])) {
            $zoneRec = rack_Zones::fetch($zoneId);

            self::$movementCache[$zoneId] = array();
            $mQuery = rack_Movements::getQuery();
            $mQuery->where("LOCATE('|{$zoneId}|', #zoneList)");

            if($filter == 'pendingAndMine'){
                $mQuery->where("#state = 'pending' OR #workerId =" . core_Users::getCurrent());
            } elseif($filter == 'onlyMine'){
                $mQuery->where("#workerId =" . core_Users::getCurrent());
            } elseif($filter == 'pending'){
                $mQuery->where("#state = 'pending'");
            } elseif($filter == 'notClosed'){
                $mQuery->where("#state != 'closed'");
            }
            $mQuery->orderBy('id', 'DESC');

            $where = (!$zoneRec->containerId) ? "(#documents IS NULL OR #documents = '')" : "LOCATE('|{$zoneRec->containerId}|', #documents)";
            $mQuery->where("#state != 'closed' OR (#state = 'closed' && {$where})");

            while ($mRec = $mQuery->fetch()) {
                if (!empty($mRec->zones)) {
                    $clone = clone $mRec;
                    self::$movementCache[$zoneId][$mRec->id] = $clone;
                }
            }
        }

        $nonClosedRecs = array_filter(self::$movementCache[$zoneId], function ($a) { return $a->state != 'closed';});
        if (!countR($nonClosedRecs)) {
            self::$movementCache[$zoneId] = array();
        }

        return self::$movementCache[$zoneId];
    }


    /**
     * Следващия номер на зона
     *
     * @param int $storeId
     *
     * @return float number
     */
    private function getNextNumber($storeId)
    {
        $query = $this->getQuery();
        $query->orderBy('#num', 'DESC');
        $lastRec = $query->fetch("#storeId = {$storeId}");

        $num = is_object($lastRec) ? $lastRec->num : 0;
        $num++;

        return $num;
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if($data->isTerminal){
            $data->listTableMvc->commonRowClass = 'zonesCommonRow zoneLighter';
            unset($data->listFields['_rowTools']);
        }
        $data->listTableMvc->setFieldType('num', 'varchar');
    }


    /**
     * Избор на зона в документ
     */
    public function act_Orderpickup()
    {
        // Проверка на права
        $this->requireRightFor('orderpickup');
        expect($storeId = Request::get('storeId', 'int'));
        $this->requireRightFor('orderpickup', (object)array('storeId' => $storeId));

        // Регенериране на всички движения
        static::pickupAll($storeId);

        followRetUrl(null, 'Движенията са генерирани успешно|*!');
    }


    /**
     * Генериране на всички движения за склада
     *
     * @param int $storeId            - ид на склад
     * @param int|null $defaultUserId - ид на дефолтен потребител (ако има)
     * @param int|null $productIds    - ид-та на артикули (null за всички)
     * @param void
     */
    public static function pickupAll($storeId, $defaultUserId = null, $productIds = null)
    {
        if (!core_Locks::get("PICKED_UP{$storeId}", 21, 20)) {
            wp('RACK_PICK_UP_ALL_TIMEOUT', $storeId, $defaultUserId, $productIds);
            return;
        }

        $productIdLogString = implode(',', arr::make($productIds, true));
        rack_Movements::logDebug("RACK PICKUP ALL - {$storeId} - '{$productIdLogString}'");

        // Изтриване на всички чакащи движения в склада преди да се регенерират наново
        $zQuery = rack_Zones::getQuery();
        $zQuery->where("#storeId = {$storeId}");
        $zQuery->show("id");
        $zoneIds = arr::extractValuesFromArray($zQuery->fetchAll(), 'id');
        static::deletePendingZoneMovements($zoneIds, core_Users::SYSTEM_USER, $productIds);

        // Групиране по групи на зоните
        $gQuery = rack_ZoneGroups::getQuery();
        $gQuery->orderBy('order', 'ASC');

        while ($gRec = $gQuery->fetch()) {
            $groupableZones = self::getZones($storeId, false, true, $gRec->id);
            if (countR($groupableZones)) {
                $groupableZones = arr::make(array_keys($groupableZones), true);
                self::pickupOrder($storeId, $groupableZones, $defaultUserId, $productIds, false);
            }
        }

        // Всички зони, които са без групиране
        $nonGroupableZones = array_keys(self::getZones($storeId, false, false));
        foreach ($nonGroupableZones as $zoneId) {
            self::pickupOrder($storeId, $zoneId, $defaultUserId, $productIds, false);
        }

        core_Locks::release("PICKED_UP{$storeId}");
    }
    
    
    /**
     * Изтриване на чакащите движения към зоната
     *
     * @param mixed $zoneIds
     * @param int $userId
     * @param null|array $productIds
     * @return void
     */
    private static function deletePendingZoneMovements($zoneIds, $userId, $productIds = null)
    {
        $productIds = arr::make($productIds, true);

        $mQuery = rack_Movements::getQuery();
        $mQuery->where("#state = 'pending' AND #zoneList IS NOT NULL AND #createdBy = {$userId}");
        if(countR($productIds)){
            $mQuery->in('productId', $productIds);
        }

        if (isset($zoneIds)) {
            $zoneIds = arr::make($zoneIds, true);
            $mQuery->likeKeylist('zoneList', $zoneIds);
        }

        $mQuery->show('id');
        $isOriginalSystemUser = core_Users::isSystemUser();
        if(!$isOriginalSystemUser){
            core_Users::forceSystemUser();
        }

        $deleted = 0;
        while ($mRec = $mQuery->fetch()) {
            rack_Movements::delete($mRec->id);
            $deleted++;
        }

        if(!$isOriginalSystemUser) {
            core_Users::cancelSystemUser();
        }

        $zoneStringLog = implode('|', $zoneIds);
        $productStringLog = implode('|', $productIds);
        rack_Movements::logDebug("RACK DELETE PENDING COUNT ({$deleted}) - '{$zoneStringLog}'- PROD -'{$productStringLog}'");
    }


    /**
     * Генерира очакваните движения за зоните в склада
     *
     * @param int $storeId - ид на склад
     * @param array|null $zoneIds - ид-та само на избраните зони
     * @param null $workerId - ид на дефолтен товарач
     * @param array|null $productIds - ид-та на артикули
     * @param boolean $deletePendingSystemMovementsInZoneFirst - да се изтрият ли първи системните движения
     */
    private static function pickupOrder($storeId, $zoneIds = null, $workerId = null, $productIds = null, $deletePendingSystemMovementsInZoneFirst = true)
    {
        // Ако се иска да се изтрият движенията към зоната
        if($deletePendingSystemMovementsInZoneFirst){
            static::deletePendingZoneMovements($zoneIds, core_Users::SYSTEM_USER, $productIds);
        }

        // Какви са очакваните количества
        $expected = self::getExpectedProducts($storeId, $zoneIds, $productIds);

        $floor = rack_PositionType::FLOOR;
        foreach ($expected->products as $pRec) {
            $BatchClass = batch_Defs::getBatchDef($pRec->productId);

            // Ако в зоната реда е с определена партидност - то трябва да се подадат само палетите с тази партидност.
            // Ако в зоната реда е без партидност, но продукта има партидност - се търсят само палетите, които са с празна партидност
            $batch = (is_object($BatchClass)) ? $pRec->batch : null;

            // Какви са наличните палети за избор (запазените се приспадат)
            $pallets = rack_Pallets::getAvailablePallets($pRec->productId, $storeId, $batch, true, true);
            $requiredQuantityOnZones = array_sum($pRec->zones);

            // Ако е склада се използват приоритетни стелажи
            if(rack_Racks::canUsePriorityRacks($storeId)){

                // Коя е групата на първата зона, очаква се че всички зони са от една група!
                $firstZoneId = key($pRec->zones);
                $groupId = rack_Zones::fetchField($firstZoneId, 'groupId');

                // Кои стелажи са с приоритет при групата на зоните
                if(!array_key_exists($groupId, static::$cachedRacksByGroup)){
                    $rQuery = rack_Racks::getQuery();
                    $rQuery->where("#storeId = {$storeId}");
                    $groupId = isset($groupId) ? $groupId : '-1';
                    $rQuery->where("LOCATE('|{$groupId}|', #groups)");
                    $rQuery->show('num');
                    static::$cachedRacksByGroup[$groupId] = arr::extractValuesFromArray($rQuery->fetchAll(), 'num');
                }

                // Оставяне само на тези палети, които са на тези стелажи
                $rackNums = static::$cachedRacksByGroup[$groupId];
                $onlyPalletsInThoseRacks = array();
                array_walk($rackNums, function($a) use (&$onlyPalletsInThoseRacks, $pallets){
                    foreach ($pallets as $k => $palletRec){
                        list($n,,) = rack_PositionType::toArray($palletRec->position);
                        if($n == $a){
                            $onlyPalletsInThoseRacks[$k] = $palletRec;
                        }
                    }
                });

                // Ако палетите от приоритетните стелажи са достатъчни за зоната, използват се само те
                $onPriorityRacks = arr::sumValuesArray($onlyPalletsInThoseRacks, 'quantity');
                if($onPriorityRacks >= $requiredQuantityOnZones){
                    $pallets = $onlyPalletsInThoseRacks;
                }
            }

            // Ако к-то по палети е достатъчно за изпълнение, не се добавя ПОД-а, @TODO да се изнесе в mainP2Q
            $quantityOnPallets = arr::sumValuesArray($pallets, 'quantity');
            if ($quantityOnPallets < $requiredQuantityOnZones) {
                $floorQuantity = rack_Products::getFloorQuantity($pRec->productId, $batch, $storeId);
                $floorWaitingQuantity = rack_Pallets::getSumInZoneMovements($pRec->productId, $batch, null, 'waiting');
                $floorPendingQuantity = rack_Pallets::getSumInZoneMovements($pRec->productId, $batch, null, 'pending');

                $floorQuantity -= $floorWaitingQuantity;
                $floorQuantity -= $floorPendingQuantity;
                if ($floorQuantity > 0) {
                    $pallets[$floor] = (object)array('quantity' => $floorQuantity, 'position' => $floor);
                }
            }

            $palletsArr = array();
            foreach ($pallets as $obj) {
                $palletsArr[$obj->position] = $obj->quantity;
            }

            if (!countR($palletsArr)) continue;

            // Какво е разпределянето на палетите
            if(rack_Setup::get('PICKUP_STRATEGY') == 'ver2') {

                // Извличане само на опаковките на артикула + основната мярка
                if(!array_key_exists($pRec->productId, static::$cache)){
                    $packQuery = cat_products_Packagings::getQuery();
                    $packQuery->EXT('type', 'cat_UoM', 'externalName=type,externalKey=packagingId');
                    $packQuery->where("#productId = {$pRec->productId} AND #type = 'packaging'");
                    $packQuery->show('quantity,packagingId');
                    $packagings = array();
                    while($packRec = $packQuery->fetch()) {
                        $packagings[] = $packRec;
                    }

                    // Ако артикула няма опаковка палет намира се к-то на най-големия палет в системата
                    $palletId = cat_UoM::fetchBySinonim('pallet')->id;
                    if(!array_key_exists($palletId, $packagings)){
                        $maxPalletQuantity = max(array_map(function($o) { return $o->quantity;}, $pallets));
                        if($maxPalletQuantity){
                            $packagings[] = (object)array('packagingId' => $palletId, 'quantity' => $maxPalletQuantity);
                        }
                    }

                    if(!countR($packagings)){
                        $measureId = cat_Products::fetchField($pRec->productId, 'measureId');
                        $packagings[] = (object)array('packagingId' => $measureId, 'quantity' => 1);
                    }

                    static::$cache[$pRec->productId] = $packagings;
                }

                $allocatedPallets = rack_MovementGenerator2::mainP2Q($pallets, $pRec->zones, static::$cache[$pRec->productId], null, null, $storeId);
            } else {
                $allocatedPallets = rack_MovementGenerator::mainP2Q($palletsArr, $pRec->zones);
            }

            // Ако има генерирани движения се записват
            $movements = rack_MovementGenerator::getMovements($allocatedPallets, $pRec->productId, $pRec->packagingId, $pRec->batch, $storeId, $workerId);

            // Движенията се създават от името на системата
            $isOriginalSystemUser = core_Users::isSystemUser();
            if(!$isOriginalSystemUser) {
                core_Users::forceSystemUser();
            }

            foreach ($movements as $movementRec) {
                rack_Movements::save($movementRec);
            }

            if(!$isOriginalSystemUser) {
                core_Users::cancelSystemUser();
            }
        }
    }


    /**
     * Екшън за ръчно премахване на документ от зоната
     */
    public function act_manualclearzone()
    {
        $this->requireRightFor('manualclearzone');
        expect($containerId = Request::get('containerId', 'int'));
        $Document = doc_Containers::getDocument($containerId);
        $this->requireRightFor('manualclearzone', (object)array('containerId' => $containerId));

        rack_Zones::clearZone($containerId);
        $Document->getInstance()->logWrite("Ръчно премахване на контиран документ от зона", $Document->that);

        followRetUrl(null, 'Документа е премахнат успешно от зоната|*');
    }


    /**
     * Премахване на документ от зоната
     *
     * @return void
     */
    public function act_Removedocument()
    {
        // Проверка на права
        $this->requireRightFor('removedocument');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('removedocument', $rec);
        $document = doc_Containers::getDocument($rec->containerId);

        if(rack_Movements::fetch("LOCATE('|{$rec->id}|', #zoneList) AND (#state = 'waiting' OR #state = 'active')")){
            followRetUrl(null, "По документа има Запазени или Започнати движения! Документът може да бъде премахнат след отказването им|*!", 'error');
        }

        // Зоната се нотифицира, че документът е премахнат от нея
        static::updateZone($rec->id, $rec->containerId, true);
        $document->getInstance()->logWrite('Премахване от зона', $document->that);

        followRetUrl(null, 'Документът е премахнат от зоната');
    }


    /**
     * Връща очакваните артикули по зони с документи
     *
     * @param int $storeId
     * @param array|null $zoneIds - ид-та само на избраните зони
     * @param array|null $productIds - ид-та само на избраните артикули или null за всички
     *
     * @return stdClass $res
     */
    private static function getExpectedProducts($storeId, $zoneIds, $productIds = null)
    {
        $productIds = arr::make($productIds, true);
        $res = (object)array('products' => array());
        $res->zones = (is_numeric($zoneIds)) ? array($zoneIds => $zoneIds) : ((is_array($zoneIds)) ? $zoneIds : array());

        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
        $dQuery->where("#documentQuantity IS NOT NULL AND #storeId = {$storeId}");
        if(countR($productIds)){
            $dQuery->in('productId', $productIds);
        }

        $zoneMovements = array();
        if (isset($zoneIds)) {
            $zoneIds = arr::make($zoneIds, true);
            $dQuery->in('zoneId', $zoneIds);

            $mQuery = rack_Movements::getQuery();
            $mQuery->where("#state = 'pending' || #state = 'waiting'");
            $zoneIds = arr::make($zoneIds, true);
            $mQuery->likeKeylist('zoneList', keylist::fromArray($zoneIds));
            if(countR($productIds)){
                $mQuery->in('productId', $productIds);
            }

            $zoneMovements = $mQuery->fetchAll();
        }

        while ($dRec = $dQuery->fetch()) {
            $notActiveQuantity = 0;
            array_walk($zoneMovements, function($a) use ($dRec, &$notActiveQuantity){
                if($dRec->productId == $a->productId && $dRec->packagingId == $a->packagingId && $dRec->batch == $a->batch){
                    $zones = rack_Movements::getZoneArr($a);
                    $quantityInZoneArr = array_values(array_filter($zones, function($z) use ($dRec){return $z->zone == $dRec->zoneId;}));
                    if(is_object($quantityInZoneArr[0])){
                        $notActiveQuantity += $quantityInZoneArr[0]->quantity * $a->quantityInPack;
                    }
                }
            });

            // Участват само тези по които се очакват още движения
            $needed = $dRec->documentQuantity - $dRec->movementQuantity - $notActiveQuantity;
            if (empty($needed) || $needed < 0) continue;

            $key = "{$dRec->productId}|{$dRec->packagingId}|{$dRec->batch}";
            if (!array_key_exists($key, $res->products)) {
                $res->products[$key] = (object)array('productId' => $dRec->productId, 'packagingId' => $dRec->packagingId, 'zones' => array(), 'batch' => $dRec->batch);
                $res->zones[$dRec->zoneId] = $dRec->zoneId;
            }

            $res->products[$key]->zones[$dRec->zoneId] += ($dRec->documentQuantity - $dRec->movementQuantity - $notActiveQuantity);
        }

        return $res;
    }


    /**
     * Връща Урл към списъка на зоните филтрирани по зона и група
     *
     * @param mixed $zoneId - ид или запис на зона
     * @return array $url - урл-то
     */
    public static function getUrlArr($zoneId)
    {
        $zoneRec = self::fetchRec($zoneId);
        $grouping = ($zoneRec->groupId) ? $zoneRec->groupId : "s{$zoneRec->id}";
        $url = array('rack_Zones', 'list', 'terminal' => 1, 'grouping' => $grouping, 'ret_url' => true);

        if (isset($zoneRec->groupId)) {
            $url['grouping'] = $zoneRec->groupId;
        }

        return $url;
    }


    /**
     * Функция по подразбиране, за връщане на хеша на резултата
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $status
     */
    protected function on_AfterGetContentHash($mvc, &$res, &$status)
    {
        $storeId = store_Stores::getCurrent();

        // Хеша е датата на последна модификация на движенията
        $mQuery = rack_Movements::getQuery();
        $mQuery->where("#storeId = {$storeId}");
        $mQuery->orderBy('modifiedOn', 'DESC');
        $mQuery->show('modifiedOn');
        $mQuery->limit(1);
        $res = md5(trim($mQuery->fetch()->modifiedOn));

    }


    /**
     * Има ли запазени движения в зоната
     *
     * @param $containerId
     * @return bool
     */
    public static function hasRackMovements($containerId)
    {
        // Има ли нагласени количества за артикула в зоната?
        $zQuery = rack_ZoneDetails::getQuery();
        $zQuery->XPR('movementQuantityRound', 'varchar', 'ROUND(COALESCE(#movementQuantity, 0), 3)');
        $zQuery->EXT('containerId', 'rack_Zones', 'externalName=containerId,externalKey=zoneId');
        $zQuery->where("#containerId = {$containerId} AND #movementQuantityRound != 0");
        $zQuery->show('id');
        $rec = $zQuery->fetch();

        return is_object($rec);
    }


    /**
     * Линк към зоната
     *
     * @param int $zoneId               - ид на зона
     * @param bool $showGroup           - да се показва ли групата на зоната или не
     * @param false|string $makeLink    - false да не е линк, single за линк към сингъла и terminal за линк към терминала
     * @param string|null $class        - с какъв клас да е елемента
     * @return string|null   $zoneTitle - заглавие на зоната
     */
    public static function getDisplayZone($zoneId, $showGroup = false, $makeLink = 'terminal', $class = 'zoneMovement')
    {
        if(Mode::is('printing') || Mode::is('text', 'xhtml')) return null;
        $zoneTitle = ($showGroup) ? rack_Zones::getRecTitle($zoneId) : rack_Zones::getVerbal(rack_Zones::fetch($zoneId), 'num');
        $warning = $hint = null;

        // Линк към зоната
        $zoneRec = rack_Zones::fetchRec($zoneId); 
        if($makeLink !== false){
            expect(in_array($makeLink, array('single', 'terminal')));
            $url = array();
            $zRec = rack_Zones::fetchRec($zoneId);
            if(isset($zRec->containerId) && doc_Containers::fetchField($zRec->containerId, 'state') == 'active'){
                if(rack_Zones::haveRightFor('manualclearzone', (object)array('containerId' => $zRec->containerId))){
                    $url = array('rack_Zones', 'manualclearzone', 'containerId' => $zRec->containerId, 'ret_url' => true);
                    $warning = 'Документът трябва ръчно да се премахне от зоната|*!';
                }
            }

            if(!$url){
                if(rack_Zones::haveRightFor('list')){
                    $currentStoreId = store_Stores::getCurrent('id', false);
                    $grouping = ($zoneRec->groupId) ? $zoneRec->groupId : "s{$zoneRec->id}";
                    $url = ($makeLink == 'terminal') ? array('rack_Zones', 'list', 'terminal' => 1, 'grouping' => $grouping) : rack_Zones::getSingleUrlArray($zoneRec->id);
                    if($zoneRec->storeId != $currentStoreId){
                        if(store_Stores::haveRightFor('select', $zoneRec->storeId)){
                            $url = array('store_Stores', 'setCurrent', $zoneRec->storeId, 'ret_url' => $url);
                        } else {
                            $url = array();
                        }
                    }
                }
            }
            
            if (!empty($zoneRec->description)) {
                $hint = rack_Zones::getVerbal($zoneRec, 'description');
            }

            if(countR($url)){
                $backgroundColor = !empty($zoneRec->color) ? $zoneRec->color : rack_Setup::get('DEFAULT_ZONE_COLORS');
                $additionalClass = phpcolor_Adapter::checkColor($backgroundColor, 'dark') ? 'lightText' : 'darkText';
                $zoneTitle = ht::createLink($zoneTitle, $url,false, array("style"=> "color:{$additionalClass}"));
                $zoneTitle->content = str_replace('lightText', '#eceff4', $zoneTitle->content);
                $zoneTitle->content = str_replace('darkText', '#2e3440', $zoneTitle->content);
            }
        }

        // Ако има клас обвива се в него
        if(isset($class)){
            $backgroundColor = !empty($zoneRec->color) ? $zoneRec->color : rack_Setup::get('DEFAULT_ZONE_COLORS');
            $additionalClass = phpcolor_Adapter::checkColor($backgroundColor, 'dark') ? 'lightText' : 'darkText';
            $res = new core_ET("<div class='{$class} {$additionalClass}' style='background-color:{$backgroundColor};'>[#element#]</div>");
            $res->replace($zoneTitle, 'element');
            $zoneTitle = $res->getContent();
            if(isset($hint)){
                $zoneTitle = ht::createHint($zoneTitle, $hint, 'notice', true,'style=background-color:#fff !important;margin-left:3px;border-radius:8px;');
            }
        }

        if($warning){
            $zoneTitle = ht::createHint($zoneTitle, $warning, 'warning');
        }

        return $zoneTitle;
    }


    /**
     * Промяне УРЛ-то за редирект след запис
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected function on_AfterPrepareRetUrl($mvc, $data)
    {
        if($data->action == 'manage' && $data->form->cmd != 'save_n_new'){
            $data->retUrl = array('rack_Zones', 'list');
        }
    }
}
