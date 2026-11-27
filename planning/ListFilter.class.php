<?php


/**
 * Последни настройки на листовите филтри за задания и операции.
 *
 * @category  bgerp
 * @package   planning
 * @author    Stefan Arsov <stefan.arsof@gmail.com> with Codex
 * @license   GPL 3
 */
class planning_ListFilter
{
    private static function getFields($mvc)
    {
        $fields = arr::make('search,selectPeriod,from,to,filterDateField,saleId,purchaseId,' . $mvc::LIST_FILTER_SPECIFIC_FIELDS, true);
        if (Request::get('Rejected', 'int')) {
            // В кошчето липсват тези полета и изборът за обикновения лист трябва да се запази.
            unset($fields['view'], $fields['state'], $fields['isFinalSelect']);
        }

        return $fields;
    }


    /**
     * Търсенето и периодът четат Request още преди листовата форма да е готова.
     *
     * @return bool има ли подаден или запомнен филтър за прилагане
     */
    public static function restore($mvc)
    {
        $key = $mvc::LIST_FILTER_KEY;
        $userId = core_Users::getCurrent();
        $saved = core_Settings::fetchKeyNoMerge($key, $userId);
        $restore = $obsolete = array();
        $hasExplicitValues = false;
        foreach (self::getFields($mvc) as $field) {
            if (Request::get($field, false) !== null) {
                $hasExplicitValues = true;
                continue;
            }
            if (!array_key_exists($field, $saved)) {
                continue;
            }

            $value = $saved[$field];
            if ($field == 'view' && (!is_string($value) || !isset(core_Type::getByName(planning_Jobs::LIST_VIEW_TYPE)->options[$value]))) {
                continue;
            }

            if (!empty($value) && ($field == 'saleId' || $field == 'purchaseId')) {
                $otherField = $field == 'saleId' ? 'purchaseId' : 'saleId';
                if (Request::get($otherField, false)) {
                    continue;
                }

                // Запомненият договор може междувременно да е оттеглен или да е останал без задания.
                list($sourceClass) = planning_Jobs::getSourceInfo((object) array($field => $value));
                if (!planning_Jobs::getListSourceOptions(array('mvc' => $sourceClass), 1, '', $value)) {
                    $obsolete[$field] = $value = '';
                }
            }
            $restore[$field] = $value;
        }

        if ($obsolete && !Request::get('ajax_mode') && !Mode::is('preserveUserChoices')) {
            core_Settings::setValues($key, $obsolete, $userId, true);
        }
        if ($restore) {
            Request::push($restore);
        }

        Mode::set($key . 'PreviousValues', $hasExplicitValues ? $saved : null);

        return $hasExplicitValues || !empty($restore);
    }


    public static function getValues($mvc, $rec)
    {
        $values = array();
        foreach (self::getFields($mvc) as $field) {
            $values[$field] = $rec->{$field} ?? '';
        }

        return $values;
    }


    public static function save($mvc, $data)
    {
        $key = $mvc::LIST_FILTER_KEY;
        $previous = Mode::get($key . 'PreviousValues');
        $form = $data->listFilter;
        // Фоновото обновяване на стар таб не бива да презаписва по-нов избор.
        if (!is_array($previous) || Request::get('ajax_mode') ||
            Mode::is('preserveUserChoices') || !empty($data->masterMvc) || $form->gotErrors()) {
            return;
        }

        $values = self::getValues($mvc, $form->rec);
        if (array_replace($previous, $values) !== $previous) {
            core_Settings::setValues($key, $values, core_Users::getCurrent(), true);
        }
    }
}
