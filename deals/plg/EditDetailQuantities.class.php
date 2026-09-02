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
     * Служебна колона за полета за редакция
     */
    const EDIT_QUANTITY_COLUMN = '_editPackQuantity';

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

        $data->toolbar->addBtn('Количества', static::getActionUrl($mvc, $data->masterId),
            'order=490,ef_icon=img/16/edit.png,title=Бързо редактиране на количествата');
    }


    /**
     * Запазва колоните за текущо и ново количество след останалите list hooks
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param stdClass $data
     *
     * @return void
     */
    public static function on_RightBeforeRenderListTable($mvc, &$res, &$data)
    {
        if (empty($data->_isEditQuantities)) return;

        $data->listFields = static::addQuantityColumns($data->listFields, $mvc->packQuantityFld);
    }


    /**
     * Връща URL към формата за редактиране на количества
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     * @param array       $filter
     *
     * @return array
     */
    public static function getActionUrl($mvc, $masterId, $filter = array())
    {
        return array($mvc, 'editquantities', $mvc->masterKey => $masterId) + $filter + array('ret_url' => true);
    }


    /**
     * Създава стандартен бутон за редактиране на количества
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     * @param array       $filter
     * @param string|null $title
     * @param array       $attr
     *
     * @return core_ET
     */
    public static function createBtn($mvc, $masterId, $filter = array(), $title = null, $attr = array())
    {
        $defaultAttr = array(
            'style' => 'margin-top:5px;margin-bottom:15px;',
            'ef_icon' => 'img/16/edit.png',
            'title' => $title ?? tr('Бързо редактиране на количествата'),
        );

        return ht::createBtn('Количества', static::getActionUrl($mvc, $masterId, $filter), null, null, $attr + $defaultAttr);
    }


    /**
     * Добавя стандартния бутон в блок от шаблона
     *
     * @param core_ET     $tpl
     * @param core_Detail $mvc
     * @param int         $masterId
     * @param string      $block
     * @param array       $filter
     * @param string|null $title
     * @param array       $attr
     *
     * @return void
     */
    public static function appendBtn($tpl, $mvc, $masterId, $block, $filter = array(), $title = null, $attr = array())
    {
        $tpl->append(static::createBtn($mvc, $masterId, $filter, $title, $attr), $block);
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
            followRetUrl($mvc->Master->getSingleUrlArray($masterId), 'Документът няма редове, които могат да се редактират!', 'warning');
        }

        $form = static::prepareQuantityForm($mvc, $data, $masterRec);
        if (!countR($data->fieldNames)) {
            followRetUrl($mvc->Master->getSingleUrlArray($masterId), 'Документът няма редове, които могат да се редактират!', 'warning');
        }
        $form->input();

        if ($form->isSubmitted()) {
            $currentMasterRec = $mvc->Master->fetch($masterId);
            if (!$currentMasterRec || !$mvc->haveRightFor('editquantities', $rightRec)) {
                $form->setError(implode(',', $data->fieldNames), 'Документът вече не може да се редактира!');
            } else {
                $currentRecs = static::getDetailQuery($mvc, $masterId, $filter)->fetchAll();
                $toSave = static::getValidatedChanges($mvc, $form, $data->fieldNames, $currentRecs, $currentMasterRec);
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
            '_isEditQuantities' => true,
        );
        $mvc->prepareListFields($data);
        $mvc->prepareListRecs($data);
        $mvc->prepareListRows($data);

        unset($data->listFields['_rowTools']);
        $data->listFields = static::addQuantityColumns($data->listFields, $mvc->packQuantityFld);

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
        $form->info = tr('Въведете ново количество или относителна промяна със знак') .
            ' <b>+</b>, <b>-</b>, <b>*</b> ' . tr('или') . ' <b>/</b>.';

        $fieldNames = array();
        foreach ($data->recs as $dId => $dRec) {
            if (!$mvc->haveRightFor('edit', $dRec)) {
                $data->rows[$dId]->{static::EDIT_QUANTITY_COLUMN} = '';

                continue;
            }

            $fieldName = "newPackQuantity{$dId}";
            $detailForm = static::prepareDetailEditForm($mvc, $dRec, $masterRec);
            $detailQuantityField = $detailForm->getField($mvc->packQuantityFld);
            if (in_array($detailQuantityField->input ?? null, array('none', 'hidden'))) {
                $data->rows[$dId]->{static::EDIT_QUANTITY_COLUMN} = '';

                continue;
            }

            $fieldNames[$dId] = $fieldName;
            $form->FLD($fieldName, 'varchar(32)', array(
                'caption' => 'Ново количество',
                'input' => 'input',
                'class' => 'w25',
            ));
        }

        $data->fieldNames = $fieldNames;
        $data->listClass = 'listRows editDetailQuantitiesTable';
        $data->listTableMvc = clone $mvc;
        $quantityColumnField = clone $mvc->getField($mvc->packQuantityFld);
        $quantityColumnField->type = clone $quantityColumnField->type;
        $quantityColumnField->name = static::EDIT_QUANTITY_COLUMN;
        $data->listTableMvc->fields[static::EDIT_QUANTITY_COLUMN] = $quantityColumnField;
        $data->hideListFieldsIfEmpty = arr::make($mvc->hideListFieldsIfEmpty ?? null, true);
        foreach ($fieldNames as $dId => $fieldName) {
            $row = $data->rows[$dId];
            unset($row->_rowTools);
            $row->{static::EDIT_QUANTITY_COLUMN} = new core_ET(core_ET::toPlace($fieldName));
        }
        $form->fieldsLayout = $mvc->renderListTable($data);

        return $form;
    }


    /**
     * Подготвя за запис само реално променените редове
     *
     * @param core_Detail $mvc
     * @param core_Form   $form
     * @param array       $fieldNames
     * @param array       $currentRecs
     * @param stdClass    $masterRec
     *
     * @return array
     */
    private static function getValidatedChanges($mvc, $form, $fieldNames, $currentRecs, $masterRec)
    {
        $toSave = array();
        foreach ($fieldNames as $dId => $fieldName) {
            $dRec = $currentRecs[$dId] ?? null;
            if (!$dRec || !$mvc->haveRightFor('edit', $dRec)) {
                $form->setError($fieldName, 'Редът вече не може да се редактира!');

                continue;
            }

            $newPackQuantity = $form->rec->{$fieldName};
            if (!strlen(trim($newPackQuantity ?? ''))) continue;

            $detailForm = static::prepareDetailEditForm($mvc, $dRec, $masterRec);
            $quantityType = clone $detailForm->getField($mvc->packQuantityFld)->type;
            $error = null;
            if (!static::calculateQuantity($newPackQuantity, $dRec->{$mvc->packQuantityFld}, $quantityType, $newPackQuantity, $error)) {
                $form->setError($fieldName, $error);

                continue;
            }
            if ($dRec->{$mvc->packQuantityFld} == $newPackQuantity) continue;

            static::validateDetailForm($mvc, $detailForm, $newPackQuantity, $form->ignore ?? null);
            static::copyFormErrors($detailForm, $form, $fieldName);
            if (!$detailForm->gotErrors()) {
                $toSave[$dId] = $detailForm->rec;
            }
        }

        if (!$form->gotErrors() && !countR($toSave)) {
            $form->setError(reset($fieldNames), 'Няма въведени нови количества!');
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

        $mvc->Master->logWrite('Бърза промяна на количества', $masterId);
        followRetUrl($mvc->Master->getSingleUrlArray($masterId), 'Количествата са променени успешно!');
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
        jquery_Jquery::run($res, "$('.editDetailQuantitiesTable a').attr('tabindex', '-1');");
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
     * Подрежда колоните за текущото и новото количество
     *
     * @param array|string $listFields
     * @param string       $quantityField
     *
     * @return array
     */
    private static function addQuantityColumns($listFields, $quantityField)
    {
        $listFields = arr::make($listFields, true);
        $result = array();
        $quantityColumnsAdded = false;
        foreach ($listFields as $name => $caption) {
            if (in_array($name, array($quantityField, static::EDIT_QUANTITY_COLUMN))) {
                if (!$quantityColumnsAdded) {
                    $result[$quantityField] = 'Количество';
                    $result[static::EDIT_QUANTITY_COLUMN] = 'Ново количество';
                    $quantityColumnsAdded = true;
                }

                continue;
            }

            $result[$name] = $caption;
        }

        if (!$quantityColumnsAdded) {
            $result[$quantityField] = 'Количество';
            $result[static::EDIT_QUANTITY_COLUMN] = 'Ново количество';
        }

        return $result;
    }


    /**
     * Изчислява крайното количество от число или относителна операция
     *
     * @param string    $input
     * @param float     $currentQuantity
     * @param core_Type $quantityType
     * @param float     $result
     * @param string    $error
     *
     * @return bool
     */
    private static function calculateQuantity($input, $currentQuantity, $quantityType, &$result, &$error)
    {
        $pattern = '/^\s*([+\-*\/])?\s*((?:\d[\d\s]*(?:[\.,]\d*)?|[\.,]\d+))\s*$/';
        if (!preg_match($pattern, $input, $matches)) {
            $error = 'Въведете число или знак +, -, *, /, последван от число';

            return false;
        }

        $operator = $matches[1] ?? null;
        $operand = $quantityType->fromVerbal($matches[2]);
        if ($operand === false || !isset($operand)) {
            $error = $quantityType->error ?: 'Невалидно число';

            return false;
        }

        switch ($operator) {
            case '+':
                $result = $currentQuantity + $operand;
                break;
            case '-':
                $result = $currentQuantity - $operand;
                break;
            case '*':
                $result = $currentQuantity * $operand;
                break;
            case '/':
                if ((float) $operand == 0.0) {
                    $error = 'Не може да се дели на нула';

                    return false;
                }
                $result = $currentQuantity / $operand;
                break;
            default:
                $result = $operand;
        }

        if (!is_finite((float) $result)) {
            $error = 'Полученото количество е невалидно';

            return false;
        }

        return true;
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
     * Подготвя стандартната edit форма за един ред
     *
     * @param core_Detail $mvc
     * @param stdClass    $rec
     * @param stdClass    $masterRec
     *
     * @return core_Form
     */
    private static function prepareDetailEditForm($mvc, $rec, $masterRec)
    {
        $detailForm = $mvc->getForm();
        $detailForm->rec = clone $rec;
        $detailForm->_editDetailQuantities = true;

        $editData = (object) array(
            'action' => 'manage',
            'form' => $detailForm,
            'masterMvc' => $mvc->Master,
            'masterKey' => $mvc->masterKey,
            'masterId' => $masterRec->id,
            'masterRec' => $masterRec,
        );
        // Подаваме и резултата, и данните, както го прави prepareEditForm() wrapper-ът
        $mvc->invoke('AfterPrepareEditForm', array(&$editData, &$editData));

        return $detailForm;
    }


    /**
     * Валидира новото количество през стандартните input handlers на детайла
     *
     * @param core_Detail $mvc
     * @param core_Form   $detailForm
     * @param float       $newPackQuantity
     * @param bool|null   $ignoreWarnings
     *
     * @return void
     */
    private static function validateDetailForm($mvc, $detailForm, $newPackQuantity, $ignoreWarnings = null)
    {
        $detailForm->rec->{$mvc->packQuantityFld} = $newPackQuantity;
        $detailForm->ignore = $ignoreWarnings;
        $detailForm->validate(null, false, (array) $detailForm->rec);
        // Както в core_Manager::validate(), за да се изпълнят стандартните input handlers
        $detailForm->cmd = 'validate';
        $detailForm->method = $_SERVER['REQUEST_METHOD'];
        $mvc->invoke('AfterInputEditForm', array($detailForm));
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
