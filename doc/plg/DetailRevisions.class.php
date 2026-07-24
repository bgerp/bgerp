<?php


/**
 * Клас 'doc_plg_DetailRevisions' - При редакция на детайл, докато мастъра е в
 * едно от $detailRevisionsStates състоянията, вместо ъпдейт на място - старият
 * ред се оттегля (state=rejected), а редакцията се записва като нов ред. Така
 * за всеки ред от детайла се пази пълна история кой/кога го е създал.
 *
 * Прикачва се само към core_Detail наследници (ползва $mvc->masterKey и $mvc->Master).
 *
 * @category  bgerp
 * @package   doc
 *
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_DetailRevisions extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$invoker)
    {
        if (!isset($invoker->fields['state'])) {
            $invoker->FLD('state', 'enum(active=Активен,rejected=Оттеглено)', 'caption=Състояние,input=none,column=none,notNull,value=active,forceField,smartCenter');
        } elseif (!isset($invoker->fields['state']->type->options['rejected'])) {
            $invoker->fields['state']->type->options['rejected'] = 'Оттеглено';
        }

        $invoker->FLD('rejectedOn', 'datetime(format=smartTime)', 'caption=Оттеглен->На,input=none,column=none,forceField');
        $invoker->FLD('rejectedBy', 'key(mvc=core_Users)', 'caption=Оттеглен->От,input=none,column=none,forceField');
        $invoker->FLD('revisionRootId', 'int', 'caption=Ревизия->Корен,input=none,column=none,forceField');
        $invoker->FLD('revisionPrevId', 'int', 'caption=Ревизия->Предходен,input=none,column=none,forceField');
        $invoker->FLD('rejectedReason', 'enum(revision=Редакция,deleted=Изтриване)', 'caption=Оттеглен->Причина,input=none,column=none,forceField');
        $invoker->setDbIndex('revisionRootId');
        $invoker->setDbIndex('revisionPrevId');

        // В кои състояния на мастъра редакция/изтриване на реда води до оттегляне+клониране
        setPartIfNot($invoker, 'detailRevisionsStates', array('pending', 'active'));

        // Дали текущия $mvc в момента показва и оттеглените редове (виж on_AfterGetQuery)
        setPartIfNot($invoker, 'showDetailRevisions', false);

        // По кое поле да се търси "мъртъв" (изтрит, без активен наследник) ред
        // при добавяне на нов ред, за да се вържат в обща верига (@see on_BeforeSave)
        setPartIfNot($invoker, 'revisionLinkField', 'productId');
    }


    /**
     * При редакция на съществуващ ред, докато мастъра е редактируем - оттегляме
     * стария ред и запазваме редакцията като нов, вместо ъпдейт на място
     */
    public static function on_BeforeSave(&$invoker, &$res, &$rec, &$fields = null, &$mode = null)
    {
        if (!empty($rec->_skipDetailRevision) || ($rec->state ?? null) == 'rejected') {

            return;
        }

        if (empty($rec->id)) {
            self::linkToDeletedRow($invoker, $rec);

            return;
        }

        $masterState = $invoker->Master->fetchField($rec->{$invoker->masterKey}, 'state');
        if (!in_array($masterState, arr::make($invoker->detailRevisionsStates, true))) {

            return;
        }

        $oldRec = $invoker->fetch($rec->id);
        $rec->revisionRootId = $oldRec->revisionRootId ?: $oldRec->id;
        $rec->revisionPrevId = $oldRec->id;
        $oldRec->state = 'rejected';
        $oldRec->rejectedOn = dt::now();
        $oldRec->rejectedBy = core_Users::getCurrent();
        $oldRec->rejectedReason = 'revision';
        $oldRec->_skipDetailRevision = true;
        $invoker->save($oldRec);

        // Запазваме старото id, за да прехвърлим партидите му към новия ред в on_AfterSave
        // (batch_BatchesInDocuments ги пази по detailRecId, а той се сменя при клонирането)
        $rec->_detailRevisionOldId = $oldRec->id;

        // unset(id) -> INSERT вместо UPDATE; unset(createdOn/createdBy) -> plg_Created
        // (ако е зареден след нас в $loadList) ги попълва наново с текущия потребител/час
        unset($rec->id, $rec->createdOn, $rec->createdBy);
    }


    /**
     * При добавяне на нов ред - ако има "мъртъв" ред (оттеглен чрез изтриване,
     * без активен наследник) с един и същ $revisionLinkField (обичайно
     * productId) в същия мастър, новият ред се включва в неговата верига,
     * все едно е редакция на него - за да се виждат заедно в „Промени“
     */
    private static function linkToDeletedRow($invoker, &$rec)
    {
        $field = $invoker->revisionLinkField;
        if (empty($rec->{$invoker->masterKey}) || !isset($rec->{$field})) {

            return;
        }

        $masterState = $invoker->Master->fetchField($rec->{$invoker->masterKey}, 'state');
        if (!in_array($masterState, arr::make($invoker->detailRevisionsStates, true))) {

            return;
        }

        $cond = "#{$invoker->masterKey} = {$rec->{$invoker->masterKey}} AND #{$field} = {$rec->{$field}} AND #state = 'rejected' AND #rejectedReason = 'deleted'";
        if (isset($rec->type) && isset($invoker->fields['type'])) {
            $cond .= " AND #type = '{$rec->type}'";
        }

        $query = $invoker->getQuery();
        $query->where($cond);
        $query->orderBy('rejectedOn', 'DESC');

        while ($deadRec = $query->fetch()) {
            $groupKey = $deadRec->revisionRootId ?: $deadRec->id;

            // Ако групата вече има активен ред, не е "мъртва" - пропускаме я
            if ($invoker->count("#{$invoker->masterKey} = {$rec->{$invoker->masterKey}} AND (#id = {$groupKey} OR #revisionRootId = {$groupKey}) AND #state != 'rejected'")) {
                continue;
            }

            $rec->revisionRootId = $groupKey;
            $rec->revisionPrevId = $deadRec->id;

            return;
        }
    }


    /**
     * Прехвърля партидните разпределения (batch_BatchesInDocuments) от стария към
     * новия ред, за да не се "изгубят" (останат сочещи към оттегления ред) или да
     * се задублират движенията при следващо контиране (@see batch_Movements::saveMovement,
     * което чете всички batch_BatchesInDocuments по containerId, без да проверява
     * дали detailRecId сочи все още към активен ред)
     */
    public static function on_AfterSave($invoker, &$res, $rec, $fields = null, $mode = null)
    {
        if (empty($rec->_detailRevisionOldId) || empty($rec->id) || !core_Packs::isInstalled('batch')) {

            return;
        }

        $bQuery = batch_BatchesInDocuments::getQuery();
        $bQuery->where("#detailClassId = {$invoker->getClassId()} AND #detailRecId = {$rec->_detailRevisionOldId}");
        $bRecs = $bQuery->fetchAll();

        if (countR($bRecs)) {
            foreach ($bRecs as $bRec) {
                $bRec->detailRecId = $rec->id;
            }
            batch_BatchesInDocuments::saveArray($bRecs);
        }
    }


    /**
     * При изтриване на ред, докато мастъра е редактируем - вместо реален DELETE,
     * оттегляме реда (пази се в историята)
     */
    public static function on_BeforeDelete($mvc, &$numRows, $query, $cond)
    {
        if (!is_numeric($cond)) {

            return;
        }

        $rec = $mvc->fetch($cond);
        if (!$rec || $rec->state == 'rejected') {

            return;
        }

        $masterState = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state');
        if (!in_array($masterState, arr::make($mvc->detailRevisionsStates, true))) {

            return;
        }

        $rec->state = 'rejected';
        $rec->rejectedOn = dt::now();
        $rec->rejectedBy = core_Users::getCurrent();
        $rec->rejectedReason = 'deleted';
        $rec->_skipDetailRevision = true;
        $mvc->save($rec);

        // Редът вече не е активен - партидите му не бива да участват в бъдещи
        // движения при контиране (@see on_AfterSave за обратния случай - редакция)
        if (core_Packs::isInstalled('batch')) {
            batch_BatchesInDocuments::delete("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$rec->id}");
        }

        $numRows = 1;

        return false;
    }


    /**
     * Изключва оттеглените редове от всяка заявка, освен ако изрично не сме
     * поискали да ги виждаме (@see showDetailRevisions), или мастъра им е сред
     * изрично поисканите от doc_plg_MasterRevision.
     *
     * ВАЖНО: тук нямаме $masterId - в една нишка/страница може да има няколко
     * мастъра от този клас едновременно (напр. няколко протокола за разпад), а
     * на този hook заявката още не е ограничена до конкретен мастър (виж
     * prepareDetailQuery_, който добавя "#{masterKey} = {masterId}" СЛЕД
     * getQuery()). Затова условието е директно в SQL-а по реда на самата заявка,
     * вместо да разчитаме на PHP флаг, който би "изтекъл" към други мастъри.
     */
    public static function on_AfterGetQuery($mvc, &$query)
    {
        if ($mvc->showDetailRevisions === true) {

            return;
        }

        $cond = "#state != 'rejected' OR #state IS NULL";

        $showIds = doc_plg_MasterRevision::getRequestedMasterIds($mvc->Master);
        if (countR($showIds)) {
            $idsCsv = implode(',', array_map('intval', $showIds));
            $cond .= " OR #{$mvc->masterKey} IN ({$idsCsv})";
        }

        $query->where($cond);
    }


    /**
     * Скрит филтър, който запазва режима за ревизии при действията на детайла
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $param = doc_plg_MasterRevision::REQUEST_PARAM;
        if (!isset($data->listFilter->fields[$param])) {
            $data->listFilter->FNC($param, 'varchar', 'input=hidden,silent');
        }

        if ($showIds = Request::get($param)) {
            $data->listFilter->setDefault($param, $showIds);
        }
    }


    /**
     * В режим „Ревизии“ добавя обща колона за автора и датата на версията
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        $showIds = doc_plg_MasterRevision::getRequestedMasterIds($mvc->Master);
        if (!in_array($data->masterId, $showIds)) {
            return;
        }

        $data->listFields['revision'] = 'Промяна';
    }


    /**
     * Групите (по revisionRootId, коренът на веригата), които в момента имат
     * активен ред. За оттеглен ред от такава група показваме код/реф/№/артикул
     * "изпразнени" (виж на_BeforeRenderListTable), защото те вече се виждат на
     * активния наследник. Ако групата няма активен ред (цялата верига е приключила
     * с изтриване), оттегленият ред си остава единственото място с тази информация.
     */
    public static function groupsWithActiveRow($recs)
    {
        $res = array();
        foreach ($recs as $rec) {
            if (($rec->state ?? null) != 'rejected') {
                $res[$rec->revisionRootId ?: $rec->id] = true;
            }
        }

        return $res;
    }


    /**
     * Добавя визуалното поле към MVC клонинга, с който ще се рендира таблицата.
     * Тук (не в on_AfterPrepareListRows) празним code/reff/tools само за оттеглените
     * редове, защото при bespoke детайли (напр. planning_DisassemblyNoteDetails)
     * собственият on_AfterPrepareDetail презаписва tools СЛЕД AfterPrepareListRows -
     * BeforeRenderListTable е последният хук преди самото рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $showIds = doc_plg_MasterRevision::getRequestedMasterIds($mvc->Master);
        if (!in_array($data->masterId, $showIds) || !isset($data->listTableMvc)) {
            return;
        }

        $data->listTableMvc->FLD('revision', 'varchar', 'caption=Промяна', 'tdClass=small nowrap,smartCenter');

        $activeGroups = self::groupsWithActiveRow($data->recs);

        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];
            if ((($rec->state ?? null) == 'rejected') && isset($activeGroups[$rec->revisionRootId ?: $rec->id])) {
                $row->code = $row->reff = $row->tools = $row->productId = '';
            }
        }
    }


    /**
     * Оттеглен ред не може да се редактира/трие директно
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (in_array($action, array('edit', 'delete')) && isset($rec->id) && ($rec->state ?? null) == 'rejected') {
            $requiredRoles = 'no_one';
        }

        if (in_array($action, array('restore', 'reject')) && isset($rec)){
            $requiredRoles = 'no_one';
        }
    }


    /**
     * Визуално открояване на оттеглените редове в режим „Ревизии“
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if (($rec->state ?? null) == 'rejected') {
            $row->ROW_ATTR['class'] = trim(($row->ROW_ATTR['class'] ?? '') . ' rejected small');
        }
    }


    /**
     * Попълва автора и датата според вида на реда: created за активните и
     * rejected за оттеглените
     */
    public static function on_AfterPrepareListRows($mvc, &$data)
    {
        $showIds = doc_plg_MasterRevision::getRequestedMasterIds($mvc->Master);
        $showRevisions = in_array($data->masterId, $showIds);
        if (!countR($data->rows)) {

            return;
        }

        $activatedOn = null;
        if ($showRevisions) {
            $activatedOn = $data->masterData->rec->activatedOn ?? null;
            if (!isset($activatedOn)) {
                $activatedOn = $mvc->Master->fetchField($data->masterId, 'activatedOn');
            }
        }
        $masterState = $data->masterData->rec->state ?? null;
        $useRevisionEdit = in_array($masterState, arr::make($mvc->detailRevisionsStates, true));

        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];
            $isRejected = ($rec->state ?? null) == 'rejected';
            if ($showRevisions) {
                $onField = $isRejected ? 'rejectedOn' : 'createdOn';
                $byField = $isRejected ? 'rejectedBy' : 'createdBy';
                $date = $mvc->getVerbal($rec, $onField);
                $nick = !empty($rec->{$byField}) ? crm_Profiles::createLink($rec->{$byField}) : '';

                if (!$isRejected && isset($activatedOn, $rec->createdOn) && $rec->createdOn >= $activatedOn) {
                    $newBadge = "<span style='display:inline-block;background:#2196F3;color:#fff;font-size:10px;font-weight:bold;padding:1px 6px;border-radius:3px;vertical-align:middle;margin-left:4px;line-height:15px;' title='" . tr('Добавен след като документа е бил контиран или е станал на заявка') . "'>" . tr('НОВ') . "</span>";
                    $date = new core_ET("{$newBadge} {$date}");
                }

                $prefix = '';
                if ($isRejected) {
                    $prefix = ($rec->rejectedReason ?? null) == 'deleted' ? 'Изтрит: ' : '↳ ';
                }

                $row->revision = $prefix . trim("{$date} от {$nick}");
            }

            $editId = "edt{$rec->id}";
            if ($useRevisionEdit && !$isRejected && isset($row->_rowTools->links[$editId])) {
                $row->_rowTools->links[$editId]->title = 'Промяна';
                $row->_rowTools->links[$editId]->attr['ef_icon'] = 'img/16/edit.png';
                $row->_rowTools->links[$editId]->attr['title'] = 'Промяна на реда';
            }
        }

        if ($showRevisions) {
            self::regroupRevisionRows($data);
        }
    }


    /**
     * Подрежда редовете в режим „Промени“ по същия начин, по който биха стояли
     * активните редове в нормалния режим (по каквото сортиране е избрано -
     * @see cat_plg_ShowCodes) - това вече е готово в текущия ред на $data->rows
     * до тук, ако се гледат само активните редове. Тук просто групираме
     * оттеглените версии на всеки логически ред точно след него, хронологично
     * от най-новата към най-старата, без да местим активните редове помежду им.
     */
    private static function regroupRevisionRows(&$data)
    {
        $groups = array();
        $groupOrder = array();
        $pos = 0;

        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];
            $groupKey = $rec->revisionRootId ?: $rec->id;
            $groups[$groupKey][] = $id;

            // Позицията на групата се определя от активния и ред. Ако групата
            // няма активен ред (веригата е приключила с изтриване), пази се
            // позицията на първия и срещнат ред в текущия масив
            if (($rec->state ?? null) != 'rejected' || !isset($groupOrder[$groupKey])) {
                $groupOrder[$groupKey] = $pos;
            }

            $pos++;
        }

        asort($groupOrder);

        $newRows = $newRecs = array();
        foreach ($groupOrder as $groupKey => $ignored) {
            $members = $groups[$groupKey];

            usort($members, function ($a, $b) use ($data) {
                $recA = $data->recs[$a];
                $recB = $data->recs[$b];
                $rejectedA = ($recA->state ?? null) == 'rejected';
                $rejectedB = ($recB->state ?? null) == 'rejected';

                if ($rejectedA != $rejectedB) {

                    return $rejectedA <=> $rejectedB;
                }

                $dateA = $rejectedA ? $recA->rejectedOn : $recA->createdOn;
                $dateB = $rejectedB ? $recB->rejectedOn : $recB->createdOn;

                return strcmp((string) $dateB, (string) $dateA);
            });

            foreach ($members as $id) {
                $newRows[$id] = $data->rows[$id];
                $newRecs[$id] = $data->recs[$id];
            }
        }

        $data->rows = $newRows;
        $data->recs = $newRecs;
    }
}
