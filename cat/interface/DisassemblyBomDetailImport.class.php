<?php


/**
 * Импорт на произведени артикули в рецепта за разпад
 *
 * @category  bgerp
 * @package   cat
 *
 * @since     v 0.1
 */
class cat_interface_DisassemblyBomDetailImport extends cat_interface_BomDetailImportProto
{
    /**
     * Интерфейси, поддържани от драйвера
     */
    public $interfaces = 'cat_interface_BomDetailImportIntf';


    /**
     * Заглавие
     */
    public $title = 'Импорт на произведени артикули в рецепта за разпад';


    /**
     * Връща полетата за импорт
     *
     * @param int|null $bomId
     *
     * @return array
     */
    public function getFields($bomId = null)
    {
        $bomId = $bomId ?? Request::get('bomId', 'int');
        expect($bomRec = cat_DisassemblyBoms::fetch($bomId));

        $fields = array();
        $fields['code'] = array('caption' => 'Код', 'mandatory' => 'mandatory', 'columnNames' => array('resourceId'));
        $fields['packagingId'] = array('caption' => 'Мярка');
        $fields['packQuantity'] = array('caption' => 'Количество', 'mandatory' => 'mandatory');
        if ($bomRec->allocationBy == 'manual') {
            $fields['costPercent'] = array('caption' => '% от себестойността');
        }
        $fields['notes'] = array('caption' => 'Описание');

        if (cat_DisassemblyBomDetails::count("#bomId = {$bomId} AND #type = 'production' AND #state != 'rejected'")) {
            $fields['existingDetails'] = array('caption' => 'Уточнения->Наличните редове', 'notColumn' => true, 'default' => 'keep', 'type' => 'varchar', 'options' => arr::make('keep=Запазване - импортираните се добавят след тях,delete=Изтриване - остават само импортираните'));
        }

        return $fields;
    }


