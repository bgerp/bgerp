<?php


/**
 * Импортиране на артикули
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
 * @title     Импортиране на артикули
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
     * Импортира транспортна услуга и я добавя към чернова покупка
     */
    public function act_Import()
    {
        //sync_Helper::requireRight('import');
        $res = new stdClass();
        
        try{
            $remoteId = Request::get('remoteId', 'int');
            if(!$remoteId){
                throw new core_exception_Expect('Невалидно remoteId', 'Несъответствие');
            }
            
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
                
                
                
                //$mRec = (object) array('classId' => cls::get('cat_Products')->getClassId(), 'remoteId' => $remoteId, 'localId' => $localId);
                //sync_Map::save($mRec);
                
                $res->status = 1;
            } else {
                $res->status = 2;
            }
            
            $res->localId = $localId;
            
            
        } catch (core_exception_Expect $e){
            $res->localId = null;
            $res->error = $e->getMessage();
            $res->status = 3;
            reportException($e);
        }
        
        echo json_encode($res);
        shutdown();
    }
    
    private static function import($data, $exportDomain)
    {
        $data = base64_decode($data);
        $data = gzuncompress($data);
        $data = json_decode($data);
        $data = (object) $data;
        $data->exportUrl = $exportDomain;
        
        core_Users::forceSystemUser();
        
        $exportContragentRes = (array)$data->exportContragentRes;
        sync_Map::importRec($data->contragentClassName, $data->contragentRemoteId, $exportContragentRes, cls::get('sync_Companies'));
        
        $matches = array();
        preg_match_all('/http.*?forceDownload=1/', $data->html, $matches);
        if (countR($matches[0])) {
            foreach ($matches[0] as $downloadFileUrl){
                if($fileContent = @file_get_contents($downloadFileUrl)){
                    $newFh = fileman::absorbStr($fileContent, 'importedProductFiles', 'fh');
                    
                    $newDownloadUrl = fileman::generateUrl($newFh, true);
                    $data->html = str_replace($downloadFileUrl, $newDownloadUrl, $data->html);
                    $data->htmlEn = str_replace($downloadFileUrl, $newDownloadUrl, $data->htmlEn);
                }
            }
        }
        
        $localContragentId = sync_Map::getLocalId($data->contragentClassName, $data->contragentRemoteId);
        $folderId = cls::get($data->contragentClassName)->forceCoverAndFolder($localContragentId);
        
        $productRec = (object)array('name' => $data->name,
            'nameEn' => $data->nameEn,
            'innerClass' => cat_ImportedProductDriver::getClassId(),
            'html' => $data->html,
            'htmlEn' => $data->htmlEn,
            'measureId' => $data->measureId,
            'meta' => $data->meta,
            'quotations' => $data->quotations,
            'folderId' => $folderId,
            'importedFromDomain' => $data->exportUrl,
        );
        
        $productRec->params = array();
        $data->params = (array)$data->params;
        
        foreach ($data->params as $obj){
            $localParamId = sync_Map::getLocalId('cat_Params', $obj->remoteId);
            $paramRec = $obj->paramRec;
            
            if(!$localParamId){
                $localParamId = cat_Params::force($paramRec->sysId, $paramRec->name, $paramRec->driverClass, null, $paramRec->suffix, $paramRec->showInTasks);
                sync_Map::add('cat_Params', $localParamId, $obj->remoteId);
                
                
                //$mRec = (object) array('classId' => cls::get('cat_Params')->getClassId(), 'remoteId' => $obj->remoteId, 'localId' => $localParamId);
               // sync_Map::save($mRec);
            }
            
            if(isset($localParamId)){
                if(in_array($paramRec->driverClass, array('cond_type_File', 'cond_type_Image'))){
                    if($fileContent = @file_get_contents($obj->value)){
                        $fileName = basename($obj->value);
                        $obj->value = fileman::absorbStr($fileContent, 'importedProductFiles', $fileName);
                    }
                }
               
                $productRec->params[$localParamId] = $obj->value;
            }
        }
        
        $productRec->quotations = $data->quotations;
        
        $Products = cls::get('cat_Products');
        $Products->route($productRec);
        $Products->save($productRec);
        $Products->logWrite('Импортиране от друга Bgerp система', $productRec->id);
        $productId = $productRec->id;
        
        if(isset($productId)){
            
            if(countR($data->packagings)){
                foreach ($data->packagings as $packObject){
                    
                    $localPackagingId = sync_Map::getLocalId('cat_UoM', $packObject->remoteId);
                    if(!$localPackagingId){
                        $newUomRec = $packObject->uomRec;
                        $localPackagingId = cat_UoM::fetchBySinonim($newUomRec->name)->id;
                        if(!$localPackagingId){
                            $localPackagingId = cat_UoM::save($newUomRec);
                        }
                        
                        sync_Map::add('cat_UoM', $localPackagingId, $packObject->remoteId);
                        
                        //$mRec = (object) array('classId' => cls::get('cat_UoM')->getClassId(), 'remoteId' => $packObject->remoteId, 'localId' => $localPackagingId);
                        //sync_Map::save($mRec);
                    }
                    
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