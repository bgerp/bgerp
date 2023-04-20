<?php


/**
 * Плъгин за превеждане на входящите имейли
 *
 * @category  bgerp
 * @package   google
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class openai_plugins_IncomingsContragentData extends core_Plugin
{
    /**
     * @var array
     */
    protected static $extractOnShutDown = array();


    /**
     * Подготвя контрагент данните и ги връща, ако има съвпадение
     *
     * @param $mvc
     * @param $res
     * @param $id
     */
    public static function on_AlternativeGetContragentData($mvc, &$res, $id)
    {
        if (!Mode::is('threadMove')) {

            return ;
        }

        if (!$id) {

            return ;
        }

        $cData = new stdClass();

        if (openai_ExtractContactInfo::extractEmailData($id, $cData, 'only') === false) {
            self::$extractOnShutDown[$id] = $id;
        } else {
            if (!empty((array) $cData)) {
                $cData->priority = 5;
                $res = $cData;
            }
        }
    }


    /**
     * Изчиства записите, заопашени за запис
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
        foreach (self::$extractOnShutDown as $eId) {
            openai_ExtractContactInfo::extractEmailData($eId);

            unset(self::$extractOnShutDown[$eId]);
        }
    }
}