    /**
     * Импортира произведените артикули
     *
     * @param array    $rows
     * @param array    $fields
     * @param int|null $bomId
     *
     * @return string
     */
    public function import($rows, $fields, $bomId = null)
    {
        $added = $skipped = $deleted = 0;
        $bomId = $bomId ?? Request::get('bomId', 'int');
        expect($bomRec = cat_DisassemblyBoms::fetch($bomId));

        $Details = cls::get('cat_DisassemblyBomDetails');
        $oFields = $this->getFields($bomId);
        $replaceExisting = (($fields['existingDetails'] ?? 'keep') == 'delete');

        $existingIds = $existingProducts = $uomProductIds = array();
        $dQuery = cat_DisassemblyBomDetails::getQuery();
        $dQuery->where("#bomId = {$bomId} AND #type = 'production' AND #state != 'rejected'");
        $dQuery->show('id,productId');
        while ($dRec = $dQuery->fetch()) {
            $existingIds[$dRec->id] = $dRec->id;
            $existingProducts[$dRec->productId] = $dRec->productId;
            if (!$replaceExisting) {
                $uomProductIds[$dRec->productId] = $dRec->productId;
            }
        }

        $parsedArr = $importedProducts = array();
        $rowNo = 0;
        foreach ($rows as $row) {
            $rowNo++;
            $parsed = $this->parseDisassemblyRow($row, $fields, $oFields, $bomRec);

            if (isset($parsed->rec->productId)) {
                $productId = $parsed->rec->productId;
                if (isset($importedProducts[$productId])) {
                    $parsed->errors[] = 'Артикулът се повтаря в данните за импорт';
                } elseif (!$replaceExisting && isset($existingProducts[$productId])) {
                    $parsed->errors[] = 'Артикулът вече е добавен в рецептата';
                }

                if (!countR($parsed->errors)) {
                    $candidateIds = $uomProductIds + array($productId => $productId);
                    if ($bomRec->allocationBy == 'quantity' && !cat_Products::areProductsInTheSameUom($candidateIds)) {
                        $parsed->errors[] = 'Артикулът не е в мярка, производна на мерките на останалите произведени артикули';
                    } else {
                        $importedProducts[$productId] = $productId;
                        $uomProductIds[$productId] = $productId;
                    }
                }
            }

            $parsed->rowNo = $rowNo;
            $parsedArr[] = $parsed;
        }

        // При замяна процентите се отнасят само за новите редове. Празният
        // процент поема остатъка по същия начин, както при ръчно добавяне.
        if ($replaceExisting && $bomRec->allocationBy == 'manual') {
            $sum = 0;
            foreach ($parsedArr as $parsed) {
                if (countR($parsed->errors)) continue;

                if (!isset($parsed->rec->costPercent)) {
                    $parsed->rec->costPercent = round(max(1 - $sum, 0), 4);
                }
                $sum += $parsed->rec->costPercent;
            }
        }

        $errorCsv = $importedManualPercents = array();
        foreach ($parsedArr as $parsed) {
            if (countR($parsed->errors)) {
                $skipped++;
                $errorCsv[] = (object) array('row' => $parsed->rowNo, 'code' => $parsed->code, 'errors' => implode(', ', $parsed->errors));

                continue;
            }

            $rec = $parsed->rec;
            if ($bomRec->allocationBy == 'manual') {
                if (!isset($rec->costPercent)) {
                    $rec->costPercent = round(max(1 - cat_plg_DisassemblyDoc::sumRowsPercent($Details->Master, $bomId), 0), 4);
                }
                if (!$replaceExisting) {
                    $rec->_rebalanceOtherRows = true;
                }
            }

            $Details->save($rec);
            if ($replaceExisting && $bomRec->allocationBy == 'manual') {
                $importedManualPercents[$rec->id] = $rec->costPercent;
            }
            $added++;
        }

        // При замяна старите редове се махат след успешното добавяне на поне един
        // нов ред, за да не остане рецептата без произведен артикул.
        if ($replaceExisting && $added) {
            foreach ($existingIds as $id) {
                $oldRec = $Details->fetch($id);
                if ($oldRec && $Details->haveRightFor('delete', $oldRec)) {
                    $Details->delete($id);
                    $deleted++;
                }
            }

            // Оттеглянето на старите редове изравнява процентите автоматично.
            // Връщаме импортираните стойности без нови ревизии, както прави
            // автоматичното изравняване в cat_plg_DisassemblyDoc.
            foreach ($importedManualPercents as $id => $percent) {
                $newRec = $Details->fetch($id);
                if (!$newRec) continue;

                $newRec->costPercent = $percent;
                $newRec->_skipDetailRevision = true;
                $Details->save($newRec, 'costPercent');
            }
        }

        if (countR($errorCsv)) {
            $csvFields = new core_FieldSet();
            $csvFields->FLD('row', 'int', 'caption=Ред');
            $csvFields->FLD('code', 'varchar', 'caption=Код');
            $csvFields->FLD('errors', 'varchar', 'caption=Грешка');
            $errorContent = csv_Lib::createCsv($errorCsv, $csvFields);
            $fileHnd = fileman::absorbStr($errorContent, 'exportFiles', 'ErrImportDisassemblyBom.csv');
            $fileId = fileman::fetchByFh($fileHnd, 'id');
            doc_Linked::add($bomRec->containerId, $fileId, 'doc', 'file');
        }

        if ($added) {
            $Details->Master->logWrite('Импорт на произведени артикули', $bomId);
        }

        $msg = "Добавени редове|*: {$added}. |Пропуснати са|* {$skipped}.";
        if ($deleted) {
            $msg .= " |Изтрити са|* {$deleted} |налични реда|*.";
        }

        return $msg;
    }


    /**
     * Може ли драйверът да бъде избран
     */
    public function canSelectDriver(core_Manager $mvc, $masterId = null, $userId = null)
    {
        if (!($mvc instanceof cat_DisassemblyBomDetails)) {

            return false;
        }

        return !isset($masterId) || (bool) cat_DisassemblyBoms::fetchField($masterId, 'id');
    }


