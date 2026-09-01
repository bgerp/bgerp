<?php


/**
 * Плъгин за бързо редактиране на количествата в детайл
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_EditDetailQuantities extends core_Plugin
{
    /**
     * Задава настройките по подразбиране след описанието на модела
     *
     * @param core_Mvc $mvc
     *
     * @return void
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        expect($mvc instanceof core_Detail, 'Плъгинът може да се използва само от детайли');
        setIfNot($mvc->canEditquantities, $mvc->canEdit);
        setIfNot($mvc->packQuantityFld, 'packQuantity');
        expect($mvc->getField($mvc->packQuantityFld, false), 'Липсва поле за количество в опаковка');
    }


    /**
     * Определя правата за бързо редактиране на количествата
     *
     * Екшънът е достъпен, ако има поне един ред, който потребителят може да редактира.
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|null $rec
     * @param int|null      $userId
     *
     * @return void
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action != 'editquantities') return;
        if ($requiredRoles == 'no_one') return;
        if (!isset($rec->{$mvc->masterKey})) {
            $requiredRoles = 'no_one';

            return;
        }

        $query = static::getDetailQuery($mvc, $rec->{$mvc->masterKey}, $rec);
        $hasEditableRec = false;
        while ($dRec = $query->fetch()) {
            if (haveRole($mvc->getRequiredRoles('edit', $dRec, $userId), $userId)) {
                $hasEditableRec = true;

                break;
            }
        }
        if (!$hasEditableRec) $requiredRoles = 'no_one';
    }


    /**
     * Добавя стандартен бутон при нормално рендиран детайл
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return void
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (empty($data->masterId) || empty($data->toolbar)) return;

        $rightRec = (object) array($mvc->masterKey => $data->masterId);
        if (!$mvc->haveRightFor('editquantities', $rightRec)) return;

        $data->toolbar->addBtn('Количества', array($mvc, 'editquantities', $mvc->masterKey => $data->masterId, 'ret_url' => true),
            'order=490,ef_icon=img/16/edit.png,title=Бързо редактиране на количествата');
    }


    /**
     * Подготвя и обработва формата за бързо редактиране на количествата
     *
     * @param core_Manager $mvc
     * @param mixed        $res
     * @param string       $action
     *
     * @return bool|null
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (strtolower($action) != 'editquantities') return;

        expect($masterId = Request::get($mvc->masterKey, 'int'));
        $filter = static::getFilterFromRequest();
        $rightRec = (object) (array($mvc->masterKey => $masterId) + (array) $filter);
        $mvc->requireRightFor('editquantities', $rightRec);

        expect($masterRec = $mvc->Master->fetch($masterId));
        $data = static::prepareRowsData($mvc, $masterRec, $filter);
        if (!countR($data->recs)) {
            followRetUrl($mvc->Master->getSingleUrlArray($masterId), 'Документът няма редове, които могат да се редактират', 'warning');
        }

        $form = static::prepareQuantityForm($mvc, $data, $masterRec);
        $form->input();

        if ($form->isSubmitted()) {
            $currentMasterRec = $mvc->Master->fetch($masterId);
            if (!$currentMasterRec || !$mvc->haveRightFor('editquantities', $rightRec)) {
                $form->setError(implode(',', $data->fieldNames), 'Документът вече не може да се редактира');
            } else {
                $currentRecs = static::getDetailQuery($mvc, $masterId, $filter)->fetchAll();
                $toSave = static::getValidatedChanges($mvc, $form, $data->recs, $data->fieldNames, $currentRecs, $currentMasterRec);
                if (!$form->gotErrors()) {
                    static::saveChanges($mvc, $toSave, $masterId);
                }
            }
        }

        $res = static::renderQuantityForm($mvc, $form, $masterId);

        return false;
    }


    /**
     * Подготвя редовете за формата
     *
     * @param core_Detail $mvc
     * @param stdClass    $masterRec
     * @param stdClass    $filter
     *
     * @return stdClass
     */
    private static function prepareRowsData($mvc, $masterRec, $filter)
    {
        $data = (object) array(
            'masterMvc' => $mvc->Master,
            'masterData' => (object) array('rec' => $masterRec),
            'masterId' => $masterRec->id,
            'query' => static::getDetailQuery($mvc, $masterRec->id, $filter),
            'recs' => array(),
            'rows' => array(),
        );
        $mvc->prepareListFields($data);
        $mvc->prepareListRecs($data);
        $mvc->prepareListRows($data);

        $recs = $data->recs;
        $rows = $data->rows;
        $listFields = $data->listFields;
        foreach ($recs as $dId => $dRec) {
            if (!$mvc->haveRightFor('edit', $dRec)) {
                unset($recs[$dId], $rows[$dId]);
            }
        }

        $listFields[$mvc->packQuantityFld] = 'Количество';
        $mvc->invoke('AfterPrepareEditQuantitiesRows', array(&$recs, &$rows, &$listFields, $masterRec));

        $data->recs = $recs;
        $data->rows = $rows;
        $data->listFields = $listFields;

        return $data;
    }


    /**
     * Създава формата за количествата
     *
     * @param core_Detail $mvc
     * @param stdClass    $data
     * @param stdClass    $masterRec
     *
     * @return core_Form
     */
    private static function prepareQuantityForm($mvc, $data, $masterRec)
    {
        $form = cls::get('core_Form');
        $form->title = 'Бързо редактиране на количествата в|* ' . $mvc->Master->getFormTitleLink($masterRec->id);
        $form->info = tr('Променете директно необходимите количества.');

        $fieldNames = array();
        foreach ($data->recs as $dId => $dRec) {
            $fieldName = "newPackQuantity{$dId}";
            $fieldNames[$dId] = $fieldName;
            $quantityType = clone $mvc->getFieldType($mvc->packQuantityFld);
            $form->FLD($fieldName, $quantityType, 'caption=Количество,input,mandatory,class=w25');
            $mvc->invoke('Calc' . $mvc->packQuantityFld, array(&$dRec));
            $form->setDefault($fieldName, $dRec->{$mvc->packQuantityFld});
        }

        $data->fieldNames = $fieldNames;
        $data->listTableMvc = clone $mvc;
        $data->hideListFieldsIfEmpty = arr::make($mvc->hideListFieldsIfEmpty ?? null, true);
        foreach ($data->rows as $dId => $row) {
            unset($row->_rowTools);
            $row->{$mvc->packQuantityFld} = new core_ET(core_ET::toPlace($fieldNames[$dId]));
        }
        $form->fieldsLayout = $mvc->renderListTable($data);

        return $form;
    }


    /**
     * Подготвя за запис само реално променените редове
     *
     * @param core_Detail $mvc
     * @param core_Form   $form
     * @param array       $displayedRecs
     * @param array       $fieldNames
     * @param array       $currentRecs
     * @param stdClass    $masterRec
     *
     * @return array
     */
    private static function getValidatedChanges($mvc, $form, $displayedRecs, $fieldNames, $currentRecs, $masterRec)
    {
        $toSave = array();
        foreach ($displayedRecs as $dId => $unusedRec) {
            $fieldName = $fieldNames[$dId];
            $dRec = $currentRecs[$dId] ?? null;
            if (!$dRec || !$mvc->haveRightFor('edit', $dRec)) {
                $form->setError($fieldName, 'Редът вече не може да се редактира');

                continue;
            }

            $newPackQuantity = $form->rec->{$fieldName};
            $mvc->invoke('Calc' . $mvc->packQuantityFld, array(&$dRec));
            if ($dRec->{$mvc->packQuantityFld} == $newPackQuantity) continue;

            $detailForm = static::prepareDetailForm($mvc, $dRec, $newPackQuantity, $masterRec, $form->ignore ?? null);
            static::copyFormErrors($detailForm, $form, $fieldName);
            if (!$detailForm->gotErrors()) {
                $toSave[$dId] = $detailForm->rec;
            }
        }

        if (!$form->gotErrors() && !countR($toSave)) {
            $form->setError(reset($fieldNames), 'Няма въведени нови количества');
        }

        return $toSave;
    }


    /**
     * Записва валидираните редове през стандартния detail lifecycle
     *
     * @param core_Detail $mvc
     * @param array       $toSave
     * @param int         $masterId
     *
     * @return void
     */
    private static function saveChanges($mvc, $toSave, $masterId)
    {
        foreach ($toSave as $dId => $saveRec) {
            expect($mvc->save($saveRec) !== false, 'Неуспешен запис на количество', $dId);
        }

        $mvc->Master->logWrite('Бърза промяна на количества: ' . countR($toSave) . ' реда', $masterId);
        followRetUrl($mvc->Master->getSingleUrlArray($masterId), 'Количествата са променени успешно');
    }


    /**
     * Рендира формата за количествата
     *
     * @param core_Detail $mvc
     * @param core_Form   $form
     * @param int         $masterId
     *
     * @return core_ET
     */
    private static function renderQuantityForm($mvc, $form, $masterId)
    {
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon=img/16/disk.png,title=Запис на новите количества');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon=img/16/close-red.png,title=Прекратяване на действието');
        $res = $mvc->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($res, $form);
        $mvc->Master->logRead('Разглеждане на формата за бърза промяна на количества', $masterId);

        return $res;
    }


    /**
     * Връща филтъра, подаден от специално рендиран детайл
     *
     * @return stdClass
     */
    private static function getFilterFromRequest()
    {
        return (object) array(
            '_filterFld' => Request::get('_filterFld', 'varchar'),
            '_filterFldVal' => Request::get('_filterFldVal', 'varchar'),
            '_filterFldVals' => Request::get('_filterFldVals', 'varchar'),
            '_filterFldNot' => Request::get('_filterFldNot', 'int'),
        );
    }


    /**
     * Подготвя заявка за редовете, без да допуска поле извън модела
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     * @param stdClass    $filter
     *
     * @return core_Query
     */
    private static function getDetailQuery($mvc, $masterId, $filter)
    {
        $query = $mvc->getQuery();
        $query->where(array("#{$mvc->masterKey} = '[#1#]'", $masterId));

        if (!empty($filter->_filterFld)) {
            $filterField = $mvc->getField($filter->_filterFld, false);
            expect($filterField && $filterField->kind == 'FLD', 'Невалидно поле за филтриране');
            if (!empty($filter->_filterFldVals)) {
                $values = array_keys(arr::make($filter->_filterFldVals, true));
                $query->in($filter->_filterFld, $values, !empty($filter->_filterFldNot));
            } else {
                $sign = !empty($filter->_filterFldNot) ? '!=' : '=';
                $query->where(array("#{$filter->_filterFld} {$sign} '[#1#]'", $filter->_filterFldVal));
            }
        }
        $query->orderBy('id', 'ASC');

        return $query;
    }


    /**
     * Подготвя промяната през стандартната форма и валидация на детайла
     *
     * @param core_Detail $mvc
     * @param stdClass    $rec
     * @param float       $newPackQuantity
     * @param stdClass    $masterRec
     * @param bool|null   $ignoreWarnings
     *
     * @return core_Form
     */
    private static function prepareDetailForm($mvc, $rec, $newPackQuantity, $masterRec, $ignoreWarnings = null)
    {
        $detailForm = $mvc->getForm();
        $detailForm->rec = clone $rec;

        foreach (array_keys($mvc->selectFields("#kind == 'FNC'")) as $name) {
            $mvc->invoke('Calc' . $name, array(&$detailForm->rec));
        }
        $detailForm->rec->{$mvc->packQuantityFld} = $newPackQuantity;

        $editData = (object) array(
            'action' => 'editquantities',
            'form' => $detailForm,
            'masterMvc' => $mvc->Master,
            'masterKey' => $mvc->masterKey,
            'masterId' => $masterRec->id,
            'masterRec' => $masterRec,
        );
        // Подаваме и резултата, и данните, както го прави prepareEditForm() wrapper-ът
        $mvc->invoke('AfterPrepareEditForm', array(&$editData, &$editData));

        $detailForm->ignore = $ignoreWarnings;
        $detailForm->validate(null, false, (array) $detailForm->rec);
        // Както в core_Manager::validate(), за да се изпълнят стандартните input handlers
        $detailForm->cmd = 'validate';
        $detailForm->method = $_SERVER['REQUEST_METHOD'];
        $mvc->invoke('AfterInputEditForm', array($detailForm));

        return $detailForm;
    }


    /**
     * Прехвърля грешките от стандартната форма към полето в бързата форма
     *
     * @param core_Form $detailForm
     * @param core_Form $form
     * @param string    $fieldName
     *
     * @return void
     */
    private static function copyFormErrors($detailForm, $form, $fieldName)
    {
        foreach ((array) ($detailForm->errors ?? null) as $error) {
            if (empty($error->msg)) continue;

            if (!empty($error->ignorable)) {
                $form->setWarning($fieldName, $error->msg, false);
            } else {
                $form->setError($fieldName, $error->msg, false, false);
            }
        }
    }
}
