<?php


/**
 * Клас 'change_plg_History' - Плъгин за историзация/версии на записи с validFrom/validTo.
 *
 * Към момента се използва основно за контрагенти
 * (crm_Companies и crm_Persons).
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

        // Истинска дата/автор на СЪЗДАВАНЕ на версията,
        // отделно от validFrom и modifiedOn
        $mvc->FLD('versionCreatedOn', 'datetime', 'caption=Версия->Създадена,input=none,silent');
        $mvc->FLD('versionCreatedBy', 'user', 'caption=Версия->Създадена от,input=none,silent');
    }
    
    
    /**
     * Нормализира началната дата към начало на деня
     *
     * @param string|null $value
     * @param bool $useTodayWhenEmpty
     * @return string|null
     */
    protected static function normalizeValidFrom($value = null, $useTodayWhenEmpty = true)
    {
        if (empty($value)) {
            return $useTodayWhenEmpty ? (dt::today() . ' 00:00:00') : null;
        }

        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ' 00:00:00';
        }

        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $value)) {
            list($day, $month, $year) = explode('.', $value);

            return $year . '-' . $month . '-' . $day . ' 00:00:00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}(?::\d{2})?$/', $value)) {
            return substr($value, 0, 10) . ' 00:00:00';
        }

        if (preg_match('/^\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}(?::\d{2})?$/', $value)) {
            list($day, $month, $year) = explode('.', substr($value, 0, 10));

            return $year . '-' . $month . '-' . $day . ' 00:00:00';
        }

        return $value;
    }


    /**
     * Вербално представяне само като дата
     *
     * @param string|null $value
     * @return string
     */
    protected static function verbalValidFrom($value)
    {
        if (empty($value)) {
            return '';
        }

        $date = substr(self::normalizeValidFrom($value, false), 0, 10);

        return core_Type::getByName('date')->toVerbal($date);
    }
    
    
    /**
     * Добавя моливче за промяна само на началото на валидност на текущата версия
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param string $value
     * @return string
     */
    public static function appendValidFromEditIcon($mvc, $rec, $value)
    {
        if (empty($value) || !$mvc->haveRightFor('edit', $rec)) {
            return $value;
        }

        $editLink = array($mvc, 'edit', $rec->id, 'ret_url' => true, 'historyEditCurrentOnlyValidFrom' => 1);
        $hint = 'Промяна САМО на началото на валидност на текущата версия на данните';

        return $value . ' ' . ht::createLink('', $editLink, false, "ef_icon=img/16/edit.png,title={$hint}")->getContent();
    }


    /**
     * Показва полето "Валидност от"
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     * @param bool $mandatory
     * @return void
     */
    protected static function showValidFromField($mvc, &$form, $mandatory = true)
    {
        $attr = 'input,caption=Валидност от,before=name,class=focus';
        if ($mandatory) {
            $attr .= ',mandatory';
        }

        $form->setField('newValidFrom', $attr);
        $form->setField('newValidFrom', array(
            'placeholder' => self::verbalValidFrom(dt::today()),
            'attr' => array('autofocus' => 'autofocus'),
        ));
    }
    
    
    /**
     * Показва нормален инструктивен текст над полето "Валидност от"
     *
     * @param core_Form $form
     * @param string $msg
     * @return void
     */
    protected static function setValidFromInfo(&$form, $msg, $subMsg = null, $hideTopError = false)
    {
        $html = '';

        if ($hideTopError) {
            $html .= "<style>
                div.formError, div.formErrors, div#formError, div#formErrors {
                    display:none !important;
                }
            </style>";
        }

        $html .= "<div style='margin:0 0 10px 0; padding:8px 10px; border:1px solid #d9c46b; background:#fff7cc; border-radius:4px;'>";
        $html .= "<div style='font-weight:bold; color:#000;'>" . tr($msg) . "</div>";

        if (!empty($subMsg)) {
            $html .= "<div style='margin-top:4px; color:#333;'>" . $subMsg . "</div>";
        }

        $html .= "</div>";

        $form->info = $html;
    }
    
    
    /**
     * Подсказка за възможните интервали при "нормална" редакция
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param string $oldCurrentValidFrom
     * @return string
     */
    protected static function getNormalEditValidFromInfo($mvc, $rec, $oldCurrentValidFrom)
    {
        $lines = array();
        $today = dt::today() . ' 00:00:00';
        $todayVerbal = self::verbalValidFrom($today);
        $currentVerbal = self::verbalValidFrom($oldCurrentValidFrom);

        $prevRec = self::getPrevActiveVersion($mvc, $rec->id, $oldCurrentValidFrom);

        // 1) Междинна/предходна версия в рамките на валидността на текущата
        if (is_object($prevRec)) {
            $fromPrev = dt::addDays(1, self::normalizeValidFrom($prevRec->validFrom, false), false);
            $toPrev = dt::addDays(-1, self::normalizeValidFrom($oldCurrentValidFrom, false), false);

            if ($fromPrev <= $toPrev) {
                $lines[] = '• ' . self::verbalValidFrom($fromPrev) . ' до ' . self::verbalValidFrom($toPrev) . ' - за да създадете нова предходна (междинна) версия в рамките на валидността на текущата';
            }
        }

        // 2) Презапис на текущата версия
        $lines[] = '• ' . $currentVerbal . ' - за да презапишете текущата/активната версия с новите данни';

        // 3) Нова текуща версия
        $fromCurrent = dt::addDays(1, self::normalizeValidFrom($oldCurrentValidFrom, false), false);
        if ($fromCurrent <= $today) {
            $lines[] = '• ' . self::verbalValidFrom($fromCurrent) . ' до ' . $todayVerbal . ' - за да създадете нова текуща/активна версия с новите данни';
        }

        // 4) Бъдеща версия
        $tomorrow = dt::addDays(1, $today, false);
        $lines[] = '• всяка дата след ' . $todayVerbal . ' (от ' . self::verbalValidFrom($tomorrow) . ') - за да създадете бъдеща/изчакваща началото на валидността си версия';

        return implode('<br>', $lines);
    }
    
    
    /**
     * Нормализира стойност за сравнение от request/record
     *
     * @param mixed $value
     * @return string
     */
    protected static function normalizeComparableValue($value)
    {
        if (is_array($value)) {
            $value = implode('|', $value);
        }

        $value = trim((string) $value);
        $value = str_replace("\n\r", '', $value);
        $value = str_replace("\r\n", '', $value);
        $value = str_replace("\n", '', $value);

        return $value;
    }


    /**
     * Дали при prepare на edit формата трябва да покажем полето "Валидност от"
     * при обикновена редакция
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return bool
     */
    protected static function shouldShowValidFromOnPrepare($mvc, $rec)
    {
        if (empty($rec->id)) {
            return false;
        }

        // Ако вече е подадена дата - полето задължително трябва да е visible още при prepare
        if (Request::get('newValidFrom')) {
            return true;
        }

        // Ако няма submit, не правим нищо
        if (!Request::get('Cmd')) {
            return false;
        }

        $fieldsToCheck = !empty($mvc->loggableField4Warning) ? $mvc->loggableField4Warning : $mvc->loggableFields;
        $loggableFields = arr::make($fieldsToCheck, true);
        $currentRec = $mvc->fetch($rec->id, '*', false);

        foreach ($loggableFields as $field) {
            $reqVal = Request::get($field);

            // Полето не участва в request-а
            if ($reqVal === null) {
                continue;
            }

            $reqVal = self::normalizeComparableValue($reqVal);
            $curVal = self::normalizeComparableValue(isset($currentRec->{$field}) ? $currentRec->{$field} : '');

            if ($reqVal !== $curVal) {
                return true;
            }
        }

        return false;
    }


    /**
     * Ако е избрана точно една стара версия - връща я
     *
     * @param core_Mvc $mvc
     * @param int $objectId
     * @return stdClass|null
     */
    protected static function getSelectedSingleOldVersion($mvc, $objectId)
    {
        $selected = change_History::getSelectedVersionsArr($mvc->getClassId(), $objectId);
        if (countR($selected) != 1) {
            return null;
        }

        $versionId = key($selected);
        if ($versionId == change_History::CURRENT_VERSION_ID) {
            return null;
        }

        return change_History::fetch($versionId);
    }


    /**
     * Предходната активна версия преди текущата
     *
     * @param core_Mvc $mvc
     * @param int $objectId
     * @param string $currentValidFrom
     * @return stdClass|null
     */
    protected static function getPrevActiveVersion($mvc, $objectId, $currentValidFrom)
    {
        $query = change_History::getQuery();
        $query->where("#classId = '{$mvc->getClassId()}' AND #objectId = '{$objectId}' AND #state = 'active' AND #validFrom < '{$currentValidFrom}'");
        $query->orderBy('validFrom', 'DESC');

        return $query->fetch();
    }


    /**
     * Активна версия от history със същата начална дата като текущата
     *
     * @param core_Mvc $mvc
     * @param int $objectId
     * @param string $currentValidFrom
     * @return stdClass|null
     */
    protected static function getCurrentHistoryVersion($mvc, $objectId, $currentValidFrom)
    {
        $query = change_History::getQuery();
        $query->where("#classId = '{$mvc->getClassId()}' AND #objectId = '{$objectId}' AND #state = 'active' AND #validFrom = '{$currentValidFrom}'");

        return $query->fetch();
    }


    /**
     * Минималната допустима начална дата
     *
     * @param core_Mvc $mvc
     * @param int $objectId
     * @param string $currentValidFrom
     * @return string
     */
    protected static function getMinAllowedValidFrom($mvc, $objectId, $currentValidFrom)
    {
        $prevRec = self::getPrevActiveVersion($mvc, $objectId, $currentValidFrom);

        if (is_object($prevRec)) {
            return dt::addDays(1, self::normalizeValidFrom($prevRec->validFrom, false), false);
        }

        return '1970-01-02 00:00:00';
    }


    /**
     * В режим "само смяна на началната дата" връща всички останали данни от стария запис
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return void
     */
    protected static function restoreRecFromOld($mvc, &$rec)
    {
        if (empty($rec->_oldRec)) {
            return;
        }

        foreach ((array) $mvc->fields as $name => $field) {
            if (in_array($name, array('validFrom', 'validTo', 'newValidFrom', 'historyEditCurrentOnlyValidFrom'), true)) {
                continue;
            }

            if (property_exists($rec->_oldRec, $name)) {
                $rec->{$name} = $rec->_oldRec->{$name};
            }
        }
    }


    /**
     * Променя началото на текущата версия.
     *
     * Може:
     * - да премести началото й назад/напред в миналото или до днес;
     * - да превърне текущата версия в бъдеща, ако има предходна,
     *   която да остане активна към момента.
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param string $newValidFrom
     * @return void
     */
    protected static function moveCurrentVersionStart($mvc, $rec, $newValidFrom)
    {
        $oldValidFrom = self::normalizeValidFrom($rec->_oldRec->validFrom, false);
        $newValidFrom = self::normalizeValidFrom($newValidFrom, false);

        if (empty($oldValidFrom) || empty($newValidFrom) || $oldValidFrom == $newValidFrom) {
            return;
        }

        $prevRec = self::getPrevActiveVersion($mvc, $rec->id, $oldValidFrom);
        $currentHistoryRec = self::getCurrentHistoryVersion($mvc, $rec->id, $oldValidFrom);
        $todayEnd = dt::today() . ' 23:59:59';

        // 1) Ако местим началото назад или в рамките на днешна/минала дата:
        // старото поведение остава
        if ($newValidFrom <= $todayEnd) {
            if (is_object($prevRec)) {
                $prevRec->validTo = $newValidFrom;
                change_History::save($prevRec, 'validTo');
            }

            if (is_object($currentHistoryRec)) {
                $currentHistoryRec->validFrom = $newValidFrom;
                $currentHistoryRec->validTo = $rec->_oldRec->validTo;
                change_History::save($currentHistoryRec, 'validFrom,validTo');
            }

            $rec->validFrom = $newValidFrom;
            $rec->validTo = $rec->_oldRec->validTo;
            $mvc->save_($rec, 'validFrom,validTo');

            return;
        }

        // 2) Ако местим текущата версия в бъдещето:
        // текущите данни трябва да станат future history версия,
        // а master-ът да се върне към версията валидна СЕГА
        if (!is_object($prevRec)) {
            return;
        }

        if (is_object($currentHistoryRec)) {
            $currentHistoryRec->validFrom = $newValidFrom;
            change_History::save($currentHistoryRec, 'validFrom');
        } else {
            $data = new stdClass();
            $loggableFields = arr::make($mvc->loggableFields, true);
            foreach ($loggableFields as $logFld) {
                $data->{$logFld} = $rec->_oldRec->{$logFld};
            }

            $futureRec = (object) array(
                'classId' => $mvc->getClassId(),
                'objectId' => $rec->id,
                'validFrom' => $newValidFrom,
                'validTo' => $rec->_oldRec->validTo,
                'data' => $data,
                'state' => 'active',
                'versionCreatedOn' => !empty($rec->_oldRec->versionCreatedOn) ? $rec->_oldRec->versionCreatedOn : $rec->_oldRec->createdOn,
                'versionCreatedBy' => !empty($rec->_oldRec->versionCreatedBy) ? $rec->_oldRec->versionCreatedBy : $rec->_oldRec->createdBy,
            );
            change_History::save($futureRec);
        }

        change_History::recalcActiveVersionValidTo($mvc->getClassId(), $rec->id);

        // Коя версия е валидна към момента след преместването
        $currentNowRec = change_History::getRecOnDate($mvc, $rec->id, dt::now());

        $saveFields = arr::make($mvc->loggableFields, true);
        foreach ($saveFields as $fld) {
            $rec->{$fld} = $currentNowRec->{$fld};
        }

        $rec->validFrom = $currentNowRec->validFrom;
        $rec->validTo = $currentNowRec->validTo;
        $rec->versionCreatedOn = !empty($currentNowRec->versionCreatedOn) ? $currentNowRec->versionCreatedOn : $rec->_oldRec->versionCreatedOn;
        $rec->versionCreatedBy = !empty($currentNowRec->versionCreatedBy) ? $currentNowRec->versionCreatedBy : $rec->_oldRec->versionCreatedBy;

        $saveFields['validFrom'] = 'validFrom';
        $saveFields['validTo'] = 'validTo';
        $saveFields['versionCreatedOn'] = 'versionCreatedOn';
        $saveFields['versionCreatedBy'] = 'versionCreatedBy';

        $mvc->save_($rec, $saveFields);
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;

        if (!isset($form->rec->id)) {
            return;
        }

        $form->FLD('newValidFrom', 'date', 'caption=Валидност от,before=name,input=none,silent');
        $form->FLD('historyEditCurrentOnlyValidFrom', 'int', 'input=hidden,silent');

        if (Request::get('historyEditCurrentOnlyValidFrom', 'int')) {
            $form->setDefault('historyEditCurrentOnlyValidFrom', 1);
        }

        // При обикновена редакция полето трябва да стане input още в prepare фазата,
        // ако има промяна в versionable полета или вече е подадена дата.
        if (self::shouldShowValidFromOnPrepare($mvc, $form->rec)) {
            self::showValidFromField($mvc, $form, false);
        }


        // Ако е натиснато моливчето от текущата версия
        if (!empty($form->rec->historyEditCurrentOnlyValidFrom)) {
            self::showValidFromField($mvc, $form, false);

            $currentValidFromVerbal = self::verbalValidFrom($form->rec->validFrom);
            self::setValidFromInfo(
                $form,
                'Изберете нова начална дата на валидност на текущите данни!',
                '(' . $currentValidFromVerbal . ' към момента)'
            );

            // Без setDefault - така в полето ще се вижда само placeholder-ът за "днес"
            foreach ($form->fields as $name => $field) {
                if (in_array($name, array('newValidFrom', 'historyEditCurrentOnlyValidFrom'), true)) {
                    continue;
                }

                $form->setReadOnly($name);
            }

            return;
        }


        // Ако е избрана точно една стара версия - редакцията тръгва от нейните данни
        $selectedVersionRec = self::getSelectedSingleOldVersion($mvc, $form->rec->id);
        if (is_object($selectedVersionRec) && is_object($selectedVersionRec->data)) {
            foreach ((array) $selectedVersionRec->data as $fld => $val) {
                if (isset($form->fields[$fld])) {
                    $form->rec->{$fld} = $val;
                }
            }

            if (!empty($selectedVersionRec->validFrom)) {
                $form->rec->newValidFrom = substr(self::normalizeValidFrom($selectedVersionRec->validFrom, false), 0, 10);
            }
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

        if (!$form->isSubmitted() || empty($rec->id)) {
            return;
        }

        // За да имаме _oldRec
        self::getOldRecHash($mvc, $rec);

        $rec->newValidFrom = self::normalizeValidFrom($rec->newValidFrom, false);
        $oldCurrentValidFrom = self::normalizeValidFrom($rec->_oldRec->validFrom, false);

        $oldHash = self::getOldRecHash($mvc, $rec, true);
        $newHash = self::getNewRecHash($mvc, $rec, true);

        // Режим: смяна само на началната дата на текущата версия
        if (!empty($rec->historyEditCurrentOnlyValidFrom)) {
            self::restoreRecFromOld($mvc, $rec);
            self::showValidFromField($mvc, $form, false);

            $currentValidFromVerbal = self::verbalValidFrom($oldCurrentValidFrom);
            self::setValidFromInfo(
                $form,
                'Изберете нова начална дата на валидност на текущите данни!',
                "({$currentValidFromVerbal} към момента)"
            );

            // Ако полето е оставено празно, приемаме "днес"
            if (empty($rec->newValidFrom)) {
                $rec->newValidFrom = self::normalizeValidFrom();
            }

            $minAllowed = self::getMinAllowedValidFrom($mvc, $rec->id, $oldCurrentValidFrom);
            if ($rec->newValidFrom < $minAllowed) {
                $form->setError(
                    'newValidFrom',
                    'Не може да преместите началната дата така, че текущата версия да припокрие напълно предходната! Началната дата на валидност не може да бъде по-малка от|* ' . self::verbalValidFrom($minAllowed) . '|*!'
                );
                return;
            }

            // Допускаме и бъдеща дата, но:
            // 1) трябва да има предходна версия, която да остане активна за текущия момент
            // 2) не трябва да има друга версия със същата начална дата
            $todayEnd = dt::today() . ' 23:59:59';

            if ($rec->newValidFrom > $todayEnd && !is_object(self::getPrevActiveVersion($mvc, $rec->id, $oldCurrentValidFrom))) {
                $form->setError('newValidFrom', 'Текущата версия не може да стане бъдеща, ако няма предходна версия, която да остане активна|*!');
                return;
            }

            $sameDateQuery = change_History::getQuery();
            $sameDateQuery->where("#classId = '{$mvc->getClassId()}' AND #objectId = '{$rec->id}' AND #state = 'active' AND #validFrom = '{$rec->newValidFrom}'");
            $sameDateRec = $sameDateQuery->fetch();
            if (is_object($sameDateRec) && $sameDateRec->validFrom != $oldCurrentValidFrom) {
                $form->setError('newValidFrom', 'Вече има версия с такава начална дата|*!');
                return;
            }

            return;
        }

        // Има промяна в versionable полетата => изисква се начална дата
        if ($oldHash != $newHash) {
            $isMissingValidFrom = empty($rec->newValidFrom);
            $subMsg = self::getNormalEditValidFromInfo($mvc, $rec, $oldCurrentValidFrom);

            self::showValidFromField($mvc, $form, false);
            self::setValidFromInfo(
                $form,
                'Изберете начална дата на валидност на новите/променените данни!',
                $subMsg,
                $isMissingValidFrom
            );

            if ($isMissingValidFrom) {
                $form->setError('newValidFrom', '');

                return;
            }

            $minAllowed = self::getMinAllowedValidFrom($mvc, $rec->id, $oldCurrentValidFrom);
            if ($rec->newValidFrom < $minAllowed) {
                $form->setError(
                    'newValidFrom',
                    'Не може да създадете версия, която напълно припокрива предходна! Началната дата на валидност не може да бъде по-малка от|* ' . self::verbalValidFrom($minAllowed) . '|*!'
                );
                return;
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
    protected static function getNewRecHash($mvc, $rec, $warning = false)
    {
        // Попълване на река с наблюдаваните полета
        $fieldArr = array();
        setIfNot($loggableWarningFields, $mvc->loggableField4Warning, $mvc->loggableFields);
        $fieldsToCheck = $warning ? $loggableWarningFields : $mvc->loggableFields;
        $loggableFields = arr::make($fieldsToCheck, true);
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
    protected static function getOldRecHash($mvc, &$rec, $warning = false)
    {
        $fieldArr = $noArr = array();
        if(isset($rec->id)){
            setIfNot($loggableWarningFields, $mvc->loggableField4Warning, $mvc->loggableFields);
            $fieldsToCheck = $warning ? $loggableWarningFields : $mvc->loggableFields;
            $loggableFields = arr::make($fieldsToCheck, true);
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
        $rec->_oldFieldHash = self::getOldRecHash($mvc, $rec);

        // При нов запис – това е първата версия
        if (empty($rec->id)) {
            if (empty($rec->versionCreatedOn)) {
                $rec->versionCreatedOn = dt::now();
            }

            if (empty($rec->versionCreatedBy)) {
                $rec->versionCreatedBy = core_Users::getCurrent();
            }

            return;
        }

        // При редакция пазим текущите metadata на версията,
        // освен ако после изрично не създадем нова версия
        if (empty($rec->versionCreatedOn) && !empty($rec->_oldRec->versionCreatedOn)) {
            $rec->versionCreatedOn = $rec->_oldRec->versionCreatedOn;
        }

        if (empty($rec->versionCreatedBy) && !empty($rec->_oldRec->versionCreatedBy)) {
            $rec->versionCreatedBy = $rec->_oldRec->versionCreatedBy;
        }
    }


    /**
     * Генериране на searchKeywords когато плъгинът е ново-инсталиран на модел в който е имало записи
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        $validFromColName = str::phpToMysqlName('validFrom');
        $createdOnColName = str::phpToMysqlName('createdOn');
        $createdByColName = str::phpToMysqlName('createdBy');
        $versionCreatedOnColName = str::phpToMysqlName('versionCreatedOn');
        $versionCreatedByColName = str::phpToMysqlName('versionCreatedBy');

        if ($mvc->count("#validFrom IS NULL")) {
            $query = "UPDATE {$mvc->dbTableName} SET {$validFromColName} = {$createdOnColName} WHERE {$validFromColName} IS NULL";
            $mvc->db->query($query);
        }

        if ($mvc->count("#versionCreatedOn IS NULL")) {
            $query = "UPDATE {$mvc->dbTableName} SET {$versionCreatedOnColName} = {$createdOnColName} WHERE {$versionCreatedOnColName} IS NULL";
            $mvc->db->query($query);
        }

        if ($mvc->count("#versionCreatedBy IS NULL")) {
            $query = "UPDATE {$mvc->dbTableName} SET {$versionCreatedByColName} = {$createdByColName} WHERE {$versionCreatedByColName} IS NULL";
            $mvc->db->query($query);
        }
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
        // Отделен режим: смяна само на началната дата на текущата версия
        if (!empty($rec->historyEditCurrentOnlyValidFrom)) {
            $newValidFrom = self::normalizeValidFrom($rec->newValidFrom, false);
            $oldValidFrom = self::normalizeValidFrom($rec->_oldRec->validFrom, false);

            if (!empty($newValidFrom) && $newValidFrom != $oldValidFrom) {
                self::moveCurrentVersionStart($mvc, $rec, $newValidFrom);
            }

            return;
        }

        // Ако няма промяна в наблюдаваните полета - нищо не правим
        $newFieldHash = self::getNewRecHash($mvc, $rec);
        if ($rec->_oldFieldHash == $newFieldHash) {
            return;
        }

        $rec->validFrom = !empty($rec->validFrom) ? $rec->validFrom : dt::now();

        $sync = false;
        if (!empty($rec->newValidFrom)) {
            $sync = true;
        } else {
            $modifiedBy = isset($rec->modifiedBy) ? $rec->modifiedBy : $rec->_oldRec->modifiedBy;
            $time = change_Setup::get('LOG_VERSION_AFTER_LAST');
            $before2hours = dt::addSecs(-1 * $time);

            if ($rec->_oldRec->modifiedOn < $before2hours || $modifiedBy != core_Users::getCurrent()) {
                $sync = true;
            }
        }

        if (!$sync) {
            return;
        }

        // Тук вече със сигурност се ражда НОВА версия.
        // Даваме й истинска дата/автор на създаване на версията.
        $rec->versionCreatedOn = dt::now();
        $rec->versionCreatedBy = core_Users::getCurrent();

        $rec->validFrom = !empty($rec->newValidFrom) ? self::normalizeValidFrom($rec->newValidFrom, false) : (dt::today() . ' 00:00:00');

        $updateFields = array();
        $currentRecData = change_History::getCurrentRec($mvc->getClassId(), $rec->id, $rec->_oldRec, $rec, $updateFields);

        if (countR($updateFields)) {
            foreach ((array) $currentRecData as $cFld => $cVal) {
                $rec->{$cFld} = $cVal;
            }

            $mvc->save_($rec, $updateFields);
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
            $oneMonthAgo = dt::addMonths(-1);
            if($rec->validFrom < $oneMonthAgo || $rec->validFrom == $rec->createdBy){
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
            
            if (isset($res->row->validFrom) && !empty($clone->validFrom)) {
                $res->row->validFrom = self::verbalValidFrom($clone->validFrom);
            }

            if (isset($res->row->validTo) && !empty($clone->validTo)) {
                $res->row->validTo = self::verbalValidFrom($clone->validTo);
            }
            
            $res->row->VERSION_STRING = $selected[$versionId]['verString'];
            $res->row->VERSION_STRING_HINT = tr('Показване на записа към версията от|* ') . self::verbalValidFrom($selected[$versionId]['date']);
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
                $res->row->VERSION_STRING_HINT = tr('Сравняване на версиите|*: ') . self::verbalValidFrom($selected[$firstVersionId]['date']) . " - " . self::verbalValidFrom($selected[$lastVersionId]['date']);
            }
        } else {
            if(!empty($rec->validTo)){
                $res->row->VALID_TO_HINT = ht::createImg(array('title' => "Има нова версия, влизаща в сила от|*: " . self::verbalValidFrom($rec->validTo), 'src' => sbf('img/32/clock_history.png', '')));
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
            $clone->{$fld} = ($versionId == change_History::CURRENT_VERSION_ID) ? $versionRec->{$fld} : (is_object($versionRec->data) && property_exists($versionRec->data, $fld) ? $versionRec->data->{$fld} : $versionRec->{$fld});
        }

        return $clone;
    }
}