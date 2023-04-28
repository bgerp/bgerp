<?php


/**
 * Драйвер за импортиране на начални салда на артикули
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_iface_OpeningBalanceImportImpl extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ImportIntf';


    /**
     * Заглавие
     */
    public $title = 'Импорт на начални салда на артикули от csv';


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
        $fields['code'] = array('caption' => 'Код', 'mandatory' => 'mandatory');
        $fields['quantity'] = array('caption' => 'Количество', 'mandatory' => 'mandatory');
        $fields['amount'] = array('caption' => 'Сума', 'mandatory' => 'mandatory');

        $fields['storeId'] = array('caption' => 'Уточнения->Склад', 'mandatory' => 'mandatory', 'notColumn' => true, 'type' => 'key(mvc=store_Stores,select=name,allowEmpty)', 'default' => store_Stores::getCurrent('id', false));
        $fields['valior'] = array('caption' => 'Уточнения->Вальор', 'mandatory' => 'mandatory', 'notColumn' => true, 'type' => 'date');

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
        $valior = $fields['valior'];
        $storeId = $fields['storeId'];

        $Double = core_Type::getByName('double');
        $wrongQuantities = $wrongAmounts = $skipped = 0;
        $notFountProducts = $details = $toCsv = $errorCsv = array();
        $productListId = acc_Lists::fetchBySystemId('catProducts')->id;
        $storeItemId = acc_Items::fetchItem('store_Stores', $storeId)->id;
        $debitAccId = acc_Accounts::getRecBySystemId('321')->id;
        $creditAccId = acc_Accounts::getRecBySystemId('900')->id;
        foreach ($rows as $csvRow){
            $code = $csvRow[$fields['code']];
            $quantityVerbal = $csvRow[$fields['quantity']];
            $amountVerbal = $csvRow[$fields['amount']];
            $errors = array();

            $add = true;
            $rec = new stdClass();
            $rec->debitAccId = $debitAccId;
            $rec->debitEnt1 = $storeItemId;
            if($pRec = cat_Products::getByCode($code)){
                $canStore = cat_Products::fetchField($pRec->productId, "canStore");
                if($canStore == 'yes'){
                    $rec->debitEnt2 = acc_Items::force(cat_Products::getClassId(), $pRec->productId, $productListId);
                } else {
                    $add = false;
                    $errors[] = "Не е складируем";
                }
            } else {
                $notFountProducts[$code] = $code;
                $add = false;
                $errors[] = "Неразпознат код";
            }

            $quantityVerbal = $quantityVerbal ?? 1;
            if($quantity = $Double->fromVerbal($quantityVerbal)){
                $rec->debitQuantity = $quantity;

                if($amount = $Double->fromVerbal($amountVerbal)){
                    $rec->amount = $amount;
                    $rec->debitPrice = $rec->amount / $quantity;
                } else {
                    $wrongAmounts++;
                    $add = false;
                    $errors[] = "Невалидна сума";
                }
            } else {
                $wrongQuantities++;
                $add = false;
                $errors[] = "Невалидно количество";
            }

            $rec->creditAccId = $creditAccId;
            $rec->reason = 'Начално салдо';
            if($add){
                $details[] = $rec;
            } else {
                $skipped++;
                $errorCsv[] = (object)array('code' => $code, 'quantity' => $quantityVerbal, 'amount' => $amountVerbal, 'errors' => implode(', ', $errors));
            }
            $toCsv[] = (object)array('code' => $code, 'quantity' => $quantityVerbal, 'amount' => $amountVerbal);
        }

        $msg = '';
        $msgType = 'notice';
        $countDetails = countR($details);
        if($countDetails){

            // Създава се МО с детайлите
            $folderId = store_Stores::forceCoverAndFolder($storeId);
            $articleRec = (object)array('valior' => $valior, 'reason' => 'Импортиране на начални салда', 'folderId' => $folderId);
            $articleId = acc_Articles::save($articleRec);
            foreach ($details as $detailRec){
                $detailRec->articleId = $articleId;
                acc_ArticleDetails::save($detailRec);
            }
            $msg = "Успешно създаден мемориален ордер|*: Mo{$articleId}. ";
            $cu = core_Users::getCurrent();
            doc_ThreadUsers::addShared($articleRec->threadId, $articleRec->containerId, $cu);

            // Импорт на данните като файл за връзка
            $csvFields = new core_FieldSet();
            $csvFields->FLD('code', 'varchar', 'caption=Код');
            $csvFields->FLD('quantity', 'varchar', 'caption=Количество');
            $csvFields->FLD('amount', 'varchar', 'caption=Сума');

            $csv = csv_Lib::createCsv($toCsv, $csvFields);
            $fileName = 'OpeningBalances.csv';
            $fileHnd = fileman::absorbStr($csv, 'exportFiles', $fileName);
            $fileId = fileman::fetchByFh($fileHnd, 'id');
            doc_Linked::add($articleRec->containerId, $fileId, 'doc', 'file');

            if(countR($errorCsv)){
                $csvFields->FLD('errors', 'varchar', 'caption=Грешка');
                $errorCsv = csv_Lib::createCsv($errorCsv, $csvFields);
                $fileName = 'ErrOpeningBalances.csv';
                $fileHnd = fileman::absorbStr($errorCsv, 'exportFiles', $fileName);
                $fileId = fileman::fetchByFh($fileHnd, 'id');
                doc_Linked::add($articleRec->containerId, $fileId, 'doc', 'file');
            }
        }

        $msg .= "|Импортирани артикули|*: {$countDetails}, |Пропуснати|*:{$skipped}. ";
        if(countR($notFountProducts)){
            $notFountProductsStr = implode(',', $notFountProducts);
            $msg .= "Следните кодове не отговарят на артикули|*: {$notFountProductsStr}. ";
            $msgType = 'warning';
        }

        if($wrongQuantities){
            $msg .= "|Има невалидни количества|*. ";
            $msgType = 'warning';
        }
        if($wrongAmounts){
            $msg .= "|Има невалидни суми|*";
            $msgType = 'warning';
        }

        if(isset($articleId)){
            redirect(array('acc_Articles', 'single', $articleId), null, $msg, $msgType);
        }

        return $msg;
    }


    /**
     * Драйвъра може да се показва към всички мениджъри
     */
    public function isApplicable($className)
    {
        return $className == 'cat_Products';
    }
}
