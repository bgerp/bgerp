<?php


/**
 * Драйвър за импортиране на артикули от Бизнес навигатор
 * в cat_Products, изпозва се плъгина bgerp_plg_Import той подава
 * на драйвъра масив от полета извлечени от csv файл, и масив от
 * полета от модела на кои колони от csv данните съответстват
 *
 *   - Преди импортирването на артикулите се по импортират при нужда
 *     Групите и Мерните единици.
 *
 *   - При импортирането на артикули имената на групите и мерките се заменят с
 *     техните ид-та от системата.
 *
 * 	 - Полето [code] в cat_Products се образува като от [Article String Code]
 * 	   на csv-то се премахнат "[" и "]".
 *
 * CSV колона:           | поле в cat_Products:
 * ––––––––––––––––––––––––––––––––––––––––––––––––––––
 * [Название]            | [name]
 * [Мерна единица]       | [measureId] (ид на подадената мярка)
 * [Номенклатура: име]   | [groups]  (ид на групата)
 * [Article String Code] | [bnavCode]
 *
 *
 * @category  bgerp
 * @package   bnav
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bnav_BnavImporter extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ImportIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Импорт от артикули от Бизнес навигатор';
    
    
    /**
     * Към кои мениджъри да се показва драйвъра
     */
    public static $applyOnlyTo = 'cat_Products';
    
    
    /**
     * Кои полета от cat_Products ще получат стойностти от csv-то
     */
    private static $importFields = 'name,measureId,groups,bnavCode';
    
    
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
     */
    public function getFields()
    {
        $fields = array();
        
        // Взимат се всички полета на мениджъра, в който ще се импортира
        $Dfields = $this->mvc->selectFields();
        $selFields = arr::make(self::$importFields, true);
        
        // За всяко поле посочено в, проверява се имали го като поле
        // ако го има се добавя в масива с неговото наименование
        foreach ($Dfields as $name => $fld) {
            if (isset($selFields[$name])) {
                $arr = array('caption' => $fld->caption, 'mandatory' => $fld->mandatory);
                $fields[$name] = $arr;
            }
        }
        
        return $fields;
    }
    
    
    /**
     * Импортиране на csv-файл в cat_Products
     *
     * @param array $rows   - масив с обработени csv данни,
     *                      получен от Експерта в bgerp_Import
     * @param array $fields - масив с съответстията на колоните от csv-то и
     *                      полетата от модела array[{поле_от_модела}] = {колона_от_csv}
     *
     * @return string $html - съобщение с резултата
     */
    public function import($rows, $fields)
    {
        $html = '';
        
        // Начало на таймера
        core_Debug::startTimer('import');
        
        // Импортиране на групите и мерните единици
        $params = $this->importParams($rows, $fields, $html);
        
        // импортиране на продуктите
        $this->importProducts($rows, $params, $fields, $html);
        
        // Стоп на таймера
        core_Debug::stopTimer('import');
        
        // Връща се резултата от импортирането, с изтеклото време
        return $html . 'Общо време: ' . round(core_Debug::$timers['import']->workingTime, 2) .' с<br />';
    }
    
    
    /**
     * Връща мениджъра към който се импортират продуктите
     */
    public function getDestinationManager()
    {
        return cls::get('cat_Products');
    }
    
    
    /**
     * Филтрира продуктовите групи и създава масив с неповтарящи се групи
     *
     * @param array $rows - масив получен от csv файл или текст
     *
     * @return array $fields - масив със съотвествия на полетата
     */
    private function filterImportParams($rows, $fields)
    {
        $newMeasures = $newGroups = array();
        foreach ($rows as $row) {
            
            // Намира се на кой индекс стои името на групата и мярката
            $groupIndex = $fields['groups'];
            $measureIndex = $fields['measureId'];
            
            if (!in_array($row[$groupIndex], $newGroups)) {
                
                // Недобавените групи в се добавят в нов масив
                $newGroups[] = $row[$groupIndex];
            }
            
            if (!array_key_exists($row[$measureIndex], $newMeasures)) {
                
                // Недобавените мерки в се добавят в нов масив
                $newMeasures[$row[$measureIndex]] = $row[$measureIndex];
            }
        }
        
        // Връщат се масив съдържащ уникалните групи и мерни единици
        return array('groups' => $newGroups, 'measures' => $newMeasures);
    }
    
    
    /**
     * Импортиране на групите от csv-то(ако ги няма)
     *
     * @param array $rows - масив получен от csv файл или текст
     *
     * @return array $fields - масив със съответствия
     *
     * @param string $html - Съобщение
     *
     * @return array масив със съответствия група от системата
     */
    private function importParams(&$rows, $fields, &$html)
    {
        $params = $this->filterImportParams($rows, $fields);
        
        $measures = $groups = array();
        $addedMeasures = $addedGroups = $updatedGroups = 0;
        
        // Импортиране на групите
        foreach ($params['groups'] as $gr) {
            $nRec = new stdClass();
            $nRec->name = $gr;
            if ($rec = cat_Groups::fetch("#name = '{$gr}'")) {
                $nRec->id = $rec->id;
                $updatedGroups++;
            } else {
                $addedGroups++;
            }
            
            $groups[$gr] = cat_Groups::save($nRec);
        }
        
        
        // Импортиране на мерните единици
        foreach ($params['measures'] as $measure) {
            if (!$id = cat_UoM::fetchBySinonim($measure)->id) {
                $id = cat_UoM::save((object) array('name' => $measure, 'shortName' => $measure));
                $addedMeasures++;
            }
            $measures[$measure] = $id;
        }
        
        $html .= "Добавени {$addedGroups} нови групи, Обновени {$updatedGroups} съществуващи групи<br>";
        $html .= "Добавени {$addedMeasures} нови мерни единици<br/>";
        
        return array('groups' => $groups, 'measures' => $measures);
    }
    
    
    /**
     * Импортиране на артикулите
     *
     * @param array  $rows   - хендлър на csv файл-а
     * @param array  $params - Масив с външни ключове на полета
     * @param array  $fields - масив със съответствия
     * @param string $html   - съобщение
     */
    private function importProducts($rows, $params, $fields, &$html)
    {
        $added = $updated = 0;
        
        foreach ($rows as $row) {
            $rec = new stdClass();
            $rec->name = $row[$fields['name']];
            $rec->measureId = $params['measures'][$row[$fields['measureId']]];
            $code = trim(str_replace(array('[', ']'), '', $row[$fields['bnavCode']]));
            $rec->code = $code;
            $rec->bnavCode = $row[$fields['bnavCode']];
            $rec->groups = "|{$params['groups'][$row[$fields['groups']]]}|";
            if ($rec->id = cat_Products::fetchField(array("#code = '[#1#]'", $code), 'id')) {
                $updated++;
            } else {
                $added++;
            }
            
            cat_Products::save($rec);
        }
        
        $html .= "Добавени {$added} нови артикула, Обновени {$updated} съществуващи артикула<br/>";
    }
    
    
    /**
     * Драйвъра може да се показва само към инстанция на cat_Products
     */
    public static function isApplicable($className)
    {
        return $className == self::$applyOnlyTo;
    }
}
