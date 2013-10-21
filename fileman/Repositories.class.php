<?php
defIfNot('EF_REPOSITORIES_PATHS',  EF_UPLOADS_PATH . '/repositories/s/b, /home/developer/aaa,' . EF_UPLOADS_PATH . '/repositories2, /../../, ../var/');

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
class fileman_Repositories extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Път до хранилище";
    
    
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
     * Плъгини за зареждане
     */
    var $loadList = 'fileman_Wrapper, plg_RowTools, plg_Created';
    
    
    /**
     * 
     */
    static $bucket = 'repositories';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('basePath', 'varchar(readonly)', 'caption=Хранилище, mandatory, width=100%');
        $this->FLD('subPath', 'varchar', 'caption=Подпапка, width=100%');
        $this->FLD('rolesForAccess', 'key(mvc=core_Roles, select=role, allowEmpty)', 'caption=Роля за достъп, width=100%,placeholder=Всички');
        $this->FLD('ignore', 'text', 'caption=Служебни файлове, width=100%');
        
//        $this->setDbUnique('basePath, subPath, rolesForAccess');
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
        
        // Избираме първия, по подразбиране
        $data->form->setDefault('basePath', key($basePathsArr));
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
     * 
     * @return array - Масив с всички папки и файловете в тях
     */
    static function retriveFiles($repositoryId, $subPath = '')
    {
        // Очакваме да е число
        expect(is_numeric($repositoryId));
        
        // Вземаме записа
        $rec = static::fetch($repositoryId);
        
        // Вземаме пътя до поддиректорията на съответното репозитори
        $fullPath = static::getFullPath($rec->basePath, $rec->subPath);
        
        // Обединяваме с подадена поддиректория
        $fullPath = static::getFullPath($fullPath, $subPath);
        
        // Вземаме итератор
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath));
        
        // Сетваме флаговете
        // NEW_CURRENT_AND_KEY = FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::CURRENT_AS_FILEINFO
        // FilesystemIterator::KEY_AS_FILENAME - ->key() да връща името на файла
        // FilesystemIterator::CURRENT_AS_FILEINFO - ->current() да връща инстанция на SplInfo
        // FilesystemIterator::SKIP_DOTS - Прескача . и ..
        $iterator->setFlags(FilesystemIterator::NEW_CURRENT_AND_KEY | FilesystemIterator::SKIP_DOTS);
        
        // Ако има файлове за игнориране
        if (trim($rec->ignore)) {
            
            // Масив с файловете, които да се игнорират
            $ignoreArr = arr::make($rec->ignore, TRUE);
        }
        
        // Обхождаме итератора
        while($iterator->valid()) {
            
            //Флаг, за игнориране на файла
            $ignore = FALSE;
            
            // Вземаме името на файла
            $fileName = $iterator->key();
            
            // Ако е сетнат масива за игнорирани файлове
            if ($ignoreArr) {
                
                // Обхождаме масива
                foreach ((array)$ignoreArr as $ignoreStr) {
                    
                    // Ако в името на файла се съдържа стринга за игнориране
                    if (stripos($fileName, $ignoreStr) !== FALSE) {
                        
                        // Вдигаме флага
                        $ignore = TRUE;
                        
                        // Прекъсваме цикъла
                        break;
                    }
                }
            }
            
            // Ако няма да се игнорира файла
            if (!$ignore) {
                
                // Вземаме пътя до файла
                $path = $iterator->current()->getPath();
                
                // Добавяме в резултатите пътя и името на файла
                $res[$path][$fileName] = TRUE;
            }
            
            // Прескачаме на следващия
            $iterator->next();
        }
        
        return $res;
    }
}
