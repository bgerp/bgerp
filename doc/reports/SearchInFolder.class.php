<?php


/**
 * Драйвер за справка търсеща думи в папка
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Документи » Търсене в папка
 */
class doc_reports_SearchInFolder extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'powerUser';
    
    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    protected $filterEmptyListFields = 'diff';
    
    
    /**
     * Кеш на предишните версии
     */
    private static $versionData = array();
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('folder', 'key2(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Папка,mandatory,after=title');
        $fieldset->FLD('text', 'text(rows=5)', 'caption=Думи,mandatory,after=folder,placeholder=Всяка отделна дума на нов ред,single=none');
    }
    
    
    /**
     * След изпращане на формата
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param core_Form           $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        $unique = array();
        $words = explode("\n", $form->rec->text);
        $duplicatedWords = array();
        
        // Проверка за повтарящи се думи
        foreach ($words as $word) {
            $key = $Driver->normalizeString($word);
            if (in_array($key, $unique)) {
                $duplicatedWords[] = trim($word);
            } else {
                $unique[] = $key;
            }
        }
        
        // Проверка за дуплицирани думи
        if (count($duplicatedWords)) {
            $duplicatedWords = implode('<span style=font-weight:normal>,</span> ', $duplicatedWords);
            $form->setError('text', "Следните думи се повтарят|*: <b>{$duplicatedWords}</b>");
        }
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
        
        // За всяка дума
        $words = explode("\n", $rec->text);
        foreach ($words as $word) {
            
            // Подготовка на заявка, намираща колко пъти се среща в документи в папка
            $cQuery = doc_Containers::getQuery();
            $cQuery->where("#folderId = {$rec->folder}");
            plg_Search::applySearch($word, $cQuery);
            
            // Нормализиране на думата
            $key = $this->normalizeString($word);
            $r = (object) array('string' => $word, 'count' => $cQuery->count(), 'index' => $key);
            
            $recs[$key] = $r;
        }
        
        // Подреждане по най-срещаните думи
        arr::sortObjects($recs, 'count', 'desc');
        
        return $recs;
    }
    
    
    /**
     * Нормализиране на думите
     *
     * @param string $string
     *
     * @return string
     */
    private function normalizeString($string)
    {
        return str_replace(' ', '_', trim(plg_Search::normalizeText($string)));
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        $fld->FLD('string', 'varchar', 'caption=Дума');
        $fld->FLD('diff', 'int', 'caption=Нови,tdClass=small-field');
        $fld->FLD('count', 'int', 'smartCenter,caption=Резултат,tdClass=small-field');
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на данните
     *
     * @param stdClass $rec     - запис на отчета
     * @param stdClass $dRec    - запис от детайла
     * @param array    $oldData - записа на предишната версия
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $row = new stdClass();
        $Int = cls::get('type_Int');
        $row->string = cls::get('type_Varchar')->toVerbal($dRec->string);
        $row->string = "<span style='font-weight:bold'>{$row->string}</span>";
        
        $row->count = $Int->toVerbal($dRec->count);
        if (doc_Threads::haveRightFor('list', (object) array('folderId' => $rec->folder))) {
            $row->count = ht::createLink($row->count, array('doc_Threads', 'list', 'folderId' => $rec->folder, 'search' => $dRec->string));
        }
        
        $row->num = $Int->toVerbal($dRec->num);
        $diff = self::getDiff($rec, $dRec);
        
        if (!empty($diff)) {
            $row->diff = $Int->toVerbal($diff);
            $color = ($diff < 0) ? 'red' : 'darkgreen';
            $sign = ($diff < 0) ? '' : '+';
            $row->diff = "<span style='color:{$color}'>{$sign}{$row->diff}</span>";
        }
        
        return $row;
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver      - драйвер
     * @param stdClass            $res         - резултатен запис
     * @param stdClass            $rec         - запис на справката
     * @param stdClass            $dRec        - запис на реда
     * @param core_BaseClass      $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->diff = self::getDiff($rec, $dRec);
    }
    
    
    /**
     * Връща разликата
     *
     * @param stdClass $rec  - запис на отчета
     * @param stdClass $dRec - запис от детайла
     *
     * @return float $diff  - разликата
     */
    private static function getDiff($rec, $dRec)
    {
        if (!isset(self::$versionData[$rec->id])) {
            self::$versionData[$rec->id] = self::getVersionBeforeData($rec);
        }
        $oldData = self::$versionData[$rec->id];
        
        // Ако има промяна спрямо старата версия, показват се промените
        if (isset($oldData[$dRec->index])) {
            $oldCount = $oldData[$dRec->index]->count;
            $diff = $dRec->count - $oldCount;
        } elseif (count($oldData)) {
            $diff = $dRec->count;
        }
        
        return $diff;
    }
    
    
    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $row->folder = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folder))->title;
    }
    
    
    /**
     * Връща данните от предишната версия
     *
     * @param stdClass $rec - записа на отчета
     *
     * @return array $versionBeforeData - данните от предишната версия
     */
    private static function getVersionBeforeData($rec)
    {
        $selectedVersionId = frame2_Reports::getSelectedVersionId($rec->id);
        
        // Ако няма избрана версия това е последната за справката
        if (!$selectedVersionId) {
            $query = frame2_ReportVersions::getQuery();
            $query->where("#reportId = {$rec->id}");
            $query->orderBy('id', 'DESC');
            $query->show('versionBefore');
            
            $versionBeforeId = $query->fetch()->versionBefore;
        } else {
            $versionBeforeId = frame2_ReportVersions::fetchField($selectedVersionId, 'versionBefore');
        }
        
        $versionBeforeData = (isset($versionBeforeId)) ? frame2_ReportVersions::fetchField($versionBeforeId, 'oldRec')->data->recs : array();
        
        return $versionBeforeData;
    }
    
    
    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     *
     * @return bool $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        $data = $rec->data;
        $oldData = self::getVersionBeforeData($rec);
        
        $send = false;
        if (is_array($data->recs)) {
            foreach ($data->recs as $rec) {
                if (isset($oldData[$rec->index])) {
                    $oldCount = $oldData[$rec->index]->count;
                    $diff = $rec->count - $oldCount;
                } else {
                    $diff = $rec->count;
                }
                
                if ($diff != 0) {
                    $send = true;
                    break;
                }
            }
        }
        
        return $send;
    }
}
