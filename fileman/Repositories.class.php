<?php


/**
 * Списък, разделен със запетайки от пътища, които могат да бъдат начало на хранилище
 */
defIfNot('EF_REPOSITORIES_PATHS', EF_UPLOADS_PATH . '/repositories');


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
     * Колко секунди след последното модифициране на файла да може да се ползва
     */
    const DONT_USE_MODIFIED_SECS = 5;
    

    /**
     *  Брой елементи в сингъл изгледа на една страница
     */
    public $singleItemsPerPage = 5000;
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = 'Път до хранилище';
    
    
    
    public $singleTitle = 'Хранилище';
    
    
    
    public $singleLayoutFile = 'fileman/tpl/SingleLayoutRepositories.shtml';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/repository.png';
    
    
    
    public $canSingle = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'admin';
    
    
    /**
     * Кой има право да обхожда папките
     */
    public $canRetrive = 'user';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'fileman_Wrapper, plg_RowTools2, plg_Created, plg_State, plg_Rejected';
    
    
    
    public $listFields = 'id, verbalName, fullPath, access, ignore';
    
    
    /**
     * Името на кофата за файловете
     */
    public static $bucket = 'repositories';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('basePath', 'varchar(readonly)', 'caption=Хранилище, mandatory, width=100%');
        $this->FLD('subPath', 'varchar', 'caption=Подпапка, width=100%');
        $this->FLD('verbalName', 'varchar', 'caption=Име, width=100%, mandatory');
        $this->FLD('rolesForAccess', 'keylist(mvc=core_Roles, select=role, allowEmpty)', 'caption=Достъп->Роли, width=100%,placeholder=Всички');
        $this->FLD('usersForAccess', 'userList', 'allowEmpty, caption=Достъп->Потребители, width=100%');
        $this->FLD('ignore', 'text', 'caption=Игнориране, width=100%');
        $this->FNC('fullPath', 'varchar', 'caption=Път, width=100%');
        $this->FNC('access', 'text', 'caption=Достъп, width=100%');
    }
    
    
    
    public function on_CalcFullPath($mvc, $rec)
    {
        // Вземаме целия път
        $rec->fullPath = static::getFullPath($rec->basePath, $rec->subPath);
    }
    
    
    /**
     * Връща всички коректни хранилища от EF_REPOSITORIES_PATHS
     */
    public static function getRepositoriesPathsArr()
    {
        // Масив с позволените разширения
        $allowedArr = array();
        
        // Масив с всички хранилища
        $repositoryPathArr = arr::make(EF_REPOSITORIES_PATHS);
        
        // Обхождаме масива
        foreach ($repositoryPathArr as $repositoryPath) {
            
            // Ако пътя не е добър, прескачаме
            if (!static::isGoodPath($repositoryPath)) {
                continue;
            }
            
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
     * @param stdClass     $data
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
        } else {
            
            // Ако е активирано
            if ($data->form->rec->state == 'active') {
                
                // Да не може да се променя пътя до хранилището
                $data->form->setReadOnly('subPath');
                $data->form->setReadOnly('basePath');
            }
        }
        
        // Плейсхолдера, който ще показваме
        $placeText = 'Текст, който да се игнорира: ^ начало, * всички, - без, $ край';
        
        // Добавяме плейсхолдера
        $data->form->addAttr('ignore', array('placeholder' => $placeText));
        
        // Добавяме помощния текст
        $data->form->addAttr('ignore', array('title' => 'Игнорира името на файла, който пасва на шаблона'));
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
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
            if (!preg_match($subPathPattern, $form->rec->subPath)) {
                
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
                        $isSame = true;
                    }
                }
                
                // Ако не е сетнат флага
                if (!$isSame) {
                    
                    // Ако е директория
                    if (is_dir($fullPath)) {
                        
                        // Сетваме предупреждение за съществуваща папка
                        $form->setWarning('subPath', 'Съществуваща папка|*: ' . $subPathField->type->escape($fullPath));
                    } elseif (!@mkdir($fullPath, 0777, true)) {
                        
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
    public static function preparePath($path)
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
    public static function isGoodPath($path)
    {
        // Подготвяме пътя
        $path = static::preparePath($path);
        
        // Ако няма връщане към предишна директория
        if (trim($path) && strpos($path, './') === false) {
            
            // Връщаме TRUE
            return true;
        }
    }
    
    
    /**
     * Връща пълния път до хранилището по подадено id
     *
     * @param integer $id - id на записа
     *
     * @return string - Пътя до хранилището
     */
    public static function getFullPathFromId($id)
    {
        return static::fetchField($id, 'fullPath');
    }
    
    
    /**
     * Обединява хранилището и подпапката
     *
     * @param string $basePath - Хранилището
     * @param string $subPat   - Подпапката
     *
     * @return string $fullPath
     */
    public static function getFullPath($basePath, $subPath = '')
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
     * @param string $repoPath - Пътя до хранилището
     * @param string $file     - Името на файла
     * @param string $subPath  - Подпапка в хранилището
     * @param string $bucket   - Кофата, в която да се качи
     *
     * @return fileHnd - Връща манипулатора на качения файл
     */
    public static function absorbFile($repoPath, $file, $subPath = '', $bucket = null)
    {
        // Задаваме кофата, ако не е зададена
        setIfNot($bucket, self::$bucket);
        
        // Обединяваме подпапката и хранилището
        $repoPath = static::getFullPath($repoPath, $subPath);
        
        // Добавяме името на файла към пътя
        $filePath = static::getFullPath($repoPath, $file);
        
        // Подготвяме пътя
        $filePath = static::preparePath($filePath);
        
        // Очакваме да няма хакове по пътя
        expect(static::isGoodPath($filePath));
        
        // Очакваме да е валиден файл
        expect(is_file($filePath));
        
        // Качваме файла и връщаме манипулатора му
        return fileman::absorb($filePath, $bucket);
    }
    
    
    /**
     * Абсорбира подадения файл, който се намира в съответното хранилище
     *
     * @param integer $id      - id на хранилището
     * @param string  $file    - Името на файла в хранилището
     * @param string  $subPath - Подпапка в хранилището
     * @param string  $bucket  - Кофата
     *
     * @return array $fh - Манипулатор на файла
     */
    public static function absorbFileFromId($id, $file, $subPath = '', $bucket = null)
    {
        // Вземаме записа
        $rec = static::fetch($id);
        
        // Вземаем пътя до хранилището
        $repoPath = static::getFullPathFromId($id);
        
        // Абсорбираме файла
        $fh = static::absorbFile($repoPath, $file, $subPath, $bucket);
        
        // Връщаме манупулатора му
        return $fh;
    }
    
    
    /**
     * Добавя файла в посочените хранилища
     *
     * @param fileHnd $fh        - Манипулатор на файла
     * @param array   $reposArr  - Масив с хранилища
     * @param string  $subPath   - Подпапка
     * @param boolean $forceSave - Дали да се форсира записа, ако съществува файл със същото име
     * @param string  $fileName  - Името на файла
     *
     * @return array $resArr - Масив с резултатите за записа на файла
     *               array $resArr['existing'] - Файл със същотото име съществува в хранилище
     *               array $resArr['copied'] - Копиран е файла в хранилище
     *               array $resArr['problem'] - Проблем при запис на файла в хранилище
     */
    public static function addFileInReposFromFh($fh, $reposArr, $subPath = '', $forceSave = false, $fileName = '')
    {
        // Резултата, който ще връщаме
        $resArr = array();
        
        // Преобразуваме в масив
        $reposArr = arr::make($reposArr);
        
        // Обхождаме масива
        foreach ((array) $reposArr as $repoId) {
            
            // Флаг, указващ дали файла съществува
            $fileExist = null;
            
            // Вземаме пълния път до хранилището
            $fullPath = static::fetchField($repoId, 'fullPath');
            
            // Вземаме пълния път до подпапката в хранилището
            $fullPath = static::getFullPath($fullPath, $subPath);
            
            // Ако не е задаено името на файла
            if (!$fileName) {
                
                // Вземаме файла
                $fRec = fileman_Files::fetchByFh($fh);
                
                // Вземаме името на файла
                $fileName = $fRec->name;
            }
            
            // Проверяваме дали файла съществува
            $fileExist = static::checkFileExistInRepo($fileName, $repoId, $subPath);
            
            // Ако файла съществува и не се форсира записа
            if ($fileExist && !$forceSave) {
                
                // Добавяме в масива със съществуващи
                $resArr['existing'][$repoId] = $fileName;
            } else {
                try {
                    
                    // Екстрактваме файла
                    static::extract($fh, $fullPath, $fileName);
                    
                    // Добавяме в копирания
                    $resArr['copied'][$repoId] = $fileName;
                } catch (core_exception_Expect $e) {
                    
                    // Ако възникне грешка, добавяме към грешките
                    $resArr['problem'][$repoId] = $fileName;
                }
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Синхронизира файловете в един от оригиналния масив с копираните масиви
     *
     * @param string  $fileName         - Името на файла
     * @param array   $originalReposArr - Масив с хранилища от където ще се копира файла
     * @param array   $copyReposArr     - Масив с хранилища където ще се копира файла
     * @param string  $subPath          - Подпапка
     * @param boolean $forceSave        - Дали да се форсира записа, ако съществува файл със същото име
     *
     * @return array $resArr
     *
     * array $resArr['existing'] - Файл със същотото име съществува в хранилище
     * array $resArr['notExist'] - Файл не съществува в оригиналното хранилище
     * array $resArr['copied'] - Копиран е файла в хранилище
     * array $resArr['problem'] - Проблем при запис на файла в хранилище
     */
    public static function syncFileInRepos($fileName, $originalReposArr, $copyReposArr, $subPath = '', $forceSave = false)
    {
        // Резултата, който ще връщаме
        $resArr = array();
        
        // Обхождаме масива
        foreach ((array) $originalReposArr as $originalRepoId) {
            
            // Вземаме пълния път до хранилището
            $fullPath = static::fetchField($originalRepoId, 'fullPath');
            
            // Вземаме пълния път до подпапката в хранилището
            $fullPath = static::getFullPath($fullPath, $subPath);
            
            // Пълния път до файла
            $filePath = static::getFullPath($fullPath, $fileName);
            
            // Проверяваме дали файла съществува
            $fileExist = static::checkFileExistInRepo($fileName, $originalRepoId, $subPath);
            
            // Ако файлъ не съществува
            if (!$fileExist) {
                
                // Добавяме в масива
                $resArr['notExist'][$originalRepoId] = $originalRepoId;
                
                // Прескачаме
                continue;
            }
            
            // Обхоцдаме масива за копиранията
            foreach ((array) $copyReposArr as $copyRepoId) {
                
                // Вземаме пълния път до хранилището
                $fullPathCopy = static::fetchField($copyRepoId, 'fullPath');
                
                // Вземаме пълния път до подпапката в хранилището
                $fullPathCopy = static::getFullPath($fullPathCopy, $subPath);
                
                // Пълния път до файла
                $filePathCopy = static::getFullPath($fullPathCopy, $fileName);
                
                // Проверяваме дали файла съществува
                $copyFileExist = static::checkFileExistInRepo($filePathCopy, $originalRepoId, $subPath);
                
                // Ако файлъъ съществува и не е форсирано записването
                if ($copyFileExist && !$forceSave) {
                    
                    // Добавяме в масива със съществуващи
                    $resArr['existing'][$copyRepoId] = $fileName;
                } else {
                    
                    // Копираме файла
                    $copied = copy($filePath, $filePathCopy);
                    
                    // Ако копирането е било успешно
                    if ($copied) {
                        
                        // Добавяме в копирания
                        $resArr['copied'][$copyRepoId] = $fileName;
                    } else {
                        
                        // Добавяме в копирания
                        $resArr['problem'][$copyRepoId] = $fileName;
                    }
                }
            }
            
            // Прекъсваме
            break;
        }
        
        return $resArr;
    }
    
    
    /**
     * Преименува подадения файл във всички хранилища
     *
     * @param string  $oldName   - Старото име на файла
     * @param string  $newName   - Новото име на файла
     * @param array   $reposArr  - Масив с хранилищата
     * @param string  $subPath   - Подпапката
     * @param boolean $forceSave - Дали да се форсира преименуването
     *
     * @return array $resArr
     *               array $resArr['existing'] - Файл с новото име съществува в хранилището
     *               array $resArr['notExist'] - Файл със стартото име несъществува в хранилището
     *               array $resArr['renamed'] - Успешно е преименуван файла
     *               array $resArr['problem'] - Проблем при преименуване на файла в хранилище
     */
    public static function renameFilesInRepos($oldName, $newName, $reposArr, $subPath = '', $forceSave = false)
    {
        // Резултатния масив
        $resArr = array();
        
        // Обхождаме масива с хранилищата
        foreach ((array) $reposArr as $repoId) {
            
            // Вземаме пълния път до хранилището
            $fullPath = static::fetchField($repoId, 'fullPath');
            
            // Вземаме пълния път до подпапката в хранилището
            $fullPath = static::getFullPath($fullPath, $subPath);
            
            // Пълния път до файла
            $oldFilePath = static::getFullPath($fullPath, $oldName);
            
            // Проверяваме дали файла съществува
            $fileExist = static::checkFileExistInRepo($oldName, $repoId, $subPath);
            
            // Ако файла не съществува
            if (!$fileExist) {
                
                // Добавяме в масива със несъществуващи
                $resArr['notExist'][$repoId] = $oldName;
            } else {
                
                // Името на новия файл
                $newFilePath = static::getFullPath($fullPath, $newName);
                
                // Проверяваме дали файла съществува
                $newFileExist = static::checkFileExistInRepo($newName, $repoId, $subPath);
                
                // Ако файла съществува и не е форсиран
                if ($newFileExist && !$forceSave) {
                    
                    // Добавяме в масива
                    $resArr['exist'][$repoId] = $newName;
                } else {
                    
                    // Преименуваме файла
                    $renamed = rename($oldFilePath, $newFilePath);
                    
                    // Ако е преименуван успешно
                    if ($renamed) {
                        
                        // Добавяме в масива
                        $resArr['renamed'][$repoId] = $newName;
                    } else {
                        
                        // Добавяме проблема в масива
                        $resArr['problem'][$repoId] = $newName;
                    }
                }
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Изтрива подадени файл от хранилищата
     *
     * @param string $fileName - Името на файла
     * @param array  $reposArr - Масив с хранилища
     * @param string $subPath  - Подпапка в хранилището
     *
     * @return array $resArr - Масив с изтритите данни
     *               array $resArr['notExist'] - Масив с несъществуващи файлове в дадено хранилище
     *               array $resArr['deleted'] - Масив с изтрити файлове в дадено хранилище
     *               array $resArr['isDir'] - Масив с файловете, които са директории в дадено хранилище
     *               array $resArr['problem'] - Масив с файлове, при които възниква проблем при изтриване
     */
    public static function deleteFileInRepos($fileName, $reposArr, $subPath = '')
    {
        // Резултата, който ще връщаме
        $resArr = array();
        
        // Преобразуваме в масив
        $reposArr = arr::make($reposArr);
        
        // Обхождаме масива
        foreach ((array) $reposArr as $repoId) {
            
            // Флаг, указващ дали файла съществува
            $fileExist = null;
            
            // Вземаме пълния път до хранилището
            $fullPath = static::fetchField($repoId, 'fullPath');
            
            // Вземаме пълния път до подпапката в хранилището
            $fullPath = static::getFullPath($fullPath, $subPath);
            
            // Пълния път до файла
            $filePath = static::getFullPath($fullPath, $fileName);
            
            // Проверяваме дали файла съществува
            $fileExist = static::checkFileExistInRepo($fileName, $repoId, $subPath);
            
            // Ако файла не съществува
            if (!$fileExist) {
                
                // Добавяме в масива със несъществуващи
                $resArr['notExist'][$repoId] = $fileName;
            } else {
                try {
                    
                    // Ако е файл
                    if (is_file($filePath)) {
                        
                        // Изтриваме файла
                        if (unlink($filePath)) {
                            
                            // Ако няма грешка, добавяме към изтритите
                            $resArr['deleted'][$repoId] = $fileName;
                        }
                    } else {
                        
                        // Ако не е файл
                        $resArr['isDir'][$repoId] = $fileName;
                    }
                } catch (core_exception_Expect $e) {
                    
                    // Ако възникне грешка, добавяме към грешките
                    $resArr['problem'][$repoId] = $fileName;
                }
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Екстрактване на файл в ОС. Връща пълния път до новия файл
     *
     * @param string $fh       - Манипулатор на файла, за който ще се създаде нова версия
     * @param string $path     - Пътя, където да се абсорбира файла
     * @param string $fileName - Името на файла
     *
     * @return string - Пътя до файла
     */
    public static function extract($fh, $path = null, $fileName = null)
    {
        return fileman::extract($fh, $path, $fileName);
    }
    
    
    /**
     * Връща масив с всички данни за файла
     *
     * @param string $filePath - Пътя до файла
     *
     * @return array $res - Масив с резултатите
     *               $res['modificationTime'] - Време на последна модификация
     *               $res['creationTime'] - Време на създаване
     *               $res['accessTime'] - Време на последен достъп до файла
     *               $res['fileSize'] - Размера на файла
     *               $res['fileType'] - Типа на файла
     *               $res['mimeType'] - Миме типа на файла
     *               $res['extension'] - Разширението на файла
     *               $res['isCorrectExt'] - Дали разширението на файла е в допусмите за миме типа
     */
    public static function getFileInfo($filePath)
    {
        return fileman::getInfoFromFilePath($filePath);
    }
    
    
    /**
     * Връща двумерен масив с всички папки и файловете в тях
     *
     * @param integer|stdClass $repoRec          - id|rec на хранилището
     * @param string           $subPath          - Подпапка в хранилището
     * @param boolean          $useFullPath      - Да се използва целия файл до папката
     * @param integer          $depth            - Дълбочината на папката, до която ще се търси
     * @param boolean          $useMTimeFromFile - Да се използва датата на последно модифициране на файловете
     *
     * @return array - Двумерен масив с всички папки и файловете в тях
     *               mTime - Дата на модифициране на директорията
     *               files - Файлове в директорията
     */
    public static function retriveFiles($repoRec, $subPath = '', $useFullPath = false, $depth = false, $useMTimeFromFile = false)
    {
        // Ако е обект
        if (!is_object($repoRec)) {

            // Очакваме да е число
            expect(is_numeric($repoRec));
            
            // Вземаме записа
            $repoRec = static::fetch($repoRec);
        }
        
        // Масива, който ще връщаме
        $res = array();
        
        // Вземаме пътя до поддиректорията на съответното репозитори
        $fullPath = static::getFullPath($repoRec->basePath, $repoRec->subPath);
        
        // Обединяваме с подадена поддиректория
        $fullPath = static::getFullPath($fullPath, $subPath);
        
        try {
            // Вземаме итератор
            // RecursiveIteratorIterator::SELF_FIRST - Служи за вземане и на директориите
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath), RecursiveIteratorIterator::SELF_FIRST);
        } catch (ErrorException $e) {
            self::logNotice('Не може да се обходи директорията', $repoRec->id);
            
            return $res;
        }
        
        // Сетваме флаговете
        // NEW_CURRENT_AND_KEY = FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::CURRENT_AS_FILEINFO
        // FilesystemIterator::KEY_AS_FILENAME - ->key() да връща името на файла
        // FilesystemIterator::CURRENT_AS_FILEINFO - ->current() да връща инстанция на SplInfo
        // FilesystemIterator::SKIP_DOTS - Прескача . и ..
        $iterator->setFlags(FilesystemIterator::NEW_CURRENT_AND_KEY | FilesystemIterator::SKIP_DOTS);
        
        // Обхождаме итератора
        while ($iterator->valid()) {
            
            //Флаг, за игнориране на файла
            $ignore = false;
            
            // Вземаме името на файла
            $fileName = $iterator->key();
            
            // Вземаме пътя
            $path = $iterator->current()->getPath();
            
            // Ако сме задали някаква дълбочина
            // Първата е 0
            if ($depth !== false) {
                
                // Вземаме текущута дълбочина
                $currentDepth = $iterator->getDepth();
                
                // Ако текущатат е повече от зададената
                if ($currentDepth > $depth) {
                    
                    // Преместваме итератора
                    $iterator->next();
                    
                    // Прескачаме, иначе ще се изпълни и останалта част от кода
                    continue;
                }
            }
            
            // Ако не е задедено да се използва целия път до файла
            if (!$useFullPath) {
                
                // Вземаме пътя без целия път
                $path = str_ireplace($fullPath, '', $path);
                
                // Ако няма път, за да не е празна стойност
                if (!$path) {
                    $path = '/';
                }
            }
            
            // Ако е директория
            if ($iterator->isDir()) {
                
                // Ако няма такъв запис
                if (!$res[$path]) {
                    
                    // Пътя до директорията
                    $path = static::getFullPath($path, $fileName);
                    
                    // Създаваме масив с директрояита
                    $res[$path] = array();
                    
                    // Добавяме времето
                    $res[$path]['mTime'] = $iterator->current()->getMTime();
                }
            } else {
                
                // Ако няма да се игнорира файла
                if (!static::isForIgnore($repoRec->ignore, $fileName)) {
                    
                    // Вземаме времето
                    $mTime = $iterator->current()->getMTime();
                    
                    // Добавяме в резултатите пътя и името на файла
                    $res[$path]['files'][$fileName] = $mTime;
                    
                    // Ако е зададено да се използва времето на последна променя на файла
                    if ($useMTimeFromFile) {
                        
                        // Ако времето на промяна на файла е след промяната на директорията
                        if ($mTime > $res[$path]['mTime']) {
                            
                            // Добавяме времето
                            $res[$path]['mTime'] = $mTime;
                        }
                    }
                }
            }
            
            // Преместваме итератора
            $iterator->next();
        }
        
        return $res;
    }
    
    
    /**
     * Проверява дали даден файл съществува в хранилището
     *
     * @param string  $fileName - Името на файла
     * @param integer $repoId   - id на хранилище
     * @param string  $subPath  - Подпапка в хранилището
     *
     * @return boolean
     */
    public static function checkFileExistInRepo($fileName, $repoId, $subPath = '')
    {
        // Ако не е подадено името на файла
        if (!$fileName) {
            return false;
        }
        
        // Вземаме записа
        $rec = static::fetch($repoId);
        
        // Вземаме пътя до поддиректорията на съответното репозитори
        $fullPath = static::getFullPath($rec->basePath, $rec->subPath);
        
        // Обединяваме с подадена поддиректория
        $fullPath = static::getFullPath($fullPath, $subPath);
        
        // Вземаме пътя до файла
        $filePath = static::getFullPath($fullPath, $fileName);
        
        // Ако файла съществува
        if (file_exists($filePath)) {
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Дали да се игнорира файла
     *
     * @param string $ignoreStr - Стринг с файловете за игнориране
     * @param string $fileName  - Име на файл
     *
     * @return boolean
     */
    public static function isForIgnore($ignoreStr, $fileName)
    {
        // Ако няма подаден стринг за игнориране, да не се игнорира файла
        if (!$ignoreStr) {
            return false;
        }
        
        // Масива със стринговете за игнориране
        // За да не се генерира всеки път
        static $ignoreStrArr = array();
        
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
            foreach ((array) $ignoreArr as $ignore) {
                
                // Тримваме текста
                $ignore = trim($ignore);
                
                // Ако няма стринг за игнориране прескачаме
                if (!$ignore) {
                    continue;
                }
                
                // Заместваме символите с генерираните числа
                $ignoreText = str_replace(
                    array('^', '$', '*', '-'),
                                          array($beginRand, $endRand, $starRand, $dashRand),
                    $ignore
                );
                
                // Ескейпваме останалите символи
                $ignoreText = preg_quote($ignoreText, '/');
                
                // Заместваме случайния текст за *, с шаблона за всички символи
                $ignoreText = str_replace($starRand, '(.*?)', $ignoreText);
                
                // Ако има '-' в текста
                if (strpos($ignoreText, $dashRand) !== false) {
                    
                    // Заместваме с празен текст '-' и вземаме текста
                    $text = str_replace($dashRand, '', $ignoreText);
                    
                    // Добавяме текста, за отказ
                    $ignoreText = "^((?!{$text}).)*$";
                }
                
                // Ако е зададено начоло на текст
                if (strpos($ignoreText, $beginRand) !== false) {
                    
                    // Заместваме с празен текст '^' и вземаме текста
                    $ignoreText = str_replace($beginRand, '', $ignoreText);
                    
                    // Добавяме начало за шаблона
                    $ignoreText = '^' . $ignoreText;
                }
                
                // Ако е зададено край на текст
                if (strpos($ignoreText, $endRand) !== false) {
                    
                    // Заместваме с празен текст '$' и вземаме текста
                    $ignoreText = str_replace($endRand, '', $ignoreText);
                    
                    // Добавяме край за шаблона
                    $ignoreText = $ignoreText . '$';
                }
                
                // Добавяме в блок
                $ignoreText = "({$ignoreText})";
                
                // Добавяме съответния шаблон, към всички
                $patternText .= ($patternText) ?  '|' . $ignoreText : $ignoreText;
            }
            
            // Получения шаблон го добавяме в масива
            $ignoreStrArr[$ignoreStrHash] = "/({$patternText})/iu";
        }
        
        // Ако има шаблона
        if ($patternText = $ignoreStrArr[$ignoreStrHash]) {
            
            // Проверяваме дали се съдържа
            $match = preg_match($patternText, $fileName);
            
            // Връщаме резултата
            return (boolean) $match;
        }
        
        return false;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Ако екшъна е retrive и сме дефинира роли за достъп до хранилището
        // И текущия потребител няма такава
        if ($action == 'retrive') {
            
            // Ако хранилището е премахнато
            if ($rec === false) {
                $requiredRoles = 'no_one';
            }
            
            // Ако няма роля admin
            // admin трябва да има достъп
            if (!haveRole('admin')) {
                
                // Роли за достъп
                $rolesForAccess = trim($rec->rolesForAccess);
                
                // Потребители, които имат достъп
                $usersForAccess = trim($rec->usersForAccess);
                
                // Флаг, указващ дали има права
                $haveRole = false;
                
                // Ако има зададени права или потребилите
                if ($rolesForAccess || $usersForAccess) {
                    
                    // Ако има зададени права и потребителя има такива
                    if ($rolesForAccess && haveRole($rolesForAccess)) {
                        
                        // Вдигаме флага
                        $haveRole = true;
                    }
                    
                    // Ако е зададен съответния потребител
                    if ($usersForAccess && type_Keylist::isIn($userId, $usersForAccess)) {
                        
                        // Вдигаме флага
                        $haveRole = true;
                    }
                }
                
                // Ако флага не е вдигнат
                if (!$haveRole) {
                    
                    // Да не може да пипа
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Ако има запис и се опитваме да изтрием
        if ($rec && ($action == 'delete')) {
            
            // Ако състоянието е активно
            if ($rec->state == 'active' || $rec->state == 'rejected') {
            
                // Да не може да се изтрие
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако екшъна е сингъл
        if ($action == 'single') {
            
            // Ако няам права за retrive
            if (!static::haveRightFor('retrive', $rec, $userId)) {
                
                // Да няма права и за сингъла
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Активира състоянието на хранилището
     *
     * @param integer $id - id на хранилище
     *
     * @return integer - id на записа, ако се е активирал
     */
    public static function activateRepo($id)
    {
        // Вземаем записа
        $rec = static::fetch($id);
        
        // Ако не е бил активиран
        if ($rec->state != 'active') {
            
            // Активираме
            $rec->state = 'active';
            
            return static::save($rec);
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
     * След подготовка на сингъла
     *
     * @param fileman_Repositories $mvc
     * @param stdClass             $res
     * @param stdClass             $data
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Подготвяме формата за филтриране
        $mvc->prepareSingleFilter($data);
        
        // Избраните филтри
        $filterRec = $data->singleFilter->rec;
        
        // Ако се търси
        if ($filterRec->searchName) {
            
            // Добавяме да се игнорират всички файлове, които не съдържат филтъра на латиница
            $data->rec->ignore .= "\n" . '-' . str::utf2ascii($filterRec->searchName);
        }
        
        // Подготвяме файловете
        $mvc->prepareFiles($data);
        
        // Подреждаме папките и файловете
        $mvc->orderFoldersAndFiles($data);
        
        // Подготвяме пейджъра
        $mvc->prepareSinglePager($data);
        
        // Задаваме лимита
        $mvc->setLimit($data);
        
        // Подготвяме дървото с файловете
        $mvc->prepareFileTree($data);
    }
    
    
    /**
     * Подготвяме файловете
     *
     * @param object $data - Данните
     */
    public function prepareFiles($data, $subPath = '')
    {
        // Вземаме съдържанието
        $foldersArr = static::retriveFiles($data->rec, $subPath, false, false, true);
        
        // Обхождаме масива
        foreach ((array) $foldersArr as $path => $filesArr) {
            
            // Ако има файлове
            if ($filesArr['files']) {
                
                // Вземаме броя им
                $cnt = count($filesArr['files']);
                
                // Добавяме масива
                $data->fileTreeArr[$path] = $filesArr;
                
                // Увеличаваме бройката
                $data->filesCnt += $cnt;
            } else {
                
                // Ако е зададено да се показват и празните папки
                if ($data->useEmptyFolders) {
                    
                    // Добавяме масива
                    $data->fileTreeArr[$path] = $filesArr;
                }
            }
        }
    }
    
    
    /**
     * Извиква се след подготвяне на данните за файловото дърво
     *
     * @param fileman_Repositories $mvc
     * @param object               $res
     * @param object               $data
     * @param string               $subPath
     */
    public function on_AfterPrepareFileTree($mvc, &$res, $data)
    {
        // Масив с папките и файловете
        $foldersArr = $data->fileTreeArr;
        
        // Ако няма, да нищо
        if (!$foldersArr) {
            return ;
        }
        
        // Инстанция на класа
        $tableInst = cls::get('core_Tree');
        
        // Брояча
        $c = 0;
        
        // Обхождаме масива
        foreach ((array) $foldersArr as $path => $filesArr) {
            
            // Вземаме файловете
            $filesArr = (array) $filesArr['files'];
            
            // Заместваме разделителите за поддиректория с разделителя за дърво
            $pathEntry = str_replace(array('/', '\\'), '->', $path);
            
            // Ако e празна директория
            if (!count($filesArr)) {
                
                // Ако е зададено да се показват и празните директории
                if ($data->useEmptyFolders) {
                    
                    // Добавяме директорията
                    $tableInst->addNode($pathEntry, false, true);
                }
            } else {
                
                // Обхождаме файловете
                foreach ((array) $filesArr as $file => $modifiedTime) {
                    
                    // Ако сме в границита на брояча
                    if (($c >= $data->singlePager->rangeStart) && ($c < $data->singlePager->rangeEnd)) {
                        
                        // Увеличаваме брояча
                        $c++;
                    } else {
                        
                        // Увеличаваме брояча
                        $c++;
                        
                        // Ако сме достигнали горната граница, да се прекъсне
                        if ($data->singlePager->rangeEnd < $c) {
                            break;
                        }
                        
                        // Прескачаме
                        continue;
                    }
                    
                    // Тримваме, за да премахнем последния
                    $filePathEntry = rtrim($pathEntry, '->');
                    
                    // Вземаме пътя до файла
                    $filePathEntry = $filePathEntry . '->' . $file;
                    
                    // Ако е бил модифициран преди последното позволено време за абсорбиране
                    if (static::checkLastModified($modifiedTime)) {
                        
                        // URL за абсорбиране на файла
                        $urlPath = static::getAbsorbUrl($data->rec->id, $file, $path);
                    } else {
                        
                         // Да няма URL за абсорбиране на файла
                        $urlPath = false;
                    }
                    
                    // Добавяме в дървото
                    $tableInst->addNode($filePathEntry, $urlPath, true);
                }
            }
        }
        
        // Името
        $tableInst->name = 'file';
        
        // Добавяме масива
        $data->fileTree = $tableInst;
    }
    
    
    /**
     * След подготовка на единичния изглед
     *
     * @param fileman_Repositories $mvc
     * @param core_ET              $tpl
     * @param object               $data
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        // Към шаблона добавя дървото с файла
        $tpl->append($mvc->renderFileTree($data), 'FileTree');
        
        // Рендираме филтъра
        $tpl->append($mvc->renderSingleFilter($data), 'SingleFilter');
        
        // Рендираме пейджъра
        $tpl->append($mvc->renderSinglePager($data), 'SinglePager');
    }
    
    
    /**
     * Връща в дървовидна структура съдържанието на хранилището
     *
     * @param integer $id      - Хранилище
     * @param string  $subPath - Подпапка в хранилището
     *
     * @return core_Et $res
     */
    public function on_AfterRenderFileTree($mvc, &$res, $data)
    {
        // Ако няма файлове
        if ($data->fileTree) {
            
            // Рендираме изгледа
            $res = $data->fileTree->renderHtml(null);
        }
        
        // Ако няма файлове
        if (!$res) {
            
            // Добаваме съобщението
            $res = new ET(tr('Няма файлове'));
        }
    }
    
    
    /**
     * Екшън за абсорбиране на файла
     */
    public function act_AbsorbFile()
    {
        // id на хранилището
        $id = Request::get('id', 'int');
        
        // Подпапката
        $subPath = Request::get('subPath');
        
        // Относителен път до файла в хранилището
        $file = Request::get('file');
        
        // Вземамем записа
        $rec = static::fetch($id);
        
        // Вземаем пътя до файла
        $path = static::getFullPath($rec->basePath, $rec->subPath);
        $path = static::getFullPath($path, $subPath);
        $path = static::getFullPath($path, $file);
        
        // Времето на последна модификация на файла
        $lastModified = fileman::getModificationTimeFromFilePath($path);
        
        // Очакваме да е била преди зададеното от нас
        expect(static::checkLastModified($lastModified), 'Файлът току що е бил променян');
        
        // Абсорбираме файла и вземаме манипулатора му
        $fh = static::absorbFileFromId($id, $file, $subPath);
        
        // Линк към сингъла на файла
        $singleUrl = fileman::getUrlToSingle($fh);
        
        // Редиректваме към сингъла
        return new Redirect($singleUrl);
    }
    
    
    /**
     * Проверява дали датата на последната модификация е била преди разрешеното време
     *
     * @param timestamp $lastModified - Времето с което искаме да сравняваме
     *
     * @return boolean
     */
    public static function checkLastModified($lastModified)
    {
        // От текущото време изваждаме последно секундите за модифициране
        $modifiedToUseTime = dt::mysql2timestamp(dt::now()) - static::DONT_USE_MODIFIED_SECS;
        
        // Ако последната модификация е била преди разрешената от нас
        if ($lastModified < $modifiedToUseTime) {
            return true;
        }
    }
    
    
    /**
     * Връща URL към екшъна за абсорбиране на съответния файл
     *
     * @param integer $id       - id на хранилищетп
     * @param string  $file     - Името на файла
     * @param string  $subPath  - Подпапка в хранилището
     * @param boolean $absolute - Дали линка е да абсолютен
     *
     * @return string
     */
    public static function getAbsorbUrl($id, $file, $subPath = '', $absolute = false)
    {
        // Очакваме да има id
        expect($id);
        
        // Вземаме URL' то
        $url = toUrl(array('fileman_Repositories', 'absorbFile', $id, 'file' => $file, 'subPath' => $subPath), $absolute);
        
        return $url;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        // Масив с пътищата до хранилищата
        $repositoryPathArr = arr::make(EF_REPOSITORIES_PATHS);
        
        // Обхождаме всички пътища
        foreach ($repositoryPathArr as $repositoryPath) {
            
            // Флаг, указващ дали има грешки
            $haveError = false;
            
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
                    $haveError = true;
                }
                
                // Ако нямаме права за запис
                if (!is_writable($repositoryPath)) {
                    
                    // Добавяме грешката
                    $res .= "<li style='color:red'>Нямате права за запис в: " . $repositoryPathEsc;
                    
                    // Вдигаме флага
                    $haveError = true;
                }
                
                // Ако име грешки прескачаме
                if ($haveError) {
                    continue;
                }
                
                // Отбелязваме, че директорията съществува
                $res .= '<li>Съществуваща директория: ' . $repositoryPathEsc;
            } else {
                
                // Ако може да се създаде хранилището
                if (@mkdir($repositoryPath, 0777, true)) {
                    
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
        $res .= $Bucket->createBucket(self::$bucket, 'Файлове в хранилищата', null, '104857600', 'user', 'user');
    }
    
    
    /**
     * Проверява дали даден потребител има достъп до някое хранилище от масива
     *
     * @param array   $reposArr - масив с id-та на хранилища
     * @param integer $userId   - id на потребителя
     *
     * @return boolean
     */
    public static function canAccessToSomeRepo($reposArr, $userId = null)
    {
        // Обхождаме масива
        foreach ((array) $reposArr as $repo) {
            
            // Ако има права
            if (static::haveRightFor('retrive', $repo, $userId)) {
                
                // Връщаме
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща масив с хранилището и вербалното му име, ако потребителя има достъп до него
     *
     * @param array   $id     - масив с id-та на хранилища
     * @param integer $userId - id на потребителя
     *
     * @return array $accessedReposArr - Масив с хранилища и вербалните им имена
     */
    public static function getAccessedReposArr($reposArr, $userId = null)
    {
        // Масива, който ще връщаме
        $accessedReposArr = array();
        
        // Обхождаме масива
        foreach ((array) $reposArr as $repoId) {
            
            // Ако имаме права
            if (static::haveRightFor('retrive', $repoId, $userId)) {
                
                // Добавяме в масива
                $accessedReposArr[$repoId] = static::getRepoName($repoId);
            }
        }
        
        return $accessedReposArr;
    }
    
    
    /**
     * Връща вербалното име на хранилището
     *
     * @param integer $repoId - id на хранилището
     *
     * @return string $name - Вербалното име на хранилището
     */
    public static function getRepoName($repoId)
    {
        // Вземаме вербалното име
        $name = static::getVerbal($repoId, 'verbalName');
        
        return $name;
    }
    
    
    /**
     * Връща линк към сингъла на документа
     *
     * @param integer $id        - id на записа
     * @param string  $fieldName - Името на полето, което ще се използва за линк
     * @param boolean $absolute
     * @param array   $attr
     *
     * @return core_Et - Линк към сингъла
     *
     * @Override
     * @see core_Master::getLinkToSingle_
     */
    public static function getLinkToSingle_($repoId, $fieldName = null, $absolute = false, $attr = array())
    {
        $attr = arr::make($attr);
        
        // Ако не е зададено
        if (!$fieldName) {
            
            // Задаваме името на полето
            $fieldName = 'verbalName';
        }
        
        $rec = self::fetch($repoId);
        
        if ($rec->state == 'rejected') {
            $attr['class'] .= ' state-rejected';
        }
        
        return parent::getLinkToSingle_($repoId, $fieldName, $absolute, $attr);
    }
    
    
    /**
     * Връща масив с всички хранилища
     *
     * @return array $reposArr - Масив с id-та на всички хранилища
     */
    public static function getReposArr()
    {
        // Масив с всички хранилища
        static $reposArr = array();
        
        // Ако не е генериран преди
        if (!$reposArr) {
            
            // Вземаме всички записи
            $query = static::getQuery();
            $query->where("#state != 'rejected'");
            
            // Обхождаме записите
            while ($rec = $query->fetch()) {
                
                // Добавяме в масива
                $reposArr[$rec->id] = $rec->id;
            }
        }
        
        return $reposArr;
    }
    
        
    /**
     * Създава директория в подаденото хранилище
     *
     * @param integer $repositoryId - id на хранилището
     * @param string  $subPath      - Подпапка в хранилището
     *
     * @return boolean - При успех връща TRUE
     */
    public static function createDirInRepo($repositoryId, $dir)
    {
        // Вземаме записа
        $rec = static::fetch($repositoryId);
        
        // Добавяме директорията към пътя
        $fullPath = static::getFullPath($rec->fullPath, $dir);
        
        // Създаваме директрията
        if (@mkdir($fullPath, 0777, true)) {
            
            // Ако се създаде, връщаме истина
            return true;
        }
    }
    
    
    /**
     * Подготвя формата за филтриране
     *
     * @param stdClass $data
     */
    public function prepareSingleFilter_($data)
    {
        // Ако не е подговено преди
        if (!$data->singleFilter) {
            $formParams = array(
                'method' => 'GET',
            );
            
            $data->singleFilter = $this->getForm($formParams);
        }
        
        return $data;
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public function on_AfterPrepareSingleFilter($mvc, $data)
    {
        // Добавяме поле за търсене по име на файл
        $data->singleFilter->FNC('searchName', 'varchar', 'placeholder=Име на файл,caption=Търсене,input,silent,recently');
        
        // Добавяме поле за подредба
        $data->singleFilter->FNC(
            'orderBy',
            'enum(nameDown=Наименование|* ↓, nameUp=Наименование|* ↑, createdDown=Създаване|* ↓, createdUp=Създаване|* ↑)',
                    'placeholder=Подредба,caption=Подредба,input,silent,allowEmpty,autoFilter'
        );
        
        // Кои полета да се показват
        $data->singleFilter->showFields = 'searchName, orderBy';
        
        // Как да се показват
        $data->singleFilter->view = 'horizontal';
        
        // Добавяме бутон за филтриране
        $data->singleFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Активиране на филтъра
        $data->singleFilter->input('searchName, orderBy', 'silent');
    }
    
    
    /**
     * Рендира формата за филтриране на сингъл изглед
     *
     * @param stdClass $data
     */
    public function renderSingleFilter_($data)
    {
        // Ако има полета, които да се покажат
        if (count($data->singleFilter->showFields)) {
            
            // Добавяме филтъра
            return new ET("<div class='singleFilter'>[#1#]</div>", $data->singleFilter->renderHtml(null, $data->singleFilter->rec));
        }
    }
    
    
    /**
     * Подготвя навигацията по страници
     *
     * @param unknown_type $data
     */
    public function prepareSinglePager_(&$data)
    {
        // Изчисляваме броя на елементите на страница
        $perPage = (Request::get('PerPage', 'int') > 0 && Request::get('PerPage', 'int') <= 10000) ?
        Request::get('PerPage', 'int') : $this->singleItemsPerPage;
        
        // Ако има
        if ($perPage) {
            
            // Добавяме пейджър
            $data->singlePager = & cls::get('core_Pager', array('pageVar' => 'P_' . $this->className));
            $data->singlePager->itemsPerPage = $perPage;
        }
    }
    
    
    /**
     * Задаваме броя на всички елементи
     *
     * @param object $data
     */
    public function setLimit($data)
    {
        // Задаваме броя на страниците
        $data->singlePager->itemsCount = $data->filesCnt;
        
        // Изчисляваме
        $data->singlePager->calc();
    }
    
    
    /**
     * Рендира  навигация по страници
     *
     * @param object $data
     */
    public function renderSinglePager_($data)
    {
        // Ако има странициране
        if ($data->singlePager) {
            
            // Рендираме
            return $data->singlePager->getHtml();
        }
    }
    
    
    /**
     * Подрежда файлаовете по зададените критерии във филтъра
     *
     * @param object $data
     */
    public static function orderFoldersAndFiles($data)
    {
        // Ако няма масив с файлове, връщаме
        if (!$data->fileTreeArr) {
            return ;
        }
        
        // Вземаме масива
        $foldersArr = $data->fileTreeArr;
        
        // Вземаме подреждането
        $orderBy = $data->singleFilter->rec->orderBy;
        
        // В зависимост от вида
        if (!$orderBy || $orderBy == 'nameDown') {
            $type = 'name';
            $order = 'DESC';
        } elseif ($orderBy == 'nameUp') {
            $type = 'name';
            $order = 'ASC';
        } elseif ($orderBy == 'createdDown') {
            $type = 'created';
            $order = 'DESC';
        } elseif ($orderBy == 'createdUp') {
            $type = 'created';
            $order = 'ASC';
        }
        
        // Подреждеаме масива по зададените критерии
        $data->fileTreeArr = static::orderFolderAndFilesArr($data->fileTreeArr, $type, $order);
    }
    
    
    /**
     * Подрежда масива с папки и файлове в зададен критерий
     *
     * @param array  $foldersArr - Масив с файловете
     * @param string $type       - Типа на подреждане - name, created
     * @param string $order      - Вида на подреждане - DESC, ASC
     */
    public static function orderFolderAndFilesArr($foldersArr, $type, $order)
    {
        // Ако няма папки
        if (!is_array($foldersArr)) {
            return ;
        }
        
        /*
         * SORT_FLAG_CASE се използва само в PHP 5.4 и затова не използваме krsort и ksort
         */
        
        // Папките да са преди файловете и подредени по имена в намаляващ ред
        uksort($foldersArr, 'static::orderDesc');
//        krsort($foldersArr, SORT_STRING | SORT_FLAG_CASE);
        
        // Обхождаме всички файлове в папките
        foreach ($foldersArr as &$filesArr) {
            
            // Ако няма файлове, прескачаме
            if (!is_array($filesArr['files'])) {
                continue;
            }
            
            // Ако типа е зададено да се подреждат по име
            if ($type == 'name') {
                
                // В намаляващ ред
                if ($order == 'DESC') {
                    
                    // Подреждаме файлове
                    uksort($filesArr['files'], 'static::orderDesc');
//                    krsort($otherArr['files'], SORT_STRING | SORT_FLAG_CASE);
                }
                
                // В увеличаващ ред
                if ($order == 'ASC') {
                    
                    // Подреждаме файловете
                    uksort($filesArr['files'], 'static::orderAsc');
//                    ksort($otherArr['files'], SORT_STRING | SORT_FLAG_CASE);
                }
            } elseif ($type == 'created') {
                
                // Ако е задедено да се подреждат по създаване
                
                // В намаляващ ред
                if ($order == 'DESC') {
                    
                    // Подреждаме
                    array_multisort($filesArr['files'], SORT_DESC);
                }
                
                // В нарастващ ред
                if ($order == 'ASC') {
                    
                    // Подреждаме
                    array_multisort($filesArr['files'], SORT_ASC);
                }
            }
        }
        
        return $foldersArr;
    }
    
    
    /**
     * Сравява два стринга и ги подрежда в намалящ ред. Извиква се от uksort().
     * За разлика от krsort мож да сравнява главни и малки букви в PHP. SORT_FLAG_CASE се използва само в PHP 5.4
     *
     * @param string $str1
     * @param string $str2
     *
     * @return integer
     */
    public static function orderDesc($str1, $str2)
    {
        $str1 = mb_strtolower($str1);
        
        $str2 = mb_strtolower($str2);
        
        if ($str1 > $str2) {
            return -1;
        }
        
        if ($str1 == $str2) {
            return 0;
        }
        
        if (!$str1 < $str2) {
            return 1;
        }
    }
    
    
    /**
     * Сравява два стринга и ги подрежда в намалящ ред. Извиква се от uksort().
     * За разлика от ksort мож да сравнява главни и малки букви в PHP. SORT_FLAG_CASE се използва само в PHP 5.4
     *
     * @param string $str1
     * @param string $str2
     *
     * @return integer
     */
    public static function orderAsc($str1, $str2)
    {
        $str1 = mb_strtolower($str1);
        
        $str2 = mb_strtolower($str2);
        
        if ($str1 > $str2) {
            return 1;
        }
        
        if ($str1 == $str2) {
            return 0;
        }
        
        if (!$str1 < $str2) {
            return -1;
        }
    }
}
