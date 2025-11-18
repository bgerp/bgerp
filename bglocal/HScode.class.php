<?php 

/**
 * Комбинирана номеклатура KH8 - 2025
 *
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Gabriela Petrova <gpetrova@experta.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bglocal_HScode extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Комбинирана митническа номенклатура';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'HS';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, bglocal_Wrapper, plg_Printing,
                       plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin,hr';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'admin,hr';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('key', 'varchar', 'caption=Код');
        $this->FLD('title', 'text', 'caption=Наименование');
    }
    
    
    /**
     * Изпълнява се преид импортирването на запис
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {

    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'bglocal/data/HScode.csv';
        $fields = array(0 => 'key', 1 => 'title');
        $cntObj = csv_Lib::largeImportOnceFromZero($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
    
    
    /**
     * Подготовка на опции за key2
     */
    public static function getSelectArr($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $mvc = cls::get(get_called_class());
        $res = [];
        
        // Вземаме всички записи в реда им (както са в CSV)
        $query = $mvc->getQuery();
        $query->orderBy('id');
        $all = [];
        while ($rec = $query->fetch()) {
            $all[] = $rec;
        }
        
        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $addedIds = [];
            
            // Проверка дали търсенето е само цифри (код)
            $searchKey = preg_replace('/\D/', '', $q);
            $isCodeSearch = ctype_digit($searchKey) && $searchKey !== '';
            
            foreach ($all as $i => $rec) {
                $match = false;
                
                // Търсене по текст
                if (mb_stripos($rec->title, $qLower) !== false) {
                    $match = true;
                }
                
                // Търсене по код (точен или частичен)
                if (!$match && $rec->key !== null) {
                    $keyTrim = trim((string)$rec->key);
                    
                    if ($isCodeSearch) {
                        // Ако е точен код, взимаме само редовете със съвпадение
                        if ($keyTrim === $searchKey || strpos($keyTrim, $searchKey) === 0 || mb_stripos($rec->title, $searchKey) !== false) {
                            $match = true;
                        }
                    }
                }
                
                if ($match && !isset($addedIds[$rec->id])) {
                    // Ако търсенето е текст → добавяме родител + деца
                    if (!ctype_digit($q)) {
                        $blockStart = $i;
                        $startKey = $rec->key !== null ? trim($rec->key) : '';
                        $startKeyLength = strlen($startKey);
                        $blockEnd = count($all);
                        
                        for ($j = $i + 1; $j < count($all); $j++) {
                            $next = $all[$j];
                            $nextKey = $next->key !== null ? trim($next->key) : '';
                            
                            if ($startKeyLength == 2 && $nextKey !== '' && strlen($nextKey) == 2) {
                                $blockEnd = $j;
                                break;
                            }
                            
                            if ($startKeyLength > 2 && $nextKey !== '' && strlen($nextKey) <= $startKeyLength) {
                                $blockEnd = $j;
                                break;
                            }
                        }
                        
                        $blockEnd = max($blockEnd, $blockStart + 1);
                        
                        for ($k = $blockStart; $k < $blockEnd; $k++) {
                            $r = $all[$k];
                            if (!isset($addedIds[$r->id])) {
                                $code = ($r->key !== null) ? $r->key : '';
                                $res[$r->id] = trim("{$code} {$r->title}");
                                $addedIds[$r->id] = true;
                            }
                        }
                    } else {
                        // Ако търсенето е само код → взимаме само реда
                        $code = ($rec->key !== null) ? $rec->key : '';
                        $res[$rec->id] = trim("{$code} {$rec->title}");
                        $addedIds[$rec->id] = true;
                    }
                }
            }
        } else {
            // Ако няма търсене → показваме целия CSV
            foreach ($all as $rec) {
                $code = ($rec->key !== null) ? $rec->key : '';
                $res[$rec->id] = trim("{$code} {$rec->title}");
            }
        }
        
        if ($limit) {
            $res = array_slice($res, 0, $limit, true);
        }
        
        return $res;
    }
}
