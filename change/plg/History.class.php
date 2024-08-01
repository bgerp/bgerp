<?php


/**
 * Клас 'change_plg_History' - Плъгин за логване на промяна в записите
 *
 * @category  bgerp
 * @package   change
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class change_plg_History extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->loggableFields, '');
        $mvc->FLD('validFrom', 'datetime', 'caption=Валидност->От,input=none');
        $mvc->FLD('validTo', 'datetime', 'caption=Валидност->До,input=none');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        if(isset($form->rec->id)){
            $form->FLD("newValidFrom", 'datetime', 'input,caption=Валидност->От,autohide');
        }
    }

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if(!empty($rec->saveVersionDate)){
            if($rec->saveVersionDate <= dt::now()){
                if(!haveRole('admin')){
                    $form->setError('versionDate', "Версията не може да е валидна с минала дата|*!");
                }
            }
        }
    }


    /**
     * Връща хеша на новите полета
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return string
     */
    protected static function getNewRecHash($mvc, $rec)
    {
        // Попълване на река с наблюдаваните полета
        $fieldArr = array();
        $loggableFields = arr::make($mvc->loggableFields, true);
        $exRec = ($rec->id) ? $mvc->fetch($rec->id, $loggableFields, false) : null;
        foreach ($loggableFields as $field){
            $fieldArr[$field] = property_exists($rec, $field) ? trim($rec->{$field}) : tr($exRec->{$field});
            $noArr[$field] = $fieldArr[$field];
            $fieldArr[$field] = str_replace("\n\r", '', $fieldArr[$field]);
            $fieldArr[$field] = str_replace("\r\n", '', $fieldArr[$field]);
            $fieldArr[$field] = str_replace("\n", '', $fieldArr[$field]);
        }
        ksort($fieldArr);

        return md5(arr::fromArray($fieldArr));
    }


    /**
     * Връща хеша на старите полета
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return string
     */
    protected static function getOldRecHash($mvc, &$rec)
    {
        $fieldArr = $noArr = array();
        if(isset($rec->id)){
            $loggableFields = arr::make($mvc->loggableFields, true);
            $exRec = $mvc->fetch($rec->id, '*', false);
            foreach ($loggableFields as $field){
                $fieldArr[$field] = trim($exRec->{$field});
                $noArr[$field] = $fieldArr[$field];
                $fieldArr[$field] = str_replace("\n\r", '', $fieldArr[$field]);
                $fieldArr[$field] = str_replace("\r\n", '', $fieldArr[$field]);
                $fieldArr[$field] = str_replace("\n", '', $fieldArr[$field]);
            }
            $rec->_oldRec = $exRec;
        }
        ksort($fieldArr);

        return md5(arr::fromArray($fieldArr));
    }


    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc $mvc Мениджър, в който възниква събитието
     * @param int $id Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass $rec Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields Имена на полетата, които трябва да бъдат записани
     * @param string $mode Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $rec->_oldFieldHash = static::getOldRecHash($mvc, $rec);
    }


    /**
     * Генериране на searchKeywords когато плъгинът е ново-инсталиран на модел в който е имало записи
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        if(!$mvc->count("#validFrom IS NULL")) return;

        $validFromColName = str::phpToMysqlName('validFrom');
        $createdOnColName = str::phpToMysqlName('createdOn');
        $query = "UPDATE {$mvc->dbTableName} SET {$validFromColName} = {$createdOnColName} WHERE {$validFromColName} IS NULL";
        $mvc->db->query($query);
    }


    /**
     * След всеки запис в журнала
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param stdClass $rec
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        // Ако има промяна в наблюдаваните полета
        $newFieldHash = static::getNewRecHash($mvc, $rec);
        if($rec->_oldFieldHash == $newFieldHash) return;

        $rec->validFrom = !empty($rec->validFrom) ? $rec->validFrom : dt::now();
        $sync = false;
        if(!empty($rec->validFrom) && $rec->validFrom != $rec->_oldRec->validFrom){
            $sync = true;
        } else {
            $modifiedBy = $rec->modifiedBy ?? $rec->_oldRec->modifiedBy;
            $time = change_Setup::get('LOG_VERSION_AFTER_LAST');
            $before2hours = dt::addSecs(-1 * $time);
            if($rec->_oldRec->modifiedOn < $before2hours || $modifiedBy != core_Users::getCurrent()){
                $sync = true;
            }
        }

        if(!$sync) return;
        $rec->validFrom = !empty($rec->newValidFrom) ? $rec->newValidFrom : dt::now();
        $updateFields = array();
        $currentRecData = change_History::getCurrentRec($mvc->getClassId(), $rec->id, $rec->_oldRec, $rec, $updateFields);

        if(countR($updateFields)){
            foreach ((array)$currentRecData as $cFld => $cVal){
                $rec->{$cFld} = $cVal;
            }
            $mvc->save_($rec, $updateFields);
            $msg = "Текущият запис ще е в сила до|*: " . dt::mysql2verbal($rec->validTo, 'd.m.Y H:i');
            core_Statuses::newStatus($msg);
        } else {
            $rec->validTo = $currentRecData->validTo;
            $mvc->save_($rec, 'validFrom,validTo');
        }
    }


    /**
     * Изпълнява се след извличане на запис чрез ->fetch()
     */
    protected static function on_AfterRead($mvc, $rec)
    {
        // Ако записа е валиде до конкретна дата
        if(empty($rec->validTo)) return;
        $now = dt::now();

        // и СЕГА сме след тази дата
        if($rec->validTo <= $now){
            $updateFields = array();

            // Проверка има ли нова версия влязла в сила
            $replaceRec = change_History::getCurrentRec($mvc, $rec->id, $rec, null, $updateFields);
            if(countR($updateFields)){

                // Ако има - редактират се данните от река и се заместват
                foreach ((array)$replaceRec as $cFld => $cVal){
                    $rec->{$cFld} = $cVal;
                }

                $cancelSysUserId = false;
                if (!core_Users::isSystemUser()) {
                    core_Users::forceSystemUser();
                    $cancelSysUserId = true;
                }

                // Подмяна на записа, логване на действието
                $rec->modifiedBy = core_Users::getCurrent();
                $rec->modifiedOn = $now;
                $updateFields['modifiedOn'] = 'modifiedOn';
                $updateFields['modifiedBy'] = 'modifiedBy';
                $mvc->save_($rec, $updateFields);
                $mvc->logWrite('Сменена версия с бъдеща', $rec->id, 360, core_Users::getCurrent());

                if (cls::haveInterface('doc_FolderIntf', $mvc)) {
                    bgerp_Notifications::add("Влязла в сила версия на|*: {$mvc->getTitleById($rec->id)}", array($mvc, 'single', $rec->id), $rec->inCharge);
                }

                if($cancelSysUserId){
                    core_Users::cancelSystemUser();
                }
            }
        }
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param core_Master $mvc
     * @param core_ET     $tpl
     * @param object      $data
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        bgerp_Notifications::clear(array($mvc, 'single', $data->rec->id));
    }


    /**
     * След подготовка на тулбара за единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (change_History::haveRightFor('list')) {
            $validFromVerbal = $mvc->getFieldType('validFrom')->toVerbal($data->rec->validFrom);
            $validToVerbal = !empty($data->rec->validTo) ? " / " .  $mvc->getFieldType('validFrom')->toVerbal($data->rec->validTo) : null;
            $data->toolbar->addBtn("Вер.|* {$validFromVerbal} {$validToVerbal}", array('change_History', 'list', 'classId' => $mvc->getClassId(), 'objectId' => $data->rec->id), 'ef_icon=img/16/bug.png,title=Версии');
        }
    }
}