    /**
     * Дефолтните колони във CSV файла
     */
    protected function getDefaultCsvColumns()
    {
        $bomId = Request::get('bomId', 'int');
        $isManual = ($bomId && cat_DisassemblyBoms::fetchField($bomId, 'allocationBy') == 'manual');

        return array('code' => 1, 'packagingId' => 2, 'packQuantity' => 3, 'costPercent' => 4, 'notes' => $isManual ? 5 : 4);
    }


    /**
     * Разчита и валидира един ред
     */
    private function parseDisassemblyRow($row, $fields, $oFields, $bomRec)
    {
        $errors = array();
        $values = new stdClass();
        foreach ($fields as $name => $position) {
            if ($position != -1) {
                $value = isset($oFields[$name]['notColumn']) ? $position : ($row[$position] ?? null);
                $values->{$name} = is_string($value) ? trim($value) : $value;
            }
        }

        $code = $values->code ?? null;
        $rec = (object) array('bomId' => $bomRec->id, 'type' => 'production', 'quantityInPack' => 1);

        $productByCode = strlen((string) $code) ? cat_Products::getByCode($code) : false;
        if (empty($productByCode->productId)) {
            $errors[] = 'Неразпознат код';
        } else {
            $rec->productId = $productByCode->productId;
            $pRec = cat_Products::fetch($rec->productId, 'state,canManifacture,generic,measureId');

            if ($pRec->state != 'active') {
                $errors[] = 'Артикулът не е активен';
            } elseif ($pRec->canManifacture != 'yes') {
                $errors[] = 'Артикулът не е производим';
            } elseif ($pRec->generic == 'yes') {
                $errors[] = 'Артикулът е генеричен';
            } elseif ($rec->productId == $bomRec->productId) {
                $errors[] = 'Артикулът съвпада с артикула за разпад';
            }

            $rec->packagingId = $productByCode->packagingId;
            if (!empty($values->packagingId)) {
                $uomRec = type_Int::isInt($values->packagingId) ? cat_UoM::fetch($values->packagingId) : cat_UoM::fetchBySinonim($values->packagingId);
                if (!is_object($uomRec)) {
                    $errors[] = 'Неразпозната мярка';
                } else {
                    $rec->packagingId = $uomRec->id;
                }
            }
            setIfNot($rec->packagingId, $pRec->measureId);

            $packs = cat_Products::getPacks($rec->productId);
            if (!isset($packs[$rec->packagingId])) {
                $errors[] = 'Мярката не е допустима за артикула';
            } else {
                $pInfo = cat_Products::getProductInfo($rec->productId);
                $rec->quantityInPack = isset($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
            }
        }

        $quantityType = core_Type::getByName('double(Min=0)');
        $rec->packQuantity = $quantityType->fromVerbal($values->packQuantity ?? null);
        if (!strlen((string) ($values->packQuantity ?? null))) {
            $errors[] = 'Няма количество';
        } elseif ($rec->packQuantity === false || $rec->packQuantity < 0) {
            $errors[] = 'Невалидно количество';
        }

        $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
        $rec->notes = !empty($values->notes) ? cls::get('type_Richtext')->fromVerbal($values->notes) : null;

        if ($bomRec->allocationBy == 'manual' && strlen((string) ($values->costPercent ?? null))) {
            if (($fields['costPercent'] ?? null) == 'costPercent' && is_numeric($values->costPercent)) {
                $rec->costPercent = (double) $values->costPercent;
            } else {
                $percentType = core_Type::getByName('percent');
                $rec->costPercent = $percentType->fromVerbal($values->costPercent);
            }
            if ($rec->costPercent === false || $rec->costPercent < 0 || $rec->costPercent > 1) {
                $errors[] = 'Невалиден процент от себестойността';
            }
        }

        return (object) array('rec' => $rec, 'errors' => $errors, 'code' => $code);
    }
}
