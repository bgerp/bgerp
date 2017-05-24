<?php


/**
 * Директория, в която ще се държат екстрактнатите файлове
 */
defIfNot('FILEMAN_TEMP_PATH', EF_TEMP_PATH . '/fileman');


/**
 * Клас 'fileman_Files2' -
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @deprecated
 */
class fileman_Files2 extends core_Master 
{
    
    
    /**
     * Създаване на файл от файл в ОС. Връща fh на новосъздания файл.
     * 
     * @param string $path - Пътя до файла в ОС
     * @param string $bucket - Името на кофата
     * @param string|NULL $name - Името на файла
     * @param string $type - Типа
     * 
     * @return string $fh - Манипулатора на файла
     */
    public static function absorb($path, $bucket, $name = NULL, $type = 'file')
    {
        wp('deprecated');
        
        if ($type == 'file') {
            // Очакваме да има валиден файл
            expect(is_file($path), 'Не е подаден валиден файл.');
            
            // Опитваме се да определим името на файла
            if(!$name) $name = basename($path);
        }
        
        // Очакваме да има такава кофа
        expect($bucketId = fileman_Buckets::fetchByName($bucket), 'Несъществуваща кофа.');

        // Абсорбираме файла
        $data = fileman_Data::absorb($path, $type);
        
        // Очаквамед да има данни
        expect($dataId = $data->id, 'Липсват данни.');
        
        // Инстанция на този клас
        $me = cls::get(get_called_class());
        
        // Инвокваме функцията
        $me->invoke('prepareFileName', array(&$name, $dataId));

        // Проверяваме дали същия файл вече съществува
        if ($data->new || !($fh = static::checkFileNameExist($dataId, $bucketId, $name))) {
            
            // Създаваме запис за файла
            $fh = static::createFile($name, $bucketId, $dataId);    
        }
        
        // Ако има манипулатор
        if ($fh) {
            
            // Обновяваме лога за използване на файла
            fileman_Log::updateLogInfo($fh, 'upload');
        }
        
        return $fh;
    }
    
    
    /**
     * Създаване на файл от стринг. Връща fh на новосъздания файл.
     * 
     * @param string $data - Данните от които ще се създаде файла
     * @param string $bucket - Името на кофата
     * @param string $name - Името на файла 
     * 
     * @return string $fh - Манипулатора на файла
     */
    public static function absorbStr($data, $bucket, $name)
    {
        wp('deprecated');
        
        return self::absorb($data, $bucket, $name, 'string');
    }

    
    /**
     * Нова версия от файл в ОС
     * 
     * @param string $fh - Манипулатор на файла, за който ще се създаде нова версия
     * @param string $path - Пътя до новата версия на файла
     * 
     * @return fileman_Versions $versionId - id от запис
     */
    public static function addVersion($fh, $path)
    {
        // Очакваме да има подаден файл
        expect(is_file($path), 'Не е подаден валиден файл.');
        
        // Очакваме да има такъв файл
        $fRec = fileman_Files::fetchByFh($fh);
        expect($fRec, 'Няма такъв запис');
        
        // Абсорбираме файла
        $data = fileman_Data::absorb($path, 'file');
        $dataId = $data->id;
        
        // Ако данните са същите, като на оригиналния файл
        if ($fRec->dataId == $dataId) {
            // TODO?
        }
        
        // Създаваме версия на файла
        $versionId = fileman_Versions::createNew($fh, $dataId);
        
        return $versionId;
    }

    
    /**
     * Нова версия от стринг
     * 
     * @param string $fh - Манипулатор на файла, за който ще се създаде нова версия
     * @param string $data - Данните от които ще се създаде весия на файла
     * 
     * @return fileman_Versions $versionId - id от запис
     */
    public static function addVersionStr($fh, $data)
    {
        // Очакваме да има такъв файл
        $fRec = fileman_Files::fetchByFh($fh);
        expect($fRec, 'Няма такъв запис');
        
        // Качваме файла и вземаме id' то на данните
        $data = fileman_Data::absorb($data, 'string');
        expect($dataId = $data->id, 'Липсват данни.');
        
        // Ако данните са същите, като на оригиналния файл
        if ($fRec->dataId == $dataId) {
            // TODO?
        }
        
        // Създаваме версия на файла
        $versionId = fileman_Versions::createNew($fh, $dataId);
        
        return $versionId;
    }

    
    /**
     * Екстрактване на файл в ОС. Връща пълния път до новия файл
     * 
     * @param string $fh - Манипулатор на файла, за който ще се създаде нова версия
     * @param string|NULL $path - Пътя, където да се абсорбира файла
     * @param string|NULL $fileName - Името на файла
     * 
     * @return string $copyPath - Пътя до файла
     */
    public static function extract($fh, $path=NULL, $fileName=NULL)
    {
        // Вземаме записите за файла
        expect($rec = fileman_Files::fetchByFh($fh), 'Няма такъв запис');
        
        // Вземаме пътя до данните на файла
        $originalPath = fileman_Files::fetchByFh($fh, 'path');
        
        // Ако е подаден пътя до файла
        if ($path) {
            
            // Очакваме да е валидна директория
            expect(is_dir($path));
        } else {
            
            // Ако не е подадена директорията
            
            // Вземамем временна
            $path = static::getTempPath();
        }
        
        // Ако не е задено името на файла
        if (!$fileName) {
            
            // Използваме от модела
            $fileName = $rec->name;
        }
        
        // Пътя до файла
        $copyPath = $path . "/" . $fileName;
        
        // Копираме файла
        $copied = @copy($originalPath, $copyPath);
        
        // Ако копирането не премине успешно
        if (!$copied) {
            fileman_Files::logErr("Не може да бъде копиран файла|* : '{$originalPath}' =>  '{$copyPath}'", $rec->id);
            expect($copied, 'Не може да бъде копиран файла');
        }
        
        // Времето на екстрактване
        $rec->extractedOn = dt::verbal2Mysql();

        // Записваме информация за екстрактването
        fileman_Files::save($rec, 'extractedOn');
        
        // Ако има запис
        if ($rec) {
            
            // Обновяваме лога за използване на файла
            fileman_Log::updateLogInfo($rec, 'preview');
        }
        
        return $copyPath;
    }

    
    /**
     * Екстрактване на файл в string. Връща стринга.
     * 
     * @param string $fh - Манипулатор на файла, за който ще се създаде нова версия
     * 
     * @return string $content - Данните на файла
     */
    public static function extractStr($fh)
    {
        // Ако има манипулатор
        if ($fh) {
            
            // Обновяваме лога за използване на файла
            fileman_Log::updateLogInfo($fh, 'preview');
        }
        
        // Екстрактваме файла във временена директория
        $tempFile = static::extract($fh);
        
        // Вземаме съдържанието му
        $content = file_get_contents($tempFile);
        
        // Изтриване на временния файл
        static::deleteTempPath($tempFile);
        
        return $content;
    }
    
    
    /**
     * Преименуване на файл
     * 
     * @param string $fh - Манипулатор на файла
     * @param string $newName - Новото име на файла
     * 
     * @return string - Новото име на файла
     */
    public static function rename($fh, $newName)
    {
        // Очакваме да има валиден запис
        expect($rec = fileman_Files::fetchByFh($fh), 'Няма такъв запис.');
        
        // Ако имена не са еднакви
        if($rec->name != $newName) {
            
            // Вземаме възможното има за съответната кофа
            $rec->name = fileman_Files::getPossibleName($newName, $rec->bucketId); 
            
            // Записваме
            fileman_Files::save($rec);
            
            // Изтриваме файла от sbf и от модела
            fileman_Download::deleteFileFromSbf($rec->id);
            
            // Изтриваме всички предишни индекси за файла
            fileman_Indexes::deleteIndexesForData($rec->dataId);
        }
        
        return $rec->name;
    }

    
    /**
     * Копиране на файл
     * 
     * @param string $fh - Манипулатора на файла
     * @param string|NULL $newBucket - Името на новата кофа
     * @param string|NULL $newName - Новото име на файла
     * 
     * @return string $newRec->fileHnd - Манипулатора на файла
     */
    public static function copy($fh, $newBucket = NULL, $newName = NULL)
    {
        // Очакваме да има такъв файл
        expect($rec = fileman_Files::fetchByFh($fh), 'Няма такъв запис');
        
        // Името на новия файл
        $newName = ($newName) ? $newName : $rec->name;
        
        // Ако е подадена кофа
        if ($newBucket) {
            
            // Очакваме да има валидна кофа
            expect($bucketId = fileman_Buckets::fetchByName($newBucket), 'Няма такава кофа');
        } else {
            
            // Ако не е подадена кофа, използваме кофата на файла
            $bucketId = $rec->bucketId;
        }
        
        // Името на новия файла
        $possibleName = fileman_Files::getPossibleName($newName, $bucketId);
        
        // Записваме данните
        $newRec = new stdClass();
        $newRec->name = $possibleName;
        $newRec->bucketId = $bucketId;
        $newRec->dataId = $rec->dataId;
        $newRec->state = 'active';
        
        $id = fileman_Files::save($newRec);
        
        // Очакваме записа да е преминал успешно
        expect($id, 'Възникна грешка при записването.');
        
        // Увеличаваме броя на линковете, които сочат към данните
        fileman_Data::increaseLinks($rec->dataId);
        
        // Връщаме манипулатора на файла
        return $newRec->fileHnd;
    }

    
    /**
     * Връща id на посочения fileHnd
     * 
     * @param string $fh - Манипулатора на файла
     * 
     * @return fileman_Files $id - id на файла
     */
    public static function fhToId($fh)
    {
        // Вземаме id' то на файла
        $id = fileman_Files::fetchByFh($fh, 'id');
        
        return $id;
    }

    
    /**
     * Връща масив от id-та  на файлове. Като аргумент получава масив или keylist от fileHandles.
     * 
     * @param array $fhKeylist - масив или keylist от манипулатора на файлове
     * 
     * @return array $idsArr - Масив с id' то във fileman_Files
     */
    public static function fhKeylistToIds($fhKeylist)
    {
        // Ако не е масив
        if (!is_array($fhKeylist)) {
            
            // Превъращаме keyList в масив
            $fhArr = keylist::toArray($fhKeylist);
        } else {
            
            // Използваме масива
            $fhArr = $fhKeylist;
        }
        
        //Създаваме променлива за id' тата
        $idsArr = array();
        
        // Обхождаме масива
        foreach ($fhArr as $fh) {
            
            //Ако няма стойност, прескачаме
            if (!$fh) continue;
            
            try {
                
                // Вземема id'то на файла
                $id = static::fhToId($fh);
            } catch (core_exception_Expect $e) {
                
                // Ако възникне грешка
                continue;
            }   
            
            // Добавяме в масива
            $idsArr[$id] = $id;
        }
        
        return $idsArr;
    }

    
    /**
     * Връща fileHnd на посоченото id
     * 
     * @param fileman_Files $id - id на файла
     * 
     * @return string $fh - Манипулатора на файла
     */
    public static function idToFh($id)
    {
        // Вземаме манипулатора на файла
        $fh = fileman_Files::fetchField($id, 'fileHnd');
        
        return $fh;
    }

    
    /**
     * Връща масив от fh-ри  на файлове. Като аргумент получава масив или keylist от id-та на файлове
     * 
     * @param array $idKeylist - масив или keylist от id (от fileman_Files) на файлове
     * 
     * @return array $idsArr - Масив с манипулатор
     */
    public static function idKeylistToFhs($idKeylist)
    {
        // Ако не е масив
        if (!is_array($idKeylist)) {
            
            // Превъращаме keyList в масив
            $idArr = keylist::toArray($idKeylist);
        } else {
            
            // Използваме масива
            $idArr = $idKeylist;
        }
        
        //Създаваме променлива за id' тата
        $fhsArr = array();
        
        foreach ($idArr as $id) {
            
            //Ако няма стойност, прескачаме
            if (!$id) continue;
            
            try {
                
                // Вземаме манипуалтора
                $fh = static::idToFh($id);
            } catch (core_exception_Expect $e) {
                
                // Ако няма такъв fh, тогава прескачаме
                continue;
            }   
            
            // Добавяме в масива
            $fhsArr[$fh] = $fh;
        }
        
        return $fhsArr;
    }

    
    /**
     * Връща всички мета-характеристики на файла
     * 
     * @param strign $fh - Манипулатор на файла
     * 
     * @param return array(
     *      'name' => '...',
     *      'bucket' => '...',
     *      'size' => ...,
     *      'creationDate' => '...',
     *      'modificationDate' => '...',
     *      'extractDate' => '...',
     *   )
     */
    public static function getMeta($fh)
    {
        // Масив с мета данни
        $metaDataArr = array();
        
        // Вземаме записите
        $rec = fileman_Files::fetchByFh($fh);
        $data = fileman_Data::fetch($rec->dataId);

        // Очакваме да има такъв запис
        expect($rec && $data, 'Няма такъв запис.');
        
        // Попълваме масива
        $metaDataArr['name'] = $rec->name;
        $metaDataArr['bucket'] = $rec->bucketId;
        $metaDataArr['size'] = $data->fileLen;
        $metaDataArr['creationDate'] = $rec->createdOn;
        $metaDataArr['modificationDate'] = $rec->modifiedOn;
        $metaDataArr['extractDate'] = $rec->extractedOn;
        
        return $metaDataArr;
    }
    
    
	/**
     * Създава нов файл
     * 
     * @param string $name - Името на файла
     * @param fileman_Buckets $bucketId - Кофата, в която ще създадем
     * @param fileman_Data $dataId - Данните за файла
     * 
     * @return string $rec->fileHnd - Манипуалатор на файла
     */
    protected static function createFile($name, $bucketId, $dataId)
    {
        // Създаваме записите
        $rec = new stdClass();
        $rec->name = fileman_Files::getPossibleName($name, $bucketId);
        $rec->bucketId = $bucketId;
        $rec->state = 'active';
        $rec->dataId = $dataId;
        
        // Записваме
        fileman_Files::save($rec);
        
        // Увеличаваме с единица броя на файловете за които отговаря файла
        fileman_Data::increaseLinks($dataId);
        
        return $rec->fileHnd;
    }
    
    
    /**
     * Създава нова директория, където ще се записват файловете
     * 
     * @return string $tempPath - Пътя до новата директория
     */
    public static function getTempPath()
    {
        // Вземаме директорията за временните файлове
        $dir = static::getTempDir();
        
        // Сканираме директорията
        $dirs = @scandir($dir);
        
        // Опитваме се да генерираме име, което не се среща в директория
        do {
            $newName = str::getRand();
        }while(in_array($newName, (array)$dirs));
        
        // Пътя на директорията
        $tempPath = $dir . '/' . $newName;
        
        // Създаваме директорията
        expect(mkdir($tempPath, 0777, TRUE), 'Не може да се създаде директория.');
        
        return $tempPath;
    }
    
    
    /**
     * Връща директорията с временните файлове
     * 
     * @return string - Пътя до директорията, където се съхраняват времените файлове
     */
    public static function getTempDir()
    {
        // TODO
//    	$conf = core_Packs::getConfig('fileman');
//    	$tempPath = $conf->FILEMAN_TEMP_PATH;
    	
        // Пътя до директория с временните файлове
        $tempDir = FILEMAN_TEMP_PATH;
        
        return $tempDir;
    }
    
    
    /**
     * Изтрива временната директория
     * 
     * @param string $tempFile - Файла, който ще бъде изтрит с директорията
     * 
     * @return boolean $deleted - Връща TRUE, ако изтриването протече коректно
     */
    public static function deleteTempPath($tempFile)
    {
        // Очакваме да е подаден валиден файл
        expect(is_file($tempFile), 'Не е валиден файл.');
        
        // Вземаме директорията, в която се намира файла
        $dirName = dirname($tempFile);
        
        // Очакваме папката, която ще изтриваме да е от темп директорията за файлове
        expect(stripos($dirName, static::getTempDir() . '/') === 0, 'Файла, който сте подали не е от позволените директории.');
        
        // Изтриваме директорията
        $deleted = core_Os::deleteDir($dirName);
        
        return $deleted;
    }
    
    
	/**
	 * 
	 */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Пътя до временните файлове
        $tempPath = static::getTempDir();
        
