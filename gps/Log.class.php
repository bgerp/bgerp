<?php 


/**
 * Дефинира име на папка в която ще се ползва за временна директория
 */
defIfNot('GPS_LOG_TEMP_DIR', EF_TEMP_PATH . "/gps");

/**
 * IP на хост от който се приемат данни
 */
defIfNot('GPS_DATA_SENDER', '127.0.0.1');


/**
 * Съхранява хронологични данни от gps тракери
 *
 *
 * @category  vendors
 * @package   gps
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gps_Log extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'gps';
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_Created';    
    
    public $listFields = 'trackerId, text, remoteIp';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('trackerId', 'varchar(12)', 'caption=Тракер Id');
        $this->FLD('data', 'blob', 'caption=gps данни');
        $this->FNC('text', 'html', 'caption=gps данни');
        $this->FLD('remoteIp', 'ip', 'caption=Tракер IP');
    }
    
    
    public function on_CalcText($mvc, $rec)
    {
        $data = self::parseGPSData($rec->data);
        
        $dateTimeGPS = substr($data['date'],4,2) . "-" . substr($data['date'],2,2) . "-" . substr($data['date'],0,2)
                . " " . substr($data['time'],0,2) . ":" . substr($data['time'],2,2) . ":" . substr($data['time'],4,2); 
                
        $rec->text = "Дата: " . $dateTimeGPS . "<br>";
        $rec->text .= "Статус: " . (($data['status'] == 'A')?'Валиден':'Невалиден'). "<br>";
        $rec->text .= "Ширина: " . $data['latitude'] . "<br>";
        $rec->text .= "Дължина: " . $data['longitude'] . "<br>";
        $rec->text .= "Скорост: " . $data['speed'] . " км/ч<br>";
        $rec->text .= "Посока: " . $data['heading'] . "<br>";
    }
    
    
    /**
     * Входна точка за взимане на данни по http заявка
     * Очаква разбити данни от тракера
     */
    public function act_Log()
    {
        if ($_SERVER['REMOTE_ADDR'] != GPS_DATA_SENDER) {
            exit;
        }
        
        $trackerId = Request::get('trackerId', 'varchar');
        $data = Request::get('data', 'varchar');
        $remoteIp = Request::get('remoteIp', 'varchar');
        
        // Махаме порта от IP адреса
        $remoteIp = substr($remoteIp, 0, strpos($remoteIp, ':'));
        $rec->trackerId = $trackerId;
        $rec->data = $data;
        $rec->remoteIp = $remoteIp;
        
        $this->save($rec);
        
        // return "$trackerId <br> $data <br> $remoteIp <br>";
    }
    
    
    /**
     * Връща GPS данните
     *
     * @param string стринг с данните - GPRMC + другите от тракера
     * @return array с елементи от GPRMS
     */
    private function parseGPSData($data)
    {
        // Взимаме GPRMC sentence 
        $res['dataGPS'] = substr($data, 0, strpos($data, '*')); // до този знак е изречението, 2 знака след това - CRC-то
        $res['CRC'] = substr($data, strpos($data, '*'), 3);
        $arrData = explode(',', $res['dataGPS']);
        $res['time'] = substr($arrData[0], 0, 6); // хилядните от времето не ни интересуват засега
        $res['status'] = $arrData[1]; // A=valid, V=invalid 
        $res['latitude'] = $arrData[2] . $arrData[3];
        $res['longitude'] = $arrData[4] . $arrData[5];
        $res['speed'] = $arrData[6];
        $res['heading'] = $arrData[7];
        $res['date'] = $arrData[8];
        
        
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
    private function getCRC($dataGPS)
    {
        $crc = 0;
        $len = strlen($dataGPS);
        for ($i=0; $i<$len; $i++) {
            $crc ^= ord($dataGPS[$i]);
            //echo ("<li>$dataGPS[$i]  --  " . $crc . "  ------ " . dechex($crc));
        }
        
        return dechex($crc);
    }
}
