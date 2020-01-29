<?php


/**
 * Плъгин за експортиране на артикул в друга бгерп система
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
 */
class sync_plg_ProductExport extends core_Plugin
{
    
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->canSyncexport, 'admin');
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('syncexport', $data->rec)) {
            $data->toolbar->addBtn('Експорт', array($mvc, 'syncexport', $data->rec->id, 'ret_url' => true), 'ef_icon = img/16/arrow_refresh.png,title=Синхронизиране на артикула с друга Bgerp система');
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param string   $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if($action == 'syncexport'){
            
            $mvc->requireRightFor('syncexport');
            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('syncexport', $rec);
            sync_Helper::requireRight('export');
            
            $importUrl = self::getImportUrl($rec);
            $params = array('remoteId' => $rec->id);
            $httpQuery = http_build_query($params);
            //bp($importUrl, $httpQuery);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $importUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $httpQuery);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            
            $serverOutput = curl_exec($ch);
            $errorCode = curl_errno($ch);
            curl_close($ch);
            
            $res = $serverOutput;
            $res = json_decode($res);
          
            $exportUrl = sync_Setup::get('EXPORT_URL');
            
            if(is_object($res)){
                if(empty($res->error)){
                    if($res->status == 2){
                        cat_Products::logWrite("Повторен опит за експорт");
                    } else {
                        cat_Products::logWrite("Експортиране към: '{$exportUrl}'", $rec->id);
                        $msg = "|Артикулът е експортиран успешно|* ";
                    }
                    
                    $msg .= "#Art{$res->localId}";
                    followRetUrl(null, $msg, 'notice');
                } else {
                    cat_Products::logErr("Грешка при ръчен експорт: '{$res->error}'", $rec->id);
                    followRetUrl(null, $res->error, 'error');
                }
            } else {
                if (!empty($errorCode)) {
                    $errorCode = curl_strerror($errorCode);
                }
                
                cat_Products::logErr("Грешка експорт на артикул: '{$errorCode}' OUTPUT '{$serverOutput}'", $rec->id);
                followRetUrl(null, 'Грешка при ръчен експорт', 'error');
            }
            
            
        }
        
        
        if($action == 'remoteexport'){
            //sync_Helper::requireRight('export');
            expect($id = Request::get('exportId', 'int'));
            
            try{
                $data = self::getExportData($id);
                //bp($data);
               // wp($data);
            } catch(core_exception_Expect $e){
                reportException($e);
                $data = 'FALSE';
            }
            
            
            echo $data;
            shutdown();
        }
        
        if($action == 'test'){
            bp(self::getExportData(3981));
        }
    }
    
    private static function getImportUrl($rec)
    {
        $exportUrl = sync_Setup::get('EXPORT_URL');
        $exportUrl = rtrim($exportUrl, '/');
        $exportUrl .= "/sync_ProductQuotes/import/";
        
        return $exportUrl;
    }
    
    
    
    
    
    private static function getExportData($rec)
    {
        $rec = cat_Products::fetchRec($rec);
        $Driver = cat_Products::getDriver($rec);
        $Cover = doc_Folders::getCover($rec->folderId);
        
        $exportContragentRes = array();
        sync_Map::exportRec($Cover->className, $Cover->that, $exportContragentRes, cls::get('sync_Companies'));
        
        $data = (object)array('name' => $rec->name, 
                              'nameEn' => $rec->nameEn, 
                              'measureId' => $rec->measureId, 
                              'meta' => $rec->meta, 
                              'contragentClassName' => $Cover->className,
                              'contragentRemoteId' => $Cover->that,
                              'exportContragentRes' => $exportContragentRes,
                              );
        
        $params = cat_Products::getParams($rec->id);
        $data->params = array();
        foreach ($params as $paramId => $value){
            $paramRec = cat_Params::fetch($paramId, 'driverClass,name,suffix,sysId,showInTasks,showInPublicDocuments,isFeature,default');
            unset($paramRec->id); 
            $paramRec->driverClass = cls::getClassName($paramRec->driverClass);
            $data->params[$paramId] = (object)array('remoteId' => $paramId, 'value' => $value, 'paramRec' => $paramRec);
        }
       
        $quotationClassId = sales_Quotations::getClassId();
        
        $data->quotations = $data->packagings = array();
        $quoteQuery = sales_QuotationsDetails::getQuery();
        $quoteQuery->EXT('state', 'sales_Quotations', 'externalName=state,externalKey=quotationId');
        $quoteQuery->EXT('folderId', 'sales_Quotations', 'externalName=folderId,externalKey=quotationId');
        $quoteQuery->where("#productId = {$rec->id} AND #state = 'active'");
        $quoteQuery->show('quotationId,packagingId,quantityInPack,quantity,price,discount,tolerance,term,optional,folderId,price');
        while($quoteRec = $quoteQuery->fetch()){
            if($tRec = sales_TransportValues::get($quotationClassId, $quoteRec->quotationId, $quoteRec->id)){
                $quoteRec->_fee = $tRec->fee;
                $quoteRec->_deliveryTime = $tRec->deliveryTime;
                $quoteRec->_explain = $tRec->explain;
            }
            
            $data->quotations[$quoteRec->id] = $quoteRec;
        }
        
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->show('packagingId,quantity,isBase,eanCode,sizeWidth,sizeHeight,sizeDepth,tareWeight');
        $packQuery->where("#productId = {$rec->id}");
        while($packRec = $packQuery->fetch()){ 
            $uomRec = cat_UoM::fetch($packRec->packagingId, 'name,shortName,type,baseUnitId,baseUnitRatio,sysId,isBasic,sinonims,showContents,defQuantity,round,state');
            unset($uomRec->id);
            unset($packRec->id);
            $data->packagings[] = (object)array('remoteId' => $packRec->packagingId, 'rec' => $packRec, 'uomRec' => $uomRec);
        }
        
        $row = cat_Products::recToVerbal($rec);
       
        $descriptionData = (object)array('rec' => $rec, 'row' => $row);
        $descriptionData->Embedder = cls::get('cat_Products');
        $descriptionData->isSingle = true;
        $descriptionData->documentType = 'internal';
        $htmlTpl = $Driver->renderProductDescription($descriptionData);
        
        core_Users::forceSystemUser();
        Mode::push('text', 'xhtml');
        core_Lg::push('bg');
        $htmlTpl = $Driver->renderProductDescription($descriptionData);
        $htmlTpl = $htmlTpl->getContent();
        core_Lg::pop('bg');
        Mode::pop('text');
        
        //$r = 'http://11.0.0.61/fileman_Download/Download/?fh=hGGdcK';
        //$l = file_get_contents($r);
        //$h = fileman::absorbStr($l, 'Notes', 'ggg');
        //bp($l, $r, $h);
        
        
        Mode::push('text', 'xhtml');
        core_Lg::push('en');
        $htmlEnTpl = $Driver->renderProductDescription($descriptionData);
        $htmlEnTpl = $htmlEnTpl->getContent();
        core_Lg::pop('en');
        Mode::pop('text');
        core_Users::cancelSystemUser();
        //core_Users::exitSudo($rec->createdBy);
        
        $data->html = $htmlTpl;
        $data->htmlEn = $htmlEnTpl;
        
        $data = base64_encode(gzcompress(json_encode($data)));
        
        return $data;
        
    }
    
    
    
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'syncexport' && isset($rec)){
            if(!sync_Setup::get('EXPORT_URL')){
                $requiredRoles = 'no_one';
            } elseif($rec->isPublic == 'yes'){
                $requiredRoles = 'no_one';
            } elseif($rec->state == 'rejected'){
                $requiredRoles = 'no_one';
            }
        }
    }
}