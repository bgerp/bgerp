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
    

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('typeId', 'key(mvc=support_IssueTypes, select=type)', 'caption=Тип, mandatory, width=100%, silent, after=title');
        $fieldset->FLD('systemId', 'key(mvc=support_Systems, select=name)', 'caption=Система, input=hidden, silent');

        $fieldset->FLD('issueTemplateId', 'key(mvc=planning_AssetGroupIssueTemplates,select=string,allowEmpty)', 'caption=Готов сигнал,input=none,before=description,changable');
        $fieldset->FLD('name', 'varchar(64)', 'caption=Данни за обратна връзка->Име, input=none, silent');
        $fieldset->FLD('email', 'email', 'caption=Данни за обратна връзка->Имейл, input=none, silent');
        $fieldset->FLD('url', 'varchar(500)', 'caption=Данни за обратна връзка->URL, input=none');
        $fieldset->FLD('ip', 'ip', 'caption=Ип,input=none');
        $fieldset->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
        $fieldset->FLD('file', 'fileman_FileType(bucket=Support)', 'caption=Файл, input=none');

        if ($this->Embedder) {
            $this->Embedder->getContragentDataFromLastDoc = false;
        }
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
    public static function getProgressSuggestions($tRec = null)
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

        $form->setField('url', 'input=hidden, silent');

        $systemId = Request::get('systemId', 'int');
        if (!$systemId) {
            $systemId = $form->rec->systemId;
        }

        expect($systemId);

        $sRec = support_Systems::fetch($systemId);
        if (!haveRole('user')) {
            expect($sRec->addFromEveryOne == 'yes', $sRec);
        }

        if (!haveRole('user')) {
            if ($sRec->addContragentValues != 'no') {
                $form->setField('name', 'input');
                $form->setField('email', 'input');

                if ($sRec->addContragentValues == 'mandatory') {
                    $form->setField('name', 'mandatory');
                    $form->setField('email', 'mandatory');
                }
            }
        }

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
            if (!isset($atOpt[$sRec->defaultType])) {
                $form->setDefault('typeId', key($atOpt));
            }
        }

        if (!haveRole('user')) {
            if (countr((array)$atOpt) == 1) {
                $form->setField('typeId', 'input=hidden');
            }
        }

        if ($sRec->defaultTitle) {
            $form->title = $sRec->defaultTitle;
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
        
        $rec = $mvc->fetchRec($id);
        
        if (($rec->state != 'active') && ($rec->state != 'waiting') && ($rec->state != 'pending')) {
            if (cal_Tasks::checkForCloseThread($rec->threadId, $rec->containerId)) {
                $res = 'closed';
            }
        }
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
        // Ако избрания етап има отговорници - няма да се добавят тези от ЦД
        if(!empty($rec->stepId)){
            $stepUsers = doc_UnsortedFolderSteps::fetchField($rec->stepId, 'supportUsers');
            if(!empty($stepUsers)) return;
        }

        // Ако има източник и той е ПО - взимат се отговорниците за системата от центъра на дейност
        if(isset($rec->srcClass) && isset($rec->srcId)){
            $Source = cls::get($rec->srcClass);
            if($Source instanceof planning_Tasks){
                $sourceFolderId = $Source->fetchField($rec->srcId, 'folderId');
                $SourceFolderCover = doc_Folders::getCover($sourceFolderId);
                if($SourceFolderCover->isInstanceOf('planning_Centers')){
                    $supportUsers = $SourceFolderCover->fetchField('supportUsers');
                    if(!empty($supportUsers)){
                        $res = keylist::merge($supportUsers, $rec->assing);
                    }
                }
            }
        } else {

            // Ако няма източник и няма етап се взимат всички отговорници от ЦД където е избрана системата
            $cQuery = planning_Centers::getQuery();
            $cQuery->where("#supportSystemFolderId = {$rec->folderId}");
            $cQuery->show('supportUsers');

            $assignedUsers = '';
            while($cRec = $cQuery->fetch()){
                $assignedUsers = keylist::merge($assignedUsers, $cRec->supportUsers);
            }
            if(!empty($assignedUsers)){
                $res = $assignedUsers;
            }
        }
    }


    /**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm($Driver, embed_Manager $Embedder, &$form)
    {
        $rec = &$form->rec;
        if($form->isSubmitted()){
            if(empty($rec->assetResourceId) && empty($rec->stepId) && $rec->_assetsAllowed){
                $form->setWarning('assetResourceId', 'За по-бърза обработка на сигнала, моля изберете "Ресурс"!');
            }
        }
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
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setField('assetResourceId', 'after=typeId,removeAndRefreshForm=issueTemplateId');
        $form->setField('title', array('mandatory' => false));
        $form->input(null, 'silent');

        $form->setField('parentId', 'changable=no');
        $systemId = Request::get('systemId', 'key(mvc=support_Systems, select=name)');
        
        if (!$systemId && $rec->folderId) {
            $coverClassRec = doc_Folders::fetch($rec->folderId);
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
                
                if ($rec->typeId) {
                    $allowedTypesArr[$rec->typeId] = $rec->typeId;
                }
                
                foreach ($allowedTypesArr as $allowedType) {
                    $typesArr[$allowedType] = support_IssueTypes::fetchField($allowedType, 'type');
                }
            }
            
            if (!empty($typesArr)) {
                $typesArr = array_unique($typesArr);
                asort($typesArr);
            }

            $form->setOptions('typeId', $typesArr);

            // Типа по подразбиране
            if (!$rec->id) {
                $sysRec = support_Systems::fetch($systemId);
                $defTypeId = $sysRec->defaultType;
                if ($defTypeId && $typesArr[$defTypeId]) {
                    $form->setDefault('typeId', $defTypeId);
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
            while ($aRec = $aUsersQuery->fetch()) {
                if (!$assetResArr[$aRec->id]) continue;
                $opt = new stdClass();
                $opt->title = $assetResArr[$aRec->id];
                $opt->attr = array('class' => 'boldText');
                $assetResArr[$aRec->id] = $opt;
            }

            $form->setOptions('assetResourceId', $assetResArr);

            $form->setField('assetResourceId', 'input=input');
            $rec->_assetsAllowed = true;
        } else {
            $form->setField('assetResourceId', 'input=none');
            $rec->_assetsAllowed = false;
        }

        if (($srcId = $rec->srcId) && ($srcClass = $rec->srcClass)) {
            if (cls::haveInterface('support_IssueCreateIntf', $srcClass)) {
                $srcInst = cls::getInterface('support_IssueCreateIntf', $srcClass);
                $defaults = (array) $srcInst->getDefaultIssueRec($srcId);
                $form->setDefaults($defaults);
            }
        }

        $form->setField('timeStart', 'autohide');
        $form->setField('timeDuration', 'autohide');
        $form->setField('timeEnd', 'autohide');

        if(isset($rec->assetResourceId)){
            $issueOptions = planning_AssetGroupIssueTemplates::getAvailableIssues($rec->assetResourceId, $rec->issueTemplateId);
            if(countR($issueOptions)){
                $form->setField('issueTemplateId', 'input');
                $form->setOptions('issueTemplateId', $issueOptions);
            }
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
        if (core_Users::getCurrent() < 1) {
            log_Browsers::setVars(array('name' => $rec->name, 'email' => $rec->email));
        }
        
        if ($rec->srcId && $rec->srcClass && cls::haveInterface('support_IssueCreateIntf', $rec->srcClass)) {
            $srcInst = cls::getInterface('support_IssueCreateIntf', $rec->srcClass);
            $srcInst->afterCreateIssue($rec->srcId, $rec);
        }

        // Промяна кога е последно използван готовия сигнал
        if(isset($rec->issueTemplateId)){
            $iRec = planning_AssetGroupIssueTemplates::fetch($rec->issueTemplateId);
            $iRec->lastUsedOn = dt::now();
            planning_AssetGroupIssueTemplates::save($iRec, 'lastUsedOn');
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

        $data->toolbar->setBtnAttr("btnSubTask_{$data->rec->containerId}", 'row', 2);
        $data->toolbar->setBtnAttr("btnClose_{$data->rec->containerId}", 'row', 2);
        $data->toolbar->setBtnAttr("btnComment_{$data->rec->id}", 'row', 2);
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


    /**
     * Подготовка на сигналите към дадено оборудване
     *
     * @param stdClass $data
     * @return void
     */
    public static function prepareAssetSupport($data)
    {
        $data->TabCaption = tr('Сигнали');

        // Подготовка на данните
        $data->listFields = arr::make("hnd=Сигнал,title=Заглавие,progress=Прогрес,folderId=Папка", true);
        $Tasks = cls::get('cal_Tasks');
        $data->recs = $data->rows = array();
        $me = cls::get(get_called_class());
        $query = $Tasks->getQuery();
        $query->where("#driverClass = {$me->getClassId()} AND #state != 'rejected'");
        $query->where("#assetResourceId = {$data->masterId}");
        $query->orderBy('createdOn=DESC,id=DESC');
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => $data->itemsPerPage));
        $data->Pager->setPageVar($data->masterMvc->className, $data->masterId);
        $data->Pager->setLimit($query);

        // Вербализиране
        $fields = $Tasks->selectFields();
        $fields['-list'] = true;
        while($rec = $query->fetch()){
            $data->recs[$rec->id] = $rec;
            $row = cal_Tasks::recToVerbal($rec, $fields);
            $row->hnd = $Tasks->getLink($rec->id, 0);
            $row->title = $Tasks->getVerbal($rec, 'title');
            $data->rows[$rec->id] = $row;
        }
    }


    /**
     * Рендиране на сигналите към дадено оборудване
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public static function renderAssetSupport($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');

        $Tasks = cls::get('cal_Tasks');
        $table = cls::get('core_TableView', array('mvc' => clone $Tasks));
        $Tasks->invoke('BeforeRenderListTable', array($tpl, &$data));
        $tableTpl = $table->get($data->rows, $data->listFields);
        if (isset($data->Pager)) {
            $tpl->append($data->Pager->getHtml(), 'content');
        }

        $tpl->append($tableTpl, 'content');
        $tpl->append(tr("Сигнали към оборудването"), 'title');

        return $tpl;
    }


    /**
     * След вербализирането на данните
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $row
     * @param stdClass          $rec
     * @param array             $fields
     */
    protected static function on_AfterRecToVerbal($Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        if(!empty($rec->issueTemplateId)){
            $row->description = "{$row->issueTemplateId}</br>{$row->description}" ;
        }
    }


    /**
     * След извличане на етапите, споделени към системата
     *
     * @param $Driver
     * @param $Embedder
     * @param $res
     * @param $data
     * @return void
     */
    protected static function on_AfterGetEditFormStepOptions($Driver, $Embedder, &$res, &$data)
    {
        $rec = $data->form->rec;
        if(!countR($res)) return;
        if(!isset($rec->srcId) || !isset($rec->srcClass)) return;

        // Ако източника е ПО
        $Source = new core_ObjectReference($rec->srcClass, $rec->srcId);
        if($Source->isInstanceOf('planning_Tasks')) {
            $productId = $Source->fetchField('productId');

            // От наличните етапи остават САМО онези, които са без посочен етап или са за този от операцията
            $sQuery = doc_UnsortedFolderSteps::getQuery();
            $sQuery->in('id', array_keys($res));
            $sQuery->where("#productSteps IS NULL OR LOCATE('|{$productId}|', #productSteps) OR #id = '{$rec->stepId}'");
            $sQuery->show('id,productSteps');
            $res = array_intersect_key($res, arr::extractValuesFromArray($sQuery->fetchAll(), 'id'));
        }
    }
}
