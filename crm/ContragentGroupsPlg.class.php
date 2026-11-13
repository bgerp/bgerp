<?php


/**
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class crm_ContragentGroupsPlg extends core_Plugin
{


    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setPartIfNot($mvc, 'countryFieldName', 'country');
        setPartIfNot($mvc, 'groupFieldName', 'groupListInput');
    }


    /**
     * Добавя помощен шаблон за попълване на полетата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $suggArr = $data->form->fields[$mvc->groupFieldName]->type->prepareSuggestions();

        $gIdArr = self::getGroupsId(true);

        foreach ($gIdArr as $gId) {
            unset($suggArr[$gId]);
        }

        $data->form->setSuggestions($mvc->groupFieldName, $suggArr);
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    protected static function on_BeforeSave($mvc, $id, $rec, &$fields = null)
    {
        $countryField = $mvc->countryFieldName ?? 'country';
        $groupField = $mvc->groupFieldName ?? 'groupListInput';
        $expandField = $mvc->expandFieldName ?? 'groupList';
        $saveFields = isset($fields) ? arr::make($fields, true) : null;
        if (isset($saveFields)) {
            // Частичен запис на друго поле не променя групите, включително в AfterSave.
            if (!isset($saveFields[$countryField]) && !isset($saveFields[$groupField]) && !isset($saveFields[$expandField])) return;

            $saveFields[$groupField] = $groupField;
            $fields = implode(',', array_keys($saveFields));
        }

        $oCountryId = null;
        $countryId = $rec->{$countryField} ?? null;

        if (!empty($rec->id)) {
            $oCountryId = $mvc->fetchField($rec->id, $countryField);

            // При частичен запис (напр. само групите) държавата не се променя
            if (!property_exists($rec, $countryField) && !isset($saveFields[$countryField])) {
                $countryId = $oCountryId;
            }

            // Добавянето на автоматичните групи не трябва да скрие липсващия ръчен вход.
            if (!property_exists($rec, $groupField)) {
                $rec->{$groupField} = $mvc->fetchField($rec->id, $groupField);
            }
        }

        $gIdArr = self::getGroupsId();

        $gForAdd = array();
        if ($countryId) {
            $gForAdd = drdata_CountryGroups::getGroupsArr($countryId);
        }

        if ($oCountryId) {
            $gForRemove = drdata_CountryGroups::getGroupsArr($oCountryId);

            foreach ($gForRemove as $id => $gRec) {
                if (!empty($gForAdd[$id])) {

                    continue;
                }

                $gId = $gIdArr[$id];

                $rec->{$groupField} = type_Keylist::removeKey($rec->{$groupField} ?? null, $gId);
            }
        }

        foreach ($gForAdd as $id => $gRec) {
            $gId = $gIdArr[$id];

            $rec->{$groupField} = type_Keylist::addKey($rec->{$groupField} ?? null, $gId);
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        $groupField = $mvc->groupFieldName ?? 'groupListInput';
        if (isset($fields)) {
            $saveFields = arr::make($fields, true);
            if (!isset($saveFields[$groupField])) return;
        }
        $gArr = self::getGroupsId(true);

        $prevVal = $rec->{$groupField} ?? null;

        foreach ($gArr as $gId) {
            $rec->{$groupField} = type_Keylist::removeKey($rec->{$groupField} ?? null, $gId);
        }

        if ($prevVal != ($rec->{$groupField} ?? null)) {
            $mvc->save_($rec, $groupField);
        }
    }


    /**
     * При преизчисляване се запазват автоматичните групи, които не стоят във входното поле.
     */
    public static function on_AfterExpandInput($mvc, &$res, $inputArr, $rec = null)
    {
        if (!is_object($rec)) return;

        $countryField = $mvc->countryFieldName ?? 'country';
        $countryId = property_exists($rec, $countryField) ? $rec->{$countryField} :
            (!empty($rec->id) ? $mvc->fetchField($rec->id, $countryField) : null);
        if (!$countryId) return;

        $groupIds = self::getGroupsId();
        $automatic = array();
        foreach (drdata_CountryGroups::getGroupsArr($countryId) as $id => $group) {
            if (!empty($groupIds[$id])) $automatic[$groupIds[$id]] = $groupIds[$id];
        }
        $res = ($res ?? array()) + $mvc->expandInput($automatic);
    }


    /**
     * Създава начални шаблони за трудови договори, ако такива няма
     */
    public static function on_AfterSetUpMvc($mvc, &$res)
    {
        self::getGroupsId();
    }


    /**
     *
     *
     * @return array
     */
    public static function getGroupsId($getParent = false)
    {
        static $resArr = null;

        if (isset($resArr)) {

            return $resArr;
        }

        // Форсираме групи за държави
        $cQuery = drdata_CountryGroups::getQuery();
        $cQuery->show('name');

        $resArr = array();

        while ($cRec = $cQuery->fetch()) {
            $gId = crm_Groups::force("Държави » {$cRec->name}");

            $resArr[$cRec->id] = $gId;
        }

        if ($getParent) {
            $gId = crm_Groups::force("Държави");

            $resArr[''] = $gId;
        }

        return $resArr;
    }
}
