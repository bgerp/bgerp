<?php


/**
 *
 *
 * @category  bgerp
 * @package   support
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class support_TaskType extends core_Mvc
{
    public $interfaces = 'cal_TaskTypeIntf';
    
    
    public $title = 'Сигнал';
    
    public $withoutResStr = 'without resources';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('typeId', 'key(mvc=support_IssueTypes, select=type)', 'caption=Тип, mandatory, width=100%, silent, after=title');
        $fieldset->FLD('assetResourceId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Ресурс, after=typeId, refreshForm, silent');
        $fieldset->FLD('systemId', 'key(mvc=support_Systems, select=name)', 'caption=Система, input=hidden, silent');
        
        $fieldset->FLD('name', 'varchar(64)', 'caption=Данни за обратна връзка->Име, mandatory, input=none, silent');
        $fieldset->FLD('email', 'email', 'caption=Данни за обратна връзка->Имейл, mandatory, input=none, silent');
        $fieldset->FLD('url', 'varchar', 'caption=Данни за обратна връзка->URL, input=none');
        $fieldset->FLD('ip', 'ip', 'caption=Ип,input=none');
        $fieldset->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
        $fieldset->FLD('file', 'fileman_FileType(bucket=Support)', 'caption=Файл, input=none');
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * Връща подсказките за добавяне на прогрес
     *
     * @param stdClass $tRec
     *
     * @return array
     */
    public static function getProgressSuggestions($tRec)
    {
        static $progressArr = array();
        
        if (empty($progressArr)) {
            $progressArr['0%'] = '0%';
            $progressArr['10%'] = tr('Информация');
            $progressArr['40%'] = tr('Корекция');
            $progressArr['60%'] = tr('Превенция');
            $progressArr['80%'] = tr('Оценка');
            $progressArr['100%'] = tr('Резолюция');
        }
        
        return $progressArr;
    }
    
    
    /**
     * Подготвя формата за добавя на сигнал от външната част
     *
     * @param core_Form $form
     */
    public function prepareFieldForIssue($form)
    {
        $form->setField('systemId', 'input=hidden');
        $form->setField('typeId', 'input');
        $form->setField('assetResourceId', 'input=none');
        if (!haveRole('user')) {
            $form->setField('name', 'input');
            $form->setField('email', 'input');
        }
        
        $form->setField('url', 'input=hidden, silent');
        
        $systemId = Request::get('systemId', 'int');
        
        $allowedTypesArr = support_Systems::getAllowedFieldsArr($systemId);
        
        $atOpt = array();
        foreach ($allowedTypesArr as $tId) {
            $atOpt[$tId] = support_IssueTypes::fetchField($tId, 'type');
        }
        
        $form->setOptions('typeId', $atOpt);
        
        if (!haveRole('user') && !core_Users::isSystemUser()) {
            $brid = log_Browsers::getBrid(false);
            if ($brid) {
                $vArr = log_Browsers::getVars(array('name', 'email'));
                
                if ($vArr['name']) {
                    $form->setDefault('name', $vArr['name']);
                }
                
                if ($vArr['email']) {
                    $form->setDefault('email', $vArr['email']);
                }
            }
        }
        
        $sRec = support_Systems::fetch($systemId);
        if ($sRec->defaultType) {
            $form->setDefault('typeId', $sRec->defaultType);
        }
    }
    
    
    /**
     * Подготвя documentRow за функцията
     *
     * @param stdClass $rec
     * @param stdClass $row
     */
    public function prepareDocumentRow($rec, $row)
    {
        if (!$row->authorId) {
            if (trim($rec->name)) {
                $row->author = type_Varchar::escape(trim($rec->name));
                
                if (trim($rec->email)) {
                    $row->authorEmail = type_Varchar::escape(trim($rec->email));
                }
            }
        }
    }
    
    
    /**
     * Подготвя getContrangentData за функцията
     *
     * @param stdClass $rec
     * @param stdClass $contrData
     */
    public function prepareContragentData($rec, $contrData)
    {
        if ($rec->createdBy < 1) {
            $contrData->email = $rec->email;
            $contrData->person = $rec->name;
        }
    }
    
    
    /**
     * Връща състоянието на нишката
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks        $mvc
     * @param string|NULL      $res
     * @param int              $id
     *
     * @return string
     */
    public static function on_AfterGetThreadState($Driver, $mvc, &$res, $id)
    {
        $res = 'opened';
    }
    
    
    /**
     * Променяме някои параметри на бутона в папката
     *
     * @param int $folderId
     */
    public function getButtonParamsForNewInFolder($folderId)
    {
        $res = array();
        
        if (!$folderId) {
            
            return $res;
        }
        
        $rec = doc_Folders::fetch($folderId);
        
        if (!$rec) {
            
            return $res;
        }
        if (!$rec->coverClass) {
            
            return $res;
        }
        
        if (!cls::load($rec->coverClass, true)) {
            
            return $res;
        }
        
        if (cls::get($rec->coverClass) instanceof support_Systems) {
            $res['btnTitle'] = 'Сигнал';
            $res['ef_icon'] = 'img/16/support.png';
        }
        
        return $res;
    }
    
    
    /**
     * Да няма потребители по подразбиране
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks        $mvc
     * @param string|NULL      $res
     * @param stdClass         $id
     */
    public static function on_AfterGetDefaultAssignUsers($Driver, $mvc, &$res, $rec)
    {
        $res = null;
    }
    
    
    /**
     *
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks        $mvc
     * @param stdClass         $res
     * @param stdClass         $data
     */
    public static function on_AfterPrepareEditForm($Driver, $mvc, &$res, $data)
    {
        $data->form->setField('title', array('mandatory' => false));
        $rec = $data->form->rec;
        
        $systemId = Request::get('systemId', 'key(mvc=support_Systems, select=name)');
        
        if (!$systemId && $data->form->rec->folderId) {
            $coverClassRec = doc_Folders::fetch($data->form->rec->folderId);
            
            if ($coverClassRec->coverClass && (cls::get($coverClassRec->coverClass) instanceof support_Systems)) {
                $systemId = $coverClassRec->coverId;
            }
        }
        
        $foldersArr = array();
        
        // Ограничаваме избора на компоненти и типове, само до тези, които ги има в системата
        if ($systemId) {
            $allSystemsArr = array();
            if ($systemId) {
                $allSystemsArr = support_Systems::getSystems($systemId);
            }
            
            $typesArr = array();
            
            if (!empty($allSystemsArr)) {
                foreach ($allSystemsArr as $sId) {
                    $sRec = support_Systems::fetch($sId);
                    
                    if (!$sRec->folderId) {
                        continue;
                    }
                    
                    $foldersArr[$sRec->folderId] = $sRec->folderId;
                }
                
                $allowedTypesArr = support_Systems::getAllowedFieldsArr($allSystemsArr);
                
                if ($data->form->rec->typeId) {
                    $allowedTypesArr[$data->form->rec->typeId] = $data->form->rec->typeId;
                }
                
                foreach ($allowedTypesArr as $allowedType) {
                    $typesArr[$allowedType] = support_IssueTypes::fetchField($allowedType, 'type');
                }
            }
            
            if (!empty($typesArr)) {
                $typesArr = array_unique($typesArr);
                asort($typesArr);
            }
            
            $data->form->setOptions('typeId', $typesArr);
            
            // Типа по подразбиране
            if (!$data->form->rec->id) {
                $sysRec = support_Systems::fetch($systemId);
                $defTypeId = $sysRec->defaultType;
                if ($defTypeId && $typesArr[$defTypeId]) {
                    $data->form->setDefault('typeId', $defTypeId);
                }
            }
        }
        
        if (empty($foldersArr)) {
            $foldersArr[$rec->folderId] = $rec->folderId;
        }
        
        $assetResArr = array();
        if (!empty($foldersArr)) {
            foreach ($foldersArr as $folderId) {
                $assetResArr += planning_AssetResources::getByFolderId($folderId);
            }
            if (!empty($assetResArr)) {
                asort($assetResArr);
            }
        }
        
        // Болдваме ресурсите, до които е споделен
        if (!empty($assetResArr)) {
            $aUsersQuery = planning_AssetResources::getQuery();
            $aUsersQuery->in('id', array_keys($assetResArr));
            $aUsersQuery->likeKeylist('assetUsers', core_Users::getCurrent());
            $aUsersQuery->show('id');
            while ($rec = $aUsersQuery->fetch()) {
                if (!$assetResArr[$rec->id]) continue;
                $opt = new stdClass();
                $opt->title = $assetResArr[$rec->id];
                $opt->attr = array('class' => 'boldText');
                $assetResArr[$rec->id] = $opt;
            }
        }
        
        $data->form->setOptions('assetResourceId', $assetResArr);
        
        if (($data->form->cmd == 'refresh') || (!$data->form->cmd && $data->form->rec->assetResourceId)) {
            // При избор на компонент, да са избрани споделените потребители, които са отговорници
            if ($data->form->rec->assetResourceId) {
                $assetId = planning_AssetResources::fetchField($data->form->rec->assetResourceId, 'id');
                
                if ($assetId) {
                    $maintainers = planning_AssetResourceFolders::fetchField(array("#classId = '[#1#]' AND #objectId = '[#2#]' AND #folderId = '[#3#]'", planning_AssetResources::getClassId(), $assetId, $rec->folderId), 'users');
                }
                
                $maintainers = keylist::removeKey($maintainers, core_Users::getCurrent());
                
                if ($maintainers) {
                    $data->form->setDefault('sharedUsers', $maintainers);
                }
            }
        }
        
        if (($srcId = $data->form->rec->SrcId) && ($srcClass = $data->form->rec->SrcClass)) {
            if (cls::haveInterface('support_IssueCreateIntf', $srcClass)) {
                $srcInst = cls::getInterface('support_IssueCreateIntf', $srcClass);
                
                $defaults = (array) $srcInst->getDefaultIssueRec($srcId);
                $data->form->setDefaults($defaults);
            }
        }
        
        $data->form->setField('timeStart', 'autohide');
        $data->form->setField('timeDuration', 'autohide');
        $data->form->setField('timeEnd', 'autohide');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param support_TaskType $Driver
     * @param core_Mvc         $mvc
     * @param stdClass         $row    Това ще се покаже
     * @param stdClass         $rec    Това е записа в машинно представяне
     * @param array|null       $fields Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($Driver, $mvc, &$row, $rec, $fields = array())
    {
        if ($rec->assetResourceId) {
            $row->assetResourceId = planning_AssetResources::getLinkToSingle($rec->assetResourceId, 'codeAndName');
        }
    }
    
    
    /**
     *
     *
     * @param support_TaskType $Driver
     * @param core_Mvc         $mvc
     * @param string           $res
     * @param int              $id
     */
    public function on_AfterGetIcon($Driver, $mvc, &$res, $id)
    {
        $res = 'img/16/support.png';
        
        if (log_Browsers::isRetina()) {
            $res = 'img/32/support.png';
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks        $mvc
     * @param int              $id     първичния ключ на направения запис
     * @param stdClass         $rec    всички полета, които току-що са били записани
     */
    public static function on_BeforeSave($Driver, $mvc, &$id, $rec)
    {
        if (!haveRole('powerUser') && !core_Users::isSystemUser()) {
            if (!$rec->ip) {
                $rec->ip = core_Users::getRealIpAddr();
            }
            
            if (!$rec->brid) {
                $rec->brid = log_Browsers::getBrid();
            }
        }
        
        if (!trim($rec->title)) {
            if ($rec->typeId) {
                $rec->title = support_IssueTypes::fetchField($rec->typeId, 'type');
            }
            
            if ($rec->assetResourceId) {
                $pRec = planning_AssetResources::fetch($rec->assetResourceId, 'code, name');
                if ($pRec) {
                    $rec->title .= ' ' . $pRec->code . ' ' . $pRec->name;
                }
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks        $mvc
     * @param int              $id     първичния ключ на направения запис
     * @param stdClass         $rec    всички полета, които току-що са били записани
     */
    public static function on_AfterSave($Driver, $mvc, &$id, $rec)
    {
        if ($rec->assetResourceId) {
            $nRec = new stdClass();
            $nRec->id = $rec->assetResourceId;
            $nRec->lastUsedOn = dt::now();
            
            planning_AssetResources::save($nRec, 'lastUsedOn');
        }
        
        if (core_Users::getCurrent() < 1) {
            log_Browsers::setVars(array('name' => $rec->name, 'email' => $rec->email));
        }
        
        if ($rec->SrcId && $rec->SrcClass && cls::haveInterface('support_IssueCreateIntf', $rec->SrcClass)) {
            $srcInst = cls::getInterface('support_IssueCreateIntf', $rec->SrcClass);
            $srcInst->afterCreateIssue($rec->SrcId, $rec);
        }
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks        $mvc
     * @param NULL|array       $resArr
     * @param object           $rec
     * @param object           $row
     */
    public static function on_AfterGetFieldForLetterHead($Driver, $mvc, &$resArr, $rec, $row)
    {
        if ($row->systemId) {
            $resArr['systemId'] = array('name' => tr('Система'), 'val' => '[#systemId#]');
        }
        
        if ($row->assetResourceId) {
            $resArr['assetResourceId'] = array('name' => tr('Ресурс'), 'val' => '[#assetResourceId#]');
        }
        
        if ($row->typeId) {
            $resArr['typeId'] = array('name' => tr('Тип'), 'val' => '[#typeId#]');
        }
        
        if ($row->name) {
            $resArr['name'] = array('name' => tr('Име'), 'val' => '[#name#]');
        }
        
        if ($row->email) {
            $resArr['email'] = array('name' => tr('Имейл'), 'val' => '[#email#]');
        }
        
        if (trim($rec->url)) {
            
            // Когато стойността е празна, трябва да върнем NULL
            $url = trim($rec->url);
            
            $attr = array();
            $attr['target'] = '_blank';
            $attr['class'] = 'out';
            if (!strpos($url, '://')) {
                $url = 'http://' . $url;
            }
            
            $v = mb_substr($url, 0, 50);
            
            if ($v != $url) {
                $v .= '...';
            }
            $url = HT::createLink($v, $url, false, $attr);
            
            $resArr['url'] = array('name' => tr('URL'), 'val' => $url);
        }
        
        if ($row->ip) {
            $resArr['ip'] = array('name' => tr('IP'), 'val' => '[#ip#]');
        }
        
        if (trim($rec->brid) && trim($row->brid)) {
            $bridLink = log_Browsers::getLink(trim($rec->brid));
            if ($bridLink) {
                $resArr['brid'] = array('name' => tr('BRID'), 'val' => $bridLink);
            }
        }
        
        if ($row->file) {
            $resArr['file'] = array('name' => tr('Файл'), 'val' => '[#file#]');
        }
        
        if ($resArr['ident']['name']) {
            $resArr['ident']['name'] = tr($Driver->title);
        }
    }
    
    
    /**
     * Кои полета да са скрити във вътрешното или външното показване
     *
     * @param support_TaskType $Driver
     * @param core_Master      $mvc
     * @param NULL|array       $res
     * @param object           $rec
     * @param object           $row
     */
    public static function on_AfterGetHideArrForLetterHead($Driver, $mvc, &$res, $rec, $row)
    {
        $res = arr::make($res);
        
        $res['external']['url'] = true;
        $res['external']['brid'] = true;
        $res['external']['ip'] = true;
        $res['external']['createdBy'] = true;
        $res['external']['progressBar'] = true;
        $res['external']['driverClass'] = true;
        $res['external']['priority'] = true;
        $res['external']['file'] = true;
    }
    
    
    /**
     * Добавя ключовите думи от допълнителните полета
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks        $mvc
     * @param object           $res
     * @param object           $rec
     */
    public function on_AfterGetSearchKeywords($Driver, $mvc, &$res, $rec)
    {
        $sTxt = $rec->name . ' ' . $rec->email . ' ' . $rec->ip . ' ' . $rec->url;
        
        if ($rec->typeId) {
            $sTxt .= ' ' . support_IssueTypes::fetchField($rec->typeId, 'type');
        }
        
        if ($rec->assetResourceId) {
            $pRec = planning_AssetResources::fetch($rec->assetResourceId, 'code, name');
            $sTxt .= ' ' . $pRec->code . ' ' . $pRec->name;
        } else {
            $sTxt .= ' ' . $Driver->withoutResStr;
        }
        
        if ($rec->systemId) {
            $sTxt .= ' ' . support_Systems::fetchField($rec->systemId, 'name');
        }
        
        if (trim($sTxt)) {
            $res .= ' ' . plg_Search::normalizeText($sTxt);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks        $mvc
     * @param object           $res
     * @param object           $data
     */
    public static function on_AfterPrepareSingleToolbar($Driver, $mvc, &$res, $data)
    {
        if ($data->rec->state != 'rejected' && $data->rec->brid && email_Outgoings::haveRightFor('add')) {
            $data->toolbar->addBtn('Отговор', array(
                'email_Outgoings',
                'add',
                'originId' => $data->rec->containerId,
                'ret_url' => true
            ), 'ef_icon = img/16/email_edit.png,title=Отговор на сигнал чрез имейл', 'onmouseup=saveSelectedTextToSession("' . $mvc->getHandle($data->rec->id) . '");');
        }
    }
    
    
    /**
     *
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks        $mvc
     * @param NULL|core_Form   $res
     * @param core_Form        $form
     *
     * @see doc_plg_SelectFolder
     */
    public static function on_AfterPrepareSelectForm($Driver, $mvc, &$res, $form)
    {
        // При създаване на задача от тип сигнал, показваме само папките от тип система
        $form->fields['folderId']->type->params['coverInterface'] = 'support_IssueIntf';
        $optArr = $form->fields['folderId']->type->getOptions();
        if (!empty($optArr)) {
            if (!$optArr[$form->rec->folderId]) {
                unset($form->rec->folderId);
                $form->setDefault('folderId', key($optArr));
            }
        }
    }
}
