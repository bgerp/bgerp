<?php


/**
 * Клас баща за наследяване на източник на етикети
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_SequenceIntf
 */
abstract class label_ProtoSequencerImpl extends core_Manager
{
    /**
     * Инстанция на класа
     */
    public $class;


    /**
     * Кога е отпечатан етикет от източника
     *
     * @param int $id
     * @return void
     */
    public function onLabelIsPrinted($id)
    {
        if($this->class instanceof core_Mvc){
            $this->class->logWrite('Печат на етикет', $id);
        }
    }


    /**
     * Връща дефолтен шаблон за печат на бърз етикет
     *
     * @param int  $id
     * @param stdClass|null  $driverRec
     *
     * @return int
     */
    public function getDefaultFastLabel($id, $driverRec = null)
    {
        return null;
    }


    /**
     * Връща попълнен дефолтен шаблон с дефолтни данни.
     * Трябва `getDefaultFastLabel` да върне резултат за да се покажат данните
     *
     * @param int  $id
     * @param int $templateId
     *
     * @return core_ET|null
     */
    public function getDefaultLabelWithData($id, $templateId)
    {
        return null;
    }


    /**
     * Кой е дефолтния шаблон за печат към обекта
     *
     * @param $id
     * @param string $series
     * @return int|null
     */
    public function getDefaultLabelTemplateId($id, $series = 'label')
    {
        return null;
    }
}