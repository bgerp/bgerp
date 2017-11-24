<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_TaskType extends core_Mvc
{
    
    
    /**
     * 
     */
    public $interfaces = 'cal_TaskTypeIntf';
    
    
    /**
     * 
     */
    public $title = 'Сигнал';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('typeId', 'key(mvc=support_IssueTypes, select=type)', 'caption=Тип, mandatory, width=100%, silent, after=title');
        $fieldset->FLD('componentId', 'key(mvc=support_Components,select=name,allowEmpty)', 'caption=Компонент, after=typeId, refreshForm, silent');
        $fieldset->FLD('systemId', 'key(mvc=support_Systems, select=name)', 'caption=Система, input=hidden, silent');
        
        $fieldset->FLD('name', 'varchar(64)', 'caption=Данни за обратна връзка->Име, mandatory, input=none');
        $fieldset->FLD('email', 'email', 'caption=Данни за обратна връзка->Имейл, mandatory, input=none');
        $fieldset->FLD('url', 'varchar', 'caption=Данни за обратна връзка->URL, input=none');
        $fieldset->FLD('ip', 'ip', 'caption=Ип,input=none');
        $fieldset->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
    }
    
    
    /**
     * Може ли вградения обект да се избере
     * 
     * @param NULL|integer $userId
     * 
     * @return boolean
     */
    public function canSelectDriver($userId = NULL)
    {
        
        return TRUE;
    }
    
    
    /**
     * Връща подсказките за добавяне на прогрес
     * 
     * @param  stdClass $tRec
     * 
     * @return array
     */
    public function getProgressSuggestions($tRec)
    {
        $progressArr = array();
        
        $progressArr['0 %'] = '0 %';
        $progressArr['10 %'] = 'Информация';
        $progressArr['40 %'] = 'Корекция';
        $progressArr['60 %'] = 'Превенция';
        $progressArr['80 %'] = 'Оценка';
        $progressArr['100 %'] = 'Резолюция';
        
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
        $form->setField('componentId', 'input=none');
        $form->setField('name', 'input');
        $form->setField('email', 'input');
        $form->setField('url', 'input=hidden, silent');
        
        $systemId = Request::get('systemId', 'int');
        
        $allowedTypesArr = support_Systems::getAllowedFieldsArr($systemId);
        
        $atOpt = array();
        foreach($allowedTypesArr as $tId) {
            $atOpt[$tId] =  support_IssueTypes::fetchField($tId, 'type');
        }
        
        $form->setOptions('typeId', $atOpt);
        
        if(!haveRole('user')) {
            $brid = log_Browsers::getBrid(FALSE);
            if($brid) {
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
     * 
     * 
     * @param support_TaskType $Driver
     * @param cal_Tasks $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($Driver, $mvc, &$res, $data)
    {
        $systemId = Request::get('systemId', 'key(mvc=support_Systems, select=name)');
        
        if (!$systemId && $data->form->rec->folderId) {
            $coverClassRec = doc_Folders::fetch($data->form->rec->folderId);
            
            if ($coverClassRec->coverClass && (cls::get($coverClassRec->coverClass) instanceof support_Systems)) {
                $systemId = $coverClassRec->coverId;
            }
        }
        
        // Ограничаваме избора на компоненти и типове, само до тези, които ги има в системата
        if ($systemId) {
            $allSystemsArr = array();
            if ($systemId) {
                $allSystemsArr = support_Systems::getSystems($systemId);
            }
            
            $componentsArr = array();
            $typesArr = array();
            
            if (!empty($allSystemsArr)) {
                $componentQuery = support_Components::getQuery();
                
                $componentQuery->orWhereArr('systemId', $allSystemsArr);
                
                if ($data->form->rec->componentId) {
                    $componentQuery->orWhere(array("#id = '[#1#]'", $data->form->rec->componentId));
                }
                
                while ($cRec = $componentQuery->fetch()) {
                    $componentsArr[$cRec->id] = $cRec->name;
                }
                
                $allowedTypesArr = support_Systems::getAllowedFieldsArr($allSystemsArr);
                
                if ($data->form->rec->typeId) {
                    $allowedTypesArr[$data->form->rec->typeId] = $data->form->rec->typeId;
                }
                
                foreach ($allowedTypesArr as $allowedType) {
                    $typesArr[$allowedType] = support_IssueTypes::fetchField($allowedType, 'type');
                }
            }
            
            if (!empty($componentsArr)) {
                $componentsArr = array_unique($componentsArr);
                asort($componentsArr);
            }
            $data->form->setOptions('componentId', $componentsArr);
            
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
        
        if ($data->form->cmd == 'refresh') {
            // При избор на компонент, да са избрани споделените потребители, които са отговорници
            if ($data->form->rec->componentId) {
                $maintainers = support_Components::fetchField($data->form->rec->componentId, 'maintainers');
                $maintainers = keylist::removeKey($maintainers, core_Users::getCurrent());
                $data->form->setDefault('sharedUsers', $maintainers);
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_BeforeSave($Driver, $mvc, &$id, $rec)
    {
        if(!haveRole('powerUser')) {
            if (!$rec->ip) {
                $rec->ip = core_Users::getRealIpAddr();
            }
                
            if (!$rec->brid) {
                $rec->brid = log_Browsers::getBrid();
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave($Driver, $mvc, &$id, $rec)
    {
        if ($rec->componentId) {
            support_Components::markAsUsed($rec->componentId);
        }
        
        if (core_Users::getCurrent() < 1) {
            log_Browsers::setVars(array('name' => $rec->name, 'email' => $rec->email));
        }
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks $mvc
     * @param NULL|array $resArr
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetFieldForLetterHead($Driver, $mvc, &$resArr, $rec, $row)
    {
        if ($row->systemId) {
            $resArr['systemId'] =  array('name' => tr('Система'), 'val' => "[#systemId#]");
        }
        
        if ($row->componentId) {
            $resArr['componentId'] =  array('name' => tr('Компонент'), 'val' => "[#componentId#]");
        }
        
        if ($row->typeId) {
            $resArr['typeId'] =  array('name' => tr('Тип'), 'val' => "[#typeId#]");
        }
        
        if ($row->name) {
            $resArr['name'] =  array('name' => tr('Име'), 'val' => "[#name#]");
        }
        
        if ($row->email) {
            $resArr['email'] =  array('name' => tr('Имейл'), 'val' => "[#email#]");
        }
        
        if (trim($rec->url)) {
            
            // Когато стойността е празна, трябва да върнем NULL
            $url = trim($rec->url);
            
            $attr = array();
            $attr['target'] = '_blank';
            $attr['class'] = 'out';
            if(!strpos($url, '://')) {
                $url = 'http://' . $url;
            }
            
            $v = mb_substr($url, 0, 50);
            
            if ($v != $url) {
                $v .= '...';
            }
            $url = HT::createLink($v, $url, FALSE, $attr);
            
            $resArr['url'] =  array('name' => tr('URL'), 'val' => $url);
        }
        
        if ($row->ip) {
            $resArr['ip'] =  array('name' => tr('IP'), 'val' => "[#ip#]");
        }
        
        if (trim($rec->brid) && trim($row->brid)) {
            $bridLink = log_Browsers::getLink(trim($rec->brid));
            if ($bridLink) {
                $resArr['brid'] =  array('name' => tr('BRID'), 'val' => $bridLink);
            }
        }
        
        if ($resArr['ident']['name']) {
            $resArr['ident']['name'] = tr($Driver->title);
        }
    }
    
    
    /**
     * Кои полета да са скрити във вътрешното или външното показване
     * 
     * @param support_TaskType $Driver
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetHideArrForLetterHead($Driver, $mvc, &$res, $rec, $row)
    {
        $res = arr::make($res);
        
        $res['external']['url'] = TRUE;
        $res['external']['brid'] = TRUE;
        $res['external']['ip'] = TRUE;
        $res['external']['createdBy'] = TRUE;
        $res['external']['progressBar'] = TRUE;
        $res['external']['driverClass'] = TRUE;
        $res['external']['priority'] = TRUE;
    }
    
    
    /**
     * Добавя ключовите думи от допълнителните полета
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks $mvc
     * @param object $res
     * @param object $rec
     */
    function on_AfterGetSearchKeywords($Driver, $mvc, &$res, $rec)
    {
        $sTxt = $rec->name . ' ' . $rec->email . ' ' . $rec->ip . ' ' . $rec->url;
        
        if (trim($sTxt)) {
            $res .= ' ' . plg_Search::normalizeText($sTxt);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks $mvc
     * @param object $res
     * @param object $data
     */
    static function on_AfterPrepareSingleToolbar($Driver, $mvc, &$res, $data)
    {
        if ($data->rec->state != 'rejected' && $data->rec->brid && email_Outgoings::haveRightFor('add')) {
            $data->toolbar->addBtn('Отговор', array(
                    'email_Outgoings',
                    'add',
                    'originId' => $data->rec->containerId,
                    'ret_url'=> TRUE
            ),'ef_icon = img/16/email_edit.png,title=Отговор на сигнал чрез имейл', 'onmouseup=saveSelectedTextToSession("' . $mvc->getHandle($data->rec->id) . '");');
        }
    }
}
