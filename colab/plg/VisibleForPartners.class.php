<?php


/**
 * Плъгин за превръщане на документи във видими за партньори,
 * за които не е зададено твърдо 'visibleForPartners' пропърти
 *
 * @category  bgerp
 * @package   colab
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class colab_plg_VisibleForPartners extends core_Plugin
{


    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription($mvc)
    {
        if (!$mvc->fields['visibleForPartners']) {
            $mvc->FLD('visibleForPartners', 'enum(no=Не,yes=Да)', 'caption=Споделяне->С партньори,input=none,before=sharedUsers, maxRadio=3');
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        if ($rec->folderId) {

            $folderId = $rec->folderId;
            if(empty($rec->id) && ($mvc instanceof planning_Tasks)){
                if($requestFolderId = Request::get('folderId', 'int')){
                    $folderId = $requestFolderId;
                }
            }

            // Полето се показва ако е в папката, споделена до колаборатор
            // Ако няма originId или ако originId е към документ, който е видим от колаборатор
            if (colab_FolderToPartners::fetch(array("#folderId = '[#1#]'", $folderId))) {
                $doc = $dIsVisible = null;
                $showVisibleField = true;
                if(isset($rec->originId)){
                    $doc = doc_Containers::getDocument($rec->originId);
                    if(!$mvc->onlyFirstInThread){
                        $dIsVisible = $doc->isVisibleForPartners();
                        if(!$dIsVisible){
                            $showVisibleField = false;
                        }
                    } else{
                        $dIsVisible = true;
                    }
                }

                if ($showVisibleField) {

                    if (core_Users::haveRole('partner')) {
                        // Ако текущия потребител е контрактор, полето да е скрито
                        $data->form->setField('visibleForPartners', 'input=hidden');
                        $data->form->setDefault('visibleForPartners', 'yes');
                    } else {
                        $showField = true;
                        if(isset($rec->threadId) && !($mvc instanceof planning_Tasks)){
                            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
                            if($rec->containerId != $firstDoc->fetchField('containerId')){
                                if(!$firstDoc->isVisibleForPartners()){
                                    $showField = false;
                                }
                            }
                        }

                        if($showField){
                            $data->form->setField('visibleForPartners', 'input=input');
                        }
                    }
                    
                    if ($rec->originId) {
                        $dRec = $doc->fetch();
                        
                        // Ако документа е създаден от контрактор или предишния документ е видим, тогава да е споделен по-подразбиране
                        if (!$rec->id) {
                            if (core_Users::haveRole('partner', $dRec->createdBy) || $dIsVisible) {
                                $data->form->setDefault('visibleForPartners', 'yes');
                            }
                        }
                    }
                    
                    // Ако няма да се показва на колаборатори по-подразбиране, да е скрито полето
                    if ($rec->visibleForPartners !== 'yes') {
                        $data->form->setField('visibleForPartners', 'autohide');
                    }
                }

                if (!$rec->originId && $mvc->visibleForPartners && core_Users::isPowerUser()) {
                    $data->form->setDefault('visibleForPartners', 'yes');
                }
            }
        }
        
        $data->form->setField('visibleForPartners', 'changable=ifInput');
        if (!core_Users::isPowerUser()) {
            $data->form->setField('visibleForPartners', 'input=none');
        }

        // Сетваме стойността, ако не е зададена
        if (!$rec->id && !$rec->visibleForPartners) {
            $data->form->setDefault('visibleForPartners', 'no');
        }
        
        if (core_Users::haveRole('partner')) {
            $mvc->currentTab = 'Нишка';
            plg_ProtoWrapper::changeWrapper($mvc, 'cms_ExternalWrapper');
        }
    }


    /**
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако е споделен към колаборатор, който има достъп до папката, да може да вижда документа
        $rec = &$form->rec;

        if ($form->isSubmitted()) {

            // Ако има споделени партньори, добавя се предупреждение
            $selectedPartners = array();
            if(isset($rec->sharedUsers)){
                $sharedUsers = keylist::toArray($rec->sharedUsers);
                $partners = core_Users::getByRole('partner');
                $selectedPartners = array_intersect_key($sharedUsers, $partners);
            }

            if(!$form->gotErrors()){
                $allSharedUsersArr = array();
                $sharedUsersArrAll = array();
                $fName = '';

                // Обхождаме всички полета от модела, за да разберем кои са ричтекст
                foreach ((array) $mvc->fields as $name => $field) {
                    if ($field->type instanceof type_Richtext) {
                        if ($field->type->params['nickToLink'] == 'no') {
                            continue;
                        }

                        // Вземаме споделените потребители
                        $sharedUsersArr = rtac_Plugin::getNicksArr($rec->$name);
                        if (empty($sharedUsersArr)) {
                            continue;
                        }

                        // Обединяваме всички потребители от споделянията
                        $sharedUsersArrAll = array_merge($sharedUsersArrAll, $sharedUsersArr);
                        if (!empty($sharedUsersArrAll)) {
                            $fName = $fName? $fName : $field->name;
                        }
                    }
                }

                // Ако има споделяния
                if (!empty($sharedUsersArrAll)) {
                    // Добавяме id-тата на споделените потребители
                    foreach ((array) $sharedUsersArrAll as $nick) {
                        $nick = strtolower($nick);
                        $id = core_Users::fetchField(array("LOWER(#nick) = '[#1#]'", $nick), 'id');

                        if (!core_Users::haveRole('partner', $id)) {
                            continue;
                        }

                        $allSharedUsersArr[$id] = $id;
                    }
                }

                $errArray = array();
                if (!empty($allSharedUsersArr)) {
                    foreach ($allSharedUsersArr as $uId) {
                        unset($selectedPartners[$uId]);

                        if(core_Users::isContractor($uId) && core_Packs::isInstalled('colab')){

                            if(empty($rec->threadId)){
                                if(isset($rec->folderId)){
                                    if(!colab_Folders::haveRightFor('list', (object) array('folderId' => $rec->folderId), $uId)){
                                        $errArray[$uId] = core_Users::getNick($uId);
                                    }
                                } else {
                                    $errArray[$uId] = core_Users::getNick($uId);
                                }
                            } else {
                                if(!colab_Threads::haveRightFor('single', doc_Threads::fetch($rec->threadId), $uId)){
                                    $errArray[$uId] = core_Users::getNick($uId);
                                }
                            }
                        }
                    }

                    if (!empty($errArray)) {
                        $form->setError($fName, '|Документът не може да бъде споделен към|*: ' . implode(', ', $errArray) . '<br>|*Партньорът не е споделен към папката или няма достъп до нишката|*!');
                    } else {
                        $form->rec->visibleForPartners = 'yes';
                    }
                }
            }

            if (countR($selectedPartners)) {
                if($rec->visibleForPartners != 'yes'){
                    $nicks = array();
                    array_walk($selectedPartners, function($a) use (&$nicks) {$nicks[] = core_Users::getNick($a);});
                    $partnerWarningMsg = "При забранено споделяне с партньори, ще бъде заличено споделянето с|* " . implode(',', $nicks);
                    $form->setWarning('sharedUsers', $partnerWarningMsg);
                }
            }
        }
    }


    /**
     * Изпълнява се преди записа
     * Ако липсва - записваме id-то на връзката към титлата
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if($rec->visibleForPartners == 'no' && isset($rec->sharedUsers)) {
            $sharedUsers = keylist::toArray($rec->sharedUsers);
            $partners = core_Users::getByRole('partner');
            $withoutSelectedPartners = array_diff_key($sharedUsers, $partners);

            if(countR($sharedUsers) != countR($withoutSelectedPartners)){
                $rec->sharedUsers = keylist::fromArray($withoutSelectedPartners);
                core_Statuses::newStatus('Документът не е видим за партньори, затова са махнати споделените такива|*!', 'warning');
            }
        }
    }


    /**
     * Връща дали документа е видим за партньори
     *
     * @param core_Mvc     $mvc
     * @param NULL|string  $res
     * @param int|stdClass $rec
     */
    public static function on_BeforeIsVisibleForPartners($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        
        if (!isset($res)) {
            if ($rec->visibleForPartners === 'yes') {
                $res = true;
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на формата
     */
    public static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Контрактора да не може да създава чернова, а директно да активира
        if (core_Users::haveRole('partner')) {
            if ($data->form->toolbar->haveButton('activate')) {
                $data->form->toolbar->removeBtn('save');
                $data->form->toolbar->removeBtn('active');
                $data->form->toolbar->addSbBtn('Запис', 'active', 'id=activate,order=0.1, ef_icon = img/16/disk.png', 'title=Запис на документа');
            }
        }
    }
}
