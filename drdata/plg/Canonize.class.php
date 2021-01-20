<?php


/**
 * Клас 'drdata_plg_Canonize' - плъгин за канонизиране на стрингове и кеширването им в друг модел
 *
 * Класа трябва да има пропърти $canonizeFields = "$field=$type".
 *      $field е полето, което ще се канонизира
 *      $type е типа от drdata_CanonizedStrings към който ще се канонизира
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_plg_Canonize extends core_Plugin
{

    /**
     * Метод по подразбиране за взимане на полетата за канонизиране
     */
    public static function on_AfterGetCanonizedFields($mvc, &$res, $rec)
    {
        if(!$res){
            $res = arr::make($mvc->canonizeFields);
        }
    }


    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $canonizeFields = $mvc->getCanonizedFields($rec);
        if(!countR($canonizeFields)) return;

        // Преди запис посочените полета се канонизират при нужда
        foreach ($canonizeFields as $field => $type) {
            if(!empty($rec->{$field})){
                $rec->{$field} = drdata_CanonizedStrings::canonize($rec->{$field}, $type);
            }
        }
    }


    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
        $canonizeFields = $mvc->getCanonizedFields($rec);
        if(!countR($canonizeFields)) return;

        // Показване на последно въведения стринг за канонизация
        if (array_key_exists($part, $canonizeFields)) {
            if(!empty($rec->{$part})){
                $value = drdata_CanonizedStrings::getString($rec->{$part}, $canonizeFields[$part]);
                if($num instanceof core_ET){
                    $num->content = str_replace($rec->{$part}, $value, $num->content);
                } else {
                    $num = $value;
                }
            }
        }
    }
}