<?php


/**
 * 1Wire вход/изход
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_ioport_W1 extends sens2_ioport_Abstract
{
    /**
     * Типът слотове за сензорите от този вид
     */
    const SLOT_TYPES = '1WIRE';
    
    
    /**
     * Описание на порта
     */
    protected $description = array(
        '1WIRE' => array(
            'name' => null,
            'uomDef' => '',
            'readable' => true,
            'writable' => true,
        ),
    );


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('unitId', 'varchar(32)', 'caption=Unit ID,mandatory');
        $fieldset->FLD('variable', 'varchar(32)', 'caption=Променлива,placeholoder=value');
        parent::addFields($fieldset);
    }
    
    
    /**
     * Връща допълнителен идентификатор за порта, който е базиран на данните в драйвера
     */
    public function getPortIdent($rec)
    {
        return $rec->unitId;
    }

}
