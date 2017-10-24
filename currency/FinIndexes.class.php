<?php


/**
 * Финансови индекси
 *
 *
 * @category  bgerp
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class currency_FinIndexes extends core_Manager {
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, currency_Wrapper,
                     plg_Sorting, plg_State2';
    
    
    /**
     * Заглавие
     */
    var $title = 'Индекси';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "indexName, period, forDate, indexValue";
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo,admin,cash,bank,currency';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,currency';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('indexName',  'varchar(64)', 'caption=Индекс,mandatory,smartCenter');
        $this->FLD('period',     'enum(ON=овърнайт,
                                       1W=1 седмица,
                                       2W=2 седмици,
                                       3W=3 седмици,
                                       1M=1 месец,
                                       2M=2 месеца,
                                       3M=3 месеца,
                                       4M=4 месеца,
                                       5M=5 месеца,
                                       6M=6 месеца,
                                       7M=7 месеца,
                                       8M=8 месеца,
                                       9M=9 месеца,
                                       10M=10 месеца,
                                       11M=11 месеца,
                                       12M=12 месеца)', 'caption=Срочност,mandatory,smartCenter');
        $this->FLD('forDate',    'date',                'caption=За дата, mandatory,smartCenter');
        $this->FLD('indexValue', 'double',              'caption=Стойност, mandatory,smartCenter');
    }
    
    
    /**
     * Въвеждане на индекси EURIBOR от CSV файлове
     *
     * @return string $res
     */
    function act_LoadEuriborCsv()
    {
        $res = '';
        // Зареждаме файлове за обработка
        $csvFiles = array("http://www.emmi-benchmarks.eu/assets/modules/rateisblue/file_processing/publication/processed/hist_EURIBOR_" . date('Y') . ".csv");
        foreach ($csvFiles as $csvFile) {
            $indexName = 'EURIBOR';
            
            $createdRecs = 0;
            
            // Зарежда, обработва и записва в базата CSV файл
            if (($handle = @fopen($csvFile, "r")) !== FALSE) {
                while (($csvRow = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    if (empty($columns)) {
                        // Колоните са броя на датите във файла + 1 
                        $columns = count($csvRow);
                    }
                    
                    // Проверка дали няма ред, който да идва празен 
                    if ($csvRow[1] == '') {
                        break;
                    }
                    
                    // Контейнер със всички редове от CSV файла 
                    $csvContent[] = $csvRow;
                }
                
                fclose($handle);
                
                // За всяка дата
                for ($j = 1; $j <= ($columns - 1); $j++) {
                    
                    if (!$csvContent[0][$j]) continue;
                    
                    $forDate = substr($csvContent[0][$j], 6, 4) . "-" . substr($csvContent[0][$j], 3, 2) . "-" . substr($csvContent[0][$j], 0, 2);
                    
                    // За всеки ред след първия от CSV файла
                    for ($k = 1; $k <= (count($csvContent) - 1); $k++) {
                        $period = $csvContent[$k][0];
                        
                        switch ($period) {
                            case "ON" :
                            case "On" :
                            case "on" :
                                $period = "ON";
                                break;
                            
                            case "1w" :
                            case "1W" :
                            case "sw" :
                            case "Sw" :
                            case "SW" :
                                $period = "1W";
                                break;
                            
                            case "2w" :
                            case "2W" :
                                $period = "2W";
                                break;
                            
                            case "3w" :
                            case "3W" :
                                $period = "3W";
                                break;
                            
                            case "1m" :
                            case "1M" :
                                $period = "1M";
                                break;
                            
                            case "2m" :
                            case "2M" :
                                $period = "2M";
                                break;
                            
                            case "3m" :
                            case "3M" :
                                $period = "3M";
                                break;
                            
                            case "4m" :
                            case "4M" :
                                $period = "4M";
                                break;
                            
                            case "5m" :
                            case "5M" :
                                $period = "5M";
                                break;
                            
                            case "6m" :
                            case "6M" :
                                $period = "6M";
                                break;
                            
                            case "7m" :
                            case "7M" :
                                $period = "7M";
                                break;
                            
                            case "8m" :
                            case "8M" :
                                $period = "8M";
                                break;
                            
                            case "9m" :
                            case "9M" :
                                $period = "9M";
                                break;
                            
                            case "10m" :
                            case "10M" :
                                $period = "10M";
                                break;
                            
                            case "11m" :
                            case "11M" :
                                $period = "11M";
                                break;
                            
                            case "12m" :
                            case "12M" :
                                $period = "12M";
                                break;
                        }
                        
                        $rec = new stdClass();
                        $rec->indexName  = $indexName;
                        $rec->period     = $period;
                        $rec->forDate    = $forDate;
                        $rec->indexValue = $csvContent[$k][$j];
                        $rec->createdBy  = -1;
                        
                        $existingRecId = currency_FinIndexes::fetchField("#indexName      = '{$rec->indexName}'
                                                                          AND #period     = '{$rec->period}'
                                                                          AND #forDate    = '{$rec->forDate}'
                                                                          AND #indexValue = '{$rec->indexValue}'", 'id');
                        
                        if (empty($existingRecId)) {
                            $this->save($rec);
                            $createdRecs++;
                        }
                    }
                }
                
                $res .= "Създадени са {$createdRecs} нови индекса.</li>";
            } else {
                $errStr = "Не може да бъде отворен файла '{$csvFile}'";
                self::logErr($errStr);
                $res .= "<li style='color:red'> {$errStr}";
                
            }
        }
        
        return $res;
    }
    
    
    /**
     * Въвеждане на индекси EONIA от CSV файлове
     *
     * @return string $res
     */
    function act_LoadEoniaCsv()
    {
        $res = '';
        // Зареждаме файлове за обработка
        // $csvFiles = array(__DIR__ . "/csv/hist_EONIA_2012.csv");
        $csvFiles = array("http://www.emmi-benchmarks.eu/assets/modules/rateisblue/file_processing/publication/processed/hist_EONIA_" . date('Y') . ".csv");
        
        foreach ($csvFiles as $csvFile) {
            $indexName = 'EONIA';
            
            $createdRecs = 0;
            
            // Зарежда, обработва и записва в базата CSV файл
            if (($handle = @fopen($csvFile, "r")) !== FALSE) {
                while (($csvRow = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    // Контейнер със всички редове от CSV файла 
                    $csvContent[] = $csvRow;
                    
                    if (count($csvContent) == 1) {
                        $columns = 1;
                        
                        while (!empty($csvContent[0][$columns])) {
                            $columns++;
                        }
                    }
                }
                
                fclose($handle);
                
                // За всяка дата
                for ($j = 1; $j <= ($columns - 1); $j++) {
                    $forDate = substr($csvContent[0][$j], 6, 4) . "-" . substr($csvContent[0][$j], 3, 2) . "-" . substr($csvContent[0][$j], 0, 2);
                    
                    $rec = new stdClass();
                    $rec->indexName  = $indexName;
                    $rec->period     = "ON";
                    $rec->forDate    = $forDate;
                    $rec->indexValue = $csvContent[1][$j];
                    $rec->createdBy  = -1;
                    
                    $existingRecId = currency_FinIndexes::fetchField("#indexName      = '{$rec->indexName}'
                                                                      AND #period     = '{$rec->period}'
                                                                      AND #forDate    = '{$rec->forDate}'
                                                                      AND #indexValue = '{$rec->indexValue}'", 'id');
                    
                    if (empty($existingRecId)) {
                        $this->save($rec);
                        $createdRecs++;
                    }
                }
                
                $res .= "Създадени са {$createdRecs} нови индекса.</li>";
            } else {
                $errStr = "Не може да бъде отворен файла '{$csvFile}'";
                self::logErr($errStr);
                $res .= "<li style='color:red'> {$errStr}";
            }
        }
        
        return $res;
    }
    
    
    /**
     * Въвеждане на индекси Sofibid и Sofibor от CSV файлове
     *
     * @return string $res
     */
    function act_LoadSofibidSofiborCsv()
    {
        $res = '';
        // Зареждаме файлове за обработка
        // $csvFiles = array(__DIR__ . "/csv/Sofibor_Sofibid.csv");
        $csvFiles = array("http://www.bnb.bg/FinancialMarkets/FMSofibidAndSofibor/index.htm?download=csv&period&search=");
        
        foreach ($csvFiles as $csvFile) {
            $createdRecs = 0;
            
            // Зарежда, обработва и записва в базата CSV файл
            if (($handle = @fopen($csvFile, "r")) !== FALSE) {
                while (($csvRow = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    if (empty($columns)) {
                        // Колоните са броя на датите във файла + 1 
                        $columns = count($csvRow);
                    }
                    
                    // Контейнер със всички редове от CSV файла 
                    $csvContent[] = $csvRow;
                }
                
                fclose($handle);
                
                $rows = count($csvContent);
                
                // $forDate
                $pattern = "/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/";
                preg_match($pattern, $csvContent[0][0], $matches);
                $forDate = $matches[0];
                $forDate = substr($forDate, 6, 4) . "-" . substr($forDate, 3, 2) . "-" . substr($forDate, 0, 2);
                
                for ($j = 2; $j <= ($rows - 1); $j++) {
                    // $period
                    $period = $csvContent[$j][0];
                    $pattern = "/\([a-zA-Z0-9]{2,3}\)$/";
                    preg_match($pattern, $period, $matches);
                    $period = $matches[0];
                    $period = substr($period, 1, strlen($period) - 2);
                    
                    switch ($period) {
                        case "ON" :
                        case "On" :
                        case "on" :
                            $period = "ON";
                            break;
                        
                        case "1w" :
                        case "1W" :
                        case "sw" :
                        case "Sw" :
                        case "SW" :
                            $period = "1W";
                            break;
                        
                        case "2w" :
                        case "2W" :
                            $period = "2W";
                            break;
                        
                        case "3w" :
                        case "3W" :
                            $period = "3W";
                            break;
                        
                        case "1m" :
                        case "1M" :
                            $period = "1M";
                            break;
                        
                        case "2m" :
                        case "2M" :
                            $period = "2M";
                            break;
                        
                        case "3m" :
                        case "3M" :
                            $period = "3M";
                            break;
                        
                        case "4m" :
                        case "4M" :
                            $period = "4M";
                            break;
                        
                        case "5m" :
                        case "5M" :
                            $period = "5M";
                            break;
                        
                        case "6m" :
                        case "6M" :
                            $period = "6M";
                            break;
                        
                        case "7m" :
                        case "7M" :
                            $period = "7M";
                            break;
                        
                        case "8m" :
                        case "8M" :
                            $period = "8M";
                            break;
                        
                        case "9m" :
                        case "9M" :
                            $period = "9M";
                            break;
                        
                        case "10m" :
                        case "10M" :
                            $period = "10M";
                            break;
                        
                        case "11m" :
                        case "11M" :
                            $period = "11M";
                            break;
                        
                        case "12m" :
                        case "12M" :
                            $period = "12M";
                            break;
                    }
                    
                    // Sofibid
                    $rec = new stdClass();
                    $rec->indexName  = "SOFIBID";
                    $rec->period     = $period;
                    $rec->forDate    = $forDate;
                    $rec->indexValue = $csvContent[$j][1];
                    $rec->createdBy  = -1;
                    
                    $existingRecId = currency_FinIndexes::fetchField("#indexName      = '{$rec->indexName}'
                                                                          AND #period     = '{$rec->period}'
                                                                          AND #forDate    = '{$rec->forDate}'
                                                                          AND #indexValue = '{$rec->indexValue}'", 'id');
                    
                    if (empty($existingRecId)) {
                        $this->save($rec);
                        $createdRecs++;
                    }
                    
                    // ENDOF Sofibid
                    
                    // Sofibor
                    unset($rec->id);
                    $rec->indexName  = "SOFIBOR";
                    $rec->indexValue = $csvContent[$j][2];
                    $rec->createdBy  = -1;
                    
                    $existingRecId = currency_FinIndexes::fetchField("#indexName      = '{$rec->indexName}'
                                                                          AND #period     = '{$rec->period}'
                                                                          AND #forDate    = '{$rec->forDate}'
                                                                          AND #indexValue = '{$rec->indexValue}'", 'id');
                    
                    if (empty($existingRecId)) {
                        $this->save($rec);
                        $createdRecs++;
                    }
                    
                    // ENDOF Sofibor                        
                }
                
                $res .= "Създадени са {$createdRecs} нови индекса.</li>";
            } else {
                $errStr = "Не може да бъде отворен файла '{$csvFile}'";
                self::logErr($errStr);
                $res .= "<li style='color:red'> {$errStr}";
            }
        }
        
        return $res;
    }
    
    
    /**
     * Добавяне на бутони за зареждане на различните индекси
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->toolbar->addBtn('Зареждане EURIBOR',           array($this, 'loadEuriborCsv'), NULL, 'title= Зареждане на Euro Interbank Offered Rate');
        $data->toolbar->addBtn('Зареждане EONIA',             array($this, 'loadEoniaCsv'), NULL, 'title= Зареждане на Euro OverNight Index Average');
        $data->toolbar->addBtn('Зареждане SOFIBID и SOFIBOR', array($this, 'loadSofibidSofiborCsv'), NULL, 'title= Зареждане на Sofia Interbank Bid Rate и Sofia Interbank Offered Rate');
    }


    public static function getIndex($name, $period, $date, $force = TRUE)
    {
        expect(in_array($name, array('SOFIBOR', 'SOFIBID', 'EONIA', 'EURIBOR'), $name));

        $me = cls::get('currency_FinIndexes');
        $periodType = $me->getFieldType('period');
        expect(in_array($period, $periodType->options), $period);

        $res = self::fetchField("#indexName = '{$name}' AND #period = '{$period}' AND #date = '{$date}'", 'indexValue');
        
        if(($res === NULL) && $force) {
            if($name == 'SOFIBOR' || $name == 'SOFIBID') {
                Request::forward(array('currency_FinIndexes', 'loadSofibidSofiborCsv'));
            } elseif($name == 'EONIA') {
                Request::forward(array('currency_FinIndexes', 'loadEoniaCsv'));
            } elseif($name == 'EURIBOR') {
                Request::forward(array('currency_FinIndexes', 'loadEuriborCsv'));
            }
            
            $res = $me->getIndex($name, $period, $date, FALSE);
        }

        return $res;
    }
}