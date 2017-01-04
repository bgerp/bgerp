<?php



/**
 * Директория, в която ще се държат екстрактнатите файлове
 */
defIfNot('ARCHIVE_TEMP_PATH', EF_TEMP_PATH . '/archive');


/**
 * Пътя до 7z пакета
 */
defIfNot('ARCHIVE_7Z_PATH', '7z');


/**
 * Максималната големина след разархивиране на един файл
 * 100 mB
 */
defIfNot('ARCHIVE_MAX_FILE_SIZE_AFTER_EXTRACT', 104857600);


/**
 * Адаптер за разглеждане и разархивиране на архиви
 *
 * @category  vendors
 * @package   archive
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class archive_Adapter
{
    
    
    /**
     * Инстанция на класа
     */
    protected $inst;
    
    
    /**
     * Пътя до временния файл на архива
     */
    public $path;
    
    
    /**
     * Временната директория с временните файлове, екстрактнати от архива
     */
    public $dir;
    
    
    /**
     * При създаване на инстанция на класа, инициализираме някои променливи
     *
     * @param array $fArr
     */
    function init($fArr = array())
    {
        // Вкарваме пакета
        require_once getFullPath("archive/7z/7z.php");
        
        if ($fArr['fileHnd']) {
            // Пътя до файла
            $this->path = fileman::extract($fArr['fileHnd']);
        } else {
            $this->path = $fArr['path'];
        }
        
        try {
            // Инстанция на архива
            $this->inst = new Archive_7z($this->path);
        } catch (Archive_7z_Exception $e) {
            throw new core_exception_Expect($e->getMessage());
        }
    }
    
    
    /**
     * Връща в дървовидна структура съдържанието на архива, които сочат в съответното URL
     *
     * @param array $url - Масив с URL
     */
    function tree($url)
    {
        try {
            // Вземаме съдържанието
            $entriesArr = $this->getEntries();
        } catch (ErrorException $e) {
            
            // Връщаме грешката
            return tr('Възникна грешка при показване на съдържанието на архива');
        }
        
        // Инстанция на класа
        $tableInst = cls::get('core_Tree');
        
        // Обхождаме масива
        foreach ((array)$entriesArr as $key => $entry) {
            
            // Пътя в архива
            $path = $entry->getPath();
            
            // Заместваме разделителите за поддиректория с разделителя за дърво
            $path = str_replace(array('/', '\\'), "->", $path);
            
            // Размер на файла след разархивиране
            $size = $entry->getSize();
            
            // Ако има размер
            if ($size && ($size < ARCHIVE_MAX_FILE_SIZE_AFTER_EXTRACT)) {
                
                $urlPath = $url;
                
                // Индекса да е ключа
                $urlPath['index'] = $key;
            } else {
                
                // Ако няма размер
                $urlPath = FALSE;
            }
            
            // Добавяме пътя в дървото със съответното URL
            $tableInst->addNode($path, $urlPath, TRUE);
        }
        
        // Името
        $tableInst->name = 'archive';
        
        // Рендираме изгледа
        $res = $tableInst->renderHtml(NULL);
        
        // Връщаме шаблона
        return $res;
    }
    
    
    /**
     * Връща масив от обекти със информацията за съдържанието на файла
     *
     * @param ingeger $entry - Индекса на файла от архива
     *
     * @return mixed - Ако е подаден индекс, връща обект с информация за съответния файл/папка
     * Ако не е подаден индек, масив с всички файлове/папки, като обекти
     */
    public function getEntries($entry = FALSE)
    {
        try {
            // Вземаме информация за всички файлове/папки
            $entriesArr = $this->inst->getEntries();
        } catch (Archive_7z_Exception $e) {
            throw new core_exception_Expect($e->getMessage());
        }
        
        // Ако е подаден номер на файл
        if ($entry !== FALSE) {
            
            // Връщаме съответния запис
            return $entriesArr[$entry];
        }
        
        // Обхождаме масива
        foreach ($entriesArr as $e) {
            
            // Минаваме пътя през изчисване на името
            $e->path = i18n_Charset::convertToUtf8($e->path);
        }
        
        // Връщаме целия маси
        return $entriesArr;
    }
    
    
    /**
     * Качва в кофа файла в съответния индекс и връща манипулатора на качения файл
     *
     * @param integer $index - Индекса на файла в архива
     */
    public function getFile($index)
    {
        try {
            // Вземаме обекта за съответния файл
            $entry = $this->getEntries($index);
            
            // Размера на файла
            $size = $entry->getSize();
            
            // Очакваме размера след декомпресия да е в допустимите граници
            expect($size < ARCHIVE_MAX_FILE_SIZE_AFTER_EXTRACT);
        } catch (ErrorException $e) {
            // Ако възникне грешка
            expect(FALSE, 'Възникна грешка при свалянето на файла');
        }
        
        // Ако няма размер
        expect($size, 'Не е файл');
        
        try {
            // Вземаме пътя до файла в архива
            $path = $entry->getPath();
        } catch (ErrorException $e) {
            // Ако възникне грешка
            expect(FALSE, 'Не може да се определи пътя до файла.');
        }
        
        // Вземаме манипулатора на файла
        $fh = $this->absorbFile($path);
        
        // Очакваме да има манипулатор
        expect($fh, 'Не може да се вземе файла');
        
        return $fh;
    }
    
    
    /**
     * Изтрива временния файл
     */
    public function deleteTempPath()
    {
        // Изтрива временния файл
        fileman::deleteTempPath($this->path);
        
        if (isset($this->dir) && is_dir($this->dir)) {
            core_Os::deleteDir($this->dir);
        }
    }
    
    
    /**
     * Абсорбираме файла от архива.
     * Качваме подадения файл от архива, в кофата 'archive'
     *
     * @param string $path - Вътрешния път в архива
     *
     * @param string - Манипулатора на файла
     */
    protected function absorbFile($path)
    {
        try {
            // Екстрактваме файла от архива и връщаме пътя във файловата система
            $path = $this->extractEntry($path);
        } catch (ErrorException $e) {
            // Ако възникне грешка
            expect(FALSE, 'Не може да се екстрактен файла от архива');
        }
        
        // Ако е файл
        if (is_file($path)) {
            
            // Абсорбираме файла
            $fh = fileman::absorb($path, 'archive');
        }
        
        // Изтриваме временнада директория със съдържанието му
        core_Os::deleteDir($this->dir);
        
        return $fh;
    }
    
    
    /**
     * Екстрактваме файла от архива и връщаме пътя във файловата система
     *
     * @param string $path - Вътрешния път в архива
     */
    function extractEntry($path)
    {
        // Вземаме директорията
        $this->setOutputDirectory();
        
        try {
            // Екстрактваме файла
            $this->inst->extractEntry($path);
        } catch (Archive_7z_Exception $e) {
            throw new core_exception_Expect($e->getMessage());
        }
        
        // Връщаме пълния път до файла
        return $this->dir . '/' . $path;
    }
    
    
    /**
     * Задаваме временна директроя, където ще се разархивират файловете
     */
    protected function setOutputDirectory()
    {
        // Вземаме директорията за временните файлове
        $dir = ARCHIVE_TEMP_PATH;
        
        // Сканираме директорията
        $dirs = @scandir($dir);
        
        // Опитваме се да генерираме име, което не се среща в директория
        do {
            $newName = str::getRand();
        }while(in_array($newName, (array)$dirs));
        
        // Пътя на директорията
        $this->dir = $dir . '/' . $newName;
        
        // Създаваме директорията
        expect(mkdir($this->dir, 0777, TRUE), 'Не може да се създаде директория.');
        
        try {
            // Инициализираме директорията
            $this->inst->setOutputDirectory($this->dir);
        } catch (Archive_7z_Exception $e) {
            throw new core_exception_Expect($e->getMessage());
        }
    }
}
