<?php


/**
 * Списък, разделен със запетайки от пътища, които могат да бъдат начало на хранилище
 */
defIfNot('EF_REPOSITORIES_PATHS',  EF_UPLOADS_PATH . '/repositories');


/**
 * Модел, който съдържа пътищата до хранилищата
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Repositories extends core_Master
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Път до хранилище";
    
    
    /**
     * 
     */
    var $singleTitle = "Хранилище";
    
    
    /**
     * 
     */
    var $singleLayoutFile = 'fileman/tpl/SingleLayoutRepositories.shtml';
    
    
    /**
     * 
     */
    var $canSingle = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'admin';
    
    
    /**
     * Кой има право да обхожда папките
     */
    var $canRetrive = 'user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'fileman_Wrapper, plg_RowTools, plg_Created';
    
    
    /**
     * 
     */
    var $listFields = 'id, verbalName, fullPath, access, ignore';
    
    
    /**
     * Името на кофата за файловете
     */
    static $bucket = 'repositories';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('basePath', 'varchar(readonly)', 'caption=Хранилище, mandatory, width=100%');
        $this->FLD('subPath', 'varchar', 'caption=Подпапка, width=100%');
        $this->FLD('verbalName', 'varchar', 'caption=Име, width=100%');
        $this->FLD('rolesForAccess', 'keylist(mvc=core_Roles, select=role, allowEmpty)', 'caption=Достъп->Роли, width=100%,placeholder=Всички');
        $this->FLD('usersForAccess', 'userList', 'allowEmpty, caption=Достъп->Потребители, width=100%');
        $this->FLD('ignore', 'text', 'caption=Игнориране, width=100%');
        $this->FNC('fullPath', 'varchar', 'caption=Път, width=100%');
        $this->FNC('access', 'text', 'caption=Достъп, width=100%');
    }
    
    
    /**
     * 
     */
    function on_CalcFullPath($mvc, $rec)
    {
        // Вземаме целия път
        $rec->fullPath = static::getFullPath($rec->basePath, $rec->subPath);
    }
    
    
    /**
     * Връща всички коректни хранилища от EF_REPOSITORIES_PATHS
     */
    static function getRepositoriesPathsArr()
    {
        // Масив с позволените разширения
        $allowedArr = array();
        
        // Масив с всички хранилища
        $repositoryPathArr = arr::make(EF_REPOSITORIES_PATHS);
        
        // Обхождаме масива
        foreach ($repositoryPathArr as $repositoryPath) {
            
            // Ако пътя не е добър, прескачаме
            if (!static::isGoodPath($repositoryPath)) continue;
            
            $repositoryPath = static::preparePath($repositoryPath);
            
            // Добавяме в масива
            $allowedArr[$repositoryPath] = $repositoryPath;
        }
        
        return $allowedArr;
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {   
        // Масив с пътищата
        $basePathsArr = static::getRepositoriesPathsArr();
        
        // Добавяме предложение за пътищата
        $data->form->appendSuggestions('basePath', $basePathsArr);
        
        // Ако създаваме нов запис
        if (!$data->form->rec->id) {
            
            // Избираме първия, по подразбиране
            $data->form->setDefault('basePath', key($basePathsArr));
        }
        
        // Плейсхолдера, който ще показваме
        $placeText = "Текст, който да се игнорира: ^ начало, * всики, - без, $ край";
        
        // Добавяме плейсхолдера
        $data->form->addAttr('ignore', array('placeholder' => $placeText));
        
        // Добавяме помощния текст
        $data->form->addAttr('ignore', array('title' => 'Игнорира името на файла, който пасва на шаблона'));
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е изпратена без грешки
        if ($form->isSubmitted()) {
            
            // Масив с всички главни пътища
            $basePathArr = static::getRepositoriesPathsArr();
            
            // Проверяваме дали е в допустимите
            if (!$form->rec->basePath || !$basePathArr[$form->rec->basePath]) {
                
                // Добавяме съответната грешка
                $form->setError('basePath', 'Не е в допустимите пътища');
            }
            
            // Полето подпапка
            $subPathField = $form->getField('subPath');
            
            // Шаблон за коректност на подпапката
            $subPathPattern = "/^[a-z0-9_\-\/]*$/i";
            
            // Ако пътя не е коректен
            if(!preg_match($subPathPattern, $form->rec->subPath)) {
                
                // Добавяме съответната грешка
                $form->setError('subPath', 'Некоректен път|*: ' . $subPathField->type->escape($form->rec->subPath));
            }
        }
        
        // Ако формата е изпратена без грешки
        if ($form->isSubmitted()) {
            
            // Ако няма подпапка
            if ($form->rec->subPath) {
                
                // Вземаме целия път
                $fullPath = static::getFullPath($form->rec->basePath, $form->rec->subPath);
                
                // Ако редактираме записа
                if ($form->rec->id) {
                    
                    // Вземаме записа от модела
                    $rec = $mvc->fetch($form->rec->id);
                    
                    // Целия път от модела
                    $fullPathFromRec = static::getFullPath($rec->basePath, $rec->subPath);
                    
                    // Ако целия път от модела отговаря на пътя от формата
                    if ($fullPathFromRec == $fullPath) {
                        
                        // Сетваме флага
                        $isSame = TRUE;
                    }
                }
                
                // Ако не е сетнат флага
                if (!$isSame) {
                    
                    // Ако е директория
                    if (is_dir($fullPath)) {
                        
                        // Сетваме предупреждение за съществуваща папка
                        $form->setWarning('subPath', 'Съществуваща папка|*: ' . $subPathField->type->escape($fullPath));
                    } elseif (!@mkdir($fullPath, 0777, TRUE)) {
                        
                        // Ако възникне грешка при създаване на папката, сетваме грешка
                        $form->setError('subPath', 'Не може да се създаде поддиректорията|*: ' . $subPathField->type->escape($fullPath));
                    }
                }
            }
        }
    }
    
    
    /**
     * Поправа наклонение черти в пътищата
     * 
     * @param string $path - Пътя
     * 
     * @return string
     */
    static function preparePath($path)
    {
        
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
        
    }
    
    
    /**
     * Проверява дали пътя е в добър за използване. Дали има евентуални хакове.
     * 
     * @param string $path - Пътя
     * 
     * @return boolean
     */
    static function isGoodPath($path)
    {
        // Подготвяме пътя
        $path = static::preparePath($path);
        
        // Ако няма връщане към предишна директория
        if (trim($path) && strpos($path, './') === FALSE) {
            
            // Връщаме TRUE
            return TRUE;
        }
    }
    
    
    /**
     * Обединява хранилището и подпапката
     * 
     * @param string $basePath - Хранилището
     * @param string $subPat - Подпапката
     * 
     * @return string $fullPath
     */
    static function getFullPath($basePath, $subPath='') 
    {
        // Подготвяме пътя до хранилището
        $basePath = static::preparePath($basePath);
        
        // Премахваме последната наклонена черта
        $basePath = rtrim($basePath, '/');
        
        // Ако е подадена подпапка
        if (trim($subPath)) {
            
            // Подготвяме подпапката
            $subPath = static::preparePath($subPath);
            
            // Премахваме първата наклонен черта
            $subPath = ltrim($subPath, '/');
            
            // Съединяваме пътищата
            $fullPath = $basePath . '/' . $subPath;
        } else {
            
            // Ако няма подпапка
            
            // Пълния път е само базовия път
            $fullPath = $basePath;
        }
        
        return $fullPath;
    }
    
    
    /**
     * Качва посочения файл в кофата и връща манипулатора му
     * 
     * @param string $filePath - Пътя до файла
     * @param string $bucket - Кофата, в която да се качи
     * 
     * @return fileHnd - Връща манипулатора на качения файл
     */
    static function absorbFile($filePath, $bucket=NULL)
    {
        // Задаваме кофата, ако не е зададена
        setIfNot($bucket, static::$bucket);
        
        // Очакваме да няма хакове по пътя
        expect(static::isGoodPath($filePath));
        
        // Подготвяме пътя
        $filePath = static::preparePath($filePath);
        
        // Очакваме да е валиден файл        
        expect(is_file($filePath));
        
        // Качваме файла и връщаме манипулатора му
        return fileman::absorb($filePath, $bucket);
    }
    
    
    /**
     * Абсорбира подадения файл, който се намира в съответното хранилище
     * 
     * @param integer $id - id на хранилището
     * @param string $file - Файла в хранилището
     * @param string $bucket - Кофата
     * 
     * @return array $fh - Манипулатор на файла
     */
    static function absorbFileFromId($id, $file, $bucket=NULL)
    {
        // Вземаме записа
        $rec = static::fetch($id);
        
        // Вземаем пътя до файла
        $filePath = static::getFullPath($rec->fullPath, $file);
        
        // Абсорбираме файла
        $fh = static::absorbFile($filePath, $bucket);
        
        // Връщаме манупулатора му
        return $fh;
    }
    
    
    /**
     * Екстрактване на файл в ОС. Връща пълния път до новия файл
     * 
     * @param string $fh - Манипулатор на файла, за който ще се създаде нова версия
     * @param string $path - Пътя, където да се абсорбира файла
     * 
     * @return string - Пътя до файла
     */
    static function extract($fh, $path=NULL)
    {
        
        return fileman::extract($fh, $path);
    }
    
    
    /**
     * Връща масив с всички данни за файла
     * 
     * @param string $filePath - Пътя до файла
     * 
     * @return array $res - Масив с резултатите
     * $res['modificationTime'] - Време на последна модификация
     * $res['creationTime'] - Време на създаване
     * $res['accessTime'] - Време на последен достъп до файла
     * $res['fileSize'] - Размера на файла
     * $res['fileType'] - Типа на файла
     * $res['mimeType'] - Миме типа на файла
     * $res['extension'] - Разширението на файла
     * $res['isCorrectExt'] - Дали разширението на файла е в допусмите за миме типа
     */
    static function getFileInfo($filePath)
    {
        
        return fileman::getInfoFromFilePath($filePath);
    }
    
    
    /**
     * Връща двумерен масив с всички папки и файловете в тях
     * 
     * @param integer $repositoryId - id на хранилището
     * @param string $subPath - Подпапка в хранилището
     * @param boolean $useFullPath - Да се използва целия файл до папката
     * 
     * @return array - Масив с всички папки и файловете в тях
     */
    static function retriveFiles($repositoryId, $subPath = '', $useFullPath=FALSE)
    {
        // Очакваме да е число
        expect(is_numeric($repositoryId));
        
        // Масива, който ще връщаме
        $res = array();
        
        // Вземаме записа
        $rec = static::fetch($repositoryId);
        
        // Проверяваме дали има права за папката
        static::requireRightFor('retrive', $rec);
        
        // Вземаме пътя до поддиректорията на съответното репозитори
        $fullPath = static::getFullPath($rec->basePath, $rec->subPath);
        
        // Обединяваме с подадена поддиректория
        $fullPath = static::getFullPath($fullPath, $subPath);
        
        // Вземаме итератор
        // RecursiveIteratorIterator::SELF_FIRST - Служи за вземане и на директориите
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath), RecursiveIteratorIterator::SELF_FIRST);
        
        // Сетваме флаговете
        // NEW_CURRENT_AND_KEY = FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::CURRENT_AS_FILEINFO
        // FilesystemIterator::KEY_AS_FILENAME - ->key() да връща името на файла
        // FilesystemIterator::CURRENT_AS_FILEINFO - ->current() да връща инстанция на SplInfo
        // FilesystemIterator::SKIP_DOTS - Прескача . и ..
        $iterator->setFlags(FilesystemIterator::NEW_CURRENT_AND_KEY | FilesystemIterator::SKIP_DOTS);
        
        // Обхождаме итератора
        while($iterator->valid()) {
            
            //Флаг, за игнориране на файла
            $ignore = FALSE;
            
            // Вземаме името на файла
            $fileName = $iterator->key();
            
            // Вземаме пътя
            $path = $iterator->current()->getPath();
            
            // Ако не е задедено да се използва целия път до файла
            if (!$useFullPath) {
                
                // Вземаме пътя без целия път
                $path = str_ireplace($fullPath, '', $path);
                if (!$path) $path = '/';
            }
            
            // Ако е директория
            if ($iterator->isDir()) {
                
                // Ако няма такъв запис
                if (!$res[$path]) {
                    
                    // Пътя до директорията
                    $path = static::getFullPath($path, $fileName);
                    
                    // Създаваме масив с директрояита
                    $res[$path] = array();
                }
            } else {
                
                // Ако няма да се игнорира файла
                if (!static::isForIgnore($rec->ignore, $fileName)) {
                    
                    // Добавяме в резултатите пътя и името на файла
                    $res[$path][$fileName] = TRUE;
                }
            }
            
            // Прескачаме на следващия
            $iterator->next();
        }
        
        return $res;
    }
    
    
    /**
     * Дали да се игнорира файла
     * 
     * @param string $ignoreStr - Стринг с файловете за игнориране
     * @param string $fileName - Име на файл
     */
    static function isForIgnore($ignoreStr, $fileName)
    {
        // Ако няма подаден стринг за игнориране, да не се игнорира файла
        if (!$ignoreStr) return FALSE;
        
        // Масива със стринговете за игнориране
        // За да не се генерира всеки път
        static $ignoreStrArr=array();
        
        // Хеша на стринга
        $ignoreStrHash = md5($ignoreStr);
        
        // Ако не е генериран регулярен израз за игнориране
        if (!$ignoreStrArr[$ignoreStrHash]) {
            
            // Разделяме стринга
            $ignoreArr = explode("\n", $ignoreStr);
            
            // Случайно число за заместване на *
            $starRand = str::getRand();
            
            // Случайно число за заместване на -
            $dashRand = str::getRand();
            
            // Случайно число за заместване на ^
            $beginRand = str::getRand();
            
            // Случайно число за заместване на $
            $endRand = str::getRand();
            
            // Шаблона
            $patternText = '';
            
            // Обхождаме масива
            foreach ((array)$ignoreArr as $ignore) {
                
                // Тримваме текста
                $ignore = trim($ignore);
                
                // Заместваме символите с генерираните числа
                $ignoreText = str_replace(array('^', '$', '*', '-'),
                                          array($beginRand, $endRand, $starRand, $dashRand), $ignore);
                
                // Ескейпваме останалите символи
                $ignoreText = preg_quote($ignoreText, '/');
                
                // Заместваме случайния текст за *, с шаблона за всички символи
                $ignoreText = str_replace($starRand, '(.*?)', $ignoreText);
                
                // Ако има '-' в текста
                if (strpos($ignoreText, $dashRand) !== FALSE) {
                    
                    // Заместваме с празен текст '-' и вземаме текста
                    $text = str_replace($dashRand, "", $ignoreText);
                    
                    // Добавяме текста, за отказ
                    $ignoreText = "^((?!{$text}).)*$";
                }
                
                // Ако е зададено начоло на текст
                if (strpos($ignoreText, $beginRand) !== FALSE) {
                    
                    // Заместваме с празен текст '^' и вземаме текста
                    $ignoreText = str_replace($beginRand, "", $ignoreText);
                    
                    // Добавяме начало за шаблона
                    $ignoreText = '^' . $ignoreText;
                }
                
                // Ако е зададено край на текст
                if (strpos($ignoreText, $endRand) !== FALSE) {
                    
                    // Заместваме с празен текст '$' и вземаме текста
                    $ignoreText = str_replace($endRand, "", $ignoreText);
                    
                    // Добавяме край за шаблона
                    $ignoreText = $ignoreText . "$";
                }
                
                // Добавяме в блок
                $ignoreText = "({$ignoreText})";
                
                // Добавяме съответния шаблон, към всички
                $patternText .= ($patternText) ?  "|" . $ignoreText : $ignoreText;
            }
            
            // Получения шаблон го добавяме в масива
            $ignoreStrArr[$ignoreStrHash] = "/({$patternText})/iu";
        }
        
        // Ако има шаблона
        if ($patternText = $ignoreStrArr[$ignoreStrHash]) {
            
            // Проверяваме дали се съдържа
            $match = preg_match($patternText, $fileName);
            
            // Връщаме резултата
            return $match;
        }
        
        return FALSE;
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако екшъна е retrive и сме дефинира роли за достъп до хранилището
        // И текущия потребител няма такава
        if ($action == 'retrive') {
            
            // Ако няма роля admin
            // admin трябва да има достъп
            if (!haveRole('admin')) {
                
                // Роли за достъп
                $rolesForAccess = trim($rec->rolesForAccess);
                
                // Потребители, които имат достъп
                $usersForAccess = trim($rec->usersForAccess);
                
                // Ако няма зададени потребители и роли
                if (!$rolesForAccess && !$usersForAccess) {
                    
                    // Да не може да пипа
                    $requiredRoles = 'no_one';
                } else {
                    
                    // Ако има роли и ние я имаме
                    // или ако е зададен потребител
                    if (($rolesForAccess && !haveRole($rolesForAccess))
                        || ($usersForAccess && !type_Keylist::isIn($userId, $usersForAccess))) {
                        
                        // Да не може да пипа
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
	
	
	/**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако има зададени потребители
        if ($rec->usersForAccess) {
            
            // Добавяме 
            $row->access = "<div class='users-for-access'>{$row->usersForAccess}</div>";
        }
        
        // Ако има зададени права за достъп
        if ($rec->rolesForAccess) {
            
            // Вземаме вербалната стойност
            $rolesForAccess = $mvc->getVerbal($rec, 'rolesForAccess');
            
            // Добавяме към достъпа
            $row->access .= "<div class='roles-for-access'>{$rolesForAccess}</div>";
        }
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
        // Вземаме файловете в дървовидна структура
        $data->row->FileTree = static::getFileTree($data->rec->id);
    }
    
    
	/**
     * Връща в дървовидна структура съдържанието на хранилището
     * 
     * @param integer $id - Хранилище
     * @param string $subPath - Подпапка в хранилището
     * 
     * @return core_Et $res
     */
    static function getFileTree($id, $subPath='', $useEmptyFolders=FALSE)
    {
        try {
            // Вземаме съдържанието
            $foldersArr = static::retriveFiles($id, $subPath);
        } catch (Exception $e) {
            
            // Връщаме грешката
            return tr('Възникна грешка при показване на съдържанието на хранилището');
        }
        
        // Сортираме масива за да може папките да са на първо място
        asort($foldersArr);
        
        // Инстанция на класа
        $tableInst = cls::get('core_Tree');

        // Обхождаме масива
        foreach ((array)$foldersArr as $path => $filesArr) {
            
            // Заместваме разделителите за поддиректория с разделителя за дърво
            $pathEntry = str_replace(array('/', '\\'), "->", $path);
            
            // Ако e празна директория
            if (!count($filesArr)) {
                
                // Ако е зададено да се показват ипразните директории
                if ($useEmptyFolders) {
                    
                    // Добавяме директорията
                    $tableInst->addNode($pathEntry, FALSE, TRUE);
                }
            } else {
                
                // Обхождаме файловете
                foreach ((array)$filesArr as $file => $dummy) {
                    
                    // Тримваме, за да премахнем последния 
                    $filePathEntry = rtrim($pathEntry, '->');
                    
                    // Вземаме пътя до файла
                    $filePathEntry = $filePathEntry . '->' . $file;
                    
                    // Пътя до файла
                    $fullPath = static::getFullPath($path, $file);
                    
                    // URL за абсорбиране на файла
                    $urlPath = static::getAbsorbUrl($id, $fullPath);
                    
                    // Добавяме в дървото
                    $tableInst->addNode($filePathEntry, $urlPath, TRUE);
                }
            }
        }
        
        // Името
        $tableInst->name = 'file';
        
        // Рендираме изгледа
        $res = $tableInst->renderHtml(NULL);
        
        // Връщаме шаблона
        return $res;
    }
    
    
    /**
     * Екшън за абсорбиране на файла
     */
    function act_AbsorbFile()
    {
        // id на хранилището
        $id = Request::get('id', 'int');
        
        // Относителен път до файла в хранилището
        $file = Request::get('file');
        
        // Абсорбираме файла и вземаме манипулатора му
        $fh = static::absorbFileFromId($id, $file);
        
        // Линк към сингъла на файла
        $singleUrl = fileman::getUrlToSingle($fh);
        
        // Редиректваме към сингъла
        return Redirect($singleUrl);
    }
    
    
    /**
     * Връща URL към екшъна за абсорбиране на съответния файл
     * 
     * @param integer $id
     * @param string $file
     * @param boolean $absolute
     * 
     * @return string $url
     */
    static function getAbsorbUrl($id, $file, $absolute=FALSE)
    {
        // Очакваме да има id
        expect($id);
        
        // Вземаме URL' то
        $url = toUrl(array('fileman_Repositories', 'absorbFile', $id, 'file' => $file), $absolute);
        
        return $url;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Масив с пътищата до хранилищата
        $repositoryPathArr = arr::make(EF_REPOSITORIES_PATHS);
        
        // Обхождаме всички пътища
        foreach ($repositoryPathArr as $repositoryPath) {
            
            // Флаг, указващ дали има грешки
            $haveError = FALSE;
            
            // Ескейпваме стринга
            $repositoryPathEsc = core_Type::escape($repositoryPath);
            
            // Подготвяме пътя до хранилището
            $repositoryPath = static::preparePath($repositoryPath);
            
            // Ако пътя не е добър
            if (!static::isGoodPath($repositoryPath)) {
                
                // Сетваме грешка
                $res .= "<li style='color:red'>Хранилището, което сте въвели не може да се използва './': " . $repositoryPathEsc;
                
                // Прескачаме
                continue;
            }
            
            // Ако е директория
            if (is_dir($repositoryPath)) {
                
                // Ако нямаме права за четене
                if (!is_readable($repositoryPath)) {
                    
                    // Добавяме грешката
                    $res .= "<li style='color:red'>Нямате права за четене в: " . $repositoryPathEsc;
                    
                    // Вдигаме флага
                    $haveError = TRUE;
                }
                
                // Ако нямаме права за запис
                if (!is_writable($repositoryPath)) {
                    
                    // Добавяме грешката
                    $res .= "<li style='color:red'>Нямате права за запис в: " . $repositoryPathEsc;
                    
                    // Вдигаме флага
                    $haveError = TRUE;
                }
                
                // Ако име грешки прескачаме
                if ($haveError) continue;
                
                // Отбелязваме, че директорията съществува
                $res .= "<li>Съществуваща директория: " . $repositoryPathEsc;
            } else {
                
                // Ако може да се създаде хранилището
                if (@mkdir($repositoryPath, 0777, TRUE)) {
                    
                    // Добавяме съобщение за успех
                    $res .= "<li style='color:green'>Създадена директория: " . $repositoryPathEsc;
                } else {
                    
                    // Добавяме грешка
                    $res .= "<li style='color:red'>Не може да се създаде директория: " . $repositoryPathEsc;
                }
            }
        }
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на blast имейлите
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket(static::$bucket, 'Файлове в хранилищата', NULL, '104857600', 'user', 'user');
    }
}
