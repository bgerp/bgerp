<?php


/**
 * Клас 'fileman_Files' -
 *
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Files extends core_Master
{


    /**
     * Кой може да променя файла
     */
    public $canEditfile = 'user';


    /**
     * Детайла, на модела
     */
    public $details = 'fileman_FileDetails';
    
    
    protected $canEdit = 'no_one';
    
    
    /**
     * Всички потребители могат да разглеждат файлове
     */
    protected $canSingle = 'user';
    
    
    protected $canDelete = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     *
     * @todo След като се направи да се показват само файловете на потребителя
     */
    protected $canList = 'ceo, admin, debug';
    
    
    public $singleLayoutFile = 'fileman/tpl/SingleLayoutFile.shtml';
    
    
    protected $canAdd = 'no_one';
    
    
    /**
     * Кой има права за регенерира на файла
     */
    protected $canRegenerate = 'admin, debug';
    
    
    /**
     * Кой има права за регенерира на файла
     */
    protected $canPrintfiles = 'user';
    
    
    /**
     * Заглавие на модула
     */
    public $title = 'Файлове';
    
    
    public $listFields = 'name=Файл->Име, fileLen=Файл->Размер, bucketId, createdOn, createdBy';
    
    
    public $loadList = 'plg_Sorting, plg_GroupByDate';


    /**
     * На участъци от по колко записа да се бекъпва?
     */
    public $backupMaxRows = 100000;
    
    
    /**
     * Кои полета да определят рзличността при backup
     */
    public $backupDiffFields = 'modifiedOn,extractedOn';
    

    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Файлов манипулатор - уникален 8 символно/цифров низ, започващ с буква.
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD(
            'fileHnd',
            'varchar(' . strlen(fileman_Setup::get('HANDLER_PTR')) . ')',
            array('notNull' => true, 'caption' => 'Манипулатор')
        );
        
        // Име на файла
        $this->FLD(
            'name',
            'varchar(255,collate=ascii_bin,indexPrefix=255)',
            array('notNull' => true, 'caption' => 'Файл')
        );
        
        // Данни (Съдържание) на файла
        $this->FLD(
            'dataId',
            'key(mvc=fileman_Data)',
            array('caption' => 'Данни Id')
        );
        
        // Клас - притежател на файла
        $this->FLD(
            'bucketId',
            'key(mvc=fileman_Buckets, select=name)',
            array('caption' => 'Кофа')
        );
        
        // Състояние на файла
        $this->FLD(
            'state',
            'enum(draft=Чернова,active=Активен,rejected=Оттеглен)',
            array('caption' => 'Състояние', 'column' => 'none')
        );
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,plg_Modified,Data=fileman_Data,Buckets=fileman_Buckets,' .
            'Download=fileman_Download,Versions=fileman_Versions,fileman_Wrapper');
        
        //
        $this->FLD('extractedOn', 'datetime(format=smartTime)', 'caption=Екстрактнато->На,input=none,column=none');
        
        $this->FLD('fileLen', 'fileman_FileSize', 'caption=Размер');
        
        $this->FLD('dangerRate', 'percent(decimals=0)', 'caption=Риск от опасност');
        
        // Индекси
        $this->setDbUnique('fileHnd');
        $this->setDbUnique('name,bucketId', 'uniqName');
        $this->setDbIndex('dataId,bucketId', 'indexDataId');
        $this->setDbIndex('createdBy');
    }
    
    
    /**
     * Връща записа за посочения файл или негово поле, ако е указано.
     * Ако посоченото поле съществува в записа за данните за файла,
     * връщаната стойност е от записа за данните на посочения файл
     */
    public static function fetchByFh($fh, $field = null)
    {
        $Files = cls::get('fileman_Files');
        
        $rec = $Files->fetch(array("#fileHnd = '[#1#]'", $fh));
        
        if ($field === null) {
            
            return $rec;
        }
        
        if (!isset($rec->{$field})) {
            $Data = cls::get('fileman_Data');
            
            $dataFields = $Data->selectFields('');
            
            if ($dataFields[$field]) {
                $rec = $Data->fetch($rec->dataId);
            }
        }
        
        return $rec->{$field};
    }
    
    
    /**
     * Създаване на файл от файл в ОС. Връща fh на новосъздания файл.
     *
     * @param string      $path   - Пътя до файла в ОС
     * @param string      $bucket - Името на кофата
     * @param string|NULL $name   - Името на файла
     * @param string      $type   - Типа
     *
     * @return string $fh - Манипулатора на файла
     */
    public static function absorb($path, $bucket, $name = null, $type = 'file')
    {
        if ($type == 'file') {
            // Очакваме да има валиден файл
            expect(is_file($path), 'Не е подаден валиден файл.');
            
            // Опитваме се да определим името на файла
            if (!$name) {
                $name = basename($path);
            }
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
     * @param string $data   - Данните от които ще се създаде файла
     * @param string $bucket - Името на кофата
     * @param string $name   - Името на файла
     *
     * @return string $fh - Манипулатора на файла
     */
    public static function absorbStr($data, $bucket, $name)
    {
        return self::absorb($data, $bucket, $name, 'string');
    }
    
    
    /**
     * Нова версия от файл в ОС
     *
     * @param string $fh   - Манипулатор на файла, за който ще се създаде нова версия
     * @param string $path - Пътя до новата версия на файла
     * @param string $type - Типа
     *
     * @return fileman_Versions $versionId - id от запис
     */
    public static function addVersion($fh, $path, $type = 'file')
    {
        if ($type == 'file') {
            // Очакваме да има подаден файл
            expect(is_file($path), 'Не е подаден валиден файл.');
        }
        
        // Очакваме да има такъв файл
        $fRec = fileman_Files::fetchByFh($fh);
        expect($fRec, 'Няма такъв запис');
        
        // Абсорбираме файла
        $data = fileman_Data::absorb($path, $type);
        $newDataId = $data->id;
        
        // Създаваме версия на файла
        $versionId = fileman_Versions::createNew($fh, $newDataId);
        
        return $versionId;
    }
    
    
    /**
     * Нова версия от стринг
     *
     * @param string $fh   - Манипулатор на файла, за който ще се създаде нова версия
     * @param string $data - Данните от които ще се създаде весия на файла
     *
     * @return fileman_Versions $versionId - id от запис
     */
    public static function addVersionStr($fh, $data)
    {
        return self::addVersion($fh, $data, 'string');
    }
    
    
    /**
     * Екстрактване на файл в ОС. Връща пълния път до новия файл
     *
     * @param string      $fh       - Манипулатор на файла, за който ще се създаде нова версия
     * @param string|NULL $path     - Пътя, където да се абсорбира файла
     * @param string|NULL $fileName - Името на файла
     *
     * @return string $copyPath - Пътя до файла
     */
    public static function extract($fh, $path = null, $fileName = null)
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
        $copyPath = $path . '/' . $fileName;
        
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
     * @param string $fh      - Манипулатор на файла
     * @param string $newName - Новото име на файла
     *
     * @return string - Новото име на файла
     */
    public static function rename($fh, $newName)
    {
        // Очакваме да има валиден запис
        expect($rec = fileman_Files::fetchByFh($fh), 'Няма такъв запис.');
        
        // Ако имена не са еднакви
        if ($rec->name != $newName) {
            
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
     * @param string      $fh        - Манипулатора на файла
     * @param string|NULL $newBucket - Името на новата кофа
     * @param string|NULL $newName   - Новото име на файла
     *
     * @return string $newRec->fileHnd - Манипулатора на файла
     */
    public static function copy($fh, $newBucket = null, $newName = null)
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
            if (!$fh) {
                continue;
            }
            
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
            if (!$id) {
                continue;
            }
            
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
     * @param string $fh - Манипулатор на файла
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
        } while (in_array($newName, (array) $dirs));
        
        // Пътя на директорията
        $tempPath = $dir . '/' . $newName;
        
        // Създаваме директорията
        core_Os::requireDir($tempPath);
        
        return $tempPath;
    }
    
    
    /**
     * Връща директорията с временните файлове
     *
     * @return string - Пътя до директорията, където се съхраняват времените файлове
     */
    public static function getTempDir()
    {
        // Пътя до директория с временните файлове
        $tempDir = fileman_Setup::get('TEMP_PATH');
        
        return $tempDir;
    }
    
    
    /**
     * Изтрива временната директория
     *
     * @param string $tempFile - Файла, който ще бъде изтрит с директорията
     *
     * @return bool $deleted - Връща TRUE, ако изтриването протече коректно
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
     * Проверява дали файла със съответните данни съществува
     *
     * @param fileman_Data    $dataId        - id на данните на файла
     * @param fileman_Buckets $bucketId      - id на кофата
     * @param string          $inputFileName - Името на файла
     *
     * @return string|FALSE - Ако открие съвпадение връща манипулатора на файла
     */
    public static function checkFileNameExist($dataId, $bucketId, $inputFileName)
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
        $regExp = '^' . $recFileNameArr['name'] . "(\_[0-9]+)*";
        
        // Ако има разширение на файла
        if ($recFileNameArr['ext']) {
            $regExp .= "(\." . $recFileNameArr['ext'] . '){1}';
        }
        
        // Край на регулярния израз
        $regExp .= '$';
        
        // Добавяме регулярния израз за търсене
        $query->where("LOWER(#name) REGEXP '{$regExp}'");
        
        // Ако сме открили запис
        if ($rec = $query->fetch()) {
            
            // Връщаме манипулатора му
            return $rec->fileHnd;
        }
        
        return false;
    }
    
    
    /**
     * Проверява дали пътя е коректен файл
     *
     * @param string $path - Пътя до файла
     *
     * @return bool
     */
    public static function isCorrectPath($path)
    {
        static $isCorrect;
        
        // Ако не е проверяван
        if (!isset($isCorrect[$path])) {
            
            // Ако е коректен път
            if (is_file($path)) {
                
                // Добавяме в масива
                $isCorrect[$path] = true;
            } else {
                $isCorrect[$path] = false;
            }
        }
        
        return $isCorrect[$path];
    }
    
    
    /**
     * Връща mime типа за съответния файл
     *
     * @param string $path - Пътя до файла
     *
     * @return string - Миме типа на файла
     */
    public static function getMimeTypeFromFilePath($path)
    {
        // Очакваме да е валиден път иначе се отказваме
        if (!static::isCorrectPath($path)) {
            
            return false;
        }
        
        // Вземаме конфигурацията
        $conf = core_Packs::getConfig('fileman');
        
        $fileCmd = $conf->FILEMAN_FILE_COMMAND;
        
        $fileCmd = escapeshellcmd($fileCmd);
        
        // Изпълняваме командата
        $res = exec("{$fileCmd} --mime-type  \"{$path}\"");
        
        // Вземаме позицията на интервала
        list(, $mime) = explode(' ', $res);
        
        // Тримваме за всеки случай
        $mime = strtolower(trim($mime));
        
        // Доуточняване, ако е изпълним файл, дали не е за MS Windows
        if ($mime == 'application/octet-stream') {
            $res = exec("{$fileCmd} \"{$path}\"");
            if (stripos($res, 'PE32 executable for MS Windows')) {
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
     * @return int - Времето на последна промяна на файла
     */
    public static function getModificationTimeFromFilePath($path)
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
     * @return int - Времето на създаване на файла
     */
    public static function getCreationTimeFromFilePath($path)
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
     * @return int - Времето на последен достъп до файла
     */
    public static function getAccessTimeFromFilePath($path)
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
     * @return int - Размера на файла в байтове
     */
    public static function getFileSizeFromFilePath($path)
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
    public static function getFileTypeFromFilePath($path)
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
    public static function getInfoFromFilePath($filePath)
    {
        $res = array();
        
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
     * Проверява дали аргумента е допустим файлов манипулатор
     */
    public static function isFileHnd($str)
    {
        $ptr = '/^[a-z][a-z0-9]{' . (fileman_Setup::get('HANDLER_LEN') - 1) . '}$/i';
        
        return preg_match($ptr, $str);
    }
    
    
    /**
     * Връща линк към сингъла на файла
     *
     * @param string      $fh       - Манипулатор на файла
     * @param bool        $absolute - Дали линка да е абсолютен
     * @param array       $attr     - Други параметри
     * @param string|NULL $name     - Името, което да се използва
     *
     * @return core_Et - Линк
     */
    public static function getLinkToSingle($fh, $absolute = false, $attr = array(), $name = null)
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
            $vName = fileman_Files::getVerbal($rec, 'name');
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
            $icon = 'fileman/icons/16/default.png';
        }
        
        // Вербалното име на файла
        $fileName = "<span class='linkWithIcon' style=\"" . ht::getIconStyle($icon) . "\">{$vName}</span>";
        
        // Вземаме URL' то
        $url = static::getUrlToSingle($fh, $absolute);
        
        $attr['rel'] = 'nofollow';
        
        $isAbsolute = (boolean) (Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf'));
        if (!$isAbsolute && fileman_Files::isDanger($rec)) {
            $attr['class'] .= ' dangerFile';
        }
        
        // Вземаме линка
        $link = ht::createLink($fileName, $url, false, $attr);
        
        return $link;
    }
    
    
    /**
     * Връща URL към сингъла на файла
     *
     * @param string $fh       - Манипулатор на файла
     * @param bool   $absolute - Дали URL-то да е абсолютен
     *
     * @return string - URL към сингъла
     */
    public static function getUrlToSingle($fh, $absolute = false)
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
        
        expect(@file_put_contents($tempDir, $fileStr) !== false);
        
        return $tempDir;
    }
    
    
    /**
     * Обновява времето на последно използване на файла
     *
     * @param string|stdClass $fh
     * @param NULL|datetime   $lastUse
     *
     * @return bool
     */
    public static function updateLastUse($fh, $lastUse = null)
    {
        if (is_object($fh)) {
            $fRec = $fh;
        } else {
            $fRec = fileman_Files::fetchByFh($fh);
        }
        
        // Обновяваме времето на последно използване на данните
        return fileman_Data::updateLastUse($fRec->dataId, $lastUse);
    }
    
    
    /**
     * Помощна фунцкия за ограничаване и подготвяне на името на файла на името на файла
     * 
     * @param string $firstName
     * @param string $ext
     * @param null|integer $index
     * @param integer $maxNameLen
     * 
     * @return string
     */
    protected static function prepareFn($firstName, $ext, $index = null, $maxNameLen = 255)
    {
        $eLen = strlen($ext);
        $len = strlen($firstName) + $eLen;
        
        $iLen = 0;
        $indexStr = '';
        if (isset($index)) {
            $indexStr = '_' . $index;
            $iLen = strlen($indexStr);
            $len += $iLen;
        }
        
        if (($len) >= $maxNameLen) {
            $firstName = substr($firstName, 0, $maxNameLen - $eLen - $iLen);
        }
        
        return $firstName . $indexStr . $ext;
    }
    
    
    /**
     * Връща първото възможно има, подобно на зададеното, така че в този
     * $bucketId да няма повторение на имената
     */
    public static function getPossibleName($fname, $bucketId)
    {
        // Конвертираме името към такова само с латински букви, цифри и знаците '-' и '_'
        $fname = static::normalizeFileName($fname);
        
        // Циклим докато генерираме име, което не се среща до сега
        $fn = $fname;
        
        if (($dotPos = strrpos($fname, '.')) !== false) {
            $firstName = substr($fname, 0, $dotPos);
            $ext = substr($fname, $dotPos);
        } else {
            $firstName = $fname;
            $ext = '';
        }
        
        $fn = self::prepareFn($firstName, $ext);
        
        // Двоично търсене за свободно име на файл
        $i = 1;
        while (self::fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id')) {
            $fn = self::prepareFn($firstName, $ext, $i);
            
            $i = $i * 2;
        }
        
        // Търсим първото незаето положение за $i в интервала $i/2 и $i
        if ($i > 4) {
            $min = $i / 4;
            $max = $i / 2;
            
            do {
                $i = ($max + $min) / 2;
                $fn = self::prepareFn($firstName, $ext, $i);
                
                if (self::fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id')) {
                    $min = $i;
                } else {
                    $max = $i;
                }
            } while ($max - $min > 1);
            
            $i = $max;
            
            $fn = self::prepareFn($firstName, $ext, $i);
            
        }
        
        return $fn;
    }
    
    
    /**
     * Нормализира името на файла
     * Конвертираме името към такова само с латински букви, цифри и знаците '-' и '_'
     *
     * @param string $fname - Името на файла
     */
    public static function normalizeFileName($fname)
    {
        // Конвертираме името към такова само с латински букви, цифри и знаците '-' и '_'
        $fname = STR::utf2ascii($fname);
        $fname = preg_replace('/[^a-zA-Z0-9\-_\.]+/', '_', $fname);
        
        return $fname;
    }
    
    
    /**
     * Връща данните на един файл като стринг
     */
    public static function getContent($hnd)
    {
        log_System::add(get_called_class(), "fileman_Files::getContent('{$hnd}')");
        
        //expect($path = fileman_Download::getDownloadUrl($hnd));
        expect($path = fileman_Files::fetchByFh($hnd, 'path'));
        
        return @file_get_contents($path);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function getUrLForAddFile($bucketId, $callback)
    {
        // Защитаваме променливите
        Request::setProtected('bucketId,callback');
        
        // Задаваме линка
        $url = array('fileman_Files', 'AddFile', 'bucketId' => $bucketId, 'callback' => $callback);
        
        return toUrl($url);
    }
    
    
    /**
     * Преименува файла
     *
     * @param object $fRec
     * @param string $newFileName
     * @param bool   $forceDriver
     *
     * @return NULL|bool
     */
    public static function renameFile($fRec, $newFileName, $forceDriver = false)
    {
        // Предишното име на файла
        $oldFileName = $fRec->name;
        
        // Ако имената съвпадат, няма какво да се променя
        if ($newFileName == $oldFileName) {
            
            return ;
        }
        
        // Изтриваме файла от sbf и от модела
        fileman_Download::deleteFileFromSbf($fRec->id);
        
        // Вземамем новото възможно име
        $newFileName = self::getPossibleName($newFileName, $fRec->bucketId);
        
        // Записа, който ще запишем
        $nRec = new stdClass();
        $nRec->id = $fRec->id;
        $nRec->name = $newFileName;
        $nRec->fileHnd = $fRec->fileHnd;
        $nRec->dangerRate = null;
        $saveId = static::save($nRec);
        
        if (!$saveId) {
            
            return false;
        }
        
        fileman_Log::updateLogInfo($nRec->fileHnd, 'rename');
        
        // Ако е форсирано рендирането на драйверите
        if ($forceDriver) {
            
            // Вземаме разширението на новия файл
            $newExt = fileman_Files::getExt($newFileName);
            
            // Вземаме разширението на стария файл
            $oldExt = fileman_Files::getExt($oldFileName);
            
            // Ако е променое разширението
            if ($newExt != $oldExt) {
                
                // Изтриваме всички предишни индекси за файла
                fileman_Indexes::deleteIndexesForData($fRec->dataId);
                
                // Ако има разширение
                if ($newExt) {
                    
                    // Вземаме драйверите
                    $drivers = fileman_Indexes::getDriver($newExt);
                    
                    // Обикаляме всички открити драйвери
                    foreach ($drivers as $drv) {
                        
                        // Стартираме процеса за извличане на данни
                        $drv->startProcessing($fRec);
                    }
                }
            }
        }
        
        return true;
    }
    
    
    /**
     * Връща разширението на файла, от името му
     */
    public static function getExt($name, $maxLen = 10)
    {
        if (($dotPos = mb_strrpos($name, '.')) !== false) {
            $ext = mb_strtolower(mb_substr($name, $dotPos + 1));
            $pattern = '/^[a-zA-Z0-9_$]{1,' . $maxLen . '}$/i';
            if (!preg_match($pattern, $ext)) {
                $ext = '';
            }
        } else {
            $ext = '';
        }
        
        $ext = mb_strtolower($ext);
        
        return $ext;
    }
    
    
    /**
     * Връща типа на файла
     *
     * @param string $fileName - Името на файла
     *
     * @return string - mime типа на файла
     */
    public static function getType($fileName)
    {
        if (($dotPos = mb_strrpos($fileName, '.')) !== false) {
            
            // Файл за mime типове
            include(dirname(__FILE__) . '/data/mimes.inc.php');
            
            // Разширение на файла
            $ext = mb_substr($fileName, $dotPos + 1);
            
            return $mimetypes["{$ext}"];
        }
    }
    
    
    /**
     * Връща стринг с всички версии на файла, който търсим
     */
    public static function getFileVersionsString($id)
    {
        // Масив с всички версии на файла
        $fileVersionsArr = fileman_FileDetails::getFileVersionsArr($id);
        
        foreach ($fileVersionsArr as $fileHnd => $fileInfo) {
            
            // Линк към single' а на файла
            $link = ht::createLink($fileInfo['fileName'], array('fileman_Files', 'single', $fileHnd), false, array('title' => '|*' . $fileInfo['versionInfo']));
            
            // Всеки линк за файла да е на нов ред
            $text .= ($text) ? '<br />' . $link : $link;
        }
        
        return $text;
    }
    
    
    /**
     * Връща името на файла без разширението му
     *
     * @param mixed $fh - Манипулатор на файла или пътя до файла
     *
     * @retun string $name - Името на файла, без разширението
     */
    public static function getFileNameWithoutExt($fh)
    {
        // Ако е подаден път до файла
        if (strstr($fh, '/')) {
            
            // Вземаме името на файла
            $fname = basename($fh);
        } else {
            
            // Ако е подаден манипулатор на файл
            // Вземаме името на файла
            $fRec = static::fetchByFh($fh);
            $fname = $fRec->name;
        }
        
        // Ако има разширение
        if (($dotPos = mb_strrpos($fname, '.')) !== false) {
            $name = mb_substr($fname, 0, $dotPos);
        } else {
            $name = $fname;
        }
        
        return $name;
    }
    
    
    /**
     * Създава масив с името на разширението на подадения файл
     *
     * @param string $fname - Името на файла
     *
     * @return array $nameArr - Масив с разширението и името на файла
     *               string $nameArr['name'] - Името на файла, без разширението
     *               string $nameArr['ext'] - Разширението на файла
     */
    public static function getNameAndExt($fname)
    {
        // Ако има точка в името на файла, вземаме мястото на последната
        if (($dotPos = mb_strrpos($fname, '.')) !== false) {
            
            // Името на файла
            $nameArr['name'] = mb_substr($fname, 0, $dotPos);
            
            // Разширението на файла
            $nameArr['ext'] = mb_substr($fname, $dotPos + 1);
        } else {
            
            // Ако няма разширение
            $nameArr['name'] = $fname;
            $nameArr['ext'] = '';
        }
        
        return $nameArr;
    }
    
    
    /**
     * Ако имаме права за сваляне връща html <а> линк за сваляне на файла.
     */
    public static function getLink($fh, $title = null, $url = null)
    {
        $conf = core_Packs::getConfig('fileman');
        
        //Намираме записа на файла
        $fRec = static::fetchByFh($fh);
        
        //Проверяваме дали сме открили записа
        if (!$fRec) {
            sleep(2);
            Debug::log('Sleep 2 sec. in ' . __CLASS__);
            
            wp($fh);
            
            return false;
        }
        
        // Дали файла го има? Ако го няма, вместо линк, връщаме името му
        $path = static::fetchByFh($fh, 'path');
        
        // Тримваме титлата
        $title = trim($title);
        
        // Ако сме подали
        if ($title) {
            
            // Използваме него за име
            $name = $title;
            
            // Обезопасяваме името
            $name = core_Type::escape($name);
        } else {
            
            // Ако не е подадено, използваме името на файла
            
            //Името на файла
            $name = static::getVerbal($fRec, 'name');
        }
        
        //Разширението на файла
        $ext = static::getExt($fRec->name);
        
        //Иконата на файла, в зависимост от разширението на файла
        $icon = "fileman/icons/16/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
        if (!is_file(getFullPath($icon))) {
            $icon = 'fileman/icons/16/default.png';
        }
        
        $attr = array();
        
        // Икона на линка
        $attr['ef_icon'] = $icon;
        
        // Клас на връзката
        $attr['class'] = 'fileLink';
        
        // Ограничаваме максиманата дължина на името на файла
        $nameFix = str::limitLen($name, 32);
        
        if ($nameFix != $name) {
            $attr['title'] = '|*' . $name;
        }
        
        // Титлата пред файла в plain режим
        $linkFileTitlePlain = tr('Файл') . ': ';
        
        // Ако има данни за файла и съществува
        if (($fRec->dataId) && file_exists($path)) {
            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('text', 'plain');
            
            $gUrl = static::generateUrl($fh, $isAbsolute);
            if (!isset($url)) {
                $url = $gUrl;
            }
            
            // Ако сме в текстов режим
            if (Mode::is('text', 'plain')) {
                
                //Добаваме линка към файла
                $link = "{$linkFileTitlePlain}$name ( ${url} )";
            } else {
                if (Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf')) {
                    
                    // Линка да се отваря на нова страница
                    $attr['target'] = '_blank';
                } else {
                    // Ако линка е в iframe да се отваря в родителския(главния) прозорец
                    $attr['target'] = '_parent';
                    
                    if (self::isDanger($fRec)) {
                        $attr['class'] .= ' dangerFile';
                        $vName = $attr['title'] ? $attr['title'] : $nameFix;
                        $attr['title'] = '|Файл с вирус|*: ' . $vName;
                        if (is_array($url)) {
                            $url['currentTab'] = 'info';
                        }
                    }
                }
                
                $attr['rel'] = 'nofollow';
                
                if (!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')) {
                    if (static::haveRightFor('single', $fRec)) {
                        $attr['class'] .= ' ajaxContext';
                        $attr['name'] = 'context-holder';
                        ht::setUniqId($attr);
                        $replaceId = $attr['id'];
                        unset($attr['name'], $attr['id']);
                        
                        $dataUrl = toUrl(array('fileman_Files', 'getContextMenu', 'fh' => $fh, 'replaceId' => $replaceId), 'local');
                        $attr['data-id'] = $replaceId;
                        $attr['data-url'] = $dataUrl;
                    }
                }

                $link = ht::createLink($nameFix, $url, null, $attr);
                $link->prepend("<span class='fileHolder'>");
                $link->append('</span>');
            }
        } else {
            
            // Ако няма файл
            
            // Ако сме в текстов режим
            if (Mode::is('text', 'plain')) {
                
                // Линка
                $link = $linkFileTitlePlain . $name;
            } else {
                if (!file_exists($path)) {
                    $attr['style'] .= ' color:red;';
                }
                
                //Генерираме името с иконата
                $link = "<span class='linkWithIcon' style=\"" . $attr['style'] . "\"> {$nameFix} </span>";
            }
        }
        
        return $link;
    }
    
    
    /**
     * Проверява дали файла е опасен
     *
     * @param stdClass $rec
     * @param float    $minDangerLevel
     *
     * @return bool
     */
    public static function isDanger($rec, $minDangerLevel = 0.1)
    {
        expect(is_object($rec));
        
        if (isset($rec->dangerRate) && ($rec->dangerRate > $minDangerLevel)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Прекъсваема функция за генериране на URL от манипулатор на файл
     */
    public static function generateUrl_($fh, $isAbsolute)
    {
        $rec = static::fetchByFh($fh);
        
        if (static::haveRightFor('single', $rec) && !Mode::is('forceDownload')) {
            
            //Генерираме връзката
            $url = toUrl(array('fileman_Files', 'single', $fh), $isAbsolute);
        } else {
            //Генерираме връзката за сваляне
            $url = toUrl(array('fileman_Download', 'Download', 'fh' => $fh, 'forceDownload' => true), $isAbsolute);
        }
        
        return $url;
    }
    
    
    /**
     * Връща линк за сваляне, според ID-то
     */
    public static function getLinkById($id)
    {
        $fh = static::fetchField($id, 'fileHnd');
        
        return static::getLink($fh);
    }
    
    
    /**
     * Екшън за обновяване на съдържанието на файла
     *
     * @return NULL|array
     */
    public function act_UpdateFile()
    {
        $fh = Request::get('fileHnd');
        $data = Request::get('data');
        $dataType = Request::get('dataType');
        
        $fRec = $this->fetchByFh($fh);
        expect($fRec);
        
        expect(fileman_Buckets::canAddFileToBucket($fRec->bucketId));
        
        $verId = $this->updateFile($fh, $data, $dataType);
        
        if (Request::get('ajax_mode')) {
            $hitId = rand();
            
            $msg = '|Успешно записахте промените във файла';
            $type = 'notice';
            
            if (!$verId) {
                $msg = '|Грешка при добавяне на нова версия';
                $type = 'warning';
            }
            
            status_Messages::newStatus($msg, $type, null, 60, $hitId);
            $res = status_Messages::getStatusesData(Request::get('hitTime', 'int'), 0, $hitId);
            
            $this->logWrite('Нова версия', $fRec->id);
            
            return $res;
        }
    }
    
    
    /**
     * Обновява съдържаните на файла - добавя нова версия на файла
     *
     * @param string   $fileHnd
     * @param string   $data
     * @param string   $dataType
     * @param NULL|int $userId
     *
     * @return NULL|int
     */
    public static function updateFile($fileHnd, $data, $dataType = 'fileman_import_Base64', $userId = null)
    {
        $fRec = self::fetchByFh($fileHnd);
        
        if (!$fRec) {
            
            return ;
        }
        
        if (!fileman_Buckets::canAddFileToBucket($fRec->bucketId, $userId)) {
            
            return ;
        }
        
        $inftCls = cls::getInterface('fileman_ConvertDataIntf', $dataType);
        
        $nData = $inftCls->convertData($data);
        
        return fileman::addVersionStr($fileHnd, $data);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function makeBtnToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $function = $this->getJsFunctionForAddFile($bucketId, $callback);
        
        return ht::createFnBtn($title, $function, null, $attr);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function makeLinkToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $attr['onclick'] = $this->getJsFunctionForAddFile($bucketId, $callback);
        $attr['href'] = $this->getUrLForAddFile($bucketId, $callback);
        $attr['target'] = 'addFileDialog';
        
        return ht::createElement('a', $attr, $title);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function getJsFunctionForAddFile($bucketId, $callback)
    {
        $url = $this->getUrLForAddFile($bucketId, $callback);
        
        $windowName = 'addFileDialog';
        
        if (Mode::is('screenMode', 'narrow')) {
            $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        } else {
            $args = 'width=400,height=530,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        }
        
        return "sessionStorage.removeItem('disabledRowArr'); openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
    }
    
    
    /**
     * Преобразува линка към single' на файла richtext линк
     *
     * @param int $id - id на записа
     *
     * @return string $res - Линка в richText формат
     */
    public function getVerbalLinkFromClass($id)
    {
        $rec = static::fetch($id);
        $fileHnd = $rec->fileHnd;
        
        return static::getLink($fileHnd);
    }
    
    
    /**
     * Интерфейсна функция
     * От манипулатора на файла връща id на записа
     *
     * @param string
     *
     * @see core_Mvc::unprotectId_()
     */
    public function unprotectId($id)
    {
        // Това е хак, за някои случаи когато има манипулатори, които са защитени допълнителни (в стари системи)
        // Ако манипулатора на файла е по дълъг манипулатора по подразбиране
        $idLen = mb_strlen($id);
        if ($idLen > fileman_Setup::get('HANDLER_LEN') && (($idLen - EF_ID_CHECKSUM_LEN) == fileman_Setup::get('HANDLER_LEN'))) {
            
            // Променлива, в която държим старото състояние
            $old = $this->protectId;
            
            // Задаваме да се защитава
            $this->protectId = true;
            
            // Вземаме id' to
            $id = $this->unprotectId_($id);
            
            // Връщаме стойността
            $this->protectId = $old;
        }
        
        // Вземаме записа от манипулатора на файла
        $rec = static::fetchByFh($id);
        
        // Ако няма запис
        if (!$rec) {
            sleep(2);
            Debug::log('Sleep 2 sec. in ' . __CLASS__);
            
            return false;
        }
        
        return $rec->id;
    }
    
    
    /**
     * Интерфейсна функция
     * Ако е подадено число за id го преобразува в манипулатор
     *
     * @see core_Mvc::protectId()
     */
    public function protectId($id)
    {
        // Ако е подадено id на запис
        if (is_numeric($id)) {
            
            // Вземаме записа
            $rec = static::fetch($id);
            
            // Вместо id използваме манипулатора на файла
            $id = $rec->fileHnd;
        }
        
        return $id;
    }
    
    
    /**
     * Създава нов файл
     *
     * @param string          $name     - Името на файла
     * @param fileman_Buckets $bucketId - Кофата, в която ще създадем
     * @param fileman_Data    $dataId   - Данните за файла
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
        fileman_Files::save($rec, null, 'IGNORE');
        
        // Увеличаваме с единица броя на файловете за които отговаря файла
        fileman_Data::increaseLinks($dataId);
        
        return $rec->fileHnd;
    }
    
    
    /**
     * Екшън, който редиректва към качването на файл в съответния таб
     */
    public function act_AddFile()
    {
        // Защитаваме променливите
        Request::setProtected('bucketId,callback');
        
        // Името на класа
        $class = fileman_DialogWrapper::getLastUploadTab();
        
        // Инстанция на класа
        $class = cls::get($class);
        
        // Вземаме екшъна
        $act = $class->getActionForAddFile();
        
        // Други допълнителни данни
        $bucketId = Request::get('bucketId', 'int');
        $callback = Request::get('callback');
        
        $url = array($class, $act, 'bucketId' => $bucketId, 'callback' => $callback);
        
        return new Redirect($url);
    }
    
    
    /**
     * Екшън за редактиране на файл
     */
    public function act_EditFile()
    {
        // id' то на записа
        $id = Request::get('id', 'int');
        
        // Очакваме да има id
        expect($id);
        
        // Вземаме записите за файла
        $fRec = fileman_Files::fetch($id);
        
        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');
        
        // Проверяваме за права
        $this->requireRightFor('editfile', $fRec);
        
        //URL' то където ще се редиректва при отказ
        $retUrl = getRetUrl();
        $retUrl = ($retUrl) ? ($retUrl) : (array('fileman_Files', 'single', $fRec->fileHnd));
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(null, 'silent');
        
        $form->input('name');
        
        // Размера да е максимален
        $form->setField('name', 'width=100%');
        
        // Ако формата е изпратена без грешки
        if ($form->isSubmitted()) {
            
            // Преименува файла
            self::renameFile($fRec, $form->rec->name, true);
            
            // Редиректваме
            return new Redirect($retUrl);
        }
        
        // Задаваме по подразбиране да е текущото име на файла
        $form->setDefault('name', $fRec->name);
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'name';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        
        // Вербалното име на файла
        $fileName = fileman_Files::getVerbal($fRec, 'name');
        
        // Добавяме титлата на формата
        $form->title = "Редактиране на файл|*:  {$fileName}";
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Екшън за преглед на файла (pdf) в браузъра
     */
    public function act_PreviewFile()
    {
        $this->requireRightFor('single');
        expect($id = Request::get('id', 'int'));
        expect($fRec = static::fetch($id));
        $this->requireRightFor('single', $fRec);
        
        $ext = fileman_Files::getExt($fRec->name);
        
        expect($ext == 'pdf');
        
        fileman_Log::updateLogInfo($fRec->fileHnd, 'preview');
        
        echo fileman_Files::getContent($fRec->fileHnd);
        
        header('Content-type: application/pdf');
        header("Content-Disposition: inline; filename={$fRec->name}.pdf");
        header('Pragma: no-cache');
        header('Expires: 0');
        
        shutdown();
    }
    
    
    /**
     * Екшън връщащ бутоните за контектстното меню
     */
    public function act_getContextMenu()
    {
        $this->requireRightFor('single');
        expect($fh = Request::get('fh', 'varchar'));
        expect($fRec = static::fetchByFh($fh));
        expect($replaceId = Request::get('replaceId', 'varchar'));
        $this->requireRightFor('single', $fRec);
        
        //Разширението на файла
        $ext = fileman_Files::getExt($fRec->name);
        
        //Иконата на файла, в зависимост от разширението на файла
        $icon = "fileman/icons/16/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение
        if (!is_file(getFullPath($icon))) {
            // Използваме иконата по подразбиране
            $icon = 'fileman/icons/16/default.png';
        }
        
        if ($ext == 'pdf') {
            // Вземаме линка към преглед на файла в браузъра
            $urlPreview = array('fileman_Files', 'PreviewFile', $fh);
        } else {
            // Вземаме линка към сингъла на файла таб преглед
            $urlPreview = array('fileman_Files', 'single', $fh);
            $urlPreview['currentTab'] = 'preview';
            $urlPreview['#'] = 'fileDetail';
        }
        
        $tpl = new core_ET();
        $preview = ht::createLink(tr('Преглед'), $urlPreview, null, array('ef_icon' => $icon, 'target' => '_blank', 'title' => 'Преглед на файла', 'class' => 'button'));
        $tpl->append($preview);
        
        // Вземаме линка към сингъла на файла таб информация
        $url = array('fileman_Files', 'single', $fh);
        $url['currentTab'] = 'info';
        $url['#'] = 'fileDetail';
        $infoBtn = ht::createLink(tr('Информация'), $url, null, array('ef_icon' => 'img/16/info-16.png', 'title' => 'Информация за файла', 'class' => 'button'));
        $tpl->append($infoBtn);
        
        $fileLen = '';
        if ($fRec->fileLen) {
            $FileSize = cls::get('fileman_FileSize');
            Mode::push('text', 'plain');
            $fileLen .= ' '. $FileSize->toVerbal($fRec->fileLen);
            Mode::pop('text');
        }
        
        $linkBtn = ht::createLink(tr('Линк'), array('F', 'GetLink', 'fileHnd' => $fh, 'ret_url' => true), null, array('ef_icon' => 'img/16/link.png', 'title' => 'Генериране на линк за сваляне', 'class' => 'button'));
        $tpl->append($linkBtn);
        
        if ($printAttr = $this->checkForPrintBtn($this, $fRec)) {
            if (!$printAttr['disabled']) {
                $printLink = ht::createLink(tr('Печат'), array($this, 'PrintFiles', 'fileHnd' => $fRec->fileHnd, 'ret_url' => true), $printAttr['warning'], array('ef_icon' => 'img/16/printer.png', 'target' => '_blank', 'title' => 'Печат на документа', 'class' => 'button', 'onclick' => 'if ($(".iw-mTrigger").contextMenu) {$(".iw-mTrigger").contextMenu("close");}'));
                $tpl->append($printLink);
            }
        }
        
        $downloadUrl = toUrl(array('fileman_Download', 'Download', 'fh' => $fh, 'forceDownload' => true), false);
        $download = ht::createLink(tr('Сваляне') . ' ' . $fileLen, $downloadUrl, null, array('ef_icon' => 'img/16/down16.png', 'title' => 'Сваляне на файла', 'class' => 'button'));
        $tpl->append($download);
        
        if (core_Users::haveRole('user')) {
            $copy = ht::createLink(tr('Копиране'), 'javascript:void(0);', null, array('ef_icon' => 'img/16/copy16.png', 'title' => 'Копиране на файла', 'class' => 'button', 'onclick' => "copyFileToLast('{$fh}')"));
            $tpl->append($copy);
        }
        
        // Ако сме в AJAX режим
        if (Request::get('ajax_mode')) {
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => $replaceId, 'html' => $tpl->getContent(), 'replace' => true);
            
            $res = array_merge(array($resObj));
            
            return $res;
        }
        
        return $tpl;
    }
    
    
    /**
     * Копира документа в последни
     */
    public function act_CopyToLast()
    {
        expect(haveRole('user'));
        
        $id = Request::get('id');
        
        expect($id);
        
        $rec = $this->fetch($id);
        
        expect($rec);
        
        $lRec = fileman_Log::updateLogInfo($rec->fileHnd, 'preview');
        
        if ($lRec) {
            
            // Сетваме последно отворения таб
            fileman_DialogWrapper::setLastUploadTab('fileman_Log');
            Mode::setPermanent('filemanLogLastOpenedPage', 1);
            
            $msg = tr('Успешно добавихте файла към последни');
        } else {
            $msg = tr('Грешка при копиране');
        }
        
        if (Request::get('ajax_mode')) {
            $statusData = array();
            $statusData['text'] = $msg;
            $statusData['type'] = 'notice';
            $statusData['timeOut'] = 700;
            $statusData['isSticky'] = 0;
            $statusData['stayTime'] = 5000;
            
            $statusObj = new stdClass();
            $statusObj->func = 'showToast';
            $statusObj->arg = $statusData;
            
            return array($statusObj);
        }
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = toUrl(array($this, 'single', $rec->fileHnd));
        }
        
        return new Redirect($retUrl, $msg);
    }
    
    
    /**
     * Преди да запишем, генерираме случаен манипулатор
     */
    public static function on_BeforeSave(&$mvc, &$id, &$rec)
    {
        // Ако липсва, създаваме нов уникален номер-държател
        if (!$rec->fileHnd) {
            do {
                if (16 < $i++) {
                    error('@Unable to generate random file handler', $rec);
                }
                
                $rec->fileHnd = str::getRand(fileman_Setup::get('HANDLER_PTR'));
            } while ($mvc->fetch("#fileHnd = '{$rec->fileHnd}'"));
        } elseif (!$rec->id && $rec->fileHnd) {
            $existingRec = $mvc->fetch(array("#fileHnd = '[#1#]'", $rec->fileHnd));
            
            if ($existingRec) {
                $rec->id = $existingRec->id;
            }
        }
        
        if ($rec->dataId) {
            $dRec = fileman_Data::fetch($rec->dataId);
            $fileLen = $dRec->fileLen;
            $rec->fileLen = $fileLen;
        }
    }
    
    
    /**
     * Какви роли са необходими за качване или сваляне?
     */
    public static function on_BeforeGetRequiredRoles($mvc, &$roles, $action, $rec = null, $userId = null)
    {
        if ($action == 'download' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForDownload');
        } elseif ($action == 'add' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForAdding');
        } else {
            
            return;
        }
        
        return false;
    }


    /**
     * Какви роли са необходими за качване или сваляне?
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = null, $userId = null)
    {
        if ($action == 'editfile' && !haveRole('powerUser')) {
            if ($rec->createdBy != $userId) {
                $roles = 'no_one';
            }
        }
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        try {
            $row->name = static::getLink($rec->fileHnd);
        } catch (core_Exception_Expect $e) {
            // Вместо линк използваме името
        }
    }
    
    
    /**
     * Изпълнява се преди подготовката на single изглед
     */
    public function on_BeforeRenderSingle($mvc, $tpl, &$data)
    {
        $row = &$data->row;
        $rec = $data->rec;
        
        expect($rec->dataId, 'Няма данни за файла');
        
        //Разширението на файла
        $ext = fileman_Files::getExt($rec->name);
        
        //Иконата на файла, в зависимост от разширението на файла
        $icon = "fileman/icons/16/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
        if (!is_file(getFullPath($icon))) {
            $icon = 'fileman/icons/16/default.png';
        }
        
        //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml
        $isAbsolute = (boolean) Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf');
        
        $dangerFileClass = '';
        if (!$isAbsolute && fileman_Files::isDanger($rec)) {
            $dangerFileClass .= ' dangerFile';
        }
        
        $fileNavArr = Mode::get('fileNavArr');
        
        $prevUrl = $fileNavArr[$rec->fileHnd]['prev'];
        $nextUrl = $fileNavArr[$rec->fileHnd]['next'];
        
        // Показваме селект с всички файлове
        if (!$dangerFileClass && $fileNavArr[$rec->fileHnd]['allFilesArr'] && countR($fileNavArr[$rec->fileHnd]['allFilesArr']) > 1) {
            $form = cls::get('core_Form');
            $form->fnc('selectFile', 'enum()', 'input=input');
            
            $form->addAttr('selectFile', array('onchange' => 'document.location = this.options[this.selectedIndex].value;'));
            
            $form->view = 'horizontal';
            
            $form->layout = "<form [#FORM_ATTR#] >[#FORM_FIELDS#][#FORM_TOOLBAR#][#FORM_HIDDEN#]</form>\n";
            
            foreach ($fileNavArr[$rec->fileHnd]['allFilesArr'] as $fUrl => $fName) {
                if ($fileNavArr[$rec->fileHnd]['current'] == $fUrl) {
                    $fName = type_Varchar::escape($data->rec->name);
                }
                $eArr[$fUrl] = str::limitLen($fName, 32);
            }
            
            $form->setOptions('selectFile', $eArr);
            $form->setDefault('selectFile', $fileNavArr[$rec->fileHnd]['current']);
            
            $row->fileName = $form->renderHtml();
        } else {
            // Вербалното име на файла
            $row->fileName = "<span class='linkWithIcon{$dangerFileClass}' style=\"margin-left:-7px; " . ht::getIconStyle($icon) . '">';
            if ($dangerFileClass) {
                $row->fileName .= tr('Файл с вирус|*: ');
            }
            $row->fileName .= $mvc->getVerbal($rec, 'name') . '</span>';
        }
        
        if ($prevUrl = $fileNavArr[$rec->fileHnd]['prev']) {
            $row->fileName .= ht::createLink('', $prevUrl, false, 'ef_icon=img/16/prev.png,style=margin-left:10px;');
        }
        
        if ($nextUrl = $fileNavArr[$rec->fileHnd]['next']) {
            $row->fileName .= ht::createLink('', $nextUrl, false, 'ef_icon=img/16/next.png,style=margin-left:6px;');
        }
        
        // Показваме и източника на файла
        if ($fileNavArr[$rec->fileHnd]['src']) {
            if ($fileNavArr[$rec->fileHnd]['srcDirName']) {
                $row->fileName .= ' « ' . type_Varchar::escape($fileNavArr[$rec->fileHnd]['srcDirName']);
            }
            
            $row->fileName .= ' « ' . self::getLink($fileNavArr[$rec->fileHnd]['src']);
        }
        
        // Масив с линка към папката и документа на първата достъпна нишка, където се използва файла
        $pathArr = static::getFirstContainerLinks($rec);

        // Ако има такъв документ
        if (countR($pathArr)) {

            // Пътя до файла и документа
            $path = ' « ' . $pathArr['firstContainer']['content'] . ' « ' . $pathArr['folder']['content'];
            
            // TODO името на самия документ, където се среща но става много дълго
            //$pathArr['container']
            
            // Пред името на файла добаваме папката и документа, къде е използван
            $row->fileName .= $path;
        }
        
        // Версиите на файла
//        $row->versions = static::getFileVersionsString($rec->id);
    }
    
    
    public function on_AfterPrepareSingle($mvc, &$tpl, $data)
    {
        // Манипулатора на файла
        $fh = $data->rec->fileHnd;
        
        // Подготвяме данните
        fileman_Indexes::prepare($data, $fh);
        
        // Задаваме екшъна
        if (!$data->action) {
            $data->action = 'single';
        }
    }
    
    
    public function on_AfterRenderSingle($mvc, &$tpl, &$data)
    {
        // Манипулатора на файла
        $fh = $data->rec->fileHnd;
        
        // Текущия таб
        $data->currentTab = Request::get('currentTab');
        
        // Рендираме табовете
        $fileInfo = fileman_Indexes::render($data);
        
        // Добавяме табовете в шаблона
        $tpl->append($fileInfo, 'fileDetail');
        
        jquery_Jquery::run($tpl, 'setFilemanPreviewSize()');
        
        // Отбелязваме като разгледан
        fileman_Log::updateLogInfo($fh, 'preview');
        
        bgerp_Notifications::clear(array($mvc, 'single', $fh));
    }
    
    
    /**
     * Проверява дали да се покаже бутона за печат. Предава и параметрите за бутона: warning, disabled
     */
    public function checkForPrintBtn($mvc, $rec, $activeProcessing = false)
    {
        $ext = self::getExt($rec->name);
        
        if ($mvc->haveRightFor('printfiles', $rec) && $ext) {
            // Вземаме уеб-драйверите за това файлово разширение
            $webdrvArr = fileman_Indexes::getDriver($ext, $rec->name);
            
            $canPrint = false;

            foreach ($webdrvArr as $drv) {
                if (!$drv) {
                    continue;
                }
                
                if (!cls::load($drv, true)) {
                    continue;
                }

                if ($drv::$defaultTab == 'preview') {
                    if ($activeProcessing) {
                        $drv->startProcessing($rec);
                    }
                    $canPrint = true;
                    
                    break;
                }
            }
            
            if ($canPrint) {
                $jpgArr = fileman_Indexes::getInfoContentByFh($rec->fileHnd, 'jpg');
                
                // Ако има грешка при конвертирането
                $disabled = '';
                if ((is_object($jpgArr) && $jpgArr->errorProc)) {
                    $disabled = ',disabled';
                }
                
                $warning = '';
                if (is_array($jpgArr) && empty($jpgArr)) {
                    $warning = 'Няма данни за отпечатване';
                }
                
                if (is_array($jpgArr) && $jpgArr['otherPagesCnt']) {
                    $all = countR($jpgArr);
                    $all--;
                    
                    $warning = "|Ще се отпечатат първите|* {$all} |страници|*. |Ще се пропуснат|* {$jpgArr['otherPagesCnt']} |страници|*.";
                }
                
                return array('disabled' => $disabled, 'warning' => $warning);
            }
        }
        
        return false;
    }
    
    
    public function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Добавяме бутон за сваляне
        $downloadUrl = toUrl(array('fileman_Download', 'Download', 'fh' => $data->rec->fileHnd, 'forceDownload' => true), false);
        $data->toolbar->addBtn('Сваляне', $downloadUrl, 'id=btn-download', 'ef_icon = img/16/down16.png, order=8');
        $data->toolbar->addBtn('Линк', array('F', 'GetLink', 'fileHnd' => $data->rec->fileHnd, 'ret_url' => true), 'id=btn-downloadLink', 'ef_icon = img/16/link.png, title=Генериране на линк за сваляне, order=9');
        
        if ($mvc->haveRightFor('regenerate', $data->rec->id)) {
            $data->toolbar->addBtn('Регенериране', array($mvc, 'Regenerate', 'fileHnd' => $data->rec->fileHnd, 'ret_url' => true), 'id=btn-regenerate', 'ef_icon = img/16/recycle.png, title=Повторна обработка на файла, order=19.99, row=2');
        }

        if ($mvc->haveRightFor('printfiles', $data->rec->id)) {
            $data->toolbar->addBtn('Печат', array($mvc, 'PrintFrame', 'fileHnd' => $data->rec->fileHnd, 'currentTab' => Request::get('currentTab'), 'ret_url' => true), 'id=btnPrint, target=_blank', "ef_icon = img/16/printer.png, title=Печат на изгледа");
        }

        $nameAndExt = $mvc->getNameAndExt($data->rec->name);
        $ext = strtolower($nameAndExt['ext']);
        if ($ext == 'pdf') {
            $data->toolbar->addBtn('Преглед', array($mvc, 'PreviewFile', $data->rec->fileHnd), 'id=btnPreview, target=_blank', "ef_icon = fileman/icons/16/{$ext}.png, title=Преглед на файла,row=2");
        }

        // Очакваме да има такъв файл
        expect($fRec = $data->rec);
        
        // Вземаме всички класове, които имплементират интерфейса
        $classesArr = core_Classes::getOptionsByInterface('fileman_FileActionsIntf');
        
        // Обхождаме всички класове, които имплементират интерфейса
        foreach ($classesArr as $className) {
            
            // Вземаме масива с документите, които може да създаде
            $arrCreate = $className::getActionsForFile($fRec);

            if (is_array($arrCreate)) {
                // Обхождаме масива
                foreach ($arrCreate as $id => $arr) {

                    // Ако има полета, създаваме бутона
                    if (count($arr) && $className::haveRightFor('add')) {
                        $data->toolbar->addBtn($arr['title'], $arr['url'], 'row=2,id=' . $id . ',ef_icon=' . $arr['icon'], $arr['btnParams']);
                    }
                }
            }
        }
        
        if ($mvc->haveRightFor('editfile', $data->rec->id)) {
            $data->toolbar->addBtn('Преименуване', array($mvc, 'editFile', $data->rec->fileHnd, 'ret_url' => true), 'id=btn-rename', 'ef_icon = img/16/edit-icon.png, title=Преименуване на файла, row=2');
        }
    }


    /**
     *
     * @return stdClass
     */
    public function act_Single()
    {
        if (core_Users::isContractor()) {
            Mode::set('noWrapper', true);

            $res = new ET();
            $res->append("<div class='filemanSingle'>");
            $res->append(parent::act_Single());
            $res->append("</div>");

            return  $res;
        }

        return parent::act_Single();
    }

    
    /**
     * Екшън за отпечатване
     */
    public function act_PrintFiles()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('printfiles');
        
        $fileHnd = Request::get('fileHnd');
        
        expect($fileHnd);
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        expect($fRec);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('printfiles', $fRec);
        
        // Вземаме масива с изображенията
        $jpgArr = fileman_Indexes::getInfoContentByFh($fileHnd, 'jpg');
        
        // Ако няма такъв запис
        if ($jpgArr === false) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }
        
        // Ако е обект и има съобщение за грешка
        if (is_object($jpgArr) && $jpgArr->errorProc) {
            
            // Сменяме мода
            Mode::set('wrapper', 'page_PreText');
            
            // Връщаме съобщението за грешка
            return tr($jpgArr->errorProc);
        }
        
        Mode::set('wrapper', 'page_Print');
        Mode::set('printing');
        
        if (($jpgArr) && (!empty($jpgArr))) {
            $ext = self::getExt($fRec->name);
            
            // Вземаме уеб-драйверите за това файлово разширение
            $webdrvArr = fileman_Indexes::getDriver($ext, $fRec->name);
            
            foreach ($webdrvArr as $drv) {
                if (!$drv) {
                    continue;
                }
                
                // Вземаме височината и широчината
                $thumbWidthAndHeightArr = $drv->getPreviewWidthAndHeight();
                
                if (!empty($thumbWidthAndHeightArr)) {
                    break;
                }
            }
            
            $preview = new ET('[#THUMB_IMAGE#]');
            
            // Атрибути на thumbnail изображението
            $attr = array('class' => 'webdrv-preview', 'style' => 'margin: 0; display: block;');
            
            unset($jpgArr['otherPagesCnt']);
            
            $multiplier = fileman_Setup::get('WEBDRV_PREVIEW_MULTIPLIER');
            
            $verbName = 'Preview';
            
            if ($multiplier > 1) {
                foreach ($thumbWidthAndHeightArr as &$wh) {
                    $wh *= $multiplier;
                }
                $verbName = 'Preview X ' . $multiplier;
            }
            
            foreach ($jpgArr as $jpgFh) {
                $imgInst = new thumb_Img(array($jpgFh, $thumbWidthAndHeightArr['width'], $thumbWidthAndHeightArr['height'], 'fileman', 'verbalName' => $verbName));
                
                // Добавяме към preview' то генерираното изображение
                $preview->append($imgInst->createImg($attr), 'THUMB_IMAGE');
            }
            
            return $preview;
        }
    }


    /**
     * Отпечатване на изгледа
     *
     * @return mixed
     */
    function act_PrintFrame()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('printfiles');

        $fileHnd = Request::get('fileHnd');

        expect($fileHnd);

        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);

        expect($fRec);

        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('printfiles', $fRec);

        $currentTab = Request::get('currentTab');

        $data = new stdClass();

        // Текущия таб
        $data->currentTab = Request::get('currentTab');

        // Рендираме табовете
        fileman_Indexes::prepare($data, $fileHnd);
        $fileInfo = fileman_Indexes::render($data);

        Mode::set('wrapper', 'page_Print');
        Mode::set('printing');

        if (stripos($fileInfo, '<iframe') !== false) {
            $fileInfo->append("function runFramePrinting(){document.getElementsByTagName('iframe')[0].contentWindow.print();};", 'SCRIPTS');
            $fileInfo->append("function fixIframeStyles(){
                                                            var head = document.getElementsByTagName('iframe')[0].contentWindow.document.getElementsByTagName('head')[0];
                                                            var s = document.createElement('style');
                                                            s.setAttribute('type', 'text/css');
                                                            s.appendChild(document.createTextNode('#imgBg {padding: 0px !important; background-image: none !important;}'));
                                                            head.appendChild(s);
                                        }", 'SCRIPTS');

            $fileInfo->append("runOnLoad(fixIframeStyles);", 'SCRIPTS');
            $fileInfo->append("runOnLoad(runFramePrinting);", 'SCRIPTS');
            $fileInfo->append(".webdrvFieldset, .webdrvTabBody, .tab-page, .tab-control, .printing {width: 100% !important; height: 100% !important;}", 'STYLES');

            Mode::set('runPrinting', false);
        }

        $fileInfo->append(".row-holder, .legend {display: none !important;}", 'STYLES');
        $fileInfo->append(".webdrvFieldset {margin: 0 !important; padding: 0 !important; box-shadow: none !important; border: none !important;}", 'STYLES');
        $fileInfo->append(".webdrvTabBody {top: 0px !important;}", 'STYLES');

        return $fileInfo;
    }
    
    
    /**
     * Регенериране на индексите за файла
     */
    public function act_Regenerate()
    {
        $this->requireRightFor('regenerate');
        
        $fileHnd = Request::get('fileHnd');
        
        expect($fileHnd);
        
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        expect($fRec);
        
        $this->requireRightFor('regenerate', $fRec);
        
        fileman_Indexes::deleteIndexesForData($fRec->dataId);
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array($this, 'single', $fRec->fileHnd);
        }
        
        return new Redirect($retUrl, '|Стартирано регенериране на индексите за файла');
    }


    /**
     * След подготвяне на филтъра
     *
     * @param fileman_Files $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('fileman/tpl/FilesFilterForm.shtml')));
        
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('search', 'varchar', 'caption=Търсене,input,silent,recently');
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=admin, rolesForTeams=admin)', 'caption=Потребител,input,silent,autoFilter');
        
        // В хоризонтален вид
        $data->listFilter->view = 'vertical';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'search, usersSearch';
        
        $data->listFilter->input('usersSearch, search', 'silent');
        
        // Ако не е избран потребител по подразбиране
        if (!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        $filter = $data->listFilter->rec;
        
        $usersArr = type_Keylist::toArray($filter->usersSearch);
        if ($usersArr[-1]) {
            $data->query->isSlowQuery = true;
            $data->query->useCacheForPager = true;
        }
        $mvc->prepareFilesQuery($data->query, $usersArr, $data->groupByDateField);
        
        $data->query->orderBy('modifiedOn', 'DESC');
        
        // Тримваме името
        $search = trim($filter->search);
        
        // Ако има съдържание
        if (strlen($search)) {
            $data->query->EXT('searchKeywords', 'fileman_Data', 'externalKey=dataId');
            plg_Search::applySearch($search, $data->query, 'searchKeywords');
        }
    }
    
    
    /**
     * Подготвя заявка за подреждане на файловете, според полседно използването им
     *
     * @param core_Query  $query
     * @param array       $usersArr
     * @param string|NULL $groupByDateField
     */
    public static function prepareFilesQuery($query, $usersArr, &$groupByDateField = null)
    {
        $userArrImp = implode(',', $usersArr);
        
        // Ако има избран повече от един потребител, ги подреждаме по послендо използване
        if (countR($usersArr) > 1) {
            $groupByDateField = 'lastUse';
            $query->EXT('lastUse', 'fileman_Data', 'externalName=lastUse, externalKey=dataId');
            $query->orderBy('#lastUse', 'DESC');
            
            if (!$usersArr[-1]) {
                $query->where("#createdBy IN ({$userArrImp})");
            }
        } else {
            
            // Подреждаме по поселдно използване от fileman_Log таблицата
            
            $groupByDateField = 'lastOn';
            $selfDbTableName = self::getDbTableName();
            $logDbTableName = fileman_log::getDbTableName();
            $idFieldName = str::phpToMysqlName('id');
            $fileIdFieldName = str::phpToMysqlName('fileId');
            $userIdFieldName = str::phpToMysqlName('userId');
            
            // Подреждаме файловете по-последно използване от съответния потребител
            $query->EXT('lastOn', 'fileman_log', 'externalName=lastOn');
            $query->where("`{$selfDbTableName}`.`{$idFieldName}` = `{$logDbTableName}`.`{$fileIdFieldName}`");
            $query->orderBy('#lastOn', 'DESC');
            
            // Файловете от последния потребител
            
            $query->EXT('userId', 'fileman_Log', 'externalName=userId');
            $query->where("`{$logDbTableName}`.`{$userIdFieldName}` IN ({$userArrImp})");
        }
    }
    
    
    /**
     *
     *
     * @param fileman_Files $mvc
     * @param object        $res
     * @param object        $data
     */
    public static function on_AfterPrepareListSummary($mvc, &$res, &$data)
    {
        // Ако няма заявка, да не се изпълнява
        if (!$data->listSummary->query) {
            
            return ;
        }
        
        // Брой записи
        $fileCnt = $data->listSummary->query->count();
        
        // Размер на всички файлове
        $data->listSummary->query->XPR('sumLen', 'int', 'SUM(#fileLen)');
        $rec = $data->listSummary->query->fetch();
        $fileLen = $rec->sumLen;
        
        if (!isset($data->listSummary->statVerb)) {
            $data->listSummary->statVerb = array();
        }
        
        $Files = cls::get('fileman_FileSize');
        $Int = cls::get('type_Int');
        
        // Размер на всички файлове
        if ($fileLen) {
            $data->listSummary->statVerb['fileSize'] = $Files->toVerbal($fileLen);
        }
        
        // Броя на файловете
        if ($fileCnt) {
            $data->listSummary->statVerb['fileCnt'] = $Int->toVerbal($fileCnt);
        }
        
        // Статистика за БД
        if (haveRole('ceo, admin, debug')) {
            $db = cls::get('core_Db');
            
            $sqlInfo = $db->getDBInfo();
            
            if ($sqlInfo) {
                $data->listSummary->statVerb['sqlSize'] = $Files->toVerbal($sqlInfo['SIZE']);
                $data->listSummary->statVerb['rowCnt'] = $Int->toVerbal($sqlInfo['ROWS']);
                $data->listSummary->statVerb['tablesCnt'] = $Int->toVerbal($sqlInfo['TABLES']);
            }
        }
    }
    
    
    /**
     *
     *
     * @param fileman_Files $mvc
     * @param core_Et       $tpl
     * @param core_Et       $data
     */
    public static function on_AfterRenderListSummary($mvc, &$tpl, &$data)
    {
        // Ако няма данни, няма да се показва нищо
        if (!$data->listSummary->statVerb) {
            
            return ;
        }
        
        // Зареждаме и подготвяме шаблона
        $tpl = getTplFromFile(('fileman/tpl/FilesSummary.shtml'));
        
        // Заместваме статусите на обажданията
        $tpl->placeArray($data->listSummary->statVerb);
        
        // Премахваме празните блокове
        $tpl->removeBlocks();
        $tpl->append2master();
    }
    
    
    /**
     * Задава файла с посоченото име в посочената кофа
     *
     * @deprecated
     */
    public function setFile($path, $bucket, $fname = null, $force = false)
    {
        wp('deprecated');
        
        if ($fname === null) {
            $fname = basename($path);
        }
        
        $Buckets = cls::get('fileman_Buckets');
        
        expect($bucketId = $Buckets->fetchByName($bucket));
        
        $fh = $this->fetchField(array("#name = '[#1#]' AND #bucketId = {$bucketId}",
            $fname,
        ), 'fileHnd');
        
        if (!$fh) {
            $fh = $this->addNewFile($path, $bucket, $fname);
        } elseif ($force) {
            $this->setContent($fh, $path);
        }
        
        return $fh;
    }
    
    
    /**
     * Добавя нов файл в посочената кофа
     *
     * @deprecated
     */
    public function addNewFile($path, $bucket, $fname = null)
    {
        wp('deprecated');
        
        if ($fname === null) {
            $fname = basename($path);
        }
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        if ($dataId = $this->Data->absorbFile($path, false)) {
            
            // Проверяваме името на файла
            $fh = $this->checkFileName($dataId, $bucketId, $fname);
        }
        
        // Ако няма манипулатор
        if (!$fh) {
            $fh = $this->createDraftFile($fname, $bucketId);
            
            $this->setContent($fh, $path);
        }
        
        // Ако има манипулатор
        if ($fh) {
            
            // Обновяваме лога за използване на файла
            fileman_Log::updateLogInfo($fh, 'upload');
        }
        
        return $fh;
    }
    
    
    /**
     * Добавя нов файл в посочената кофа от стринг
     *
     * @deprecated
     */
    public function addNewFileFromString($string, $bucket, $fname = null)
    {
        wp('deprecated');
        
        $me = cls::get('fileman_Files');
        
        if ($fname === null) {
            $fname = basename($path);
        }
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        if ($dataId = $this->Data->absorbString($string, false)) {
            
            // Проверяваме името на файла
            $fh = $this->checkFileName($dataId, $bucketId, $fname);
        }
        
        // Ако няма манипулатор
        if (!$fh) {
            $fh = $me->createDraftFile($fname, $bucketId);
            
            $me->setContentFromString($fh, $string);
        }
        
        // Ако има манипулатор на файла
        if ($fh) {
            
            // Обновяваме лога за използване на файла
            fileman_Log::updateLogInfo($fh, 'upload');
        }
        
        return $fh;
    }
    
    
    /**
     * Създаваме нов файл в посочената кофа
     *
     * @deprecated
     */
    public function createDraftFile($fname, $bucketId)
    {
        wp('deprecated');
        
        expect($bucketId, 'Очаква се валидна кофа');
        
        $rec = new stdClass();
        $rec->name = $this->getPossibleName($fname, $bucketId);
        $rec->bucketId = $bucketId;
        $rec->state = 'draft';
        
        $this->save($rec);
        
        return $rec->fileHnd;
    }
    
    
    /**
     * Задава данните на даден файл от съществуващ файл в ОС
     *
     * @deprecated
     */
    public function setContent($fileHnd, $osFile)
    {
        wp('deprecated');
        
        $dataId = $this->Data->absorbFile($osFile);
        
        return $this->setData($fileHnd, $dataId);
    }
    
    
    /**
     * Задава данните на даден файл от стринг
     *
     * @deprecated
     */
    public function setContentFromString($fileHnd, $string)
    {
        wp('deprecated');
        
        $dataId = $this->Data->absorbString($string);
        
        return $this->setData($fileHnd, $dataId);
    }
    
    
    /**
     * Ако имаме нови данни, които заменят стари
     * такива указваме, че старите са стара версия
     * на файла и ги разскачаме от файла
     *
     * @deprecated
     */
    public function setData($fileHnd, $newDataId)
    {
        wp('deprecated');
        
        $rec = $this->fetch("#fileHnd = '{$fileHnd}'");
        
        // Ако новите данни са същите, като старите
        // нямаме смяна
        if ($rec->dataId == $newDataId) {
            
            return $rec->dataId;
        }
        
        // Ако имаме стари данни, изпращаме ги в историята
        if ($rec->dataId) {
            $verRec->fileHnd = $fileHnd;
            $verRec->dataId = $rec->dataId;
            $verRec->from = $rec->modifiedOn;
            $verRec->to = dt::verbal2mysql();
            $this->Versions->save($verRec);
            
            // Намаляваме с 1 броя на линковете към старите данни
            $this->Data->decreaseLinks($rec->dataId);
        }
        
        // Записваме новите данни
        $rec->dataId = $newDataId;
        $rec->state = 'active';
        
        // Генерираме събитие преди съхраняването на записа с добавения dataId
        $this->invoke('BeforeSaveDataId', array($rec));
        
        $this->save($rec);
        
        // Ако има запис
        if ($rec) {
            
            // Обновяваме лога за използване на файла
            fileman_Log::updateLogInfo($rec, 'upload');
        }
        
        // Увеличаваме с 1 броя на линковете към новите данни
        $this->Data->increaseLinks($newDataId);
        
        return $rec->dataId;
    }
    
    
    /**
     * Копира данните от един файл на друг файл
     *
     * @deprecated
     */
    public function copyContent($sHnd, $dHnd)
    {
        wp('deprecated');
        
        $sRec = $this->fetch("#fileHnd = '{$sHnd}'");
        
        if ($sRec->state != 'active') {
            
            return false;
        }
        
        return $this->setData($dHnd, $sRec->dataId);
    }
    
    
    /**
     * Проверява дали името на подадения файл не се съдържа в същата кофа със същите данни.
     * Ако същия файл е бил качен връща манипулатора на файла
     *
     * @param fileman_Data    $dataId        - id' то на данните на файка
     * @param fileman_Buckets $bucketId      - id' то на кофата
     * @param string          $inputFileName - Името на файла, който искаме да качим
     *
     * @return fileman_Files $fileHnd - Манипулатора на файла
     *
     * @deprecated
     */
    public static function checkFileName($dataId, $bucketId, $inputFileName)
    {
        wp('deprecated');
        
        // Вземаме всички файлове, които са в съответната кофа и със същите данни
        $query = static::getQuery();
        $query->where("#bucketId = '{$bucketId}' AND #dataId = '{$dataId}'");
        $query->show('fileHnd, name');
        
        // Масив с името на файла и разширението
        $inputFileNameArr = static::getNameAndExt($inputFileName);
        
        // Обикаляме всички открити съвпадения
        while ($rec = $query->fetch($where)) {
            
            // Ако имената са еднакви
            if ($rec->name == $inputFileName) {
                
                return $rec->fileHnd;
            }
            
            // Вземаме името на файла и разширението
            $recFileNameArr = static::getNameAndExt($rec->name);
            
            // Намираме името на файла до последния '_'
            if (($underscorePos = mb_strrpos($recFileNameArr['name'], '_')) !== false) {
                $recFileNameArr['name'] = mb_substr($recFileNameArr['name'], 0, $underscorePos);
            }
            
            // Ако двата масива са еднакви
            if ($inputFileNameArr == $recFileNameArr) {
                
                // Връщаме манипулатора на файла
                return $rec->fileHnd;
            }
        }
        
        return false;
    }
    
    
    /**
     * Превръща масив с fileHandler' и в масив с id' тата на файловете
     *
     * @param array $fh - Масив с манупулатори на файловете
     *
     * @return array $newArr - Масив с id' тата на съответните файлове
     *
     * @deprecated
     */
    public static function getIdFromFh($fh)
    {
        wp('deprecated');
        
        //Преобразуваме към масив
        $fhArr = (array) $fh;
        
        //Създаваме променлива за id' тата
        $newArr = array();
        
        foreach ($fhArr as $val) {
            
            //Ако няма стойност, прескачаме
            if (!$val) {
                continue;
            }
            
            //Ако стойността не е число
            if (!is_numeric($val)) {
                
                //Вземема id'то на файла
                try {
                    $id = static::fetchByFh($val, 'id');
                } catch (core_exception_Expect $e) {
                    //Ако няма такъв fh, тогава прескачаме
                    continue;
                }
            } else {
                
                //Присвояваме променливата, като id
                $id = $val;
            }
            
            //Записваме в масива
            $newArr[$id] = $id;
        }
        
        return $newArr;
    }
}
