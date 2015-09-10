<?php


/**
 * Грешки, които не са прихванати от обработвача 
 * 
 * @category  bgerp
 * @package   logs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_PhpErr extends core_Manager
{
    
    
    /**
     * Максимален брой записи, които ще се извличат
     */
    static $maxLimit = 20;
    
    
    /**
     * След колко дни да се итриват от лога
     */
    static $removeAfterDays = 7;
    
    
    /**
     * Кои грешки да се логват отдалечено, ако са зададени константите
     * Ако е празен масив ще се репортуват всички грешки, в противен случай, 
     * само грешките които са в масива
     */
    static $reportTypeArr = array('error', 'warning');
    
    
    /**
     * Заглавие
     */
    public $title = "PHP грешки";
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin, debug';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, log_Wrapper, plg_Created';
    
    
    /**
     * 
     */
    public $listItemsPerPage = 20;
    
    
    /**
     * 
     */
    public $listFields = 'type, err, time, createdOn=Дата';
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('err', 'text', 'caption=Грешка');
        $this->FLD('time', 'datetime', 'caption=Време');
        $this->FLD('type', 'varchar', 'caption=Тип');
        $this->FLD('hash', 'varchar(32)', 'caption=Хеш на грешката');
        
        $this->setDbUnique('hash');
    }
    
    
    /**
     * Извлича грешките от "error_log" по cron
     */
    function cron_getErr()
    {
        // Пътя до файла с грешки
        $errLogPath = get_cfg_var("error_log");
        
        if (!$errLogPath) return;
        
        if (!is_file($errLogPath)) return;
        
        $content = file_get_contents($errLogPath);
        
        if (!$content) return;
        
        // Вземаме само последните n на брой
        $contentArr = explode("\n", $content);
        
        $contentArr = array_reverse($contentArr);
        
        $i = 0;
        
        foreach ($contentArr as $errStr) {
            $errStr = trim($errStr);
            if (!strlen($errStr)) continue;
            
            // Максимумалния лимит, който може да се извлече
            // Да не претовари сървъра, когато се пуска за първи път или след дълго време
            if ($i >= self::$maxLimit) break;
            
            $i++;
            
            $hash = self::getErrStrHash($errStr);
            
            // Ако грешката е записана, прекъсваме цикъла
            // Достигнали сме до вече записани грешки
            if (self::fetch("#hash = '{$hash}'")) break;
            
            // Парсираме и записваме грешката
            $errArr = self::parseErr($errStr);
            
            $rec = new stdClass();
            $rec->err = $errArr['err'];
            $rec->time = $errArr['time'];
            $rec->type = $errArr['type'];
            $rec->hash = $hash;
            self::save($rec);
            
            $report = FALSE;
            
            // Проверяваме дали трябва да се репортува грешката
            if (self::$reportTypeArr) {
                foreach (self::$reportTypeArr as $reportType) {
                    if (stripos($errArr['type'], $reportType) !== FALSE) {
                        $report = TRUE;
                        break;
                    }
                }
            } else {
                $report = TRUE;
            }
            
            // Рендираме грешката, без показване, което ако са зададени константи,
            // ще запиши грешката в лог файл и/или ще изпрати грешката до зададения адрес
            if ($report) {
                
                $errStateArr = array('errTitle' => $errArr['type'], 'dump' => array('Грешка' => $errArr['err'], 'Време' => $errArr['time']));
                
                $oldGet = $_GET['Ctr'];
                $oldAct = $_GET['Act'];
                
                // Променяме контолера и екшъна до сочат към съответна грешка и клас
                $_GET['Ctr'] = get_called_class();
                $errHash = self::getErrStrHash($errArr['type'] . '|' . $errArr['err']);
                $_GET['Act'] = substr($errHash, 0, 6);
                
                core_Debug::renderErrorState($errStateArr, TRUE);
                
                $_GET['Ctr'] = $oldGet;
                $_GET['Act'] = $oldAct;
            }
        }
    }
    
    
    /**
     * Почистване на старите записи
     */
    function cron_DeleteOldRecords()
    {
        $now = dt::verbal2mysql();
        $deletedRecs = $this->delete("ADDDATE(#createdOn, " . self::$removeAfterDays . ") < '" . $now . "'");
        
        return "Изтрити записи: " . $deletedRecs;
    }
    
    
    /**
     * Парсира стринга и взма времето, типа и съобщението за грешка
     * 
     * @param string $errStr
     * 
     * @return array
     * 'time'
     * 'type'
     * 'err'
     */
    protected static function parseErr($errStr)
    {
        $resArr = array();
        $timeEdnPos = 0;
        if (strpos($errStr, '[') === 0) {
            $timeEdnPos = strpos($errStr, '] ');
            $resArr['time'] = substr($errStr, 1, $timeEdnPos-1);
            $resArr['time'] = dt::verbal2mysql($resArr['time']);
        }
        
        $errEndPos = strpos($errStr, ': ');
        
        $resArr['type'] = substr($errStr, $timeEdnPos+2, $errEndPos-$timeEdnPos-2);
        $resArr['err'] = substr($errStr, $errEndPos+2);
        
        return $resArr;
    }
    
    
    /**
     * Връща хешна на грешката
     * 
     * @param string $str
     * 
     * @return string
     */
    protected static function getErrStrHash($str)
    {
        
        return md5($str);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('createdOn', 'DESC');
    }
    
    
    /**
     * Начално установяване на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Нагласяване на Крон        
        $rec = new stdClass();
        $rec->systemId = 'getErr';
        $rec->description = 'Извлича грешките от "error_log"';
        $rec->controller = $mvc->className;
        $rec->action = 'getErr';
        $rec->period = 5;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $res .= core_Cron::addOnce($rec);
        
        // Нагласяване на Крон        
        $rec = new stdClass();
        $rec->systemId = 'DeleteExpired';
        $rec->description = 'Изтриване на старите PHP грешки';
        $rec->controller = "{$mvc->className}";
        $rec->action = 'DeleteOldRecords';
        $rec->period = 24 * 60;
        $rec->offset = rand(1320, 1439); // ot 22h до 24h
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
    }
}
