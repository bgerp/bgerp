<?php


/**
 * Абстрактен клас за наследяване на обекти на CSV
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
abstract class cvc_proto_Object extends core_Manager
{
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';


    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper, plg_Sorting, plg_State2';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "id,num,name,pCode,coord,countryId";


    /**
     * Дефолтен файл за първоначално затеждане от csv-та
     */
    protected $defaultCsvFile;


    /**
     * Описание на модела
     */
    public function addFields($mvc)
    {
        $mvc->FLD('num', 'int', 'caption=Код');
        $mvc->FLD('name', 'varchar', 'caption=Име');
        $mvc->FLD('pCode', 'varchar', 'caption=П.код');
        $mvc->FLD('coord', 'varchar', 'caption=Координати');
        $mvc->FLD('countryId', 'int', 'caption=Държава');
        $mvc->FNC('nameExt', 'varchar', 'caption=Пълно име');

        $this->setDbUnique('num');
    }


    /**
     * Разширеното име
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcNameExt(core_Mvc $mvc, $rec)
    {
        $rec->nameExt = "[{$rec->num}] {$rec->name}";
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        if(isset($this->defaultCsvFile)){

            $fields = array(
                0 => 'num',
                1 => 'name',
                2 => 'pCode',
                3 => 'coord',
                4 => 'countryId',
            );

            $cntObj = csv_Lib::importOnce($this, $this->defaultCsvFile, $fields);
            $res = $cntObj->html;

            return $res;
        }
    }


    /**
     * Синхронизира масив с обектите, с вече записаните такива
     *
     * @param array $array
     * @return void
     */
    public function sync($array)
    {
        // Ако има намерени офиси
        if(is_array($array)){
            core_Users::forceSystemUser();
            $defaultCountryId = cvc_Adapter::DEFAULT_COUNTRY_ID;
            array_walk($array, function(&$o) use ($defaultCountryId) {$o['countryId'] = $defaultCountryId;});

            // Извличат им се адресните данни
            $current = array();
            foreach ($array as $objectArr){
                $obj = (object)array('num' => $objectArr['id'], 'name' => $objectArr['nameBg'], 'pCode' => $objectArr['zip'], 'coord' => $objectArr['coord'], 'countryId' => $objectArr['countryId'], 'state' => 'active');
                $current[$obj->num] = $obj;
            }

            $query = self::getQuery();
            $exRecs = $query->fetchAll();
            $sync = arr::syncArrays($current, $exRecs, 'num', 'name,pCode,coord,countryId,state');

            // Добавяне на новите офиси
            if(countR($sync['insert'])){
                $this->saveArray($sync['insert']);
            }

            // Ъпдейт на офисите с промяна
            if(countR($sync['update'])){
                $this->saveArray($sync['update'], 'id,pCode,address,name,state');
            }

            // Затваряне на вече не-активните офиси
            if(countR($sync['delete'])){
                $closeRecs = array();
                foreach ($sync['delete'] as $officeId){
                    $closeRecs[] = (object)array('id' => $officeId, 'state' => 'closed');
                }

                $this->saveArray($closeRecs, 'id,state');
            }
        }
    }
}