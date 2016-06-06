<?php

//require_once __DIR__ . "/Api.class.php";

class vtotal_Checks extends core_Master
{

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
        $this->FLD('timesScanеd', 'int', 'caption=Пъти сканиран този файл');
        $this->setDbUnique('filemanDataId');
    }


    public function act_CheckFiles()
    {
        return $this->cron_MoveFilesFromFilemanLog();
    }


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
            'PPTM', 'POTM', 'PPAM', 'PPSM' , 'SLDM', 'ZIP', 'TXT'
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

    public function cron_VTCheck()
    {
        $now = dt::now();
        //#firstCheck IS NULL OR #lastCheck IS NULL OR
        $query = self::getQuery();
        //Тук има константа
        $query->where("#lastCheck IS NULL OR DATEDIFF( '{$now}', #lastCheck) > 10");
        $query->orderBy("#createdOn", "DESC");
        $query->limit(4);


        $testArray = array();
        while($rec = $query->fetch())
        {

            $result = self::VTGetReport($rec->md5);
            array_push($testArray, $result);

            if($result->timesScanеd >= 2)
            {
                fileman_Files::fetch(array("#dataId = {$rec->filemanDataId}"));
            }
            else{
                if($result == -1 || $result == -3 || $result->response_code == 0)
                {
                    $rec->timesScanеd++;
                    $rec->lastCheck = $now;
                    fileman_Files::save($rec);
                }
                elseif ($result->response_code == 1)
                {
                    $rec->firstCheck = $result->scan_date;
                    $rec->lastCheck = $now;
                    fileman_Files::save($rec);
                }
            }
        }
        bp($testArray);

    }

    private static $VTScanUrl = "https://www.virustotal.com/vtapi/v2/file/report";

    public static function VTGetReport($md5Hash)
    {
        $post = array(
            "resource" => $md5Hash,
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
}