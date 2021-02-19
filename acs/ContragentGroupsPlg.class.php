<?php


/**
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acs_ContragentGroupsPlg extends core_Plugin
{
    public static $sysId = 'accessControl';

    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    protected static function on_BeforeSave($mvc, $id, $rec)
    {
        if ($rec->personId && $rec->userId) {
            if (haveRole('executive', $rec->userId)) {
                $Persons = cls::get('crm_Persons');
                $pRec = $Persons->fetch($rec->personId);
                $gId = self::getGroupId();
                if ($gId) {
                    $pRec->groupListInput = type_Keylist::addKey($pRec->groupListInput, $gId);

                    $inputArr = type_Keylist::toArray($pRec->groupListInput);

                    $resArr = $Persons->expandInput($inputArr);

                    $pRec->groupList = type_Keylist::fromArray($resArr);

                    $Persons->save_($pRec, 'groupListInput, groupList');
                }
            }
        }
    }


    /**
     *
     *
     * @return array
     */
    public static function getGroupId()
    {
        $groupRec = (object)array('name' => 'Контрол на достъпа', 'sysId' => self::$sysId);

        return crm_Groups::forceGroup($groupRec);
    }
}
