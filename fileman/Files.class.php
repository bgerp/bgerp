<?php


/**
 * Какъв е шаблона за манипулатора на файла?
 */
defIfNot('FILEMAN_HANDLER_PTR', '$*****');


/**
 * Каква да е дължината на манипулатора на файла?
 */
defIfNot('FILEMAN_HANDLER_LEN', strlen(FILEMAN_HANDLER_PTR));


/**
 * Клас 'fileman_Files' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Files extends core_Master 
{
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'fileman_FileDetails';
    
    
    /**
     * 
     */
    protected $canEdit = 'no_one';
    
    
    /**
     * Всички потребители могат да разглеждат файлове
     */
    protected $canSingle = 'powerUser';
    
    
    /**
     * 
     */
    protected $canDelete = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 * @todo След като се направи да се показват само файловете на потребителя
	 */
	protected $canList = 'powerUser';
    
	
    /**
     * 
     */
    public $singleLayoutFile = 'fileman/tpl/SingleLayoutFile.shtml';
    
    
    /**
     * 
     */
    protected $canAdd = 'no_one';
    
    
    /**
     * Заглавие на модула
     */
    public $title = 'Файлове';
    
    
    /**
     * 
     */
    public $listFields = 'name=Файл->Име, fileLen=Файл->Размер, bucketId, createdOn, createdBy';
    
    
    /**
     * 
     */
    public $loadList = 'plg_Sorting';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Файлов манипулатор - уникален 8 символно/цифров низ, започващ с буква.
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD("fileHnd", "varchar(" . strlen(FILEMAN_HANDLER_PTR) . ")",
            array('notNull' => TRUE, 'caption' => 'Манипулатор'));
        
        // Име на файла
        $this->FLD("name", "varchar(255)",
            array('notNull' => TRUE, 'caption' => 'Файл'));
        
        // Данни (Съдържание) на файла
        $this->FLD("dataId", "key(mvc=fileman_Data)",
            array('caption' => 'Данни Id'));
        
        // Клас - притежател на файла
        $this->FLD("bucketId", "key(mvc=fileman_Buckets, select=name)",
            array('caption' => 'Кофа'));
        
        // Състояние на файла
        $this->FLD("state", "enum(draft=Чернова,active=Активен,rejected=Оттеглен)",
            array('caption' => 'Състояние', 'column' => 'none'));
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,plg_Modified,Data=fileman_Data,Buckets=fileman_Buckets,' .
            'Download=fileman_Download,Versions=fileman_Versions,fileman_Wrapper');
        
        // 
        $this->FLD('extractedOn', 'datetime(format=smartTime)', 'caption=Екстрактнато->На,input=none,column=none');
        
        $this->FLD("fileLen", "fileman_FileSize", 'caption=Размер');
        
        // Индекси
        $this->setDbUnique('fileHnd');
        $this->setDbUnique('name,bucketId', 'uniqName');
        $this->setDbIndex('dataId,bucketId', 'indexDataId');
        $this->setDbIndex('createdBy');
    }
    
    
    /**
     * Преди да запишем, генерираме случаен манипулатор
     */
    static function on_BeforeSave(&$mvc, &$id, &$rec)
    {
        // Ако липсва, създаваме нов уникален номер-държател
        if(!$rec->fileHnd) {
            do {
                
                if(16 < $i++) error('@Unable to generate random file handler', $rec);
                
                $rec->fileHnd = str::getRand(FILEMAN_HANDLER_PTR);
            } while($mvc->fetch("#fileHnd = '{$rec->fileHnd}'"));
         } elseif(!$rec->id) {
            
              $existingRec = $mvc->fetch("#fileHnd = '{$rec->fileHnd}'");

            
            $rec->id = $existingRec->id;
        }
        
        if ($rec->dataId) {
            $dRec = fileman_Data::fetch($rec->dataId);
            $fileLen = $dRec->fileLen;
            $rec->fileLen = $fileLen;
        }
    }
    
    
    /**
     * Задава файла с посоченото име в посочената кофа
     */
    function setFile($path, $bucket, $fname = NULL, $force = FALSE)
    {
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        expect($bucketId = $Buckets->fetchByName($bucket));
        
        $fh = $this->fetchField(array("#name = '[#1#]' AND #bucketId = {$bucketId}",
                $fname,
            ), "fileHnd");
        
        if(!$fh) {
            $fh = $this->addNewFile($path, $bucket, $fname);
        } elseif($force) {
            $this->setContent($fh, $path);
        }
        
        return $fh;
    }
    
    
    /**
     * Добавя нов файл в посочената кофа
     */
    function addNewFile($path, $bucket, $fname = NULL)
    {
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        if($dataId = $this->Data->absorbFile($path, FALSE)) {
            
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
     */
    function addNewFileFromString($string, $bucket, $fname = NULL)
    {
        $me = cls::get('fileman_Files');
        
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        if($dataId = $this->Data->absorbString($string, FALSE)) {

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
     */
    function createDraftFile($fname, $bucketId)
    {
        expect($bucketId, 'Очаква се валидна кофа');
        
        $rec = new stdClass();
        $rec->name = $this->getPossibleName($fname, $bucketId);
        $rec->bucketId = $bucketId;
        $rec->state = 'draft';
        
        $this->save($rec);
        
        return $rec->fileHnd;
    }


    /**
     * Променя името на съществуващ файл
     * Връща новото име, което може да е различно от желаното ново име
     */
    static function rename($id, $newName) 
    {
        expect($rec = static::fetch($id));

        if($rec->name != $newName) { 
            $rec->name = static::getPossibleName($newName, $rec->bucketId); 
            static::save($rec);
        }

        return $rec->name;
    }
    
    
    /**
     * Връща първото възможно има, подобно на зададеното, така че в този
     * $bucketId да няма повторение на имената
     */
    static function getPossibleName($fname, $bucketId)
    {
        // Конвертираме името към такова само с латински букви, цифри и знаците '-' и '_'
        $fname = static::normalizeFileName($fname);
        
        // Циклим докато генерираме име, което не се среща до сега
        $fn = $fname;
        
        if(($dotPos = strrpos($fname, '.')) !== FALSE) {
            $firstName = substr($fname, 0, $dotPos);
            $ext = substr($fname, $dotPos);
        } else {
            $firstName = $fname;
            $ext = '';
        }
        
        // Двоично търсене за свободно име на файл
        $i = 1;
        
        while(self::fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id')) {
            $fn = $firstName . '_' . $i . $ext;
            $i = $i * 2;
        }
        
        // Търсим първото незаето положение за $i в интервала $i/2 и $i
        if($i > 4) {
            $min = $i / 4;
            $max = $i / 2;
            
            do {
                $i =  ($max + $min) / 2;
                $fn = $firstName . '_' . $i . $ext;
                
                if(self::fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id')) {
                    $min = $i;
                } else {
                    $max = $i;
                }
            } while ($max - $min > 1);
            
            $i = $max;
            
            $fn = $firstName . '_' . $i . $ext;
        }

        return $fn;
    }
    
    
    /**
     * Нормализира името на файла
     * Конвертираме името към такова само с латински букви, цифри и знаците '-' и '_'
     * 
     * @param string $fname - Името на файла
     */
    static function normalizeFileName($fname)
    {
        // Конвертираме името към такова само с латински букви, цифри и знаците '-' и '_'
        $fname = STR::utf2ascii($fname);
        $fname = preg_replace('/[^a-zA-Z0-9\-_\.]+/', '_', $fname);
        
        return $fname;
    }
    
    
    /**
     * Ако имаме нови данни, които заменят стари
     * такива указваме, че старите са стара версия
     * на файла и ги разскачаме от файла
     */
    function setData($fileHnd, $newDataId)
    {
        $rec = $this->fetch("#fileHnd = '{$fileHnd}'");
        
        // Ако новите данни са същите, като старите 
        // нямаме смяна
        if($rec->dataId == $newDataId) return $rec->dataId;
        
        // Ако имаме стари данни, изпращаме ги в историята
        if($rec->dataId) {
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
     * Задава данните на даден файл от съществуващ файл в ОС
     */
    function setContent($fileHnd, $osFile)
    {
        $dataId = $this->Data->absorbFile($osFile);
        
        return $this->setData($fileHnd, $dataId);
    }
    
    
    /**
     * Задава данните на даден файл от стринг
     */
    function setContentFromString($fileHnd, $string)
    {
        $dataId = $this->Data->absorbString($string);
        
        return $this->setData($fileHnd, $dataId);
    }
    
    
    /**
     * Връща данните на един файл като стринг
     */
    static function getContent($hnd)
    {
        Debug::log("fileman_Files::getContent('{$hnd}')");
        //expect($path = fileman_Download::getDownloadUrl($hnd));  
        expect($path = fileman_Files::fetchByFh($hnd, 'path'));
        
        return @file_get_contents($path);
    }
    
    
    /**
     * Копира данните от един файл на друг файл
     */
    function copyContent($sHnd, $dHnd)
    {
        $sRec = $this->fetch("#fileHnd = '{$sHnd}'");
        
        if($sRec->state != 'active') return FALSE;
        
        return $this->setData($fileHnd, $sRec->dataId);
    }
    
    
    /**
     * Връща записа за посочения файл или негово поле, ако е указано.
     * Ако посоченото поле съществува в записа за данните за файла,
     * връщаната стойност е от записа за данните на посочения файл
     */
    static function fetchByFh($fh, $field = NULL)
    {
        $Files = cls::get('fileman_Files');
        
        $rec = $Files->fetch(array("#fileHnd = '[#1#]'", $fh));
        
        if($field === NULL) return $rec;
        
        if(!isset($rec->{$field})) {
            $Data = cls::get('fileman_Data');
            
            $dataFields = $Data->selectFields("");
            
            if($dataFields[$field]) {
                $rec = $Data->fetch($rec->dataId);
            }
        }
        
        return $rec->{$field};
    }
    
    
    /**
     * Какви роли са необходими за качване или сваляне?
     */
    static function on_BeforeGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'download' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForDownload');
        } elseif($action == 'add' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForAdding');
        } else {
            
            return;
        }
        
        return FALSE;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {   
        try {
			$row->name = static::getLink($rec->fileHnd);
        } catch(core_Exception_Expect $e) {
            // Вместо линк използваме името
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function makeBtnToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $function = $this->getJsFunctionForAddFile($bucketId, $callback);
        
        return ht::createFnBtn($title, $function, NULL, $attr);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function makeLinkToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $attr['onclick'] = $this->getJsFunctionForAddFile($bucketId, $callback);
        $attr['href'] = $this->getUrLForAddFile($bucketId, $callback);
        $attr['target'] = 'addFileDialog';
        
        return ht::createElement('a', $attr, $title);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getUrLForAddFile($bucketId, $callback)
    {
        // Защитаваме променливите
        Request::setProtected('bucketId,callback');
        
        // Задаваме линка
        $url = array('fileman_Files', 'AddFile', 'bucketId' => $bucketId, 'callback' => $callback);
        
        return toUrl($url);
    }
    
    
    /**
     * Екшън, който редиректва към качването на файл в съответния таб
     */
    function act_AddFile()
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
        $bucketId = Request::get('bucketId');
        $callback = Request::get('callback');
        
        $url = array($class, $act, 'bucketId' => $bucketId, 'callback' => $callback);
        
        return new Redirect($url);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getJsFunctionForAddFile($bucketId, $callback)
    {
        $url = $this->getUrLForAddFile($bucketId, $callback);
        
        $windowName = 'addFileDialog';
        
        if(Mode::is('screenMode', 'narrow')) {
            $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        } else {
            $args = 'width=400,height=530,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        }
        
        return "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
    }
    
    
    /**
     * Превръща масив с fileHandler' и в масив с id' тата на файловете
     * 
     * @param array $fh - Масив с манупулатори на файловете
     * 
     * @return array $newArr - Масив с id' тата на съответните файлове
     */
    static function getIdFromFh($fh)
    {
        //Преобразуваме към масив
        $fhArr = (array)$fh;
        
        //Създаваме променлива за id' тата
        $newArr = array();
        
        foreach ($fhArr as $val) {
            
            //Ако няма стойност, прескачаме
            if (!$val) continue;
            
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
    
    
    /**
     * Изпълнява се преди подготовката на single изглед
     */
    function on_BeforeRenderSingle($mvc, $tpl, &$data)
    {
        $row = &$data->row;
        $rec = $data->rec;

        expect($rec->dataId, 'Няма данни за файла');
        
        //Разширението на файла
        $ext = fileman_Files::getExt($rec->name);
        
        //Иконата на файла, в зависимост от разширението на файла
        $icon = "fileman/icons/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
        if (!is_file(getFullPath($icon))) {
            $icon = "fileman/icons/default.png";
        }
        
        //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');

        // Вербалното име на файла
        $row->fileName = "<span class='linkWithIcon' style='margin-left:-7px; background-image:url(" . sbf($icon, '"', $isAbsolute) . ");'>" . $mvc->getVerbal($rec,'name') . "</span>";
        
        // Иконата за редактиране     
        $editImg = "<img src=" . sbf('img/16/edit-icon.png') . ">";
            
        // URL' то където ще препрати линка
        $editUrl = array(
            $mvc,
            'editFile',
            'id' => $rec->fileHnd,
            'ret_url' => TRUE
        );
            
        // Създаваме линка
        $editLink = ht::createLink($editImg, $editUrl);
        
        // Добавяме линка след името на файла
        $row->fileName .= "<span style='margin-left:3px;'>{$editLink}</span>";

        // Масив с линка към папката и документа на първата достъпна нишка, където се използва файла
        $pathArr = static::getFirstContainerLinks($rec);
        
        // Ако има такъв документ
        if (count($pathArr)) {
            
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
    
    
    /**
     * Екшън за редактиране на файл
     */
    function act_EditFile()
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
        $this->requireRightFor('single', $fRec);
        
        //URL' то където ще се редиректва при отказ
        $retUrl = getRetUrl();
        $retUrl = ($retUrl) ? ($retUrl) : (array('fileman_Files', 'single', $fRec->fileHnd));
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(NULL, 'silent');
        
        $form->input('name');
        
        // Размера да е максимален
        $form->setField('name', 'width=100%');
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Преименува файла
            self::renameFile($fRec, $form->rec->name, TRUE);

            // Редиректваме
            Redirect($retUrl);
        }
        
        // Задаваме по подразбиране да е текущото име на файла
        $form->setDefault('name', $fRec->name);
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'name';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');

        // Вербалното име на файла
        $fileName = fileman_Files::getVerbal($fRec, 'name');
        
        // Добавяме титлата на формата
        $form->title = "Редактиране на файл|*:  {$fileName}";
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Преименува файла
     * 
     * @param object $fRec
     * @param string $newFileName
     * @param boolean $forceDriver
     * 
     * @return NULL|boolean
     */
    public static function renameFile($fRec, $newFileName, $forceDriver=FALSE)
    {
        // Предишното име на файла
        $oldFileName = $fRec->name;
        
        // Ако имената съвпадат, няма какво да се променя
        if ($newFileName == $oldFileName) return ;
        
        // Изтриваме файла от sbf и от модела
        fileman_Download::deleteFileFromSbf($fRec->id);
        
        // Вземамем новото възможно име
        $newFileName = self::getPossibleName($newFileName, $fRec->bucketId);
        
        // Записа, който ще запишем
        $nRec = new stdClass();
        $nRec->id = $fRec->id;
        $nRec->name = $newFileName;
        $nRec->fileHnd = $fRec->fileHnd;
        $saveId = static::save($nRec);
        
        if (!$saveId) return FALSE;
        
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
                    foreach($drivers as $drv) {
                        
                        // Стартираме процеса за извличане на данни
                        $drv->startProcessing($fRec);
                    }
                }    
            }
        }
        
        return TRUE;
    }
    
    
    /**
     * 
     */
    function on_AfterPrepareSingle($mvc, &$tpl, $data)
    {
        // Манипулатора на файла
        $fh = $data->rec->fileHnd;
        
        // Подготвяме данните
        fileman_Indexes::prepare($data, $fh);
        
        // Задаваме екшъна
        if (!$data->action) $data->action = 'single';
    }
    
    
    /**
     * 
     */
    function on_AfterRenderSingle($mvc, &$tpl, &$data)
    {
        // Манипулатора на файла
        $fh = $data->rec->fileHnd;
        
        // Текущия таб
        $data->currentTab = Request::get('currentTab');
        
        // Рендираме табовете
        $fileInfo = fileman_Indexes::render($data);
        
        // Добавяме табовете в шаблона
        $tpl->append($fileInfo, 'fileDetail');
        
        // Отбелязваме като разгледан
        fileman_Log::updateLogInfo($fh, 'preview');
    }
    
    
    /**
     * Връща разширението на файла, от името му
     */
    static function getExt($name)
    {
        if(($dotPos = mb_strrpos($name, '.')) !== FALSE) {
            $ext =  mb_strtolower(mb_substr($name, $dotPos + 1));
            $pattern = "/^[a-zA-Z0-9_\$]{1,10}$/i";
            if(!preg_match($pattern, $ext)) {
                $ext = '';
            }
        } else {
            $ext = '';
        }
        
        return $ext;
    }

    
    /**
     * Връща типа на файла
     * 
     * @param string $fileName - Името на файла
     * 
     * @return string - mime типа на файла
     */
    static function getType($fileName)
    {
        if (($dotPos = mb_strrpos($fileName, '.')) !== FALSE) {
            
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
    static function getFileVersionsString($id)
    {
        // Масив с всички версии на файла
        $fileVersionsArr = fileman_FileDetails::getFileVersionsArr($id);
        
        foreach ($fileVersionsArr as $fileHnd => $fileInfo) {
            
            // Линк към single' а на файла
            $link = ht::createLink($fileInfo['fileName'], array('fileman_Files', 'single', $fileHnd), FALSE, array('title' => $fileInfo['versionInfo']));
            
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
    static function getFileNameWithoutExt($fh)
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
        if(($dotPos = mb_strrpos($fname, '.')) !== FALSE) {
            $name = mb_substr($fname, 0, $dotPos);
        } else {
            $name = $fname;
        }
        
        return $name;
    }
    

	/**
     * 
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Добавяме бутон за сваляне
        $downloadUrl = toUrl(array('fileman_Download', 'Download', 'fh' => $data->rec->fileHnd, 'forceDownload' => TRUE), FALSE);
        $data->toolbar->addBtn('Сваляне', $downloadUrl, 'id=btn-download', 'ef_icon = img/16/down16.png', array('order=8'));
        
        // Вземаме конфигурацията за fileman
        $conf = core_Packs::getConfig('fileman');
        try {
            
            // Ако има зададен клас
            if (trim($conf->FILEMAN_OCR)) {
                
                // Опитваме се да вземаме инстанция на класа
                $OcrInst = cls::get($conf->FILEMAN_OCR);
                
                // Добавяме бутон в тулбара
                $OcrInst->addOcrBtn($data->toolbar, $data->rec);
            }
        } catch (core_exception_Expect $e) { }
    }
    
    
    /**
     * Проверява дали името на подадения файл не се съдържа в същата кофа със същите данни.
     * Ако същия файл е бил качен връща манипулатора на файла
     * 
     * @param fileman_Data $dataId - id' то на данните на файка
     * @param fileman_Buckets $bucketId - id' то на кофата
     * @param string $inputFileName - Името на файла, който искаме да качим
     * 
     * @return fileman_Files $fileHnd - Манипулатора на файла
     */
    static function checkFileName($dataId, $bucketId, $inputFileName)
    {
        // Вземаме всички файлове, които са в съответната кофа и със същите данни
        $query = static::getQuery();
        $query->where("#bucketId = '{$bucketId}' AND #dataId = '{$dataId}'");
        $query->show('fileHnd, name');
        
        // Масив с името на файла и разширението
        $inputFileNameArr = static::getNameAndExt($inputFileName);
        
        // Обикаляме всички открити съвпадения
        while ($rec = $query->fetch($where)) {

            // Ако имената са еднакви
            if ($rec->name == $inputFileName) return $rec->fileHnd;
            
            // Вземаме името на файла и разширението
            $recFileNameArr = static::getNameAndExt($rec->name);
            
            // Намираме името на файла до последния '_'
            if(($underscorePos = mb_strrpos($recFileNameArr['name'], '_')) !== FALSE) {
                $recFileNameArr['name'] = mb_substr($recFileNameArr['name'], 0, $underscorePos);
            }

            // Ако двата масива са еднакви
            if ($inputFileNameArr == $recFileNameArr) {
                
                // Връщаме манипулатора на файла
                return $rec->fileHnd;
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * Създава масив с името на разширението на подадения файл
     * 
     * @param string $fname - Името на файла
     * 
     * @return array $nameArr - Масив с разширението и името на файла
     * 		   string $nameArr['name'] - Името на файла, без разширението
     * 		   string $nameArr['ext'] - Разширението на файла
     */
    static function getNameAndExt($fname)
    {
        // Ако има точка в името на файла, вземаме мястото на последната
        if(($dotPos = mb_strrpos($fname, '.')) !== FALSE) {
            
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
     * Преобразува линка към single' на файла richtext линк
     * 
     * @param integer $id - id на записа
     * 
     * @return string $res - Линка в richText формат
     */
    function getVerbalLinkFromClass($id)
    {
        $rec = static::fetch($id);
        $fileHnd = $rec->fileHnd;
        
        return static::getLink($fileHnd);
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('fileman/tpl/FilesFilterForm.shtml')));
        
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('fName', 'varchar', 'caption=Име на файл,input,silent');
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Потребител,input,silent,refreshForm');
        $data->listFilter->FNC('bucket', 'key(mvc=fileman_Buckets, select=name, allowEmpty)', 'caption=Кофа,input,silent');
        
        // В хоризонтален вид
        $data->listFilter->view = 'vertical';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'fName, usersSearch, bucket';
        
        $data->listFilter->input('usersSearch, bucket, fName', 'silent');
        
    	// По - новите да са по - напред
        $data->query->orderBy("#modifiedOn", 'DESC');
        
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако филтъра е по потребители
            if($filter->usersSearch) {
                
    			// Ако се търси по всички и има права admin или ceo
    			if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo, admin'))) {
    			    // Търсим всичко
                } else {
                    
                    // Масив с потребителите
                    $usersArr = type_Keylist::toArray($filter->usersSearch);
                    
                    // Търсим по създатели
                    $data->query->orWhereArr('createdBy', $usersArr);
                }
    		}
    		
    		// Тримваме името
    		$fName = trim($filter->fName);
    		
    		// Ако има съдържание
    		if (strlen($fName)) {
    		    
    		    // Търсим в името
    		    $data->query->where(array("LOWER(#name) LIKE LOWER('%[#1#]%')", $filter->fName));
    		}
    		
    		// Ако има филтър
            if($filter->bucket) {
                
                // Търсим в кофата
    		    $data->query->where(array("#bucketId = '[#1#]'", $filter->bucket));
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param fileman_Files $mvc
     * @param object $res
     * @param object $data
     */
    static function on_AfterPrepareListSummary($mvc, &$res, &$data)
    {
        // Ако няма заявка, да не се изпълнява
        if (!$data->listSummary->query) return ;
        
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
            $sqlInfo = core_Db::getDBInfo();
            
            if ($sqlInfo) {
                
                $data->listSummary->statVerb['sqlSize'] = $Files->toVerbal($sqlInfo['Size']);
                $data->listSummary->statVerb['rowCnt'] = $Int->toVerbal($sqlInfo['Rows']);
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param fileman_Files $mvc
     * @param core_Et $tpl
     * @param core_Et $data
     */
    static function on_AfterRenderListSummary($mvc, &$tpl, &$data)
    {
        // Ако няма данни, няма да се показва нищо
        if (!$data->listSummary->statVerb) return ;
        
    	// Зареждаме и подготвяме шаблона
    	$tpl = getTplFromFile(("fileman/tpl/FilesSummary.shtml"));
    	
    	// Заместваме статусите на обажданията
    	$tpl->placeArray($data->listSummary->statVerb);
    	
    	// Премахваме празните блокове
		$tpl->removeBlocks();
		$tpl->append2master();
    }
    
    
    /**
     * Интерфейсна функция
     * От манипулатора на файла връща id на записа
     * 
     * @param string
     * @see core_Mvc::unprotectId_()
     */
    function unprotectId($id)
    {
        // Това е хак, за някои случаи когато има манипулатори, които са защитени допълнителни (в стари системи)
        // Ако манипулатора на файла е по дълъг манипулатора по подразбиране
        if (mb_strlen($id) > FILEMAN_HANDLER_LEN) {
            
            // Променлива, в която държим старото състояние
            $old = $this->protectId;
            
            // Задаваме да се защитава
            $this->protectId = TRUE;
            
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
            
            return FALSE;
        }
        
        return $rec->id;
    }
    
    
    /**
     * Интерфейсна функция
     * Ако е подадено число за id го преобразува в манипулатор
     * 
     * @see core_Mvc::protectId()
     */
    function protectId($id)
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
     * Ако имаме права за сваляне връща html <а> линк за сваляне на файла.
     */
    static function getLink($fh, $title=NULL)
    {
    	$conf = core_Packs::getConfig('fileman');
    	
        //Намираме записа на файла
        $fRec = static::fetchByFh($fh);
        
        //Проверяваме дали сме отркили записа
        if(!$fRec) {
            
            sleep(2);
            
            return FALSE;
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
        $icon = "fileman/icons/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
        if (!is_file(getFullPath($icon))) {
            $icon = "fileman/icons/default.png";
        }
        
        $attr = array();
        
        // Икона на линка
        $attr['ef_icon'] = $icon;
        
        // Клас на връзката
        $attr['class'] = 'fileLink';

        // Ограничаваме максиманата дължина на името на файла
        $nameFix = str::limitLen($name, 32);

        if($nameFix != $name) {
            $attr['title'] = $name;
        }

        //Инстанция на класа
        $FileSize = cls::get('fileman_FileSize');
        
        // Титлата пред файла в plain режим
        $linkFileTitlePlain = tr('Файл') . ": ";
        
        // Ако има данни за файла и съществува
        if (($fRec->dataId) && file_exists($path)) {
            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('text', 'plain');
            
            //Генерираме връзката 
            $url  = static::generateUrl($fh, $isAbsolute);
            
            // Ако сме в текстов режим
            if(Mode::is('text', 'plain')) {
                
                //Добаваме линка към файла
                $link = "{$linkFileTitlePlain}$name ( $url )";
            } else {
                
                //Големината на файла в байтове
                $fileLen = fileman_Data::fetchField($fRec->dataId, 'fileLen');
                
                //Преобразуваме големината на файла във вербална стойност
                $size = $FileSize->toVerbal($fileLen);
                
                if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
                        
                    // Линка да се отваря на нова страница
                    $attr['target'] = '_blank';    
                } else {
                    // Ако линка е в iframe да се отваря в родителския(главния) прозорец
                    $attr['target'] = "_parent";
                }
                
                //Заместваме &nbsp; с празен интервал
                $size =  str_ireplace('&nbsp;', ' ', $size);
                    
                //Добавяме към атрибута на линка информация за размера
                $attr['title'] .= ($attr['title'] ? "\n" : '') . tr("|Размер|*: {$size}");
                
                $attr['rel'] = 'nofollow';
                
                $link = ht::createLink($nameFix, $url, NULL, $attr);
            }
        } else {
            
            // Ако няма файл
            
            // Ако сме в текстов режим
            if(Mode::is('text', 'plain')) {
                
                // Линка 
                $link = $linkFileTitlePlain . $name;
            } else {
                if(!file_exists($path)) {
    				$attr['style'] .= ' color:red;';
    			}
                //Генерираме името с иконата
                $link = "<span class='linkWithIcon' style=\"" . $attr['style'] . "\"> {$nameFix} </span>";
            }
        }
        
        return $link;
    }
    
    
    /**
     * Прекъсваема функция за генериране на URL от манипулатор на файл
     */
    static function generateUrl_($fh, $isAbsolute)
    {
        $rec = static::fetchByFh($fh);
        
        if (static::haveRightFor('single', $rec)) {
            
            //Генерираме връзката 
            $url = toUrl(array('fileman_Files', 'single', $fh), $isAbsolute);
        } else {
            //Генерираме връзката за сваляне
            $url = toUrl(array('fileman_Download', 'Download', 'fh' => $fh, 'forceDownload' => TRUE), $isAbsolute);
        }
        
        return $url;
    }
    
    
    /**
     * Връща линк за сваляне, според ID-то
     */
    static function getLinkById($id)
    {
        $fh = static::fetchField($id, 'fileHnd');
        
        return static::getLink($fh);
    }
}
