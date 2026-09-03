<?php


/**
 * Обща реализация на формата за импорт на детайли в рецепти
 *
 * @category  bgerp
 * @package   cat
 *
 * @since     v 0.1
 */
abstract class cat_interface_BomDetailImportProto extends import2_AbstractDriver
{
    /**
     * Добавя полетата във формата за импорт
     */
    public function addImportFields($mvc, core_FieldSet $form)
    {
        $fields = $this->getFields($form->rec->{$mvc->masterKey});
        $form->_bomImportFields = $fields;

        $form->FLD('csvData', 'text(1000000)', 'width=100%,caption=Данни');
        $form->FLD('csvFile', 'fileman_FileType(bucket=bnav_importCsv)', 'width=100%,caption=CSV файл');

        $clipboardVals = import2_Clipboard::getVals();
        if (countR(import2_Clipboard::getOptions($clipboardVals))) {
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
     */
    public function prepareImportForm($mvc, core_FieldSet $form)
    {
        $form->info = tr('Въведете данни, качете CSV файл или изберете документ от клипборда');

        $clipboardVals = import2_Clipboard::getVals();
        if (isset($form->fields['fromClipboard'])) {
            $form->setOptions('fromClipboard', array('' => '') + import2_Clipboard::getOptions($clipboardVals));
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

        $clipboardColumns = $fromClipboard ? import2_Clipboard::getColumns($form->rec->fromClipboard, $clipboardVals) : array();
        $csvColumns = array(-1 => '') + array_combine(range(1, 20), range(1, 20));
        $defaultCsvColumns = $this->getDefaultCsvColumns();
        $columnNo = 1;

        foreach ($form->_bomImportFields as $name => $field) {
            if (!empty($field['notColumn'])) {

                continue;
            }

            $fieldName = "col{$name}";
            if ($fromClipboard) {
                $form->setOptions($fieldName, array('' => '') + $clipboardColumns['options']);
                $matchedColumn = null;
                $columnNames = array_unique(array_merge((array) ($field['columnNames'] ?? array()), array($name)));
                foreach ($columnNames as $columnName) {
                    $caption = ($columnName == $name) ? ($field['caption'] ?? $name) : $columnName;
                    $matchedColumn = import2_Clipboard::findMatchingColumn($columnName, $caption, $clipboardColumns);
                    if (isset($matchedColumn)) break;
                }
                if (isset($matchedColumn)) {
                    $form->setDefault($fieldName, $matchedColumn);
                }
            } else {
                $form->setSuggestions($fieldName, $csvColumns);
                $form->setDefault($fieldName, $defaultCsvColumns[$name] ?? $columnNo);
            }
            $columnNo++;
        }

        $form->_clipboardColumnMap = $clipboardColumns['map'] ?? array();
    }


    /**
     * Проверява входа и подготвя данните за импорт
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
     * Извлича редовете от избрания източник
     */
    private function getImportRows($rec)
    {
        if (!empty($rec->fromClipboard)) {
            return import2_Clipboard::getRows($rec->fromClipboard);
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
     * Връща полетата за конкретния вид рецепта
     */
    abstract public function getFields($bomId = null);


    /**
     * Връща дефолтните номера на CSV колоните
     */
    abstract protected function getDefaultCsvColumns();


    /**
     * Импортира подготвените редове
     */
    abstract public function import($rows, $fields, $bomId = null);
}
