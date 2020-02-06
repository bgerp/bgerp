<?php


/**
 * Импортиране на артикули от друга Bgerp система
 *
 *
 * @category  bgerp
 * @package   sync
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Импортиране на артикули от друга Bgerp система
 */
class sync_ProductQuotes extends core_BaseClass
{
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
        //sync_Helper::requireRight('import');
        $res = new stdClass();
        
        try{
            // Кое отдалечено ид ще се очаква за импорт
            $remoteId = Request::get('remoteId', 'int');
            if(!$remoteId){
                throw new core_exception_Expect('Невалидно remoteId', 'Несъответствие');
            }
            
            // Проверка дали вече не е импортирано
            $localId = sync_Map::getLocalId('cat_Products', $remoteId);
            if(empty($localId)){
                $options = array('http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST'));
                
                $context  = stream_context_create($options);
                $exportDomain = sync_Setup::get('EXPORT_URL');
                
                $exportUrl = rtrim($exportDomain, '/');
                $exportUrl .= "/cat_Products/remoteexport/?exportId={$remoteId}";
                
                @$data = file_get_contents($exportUrl, false, $context);
                
                if($data === 'FALSE' || $data === FALSE){
                    throw new core_exception_Expect('Проблем при подготовката на данните за експорт', 'Несъответствие');
                }
                
                $localId = self::import($data, $exportDomain);
                if(!$localId){
                    throw new core_exception_Expect('Проблем при импортирането на артикул', 'Несъответствие');
                }
                
                sync_Map::add('cat_Products', $localId, $remoteId);
                
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
     * @param stdClass $data
     * @param string $exportDomain
     * 
     * @return int $productId
     */
    private static function import($data, $exportDomain)
    {
        // Разкриптиране на данните за импорт
        $data = base64_decode($data);
        $data = gzuncompress($data);
        $data = unserialize($data, array('allowed_classes' => array('stdClass')));
        $data = (object) $data;
        $data->exportUrl = $exportDomain;
        
        // Импортираните записи, ще са направени от системния потребител
        core_Users::forceSystemUser();
        
        // Импортиране на контрагента, ако е нужно
        $exportContragentRes = (array)$data->exportContragentRes;
        sync_Map::importRec($data->contragentClassName, $data->contragentRemoteId, $exportContragentRes, cls::get('sync_Companies'));
        $localContragentId = sync_Map::getLocalId($data->contragentClassName, $data->contragentRemoteId);
        
        if(!$localContragentId){
            throw new core_exception_Expect('Проблем при импортирането на контрагента', 'Несъответствие');
        }
        
        // Подмяна на линковете за сваляне на файловете от хтмл-а
        $matches = array();
        preg_match_all('/http.*?forceDownload=1/', $data->html, $matches);
        if (countR($matches[0])) {
            foreach ($matches[0] as $downloadFileUrl){
                
                // Ако е открит линк за сваляне на файл, файла се сваля и абсорбира в системата
                if($fileContent = @file_get_contents($downloadFileUrl)){
                    $newFh = fileman::absorbStr($fileContent, 'importedProductFiles', 'fh');
                    
                    // Урл-то за сваляне, се подменя с такова за сваляне в приемащата система
                    $singleFileUrl = toUrl(array('fileman_Files', 'single', $newFh));
                    $data->html = str_replace($downloadFileUrl, $singleFileUrl, $data->html);
                    $data->htmlEn = str_replace($downloadFileUrl, $singleFileUrl, $data->htmlEn);
                }
            }
        }
        
        // Мапване на контрагента, и форсиране на папка
        $folderId = cls::get($data->contragentClassName)->forceCoverAndFolder($localContragentId);
        
        // Проверка има ли я мапната основната мярка в системата, ако не се импортира при нужда и мапва
        $localBaseMeasureId = sync_Map::getLocalId('cat_UoM', $data->measureRec->id);
        if(!$localBaseMeasureId){
            $newBaseUomRec = clone $data->measureRec;
            unset($newBaseUomRec->id);
            
            $localBaseMeasureId = cat_UoM::fetchBySinonim($newBaseUomRec->name)->id;
            if(!$localBaseMeasureId){
                $localBaseMeasureId = cat_UoM::save($newBaseUomRec);
           }
           
           sync_Map::add('cat_UoM', $localBaseMeasureId, $data->measureRec->id);
        }
        
        // Попълват се данните на драйвера за импортиран артикул
        $productRec = (object)array('name' => $data->name,
            'nameEn' => $data->nameEn,
            'innerClass' => cat_ImportedProductDriver::getClassId(),
            'html' => $data->html,
            'htmlEn' => $data->htmlEn,
            'measureId' => $localBaseMeasureId,
            'meta' => $data->meta,
            'quotations' => $data->quotations,
            'folderId' => $folderId,
            'importedFromDomain' => $data->exportUrl,
            'moq' => $data->moq,
            'conditions' => $data->conditions,
        );
        
        // Импортиране на параметри
        $productRec->params = array();
        $data->params = (array)$data->params;
        foreach ($data->params as $obj){
            
            // Мапване на параметъра
            $localParamId = sync_Map::getLocalId('cat_Params', $obj->remoteId);
            $paramRec = $obj->paramRec;
            
            // Ако няма такъв се създава и мапва
            if(!$localParamId){
                $localParamId = cat_Params::force($paramRec->sysId, $paramRec->name, $paramRec->driverClass, $paramRec->options, $paramRec->suffix, $paramRec->showInTasks);
                sync_Map::add('cat_Params', $localParamId, $obj->remoteId);
            }
            
            // Ако има намерен параметър и той е с драйвер за качен файл
            if(isset($localParamId)){
                if(in_array($paramRec->driverClass, array('cond_type_File', 'cond_type_Image'))){
                    
                    // Абсорбиране на файла от урл-то за сваляне и подмяна с хендлъра към новия файл
                    if($fileContent = @file_get_contents($obj->value)){
                        $fileName = basename($obj->value);
                        $obj->value = fileman::absorbStr($fileContent, 'importedProductFiles', $fileName);
                    }
                } elseif($paramRec->driverClass == 'cond_type_Store'){
                    continue;
                }
               
                // Записване на стойността на параметъра, съответстваща на локалния ключ
                $productRec->params[$localParamId] = $obj->value;
            }
        }
        
        $productRec->quotations = $data->quotations;
        
        // Артикулът се създава
        $Products = cls::get('cat_Products');
        $Products->route($productRec);
        $Products->save($productRec);
        $Products->logWrite('Импортиране от друга Bgerp система', $productRec->id);
        $productId = $productRec->id;
        
        // Ако е създаден артикул и има опаковки за импорт
        if(isset($productId)){
            if(countR($data->packagings)){
                foreach ($data->packagings as $packObject){
                    
                    // Мапване на опаковката
                    $localPackagingId = sync_Map::getLocalId('cat_UoM', $packObject->remoteId);
                    
                    // Ако не е мапната и не съществува се форсира нова
                    if(!$localPackagingId){
                        $newUomRec = $packObject->uomRec;
                        $localPackagingId = cat_UoM::fetchBySinonim($newUomRec->name)->id;
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
        
        core_Users::cancelSystemUser();
        
        return $productId;
    }
}