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
            $mvc->FLD('visibleForPartners', 'enum(no=Не,yes=Да)', 'caption=Споделяне->С партньори,input=none,before=sharedUsers');
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
            
            // Полето се показва ако е в папката, споделена до колаборатор
            // Ако няма originId или ако originId е към документ, който е видим от колаборатор
            if (colab_FolderToPartners::fetch(array("#folderId = '[#1#]'", $rec->folderId))) {
                if (!$rec->originId || ($doc = doc_Containers::getDocument($rec->originId)) && ($dIsVisible = $doc->isVisibleForPartners())) {
                    if (core_Users::haveRole('partner')) {
                        // Ако текущия потребител е контрактор, полето да е скрито
                        $data->form->setField('visibleForPartners', 'input=hidden');
                        $data->form->setDefault('visibleForPartners', 'yes');
                    } else {
                        $data->form->setField('visibleForPartners', 'input=input');
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
        if ($form->rec->visibleForPartners == 'no') {
            if ($form->isSubmitted()) {
                $allSharedUsersArr = array();

                $rec = &$form->rec;

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
                        $cRec = colab_FolderToPartners::fetchField(array("#folderId = '[#1#]' AND #contractorId = '[#2#]'", $form->rec->folderId, $uId));

                        if (!$cRec) {
                            $errArray[$uId] = core_Users::getNick($uId);
                        }
                    }

                    if (!empty($errArray)) {
                        $form->setError($fName, '|Документът не може да бъде споделен към|*: ' . implode(', ', $errArray) . '<br>|*Контракторът не е споделен към папката');
                    } else {
                        $form->rec->visibleForPartners = 'yes';
                    }
                }
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
