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
        // Добавяне на бутон за експорт на артикул
        if ($mvc->haveRightFor('syncexport', $data->rec)) {
            $exportUrl = sync_Setup::get('EXPORT_URL');
            $warning = "Наистина ли желаете да синхронизирате с|*: {$exportUrl}";
            $data->toolbar->addBtn('Синхронизация', array($mvc, 'syncexport', $data->rec->id, 'ret_url' => true), "ef_icon = img/16/arrow_refresh.png,title=Синхронизиране на артикула с друга Bgerp система,warning={$warning}");
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
            
            expect($importUrl = self::getImportUrl($rec));
            
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
            
            // Прави се опит за импорт в приемащата система
            $serverOutput = curl_exec($ch);
            $errorCode = curl_errno($ch);
            curl_close($ch);
            $res = $serverOutput;
            $res = json_decode($res);
          
            $exportUrl = sync_Setup::get('EXPORT_URL');
            if(is_object($res)){
                
                // Ако не е върната грешка, се показва подходящо съобщение
                if(empty($res->error)){
                    if($res->status == 2){
                        cat_Products::logWrite("Повторен опит за експорт");
                    } else {
                        cat_Products::logWrite("Експортиране към: '{$exportUrl}'", $rec->id);
                        $msg = "|Артикулът е експортиран успешно|* ";
                    }
                    
                    // Ако върнатото урл е оторизирано потребителя ще се редиректва към него
                    $redirectUrl = getRetUrl();
                    if(core_Packs::isInstalled('remote')){
                        if($remoteUrl = remote_Authorizations::getAutoLoginUrl($res->url)){
                            $redirectUrl = $remoteUrl;
                        }
                    }
                    
                    redirect($redirectUrl, true, $msg);
                } else {
                    
                    // Ако е върната грешла, се показва подходящо съобщение
                    cat_Products::logErr("Грешка при ръчен експорт: '{$res->error}'", $rec->id);
                    followRetUrl(null, $res->error, 'error');
                }
            } else {
                
                // Ако има проблем при връзката с приемащата система, тя се логва и визуализира
                if (!empty($errorCode)) {
                    $errorCode = curl_strerror($errorCode);
                }
                
                cat_Products::logErr("Грешка експорт на артикул: '{$errorCode}' OUTPUT '{$serverOutput}'", $rec->id);
                followRetUrl(null, 'Грешка при ръчен експорт', 'error');
            }
        }
        
        // Екшън който 'сервира' данните за експорт на артикула
        if($action == 'remoteexport'){
            sync_Helper::requireRight('export');
            expect($id = Request::get('exportId', 'int'));
            
            try{
                $data = self::getExportData($id);
            } catch(core_exception_Expect $e){
                cat_Products::logErr("Грешка подготовка на данни за експорт");
                reportException($e);
                $data = 'FALSE';
            }
            
            echo $data;
            shutdown();
        }
        
        //@TODO тестов екшън да се премахне
        if($action == 'test'){
            requireRole('debug');
            $exp = self::getExportData(4015);
            
            bp($exp,$data);
        }
    }
    
    
    /**
     * Koe урл- ще се вика за импорт
     * 
     * @param stdClass $rec
     * @return NULL|string
     */
    private static function getImportUrl($rec)
    {
        $importUrl = sync_Setup::get('EXPORT_URL');
        if(empty($importUrl)) return null;
        
        $importUrl = rtrim($importUrl, '/');
        $importUrl .= "/sync_ProductQuotes/import/";
        
        return $importUrl;
    }
    
    
    /**
     * Връща данните за експорт на артикул във формат за драйвера cat_ImportedProductDriver
     * 
     * @param stdClass $rec
     * @return stdClass $data
     */
    private static function getExportData($rec)
    {
        $rec = cat_Products::fetchRec($rec);
        $Driver = cat_Products::getDriver($rec);
        $Cover = doc_Folders::getCover($rec->folderId);
        expect($Cover->isInstanceOf('crm_Companies'));
        
        // Подготовка на данните за експорт на контрагента, ако е нужно
        $exportContragentRes = array();
        sync_Map::exportRec($Cover->className, $Cover->that, $exportContragentRes, cls::get('sync_Companies'));
        
        $data = (object)array('name' => $rec->name, 
                              'nameEn' => $rec->nameEn, 
                              'meta' => $rec->meta, 
                              'contragentClassName' => $Cover->className,
                              'contragentRemoteId' => $Cover->that,
                              'exportContragentRes' => $exportContragentRes,
                              );
        
        // Подготовка на продуктовите параметри за експорт
        $data->params = array();
        $params = cat_Products::getParams($rec->id);
        foreach ($params as $paramId => $value){
            $paramRec = cat_Params::fetch($paramId);
            unset($paramRec->id); 
            $paramRec->driverClass = cls::getClassName($paramRec->driverClass);
            
            // Ако параметъра е за качен файл, той се подменя с урл за сваляне към него
            if(in_array($paramRec->driverClass, array('cond_type_File', 'cond_type_Image'))){
                $value = fileman_Download::getDownloadUrl($value);
                if(!$value) continue;
            }
            
            $data->params[$paramId] = (object)array('remoteId' => $paramId, 'value' => $value, 'paramRec' => $paramRec);
        }
        
        // Извличане на данните от офертите
        $data->quotations = $data->packagings = array();
        $quoteQuery = sales_QuotationsDetails::getQuery();
        $quoteQuery->EXT('state', 'sales_Quotations', 'externalName=state,externalKey=quotationId');
        $quoteQuery->EXT('folderId', 'sales_Quotations', 'externalName=folderId,externalKey=quotationId');
        $quoteQuery->where("#productId = {$rec->id} AND #state = 'active'");
        $quoteQuery->show('quotationId,packagingId,quantityInPack,quantity,price,discount,tolerance,term,optional,price');
        while($quoteRec = $quoteQuery->fetch()){
            $data->quotations[$quoteRec->id] = $quoteRec;
        }
        
        // Подготовка на данните за опаковките, готови за експорт
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
       
        // Рендиране на показването на артикула на БГ и на EN
        $descriptionData = (object)array('rec' => $rec, 'row' => $row);
        $descriptionData->Embedder = cls::get('cat_Products');
        $descriptionData->isSingle = true;
        $descriptionData->documentType = 'internal';
        $dataBg = clone $descriptionData;
        $dataEn = clone $descriptionData;
        
        Mode::push('forceDownload', true);
        core_Users::sudo($rec->createdBy);
        Mode::push('text', 'xhtml');
        core_Lg::push('bg');
        $htmlTpl = $Driver->renderProductDescription($dataBg);
        $htmlTpl = $htmlTpl->getContent();
        core_Lg::pop('bg');
        Mode::pop('text');
        
        Mode::push('text', 'xhtml');
        core_Lg::push('en');
        $htmlEnTpl = $Driver->renderProductDescription($dataEn);
        $htmlEnTpl = $htmlEnTpl->getContent();
        core_Lg::pop('en');
        Mode::pop('text');
        core_Users::exitSudo($rec->createdBy);
        Mode::pop('forceDownload');
        
        $measureRec = cat_UoM::fetch($rec->measureId, 'name,shortName,type,baseUnitId,baseUnitRatio,sysId,isBasic,sinonims,showContents,defQuantity,round,state');
        $data->measureRec = $measureRec;
        $data->html = $htmlTpl;
        $data->htmlEn = $htmlEnTpl;
        
        // Защита на данните за експорт
        $data = base64_encode(gzcompress(serialize($data)));
        
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
            } else {
                $Cover = doc_Folders::getCover($rec->folderId);
                if(!$Cover->haveInterface('crm_CompanyAccRegIntf')){
                    $requiredRoles = 'no_one';
                } else {
                    $groupId = sync_Setup::get('COMPANY_GROUP');
                    $groupList = $Cover->fetchField($Cover->groupsField);
                    if(empty($groupId) || !keylist::isIn($groupId, $groupList)){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
}