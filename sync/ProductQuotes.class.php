<?php


/**
 * Импортиране на артикули от друга Bgerp система
 *
 *
 * @category  bgerp
 * @package   sync
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Импортиране на артикули от друга Bgerp система
 */
class sync_ProductQuotes extends core_BaseClass
{
    /**
     * Граници за legacy product payload преди unserialize
     */
    const MAX_ENCODED_PAYLOAD_SIZE = 16777216;
    const MAX_UNCOMPRESSED_PAYLOAD_SIZE = 33554432;
    const MAX_PAYLOAD_COLLECTION_ITEMS = 10000;


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Импортира артикул от друга Bgerp система
     */
    public function act_Import()
    {
        // Валидираме идентификацията на викащата система (syncSysId + syncPass)
        $settingsRec = sync_Helper::requireRight('export');
        expect(
            $settingsRec &&
            ($settingsRec->allowProductPush ?? 'no') == 'yes' &&
            sync_Settings::getExportMode($settingsRec, 'products') == 'all',
            'Legacy product push не е разрешен за този client'
        );
        expect(
            !empty($settingsRec->productPushSourceUrl),
            'Липсва доверен source URL за legacy product push'
        );
        $otherPushSource = sync_Settings::fetch(
            array(
                "#id != [#1#] AND #state = 'active' AND #allowProductPush = 'yes'",
                $settingsRec->id ?? null
            )
        );
        expect(
            !$otherPushSource,
            'Legacy product push е разрешен за повече от една source система'
        );
        expect(
            sync_Helper::isTrustedOriginUrl($settingsRec->productPushSourceUrl),
            'Невалиден доверен HTTP/HTTPS source URL за legacy product push'
        );

        $res = new stdClass();
        
        try{
            // Кое отдалечено ид ще се очаква за импорт
            $remoteId = Request::get('remoteId', 'int');
            if (!$remoteId || $remoteId < 1 || $remoteId > sync_Map::MAX_REMOTE_ID) {
                throw new core_exception_Expect('Невалидно remoteId', 'Несъответствие');
            }
            
            // Проверка дали вече не е импортирано
            $localId = sync_Map::getLocalId('cat_Products', $remoteId);
            if(empty($localId)){
                $data = Request::get('data', false);
                $sourceUrl = trim((string) $settingsRec->productPushSourceUrl);
                if (!$data) {
                    throw new core_exception_Expect(
                        'Липсва директен product payload; обновете изпращащата система',
                        'Несъответствие'
                    );
                }

                core_Users::forceSystemUser();
                try {
                    $localId = self::import($data, $sourceUrl);
                    if(!$localId){
                        throw new core_exception_Expect('Проблем при импортирането на артикул', 'Несъответствие');
                    }

                    sync_Map::add('cat_Products', $localId, $remoteId);
                } finally {
                    core_Users::cancelSystemUser();
                }

                $res->status = 1;
            } else {
                $res->status = 2;
            }
            $res->localId = $localId;
            $res->url = toUrl(array('cat_Products', 'single', $localId), 'absolute');
            
        } catch (core_exception_Expect $e){
            
            // Ако има грешка по експорта показва се
            $res->localId = null;
            $res->error = $e->getMessage();
            $res->status = 3;
            reportException($e);
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Throwable $e) {
            $res->localId = null;
            $res->error = 'Невалиден или непълен product payload';
            $res->status = 3;
            reportException($e);
        }
        
        // Връщане на обекта с резултата на импорта
        echo json_encode($res);
        shutdown();
    }
    
    
    /**
     * Импорт на артикула по подадените данни за експорт.
     * Артикулът влиза с драйвер cat_ImportedProductDriver
     * @see cat_ImportedProductDriver
     * 
     * @param string $data
     * @param string $sourceUrl
     * 
     * @return int $productId
     */
    private static function import($data, $sourceUrl)
    {
        // Разкриптиране на данните за импорт
        expect(
            is_string($data) && strlen($data) <= self::MAX_ENCODED_PAYLOAD_SIZE,
            'Product payload надвишава разрешения размер'
        );
        $decoded = base64_decode($data, true);
        expect($decoded !== false, 'Невалиден product payload');
        $uncompressed = @gzuncompress($decoded, self::MAX_UNCOMPRESSED_PAYLOAD_SIZE);
        expect($uncompressed !== false, 'Невалиден компресиран product payload');
        $data = @unserialize($uncompressed, array('allowed_classes' => array('stdClass')));
        expect(is_object($data), 'Невалидни product данни');
        foreach (array('params', 'packagings', 'quotations') as $collection) {
            expect(
                count((array) ($data->{$collection} ?? array())) <=
                    self::MAX_PAYLOAD_COLLECTION_ITEMS,
                "Прекалено много записи в product payload: {$collection}"
            );
        }
        $data->exportUrl = $sourceUrl;
        expect(
            ($data->contragentClassName ?? null) === 'crm_Companies',
            'Невалиден контрагент в product payload'
        );
        
        // Импортиране на контрагента, ако е нужно
        $exportContragentRes = (array) ($data->exportContragentRes ?? array());
        $controller = cls::get('sync_Companies');
        $controller->invoke('BeforeSyncImportAll', array(&$exportContragentRes, $controller, null));
        Mode::push('syncTrustedFileOrigin', $sourceUrl);
        try {
            $localContragentId = sync_Map::importRec(
                $data->contragentClassName,
                $data->contragentRemoteId ?? null,
                $exportContragentRes,
                $controller,
                null
            );
        } finally {
            Mode::pop('syncTrustedFileOrigin');
        }
        if(!$localContragentId){
            throw new core_exception_Expect('Проблем при импортирането на контрагента', 'Несъответствие');
        }
        
        // Подмяна на линковете за сваляне на файловете от хтмл-а
        $data->html = $data->html ?? '';
        $data->htmlEn = $data->htmlEn ?? '';
        $matches = array();
        preg_match_all('/http.*?forceDownload=1/', $data->html, $matches);
        if (countR($matches[0])) {
            foreach ($matches[0] as $downloadFileUrl){
                
                // Ако е открит линк за сваляне на файл, файла се сваля и абсорбира в системата
                $fileContent = sync_Helper::fetchFileFromTrustedOrigin(
                    $downloadFileUrl,
                    $sourceUrl
                );
                $newFh = fileman::absorbStr($fileContent, 'importedProductFiles', 'fh');
                expect($newFh, 'Проблем при запис на файл от product payload');

                // Урл-то за сваляне, се подменя с такова за сваляне в приемащата система
                $singleFileUrl = toUrl(array('fileman_Files', 'single', $newFh));
                $data->html = str_replace($downloadFileUrl, $singleFileUrl, $data->html);
                $data->htmlEn = str_replace($downloadFileUrl, $singleFileUrl, $data->htmlEn);
            }
        }
        
        // Мапване на контрагента, и форсиране на папка
        $folderId = cls::get($data->contragentClassName)->forceCoverAndFolder($localContragentId);
        
        // Проверка има ли я мапната основната мярка в системата, ако не се импортира при нужда и мапва
        expect(is_object($data->measureRec ?? null) && !empty($data->measureRec->id), 'Липсва основна мярка в product payload');
        $localBaseMeasureId = sync_Map::getLocalId('cat_UoM', $data->measureRec->id);
        if(!$localBaseMeasureId){
            $newBaseUomRec = clone $data->measureRec;
            unset($newBaseUomRec->id);
            
            $localBaseMeasureId = cat_UoM::fetchBySinonim($newBaseUomRec->name ?? '')->id ?? null;
            if(!$localBaseMeasureId){
                $localBaseMeasureId = cat_UoM::save($newBaseUomRec);
           }
           
           sync_Map::add('cat_UoM', $localBaseMeasureId, $data->measureRec->id);
        }
        
        // Попълват се данните на драйвера за импортиран артикул
        $productRec = (object)array('name' => $data->name ?? null,
            'nameEn' => $data->nameEn ?? null,
            'innerClass' => cat_ImportedProductDriver::getClassId(),
            'html' => $data->html,
            'htmlEn' => $data->htmlEn,
            'measureId' => $localBaseMeasureId,
            'meta' => $data->meta ?? null,
            'quotations' => $data->quotations ?? array(),
            'folderId' => $folderId,
            'importedFromDomain' => $data->exportUrl,
            'moq' => $data->moq ?? null,
            'conditions' => $data->conditions ?? array(),
        );
        
        // Импортиране на параметри
        $productRec->params = array();
        $data->params = (array) ($data->params ?? array());
        foreach ($data->params as $obj){
            expect(is_object($obj->paramRec ?? null) && !empty($obj->remoteId), 'Невалиден параметър в product payload');
            
            // Мапване на параметъра
            $localParamId = sync_Map::getLocalId('cat_Params', $obj->remoteId);
            $paramRec = $obj->paramRec;
            
            // Ако няма такъв се създава и мапва
            if(!$localParamId){
                $localParamId = cat_Params::force($paramRec->sysId ?? null, $paramRec->name ?? '', $paramRec->driverClass ?? null, $paramRec->options ?? array(), $paramRec->suffix ?? null, $paramRec->showInTasks ?? false);
                sync_Map::add('cat_Params', $localParamId, $obj->remoteId);
            }
            
            // Ако има намерен параметър и той е с драйвер за качен файл
            if(isset($localParamId)){
                if(in_array($paramRec->driverClass ?? null, array('cond_type_File', 'cond_type_Image'))){
                    
                    // Абсорбиране на файла от урл-то за сваляне и подмяна с хендлъра към новия файл
                    $fileContent = sync_Helper::fetchFileFromTrustedOrigin(
                        $obj->value ?? '',
                        $sourceUrl
                    );
                    $fileName = basename(parse_url($obj->value ?? '', PHP_URL_PATH) ?? '');
                    $obj->value = fileman::absorbStr(
                        $fileContent,
                        'importedProductFiles',
                        $fileName ?: 'file'
                    );
                    expect($obj->value, 'Проблем при запис на параметър-файл');
                } elseif(($paramRec->driverClass ?? null) == 'cond_type_Store'){
                    continue;
                }
               
                // Записване на стойността на параметъра, съответстваща на локалния ключ
                $productRec->params[$localParamId] = $obj->value ?? null;
            }
        }
        
        $productRec->quotations = $data->quotations ?? array();
        
        // Артикулът се създава
        $Products = cls::get('cat_Products');
        $Products->route($productRec);
        $Products->save($productRec);
        $Products->logWrite('Импортиране от друга Bgerp система', $productRec->id ?? null);
        $productId = $productRec->id ?? null;
        
        // Ако е създаден артикул и има опаковки за импорт
        if(isset($productId)){
            if(countR($data->packagings ?? null)){
                foreach ($data->packagings as $packObject){
                    expect(is_object($packObject->uomRec ?? null) && is_object($packObject->rec ?? null) && !empty($packObject->remoteId), 'Невалидна опаковка в product payload');
                    
                    // Мапване на опаковката
                    $localPackagingId = sync_Map::getLocalId('cat_UoM', $packObject->remoteId);
                    
                    // Ако не е мапната и не съществува се форсира нова
                    if(!$localPackagingId){
                        $newUomRec = $packObject->uomRec;
                        $localPackagingId = cat_UoM::fetchBySinonim($newUomRec->name ?? '')->id ?? null;
                        if(!$localPackagingId){
                            $localPackagingId = cat_UoM::save($newUomRec);
                        }
                        
                        sync_Map::add('cat_UoM', $localPackagingId, $packObject->remoteId);
                    }
                    
                    // Импортиране на опаковките, но с подменено ид на опаковката/мярката
                    $packObject->rec->packagingId = $localPackagingId;
                    $packObject->rec->productId = $productId;
                    cat_products_Packagings::save($packObject->rec);
                }
            }
        }
        
        return $productId;
    }


}
