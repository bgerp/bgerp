<?php


/**
 * Плъгин за проследяване и показване на историята на споделянията на документ
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_SharablePlg extends core_Plugin
{
    
    
    /**
     * След дефиниране на полетата на модела - добавя поле за споделените потребители.
     * 
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Този плъгин може да се прикача само към документи
        expect(cls::haveInterface('doc_DocumentIntf', $mvc), 'doc_SharablePlg е приложим само към документи');
        
        // Поле за потребителите, с които е споделен документа (ако няма)
        if (!$mvc->getField('sharedUsers', FALSE)) {
            $mvc->FLD('sharedUsers', 'userList', 'caption=Споделяне->Потребители');
        }
        // Поле за първите виждания на документа от потребителите с които той е споделен
        if (!$mvc->getField('sharedViews', FALSE)) {
            // Стойността на полето е сериализиран масив с ключ - потребител и стойност - дата
            // на първо виждане от потребителя
            $mvc->FLD('sharedViews', 'blob', 'caption=Споделяне->Виждания,input=none');
        }
        
        // Дали да са споделени потребителите от оригиналния документ (ако създателят е един и същи)
        setIfNot($mvc->autoShareOriginShared, TRUE);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            
            $rec = &$form->rec;
            
            $sharedUsersArrAll = array();
            
            // Обхождаме всички полета от модела, за да разберем кои са ричтекст
            foreach ((array)$mvc->fields as $name => $field) {
                if ($field->type instanceof type_Richtext) {
                    
                    if ($field->type->params['nickToLink'] == 'no') continue;
                    
                    // Вземаме споделените потребители
                    $sharedUsersArr = rtac_Plugin::getNicksArr($rec->$name);
                    if (empty($sharedUsersArr)) continue;
                    
                    // Обединяваме всички потребители от споделянията
                    $sharedUsersArrAll = array_merge($sharedUsersArrAll, $sharedUsersArr);
                }
            }
            
            // Ако има споделяния
            if (!empty($sharedUsersArrAll)) {
                
                // Добавяме id-тата на споделените потребители
                foreach ((array)$sharedUsersArrAll as $nick) {
                    $nick = strtolower($nick);
                    $id = core_Users::fetchField(array("LOWER(#nick) = '[#1#]'", $nick), 'id');
                    $rec->sharedUsers = type_Keylist::addKey($rec->sharedUsers, $id);
                }
            }
        }
    }
    
    
    /**
     * След рендиране на документ отбелязва акта на виждането му от тек. потребител
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param unknown_type $data
     */
    public static function on_AfterRenderSingle(core_Mvc $mvc, &$tpl, $data)
    {
        if (Request::get('Printing')) {
            // В режим на печат, маркираме документа като видян.
            // Ако не сме в режим печат, маркирането става в on_AfterRenderDocument() 
            static::markViewed($mvc, $data);
        }  
        
        // Ако не сме в xhtml режим
        if (!Mode::is('text', 'xhtml')) {
            
            $data->rec->sharedUsers = $mvc->getShared($data->rec->id);
            $history = static::prepareHistory($data->rec);
                    
            // показваме (ако има) с кого е споделен файла
            if (!empty($history)) {
                $tpl->replace(static::renderSharedHistory($history), 'shareLog');
            }
        }
    }
    
    
    /**
     * След рендиране на документ отбелязва акта на виждането му от тек. потребител
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param unknown_type $data
     */
    public static function on_AfterRenderDocument(core_Mvc $mvc, &$tpl, $id, $data)
    {
        static::markViewed($mvc, $data);
    }
    
    
    /**
     * Помощен метод: маркиране на споделен док. като видян от тек. потребител
     * 
     * @param stdClass $data
     */
    protected static function markViewed($mvc, $data)
    {
        $rec = $data->rec;
        
        if ($rec->state == 'draft' || $rec->state == 'rejected') {
            // На практика документа не е споделен
            return;
        }
        
        $userId = core_Users::getCurrent('id');
        
        if (!keylist::isIn($userId, $mvc->getShared($data->rec->id))) {
            // Документа не е споделен с текущия потребител - не правим нищо
            return;
        }
        
        $viewedBy = array();
        
        if (!empty($rec->sharedViews)) {
            $viewedBy = unserialize($rec->sharedViews);
        }
        
        if (!isset($viewedBy[$userId])) {
            // Първо виждане на документа от страна на $userId
            $viewedBy[$userId] = dt::now(TRUE);
            $rec->sharedViews = serialize($viewedBy);
            $rec->modifiedOn = dt::verbal2mysql();
            
            if ($mvc->save_($rec, 'sharedViews,modifiedOn')) {
                core_Cache::remove($mvc->className, $data->cacheKey . '%');
                if($rec->containerId) {
                    $cRec = new stdClass();
                    $cRec->id = $rec->containerId;
                    $cRec->modifiedOn = $rec->modifiedOn;
                    $cRec->modifiedBy = $userId;
                    $dCon = cls::get("doc_Containers");
                    $dCon->save_($cRec, 'modifiedOn,modifiedBy');
                }
            }
        }
    }
    
    
    /**
     * Помощен метод: подготовка на информацията за споделяне на документ
     * 
     * @param stdClass $rec обект-контейнер
     * @return array масив с ключове - потребителите, с които е споделен документа и стойност
     *                 датата, на която съотв. потребител е видял документа за пръв път (или
     *                 NULL, ако не го е виждал никога)
     */
    protected static function prepareHistory($rec)
    {
        $history = keylist::toArray($rec->sharedUsers);
        $history = array_fill_keys($history, NULL);
        
        if (!empty($rec->sharedViews)) {
            $history = unserialize($rec->sharedViews) + $history;
        }
        
        return $history;
    }
    
    
    /**
     * Помощен метод: рендира историята на споделянията и вижданията
     *
     * @param array $sharedWith масив с ключ ИД на потребител и стойност - дата
     * @return string
     */
    public static function renderSharedHistory($sharedWith)
    {
        expect(is_array($sharedWith), $sharedWith);
        
        $html = array();
        
        foreach ($sharedWith as $userId => $seenDate) {
            $nick = crm_Profiles::createLink($userId);
            
            if (!empty($seenDate)) {
                $seenDate = mb_strtolower(core_DateTime::mysql2verbal($seenDate, 'smartTime'));
                $seenDate = " ({$seenDate})";
            }

            $html[] = "<span style='color:black;'>" . $nick . "</span>{$seenDate}";
        }
        
        $htmlStr = implode(', ', $html);
        
        $htmlStr = "$htmlStr";
        
        return $htmlStr;
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::getShared()
     */
    function on_AfterGetShared($mvc, &$shared, $id)
    {
        // Потребители на коит е споделен документа
        $sharedInDocs = $mvc->fetchField($id, 'sharedUsers');
        
        // Обединяваме потребителите, на които е споделен
        $shared = keylist::merge($sharedInDocs, $shared);
    }
    
    
    /**
     * 
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
        
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
            
            // Да има само 2 колони
            $data->form->setField('sharedUsers', array('maxColumns' => 2));    
        }
        
        // изчисляваме колко са потребителите със съответните роли
        $roles = $data->form->getField('sharedUsers')->type->params['roles'];

        $roles = core_Roles::getRolesAsKeylist($roles);

        $roles = keylist::toArray($roles);
        
        $allUsers = core_Users::getRolesWithUsers();
        $users = array();

        foreach($roles as $rId) {
            if(is_array($allUsers[$rId])) {
                $users += $allUsers[$rId];
            }
        }
   
        if(count($users) > core_Setup::get('AUTOHIDE_SHARED_USERS')) {
            $data->form->setField('sharedUsers', 'autohide');    
        }

        if(isset($mvc->shareUserRoles)){
        	$sharedRoles = arr::make($mvc->shareUserRoles, TRUE);
        	$sharedRoles = implode(',', $sharedRoles);
        	
        	// Ако има зададени роли за търсене
        	if($form->getField('sharedUsers', FALSE)){
        		$form->setFieldTypeParams('sharedUsers', array('roles' => $sharedRoles));
        	}
        }
    }
    
    
    /**
     * Прихваща извикването на AfterSaveLogChange в change_Plugin
     * Добавя нотификация след промяна на документа
     * 
     * @param core_MVc $mvc
     * @param array $recsArr - Масив със записаните данни
     */
    function on_AfterSaveLogChange($mvc, $recsArr)
    {
        $mvcClassId = core_Classes::getId($mvc);
        foreach ($recsArr as $rec) {
            if ($mvcClassId != $rec->docClass) continue;
            $mRec = $mvc->fetch($rec->docId);
            
            if (!$mRec->threadId || !$mRec->containerId) continue;
            
            $cRec = doc_Containers::fetch($mRec->containerId);
            
            // Всички споделени и абонирани потребители
            $sharedArr = doc_ThreadUsers::getShared($mRec->threadId);
            $subscribedArr = doc_ThreadUsers::getSubscribed($mRec->threadId);
            $subscribedArr += $sharedArr;
            
            doc_Containers::addNotifications($subscribedArr, $mvc, $cRec, 'промени');
            
            break;
        }
    }
    
    
    /**
     * Прихваща извикването на AfterInputChanges в change_Plugin
     * 
     * @param core_MVc $mvc
     * @param object $oldRec - Стария запис
     * @param object $newRec - Новия запис
     */
    function on_AfterInputChanges($mvc, $oldRec, $newRec)
    {
        doc_Containers::changeNotifications($newRec, $oldRec->sharedUsers, $newRec->sharedUsers);
    }
    
    
    /**
     * Връща споделените потребители по подразбиране.
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param integer $originId
     */
    function on_AfterGetDefaultShared($mvc, &$res, $rec, $originId = NULL)
    {
        $res = arr::make($res, TRUE);
        
        if (!isset($originId)) return ;
        
        if (!$mvc->autoShareOriginShared) return ;
        
        $document = doc_Containers::getDocument($originId);
        $dRec = $document->fetch();
        
        $createdBy = NULL;
        
        if ($dRec->createdBy > 0) {
            $createdBy = $dRec->createdBy;
        } elseif ($dRec->modifiedBy > 0) {
            $createdBy = $dRec->modifiedBy;
        }
        
        // Ако създадетеля на оригиналния документ е текущия
        if (isset($createdBy)) {
            $currUserId = core_Users::getCurrent();
            if ($createdBy == $currUserId) {
                if ($dRec->sharedUsers) {
                    $sharedArr = type_Keylist::toArray($dRec->sharedUsers);
                    unset($sharedArr[$currUserId]);
                    $res += $sharedArr;
                }
        
                // Предотвратяване на евентуално зацикляне
                static $originArr = array();
                
                if ($dRec->originId && !$originArr[$dRec->originId]) {
                    $originArr[$dRec->originId] = TRUE;
                    $sharedArr = $mvc->getDefaultShared($dRec, $dRec->originId);
                    $res += $sharedArr;
                }
            }
        }
        
        // Ако потребителя се е отписал от папката, да не излиза при автоматично споделените
        if ($rec->folderId) {
            $sKey = doc_Folders::getSettingsKey($rec->folderId);
            $noNotifyArr = core_Settings::fetchUsers($sKey, 'newDoc', 'no');
            
            if ($noNotifyArr) {
                $keysArr = array_keys($noNotifyArr);
                $res = array_diff($res, $keysArr);
            }
        }
        
        // Ако потребителя се е отписал от нишката, да не излиза при автоматично споделените
        if ($rec->threadId) {
            $sKey = doc_Threads::getSettingsKey($rec->threadId);
            $noNotifyArr = core_Settings::fetchUsers($sKey, 'notify', 'no');
            
            if ($noNotifyArr) {
                $keysArr = array_keys($noNotifyArr);
                $res = array_diff($res, $keysArr);
            }
        }
    }
    
    
    /**
     * Преди записване на клонирания запис
     * 
     * @param core_Mvc $mvc
     * @param object $rec
     * @param object $nRec
     * 
     * @see plg_Clone
     */
    function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        // Премахваме ненужните полета
        unset($nRec->sharedViews);
    }
}
