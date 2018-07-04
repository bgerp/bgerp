<?php

/**
 * Абстрактен родителски клас за вход/изход/регистър на контролер
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class sens2_ioport_Abstract extends core_BaseClass
{

    /**
     * Типът слотове за сензорите от този вид
     */
    const SLOT_TYPES = '';


    /**
     * Подръжани интерфейси
     */
    public $interfaces = 'sens2_ioport_Intf';

    
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Може ли вградения обект да се избере
     */
    public function canSelectDriver($rec, $userId = null)
    {
        if ($rec->controllerId && static::SLOT_TYPES) {
            $Plc   = sens2_Controllers::getDriver($rec->controllerId);
            $slotsCnt = $Plc->getSlotCnt();
            $slotTypesArr = arr::make(static::SLOT_TYPES, TRUE);
            foreach($slotTypesArr as $sl) {
                if ($slotsCnt[$sl] > 0) {

                    return true;
                }
            }
        }
    }


    /**
     * Конвертира извлечената стойност в масив от Име => Стойност
     */
    public static function convert($value, $portRec, $controller)
    {
        return $value;
    }
}
