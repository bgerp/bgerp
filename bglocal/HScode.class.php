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
     * Кой има право да добавя?
     */
    public $canAdd = 'admin';
    
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('sectionId', 'varchar(4)', 'caption=Раздел->код');
        $this->FLD('sectionName', 'text', 'caption=Раздел->име');
        $this->FLD('chapterId', 'varchar(2)', 'caption=Глава->код');
        $this->FLD('chapterName', 'text', 'caption=Глава->име');
        $this->FLD('headingId', 'varchar(4)', 'caption=Подглава->код');
        $this->FLD('headingName', 'text', 'caption=Подглава->име');
        $this->FLD('level', 'int(4)', 'caption=Степен на подробност на съответния код на продукта');
        $this->FLD('cnCode', 'varchar(8)', 'caption=Код по КН');
        $this->FLD('title', 'text', 'caption=Описание на стоката');
        
        $this->setDbIndex('cnCode');
    }
    
    
    /**
     * Изпълнява се преид импортирването на запис
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {
        //$rec->title = $rec->cnCode . ' ' . $rec->title;
    }
    

    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'bglocal/data/HScode.csv';
        $fields = array(0 => 'sectionId', 1 => 'sectionName',2 => 'chapterId', 3 => 'chapterName',
                        4 => 'headingId', 5 => 'headingName',6 => 'level', 7 => 'cnCode',8 => 'title');
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
                if (!$match && $rec->cnCode !== null) {
                    $keyTrim = trim((string)$rec->cnCode);
                    
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
                        $startKey = $rec->cnCode !== null ? trim($rec->cnCode) : '';
                        $startKeyLength = strlen($startKey);
                        $blockEnd = count($all);
                        
                        for ($j = $i + 1; $j < count($all); $j++) {
                            $next = $all[$j];
                            $nextKey = $next->cnCode !== null ? trim($next->cnCode) : '';
                            
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
                        //$res[$r->chapterId] =  trim("{$r->chapterName}"); 
                        for ($k = $blockStart; $k < $blockEnd; $k++) {
                            $r = $all[$k];
                           
                            if (!isset($addedIds[$r->id])) {
                                $code = ($r->cnCode !== null) ? $r->cnCode : '';
                                
                                $res[$r->id] = trim("{$code} {$r->title}"); 
                                $addedIds[$r->id] = true;
                            }
                        }
                    } else {
                        // Ако търсенето е само код → взимаме само реда
                        $code = ($rec->cnCode !== null) ? $rec->cnCode : '';
                        $res[$rec->id] = trim("{$code} {$rec->title}");
                        $addedIds[$rec->id] = true;
                    }
                }
            }
        } else {
            // Ако няма търсене → показваме целия CSV
            foreach ($all as $rec) {
                $code = ($rec->cnCode !== null) ? $rec->cnCode : '';
                $res[$rec->id] = trim("{$code} {$rec->title}");
            }
        }
        
        if ($limit) {
            $res = array_slice($res, 0, $limit, true);
        }
        
        return $res;
    }


    /**
     * Връща HS кода, който най-точно съвпада с подадения префикс:
     * - кодът трябва да започва с $input
     * - избира се този с най-малко допълнителни цифри
     * - при равни дължини: най-близкият числово до $input, паднат с нули до дължината на кода
     *
     * @param string $input Подаден стринг/префикс (може да съдържа и други символи)
     * @param array  $codes Масив от HS кодове (стрингове)
     * @return string|null  Намереният код (почистен като оригиналния е trim-нат) или null ако няма съвпадение
     */
    public static function findBestHsCode(string $input, array $codes = array(), int $minLen = 8)
    {
        // Нормализираме входа до цифри
        $needle = preg_replace('/\D+/', '', $input);
        if ($needle === '') return null;

        // Ако не са подадени - зареждаме всички кодове
        if (empty($codes)) {
            $hsQuery = bglocal_HScode::getQuery();
            $hsQuery->show('id,cnCode');
            while ($hsRec = $hsQuery->fetch()) {
                $codes[$hsRec->id] = $hsRec->cnCode;
            }
        }

        // Нормализираме кодовете и правим индекс: normCode => оригинален код
        $map = array();
        foreach ($codes as $code) {
            $orig = trim((string)$code);
            $norm = preg_replace('/\D+/', '', $orig);
            if ($norm === '') continue;

            // Ако има дубликати след нормализация - пазим първия
            if (!isset($map[$norm])) {
                $map[$norm] = $orig;
            }
        }

        $len = strlen($needle);

        // Ако входът е >= 8 цифри: режем отдясно и търсим ТОЧНО съвпадение
        if ($len >= $minLen) {
            for ($tryLen = $len; $tryLen >= $minLen; $tryLen--) {
                $candidate = substr($needle, 0, $tryLen);
                if (isset($map[$candidate])) {
                    return $map[$candidate];
                }
            }

            return null;
        }

        // Ако входът е < 8 цифри: няма как да "режем до 8".
        // По избор: връщаме най-късия код, който започва с входа (практично за частично въведени кодове).
        $bestOrig = null;
        $bestExtra = null;
        $bestNorm = null;

        foreach ($map as $norm => $orig) {
            if (strpos($norm, $needle) !== 0) continue;

            $extra = strlen($norm) - $len; // колко цифри добавя кодът след входа
            if ($bestOrig === null || $extra < $bestExtra || ($extra === $bestExtra && strcmp($norm, $bestNorm) < 0)) {
                $bestOrig = $orig;
                $bestExtra = $extra;
                $bestNorm = $norm;
            }
        }

        return $bestOrig;
    }
}
