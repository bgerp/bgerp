<?php 


/**
 * Дефинира име на папка в която ще се ползва за временна директория
 */
defIfNot('GPS_LOG_TEMP_DIR', EF_TEMP_PATH . "/gps");


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
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('trackerId', 'varchar(12)', 'caption=Тракер Id');
        $this->FLD('data', 'blob', 'caption=gps данни');
        $this->FLD('gpsTime', 'datetime', 'caption=gps време');
        $this->FLD('remoteIp', 'ip', 'caption=Tракер IP');
    }
        
    /**
     * Входна точка за взимане на данни по http заявка
     * Очаква разбити данни от тракера
     */
    public function act_Log()
    {
        $trackerId = Request::get('trackerId', 'varchar');
        $data = Request::get('data', 'varchar');
        $remoteIp = Request::get('remoteIp', 'varchar');
        
        // Махаме порта от IP адреса
        $remoteIp = substr($remoteIp, 0, strpos($remoteIp, ':'));
        $rec->trackerId = $trackerId;
        $rec->data = $data;
        $rec->remoteIp = $remoteIp;
        $rec->gpsTime = date("Y-m-d H:i:s");
        
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
     * Връща Tracker данните
     * 
     * @param string GPRMC стринг
     * @return boolean - валидна ли е CRC сумата
     */
    private function checkCRC($dataGPS)
    {
        
        return $res;
    }
}
