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
        setIfNot($mvc->loggableAdditionalComparableFields, '');

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
        if(!empty($rec->newValidFrom)){
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
        $rec->validFrom = !empty($rec->newValidFrom) ? $rec->newValidFrom : (dt::today() . " 00:00:00");
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
     * Изпълнява се след закачане на детайлите
     */
    public static function on_AfterAttachDetails(core_Mvc $mvc, &$res, $details)
    {
        $details = arr::make($mvc->details);
        $details['change_History'] = 'change_History';
        $mvc->details = $details;
    }


    /**
     * След подготовка на сингъла
     *
     * @param $mvc
     * @param $res
     * @param $data
     * @return void
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        if($data->skip) return;

        $rec = &$data->rec;
        $row = &$data->row;

        if(isset($rec->validFrom)){
            $oneMothAgo = dt::addMonths(-1);
            if($rec->validFrom < $oneMothAgo || $rec->validFrom == dt::today() . " 00:00:00"){
                unset($row->validFrom);
            }
        }

        $loggableFields = arr::make($mvc->loggableFields, true) + arr::make($mvc->loggableAdditionalComparableFields, true);
        $loggableFields['validFrom'] = 'validFrom';
        $loggableFields['validTo'] = 'validTo';

        // Кои са избраните версии за преглед от сингъла
        $selected = change_History::getSelectedVersionsArr($mvc->getClassId(), $rec->id);

        $count = countR($selected);

        // Ако е само една
        if($count == 1){

            // Ако е текущата - нищо не се прави
            $versionId = key($selected);
            if($versionId == change_History::CURRENT_VERSION_ID) return;

            // Ако не е текущата зареждат се данните от избрания рек
            $clone = static::getVersionRec($mvc, $rec, $versionId, $loggableFields);
            $res->row = $mvc->recToVerbal($clone, arr::combine($data->singleFields, '-single'));
            $res->row->VERSION_STRING = $selected[$versionId]['verString'];
            $res->row->VERSION_STRING_HINT = tr('Показване на записа към версията от|* ') . dt::mysql2verbal($selected[$versionId]['date']);
        } elseif($count == 2) {

            // Ако има избрани две различни версии
            $firstVersionId = key($selected);
            $lastVersionId = key(array_slice($selected, -1, 1, true));

            if($firstVersionId == $lastVersionId) return;

            // Подготвят се записите спрямо версиите от историята
            $firstRec = static::getVersionRec($mvc, $rec, $firstVersionId, $loggableFields);
            $lastRec = static::getVersionRec($mvc, $rec, $lastVersionId, $loggableFields);

            // Подготвяне на вербалните данни на двете версии
            $mvc->prepareSingleFields($data);
            $data->singleFields['-compare'] = true;
            $firstRow = $mvc->recToVerbal($firstRec, arr::combine($data->singleFields, '-single'));
            $lastRow = $mvc->recToVerbal($lastRec, arr::combine($data->singleFields, '-single'));

            // Сравняват се двата варианта
            foreach ($loggableFields as $fld){
                $newFieldVal = lib_Diff::getDiff($firstRow->{$fld}, $lastRow->{$fld});

                // Добавяне на pending полетата от новия запис
                if ($firstRow->{$fld} instanceof core_ET) {
                    $newFieldVal = new ET($newFieldVal);
                    foreach ((array) $firstRow->{$fld}->pending as $pending) {
                        $newFieldVal->addSubstitution($pending->str, $pending->place, $pending->once, $pending->mode);
                    }
                }

                // Добавяне на pending полетата от стария запис
                if ($lastRow->{$fld} instanceof core_ET) {
                    $newFieldVal = new ET($newFieldVal);
                    foreach ((array) $lastRow->{$fld}->pending as $pending) {
                        $newFieldVal->addSubstitution($pending->str, $pending->place, $pending->once, $pending->mode);
                    }
                }

                $res->row->{$fld} = $newFieldVal;
                $res->row->VERSION_STRING = $selected[$firstVersionId]['verString'] . "/" . $selected[$lastVersionId]['verString'];
                $res->row->VERSION_STRING_HINT = tr('Сравняване на версиите|*: ') . dt::mysql2verbal($selected[$firstVersionId]['date']) . " - " . dt::mysql2verbal($selected[$lastVersionId]['date']);
            }
        } else {
            if(!empty($rec->validTo)){
                $res->row->VALID_TO_HINT = ht::createImg(array('title' => "Има нова версия, влизаща в сила от|*: {$res->row->validTo}", 'src' => sbf('img/32/clock_history.png', '')));
            }
        }
    }


    /**
     * Връща клонинг на записа с подменени полета от версията
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param int $versionId
     * @param array $fields
     * @return stdClass
     */
    private static function getVersionRec($mvc, $rec, $versionId , $fields)
    {
        $clone = clone $rec;
        $versionRec = ($versionId == change_History::CURRENT_VERSION_ID) ? $rec : change_History::fetch($versionId);
        foreach ($fields as $fld){
            $clone->{$fld} = ($versionId == change_History::CURRENT_VERSION_ID) ? $versionRec->{$fld} : (property_exists($versionRec->data, $fld) ? $versionRec->data->{$fld} : $versionRec->{$fld});
        }

        return $clone;
    }
}