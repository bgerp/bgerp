<?php


/**
 * Драйвер за импортиране на производствени етапи
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_interface_ImportStep extends bgerp_BaseImporter
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ImportIntf';


    /**
     * Заглавие
     */
    public $title = 'Импорт на етапи в производството от csv';

    /*
     * Имплементация на bgerp_ImportIntf
     */


    /**
     * Функция, връщаща полетата в които ще се вкарват данни
     * в мениджъра-дестинация
     * Не връща полетата които са hidden, input=none,enum,key и keylist
     */
    public function getFields()
    {
        $fields = array();
        $fields['code'] = array('caption' => 'Код', 'mandatory' => 'mandatory');
        $fields['name'] = array('caption' => 'Наименование', 'mandatory' => 'mandatory');
        $fields['measureId'] = array('caption' => 'Мярка', 'mandatory' => 'mandatory');
        $fields['info'] = array('caption' => 'Описание');
        $fields['groups'] = array('caption' => 'Групи');
        $fields['planning_Steps_name'] = array('caption' => 'Операция');
        $fields['planning_Steps_centerId'] = array('caption' => 'Уточнения->Център на дейност', 'notColumn' => true, 'type' => 'key(mvc=planning_Centers,select=name)', 'default' => planning_Centers::UNDEFINED_ACTIVITY_CENTER_ID, 'mandatory' => 'mandatory');
        $fields['planning_Steps_isFinal'] = array('caption' => 'Уточнения->Вид', 'notColumn' => true, 'type' => 'enum(no=Междинен етап,yes=Финален етап)', 'default' => 'no', 'mandatory' => 'mandatory');
        $fields['planning_Steps_canStore'] = array('caption' => 'Уточнения->Складируем', 'notColumn' => true, 'type' => 'enum(yes=Да,no=Не)');
        $fields['planning_Steps_planningParams'] = array('caption' => 'Уточнения->Параметри', 'notColumn' => true, 'type' => 'keylist(mvc=cat_Params,select=typeExt)');
        $fields['planning_Steps_inputStores'] = array('caption' => 'Уточнения->Влагане ОТ', 'notColumn' => true, 'type' => 'keylist(mvc=store_Stores,select=name)');
        $fields['planning_Steps_storeIn'] = array('caption' => 'Уточнения->Произвеждане В', 'notColumn' => true, 'type' => 'key(mvc=store_Stores,select=name,allowEmpty)');
        $fields['Category'] = array('caption' => 'Уточнения->Категория', 'mandatory' => 'mandatory', 'notColumn' => true, 'type' => 'key(mvc=cat_Categories,select=name,coverClasses=cat_Categories,allowEmpty)');
        if($defaultFolderId = planning_Setup::get('DEFAULT_PRODUCTION_STEP_FOLDER_ID')){
            $fields['Category']['default'] = cat_Categories::fetchField("#folderId={$defaultFolderId}", 'id');
        }

        $driverId = planning_interface_StepProductDriver::getClassId();
        $driverOptions = array($driverId => cls::getTitle('planning_interface_StepProductDriver'));
        $fields['innerClass'] = array('caption' => 'Уточнения->Вид', 'notColumn' => true, 'type' => 'key(mvc=core_Classes,select=title)', 'default' => $driverId, 'options' => $driverOptions);

        return $fields;
    }



    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param array $rows   - масив с обработени csv данни, получен от Експерта в bgerp_Import
     * @param array $fields - масив с съответстията на колоните от csv-то и
     *                      полетата от модела array[{поле_от_модела}] = {колона_от_csv}
     *
     * @return string $html - съобщение с резултата
     */
    public function import($rows, $fields)
    {
        $fields['innerClass'] = planning_interface_StepProductDriver::getClassId();

        $res = parent::import($rows, $fields);


        return $res;
    }


    /**
     * Драйвъра може да се показва към всички мениджъри
     */
    public function isApplicable($className)
    {
        return $className == 'cat_Products';
    }
}
