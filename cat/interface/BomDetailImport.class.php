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
class cat_interface_BomDetailImport extends import2_AbstractDriver
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'cat_interface_BomDetailImportIntf';


    /**
     * Заглавие
     */
    public $title = 'Импорт детайл на технологична рецепта';


    /**
     * Коя е ценовата политика
     */
    protected $bomRec;


    /**
     * Възможните действия в csv-то и на кой вид ред отговарят
     */
    private static $typeMap = array('input' => 'input', 'влаг.' => 'input', 'влаг' => 'input', 'влагане' => 'input',
                                    'pop' => 'pop', 'отп.' => 'pop', 'отп' => 'pop', 'отпадък' => 'pop', 'отпадак' => 'pop',
                                    'stage' => 'stage', 'етап' => 'stage',
                                    'subproduct' => 'subProduct', 'субпр.' => 'subProduct', 'субпр' => 'subProduct', 'субпродукт' => 'subProduct');


    /**
     * Добавя специфичните полета във формата за импорт
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function addImportFields($mvc, core_FieldSet $form)
    {
        $fields = $this->getFields($form->rec->{$mvc->masterKey});
        $form->_bomImportFields = $fields;

        $form->FLD('csvData', 'text(1000000)', 'width=100%,caption=Данни');
        $form->FLD('csvFile', 'fileman_FileType(bucket=bnav_importCsv)', 'width=100%,caption=CSV файл');

        $clipboardVals = export_Clipboard::getVals();
        if (countR($this->getClipboardOptions($clipboardVals))) {
            $refreshFields = array('csvData', 'csvFile', 'delimiter', 'enclosure', 'firstRow');
            foreach ($fields as $name => $field) {
                if (empty($field['notColumn'])) {
                    $refreshFields[] = "col{$name}";
                }
            }

            $form->FLD('fromClipboard', 'varchar', 'width=100%,caption=От клипборда,silent,removeAndRefreshForm=' . implode('|', $refreshFields));
        }

        $form->FLD('delimiter', 'varchar(1,size=5)', 'width=100%,caption=Настройки->Разделител,maxRadio=5,placeholder=Автоматично');
        $form->FLD('enclosure', 'varchar(1,size=3)', 'width=100%,caption=Настройки->Ограждане,placeholder=Автоматично');
        $form->FLD('firstRow', 'enum(,data=Данни,columnNames=Имена на колони)', 'width=100%,caption=Настройки->Първи ред,placeholder=Автоматично');

        foreach ($fields as $name => $field) {
            $fieldName = "col{$name}";
            $caption = $field['caption'] ?? $name;

            if (!empty($field['notColumn'])) {
                $type = $field['type'] ?? 'varchar';
                $form->FLD($fieldName, $type, "caption={$caption}");
                if (isset($field['options'])) {
                    $options = !empty($field['allowEmpty']) ? array('' => '') + $field['options'] : $field['options'];
                    $form->setOptions($fieldName, $options);
                } elseif (isset($field['suggestions'])) {
                    $form->setSuggestions($fieldName, $field['suggestions']);
                }
                if (array_key_exists('default', $field)) {
                    $form->setDefault($fieldName, $field['default']);
                }

                continue;
            }

            $mandatory = !empty($field['mandatory']) ? ',mandatory' : '';
            $form->FLD($fieldName, 'varchar', "caption=Съответствие в данните->{$caption}{$mandatory}");
        }
    }


    /**
     * Подготвя формата за импорт
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function prepareImportForm($mvc, core_FieldSet $form)
    {
        $form->info = tr('Въведете данни, качете CSV файл или изберете документ от клипборда');

        $clipboardVals = export_Clipboard::getVals();
        if (isset($form->fields['fromClipboard'])) {
            $form->setOptions('fromClipboard', array('' => '') + $this->getClipboardOptions($clipboardVals));
        }

        $fromClipboard = !empty($form->rec->fromClipboard);
        if ($fromClipboard) {
            foreach (array('delimiter', 'enclosure', 'firstRow') as $name) {
                $form->setField($name, 'input=none');
            }
        } else {
            $form->setSuggestions('delimiter', array('' => '', ',' => ',', ';' => ';', ':' => ':', '|' => '|', '\\t' => 'Таб'));
            $form->setSuggestions('enclosure', array('' => '', '"' => '"', "'" => "'"));
            $form->setDefault('delimiter', ',');
            $form->setDefault('enclosure', '"');
        }

        $clipboardColumns = $fromClipboard ? $this->getClipboardColumns($form->rec->fromClipboard, $clipboardVals) : array();
        $csvColumns = array(-1 => '') + array_combine(range(1, 20), range(1, 20));
        $defaultCsvColumns = array('position' => 1, 'type' => 2, 'productId' => 3, 'packagingId' => 4, 'propQuantity' => 5, 'description' => 6, 'paramId' => 7);
        $columnNo = 1;

        foreach ($form->_bomImportFields as $name => $field) {
            if (!empty($field['notColumn'])) {

                continue;
            }

            $fieldName = "col{$name}";
            if ($fromClipboard) {
                $form->setOptions($fieldName, array('' => '') + $clipboardColumns['options']);
                $matchedColumn = $this->findMatchingColumn($name, $field['caption'] ?? $name, $clipboardColumns);
                if (isset($matchedColumn)) {
                    $form->setDefault($fieldName, $matchedColumn);
                }
            } else {
                $form->setSuggestions($fieldName, $csvColumns);
                $form->setField($fieldName, 'unit=колона');
                $form->setDefault($fieldName, $defaultCsvColumns[$name] ?? $columnNo);
            }
            $columnNo++;
        }

        $form->_clipboardColumnMap = $clipboardColumns['map'] ?? array();
    }


    /**
     * Проверява входа и подготвя данните за импорт
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function checkImportForm($mvc, core_FieldSet $form)
    {
        $rec = &$form->rec;
        $sourceFields = 'csvData,csvFile' . (isset($form->fields['fromClipboard']) ? ',fromClipboard' : '');
        $sourceCnt = (int) !empty($rec->csvData) + (int) !empty($rec->csvFile) + (int) !empty($rec->fromClipboard);

        if (!$sourceCnt) {
            $form->setError($sourceFields, 'Трябва да е попълнено поне едно от полетата');
        } elseif ($sourceCnt > 1) {
            $form->setError($sourceFields, 'Трябва да е попълнено само едно от полетата');
        }

        if (!$form->gotErrors()) {
            $rec->importRows = $this->getImportRows($rec);
            if (!countR($rec->importRows)) {
                $form->setError($sourceFields, 'Не са открити данни за импорт');
            }
        }

        if (!$form->gotErrors()) {
            $rec->importFields = $this->getImportFieldMap($form, $form->_bomImportFields);
        }
    }


    /**
     * Изпълнява импорта
     *
     * @param core_Manager $mvc
     * @param stdClass     $rec
     *
     * @return string
     */
    public function doImport(core_Manager $mvc, $rec)
    {
        core_App::setTimeLimit(countR($rec->importRows) / 10 + 10);
        ini_set('memory_limit', '2048M');
        core_Debug::$isLogging = false;

        Mode::push('importing', 'true');
        $msg = $this->import($rec->importRows, $rec->importFields, $rec->{$mvc->masterKey});
        Mode::pop('importing');
        $mvc->_haveImportedRecs = true;

        return $msg;
    }


    /**
     * Функция, връщаща полетата в които ще се вкарват данни
     * в мениджъра-дестинация
     * Не връща полетата които са hidden, input=none,enum,key и keylist
     */
    public function getFields($bomId = null)
    {
        $fields = array();

        $bomId = $bomId ?? Request::get('bomId', 'int');
        expect($this->bomRec = cat_Boms::fetch($bomId));

        // Полетата са като в експорта на рецептата
        $fields['position'] = array('caption' => 'Позиция');
        $fields['type'] = array('caption' => 'Действие');
        $fields['productId'] = array('caption' => 'Артикул', 'mandatory' => 'mandatory');
        $fields['packagingId'] = array('caption' => 'Мярка');
        $fields['propQuantity'] = array('caption' => 'Количество');
        $fields['paramId'] = array('caption' => 'Параметър');
        $fields['description'] = array('caption' => 'Забележка');

        // Какво да става с редовете, които рецептата вече има
        if (cat_BomDetails::count("#bomId = {$bomId}")) {
            $fields['existingDetails'] = array('caption' => 'Уточнения->Наличните редове', 'notColumn' => true, 'default' => 'keep', 'type' => 'varchar', 'options' => arr::make('keep=Запазване - импортираните се добавят след тях,delete=Изтриване - остават само импортираните'));
        }

        return $fields;
    }


    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * Поддържа се експортния формат на рецептата, в който йерархията е в колоната с
     * позицията - редът с позиция '3.1' е подред на реда с позиция '3'. Първо се
     * валидират всички редове, след което се импортират ниво по ниво: най-напред тези
     * без баща, после тези с един баща и т.н. Позициите се преномерират плътно във
     * всяко ниво, така че дупките и разбърканите номера от csv-то се изчистват
     *
     * @param array $rows   - масив с обработените CSV или clipboard данни
     * @param array $fields - масив с съответстията на колоните от csv-то и
     *                      полетата от модела array[{поле_от_модела}] = {колона_от_csv}
     *
     * @return string $html - съобщение с резултата
     */
    public function import($rows, $fields, $bomId = null)
    {
        $added = $skipped = 0;
        core_Debug::startTimer('import');
        $bomId = $bomId ?? Request::get('bomId', 'int');
        $bomRec = cat_Boms::fetch($bomId);

        $oFields = $this->getFields($bomId);
        $Details = cls::get('cat_BomDetails');

        // Ако е указано, наличните редове се изтриват, за да остане само импортираното
        $deleted = 0;
        if (($fields['existingDetails'] ?? 'keep') == 'delete') {
            $deleted = $this->deleteExistingDetails($bomRec);
        }

        // 1. Всеки ред се разчита и валидира сам за себе си
        $parsedArr = $byPosition = array();
        $rowNo = 0;
        foreach ($rows as $row) {
            $rowNo++;
            $parsed = $this->parseRow($row, $fields, $oFields, $bomRec);
            $parsed->rowNo = $rowNo;

            // Не може две от позициите в csv-то да съвпадат
            if (isset($parsed->position)) {
                if (array_key_exists($parsed->position, $byPosition)) {
                    $parsed->errors[] = 'Дублирана позиция';
                } else {
                    $byPosition[$parsed->position] = $rowNo;
                }
            }

            $parsedArr[$rowNo] = $parsed;
        }

        // 2. Проверка на бащите и подреждане на редовете по нива
        $this->prepareHierarchy($parsedArr, $byPosition, $bomRec->id);

        // 3. Записване ниво по ниво, като позициите се преномерират в рамките на всяко ниво
        $errorCsv = array();
        $positionMap = $positionCounters = array();
        foreach ($parsedArr as $parsed) {
            if (countR($parsed->errors)) {
                $skipped++;
                $errorCsv[] = (object) array('position' => $parsed->csvPosition, 'code' => $parsed->code, 'errors' => implode(', ', $parsed->errors));

                continue;
            }

            $rec = $parsed->rec;
            $rec->parentId = isset($parsed->parentPosition) ? $positionMap[$parsed->parentPosition] : null;

            $counterKey = isset($rec->parentId) ? $rec->parentId : 0;
            if (!array_key_exists($counterKey, $positionCounters)) {
                $positionCounters[$counterKey] = $this->getNextPosition($bomRec->id, $rec->parentId);
            }
            $rec->position = $positionCounters[$counterKey]++;

            $this->saveRec($rec, $bomRec, $parsed->hasChildren);
            $added++;

            if (isset($parsed->position)) {
                $positionMap[$parsed->position] = $rec->id;
            }
        }

        // Ако има файл с грешки - създава се и се прикача към рецептата
        if(countR($errorCsv)){
            $csvFields = new core_FieldSet();
            $csvFields->FLD('position', 'varchar', 'caption=Позиция');
            $csvFields->FLD('code', 'varchar', 'caption=Код');
            $csvFields->FLD('errors', 'varchar', 'caption=Грешка');
            $errorCsv = csv_Lib::createCsv($errorCsv, $csvFields);
            $fileName = 'ErrImportBom.csv';
            $fileHnd = fileman::absorbStr($errorCsv, 'exportFiles', $fileName);
            $fileId = fileman::fetchByFh($fileHnd, 'id');
            doc_Linked::add($bomRec->containerId, $fileId, 'doc', 'file');
        }

        $msg = "Добавени редове|*: {$added}. |Пропуснати са|* {$skipped}.";
        if ($deleted) {
            $msg .= " |Изтрити са|* {$deleted} |налични реда|*.";
        }

        return $msg;
    }


    /**
     * Изтрива наличните редове на рецептата, преди да се импортират новите
     *
     * @param stdClass $bomRec - рецептата, в която се импортира
     *
     * @return int $deleted - брой изтрити редове
     */
    private function deleteExistingDetails($bomRec)
    {
        $Details = cls::get('cat_BomDetails');
        $countBefore = cat_BomDetails::count("#bomId = {$bomRec->id}");

        $query = cat_BomDetails::getQuery();
        $query->where("#bomId = {$bomRec->id} AND #parentId IS NULL");

        // Подредовете на етапите се изтриват от самия модел
        while ($rec = $query->fetch()) {
            if (!$Details->haveRightFor('delete', $rec)) continue;

            $Details->delete("#id = {$rec->id}");
        }

        $deleted = $countBefore - cat_BomDetails::count("#bomId = {$bomRec->id}");

        if ($deleted) {
            $Details->Master->logWrite('Изтриване на редовете преди импорт', $bomRec->id);
        }

        return $deleted;
    }


    /**
     * Разчита и валидира един ред от csv-то
     *
     * @param array    $row     - реда от csv-то
     * @param array    $fields  - съответствията на колоните
     * @param array    $oFields - полетата на драйвера
     * @param stdClass $bomRec  - рецептата, в която се импортира
     *
     * @return stdClass - разчетения ред, с намерените в него грешки
     */
    private function parseRow($row, $fields, $oFields, $bomRec)
    {
        $errors = array();
        $values = new stdClass();
        foreach ($fields as $name => $position) {
            if ($position != -1) {
                $value = isset($oFields[$name]['notColumn']) ? $position : ($row[$position] ?? null);
                $values->{$name} = is_string($value) ? trim($value) : $value;
            }
        }

        $csvPosition = (string) ($values->position ?? '');
        $code = $values->productId ?? null;

        // Позицията определя мястото в йерархията - '3.1' е подред на '3'
        $path = ($csvPosition !== '') ? $this->parsePosition($csvPosition) : array();
        if ($csvPosition !== '' && !countR($path)) {
            $errors[] = 'Невалидна позиция';
        }

        $rec = (object) array('bomId' => $bomRec->id, 'quantityInPack' => 1);

        // Какъв е вида на реда
        $rec->type = $this->getTypeFromVerbal($values->type ?? null);
        if (!isset($rec->type)) {
            $errors[] = 'Неразпознато действие';
        }

        $productRec = !empty($code) ? cat_Products::getByCode($code) : false;
        if (empty($productRec->productId)) {
            $errors[] = 'Неразпознат код';
        } else {
            $rec->resourceId = $productRec->productId;
            $pRec = cat_Products::fetch($rec->resourceId, 'canConvert,canManifacture,canStore,measureId');

            if (in_array($rec->type, array('input', 'pop')) && $pRec->canConvert != 'yes') {
                $errors[] = 'Материалът/Отпадакът не е вложим';
            } elseif (in_array($rec->type, array('pop', 'subProduct')) && $pRec->canStore != 'yes') {
                $errors[] = 'Отпадакът/Субпродукта не е складируем';
            } elseif ($rec->type == 'subProduct' && $pRec->canManifacture != 'yes') {
                $errors[] = 'Субпродуктът не е производим';
            } elseif ($rec->type == 'stage' && $pRec->canManifacture != 'yes') {
                $errors[] = 'Етапът не е производим';
            } else {
                $notAllowed = array();
                cls::get('cat_BomDetails')->findNotAllowedProducts($rec->resourceId, $bomRec->productId, $notAllowed);
                if (isset($notAllowed[$rec->resourceId])) {
                    $errors[] = 'Невъзможен за добавяне';
                }

                // Ако няма мярка, използва се основната на артикула
                $rec->packagingId = $productRec->packagingId;
                if (!empty($values->packagingId)) {
                    // В клипборда key полетата са във вътрешен вид (id),
                    // а в CSV мярката е текстово име/синоним
                    $uomRec = type_Int::isInt($values->packagingId) ? cat_UoM::fetch($values->packagingId) : cat_UoM::fetchBySinonim($values->packagingId);
                    if (!is_object($uomRec)) {
                        $errors[] = 'Неразпозната мярка';
                    } else {
                        $rec->packagingId = $uomRec->id;
                    }
                }
                setIfNot($rec->packagingId, $pRec->measureId);

                if (!array_key_exists($rec->packagingId, cat_Products::getPacks($rec->resourceId))) {
                    $errors[] = 'Мярката не е допустима за артикула';
                } else {
                    $pInfo = cat_Products::getProductInfo($rec->resourceId);
                    $rec->quantityInPack = isset($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
                }
            }
        }

        $rec->propQuantity = $values->propQuantity ?? null;
        if (empty($rec->propQuantity)) {
            $errors[] = 'Няма формула';
        }

        // От кой параметър зависи реда
        $rec->paramId = null;
        if (!empty($values->paramId)) {
            $rec->paramId = $this->getParamId($values->paramId);
            if (empty($rec->paramId)) {
                $errors[] = 'Неразпознат параметър';
            }
        }

        $rec->description = !empty($values->description) ? cls::get('type_Richtext')->fromVerbal($values->description) : null;

        $res = new stdClass();
        $res->rec = $rec;
        $res->errors = $errors;
        $res->code = $code;
        $res->csvPosition = $csvPosition;
        $res->position = countR($path) ? implode('.', $path) : null;
        $res->parentPosition = (countR($path) > 1) ? implode('.', array_slice($path, 0, -1)) : null;
        $res->path = $path;
        $res->hasChildren = false;

        return $res;
    }


    /**
     * Проверява бащите на редовете и ги подрежда по нива - първо тези без баща,
     * после тези с един баща и т.н.
     *
     * @param array $parsedArr  - разчетените редове, подреждат се на място
     * @param array $byPosition - позиция от csv-то към номера на реда
     * @param int   $bomId      - рецептата, в която се импортира
     *
     * @return void
     */
    private function prepareHierarchy(&$parsedArr, $byPosition, $bomId)
    {
        // Редовете се проверяват от най-плитките към най-дълбоките, за да се знае
        // дали бащата е валиден, преди да се проверява детето
        uasort($parsedArr, function ($a, $b) {

            return $this->compareParsed($a, $b);
        });

        $seen = array();
        foreach ($parsedArr as $parsed) {
            $parentParsed = null;

            if (isset($parsed->parentPosition)) {
                $parentRowNo = $byPosition[$parsed->parentPosition] ?? null;
                if (!isset($parentRowNo)) {
                    $parsed->errors[] = "Няма ред за позиция {$parsed->parentPosition}";
                } else {
                    $parentParsed = $parsedArr[$parentRowNo];
                    if (countR($parentParsed->errors)) {
                        $parsed->errors[] = "Пропуснат е редът на позиция {$parsed->parentPosition}";
                    } elseif ($parentParsed->rec->type != 'stage') {
                        $parsed->errors[] = "Редът на позиция {$parsed->parentPosition} не е етап";
                    }
                }
            }

            if (countR($parsed->errors)) continue;

            // Един и същ артикул не се импортира втори път в нивото
            $levelKey = (isset($parsed->parentPosition) ? $parsed->parentPosition : 'root') . '|' . $parsed->rec->resourceId . '|' . $parsed->rec->paramId;
            if (array_key_exists($levelKey, $seen)) {
                $parsed->errors[] = 'Артикулът се повтаря в нивото';

                continue;
            }
            $seen[$levelKey] = true;

            // Нито се дублира с ред, който рецептата вече има на това ниво
            if (!isset($parsed->parentPosition) && $this->isInBom($bomId, $parsed->rec)) {
                $parsed->errors[] = 'Артикулът вече е в рецептата на това ниво';

                continue;
            }

            // Етапът си има подред в csv-то, значи детайлите му не идват от рецептата му
            if (isset($parentParsed)) {
                $parentParsed->hasChildren = true;
            }
        }
    }


    /**
     * Рецептата има ли вече такъв ред на това ниво
     *
     * @param int      $bomId
     * @param stdClass $rec - разчетения ред
     *
     * @return bool
     */
    private function isInBom($bomId, $rec)
    {
        $query = cat_BomDetails::getQuery();
        $query->where("#bomId = {$bomId} AND #resourceId = {$rec->resourceId} AND #parentId IS NULL");
        $query->where(isset($rec->paramId) ? "#paramId = {$rec->paramId}" : '#paramId IS NULL');

        return (bool) $query->count();
    }


    /**
     * Кой ред се импортира преди другия - първо по-плитките, а в едно ниво - по позиция
     *
     * @param stdClass $a
     * @param stdClass $b
     *
     * @return int
     */
    private function compareParsed($a, $b)
    {
        $aDepth = countR($a->path);
        $bDepth = countR($b->path);
        if ($aDepth != $bDepth) {

            // Редовете без позиция са на най-горното ниво, но след номерираните
            if (!$aDepth || !$bDepth) {

                return $aDepth ? -1 : 1;
            }

            return ($aDepth < $bDepth) ? -1 : 1;
        }

        foreach ($a->path as $i => $aSegment) {
            if ($aSegment != $b->path[$i]) {

                return ($aSegment < $b->path[$i]) ? -1 : 1;
            }
        }

        return ($a->rowNo < $b->rowNo) ? -1 : (($a->rowNo > $b->rowNo) ? 1 : 0);
    }


    /**
     * Записва разчетен ред от csv-то
     *
     * @param stdClass $rec         - записа за добавяне
     * @param stdClass $bomRec      - рецептата, в която се импортира
     * @param bool     $hasChildren - дали подредовете му идват от csv-то
     *
     * @return void
     */
    private function saveRec($rec, $bomRec, $hasChildren)
    {
        // На етапите се задават и данните за производство от артикула им
        if ($rec->type == 'stage') {
            if ($Driver = cat_Products::getDriver($rec->resourceId)) {
                $productionData = $Driver->getProductionData($rec->resourceId);
                foreach (array('centerId', 'norm', 'storeIn', 'inputStores', 'fixedAssets', 'employees', 'labelPackagingId', 'labelQuantityInPack', 'labelType', 'labelTemplate') as $productionFld) {
                    $productionValue = $productionData[$productionFld] ?? null;
                    $rec->{$productionFld} = is_array($productionValue) ? keylist::fromArray($productionValue) : $productionValue;
                }
            }
            $rec->inputPreviousSteps = 'auto';
        }

        // Контекста на параметрите за формулата (бащата му вече е записан)
        $Details = cls::get('cat_BomDetails');
        $rec->params = $Details->getProductParamScope($rec, $bomRec->productId);

        // Ако етапът има подредове в csv-то, те се вземат от там, а не от рецептата му
        $skipStepDetails = ($rec->type == 'stage') && $hasChildren;
        if ($skipStepDetails) {
            Mode::push('dontAutoAddStepDetails', true);
        }
        $Details->save($rec);
        if ($skipStepDetails) {
            Mode::pop('dontAutoAddStepDetails');
        }

        if ($rec->type == 'stage') {
            cat_BomDetails::addParamsToStepRec($bomRec->productId, $rec);
        }
    }


    /**
     * Разбива позицията от csv-то на пътя ѝ в йерархията
     *
     * @param string $position - позиция от csv-то, например '3.1'
     *
     * @return array - сегментите на позицията или празен масив, ако е невалидна
     */
    private function parsePosition($position)
    {
        $res = array();
        $position = trim($position, " \t\n\r\0\x0B.");
        foreach (explode('.', $position) as $segment) {
            $segment = trim($segment);
            if ($segment === '') continue;

            if (!type_Int::isInt($segment) || $segment < 0) {

                return array();
            }
            $res[] = (int) $segment;
        }

        return $res;
    }


    /**
     * Кой вид ред отговаря на действието от csv-то
     *
     * @param string|null $value - действието от csv-то
     *
     * @return string|null - вида на реда или NULL, ако не е разпознат
     */
    private function getTypeFromVerbal($value)
    {
        $value = trim((string) $value);
        if ($value === '') return 'input';

        return static::$typeMap[mb_strtolower($value)] ?? null;
    }


    /**
     * Кой е параметъра с това име
     *
     * @param string $name - име на параметър
     *
     * @return int|null - ид на параметъра или NULL, ако не е намерен
     */
    private function getParamId($name)
    {
        $name = trim($name);
        if ($name === '') return null;

        // От клипборда key полето идва директно като id
        if (type_Int::isInt($name) && ($id = cat_Params::fetchField($name, 'id'))) {

            return $id;
        }

        if ($id = cat_Params::fetchField(array("#name = '[#1#]'", $name), 'id')) {

            return $id;
        }

        // Търси се и по показваното име на параметъра
        $nameLower = mb_strtolower($name);
        $query = cat_Params::getQuery();
        $query->where("#state = 'active'");
        $query->show('id,typeExt');
        while ($pRec = $query->fetch()) {
            if (mb_strtolower($pRec->typeExt) == $nameLower) {

                return $pRec->id;
            }
        }

        return null;
    }


    /**
     * Коя е следващата свободна позиция в нивото
     *
     * @param int      $bomId
     * @param int|null $parentId
     *
     * @return int
     */
    private function getNextPosition($bomId, $parentId)
    {
        $query = cat_BomDetails::getQuery();
        $cond = "#bomId = {$bomId} AND ";
        $cond .= isset($parentId) ? "#parentId = {$parentId}" : '#parentId IS NULL';
        $query->where($cond);
        $query->XPR('maxPosition', 'int', 'MAX(#position)');
        $position = $query->fetch()->maxPosition;

        return ++$position;
    }


    /**
     * Връща документите, запазени в клипборда
     */
    private function getClipboardOptions($clipboardVals)
    {
        $options = array();
        foreach ((array) $clipboardVals as $classId => $objects) {
            if (!cls::load($classId, true)) {

                continue;
            }

            $Class = cls::get($classId);
            foreach ((array) $objects as $objectId => $data) {
                $documentRow = $Class->getDocumentRow($objectId);
                $options["{$classId}_{$objectId}"] = !empty($documentRow->recTitle) ? $documentRow->recTitle : $documentRow->title;
            }
        }

        return $options;
    }


    /**
     * Връща колоните на избрания запис от клипборда
     */
    private function getClipboardColumns($source, $clipboardVals)
    {
        $result = array('options' => array(), 'map' => array(), 'names' => array());
        list($classId, $objectId) = explode('_', $source, 2);
        $data = $clipboardVals[$classId][$objectId] ?? null;
        if (!$data || !countR($data->recs ?? array())) {

            return $result;
        }

        $firstRec = reset($data->recs);
        foreach ((array) $firstRec as $name => $value) {
            $caption = $data->fields->fields[$name]->caption ?? $name;
            $result['options'][$caption] = $caption;
            $result['map'][$caption] = $name;
            $result['names'][$caption] = $name;
        }

        return $result;
    }


    /**
     * Намира най-подходящата колона от клипборда
     */
    private function findMatchingColumn($name, $caption, $columns)
    {
        $caption = mb_strtolower(trim($caption));
        $name = mb_strtolower(trim($name));

        foreach ($columns['names'] as $columnCaption => $columnName) {
            $columnCaptionLower = mb_strtolower(trim($columnCaption));
            $columnNameLower = mb_strtolower(trim($columnName));
            if ($columnCaptionLower == $caption || $columnNameLower == $name) {

                return $columnCaption;
            }
        }

        foreach ($columns['names'] as $columnCaption => $columnName) {
            $columnCaptionLower = mb_strtolower(trim($columnCaption));
            if (strpos($caption, $columnCaptionLower) !== false || strpos($columnCaptionLower, $caption) !== false) {

                return $columnCaption;
            }
        }

        return null;
    }


    /**
     * Извлича редовете от избрания източник
     */
    private function getImportRows($rec)
    {
        if (!empty($rec->fromClipboard)) {
            list($classId, $objectId) = explode('_', $rec->fromClipboard, 2);
            $clipboardVals = export_Clipboard::getVals();

            return (array) ($clipboardVals[$classId][$objectId]->recs ?? array());
        }

        $data = !empty($rec->csvFile) ? bgerp_plg_Import::getFileContent($rec->csvFile) : $rec->csvData;
        $delimiter = ($rec->delimiter == '\\t') ? "\t" : $rec->delimiter;

        return csv_Lib::getCsvRows($data, $delimiter, $rec->enclosure, $rec->firstRow);
    }

    /**
     * Връща съответствията между полетата за импорт и колоните
     */
    private function getImportFieldMap($form, $fields)
    {
        $result = array();
        $fromClipboard = !empty($form->rec->fromClipboard);

        foreach ($fields as $name => $field) {
            $value = $form->rec->{"col{$name}"} ?? null;
            if (!empty($field['notColumn'])) {
                $result[$name] = $value;
            } elseif ($fromClipboard) {
                $result[$name] = $form->_clipboardColumnMap[$value] ?? -1;
            } else {
                $result[$name] = isset($value) && strlen($value) ? $value : -1;
            }
        }

        return $result;
    }


    /**
     * Може ли драйверът да бъде избран
     *
     * @param core_Manager $mvc
     * @param int|null     $masterId
     * @param int|null     $userId
     *
     * @return bool
     */
    public function canSelectDriver(core_Manager $mvc, $masterId = null, $userId = null)
    {
        if (!($mvc instanceof cat_BomDetails)) {

            return false;
        }

        return !isset($masterId) || (bool) cat_Boms::fetchField($masterId, 'id');
    }
}
