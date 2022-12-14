<?php


/**
 * Хъбове на CSV
 *
 * @category  bgerp
 * @package   cvc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cvc_Hubs extends cvc_proto_Object
{
    /**
     * Заглавие на модела
     */
    public $title = 'Хъбове на CVC';


    /**
     * Дефолтен файл за първоначално затеждане от csv-та
     */
    protected $defaultCsvFile = 'cvc/data/Hubs.csv';


    /**
     * Описание на модела
     */
    public function description()
    {
        parent::addFields($this);
    }
}