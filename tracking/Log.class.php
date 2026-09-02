<?php 

/**
 * Съхранява хронологични данни от тракери
 *
 *
 * @category  bgerp
 * @package   tracking
 *
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tracking_Log extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Лог';
    
    
    /**
     * Права
     */
    public $canList = 'tracking, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_Created, plg_RowTools2, tracking_Wrapper';
    
    
    /**
     * Полета за показване
     *
     * var string|array
     */
    public $listFields = 'id,vehicleId, driverId, location, fixTime, remoteIp, createdOn';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('vehicleId', 'key(mvc=tracking_Vehicles, select=number, allowEmpty=true)', 'caption=Автомобил, silent');
        $this->FLD('driverId', 'key(mvc=crm_Persons, select=name, allowEmpty=true)', 'caption=Водач');
        $this->FLD('location', 'location_Type', 'caption=Локация, tdClass=large-field');
        
        $this->FLD('data', 'blob', 'caption=Данни');
        $this->FLD('fixTime', 'datetime()', 'caption=Време на засичане');
        $this->FNC('text', 'html', 'caption=Данни');
        $this->FLD('remoteIp', 'ip', 'caption=Тракер IP');
        
        $this->setDbIndex('vehicleId');
        // $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * Добавя форма за търсене
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FNC('dateFrom', 'date', 'caption=От,input');
        $data->listFilter->FNC('dateTo', 'date', 'caption=Сега,input');
        $data->listFilter->setField('vehicleId', 'placeholderType=all');
        $data->listFilter->setField('driverId', 'placeholderType=all');
        
        $data->listFilter->showFields = 'vehicleId,driverId,dateFrom,dateTo';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->view = 'horizontal';
        
        $rec = $data->listFilter->input($data->listFilter->showFields, true);
        
        if ($rec) {
            $vehicleId = $rec->vehicleId ?? null;
            $driverId = $rec->driverId ?? null;
            $dateFrom = $rec->dateFrom ?? null;
            $dateTo = $rec->dateTo ?? null;

            if ($vehicleId) {
                $data->query->where("#vehicleId = {$vehicleId}");
            }
            
            if ($driverId) {
                $data->query->where("#driverId = '{$driverId}'");
            }
            
            if ($dateFrom) {
                if (empty($dateTo)) {
                    $dateTo = date('Y-m-d');
                }
                
                // Понеже fixTime съдържа времева част - кастваме до дата
                $data->query->where("CAST(#fixTime AS DATE) BETWEEN '{$dateFrom}' AND '{$dateTo}'");
            }
        }
        
        $data->query->orderBy('#fixTime', 'DESC');
    }
    
    
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $data = self::parseTrackingData($rec->data);
        $l = cls::get('location_Type');
      //  $row->location = $l->toVerbal(self::DMSToDD($data['latitude']) . ',' . self::DMSToDD($data['longitude']));
        
        $place = location_Places::toVerbal(self::DMSToDD($data['latitude']) . ',' . self::DMSToDD($data['longitude']));
        
        $row->location = '';
        $row->location .= '<a href="https://maps.google.com/?q='
            . self::DMSToDD($data['latitude'])
            . ',' . self::DMSToDD($data['longitude']) . '" target=_new>' . $place. '</a><br>';
        
    }
    
    protected function on_CalcText($mvc, $rec)
    {
        $data = self::parseTrackingData($rec->data);
        
        $rec->text = 'Дата: ' . $data['fixTime'] . '<br>';
        $rec->text .= 'Статус: ' . (($data['status'] == 'A')?'Валиден':'Невалиден'). '<br>';
        $rec->text .= 'Ширина DD: ' . self::DMSToDD($data['latitude']) . '<br>';
        $rec->text .= 'Дължина DD: ' . self::DMSToDD($data['longitude']) . '<br>';
        $rec->text .= 'Скорост: ' . $data['speed'] . ' км/ч<br>';
        $rec->text .= 'Посока: ' . $data['heading'] . '<br>';
        $rec->text .= 'Карта: <a href="https://maps.google.com/?q='
            . self::DMSToDD($data['latitude'])
            . ',' . self::DMSToDD($data['longitude']) . '" target=_new>виж</a><br>';
    }
    
    
    /**
     * Входна точка за взимане на данни по http заявка
     * Очаква разбити данни от тракера
     */
    public function act_Log()
    {
        $conf = core_Packs::getConfig('tracking');
        
        // Ако получаваме данни от неоторизирано IP ги игнорираме
        if (($_SERVER['REMOTE_ADDR'] ?? null) != $conf->DATA_SENDER) {
            // file_put_contents('tracking.log', "\n неоторизирано IP. Данните идват от: {$_SERVER['REMOTE_ADDR']} а ги очакваме от: {$conf->DATA_SENDER} ". date("Y-m-d H:i:s") . "\n", FILE_APPEND);
            shutdown();
        }
        
        // file_put_contents('tracking.log', "\n accepted", FILE_APPEND);
        
        $trackerId = Request::get('trackerId', 'varchar');
        $trackerData = Request::get('data', 'varchar');
        $remoteIp = Request::get('remoteIp', 'varchar');
        
        // Махаме порта от IP адреса
        $portPos = strpos($remoteIp, ':');
        if ($portPos !== false) {
            $remoteIp = substr($remoteIp, 0, $portPos);
        }
        
        // Взимаме данните за колата, на която е закачен тракера
        $recVehicle = tracking_Vehicles::getRecByTrackerId($trackerId);
        if (false === $recVehicle) {
            /* @TODO Логваме съобщение, че нямаме въведена кола за този тракер */
            //file_put_contents("tracking.log", "\n Липсваща кола с тракер No: {$trackerId} ". date("Y-m-d H:i:s") . "\n", FILE_APPEND);
            
            shutdown();
        }
        
        $trackerDataArr = self::parseTrackingData($trackerData);
        
        $rec = new stdClass();
        
        // Проверяваме дали скоростта е нула
        if (((float) $trackerDataArr['speed'] - 0.01) < 0) {
            // Проверяваме последния запис от този тракер, дали е с нулева скорост. Ако - да - не го записваме
            $query = $this->getQuery();
            $query->show('data');
            $query->where(array("#vehicleId = '[#1#]'", $recVehicle->id));
            $query->orderBy('#fixTime', 'DESC');
            $query->limit(1);
            $lastRec = $query->fetch();
            if ($lastRec) {
                $recData = self::parseTrackingData($lastRec->data);
            }
            if (isset($recData) && (((float) $recData['speed'] - 0.01) < 0)) {
                // file_put_contents('tracking.log', "\n NEZAPISAN - sprial". date("Y-m-d H:i:s") . "\n", FILE_APPEND);
                
                // Не го записваме
                shutdown();
            }
        }
        
        // Записваме в базата само ако записа е валиден
        $data = self::parseTrackingData($trackerData);
        if ($data['status'] != 'A' || empty($trackerDataArr['fixTime'])) {
            shutdown();
        }
        
        $rec->vehicleId = $recVehicle->id;
        $rec->driverId = $recVehicle->personId ?? null;
        $rec->data = $trackerData;
        $rec->fixTime = self::GMT2Local($trackerDataArr['fixTime']);
        $rec->remoteIp = $remoteIp;
        
        $this->save($rec);
    }
    
    
    /**
     * Функция по крон, която се стартира ежеседмично и изтрива старите записи
     *
     */
    public function cron_DeleteOldRecords()
    {
        $conf = core_Packs::getConfig('tracking');
        
        $date = dt::addDays(-$conf->DAYS_TO_KEEP);
        $info = null;
        
        if ($numRows = self::delete("#createdOn < '{$date}'")) {
            $this->logWrite('Изтрити изтекли записи за тракери');
            
            $info = "Изтрити са {$numRows} изтекли записи за тракери";
            $this->logInfo($info);
        }
        
        return $info;
    }
    
    
    /**
     * Връща Tracking данните
     *
     * @param string стринг с данните - GPRMC + другите от тракера
     *
     * @return array с елементи от GPRMS
     */
    public static function parseTrackingData($data)
    {
        $data = (string) $data;
        $res = array(
            'dataTracking' => '',
            'CRC' => '',
            'time' => '',
            'status' => '',
            'latitude' => '',
            'longitude' => '',
            'speed' => 0,
            'heading' => '',
            'date' => '',
            'fixTime' => null,
        );

        // Взимаме GPRMC sentence
        $crcPos = strpos($data, '*');
        $res['dataTracking'] = ($crcPos === false) ? $data : substr($data, 0, $crcPos);
        $res['CRC'] = ($crcPos === false) ? '' : substr($data, $crcPos, 3);
        $arrData = explode(',', $res['dataTracking']);
        $rawTime = $arrData[0] ?? '';
        $dotPos = strpos($rawTime, '.');
        $res['time'] = ($dotPos === false) ? substr($rawTime, -6) : substr($rawTime, max(0, $dotPos - 6), 6);
        $res['status'] = $arrData[1] ?? ''; // A=valid, V=invalid
        $res['latitude'] = ($arrData[2] ?? '') . ($arrData[3] ?? '');
        $res['longitude'] = ($arrData[4] ?? '') . ($arrData[5] ?? '');
        $res['speed'] = $arrData[6] ?? 0;
        $res['heading'] = $arrData[7] ?? '';
        $res['date'] = $arrData[8] ?? '';
        
        // Ако имаме дата и час - конструираме времето на фиксиране в mysql формат
        if (!empty($res['date']) && !empty($res['time'])) {
            $res['fixTime'] = '20' . substr($res['date'], 4, 2) . '-' . substr($res['date'], 2, 2) . '-' . substr($res['date'], 0, 2)
                . ' ' . substr($res['time'], 0, 2) . ':' . substr($res['time'], 2, 2) . ':' . substr($res['time'], 4, 2);
        }
        
        return $res;
    }
    
    
    /**
     * Превръща от DMS (degrees, minutes, secondes) към DD (decimal degrees)
     *
     * @param string  - стринг с данните - в стил DMS ()
     *
     * @return float - decimal degrees
     */
    public static function DMSToDD($data)
    {
        $data = (string) $data;
        if ($data === '') {
            return 0;
        }

        // Махаме последния символ
        $sign = substr($data, -1);
        $data = substr($data, 0, -1);
        $dotPos = strpos($data, '.');
        if ($dotPos === false || $dotPos < 2) {
            return 0;
        }
        $min = substr($data, $dotPos - 2);
        $deg = substr($data, 0, $dotPos - 2);
        $res = (float) $deg + ((float) $min / 60);
        if ($sign == 'N' || $sign == 'E') {
            // $res - непроменено
        } else {
            $res *= -1;
        }
        
        return $res;
    }
    
    
    /**
     * Превръща от GMT Mysql време в локано
     *
     * @param string  - datetime - в UTC
     *
     * @return string - DateTime локално време в Mysql формат
     */
    private static function GMT2Local($date)
    {
        return date('Y-m-d H:i:s', strtotime($date . ' UTC'));
    }
    
    
    /**
     * Изчислява CRC
     *
     * @param string GPRMC стринг
     *
     * @return string - CRC сумата
     */
    private function getCRC($dataTracking)
    {
        $crc = 0;
        $len = strlen($dataTracking);
        for ($i = 0; $i < $len; $i++) {
            $crc ^= ord($dataTracking[$i]);
        }
        
        return dechex($crc);
    }
}
