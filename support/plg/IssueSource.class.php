<?php


/**
 * Плъгин за документи, които да са източници на сигнали
 *
 *
 * @category  bgerp
 * @package   support
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class support_plg_IssueSource extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('support_IssueCreateIntf');
        setIfNot($mvc->canSelectissuefolder, 'powerUser');
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        if($mvc->haveRightFor('selectissuefolder', (object)array('originId' => $rec->containerId))){
            $data->toolbar->addBtn('Сигнал', array($mvc, 'selectissuefolder', 'originId' => $rec->containerId, 'ret_url' => true), "id=issueBtn{$rec->containerId},title=Създаване на сигнал към документа,ef_icon=img/16/support.png");
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
        if($action == 'selectissuefolder'){
            $mvc->requireRightFor('selectissuefolder');
            expect($originId = Request::get('originId', 'int'));
            $mvc->requireRightFor('selectissuefolder', (object)array('originId' => $originId));

            $Document = doc_Containers::getDocument($originId);
            $Intf = cls::getInterface('support_IssueCreateIntf', $Document->getInstance());
            $folders = $Intf->getIssueSystemFolders($Document->that);
            $folderOptions = array();
            foreach ($folders as $folderId){
                $folderOptions[$folderId] = doc_Folders::getTitleById($folderId, false);
            }

            if(countR($folderOptions) == 1){
                if(!cal_Tasks::haveRightFor('add', (object)array('folderId' => key($folderOptions)))){
                    followRetUrl(null, 'Нямате права да пускате в система|*: ' . $folderOptions[key($folderOptions)], 'error');
                }

                redirect(array('cal_Tasks', 'add', 'folderId' => $folders[key($folders)], 'srcClass' => $Document->getClassId(), 'srcId' => $Document->that, 'ret_url' => getRetUrl()));
            }

            $form = cls::get('core_Form');
            $form->title = "Подаване на сигнал от|* " . $Document->getFormTitleLink();
            $form->FLD('folderId', 'int', 'caption=В система');
            $form->setOptions('folderId', $folderOptions);
            $form->setDefault('folderId', key($folderOptions));
            $form->input();

            if($form->isSubmitted()){
                $folderId = $form->rec->folderId;
                if(!cal_Tasks::haveRightFor('add', (object)array('folderId' => $folderId))){
                    $form->setError('folderId', 'Нямате права да пускате сигнал в системата');
                }

                if(!$form->gotErrors()){
                    redirect(array('cal_Tasks', 'add', 'folderId' => $folderId, 'srcId' => $Document->that, 'srcClass' => $Document->getClassId(), 'ret_url' => true));
                }
            }

            $form->toolbar->addSbBtn('Напред', 'save', 'ef_icon = img/16/disk.png, title = Напред');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');


            $res = $mvc->renderWrapping($form->renderHtml());


            return false;
        }
    }


    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $user = null)
    {
        if($action == 'selectissuefolder' && isset($rec)){
            if(!cal_Tasks::haveRightFor('add')){
                $requiredRoles = 'no_one';
            } elseif(isset($rec->originId)){
                $Document = doc_Containers::getDocument($rec->originId);
                $Intf = cls::getInterface('support_IssueCreateIntf', $Document->getInstance());
                $folders = $Intf->getIssueSystemFolders($Document->that);
                if(!countR($folders)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Връща папките на системите, в които може да се пусне сигнала по подразбиране
     */
    public static function on_AfterGetIssueSystemFolders($mvc, &$res, $rec)
    {
        if(!$res){
            $res = array();
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterGetDefaultIssueRec($mvc, &$res, $rec)
    {
        if(!$res){
            $res = new stdClass();
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterAfterCreateIssue($mvc, &$res, $rec, $iRec)
    {
        if(!isset($res)){
            $rec = $mvc->fetchRec($rec);
            doc_Linked::add($rec->containerId, $iRec->containerId, 'doc', 'doc', support_IssueTypes::fetchField($iRec->typeId, 'type'));
        }
    }
}