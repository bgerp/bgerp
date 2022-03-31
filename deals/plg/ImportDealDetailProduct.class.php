<?php


/**
 * Плъгин за импорт на артикули към бизнес документи. Закача се към техен детайл който има интерфейс 'deals_DealImportCsvIntf'
 *
 * Целта е да се уточни:
 * 1. Как се въвеждат csv данните с ъплоуд на файл или с copy & paste
 * 2. Какви са разделителят, ограждането и първия ред на данните
 * 3. Кои колони от csv-то на кои полета от мениджъра отговарят.
 *
 * След определянето на тези данни драйвъра се грижи за правилното импортиране
 *
 * Мениджъра в който ще се импортира и кои полета от него ще бъдат попълнени
 * се определя от драйвъра.
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_ImportDealDetailProduct extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('deals_DealImportProductIntf');
        setIfNot($mvc->allowPriceImport, true);
    }
    
    
    /**
     * Преди всеки екшън на мениджъра-домакин
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if ($action == 'import') {
            $mvc->requireRightFor('import');
            expect($masterId = Request::get($mvc->masterKey, 'int'));
            $masterRec = $mvc->Master->fetch($masterId);
            $mvc->requireRightFor('import', (object) array($mvc->masterKey => $masterId));
            
            $mvc->requireRightFor('import');
            $form = cls::get('core_Form');
            
            $cu = core_Users::getCurrent();

            // Подготвяме формата
            $form->FLD($mvc->masterKey, "key(mvc={$mvc->Master->className})", 'input=hidden,silent');
            if($mvc instanceof sales_QuotationsDetails){
                $form->FLD('optional', 'enum(yes,no)', 'silent,input=hidden');
            }

            $form->input(null, 'silent');
            $form->title = 'Импортиране на артикули към|*' . ' <b>' . $mvc->Master->getFormTitleLink($masterRec) . '</b>';
            $form->FLD('folderId', 'int', 'input=hidden');
            $form->setDefault('folderId', $masterRec->folderId);
            
            self::prepareForm($form, $mvc);

            if (isset($form->rec->fromClipboard)) {
                list($isFromClipboard) = explode('_', $form->rec->fromClipboard);
            }

            $cacheRec = core_Cache::get($mvc->className, "importProducts_{$cu}_{$isFromClipboard}");

            if ($cacheRec) {
                foreach ($cacheRec as $name => $value) {
                    $form->rec->{$name} = $value;
                }
            }

            $form->input();
            
            // Ако формата е импутната
            if ($form->isSubmitted()) {
                $rec = &$form->rec;

                if (empty($rec->csvData) && empty($rec->csvFile) && empty($rec->fromClipboard)) {
                    $form->setError('csvData,csvFile,fromClipboard', 'Трябва да е попълнено поне едно от полетата');
                }

                if ((!empty($rec->csvData) + !empty($rec->csvFile) + !empty($rec->fromClipboard)) >= 2) {
                    $form->setError('csvData,csvFile,fromClipboard', 'Трябва да е попълнено само едно от полетата');
                }

                $isFromClipboard = false;

                if (!$form->gotErrors()) {
                    if (!$rec->fromClipboard) {
                        $data = ($rec->csvFile) ? bgerp_plg_Import::getFileContent($rec->csvFile) : $rec->csvData;

                        $delimiter = $rec->delimiter == '\t' ? "\t" : $rec->delimiter;

                        // Обработваме данните
                        $rows = csv_Lib::getCsvRows($data, $delimiter, $rec->enclosure, $rec->firstRow);
                    } else {
                        $clipboardValsArr = export_Clipboard::getVals();

                        list($clipboardClass, $clipboardObjId) = explode('_', $rec->fromClipboard);

                        $rows = (array) $clipboardValsArr[$clipboardClass][$clipboardObjId]->recs;

                        $isFromClipboard = true;
                    }

                    $fields = array('code' => $rec->codecol, 'quantity' => $rec->quantitycol, 'price' => $rec->pricecol, 'pack' => $rec->packcol);
                    
                    if (core_Packs::isInstalled('batch') && !($mvc instanceof sales_QuotationsDetails)) {
                        $fields['batch'] = $rec->batchcol;
                    }
                    
                    if (!countR($rows)) {
                        $form->setError('csvData,csvFile,fromClipboard', 'Не са открити данни за импорт');
                    }

                    if ($isFromClipboard) {
                        foreach ($fields as $fKey => $fVal) {
                            if (isset($fVal) && isset($form->_cMap[$fVal])) {
                                $fields[$fKey] = $form->_cMap[$fVal];
                            }
                        }
                    }

                    // Ако можем да импортираме импортираме
                    if ($mvc->haveRightFor('import')) {

                        // Обработваме и проверяваме данните
                        $errArr = self::checkRows($rows, $fields, $rec->folderId, $mvc);

                        if (!empty($errArr)) {
                            if ($isFromClipboard && $clipboardClass) {

                                $j = cls::get($clipboardClass)->getLinkToSingle($clipboardObjId);

                                $msg = "|Има проблем със следните записи от|* {$j}:";

                                $msg .= '<ul>';

                                foreach ($errArr as $r) {
                                    $errMsg = implode(', ', $r);

                                    $msg .= "|*<li>{$errMsg}" . '</li>';
                                }
                                $msg .= '</ul>';

                                $form->setError('fromClipboard', $msg);
                            } else {
                                $msg = '|Има проблем със следните редове|*:';
                                $msg .= '<ul>';
                                foreach ($errArr as $j => $r) {
                                    $errMsg = implode(', ', $r);
                                    $msg .= "|*<li>|Ред|* '{$j}' : {$errMsg}" . '</li>';
                                }
                                $msg .= '</ul>';

                                $form->setError('csvData', $msg);
                            }
                        }

                        if (!$form->gotErrors()) {
                            if($mvc instanceof sales_QuotationsDetails){
                                array_walk($rows, function($a) use ($form){ $a->optional = $form->rec->optional;});
                            }

                            // Импортиране на данните от масива в зададените полета
                            $msg = self::importRows($mvc, $rec->{$mvc->masterKey}, $rows, $fields);
                            
                            self::cacheImportParams($mvc, $rec);
                            $mvc->Master->logWrite('Импортиране на артикули', $rec->{$mvc->masterKey});
                            
                            // Редирект кум мастъра на документа към който ще импортираме
                            redirect(array($mvc->Master, 'single', $rec->{$mvc->masterKey}), false, $msg);
                        }
                    }
                }
            }
            
            if (core_Users::haveRole('partner')) {
                $mvc->currentTab = 'Нишка';
                plg_ProtoWrapper::changeWrapper($mvc, 'cms_ExternalWrapper');
            }
            
            // Рендиране на опаковката
            $tpl = $mvc->renderWrapping($form->renderHtml());
            core_Form::preventDoubleSubmission($tpl, $form);
            
            return false;
        }
    }
    
    
    /**
     * Проверява и обработва записите за грешки
     */
    private static function checkRows(&$rows, $fields, $folderId, $mvc)
    {
        $err = array();
        $msg = false;

        $isPartner = core_Users::haveRole('partner');
        $batchInstalled = core_Packs::isInstalled('batch');
        foreach ($rows as $i => &$row) {

            // Подготвяме данните за реда
            $obj = (object) array('code' => $row[$fields['code']],
                'quantity' => $row[$fields['quantity']],
                'pack' => ($row[$fields['pack']]) ? $row[$fields['pack']] : null,
                'price' => $row[$fields['price']] ? $row[$fields['price']] : null,
                'batch' => ($row[$fields['batch']]) ? $row[$fields['batch']] : null,
            );
            
            // Подсигуряваме се, че подадените данни са във вътрешен вид
            $obj->code = cls::get('type_Varchar')->fromVerbal($obj->code);
            $obj->quantity = cls::get('type_Double')->fromVerbal($obj->quantity);
            
            if (!strlen($obj->code)) {
                $err[$i][] = $obj->code . ' |Липсващ код|*';
                continue;
            }
            
            $pRec = cat_Products::getByCode($obj->code);

            if (!$pRec) {
                $err[$i][] = $obj->code . ' |Няма артикул с такъв код|*';
                continue;
            }

            $masterId = Request::get($mvc->masterKey, 'int');
            $metaArr = arr::make($mvc->metaProducts, true);
            if(!countR($metaArr)){
                $masterRec = $mvc->Master->fetch($masterId);
                if($mvc instanceof planning_DirectProductNoteDetails){
                    $metaArr = array('canConvert' => 'canConvert');
                } else {
                    if(isset($masterRec->originId)){
                        $Document = doc_Containers::getDocument($masterRec->originId);
                        if ($Document->className == 'sales_Sales') {
                            $metaArr = array('canSell' => 'canSell');
                        } elseif ($Document->className == 'purchase_Purchases') {
                            $metaArr = array('canBuy' => 'canBuy');
                        }
                    } elseif($mvc instanceof store_TransfersDetails){
                        $metaArr = array('canStore' => 'canStore');
                    }
                }
            }

            $metaString = implode(',', $metaArr);
            $productRec = cat_Products::fetch($pRec->productId, "state,isPublic,folderId,{$metaString}");
            if ($productRec->state != 'active') {
                $err[$i][] = $obj->code . ' - |Артикулът не е активен|*!';
                continue;
            }

            $meta = (array) $productRec;
            unset($meta['id'], $meta['state'], $meta['isPublic'], $meta['folderId']);

            // Ако артикула е нестандартен, проверява се все пак може ли да се добави в папката на документа
            if($productRec->isPublic != 'yes'){
                $productSharedFolders = cat_products_SharedInFolders::getSharedFolders($productRec->id);
                if(!in_array($folderId, $productSharedFolders)){
                    $err[$i][] = $obj->code . ' - |Артикулът е частен и не е достъпен в папката на документа|*!';
                    continue;
                }
            }

            foreach ($meta as $metaValue) {
                if ($metaValue != 'yes') {
                   $err[$i][] = $obj->code . ' - |Артикулът няма вече нужните свойства|*!';
                }
            }
            
            $packs = cat_Products::getPacks($pRec->productId);

            if (isset($obj->pack)) {
                $obj->exPack = $obj->pack;
                $packId = is_numeric($obj->pack) ? $obj->pack : cat_UoM::fetchBySinonim($obj->pack)->id;

                if (!$packId) {
                    foreach ($packs as $pId => $pName) {
                        if (strpos($obj->pack, $pName) !== false) {
                            $packId = $pId;
                            break;
                        }
                    }
                }
                if ($packId) {
                    $obj->pack = $packId;
                }
            } else {
                $obj->pack = ($pRec->packagingId) ? $pRec->packagingId : key($packs);
            }
            
            if ($obj->price) {
                if ($isPartner === false) {
                    $obj->price = cls::get('type_Double')->fromVerbal($obj->price);
                    if (!$obj->price) {
                        $err[$i][] = $obj->code . ' - |Грешна цена|*!';
                    }
                }
            } else {
                if($priceField = $mvc->getField('packPrice', false)){
                    if($priceField->mandatory){
                        $err[$i][] = $obj->code . ' - |Посочването на цена е задължително|*!';
                    }
                }
            }

            if (!isset($obj->price)) {
                $Cover = doc_Folders::getCover($folderId);
                $Policy = (isset($mvc->Policy)) ? $mvc->Policy : cls::get('price_ListToCustomers');
                $policyInfo = $Policy->getPriceInfo($Cover->getInstance()->getClassId(), $Cover->that, $pRec->productId, null, 1);
                
                if ($Document) {
                    $Document = cls::get($Document->className);
                }
                
                //Ако документа е в покупка не искаме ценова политика
                if ($Document instanceof sales_Sales ||
                    $mvc->Master instanceof sales_Sales) {
                    if (empty($policyInfo->price)) {
                        $err[$i][] = $obj->code . ' |Артикулът няма цена|*';
                    }
                }
                
                if ($Document instanceof purchase_Purchases || 
                    $mvc->Master instanceof purchase_Purchases) {
                    
                    // Себестойност
                    $selfPrice = cat_Products::getPrimeCost($pRec->productId, null, null, null);
                   
                    if (!$selfPrice) {
                        $err[$i][] = $obj->code . ' - |Артикулът няма себестойност|*!';
                    }
                }
            }
            
            if (!isset($obj->quantity)) {
                $obj->quantity = 1;
            }
            
            if ($pRec && isset($obj->pack)) {
                if (isset($pRec->packagingId) && $pRec->packagingId != $obj->pack) {
                    $err[$i][] = $obj->code . ' - |Подадения баркод е за друга опаковка|*!';
                }

                if (!array_key_exists($obj->pack, $packs)) {
                    $err[$i][] = $obj->code . ' - |Артикулът не поддържа подадената мярка/опаковка|* (' . $obj->pack . ')!';
                }
            }
            
            // Проверка за точност на к-то
            if (isset($obj->quantity)) {
                if ($pRec) {
                    $obj->quantity = cls::get('type_Double')->fromVerbal($obj->quantity);
                    if (!$obj->quantity) {
                        if(!$mvc->hasPlugin('store_plg_RequestDetail')){
                            $err[$i][] = $obj->code . '|Невалидно количество|*';
                        }
                    } else {
                        $packagingId = isset($pRec->packagingId) ? $pRec->packagingId : cat_Products::fetchField($pRec->productId, 'measureId');
                        $warning = null;
                        if (!deals_Helper::checkQuantity($packagingId, $obj->quantity, $warning)) {
                            $err[$i][] = $warning;
                        }
                    }
                }
            }
            
            // Ако е инсталиран пакета за партидност и има партида
            if ($batchInstalled && isset($obj->batch, $pRec->productId)) {
                if ($batchDef = batch_Defs::getBatchDef($pRec->productId)) {
                    $batchType = $batchDef->getBatchClassType();
                    $obj->batch = $batchType->fromVerbal($obj->batch);
                    $r = $batchType->isValid($obj->batch);
                    
                    if (!$obj->batch || !empty($r['error'])) {
                        $error = !empty($r['error']) ? $r['error'] : $batchType->error;
                        $err[$i][] = $obj->batch . " |{$error}|*";
                        continue;
                    }
                    
                    $obj->batch = $batchDef->denormalize($obj->batch);
                    if (!$batchDef->isValid($obj->batch, $obj->quantity, $msg)) {
                        $msg = str_replace(',', ' ', $msg);
                        $err[$i][] = $obj->batch . " {$msg}";
                        continue;
                    }
                } else {
                    $err[$i][] = $obj->batch . ' - |Продукта не поддържа партидност|*!';
                    continue;
                }
            }
            
            if ($isPartner === true) {
                unset($obj->price);
            }
            
            $row = clone $obj;
        }

        return $err;
    }
    
    
    /**
     * Импортиране на записите ред по ред от мениджъра
     */
    private static function importRows($mvc, $masterId, $rows, $fields)
    {
        $added = $failed = 0;
        
        foreach ($rows as $row) {
         
            // Опитваме се да импортираме записа
            try {
                if ($mvc->import($masterId, $row)) {
                    $added++;
                }
            } catch (core_exception_Expect $e) {
                $failed++;
                $mvc->logNotice('Грешка при импорт: ' . $e->getMessage());
                if (haveRole('debug')) {
                    status_Messages::newStatus('Грешка при импорт: ' . $e->getMessage(), 'error');
                }
            }
        }
        
        $msg = ($added == 1) ? '|Импортиран е|* 1 |артикул|*' : "|Импортирани са|* {$added} |артикула|*";
        if ($failed != 0) {
            $msg .= ". |Не са импортирани|* {$failed} |артикула";
        }

        return $msg;
    }
    
    
    /**
     * Кешира данните от последното импортиране на потребителя за документа
     */
    private static function cacheImportParams($mvc, $rec)
    {
        $cu = core_Users::getCurrent();

        if (isset($rec->fromClipboard)) {
            list($isFromClipboard) = explode('_', $rec->fromClipboard);
        }

        $key = "importProducts_{$cu}_{$isFromClipboard}";

        core_Cache::remove($mvc->className, $key);
        $nRec = (object) array('delimiter' => $rec->delimiter,
            'enclosure' => $rec->enclosure,
            'firstRow' => $rec->firstRow,
            'codecol' => $rec->codecol,
            'quantitycol' => $rec->quantitycol,
            'pricecol' => $rec->pricecol);
        
        
        core_Cache::set($mvc->className, $key, $nRec, 1440);
    }
    
    
    /**
     * Подготовка на формата за импорт на артикули
     *
     * @param core_Form $form
     */
    private static function prepareForm(&$form, $mvc)
    {
        // Полета за орпеделяне на данните
        $form->info = tr('Въведете данни или качете csv файл');
        $form->FLD('csvData', 'text(1000000)', 'width=100%,caption=Данни');
        $form->FLD('csvFile', 'fileman_FileType(bucket=bnav_importCsv)', 'width=100%,caption=CSV файл');

        $clipboardValsArr = export_Clipboard::getVals();

        if ($clipboardValsArr) {

            $docArr = array();
            foreach ($clipboardValsArr as $clsId => $clsObjArr) {
                if (!cls::load($clsId, true)) {

                    continue;
                }

                $clsInst = cls::get($clsId);
                foreach ($clsObjArr as $objId => $recs) {
                    $dRow = $clsInst->getDocumentRow($objId);
                    $title = $dRow->recTitle ? $dRow->recTitle : $dRow->title;

                    $docArr["{$clsId}_{$objId}"] = $title;
                }
            }

            if (!empty($docArr)) {
                $enum = cls::get('type_Enum');

                $options = array('' => '') + $docArr;

                $enum->options = $options;

                $form->FLD('fromClipboard', $enum, 'width=100%,caption=От клипборда, removeAndRefreshForm=csvData|csvFile|delimiter|enclosure|firstRow|codecol|quantitycol|packcol|pricecol|batchcol, silent');
            }
        }

        if ($form->cmd == 'refresh') {
            $form->input('fromClipboard');
        } else {
            $form->input(null, true);
        }

        $isFromClipboard = false;

        if ($form->rec->fromClipboard) {
            $isFromClipboard = true;
        }

        $unit = '';
        $type = 'enum()';
        if (!$isFromClipboard) {
            // Настройки на данните
            $form->FLD('delimiter', 'varchar(1,size=5)', 'width=100%,caption=Настройки->Разделител,maxRadio=5,placeholder=Автоматично');
            $form->FLD('enclosure', 'varchar(1,size=3)', 'width=100%,caption=Настройки->Ограждане,placeholder=Автоматично');
            $form->FLD('firstRow', 'enum(,data=Данни,columnNames=Имена на колони)', 'width=100%,caption=Настройки->Първи ред,placeholder=Автоматично');

            $form->setSuggestions('delimiter', array('' => '', ',' => ',', ';' => ';', ':' => ':', '|' => '|', '\t' => 'Таб'));

            $form->setSuggestions('enclosure', array('' => '', '"' => '"', '\'' => '\''));
            $form->setDefault('delimiter', ',');
            $form->setDefault('enclosure', '"');

            $unit = ",unit=колона";
            $type = 'int';
        }



        // Съответстващи колонки на полета
        $form->FLD('codecol', $type, "caption=Съответствие в данните->Код{$unit},mandatory");
        $form->FLD('quantitycol', $type, "caption=Съответствие в данните->Количество{$unit},mandatory");
        $form->FLD('packcol', $type, "caption=Съответствие в данните->Мярка/Опаковка{$unit},mandatory");
        
        $fields = array('codecol', 'quantitycol', 'packcol');
        if (!core_Users::haveRole('partner') && $mvc->allowPriceImport) {
            $form->FLD('pricecol', $type, "caption=Съответствие в данните->Цена{$unit}");
            $fields[] = 'pricecol';
        }
        
        if (core_Packs::isInstalled('batch') && !($mvc instanceof sales_QuotationsDetails)) {
            $form->FLD('batchcol', $type, "caption=Съответствие в данните->Партида{$unit}");
            $fields[] = 'batchcol';
        }

        if (!$isFromClipboard) {
            foreach ($fields as $i => $fld) {
                $form->setSuggestions($fld, array(1 => 1,2 => 2,3 => 3,4 => 4,5 => 5,6 => 6,7 => 7));
                $form->setDefault($fld, $i + 1);
            }
        } else {
            list($clsId, $objId) = explode('_', $form->rec->fromClipboard);

            $fElemKey = key($clipboardValsArr[$clsId][$objId]->recs);
            $fElemArr = $clipboardValsArr[$clsId][$objId]->recs[$fElemKey];

            if ($fElemArr) {

                $fArr = array('' => '');
                $cMap = array();
                foreach ((array)$fElemArr as $fName => $fVal) {
                    if ($clipboardValsArr[$clsId][$objId]->fields->fields[$fName]->caption) {
                        $caption = $clipboardValsArr[$clsId][$objId]->fields->fields[$fName]->caption;
                        $fArr[$caption] = $caption;
                        $cMap[$caption] = $fName;
                    } else {
                        $caption = $fName;
                        $fArr[$fName] = $fName;
                        $cMap[$fName] = $fName;
                    }

                    if (mb_stripos($form->fields['codecol']->caption, $caption) !== false) {
                        $form->setDefault('codecol', $caption);
                    }

                    if (mb_stripos($form->fields['quantitycol']->caption, $caption) !== false) {
                        $form->setDefault('quantitycol', $caption);
                    }

                    if (mb_stripos($form->fields['packcol']->caption, $caption) !== false) {
                        $form->setDefault('packcol', $caption);
                    }
                }

                $form->_cMap = $cMap;

                foreach ($fields as $i => $fld) {
                    $form->setOptions($fld, $fArr);
                }
            }
        }

        $form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/import.png, title = Импорт');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $masterRec = $data->masterData->rec;
        $error = '';
        if ($mvc->haveRightFor('import', (object) array("{$mvc->masterKey}" => $masterRec->id))) {
            $data->toolbar->addBtn(
                'Импортиране (CSV)',
                array($mvc, 'import', "{$mvc->masterKey}" => $masterRec->id, 'ret_url' => true),
            "id=btnAdd-import,title=Импортиране на артикули от CSV",
                "ef_icon = img/16/import.png,order=15,{$error}"
            );
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        
        if ($action == 'import' && isset($rec->{$mvc->masterKey})) {
            if ($mvc instanceof sales_SalesDetails) {
                $roles = sales_Setup::get('ADD_BY_IMPORT_BTN');
                if (!haveRole($roles, $userId)) {
                    $requiredRoles = 'no_one';
                }
            } else {
                if (!$mvc->haveRightFor('add', $rec)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
