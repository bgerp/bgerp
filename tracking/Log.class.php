<?php 




/**
 * Съхранява хронологични данни от тракери
 *
 *
 * @category  vendors
 * @package   tracking
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tracking_Log extends core_Manager
{
    
    /**
     * Заглавие
     */
    public $title = 'Точки';
    
    /**
     * Заглавие
     */
    public $canList = 'tracking, admin, ceo';
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_Created, tracking_Wrapper';
    
    /**
     * Полета за показване
     *
     * var string|array
     */
    public $listFields = 'trackerId, text, remoteIp, createdOn';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('trackerId', 'varchar(12)', 'caption=Тракер Id');
        $this->FLD('data', 'blob', 'caption=tracking данни');
        $this->FNC('text', 'html', 'caption=tracking данни');
        $this->FLD('remoteIp', 'ip', 'caption=Tракер IP');
    }
    
    
    public function on_CalcText($mvc, $rec)
    {
        $data = self::parseTrackingData($rec->data);

        $dateTimeTracking = "20" . substr($data['date'],4,2) . "-" . substr($data['date'],2,2) . "-" . substr($data['date'],0,2)
                . " " . substr($data['time'],0,2) . ":" . substr($data['time'],2,2) . ":" . substr($data['time'],4,2); 
                
        $rec->text  = "Дата: " . $dateTimeTracking . "<br>";
        $rec->text .= "Статус: " . (($data['status'] == 'A')?'Валиден':'Невалиден'). "<br>";
        $rec->text .= "Ширина: " . $data['latitude'] . "<br>";
        $rec->text .= "Дължина: " . $data['longitude'] . "<br>";
        $rec->text .= "Ширина DD: " . self::DMSToDD($data['latitude']) . "<br>";
        $rec->text .= "Дължина DD: " . self::DMSToDD($data['longitude']) . "<br>";
        $rec->text .= "Скорост: " . $data['speed'] . " км/ч<br>";
        $rec->text .= "Посока: " . $data['heading'] . "<br>";
        $rec->text .= "Карта: <a href=\"https://maps.google.com/?q="
            . self::DMSToDD($data['latitude'])
            . "," . self::DMSToDD($data['longitude']) . "\" target=_new>виж</a><br>";
    }
    
    
    /**
     * Входна точка за взимане на данни по http заявка
     * Очаква разбити данни от тракера
     */
    public function act_Log()
    {
        $conf = core_Packs::getConfig('tracking');
        if ($_SERVER['REMOTE_ADDR'] != $conf->DATA_SENDER) {

            exit;
        }
        file_put_contents('tracking_log.log', "\n accepted", FILE_APPEND);
        
        $trackerId = Request::get('trackerId', 'varchar');
        $data = Request::get('data', 'varchar');
        $remoteIp = Request::get('remoteIp', 'varchar');
        
        // Проверяваме дали скоростта е нула
        $dataArr = self::parseTrackingData($data);
        if (($dataArr['speed']-0.01) < 0) {
            // Проверяваме последния запис от този тракер, дали е с нулева скорост. Ако - да - не го записваме
            $query = $this->getQuery();
            $query->show('data');
            $query->where(array("#trackerId = '[#1#]'", $trackerId));
            $query->orderBy('#createdOn','DESC');
            $query->limit(1);
            $rec = $query->fetch();
            $recData = self::parseTrackingData($rec->data); 
            if (is_array($recData) && ($recData['speed'] -0.01) < 0) {
                file_put_contents('tracking_log.log', "\n NEZAPISAN", FILE_APPEND);
                
                // Не го записваме
                exit;
            }
        }
        
        // Махаме порта от IP адреса
        $remoteIp = substr($remoteIp, 0, strpos($remoteIp, ':'));
        $rec->trackerId = $trackerId;
        $rec->data = $data;
        $rec->remoteIp = $remoteIp;
        
        $this->save($rec);
    }
    
    
    /**
     * Връща Tracking данните
     *
     * @param string стринг с данните - GPRMC + другите от тракера
     * @return array с елементи от GPRMS
     */
    private function parseTrackingData($data)
    {
        // Взимаме GPRMC sentence 
        $res['dataTracking'] = substr($data, 0, strpos($data, '*')); // до този знак е изречението, 2 знака след това - CRC-то
        $res['CRC'] = substr($data, strpos($data, '*'), 3);
        $arrData = explode(',', $res['dataTracking']);
        $res['time'] = substr($arrData[0], strpos($arrData[0], '.')-6, 6); // хилядните от времето не ни интересуват засега
        $res['status'] = $arrData[1]; // A=valid, V=invalid 
        $res['latitude'] = $arrData[2] . $arrData[3];
        $res['longitude'] = $arrData[4] . $arrData[5];
        $res['speed'] = $arrData[6];
        $res['heading'] = $arrData[7];
        $res['date'] = $arrData[8];
        
        
        return $res;
    }

    
    /**
     * Превръща от DMS (degrees, minutes, secondes) към DD (decimal degrees)
     * 
     * @param string  - стринг с данните - в стил DMS ()
     * @return double  - decimal degrees
     */
    private function DMSToDD($data)
    {
        // Махаме последния символ
        $sign = substr($data, -1);
        $data = substr($data, 0, -1);
        $min = substr($data, strpos($data, '.') - 2);
        $deg = substr($data, 0, strpos($data, $min));
        $res = $deg+($min/60);
        if ($sign == 'N' || $sign == 'E') {
            // $res - непроменено
        } else {
            $res *= -1;
        }

        return $res;
    }

    
    /**
     * Връща Tracker данните
     *
     * @param string стринг с данните - GPRMC + другите от тракера
     * @return array с елементи данните от тракера
     */
    private function parseTrackerData($data)
    {
    
        return $res;
    }
    
    
    /**
     * Изчислява CRC
     * 
     * @param string GPRMC стринг
     * @return string - CRC сумата
     */
    private function getCRC($dataTracking)
    {
        $crc = 0;
        $len = strlen($dataTracking);
        for ($i=0; $i<$len; $i++) {
            $crc ^= ord($dataTracking[$i]);
            //echo ("<li>$dataTracking[$i]  --  " . $crc . "  ------ " . dechex($crc));
        }
        
        return dechex($crc);
    }
}
