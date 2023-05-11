<?php


/**
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_interface_BomDetailImport extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ImportIntf';


    /**
     * Заглавие
     */
    public $title = 'Импорт детайл на технологична рецепта';


    /**
     * Коя е ценовата политика
     */
    protected $bomRec;


    /**
     * Инициализиране драйвъра
     */
    public function init($params = array())
    {
        $this->mvc = $params['mvc'];
    }


    /**
     * Функция, връщаща полетата в които ще се вкарват данни
     * в мениджъра-дестинация
     * Не връща полетата които са hidden, input=none,enum,key и keylist
     */
    public function getFields()
    {
        $fields = array();

        $bomId = Request::get('bomId', 'int');
        expect($this->bomRec = cat_Boms::fetch($bomId));
        $stepOptions = cat_BomDetails::getParentOptions($bomId);

        $fields['productId'] = array('caption' => 'Код', 'mandatory' => 'mandatory');
        $fields['propQuantity'] = array('caption' => 'Количество');
        $fields['parentId'] = array('caption' => 'Уточнения->Етап', 'notColumn' => true, 'default' => null, 'type' => 'varchar', 'options' => $stepOptions, 'allowEmpty' => true);

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
        $ignoreArr = array();

        $added = $skipped = 0;
        core_Debug::startTimer('import');
        $bomId = Request::get('bomId');
        $bomRec = cat_Boms::fetch($bomId);

        $oFields = $this->getFields();

        $errorCsv = $saveArr = array();
        foreach ($rows as $row) {
            $errors = array();
            $rec = new stdClass();
            foreach ($fields as $name => $position) {
                if ($position != -1) {
                    $value = $row[$position];
                    if (isset($oFields[$name]['notColumn'])) {
                        $value = $position;
                    }

                    $rec->{$name} = $value;
                }
            }

            $code = $rec->productId;
            $add = true;
            $productRec = cat_Products::getByCode($rec->productId);
            if(empty($productRec->productId)){
                $errors[] = 'Неразпознат код';
                $add = false;
            } else {
                $productRec = cat_Products::fetch($productRec->productId, 'state,canConvert');
                if($productRec->canConvert != 'yes'){
                    $errors[] = 'Артикулът не е вложим';
                    $add = false;
                } elseif(in_array($productRec, array('closed', 'rejected'))) {
                    $errors[] = 'Артикулът не е активен';
                    $add = false;
                } else {
                    $rec->resourceId = $productRec->id;
                    $rec->packagingId = cat_Products::fetchField($productRec->id, 'measureId');
                    $rec->quantityInPack = 1;
                    $rec->type = 'input';
                    $rec->bomId = $bomRec->id;
                    $rec->parentId = empty($fields['parentId']) ? null : $fields['parentId'];
                }
            }

            if($add){
                $added++;
                $saveArr[] = $rec;
            } else {
                $skipped++;
                $errorCsv[] = (object)array('code' => $code, 'errors' => implode(', ', $errors));
            }
        }

        if(countR($saveArr)){
            foreach ($saveArr as $newRec){
                cls::get('cat_BomDetails')->save($newRec);
            }
        }

        if(countR($errorCsv)){
            $csvFields = new core_FieldSet();
            $csvFields->FLD('code', 'varchar', 'caption=Код');
            $csvFields->FLD('errors', 'varchar', 'caption=Грешка');
            $errorCsv = csv_Lib::createCsv($errorCsv, $csvFields);
            $fileName = 'ErrImportBom.csv';
            $fileHnd = fileman::absorbStr($errorCsv, 'exportFiles', $fileName);
            $fileId = fileman::fetchByFh($fileHnd, 'id');
            doc_Linked::add($bomRec->containerId, $fileId, 'doc', 'file');
        }

        $msg = "Добавени редове|*: {$added}. |Пропуснати са|* {$skipped}";

        return $msg;
    }


    /**
     * Драйвъра може да се показва към всички мениджъри
     */
    public function isApplicable($className)
    {
        return $className == 'cat_BomDetails';
    }
}