        // Ако не същестува
        if(!is_dir($tempPath)) {
            
            // Ако не може да се създаде
            if(!mkdir($tempPath, 0777, TRUE)) {
                
                $res .= '<li class="debug-error">' . tr('Не може да се създаде директорията') . ': "' . $tempPath . '"</li>';
            } else {
                $res .= '<li class="debug-new">' . tr('Създадена е директорията') . ': "' . $tempPath . '"</li>';
            }
        } else {
            $res .= '<li>' . tr('Директорията съществува') . ': "' . $tempPath . '"</li>';
        }
        
        return $res;
    }
    
    
    /**
     * Проверява дали файла със съответните данни съществува
     * 
     * @param fileman_Data $dataId - id на данните на файла
     * @param fileman_Buckets $bucketId - id на кофата
     * @param string $inputFileName - Името на файла
     * 
     * @return string|FALSE - Ако открие съвпадение връща манипулатора на файла
     */
    static function checkFileNameExist($dataId, $bucketId, $inputFileName)
    {
        // Името на файла в долния регистър
        $inputFileName = strtolower($inputFileName);
        
        // Вземаме първия файл (по име) в съответната кофа със съответните данни
        $query = fileman_Files::getQuery();
        $query->where("#bucketId = '{$bucketId}' AND #dataId = '{$dataId}'");
        $query->show('fileHnd, name');
        $query->orderBy('name', 'ASC');
        
        // Нормализираме името на файла
        $inputFileName = fileman_Files::normalizeFileName($inputFileName);

        // Вземаме името на файла и разширението
        $recFileNameArr = fileman_Files::getNameAndExt($inputFileName);
        
        // Ескейпваме името на файла
        $recFileNameArr['name'] = preg_quote($recFileNameArr['name'], '/');
        $recFileNameArr['ext'] = preg_quote($recFileNameArr['ext'], '/');
        
        // Регулярният израз за откриване на подобни файлове
        $regExp = "^" . $recFileNameArr['name'] . "(\_[0-9]+)*";
        
        // Ако има разширение на файла
        if ($recFileNameArr['ext']) {
            $regExp .= "(\." . $recFileNameArr['ext'] . '){1}';
        }
        
        // Край на регулярния израз
        $regExp .= "$";
        
        // Добавяме регулярния израз за търсене
        $query->where("LOWER(#name) REGEXP '{$regExp}'");
        
        // Ако сме открили запис
        if ($rec = $query->fetch()) {
            
            // Връщаме манипулатора му
            return $rec->fileHnd;
        }
        
        return FALSE;
    }
    
    
    /**
     * Проверява дали пътя е коректен файл
     * 
     * @param string $path - Пътя до файла
     * 
     * @return boolean
     */
    static function isCorrectPath($path)
    {
        static $isCorrect;
        
        // Ако не е проверяван
        if (!isset($isCorrect[$path])) {
            
            // Ако е коректен път
            if (is_file($path)) {
                
                // Добавяме в масива
                $isCorrect[$path] = TRUE;
            } else {
                
                $isCorrect[$path] = FALSE;
            }
        }
        
        return $isCorrect[$path];
    }
    

    /**
     * Проверява дали аргумента е допустим файлов манипулатор
     */
    public static function isFileHnd($str)
    {
        cls::load('fileman_Files');
        $ptr = "/^[a-z][a-z0-9]{" . (FILEMAN_HANDLER_LEN-1) . "}\$/i";

        return preg_match($ptr, $str);
    }

    
    /**
     * Връща mimе типа за съответния файл
     * 
     * @param string $path - Пътя до файла
     * 
     * @return string - Миме типа на файла
     */
    static function getMimeTypeFromFilePath($path)
    {
        // Очакваме да е валиден път иначе се отказваме
        if(!static::isCorrectPath($path)) return FALSE;
        
        // Вземаме конфигурацията
        $conf = core_Packs::getConfig('fileman');
        
        $fileCmd = $conf->FILEMAN_FILE_COMMAND;
        
        $fileCmd = escapeshellcmd($fileCmd);
        
        // Изпълняваме командата
        $res = exec("{$fileCmd} --mime-type  \"{$path}\"");
 
        // Вземаме позицията на интервала
        list($p, $mime) = explode(' ', $res);
        
        // Тримваме за всеки случай
        $mime = strtolower(trim($mime));
        
        // Доуточняване, ако е изпълним файл, дали не е за MS Windows
        if($mime == 'application/octet-stream') {
            $res = exec("{$fileCmd} \"{$path}\"");
            if(stripos($res, 'PE32 executable for MS Windows')) {
                $mime = 'application/x-msdownload';
            }
        }

        // Връщаме mime типа
        return $mime;
    }
    
    
    /**
     * Връща времето на последната модификация на файла
     * 
     * @param string $path - Пътя до файла
     * 
     * @return timeStamp - Времето на последна промяна на файла
     */
    static function getModificationTimeFromFilePath($path)
    {
        // Очакваме да е валиден път
        expect(static::isCorrectPath($path));
        
        return filemtime($path);
    }
    
    
    /**
     * Връща времето на създаване на файла
     * 
     * @param string $path - Пътя до файла
     * 
     * @return timeStamp - Времето на създаване на файла
     */
    static function getCreationTimeFromFilePath($path)
    {
        // Очакваме да е валиден път
        expect(static::isCorrectPath($path));
        
        return filectime($path);
    }
    
    
    /**
     * Връща времето на последен достъп до файла
     * 
     * @param string $path - Пътя до файла
     * 
     * @return timeStamp - Времето на последен достъп до файла
     */
    static function getAccessTimeFromFilePath($path)
    {
        // Очакваме да е валиден път
        expect(static::isCorrectPath($path));
        
        return fileatime($path);
    }
    
    
    /**
     * Връща размера на файла
     * 
     * @param string $path - Пътя до файла
     * 
     * @return integer - Размера на файла в байтове
     */
    static function getFileSizeFromFilePath($path)
    {
        // Очакваме да е валиден път
        expect(static::isCorrectPath($path));
        
        return filesize($path);
    }
    
    
    /**
     * Връща типа на файла. Позволените са: fifo, char, dir, block, link, file, socket and unknown
     * 
     * @param string $path - Пътя до файла
     * 
     * @return string - Типа на файла
     */
    static function getFileTypeFromFilePath($path)
    {
        // Очакваме да е валиден път
        expect(static::isCorrectPath($path));
        
        return filetype($path);
    }
    
    
    /**
     * Връща масив с всички данни за файла
     * 
     * @param string $filePath - Пътя до файла
     * 
     * @return array $res - Масив с резултатите
     * 
     * $res['modificationTime'] - Време на последна модификация
     * $res['creationTime'] - Време на създаване
     * $res['accessTime'] - Време на последен достъп до файла
     * $res['fileSize'] - Размера на файла
     * $res['fileType'] - Типа на файла
     * $res['mimeType'] - Миме типа на файла
     * $res['extension'] - Разширението на файла
     * $res['isCorrectExt'] - Дали разширението на файла е в допусмите за миме типа
     */
    static function getInfoFromFilePath($filePath)
    {
        // Време на последна модификация
        $res['modificationTime'] = static::getModificationTimeFromFilePath($filePath);
        
        // Време на създаване
        $res['creationTime'] = static::getCreationTimeFromFilePath($filePath);
        
        // Време на последен достъп до файла
        $res['accessTime'] = static::getAccessTimeFromFilePath($filePath);
        
        // Размера на файла
        $res['fileSize'] = static::getFileSizeFromFilePath($filePath);
        
        // Типа на файла
        $res['fileType'] = static::getFileTypeFromFilePath($filePath);
        
        // Миме типа на файла
        $res['mimeType'] = static::getMimeTypeFromFilePath($filePath);
        
        // Разширението на файла
        $res['extension'] = fileman_Files::getExt($filePath);
        
        // Дали разширението на файла е в допусмите за миме типа
        $res['isCorrectExt'] = fileman_Mimes::isCorrectExt($res['mimeType'], $res['extension']);
        
        return $res;
    }
    
    
    /**
     * Връща линк към сингъла на файла
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param boolean $absolute - Дали линка да е абсолютен
     * @param array $attr - Други параметри
     * @param string|NULL $name - Името, което да се използва
     * 
     * @return core_Et - Линк
     */
    static function getLinkToSingle($fh, $absolute=FALSE, $attr=array(), $name=NULL)
    {
        // Вземаме записа
        $rec = fileman_Files::fetchByFh($fh);
        
        // Ако е задедено името
        if ($name) {
            
            // Ескейпваме вербалното
            $vName = type_Varchar::escape($name);
            $vName = core_ET::escape($vName);
        } else {
            
            // Името
            $name = $rec->name;
            
            // Вербалното име
            $vName = fileman_Files::getVerbal($rec,'name');
        }
        
        if (isset($attr['limitName'])) {
            $vName = str::limitLen($vName, $attr['limitName']);
            unset($attr['limitName']);
        }
        
        //Разширението на файла
        $ext = fileman_Files::getExt($name);
        
        //Иконата на файла, в зависимост от разширението на файла
        $icon = "fileman/icons/16/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение
        if (!is_file(getFullPath($icon))) {
            
            // Използваме иконата по подразбиране
            $icon = "fileman/icons/16/default.png";
        }
        
        // Вербалното име на файла
        $fileName = "<span class='linkWithIcon' style=\"" . ht::getIconStyle($icon) . "\">{$vName}</span>";
        
        // Вземаме URL' то
        $url = static::getUrlToSingle($fh, $absolute);
        
        $attr['rel'] = 'nofollow';
        
        $isAbsolute = (boolean)(Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf'));
        if (!$isAbsolute && fileman_Files::isDanger($rec)) {
            $attr['class'] .= ' dangerFile';
        }
        
        // Вземаме линка
        $link = ht::createLink($fileName, $url, FALSE, $attr);
        
        return $link;
    }
    
    
    /**
     * Връща URL към сингъла на файла
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param boolean $absolute - Дали URL-то да е абсолютен
     * 
     * @return string - URL към сингъла
     */
    static function getUrlToSingle($fh, $absolute=FALSE)
    {
        // Вземаме URL' то
        $url = toUrl(array('fileman_Files', 'single', $fh), $absolute);
        
        // Връщаме URL' то
        return $url;
    }
    
    
    /**
     * Добавя стринг във временната директория с подадения файл
     * Не се записва във fileman
     *
     * @param string $fileStr
     * @param string $name
     *
     * @return string
     */
    public static function addStrToFile($fileStr, $name)
    {
        $tempDir = fileman::getTempPath() . '/' . $name;
    
        expect(@file_put_contents($tempDir, $fileStr) !== FALSE);
    
        return $tempDir;
    }
    
    
    /**
     * Обновява времето на последно използване на файла
     * 
     * @param string|stdObject $fh
     * @param NULL|datetime $lastUse
     * 
     * @return boolean
     */
    public static function updateLastUse($fh, $lastUse = NULL)
    {
        if (is_object($fh)) {
            $fRec = $fh;
        } else {
            $fRec = fileman_Files::fetchByFh($fh);
        }
        
        // Обновяваме времето на последно използване на данните
        return fileman_Data::updateLastUse($fRec->dataId, $lastUse);
    }
}
