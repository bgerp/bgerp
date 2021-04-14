<?php


/**
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class price_interface_ListRulesImport extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ImportIntf';


    /**
     * Коя е ценовата политика
     */
    public $listRec;


    /**
     * Заглавие
     */
    public $title = 'Импорт на ценови правила от csv';

    /*
     * Имплементация на bgerp_ImportIntf
     */


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

        $fields['productId'] = array('caption' => 'Код', 'mandatory' => 'mandatory');
        $fields['price'] = array('caption' => 'Цена');

        $listId = Request::get('listId', 'int');
        expect($this->listRec = price_Lists::fetch($listId));

        $fields['currencyId'] = array('caption' => 'Уточнения->Валута', 'mandatory' => 'mandatory', 'notColumn' => true, 'type' => 'customKey(mvc=currency_Currencies,key=code,select=code)', 'default' => $this->listRec->currency);
        $fields['vat'] = array('caption' => 'Уточнения->ДДС', 'mandatory' => 'mandatory', 'notColumn' => true, 'type' => 'enum(yes=С ДДС,no=Без ДДС)', 'default' => $this->listRec->vat);

        return $fields;
    }


    /**
     * Проверява редовете от csv-то
     *
     * @param $rows
     * @param $fields
     * @param $errorStr
     * @throws core_exception_Break
     */
    public function checkRows(&$rows, $fields, &$errArr)
    {
        $errArr = $recs = array();
        //bp($errArr, $rows);
        $i = 1;
        foreach ($rows as $row) {
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

            $productRec = cat_Products::getByCode($rec->productId);

            if(empty($productRec->productId)){
                $errArr[$i][] = $rec->productId . ' |Няма артикул с такъв код|*';
            } else {
                $rec->productId = $productRec->productId;

                $productRec = cat_Products::fetch($rec->productId, 'state,canSell');
                if($productRec->canSell != 'yes'){
                    $errArr[$i][] = $rec->productId . ' |Не е продаваем|*';
                } elseif($productRec->state == 'rejected'){
                    $errArr[$i][] = $rec->productId . ' |Артикулът е оттеглен|*';
                }
            }

            $Double = core_Type::getByName('double');
            if (!$price = $Double->fromVerbal($rec->price)) {
                $errArr[$i][] = $rec->price . ' |Не е валидна цена|*';
            } else {
                $rec->price = $price;
            }
            $recs[] = $rec;

            $i++;
        }

        $rows = $recs;

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
        $added = $skipped = 0;
        core_Debug::startTimer('import');

        $oFields = $this->getFields();
        foreach ($rows as $row) {
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

            $productRec = cat_Products::getByCode($rec->productId);
            if(empty($productRec->productId)){
                $skipped++;
                continue;
            } else {
                $productId = $productRec->productId;

                $productRec = cat_Products::fetch($productId, 'state,canSell');
                if($productRec->canSell != 'yes'){
                    $skipped++;
                    continue;
                } elseif($productRec->state == 'rejected'){
                    $skipped++;
                    continue;
                }
            }

            $Double = core_Type::getByName('double');
            if (!$price = $Double->fromVerbal($rec->price)) {
                $skipped++;
                continue;
            }

            $vat = ($rec->vat == 'yes');
            price_ListRules::addProductRule($this->listRec->id, $rec->productId, $price, $rec->currencyId, $vat);
            $added++;
        }

        $msg = "{$added} нови правила са добавени. Пропуснати са {$skipped}";
        return $msg;
    }


    /**
     * Драйвъра може да се показва към всички мениджъри
     */
    public function isApplicable($className)
    {
        return $className == 'price_ListRules';
    }
}
