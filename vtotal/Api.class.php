<?php


require_once __DIR__ . "/VirusTotalApiV2.php";

class vtotal_Api extends core_Master
{

    public $loadList = "vtotal_Setup";

    private static $VTScanUrl = "https://www.virustotal.com/vtapi/v2/file/report";

    //public static $VTApiKey = "7bd9b8cf8075a43624ea21db550e3caf04d201a99b8ddc634d47995ea5822148";

    /**
     * @param   string $md5Hash     MD5 на файла идващ от cronJob
     * @return  stdClass            Връща обект от много и различна информаниция за сигурността на файла за файла
     */
    public static function VTGetReport($md5Hash)
    {
        $post = array(
            "resource" => $md5Hash,
//            "apikey" => vtotal_Setup::get('VIRUSTOTAL_API_KEY'),
        //Api key here
            "apikey" => "",
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$VTScanUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $responce = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == '429') {
            return array(
                'response_code' => -3
            );
        } elseif ($httpCode == '403') {
            return array(
                'response_code' => -1
            );
        } else
            return json_decode($responce);

    }

    /**
     * @param stdClass $VTResponse
     * @return float|int
     */
    public static function getDangerRateVTResponse($VTResponse)
    {

        return  $VTResponse->positives/$VTResponse->total * 100;

    }

    public static function getLastCheckVTResponse($VTResponse)
    {
        return $VTResponse->scan_date;
    }

    /**
     * @param stdClass $VTResponse Virus Total Api Response
     * @return int Отговор
     * -1 ако файлът НЕ е преминал проверка
     *  1 ако файлът е преминал проверка
     */
    public function checkIfFileIsUseable($VTResponse)
    {


        //Вземането на датата от VT отговора, като Datetime обект
        $lastScanDate = new DateTime($VTResponse->scan_date);

        //Вземането на днешната дата, като Datetime обект
        $today = new DateTime(dt::today());

        //Получаването на интервал
        $interval = $lastScanDate->diff($today);

        return $interval < 10 ? 1 : -1;
    }
}