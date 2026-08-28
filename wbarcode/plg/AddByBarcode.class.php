<?php


/**
 * Клас 'wbarcode_plg_AddByBarcode'
 *
 * Добавяне на редове в детайл чрез последователно сканиране на тегловни баркодове
 *
 * Закача се към детайл на документ. В тулбара му се добавя бутон "Баркод", който отваря форма
 * за сканиране. След всеки сканиран баркод редът се записва през стандартната форма за добавяне
 * на детайла и потребителят се връща обратно на формата за сканиране. Ако артикулът вече е в
 * документа, вместо нов ред се редактира първият му ред и количеството му се заменя с новото.
 *
 * Кои полета се попълват от баркода се взима от пропъртита на мениджъра-домакин:
 *
 *      $wbarcodeProductFld   - артикулът, по подразбиране #productId
 *      $wbarcodePackagingFld - мярката/опаковката, по подразбиране #packagingId
 *      $wbarcodeQuantityFld  - количеството в опаковки, по подразбиране #packQuantity
 *      $wbarcodeTypeFld      - поле за тип на реда, ако детайлът има такова; когато е зададено,
 *                              формата за сканиране показва и избор на тип
 *
 * Ако детайлът дефинира getWbarcodeScanStage_($masterId) и той върне етап на мастъра, формата
 * за сканиране показва и избор дали к-то да се заменя, или да се натрупва. Автоматично първото
 * сканиране на реда в етапа заменя, а следващите натрупват.
 *
 * @category  bgerp
 * @package   wbarcode
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wbarcode_plg_AddByBarcode extends core_Plugin
{
    /**
     * Екшънът с формата за сканиране
     */
    const ACTION = 'addByBarcode';


    /**
     * Защитен параметър със сесийния код, който пуска автоматичния запис на формата за добавяне
     */
    const AUTO_PARAM = 'wbarcodeAuto';


    /**
     * Параметър в URL-то с ид-то на последно записания ред
     */
    const LAST_PARAM = 'wbarcodeLast';


    /**
     * Променлива в сесията с последното сканиране - баркод, к-во и режим
     */
    const CODE_VAR = 'wbarcodeLastCode';


    /**
     * Променлива в сесията с редовете, сканирани в текущия етап
     */
    const SCANNED_VAR = 'wbarcodeScanned';


    /**
     * Полето във формата за сканиране с режима на количеството
     */
    const MODE_FLD = 'wbarcodeMode';


    /**
     * Етапът, в който е мастърът - по подразбиране детайлът не е на етапи
     *
     * Мениджърът-домакин дефинира getWbarcodeScanStage_($masterId), ако има етапи
     *
     * @param core_Detail $mvc
     * @param string|null $res
     * @param int         $masterId
     *
     * @return void
     */
    public static function on_AfterGetWbarcodeScanStage($mvc, &$res, $masterId)
    {
    }


    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setPartIfNot($mvc, 'wbarcodeProductFld', $mvc->productFld ?? null, $mvc->productFieldName ?? null, 'productId');
        setPartIfNot($mvc, 'wbarcodePackagingFld', 'packagingId');
        setPartIfNot($mvc, 'wbarcodeQuantityFld', 'packQuantity');

        // Стойностите от баркода се подават по URL-то към формата за добавяне/редактиране
        $mvc->setField("{$mvc->wbarcodeProductFld},{$mvc->wbarcodePackagingFld},{$mvc->wbarcodeQuantityFld}", 'silent');

        // При сканиране трябва да се вижда как се пълни документът (@see doc_Detail)
        $mvc->renderMasterBellowForm = true;
    }


    /**
     * След подготовката на лентата с инструменти на детайла
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $masterId = $data->masterId ?? null;
        if (empty($masterId)) {

            return;
        }

        if (!$mvc->haveRightFor('add', (object) array($mvc->masterKey => $masterId))) {

            return;
        }

        $error = countR(wbarcode_Helper::getMasks()) ? '' : ',error=Не е въведена нито една маска на тегловен баркод';
        $url = array($mvc, self::ACTION, $mvc->masterKey => $masterId, 'ret_url' => true);
        $data->toolbar->addBtn('Баркод', $url, "id=btnAddByBarcode,title=Добавяне на артикули чрез сканиране на тегловен баркод{$error}", 'ef_icon=img/16/barcode-icon.png,order=11');
    }


    /**
     * Преди всеки екшън на мениджъра-домакин
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if (strtolower($action) != strtolower(self::ACTION)) {

            return;
        }

        expect($masterId = Request::get($mvc->masterKey, 'int'));
        expect($mvc->Master->fetch($masterId));

        $typeFld = $mvc->wbarcodeTypeFld ?? null;

        $form = cls::get('core_Form');
        $form->FLD($mvc->masterKey, "key(mvc={$mvc->Master->className})", 'input=hidden,silent');

        // Детайлите с тип на реда го избират тук и той се пренася през целия цикъл на сканиране
        if (!empty($typeFld)) {
            $form->FLD($typeFld, clone $mvc->getFieldType($typeFld), array('caption' => $mvc->getField($typeFld)->caption, 'mandatory' => 'mandatory', 'silent' => 'silent'));
        }

        $form->FNC('barcode', 'varchar(32)', 'caption=Баркод,input,mandatory,autocomplete=off,elementId=wbarcodeInput,class=w100');

        // Името на полето и id-то на формата са като очакваните от wscales, за да може после
        // теглото да дойде и от везна - връзката с реална везна още не е проверявана
        $form->FNC(static::getWeightFld($mvc), 'double(min=0)', 'caption=Тегло,input,unit=кг,hint=Замества теглото от баркода');

        // В етап на заявката к-то може и да се натрупва, вместо да заменя досегашното
        if (!empty($mvc->getWbarcodeScanStage($masterId))) {
            $form->FNC(self::MODE_FLD, 'enum(auto=Автоматично,replace=Замяна,add=Натрупване)', 'maxRadio=0,caption=Повторно сканиране,input,silent,value=auto,hint=Как се записва к-то при артикул, който вече е в документа');
        }
        $form->formAttr['id'] = $mvc->className . '-EditForm';

        $form->input(null, 'silent');

        // При детайл с тип правата зависят от него, затова се проверяват след тихото въвеждане
        $type = !empty($typeFld) ? ($form->rec->{$typeFld} ?? null) : null;
        $mvc->requireRightFor('add', static::getRightsRec($mvc, $masterId, $type));

        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array($mvc->Master, 'single', $masterId);
        }

        $form->title = 'Добавяне по баркод в|*' . ' <b>' . $mvc->Master->getFormTitleLink($masterId) . '</b>';
        $form->info = static::getScanInfo($mvc, $masterId);
        $form->toolbar->addSbBtn('Напред', 'save', 'id=save,ef_icon=img/16/move.png', 'title=Разчитане на баркода и записване на реда');
        $form->toolbar->addBtn('Отказ', $retUrl, 'id=cancel,ef_icon=img/16/close-red.png', 'title=Прекратяване на действията');

        $form->input();
        if ($form->isSubmitted()) {
            static::processBarcode($mvc, $form, $masterId);
        }

        static::renderMasterBellowForm($mvc, $form, $masterId);

        $tpl = $mvc->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        static::appendScaleJs($mvc, $tpl, $form);
        jquery_Jquery::run($tpl, "$('#wbarcodeInput').focus();");

        return false;
    }


    /**
     * Рендира мастъра под формата, за да се вижда как се пълни документът при сканиране
     *
     * @param core_Detail $mvc
     * @param core_Form   $form
     * @param int         $masterId
     *
     * @return void
     */
    private static function renderMasterBellowForm($mvc, $form, $masterId)
    {
        if ($mvc->renderMasterBellowForm !== true || !$mvc->Master->getField('containerId', false)) {

            return;
        }

        $containerId = $mvc->Master->fetchField($masterId, 'containerId');
        if (empty($containerId)) {

            return;
        }

        $document = doc_Containers::getDocument($containerId);
        if (!$document->haveRightFor('single')) {

            return;
        }

        // Както при $renderMasterBellowForm на детайлите (@see doc_Detail)
        $className = Mode::is('screenMode', 'wide') ? ' floatedElement ' : '';
        $form->layout = $form->renderLayout();
        $tpl = new ET("<div class='preview-holder {$className}'><div class='scrolling-holder'>[#DOCUMENT#]</div></div><div class='clearfix21'></div>");
        $tpl->append($document->getInlineDocumentBody(), 'DOCUMENT');
        $form->layout->append($tpl);
    }


    /**
     * Разчита сканирания баркод и при успех редиректва към формата за добавяне на ред
     *
     * @param core_Detail $mvc
     * @param core_Form   $form
     * @param int         $masterId
     *
     * @return void
     */
    private static function processBarcode($mvc, $form, $masterId)
    {
        $barcode = trim($form->rec->barcode);

        if (!countR(wbarcode_Helper::getMasks())) {
            $form->setError('barcode', 'Не е въведена нито една маска на тегловен баркод|*!');

            return;
        }

        $parsed = wbarcode_Helper::parse($barcode);
        if (empty($parsed)) {
            $form->setError('barcode', 'Баркодът не отговаря на нито една от маските за тегловни баркодове|*!');

            return;
        }

        $productRec = wbarcode_Helper::getProduct($barcode);
        if (empty($productRec)) {
            $form->setError('barcode', "Няма артикул с код|* <b>{$parsed->productCode}</b> |от баркода|*!");

            return;
        }

        if (!empty($productRec->error)) {
            $form->setError('barcode', $productRec->error);

            return;
        }

        // Въведеното или измереното от везна тегло е с приоритет пред това от баркода
        $weight = $form->rec->{static::getWeightFld($mvc)} ?? null;
        $quantity = empty($weight) ? $productRec->quantity : cat_UoM::convertValue($weight, cat_UoM::fetchBySysId('kg')->id, $productRec->measureId);

        // Закръгля се до точността на мярката, иначе формата за добавяне ще предупреди за нея
        $round = cat_UoM::fetchField($productRec->measureId, 'round');
        if (isset($round)) {
            $quantity = round($quantity, $round);
        }

        if (empty($quantity)) {
            $form->setError('barcode', 'Теглото от баркода е нула|*!');

            return;
        }

        $typeFld = $mvc->wbarcodeTypeFld ?? null;
        $type = !empty($typeFld) ? $form->rec->{$typeFld} : null;

        // Артикулът вече е в документа - количеството на първия му ред се заменя или се натрупва
        $existingRec = static::fetchExistingRec($mvc, $masterId, $productRec->productId, $productRec->measureId, $type);

        $mode = static::getQuantityMode($mvc, $form, $masterId, $existingRec);
        $scannedQuantity = $quantity;

        if ($mode == 'add') {
            $oldQuantity = $existingRec->{$mvc->wbarcodeQuantityFld} ?? null;
            if (isset($oldQuantity)) {
                $quantity += $oldQuantity;
                if (isset($round)) {
                    $quantity = round($quantity, $round);
                }
            }
        }

        // Правата се проверяват пак, защото при детайл с тип те зависят от избрания тип
        $action = isset($existingRec) ? 'edit' : 'add';
        $rightsRec = isset($existingRec) ? $existingRec : static::getRightsRec($mvc, $masterId, $type);
        if (!$mvc->haveRightFor($action, $rightsRec)) {
            $form->setError($typeFld ?: 'barcode', 'Няма права за записване на такъв ред|*!');

            return;
        }

        // Кодът не се изнася като видим параметър, а в защитената част на адреса
        Request::setProtected(self::AUTO_PARAM);

        // Връщане обратно на формата за сканиране, като се пази нейният адрес за връщане
        $backUrl = array($mvc, self::ACTION, $mvc->masterKey => $masterId);
        if (!empty($typeFld)) {
            $backUrl[$typeFld] = $type;
        }
        if (!empty($form->rec->{self::MODE_FLD})) {
            $backUrl[self::MODE_FLD] = $form->rec->{self::MODE_FLD};
        }
        if ($retUrl = getRetUrl()) {
            $backUrl['ret_url'] = $retUrl;
        }

        $mvc->Master->logWrite('Въвеждане на тегловен код', $masterId);

        // Баркодът не се пази в реда, затова се помни в сесията за инфото на следващата форма
        Mode::setPermanent(self::CODE_VAR, array('code' => $barcode, 'quantity' => $scannedQuantity, 'mode' => $mode));

        $existingId = isset($existingRec) ? $existingRec->id : null;
        $url = array($mvc, $action,
            $mvc->masterKey => $masterId,
            $mvc->wbarcodeProductFld => $productRec->productId,
            $mvc->wbarcodePackagingFld => $productRec->measureId,
            $mvc->wbarcodeQuantityFld => $quantity,
            self::AUTO_PARAM => static::getAutoHash($mvc, $masterId, $productRec->productId, $productRec->measureId, $type, $existingId),
            'ret_url' => $backUrl);

        if (!empty($typeFld)) {
            $url[$typeFld] = $type;
        }

        if (isset($existingId)) {
            $url['id'] = $existingId;
        }

        redirect($url);
    }


    /**
     * Заменя ли се к-то на съществуващия ред, или се натрупва
     *
     * @param core_Detail   $mvc
     * @param core_Form     $form
     * @param int           $masterId
     * @param stdClass|null $existingRec
     *
     * @return string - replace|add
     */
    private static function getQuantityMode($mvc, $form, $masterId, $existingRec)
    {
        if (empty($existingRec)) {

            return 'replace';
        }

        // Без етапи се работи както преди - винаги замяна
        if (empty($mvc->getWbarcodeScanStage($masterId))) {

            return 'replace';
        }

        $mode = $form->rec->{self::MODE_FLD} ?? 'auto';
        if ($mode != 'auto') {

            return $mode;
        }

        // Първото сканиране на реда в етапа заменя пренесеното к-то, следващите го натрупват
        $scanned = static::getScannedIds($mvc, $masterId);

        return isset($scanned[$existingRec->id]) ? 'add' : 'replace';
    }


    /**
     * Ключ на сесийната памет със сканираните редове - при смяна на етапа тя се занулява
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     *
     * @return string
     */
    private static function getScanKey($mvc, $masterId)
    {
        $stage = $mvc->getWbarcodeScanStage($masterId);

        return "{$mvc->className}|{$masterId}|{$stage}";
    }


    /**
     * Ид-та на редовете, вече сканирани в текущия етап
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     *
     * @return array
     */
    private static function getScannedIds($mvc, $masterId)
    {
        $scanned = Mode::get(self::SCANNED_VAR);
        if (!is_array($scanned) || ($scanned['key'] ?? null) !== static::getScanKey($mvc, $masterId)) {

            return array();
        }

        return $scanned['ids'];
    }


    /**
     * Отбелязва реда като сканиран в текущия етап
     *
     * @param core_Detail $mvc
     * @param int         $id
     *
     * @return void
     */
    private static function markScanned($mvc, $id)
    {
        $rec = $mvc->fetch($id, "id,{$mvc->masterKey}");
        if (empty($rec)) {

            return;
        }

        $masterId = $rec->{$mvc->masterKey};
        $ids = static::getScannedIds($mvc, $masterId);
        $ids[$id] = $id;

        Mode::setPermanent(self::SCANNED_VAR, array('key' => static::getScanKey($mvc, $masterId), 'ids' => $ids));
    }


    /**
     * Общото к-во на сканираните досега редове, по мерки
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     *
     * @return string|null
     */
    private static function getScannedTotal($mvc, $masterId)
    {
        $ids = static::getScannedIds($mvc, $masterId);

        // При един сканиран ред общото е к-то, което вече се показва
        if (countR($ids) < 2) {

            return null;
        }

        $query = $mvc->getQuery();
        $query->where("#{$mvc->masterKey} = {$masterId}");
        $query->in('id', $ids);
        if ($mvc->getField('state', false)) {
            $query->where("#state != 'rejected'");
        }

        $sum = array();
        while ($rec = $query->fetch()) {
            if (!isset($rec->{$mvc->wbarcodeQuantityFld})) {

                continue;
            }

            $quantity = $rec->{$mvc->wbarcodeQuantityFld};
            $measureId = $rec->{$mvc->wbarcodePackagingFld};
            if (!isset($sum[$measureId])) {
                $sum[$measureId] = 0;
            }
            $sum[$measureId] += $quantity;
        }

        if (empty($sum)) {

            return null;
        }

        // Мерките не се събират една с друга, а се изреждат
        $Double = core_Type::getByName('double(smartRound)');
        $parts = array();
        foreach ($sum as $measureId => $quantity) {
            $parts[] = '<b>' . $Double->toVerbal($quantity) . '</b> ' . cat_UoM::getShortName($measureId);
        }

        return tr('|Общо сканирано|*:') . ' ' . implode(', ', $parts) . ' (' . countR($ids) . ' ' . tr('реда') . ')';
    }


    /**
     * Името на полето за тегло във формата за сканиране
     *
     * Взима се от wscales, за да може същото поле да се пълни и от електронна везна
     *
     * @param core_Detail $mvc
     *
     * @return string
     */
    private static function getWeightFld($mvc)
    {
        return $mvc->scaleWeightFieldName ?? 'weight';
    }


    /**
     * Полето, в което везната държи живото тегло
     *
     * @param core_Detail $mvc
     *
     * @return string
     */
    private static function getLiveWeightFld($mvc)
    {
        return $mvc->scaleLiveWeightFieldName ?? 'liveWeight';
    }


    /**
     * Добавя четенето от електронна везна към формата за сканиране, ако има такава
     *
     * @param core_Detail $mvc
     * @param core_ET     $tpl
     * @param core_Form   $form
     *
     * @return void
     */
    private static function appendScaleJs($mvc, &$tpl, $form)
    {
        // Мека зависимост - без пакета за везни се работи само с теглото от баркода
        if (!core_Packs::isInstalled('wscales')) {

            return;
        }

        wscales_Helper::appendJs($tpl, static::getWeightFld($mvc), static::getLiveWeightFld($mvc), $form->formAttr['id']);
    }


    /**
     * Записът, с който се проверяват правата за добавяне на нов ред
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     * @param string|null $type
     *
     * @return stdClass
     */
    private static function getRightsRec($mvc, $masterId, $type = null)
    {
        $res = (object) array($mvc->masterKey => $masterId);

        // Празен тип не се подава - иначе проверката би се направила за несъществуващ тип
        if (($typeFld = ($mvc->wbarcodeTypeFld ?? null)) && !empty($type)) {
            $res->{$typeFld} = $type;
        }

        return $res;
    }


    /**
     * Първият неоттеглен ред със същия артикул, мярка и тип в документа
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     * @param int         $productId
     * @param int         $measureId
     * @param string|null $type
     *
     * @return stdClass|null
     */
    private static function fetchExistingRec($mvc, $masterId, $productId, $measureId, $type)
    {
        $query = $mvc->getQuery();
        $query->where("#{$mvc->masterKey} = {$masterId} AND #{$mvc->wbarcodeProductFld} = {$productId}");

        // Мярката също участва, за да не се събират количества в различни мерни единици
        $query->where("#{$mvc->wbarcodePackagingFld} = {$measureId}");

        if ($typeFld = ($mvc->wbarcodeTypeFld ?? null)) {
            $query->where(array("#{$typeFld} = '[#1#]'", $type));
        }

        // Оттеглените редове не се броят
        if ($mvc->getField('state', false)) {
            $query->where("#state != 'rejected'");
        }

        $query->orderBy('id', 'ASC');
        $query->limit(1);

        $rec = $query->fetch();

        return is_object($rec) ? $rec : null;
    }


    /**
     * Сесийният код, с който формата за добавяне се събмитва автоматично
     *
     * Обвързан е със сесията, за да не може автоматичният запис да се пусне с копиран
     * от друг потребител адрес (@see Request::setProtected)
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     * @param int         $productId
     * @param int         $measureId
     * @param string|null $type
     * @param int|null    $recId - ид на реда при редактиране
     *
     * @return string
     */
    private static function getAutoHash($mvc, $masterId, $productId, $measureId, $type, $recId)
    {
        return Request::getSessHash("{$mvc->className}|{$masterId}|{$productId}|{$measureId}|{$type}|{$recId}", 8);
    }


    /**
     * Инфо за формата с данните на последно записания ред
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     *
     * @return string|null
     */
    private static function getLastRowInfo($mvc, $masterId)
    {
        $lastId = Request::get(self::LAST_PARAM, 'int');
        if (empty($lastId)) {

            return null;
        }

        $rec = $mvc->fetch($lastId);
        if (empty($rec) || $rec->{$mvc->masterKey} != $masterId) {

            return null;
        }

        // Кодът на артикула е линк към единичния му изглед, а името му остава текст
        $productId = $rec->{$mvc->wbarcodeProductFld};
        $productRec = cat_Products::fetchRec($productId, 'id,code,isPublic');
        cat_Products::setCodeIfEmpty($productRec);
        $code = ht::createLink('[' . cat_Products::getVerbal($productRec, 'code') . ']', cat_Products::getSingleUrlArray($productId));
        $name = cat_Products::getVerbal($productId, 'name');

        $Double = core_Type::getByName('double(smartRound)');
        $quantity = $Double->toVerbal($rec->{$mvc->wbarcodeQuantityFld});
        $measure = cat_UoM::getShortName($rec->{$mvc->wbarcodePackagingFld});

        $last = Mode::get(self::CODE_VAR);
        if (!is_array($last)) {
            $last = array();
        }
        $barcode = !empty($last['code']) ? '<b>' . core_Type::escape($last['code']) . '</b> &rarr; ' : '';

        // При натрупване се показва и сканираното к-во, за да е ясно от какво е станало общото
        $added = '';
        if (!empty($last['mode']) && $last['mode'] == 'add' && !empty($last['quantity'])) {
            $added = '<b>+' . $Double->toVerbal($last['quantity']) . "</b> {$measure} &rarr; " . tr('общо') . ' ';
        }

        return "<div>" . tr('|Последно|*:') . " {$barcode}{$code} {$name} &rarr; {$added}<b>{$quantity}</b> {$measure}</div>";
    }


    /**
     * Инфото на формата за сканиране - последният ред и общото сканирано к-во
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     *
     * @return string|null
     */
    private static function getScanInfo($mvc, $masterId)
    {
        $info = static::getLastRowInfo($mvc, $masterId);

        $total = static::getScannedTotal($mvc, $masterId);
        if (!empty($total)) {
            $info .= "<div>{$total}</div>";
        }

        if (empty($info)) {

            return null;
        }

        return "<div class='formCustomInfo'>{$info}</div>";
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        // Така кодът се чете само от защитената част на адреса, а не от ръчно подаден параметър
        Request::setProtected(self::AUTO_PARAM);

        $hash = Request::get(self::AUTO_PARAM, 'varchar');
        if (empty($hash) || Request::get('Cmd') !== null) {

            return;
        }

        $rec = $data->form->rec;
        $typeFld = $mvc->wbarcodeTypeFld ?? null;
        $type = !empty($typeFld) ? ($rec->{$typeFld} ?? null) : null;
        $expected = static::getAutoHash($mvc, $rec->{$mvc->masterKey}, $rec->{$mvc->wbarcodeProductFld}, $rec->{$mvc->wbarcodePackagingFld}, $type, $rec->id ?? null);
        if ($hash !== $expected) {

            return;
        }

        // Формата се "излъгва", че е събмитната, за да мине пълния път на валидациите и записа
        $data->form->_wbarcodeRestore = array($data->form->cmd ?? null, $data->form->method ?? null);
        $data->form->cmd = 'save';
        $data->form->method = $_SERVER['REQUEST_METHOD'];
    }


    /**
     * Ако автоматичният запис не е минал, потребителят остава на формата за добавяне
     */
    public static function on_AfterPrepareEditToolbar($mvc, $res, $data)
    {
        if (empty($data->form->_wbarcodeRestore)) {

            return;
        }

        list($data->form->cmd, $data->form->method) = $data->form->_wbarcodeRestore;
        unset($data->form->_wbarcodeRestore);
    }


    /**
     * След записа се показва какво е добавено на формата за сканиране
     */
    public static function on_AfterPrepareRetUrl($mvc, $res, $data = null, $id = null)
    {
        if (empty($id) || !is_array($data->retUrl ?? null)) {

            return;
        }

        if (strtolower($data->retUrl['Ctr'] ?? '') != strtolower($mvc->className)) {

            return;
        }

        if (strtolower($data->retUrl['Act'] ?? '') != strtolower(self::ACTION)) {

            return;
        }

        static::markScanned($mvc, $id);
        $data->retUrl[self::LAST_PARAM] = $id;
    }
}
