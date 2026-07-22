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

        // В кои състояния на мастъра редакция/изтриване на реда води до оттегляне+клониране
        setPartIfNot($invoker, 'detailRevisionsStates', array('pending', 'active'));

        // Дали текущия $mvc в момента показва и оттеглените редове (виж on_AfterGetQuery)
        setPartIfNot($invoker, 'showDetailRevisions', false);
    }


    /**
     * При редакция на съществуващ ред, докато мастъра е редактируем - оттегляме
     * стария ред и запазваме редакцията като нов, вместо ъпдейт на място
     */
    public static function on_BeforeSave(&$invoker, &$res, &$rec, &$fields = null, &$mode = null)
    {
        if (!empty($rec->_skipDetailRevision) || empty($rec->id) || ($rec->state ?? null) == 'rejected') {

            return;
        }

        $masterState = $invoker->Master->fetchField($rec->{$invoker->masterKey}, 'state');
        if (!in_array($masterState, arr::make($invoker->detailRevisionsStates, true))) {

            return;
        }

        $oldRec = $invoker->fetch($rec->id);
        $oldRec->state = 'rejected';
        $oldRec->rejectedOn = dt::now();
        $oldRec->rejectedBy = core_Users::getCurrent();
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
     * изрично поисканите в ?ShowRevisions=<masterId>[,<masterId>...].
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

        $showIds = arr::make(Request::get('ShowRevisions'), true);
        if (countR($showIds)) {
            $idsCsv = implode(',', array_map('intval', $showIds));
            $cond .= " OR #{$mvc->masterKey} IN ({$idsCsv})";
        }

        $query->where($cond);
    }


    /**
     * Когато се показва и историята (оттеглените редове) на текущия мастър, ги
     * подреждаме по хронология на "приключване на реда" - за активния ред това е
     * createdOn (откога е текущ), за оттеглените - rejectedOn (докога са били
     * текущи). DESC, за да е най-скорошната активност първа (както при "Кош"-а
     * на plg_Rejected: orderBy('#modifiedOn', 'DESC', ...))
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if (!$data->query) {

            return;
        }

        $showIds = arr::make(Request::get('ShowRevisions'), true);
        if (!in_array($data->masterId, $showIds)) {

            return;
        }

        $data->query->XPR('filterDate', 'datetime', 'COALESCE(#rejectedOn, #createdOn)');

        // По-висок priority от 0 (по подразбиране), защото deals_ManifactureDetail
        // вече слага "orderBy('id', 'ASC')" в prepareListFilter, преди нас
        $data->query->orderBy('#filterDate', 'DESC', 1);
    }


    /**
     * Скрит филтър за показване на оттеглените редове през ?ShowRevisions=<masterId>
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if (!isset($data->listFilter->fields['ShowRevisions'])) {
            $data->listFilter->FNC('ShowRevisions', 'varchar', 'input=hidden,silent');
        }

        if ($showIds = Request::get('ShowRevisions')) {
            $data->listFilter->setDefault('ShowRevisions', $showIds);
        }
    }


    /**
     * Бутон за превключване между активните редове и пълната история (вкл. оттеглените).
     * Изнесен като самостоятелен метод, за да може да се вика и ръчно от bespoke
     * renderDetail_ методи (напр. planning_DisassemblyNoteDetails), без да минава
     * през целия renderListToolbar()/$data->toolbar
     *
     * @return core_ET|null
     */
    public static function getToggleBtn($mvc, $masterId)
    {
        if (Mode::is('printing')) {

            return null;
        }

        // За да не скача страницата най-отгоре след редирект, а остане на детайла
        $anchor = null;
        if (isset($mvc->Master) && cls::existsMethod($mvc->Master, 'getHandle')) {
            $anchor = $mvc->Master->getHandle($masterId);
        }

        // Кои мастъри вече са в режим "покажи историята" - може да са няколко
        // едновременно на една страница (напр. няколко протокола за разпад в нишка)
        $showIds = arr::make(Request::get('ShowRevisions'), true);

        if (in_array($masterId, $showIds)) {
            $curUrl = getCurrentUrl();
            $remainingIds = array_diff($showIds, array($masterId));
            if (countR($remainingIds)) {
                $curUrl['ShowRevisions'] = implode(',', $remainingIds);
            } else {
                unset($curUrl['ShowRevisions']);
            }
            if ($anchor) {
                $curUrl['#'] = $anchor;
            }

            return ht::createBtn('Текущи редове', $curUrl, null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/application_view_list.png', 'title' => 'Само активните редове'));
        }

        // Временно сваляме собствения си филтър, за да преброим оттеглените редове на този мастър
        $mvc->showDetailRevisions = true;
        $rejCnt = $mvc->count("#{$mvc->masterKey} = {$masterId} AND #state = 'rejected'");
        $mvc->showDetailRevisions = false;

        if (!$rejCnt) {

            return null;
        }

        $curUrl = getCurrentUrl();
        $curUrl['ShowRevisions'] = implode(',', $showIds + array($masterId => $masterId));
        if ($anchor) {
            $curUrl['#'] = $anchor;
        }

        return ht::createBtn("Оттеглени редове|* ({$rejCnt})", $curUrl, null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/bin_closed.png', 'title' => 'Преглед на оттеглените (презаписани) редове'));
    }


    /**
     * За детайли, които рендират тулбара си по стандартния начин ($data->toolbar)
     */
    public static function on_AfterRenderListToolbar($mvc, &$res, $data)
    {
        $btn = self::getToggleBtn($mvc, $data->masterId);
        if (empty($btn)) return;

        $res->append($btn);
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
     * Визуално открояване на оттеглените редове (виждат се само с ?ShowRevisions=1)
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if (($rec->state ?? null) == 'rejected') {
            $row->ROW_ATTR['class'] = trim(($row->ROW_ATTR['class'] ?? '') . ' state-rejected');
        }
    }


    /**
     * Hint "кой и кога" върху номера на реда (tools) на оттеглените редове - на
     * on_AfterPrepareListRows (не на on_AfterRecToVerbal), защото плъгините се
     * изпълняват ПРЕДИ on_AfterRecToVerbal на самия клас/родителите му, а напр.
     * deals_ManifactureDetail презаписва $row->productId точно там - на този hook
     * вече всички редове (вкл. номерацията в tools) са напълно готови
     */
    public static function on_AfterPrepareListRows($mvc, &$data)
    {
        if (!countR($data->rows)) {

            return;
        }

        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];
            if (($rec->state ?? null) != 'rejected') {

                continue;
            }

            $userName = $rec->rejectedBy ? core_Users::getVerbal($rec->rejectedBy, 'nick') : '?';
            // Датата минава през type->toVerbal(), за да ползва smartTime форматирането на полето rejectedOn
            $rejDate = $mvc->fields['rejectedOn']->type->toVerbal($rec->rejectedOn);
            $hint = "Оттеглен от {$userName} на {$rejDate}";

            $field = isset($row->tools) ? 'tools' : (isset($row->productId) ? 'productId' : null);
            if (!$field) {

                continue;
            }

            $row->{$field} = ht::createHint($row->{$field}, $hint);
        }
    }
}