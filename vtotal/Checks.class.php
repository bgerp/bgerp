<?php

//require_once __DIR__ . "/Api.class.php";

class vtotal_Checks extends core_Master
{
    /**
     * @var Url нужно на VirusTotal за Api-то
     */
    private static $VTScanUrl = "https://www.virustotal.com/vtapi/v2/file/report";

    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created";



    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('firstCheck', 'datetime', 'caption=Последно от вирус тотал'); //
        $this->FLD('lastCheck', 'datetime', 'caption=Последно проверяване от системата');
        $this->FLD('filemanDataId', 'key(mvc=fileman_Files,select=id)', 'caption=Файл');
        $this->FLD('md5', 'varchar', 'caption=Хеш на съответния файл');
        $this->FLD('timesScanеd', 'int', 'caption=Пъти сканиран този файл, notNull ,value=0');
        $this->setDbUnique('filemanDataId');
    }


    /**
     * @param $VTResult обект от тип stdClass VirusTotalRespone
     * @return type_Percent колко опасен е съответния файл
     */
    public function getDangerRate($VTResult)
    {
        return $VTResult->positives/$VTResult->total;
    }


    /**
     * @param $md5Hash Хеш за проверка на файл през VirusTotal MD5
     * @return mixed
     * При неуспешно повикване връща int respone_code
     * При успешно повикване връща stdClass Обект от VirusTotal отговор
     */
    public static function VTGetReport($md5Hash)
    {
        $post = array(
            "resource" => $md5Hash,
            "apikey" => vtotal_Setup::get('VIRUSTOTAL_API_KEY'),
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
     * Функция по крон, която проверява по 4 файла взети от fileman_Files и
     * ги поставя в този модел за инспекция
     */
    public function cron_MoveFilesFromFilemanLog()
    {
        $dangerExtensions = array(
            //Executable
            'EXE', 'PIF', 'APPLICATION', 'GADGET',
            'MSI', 'MSP', 'COM', 'SCR', 'HTA', 'CPL',
            'MSC', 'JAR', 'BAT', 'CMD', 'VB', 'VBS',
            'JS', 'JSE', 'WS', 'WSH', 'WSC', 'WSF',
            "PS1", 'PS1XML', 'PS2', 'PS2XML', 'PSC1',
            'PSC2', 'SCF', 'LNK', 'INF',
            //Macro
            'REG', 'DOC', 'XLS', 'PPT', 'DOCM',
            'DOTM', 'XLSM', 'XLTM', 'XLAM',
            'PPTM', 'POTM', 'PPAM', 'PPSM' , 'SLDM'
        );

        $query = fileman_Files::getQuery();
        $query->where("#dangerRate IS NULL");
        $query->orderBy("#createdOn", "DESC");
        $query->limit(4);

        while($rec = $query->fetch()) {
            $extension = pathinfo($rec->name, PATHINFO_EXTENSION);

            if (!in_array(strtoupper($extension), $dangerExtensions)) {
                $rec->dangerRate = 0;
                fileman_Files::save($rec, 'dangerRate');
            }
            elseif (!$this->fetch("#filemanDataId = {$rec->dataId}")) {
                $vtotalFilemanDataObject = fileman_Data::fetch($rec->dataId);
                $checkFile = (object)array('filemanDataId' => $rec->dataId,
                    'firstCheck' => NULL, 'lastCheck' => NULL, 'md5'=> $vtotalFilemanDataObject->md5, 'timesScand' => 0);
                $this->save($checkFile);
            }
        }
    }


    /**
     * Функция по крон, която врема запосите от този модел и
     * прави определени функции според техния вид
     */
    public function cron_VTCheck()
    {
        $now = dt::now();
        //#firstCheck IS NULL OR #lastCheck IS NULL OR
        $query = self::getQuery();
        //Тук има константа
        $query->where("#lastCheck IS NULL OR DATEDIFF( '{$now}', #lastCheck) > 10");
        $query->orderBy("#createdOn", "DESC");
        $query->limit(4);


        $array = [];
        while($rec = $query->fetch())
        {

            $result = self::VTGetReport($rec->md5);
            array_push($array, $result);


            if($rec->timesScanеd >= 2)
            {
                $fRec = fileman_Files::fetch(array("#dataId = {$rec->filemanDataId}"));
                $fRec->dangerRate = -1;
                fileman_Files::save($fRec);
            }
            else{
                if($result == -1 || $result == -3 || $result->response_code == 0)
                {
                    $rec->timesScanеd = $rec->timesScanеd + 1;
                    $rec->lastCheck = $now;
                    $this->save($rec);
                }
                elseif ($result->response_code == 1)
                {
                    $dangerRate = $this->getDangerRate($result);
                    $rec->firstCheck = $result->scan_date;
                    $rec->lastCheck = $now;
                    $this->save($rec, 'firstCheck, lastCheck');
                    $fRec = fileman_Files::fetch(array("#dataId = {$rec->filemanDataId}"));
                    $fRec->dangerRate = $dangerRate;
                    fileman_Files::save($fRec);
                }
            }
        }

    }


    public function act_CheckFiles()
    {
        return $this->cron_MoveFilesFromFilemanLog();
    }

    public function act_VTCheck()
    {
        return $this->cron_VTCheck();
    }
}