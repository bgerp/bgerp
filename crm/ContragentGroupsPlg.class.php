<?php


/**
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
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
        setIfNot($mvc->countryFieldName, 'country');
        setIfNot($mvc->groupFieldName, 'groupListInput');
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    protected static function on_BeforeSave($mvc, $id, $rec)
    {
        $oCountryId = null;

        if ($rec->id) {
            $oCountryId = $mvc->fetchField($rec->id, $mvc->countryFieldName);

            if ($oCountryId != $rec->{$mvc->countryFieldName}) {
                $mustUpdateGroups = true;
                $oCountryId = $oCountryId;
            }
        } else {
            $mustUpdateGroups = true;
        }

        if ($mustUpdateGroups) {
            $gIdArr = self::getGroupsId();

            $gForAdd = array();
            if ($rec->{$mvc->countryFieldName}) {
                $gForAdd = drdata_CountryGroups::getGroupsArr($rec->{$mvc->countryFieldName});
            }

            if ($oCountryId) {
                $gForRemove = drdata_CountryGroups::getGroupsArr($oCountryId);

                foreach ($gForRemove as $id => $gRec) {
                    if ($gForAdd[$id]) {

                        continue;
                    }

                    $gId = $gIdArr[$id];

                    $rec->{$mvc->groupFieldName} = type_Keylist::removeKey($rec->{$mvc->groupFieldName}, $gId);
                }
            }

            foreach ($gForAdd as $id => $gRec) {
                $gId = $gIdArr[$id];

                $rec->{$mvc->groupFieldName} = type_Keylist::addKey($rec->{$mvc->groupFieldName}, $gId);
            }
        }
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
    public static function getGroupsId()
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

        return $resArr;
    }
}
