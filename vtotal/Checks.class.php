<?php


class vtotal_Checks extends core_Master
{


    /**
     * Заглавие
     */
    public $title = "Проверки във VirusTotal";


    /**
     * Кой има право да чете?
     */
    var $canRead = 'debug, admin';


    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    var $canList = 'debug, admin';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'debug, admin';


    /**
     * Кой може да го види?
     */
    var $canView = 'debug, admin';


    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'debug, admin';


    /**
     * @var Url нужно на VirusTotal за Api-то
     */
    private static $VTScanUrl = "https://www.virustotal.com/vtapi/v2/file/report";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting";


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('firstCheck', 'datetime(format=smartTime)', 'caption=Последно от вирус тотал');
        $this->FLD('lastCheck', 'datetime(format=smartTime)', 'caption=Последно проверяване от системата');
        $this->FLD('filemanDataId', 'key(mvc=fileman_Files,select=id)', 'caption=Файл');
        $this->FLD('md5', 'varchar', 'caption=Хеш на съответния файл, silent');
        $this->FLD('timesScanned', 'int', 'caption=Пъти сканиран този файл, notNull, value=0, oldFieldName=timesScaned');
        $this->FLD('rateByVT', 'varchar(8)', 'caption=Опастност');
        $this->setDbUnique('filemanDataId');
    }
    
    
    /**
     * 
     */
    public function act_manualCheck()
    {
        $this->requireRightFor('admin, debug');

        $md5 = Request::get('md5');
        $query = fileman_Files::getQuery();
        $query->where(array("#fileHnd = '[#1#]'", Request::get('fileHnd')));
        $query->limit(1);
        $rec = $query->fetch();
        $this->putNewFileForCheck($rec, $md5);

        return new Redirect( array('vtotal_Checks', null , 'md5' => $md5));
    }
    

    /**
     * 
     * 
     * @param $VTResult обект от тип stdClass VirusTotalRespone
     * 
     * @return type_Percent колко опасен е съответния файл
     */
    public function getDangerRate($VTResult)
    {
        return $VTResult->positives/$VTResult->total;
    }


    /**
     * Проверява дали Avast е инсталиран на работната машината
     * 
     * @return bool
     */
    public static function isAvastInstalled()
    {
        $inst = cls::get('vtotal_Setup');
        $isInstalled = $inst->checkConfig();
        
        if (is_null($isInstalled)) return TRUE;
        
        return FALSE;
    }


    /**
     * 
     * 
     * @param $path Път до файла, който трябва да се сканира
     * 
     * @return int  Краен резултар дали е опасен.
     * 1 -> да опасен е
     * 0 -> не е опасе
     */
    public function AvastSingleFileScan($path)
    {
        expect(is_file($path));
        $path = escapeshellarg($path);
        $command = escapeshellcmd(vtotal_Setup::get('AVAST_COMMAND') . " " . $path);
        $output = exec($command, $output, $code);

        preg_match("/(?'file'.+?)\[(?'result'.+?)\]/", $output , $matches);
        return !empty($matches[0]) ? 1 : 0;
    }


    /**
     * 
     * 
     * @param $md5Hash Хеш за проверка на файл през VirusTotal MD5
     * 
     * @return mixed
     * При неуспешно повикване връща int respone_code
     * При успешно повикване връща stdClass Обект от VirusTotal отговор
     */
    public static function VTGetReport($md5Hash)
    {
        $post = array(
            "resource" => $md5Hash,
            "apikey" => vtotal_Setup::get("API_KEY"),
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
            return (object) array(
                'response_code' => -3
            );
        } elseif ($httpCode == '403') {
            return (object) array(
                'response_code' => -1
            );
        } else
            return json_decode($responce);
    }
    
    
    /**
     * 
     * 
     * @param stdObjec $fRec
     * @param string $md5
     * @param string|null $counter
     */
    public function putNewFileForCheck(&$fRec, $md5, &$counter = null)
    {
        $checkFile = (object)array('filemanDataId' => $fRec->dataId,
            'firstCheck' => NULL, 'lastCheck' => NULL, 'md5'=> $md5, 'timesScanned' => 0);
        $result = $this->save($checkFile, NULL, "IGNORE");
        if(!$result) {
            $cRec = $this->fetch("#filemanDataId = {$fRec->dataId}");
            $fRec->dangerRate = $this->getDangerRateByRateStr($cRec->rateByVT);
            fileman_Files::save($fRec, "dangerRate");
        } else {
            $counter++;
        }
    }
    
    
    /**
     * 
     * 
     * @return array
     */
    protected static function getDangerExtension()
    {
        static $extensionsArr = array();
        
        if (empty($extensionsArr)) {
            $extensionStr = mb_strtolower(vtotal_Setup::get('DANGER_EXTENSIONS'));
            $extensionsArr = arr::make($extensionStr, TRUE);
        }
        
        return $extensionsArr;
    }
    

    /**
     * Функция по крон, която проверява по 4 файла взети от fileman_Files и
     * ги поставя в този модел за инспекция
     */
    public function cron_MoveFilesFromFilemanLog()
    {
        $dangerExtensionsArr = $this->getDangerExtension();
        
        $archiveExtensions = array(
          'ZIP', 'RAR', 'GZIP', '7Z', 'GZ', 'ISO'
        );

        $query = fileman_Files::getQuery();
        $query->where("#dangerRate IS NULL");
        $query->orderBy("#createdOn", "DESC");
        $query->limit(300);
        
        $counter = 0;

        while($rec = $query->fetch()) {
            if($counter == vtotal_Setup::get("NUMBER_OF_ITEMS_TO_SCAN_BY_VIRUSTOTAL")) break;
            
            if (!$rec->dataId) {
                $rec->dangerRate = 0;
                fileman_Files::save($rec, "dangerRate");
                continue;
            }
            
            $extension = pathinfo($rec->name, PATHINFO_EXTENSION);

            if(in_array(strtoupper($extension), $archiveExtensions)) {
                $fileHnd = $rec->fileHnd;
                $fRec = fileman_Files::fetchByFh($fileHnd);

                // throw fileman_Exception - ако размера е над допустимия за обработка,
                // трябва да го прихванеш
                try{
                    $archivInst = fileman_webdrv_Archive::getArchiveInst($fRec);
                }catch(fileman_Exception $e){
                    // Проверка във VT
                    $vtotalFilemanDataObject = fileman_Data::fetch($rec->dataId);
                    $this->putNewFileForCheck($rec, $vtotalFilemanDataObject->md5, $counter);
                    continue;
                }
                
                try {
                    $entriesArr = $archivInst->getEntries();
                } catch (ErrorException $e) {
                    self::logWarning("Грешка при обработка на архив - {$fRec->dataId}: " . $e->getMessage());
                    // Проверка във VT
                    $vtotalFilemanDataObject = fileman_Data::fetch($rec->dataId);
                    $this->putNewFileForCheck($rec, $vtotalFilemanDataObject->md5, $counter);
                    continue;
                }
				
                $archiveHaveExt = FALSE;
                
                foreach ($entriesArr as $key => $entry) {
                    $size = $entry->getSize();

                    if (!$size) continue;

                    // Гледаме размера след разархивиране да не е много голям
                    // Защита от "бомби" - от препълване на сървъра
                    if ($size > ARCHIVE_MAX_FILE_SIZE_AFTER_EXTRACT) continue;

                    $path = $entry->getPath();

                    $ext = pathinfo($path, PATHINFO_EXTENSION);

                    if (!$ext) continue;

                    // Проверка на разширението дали е от сканируемите
                    if (!$dangerExtensionsArr[mb_strtolower($ext)]) continue;
                    
                    $archiveHaveExt = TRUE;
                    
                    // След като открием файла който ще пратим към VT
                    try {
                        $extractedPath = $archivInst->extractEntry($path);
                    } catch (ErrorException $e) {
                        $archiveHaveExt = FALSE;
                        continue;
                    }

                    if (!is_file($extractedPath)) {
                        $archiveHaveExt = FALSE;
                        continue;
                    }
                    
                    $md5 = @md5_file($extractedPath);
                    
                    if (!$md5) {
                        $archiveHaveExt = FALSE;
                        continue;
                    } else {
                        // Проверка във VT
                        $this->putNewFileForCheck($rec, $md5, $counter);
                        break;
                    }
                }
                
                if (!$archiveHaveExt) {
                    $vtotalFilemanDataObject = fileman_Data::fetch($rec->dataId);
                    $this->putNewFileForCheck($rec, $vtotalFilemanDataObject->md5, $counter);
                }
                
                // Изтриваме временната директория за съхранение на архива.
                $archivInst->deleteTempPath();
            } elseif (!$dangerExtensionsArr[mb_strtolower($extension)]) {
                
                $cRec = $this->fetch("#filemanDataId = {$rec->dataId}");

                if($cRec) {
                    $rec->dangerRate = $this->getDangerRateByRateStr($cRec->rateByVT);
                    fileman_Files::save($rec, "dangerRate");
                } else {
                    $rec->dangerRate = 0;

                    $fQuery = fileman_Files::getQuery();
                    $fQuery->where("#dataId = {$rec->dataId}");

                    while ($fRec = $fQuery->fetch()) {
                        $extensionFRec = pathinfo($fRec->name, PATHINFO_EXTENSION);
                        if (!isset($fRec->dangerRate) && !$dangerExtensionsArr[mb_strtolower($extensionFRec)]) {
                            $fRec->dangerRate = 0;
                            fileman_Files::save($fRec, "dangerRate");
                        }
                    }
                }
            } elseif ($rec->dangerRate == NULL) {
                $vtotalFilemanDataObject = fileman_Data::fetch($rec->dataId);
                $this->putNewFileForCheck($rec, $vtotalFilemanDataObject->md5, $counter);
            }
        }
    }
    
    
    /**
     * Функция по крон, която врема запосите от този модел и
     * прави определени функции според техния вид
     */
    public function cron_VTCheck()
    {
        $dangerExtensionsArr = $this->getDangerExtension();
        
        $now = dt::now();
        $query = self::getQuery();
        $query->where("#lastCheck IS NULL");
        $query->orWhere("ADDDATE(#lastCheck, INTERVAL " . vtotal_Setup::get("BETWEEN_TIME_SCANS") . " SECOND) < '{$now}'");
        $query->orderBy("#createdOn", "DESC");
        $query->limit(vtotal_Setup::get("NUMBER_OF_ITEMS_TO_SCAN_BY_VIRUSTOTAL"));
        
        $maxScanLimit = vtotal_Setup::get("MAX_SCAN_OF_FILE");
        
        if ($maxScanLimit > 0) {
            $query->where(array("#timesScanned < '[#1#]'", $maxScanLimit));
        }
        
        $isAvastInstalled = $this->isAvastInstalled();
        
        while($rec = $query->fetch())
        {
            $result = self::VTGetReport($rec->md5);
            
            if ($result->response_code == -1) {
                self::logErr('403: Нямате права за достъп, моля прегледайте API ключа за VirusTotal', $rec->id);
                
                break;
            } elseif ($result->response_code == -3) {
                self::logWarning('429: Твърде много заявки към системата на VirusTotal, моля намалете броя на заявките от настройките на пакета или
                увеличете вашият абонамент на един от платените във VirusTotal', $rec->id);
                
                break;
            } elseif($result->response_code == 0) {
                $rec->timesScanned = $rec->timesScanned + 1;

                if ($isAvastInstalled) {
                    $dQuery = fileman_Data::getQuery();
                    $dRec = $dQuery->fetch($rec->filemanDataId);
                    $dangerRate = $this->AvastSingleFileScan($dRec->path);

                    $fsQuery = fileman_Files::getQuery();
                    $fsQuery->where("#dataId = {$rec->filemanDataId}");

                    while($fRec = $fsQuery->fetch())
                    {
                        $fRec->dangerRate = $dangerRate;
                        fileman_Files::save($fRec, 'dangerRate');
                    }
                } else {
                    if ($maxScanLimit && ($rec->timesScanned >= $maxScanLimit)) {
                        $fQuery = fileman_Files::getQuery();
                        $fQuery->where("#dataId = {$rec->filemanDataId}");
                        while($fRec = $fQuery->fetch())
                        {
                            $fRec->dangerRate = -1;
                            fileman_Files::save($fRec, 'dangerRate');
                        }
                    }
                }
                $rec->lastCheck = dt::now();
                $this->save($rec);
            } elseif ($result->response_code == 1) {
                $dangerRate = $this->getDangerRate($result);

                $rec->timesScanned = $rec->timesScanned + 1;
                $rec->firstCheck = $result->scan_date;
                $rec->lastCheck = dt::now();
                $rec->rateByVT = $result->positives . "|" . $result->total;
                $this->save($rec);

                $fsQuery = fileman_Files::getQuery();
                $fsQuery->where("#dataId = {$rec->filemanDataId}");
                
                if ($result->positives) {
                    fileman_Data::logNotice('Файл с вирус', $rec->filemanDataId);
                }
                
                while($fRec = $fsQuery->fetch())
                {
                    $fRec->dangerRate = $dangerRate;
                    fileman_Files::save($fRec, 'dangerRate');
                    
                    if ($result->positives) {
                        fileman_Files::logNotice('Файл с вирус', $fRec->id);
                        
                        $extensionFRec = mb_strtolower(pathinfo($fRec->name, PATHINFO_EXTENSION));
                        
                        if (!$dangerExtensionsArr[$extensionFRec]) {
                            $dangerExtensionsArr[$extensionFRec] = $extensionFRec;
                            
                            core_Packs::setConfig('vtotal', array('VTOTAL_DANGER_EXTENSIONS' => implode(',', $dangerExtensionsArr)));
                        }
                        
                        // Автоматично оттегляне на имейлите с вируси
                        $eQuery = email_Incomings::getQuery();
                        $eQuery->where("#routeBy = 'country'");
                        $eQuery->orWhere("#routeBy = 'toBox'");
                        $eQuery->orWhere("#routeBy = 'fromTo'");
                        $eQuery->where("#state != 'rejected'");
                        $eQuery->where(array("#emlFile = '[#1#]'", $fRec->id));
                        $eQuery->orWhere(array("#htmlFile = '[#1#]'", $fRec->id));
                        $eQuery->orLike('files', '|' . $fRec->id . '|');
                        
                        while ($eRec = $eQuery->fetch()) {
                            
                            if ($eRec->routeBy == 'fromTo' && $eRec->folderId) {
                                $coverClass = doc_Folders::getCover($eRec->folderId);
                                
                                // Ако ще се рутира към пощенска кутия или проект
                                if ($coverClass) {
                                    if ((!$coverClass->instance instanceof email_Inboxes) && (!$coverClass->instance instanceof doc_UnsortedFolders)) continue;
                                }
                            }
                            
                            if (email_Incomings::reject($eRec)) {
                                
                                if ($eRec->threadId) {
                                    $tRec = doc_Threads::fetch($eRec->threadId);
                                }
                                
                                // Ако оттегляме първия документ в нишка, то оттегляме цялата нишка
                                if ($tRec->firstContainerId == $eRec->containerId) {
                                    doc_Threads::rejectThread($eRec->threadId);
                                    
                                    doc_Threads::logWrite('Оттегляне на нишка с вирусусен имейл', $tRec->id);
                                    doc_Threads::logNotice('Оттегляне на нишка с вирусусен имейл', $tRec->id);
                                } else {
                                    email_Incomings::logWrite('Оттегляне на имейл с вирус', $eRec->id);
                                }
                                
                                email_Incomings::logNotice('Оттегляне на имейл с вирус', $eRec->id);
                            }
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Добавя филтър към перата
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->input(NULL, 'silent');
        
        if ($data->listFilter->rec->md5) {
            $data->query->where(array("#md5 = '[#1#]'", $data->listFilter->rec->md5));
        }
        
        $data->query->orderBy('lastCheck', 'DESC');
    }
    
    
    /**
     * 
     * 
     * @param string $rateStr
     * 
     * @return number
     */
    protected function getDangerRateByRateStr($rateStr)
    {
        $rate = 0;
        
        if (!trim($rateStr)) return $rate;
        
        $obj = new stdClass();
        
        list($obj->positives, $obj->total) = explode('|', $rateStr);
        
        if (!$obj->positives || !$obj->total) return $rate;
        
        $rate = vtotal_Checks::getDangerRate($obj);
        
        return $rate;
    }

}
