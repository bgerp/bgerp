<?php


/**
 * Драйвер за работа с архиви.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Archive extends fileman_webdrv_Generic
{
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Generic::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Директорията, в която се намираме вътре в архива
        $path = core_Type::escape(Request::get('path'));
        
        // Вземаме съдържанието
        $contentStr = static::getContent($fRec, $path);
        
        // Таб за съдържанието
		$tabsArr['content'] = (object) 
			array(
				'title'   => 'Съдържание',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><fieldset class='webdrvFieldset'><legend>Съдържание</legend>{$contentStr}</fieldset></div>",
				'order' => 1,
			);
        
        return $tabsArr;
    }
    
    
    /**
     * Връща съдържанието за записа в архива на текущата директория
     * 
     * @param fileman_Files $frec - Запис на архива
     * @param string $path - Директорията директорията във файла
     * 
     * @return string $dirsAndFilesStr - Стринг с всички директории и файлове в текущата директория
     */
    static function getContent($fRec, $path = NULL) 
    {
        // Конфигурационните константи
        $conf = core_Packs::getConfig('fileman');
        
        // Записите за файла
        $dataRec = fileman_Data::fetch($fRec->dataId);
        
        // Дължината на файла
        $fLen = $dataRec->fileLen;
        
        // Ако дължината на файла е по голяма от максимално допустимата
        if ($fLen >= $conf->FILEINFO_MAX_ARCHIVE_LEN) {
            
            // Инстанция на класа
            $fileSizeInst = cls::get('fileman_FileSize');
            
            // Създаваме съобщение за грешка
            $text = "Архива е много голям: " . fileman_Data::getVerbal($dataRec, 'fileLen');
            $text .= "\nДопустимият размер е: " . $fileSizeInst->toVerbal($conf->FILEINFO_MIN_FILE_LEN_BARCODE);
            
            return $text;
        }
        
        // Създаваме инстанция
        $zip = new ZipArchive();
        
        // Очакваме да може да се създане инстация
        expect($zip);
        
        // Резултата, който ще върнем
        $dirsAndFilesStr = '';
        
        // Ако е зададен пътя
        if ($path) {
            
            // Създаваме линк от пътя, който да сочи към предишната директория
            $link = ht::createLink($path, static::getBackFolderLink());
            
            // Иконата на файла
            $sbfIcon = sbf('/img/16/back16.png',"");

            // Добавяме към стринга линк с икона
            $dirsAndFilesStr = "<span class='linkWithIcon' style='background-image:url($sbfIcon);'>{$link}</span>";
        }

        // Пътя до архива
        $filePath = fileman_Files::fetchByFh($fRec->fileHnd, 'path');
        
        // Отваряме архива да четем от него
        $open = $zip->open($filePath, ZIPARCHIVE::CHECKCONS);

        // Очакваме да няма грешки при отварянето
        expect(($open === TRUE), 'Възникна грешка при отварянето на файла.');
        
        // Броя на всички документи в архива
        $numFiles = $zip->numFiles;
        
        // Обхождаме всички документи в архива
        for ($i=0; $i < $numFiles; $i++) {
            
            // Създаваме масив с файлове в архива
            $zipContentArr[$i] = $zip->statIndex($i);
        }
        
        // Вземаме всики директории и файлове в текущада директория на архива
        $filesArr = static::getFiles($zipContentArr, $path);
        
        // Подговаме стринга с папките
        $dirsStr = static::prepareDirs((array)$filesArr['dirs'], $path);
        
        // Подготвяме стринга с файловете
        $filesStr = static::prepareFiles((array)$filesArr['files'], $fRec->fileHnd);
        
        // Ако има папки
        if ($dirsStr) {
            
            // Ако се намираме в поддиреткрия, добавяме интервал преди папките
            if ($path) $dirsAndFilesStr .=  "\n";
            
            // Добавяме папките
            $dirsAndFilesStr .= $dirsStr;    
        }
        
        // Ако има файлове, добавяме ги към стринга
        if ($filesStr) $dirsAndFilesStr .= "\n" . $filesStr;

        // Затваряме връзката
        $zip->close();
        
        // Връщаме стринга с файловете и документите
        return $dirsAndFilesStr;
    }
    
    
    /**
     * Екшън за абсорбиране на файлове от архива
     */
    function act_Absorb()
    {
        // Манипулатора на архива
        $fh = $this->db->escape(Request::get('id'));
        
        // Индекса на файла
        $index = Request::get('index', 'int');
        
        // Записите за съответния архив
        $rec = fileman_Files::fetchByFh($fh);
        
        // Изискваме да име права за single
        fileman_Files::requireRightFor('single', $rec);
        
        // Инстанция на класа
        $zip = new ZipArchive();
        
        // Очакваме да няма грешка
        expect($zip);
        
        //Пътя до файла
        $filePath = fileman_Files::fetchByFh($fh, 'path');
        
        // Отваряме файла за четене
        $open = $zip->open($filePath, ZIPARCHIVE::CHECKCONS);

        // Очакваме да няма проблеми при отварянето
        expect(($open === TRUE), 'Възникна грешка при отварянето на файла.');
        
        // Вземаме съдържанието на файла
        $fileContent = $zip->getFromIndex($index);
        
        // Пътя до файла в архива
        $path = $zip->getNameIndex($index);
        
        // Името на файла
        $name = basename($path);
        
        // Затваряме връзката
        $zip->close();
        
        // Очакваме да има съдържание
        expect($fileContent, 'Файлът няма съдържание');
        
        // Инстанция на fileman
        $filesInst = cls::get('fileman_Files');
    
        // Добавяме файла в кофата
        $fh = $filesInst->addNewFileFromString($fileContent, 'archive', $name);
        
        // Очакваме да няма грешка при добавянето
        expect($fh, 'Възникна грешка при обработката на файла');
        
        // Редиреткваме към single'а на качения файл
        return new Redirect(array('fileman_Files', 'single', $fh, '#' => 'fileDetail'));    
    }
    
    
    /**
     * Връща всики файлове и директории в текущия път
     * 
     * @param array $zipContentArr - Масив с всички файлове и директории в архива
     * @param string $path - Директорията в която търсим
     * 
     * @return array $dirAndFiles - Масив с всички директории и файлове в архива
     * 				 $dirAndFiles['dirs'] - Всички директории в текущата директория на архива
     * 				 $dirAndFiles['files'] - Всички файлове в текущата директория на архива
     */
    static function getFiles($zipContentArr, $path=NULL) 
    {
        // Масив с всички файлове и директории
        $dirAndFiles = array();
        
        // Масив с всички директории и поддиректории
        $filesArr = array();
        
        // Дълбочината на директорията
        $depth = 0;
        
        // Обхождаме масива с всички директории и файлове в архива
        foreach ($zipContentArr as $zipContent) {
            
            // Създаваме масив с всички директории и поддиректории
            $filesArr[$zipContent['index']] = (explode('/', $zipContent['name']));
        }
        
        // Ако е зададен пътя, определяме дълбочината
        if ($path) {

            // Намираме дълбочината на директорията
            $pathArr = explode('/', $path);  
            $depth = count($pathArr);  
        }
        
        // Обхождаме всики директории и файлове
        foreach ($filesArr as $index=>$file) {
            
            // В зависимост от дълбочината обхождаме файловете
            for($i=0; $i<$depth; $i++) {
                
                // Дали да прескочи
                $continue = FALSE;

                // Ако пътя до файла е различен от директорията
                if ($file[$i] != $pathArr[$i]) {
                    
                    // Задаваме да се прескочи
                    $continue = TRUE;
                    
                    // Прескачаме вътрешния цикъл
                    break;
                }
            }
            
            // Ако не сме в зададената директория прескачаме
            if (($continue) || !$file[$depth]) continue;
            
            // Ако е директория
            if (isset($file[$depth+1])) {
                
                // Добавяме името на файла и индекса
                $dirAndFiles['dirs'][$file[$depth]] = $index;
            } else {
                
                // Ако не е директория, трябва да е файл
                $dirAndFiles['files'][$file[$depth]] = $index;
            }
        }
        
        return $dirAndFiles;
    }
    
    
    /**
     * Подготвя стринга с папките
     * 
     * @param array $filesArr - Масив с всики директории и файлове в текущада директория на архива
     * @param string $path - Пътя до файла в архива
     */
    static function prepareDirs($filesArr, $path)
    {
        // Обхождаме всики директории
        foreach ($filesArr as $file => $index) {
            
            // Иконата за папките
            $icon = "img/16/folder.png";
            
            // Генерираме новия път
            $newPath = ($path) ? $path . "/". $file : $path . $file;
            
            // Вземаме текущото URL
            $url = getCurrentUrl();
            
            // Променяме пътя
            $url['path'] = $newPath;
            
            // Създаваме линк
            $link = ht::createLink($file, $url);
            
            // SBF иконата
            $sbfIcon = sbf($icon,"");
            
            // Създаваме стринга
            $foldersStr = "<span class='linkWithIcon' style='background-image:url($sbfIcon);'>{$link}</span>";
            $text .= ($text) ? "\n" . $foldersStr : $foldersStr;
        }
        
        return $text;
    }
    
    
    /**
     * Подготвя стринга с файловете
     * 
     * @param array $filesArr - Масив с всики директории и файлове в текущада директория на архива
     * @param string $fileHnd - Манипулатора на архива
     */
    static function prepareFiles($filesArr, $fileHnd)
    {
        // Обхождаме вски файлове в текущата директория
        foreach ($filesArr as $file => $index) {
            
            //Разширението на файла
            $ext = fileman_Files::getExt($file);
            
            //Иконата на файла, в зависимост от разширението на файла
            $icon = "fileman/icons/{$ext}.png";
            
            //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
            if (!is_file(getFullPath($icon))) {
                $icon = "fileman/icons/default.png";
            }
            
            // Иконата в SBF директорията
            $sbfIcon = sbf($icon,"");
            
            // Създаваме линк, който сочи към екшън за абсорбиране на файла
            $link = ht::createLink($file, array('fileman_webdrv_Archive', 'absorb', $fileHnd, 'index' => $index), NULL, array('target'=>'_blank'));
            
            // Създаваме стринга
            $fileStr = "<span class='linkWithIcon' style='background-image:url($sbfIcon);'>{$link}</span>";
            $text .= ($text) ? "\n" . $fileStr : $fileStr;
        }
        
        return $text;
    }

    
    /**
     * Връща линка към предишната директория
     */
    static function getBackFolderLink()
    {
        // Вземаме текущото URL
        $url = getCurrentUrl();

        // Ако няма път, връщаме
        if (!$url['path']) return;
        
        // Ако има поддиретктория
        if(($slashPos = mb_strrpos($url['path'], '/')) !== FALSE) {
            
            // Преобразуваме пътя на нея
            $url['path'] = mb_substr($url['path'], 0, $slashPos);
        } else {
            
            // Ако няма поддиреткроя, тогава връщаме празен стринг
            $url['path'] = '';
        }
        
        return $url;        
    }
}