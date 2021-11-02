<?php


/**
 * Интерфейс транспортна информация в детайлите на складовите документи
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_iface_DetailsTransportData
{
    /**
     * Клас имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Изчисляване на общото тегло и обем на редовете
     *
     * @param stdClass $masterRec - ид на мастъра
     * @param bool     $force
     *
     * @return stdClass $res
     *                  - weight    - теглото на реда
     *                  - volume    - теглото на реда
     *                  - transUnits - транспортните еденици
     */
    public function getTransportInfo($masterId, $force = false)
    {
        return $this->class->getTransportInfo($masterId, $force);
    }
}
