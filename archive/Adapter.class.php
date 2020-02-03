<?php


/**
 * Директория, в която ще се държат екстрактнатите файлове
 */
defIfNot('ARCHIVE_TEMP_PATH', EF_TEMP_PATH . '/archive');


/**
 * Пътя до 7z пакета
 */
# defIfNot('ARCHIVE_7Z_PATH',  '7z');


/**
 * Адаптер за разглеждане и разархивиране на архиви
 *
 * @category  vendors
 * @package   archive
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class archive_Adapter
{
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
    public function init($fArr = array())
    {
        // Вкарваме пакета
        require_once getFullPath('archive/7z/7z.php');
        
        if ($fArr['fileHnd']) {
            // Пътя до файла
            $this->path = fileman::extract($fArr['fileHnd']);
        } else {
            $this->path = $fArr['path'];
        }
        
        
        // Инстанция на архива
        $this->inst = new Archive_7z($this->path);
    }
    
    
    /**
     * Връща в дървовидна структура съдържанието на архива, които сочат в съответното URL
     *
     * @param array $url - Масив с URL
     */
    public function tree($url)
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
        foreach ((array) $entriesArr as $key => $entry) {
            
            // Пътя в архива
            $path = $entry->getPath();
            
            // Заместваме разделителите за поддиректория с разделителя за дърво
            $path = str_replace(array('/', '\\'), '->', $path);
            
            // Размер на файла след разархивиране
            $size = $entry->getSize();
            
            // Ако има размер
            if ($size && ($size < archive_Setup::get('MAX_LEN'))) {
                $urlPath = $url;
                
                // Индекса да е ключа
                $urlPath['index'] = $key;
            } else {
                
                // Ако няма размер
                $urlPath = false;
            }
            
            // Добавяме пътя в дървото със съответното URL
            $tableInst->addNode($path, $urlPath, true);
        }
        
        // Името
        $tableInst->name = 'archive';
        
        // Рендираме изгледа
        $res = $tableInst->renderHtml(null);
        
        // Връщаме шаблона
        return $res;
    }
    
    
    /**
     * Връща масив от обекти със информацията за съдържанието на файла
     *Portal/Show/
     *
     * @param int $entry - Индекса на файла от архива
     *
     * @return mixed - Ако е подаден индекс, връща обект с информация за съответния файл/папка
     *               Ако не е подаден индек, масив с всички файлове/папки, като обекти
     */
    public function getEntries($entry = false)
    {
        try {
            // Вземаме информация за всички файлове/папки
            $entriesArr = $this->inst->getEntries();
        } catch (Archive_7z_Exception $e) {
            throw new core_exception_Expect($e->getMessage());
        }
        
        // Ако е подаден номер на файл
        if ($entry !== false) {
            
            // Връщаме съответния запис
            return $entriesArr[$entry];
        }
        
        // Обхождаме масива
        foreach ($entriesArr as $e) {
            
            // Минаваме пътя през изчистване на името
            $e->path = i18n_Charset::convertToUtf8($e->path);
        }
        
        // Връщаме целия маси
        return $entriesArr;
    }
    
    
    /**
     * Качва в кофа файла в съответния индекс и връща манипулатора на качения файл
     *
     * @param int $index - Индекса на файла в архива
     */
    public function getFile($index)
    {
        try {
            // Вземаме обекта за съответния файл
            $entry = $this->getEntries($index);
            
            // Размера на файла
            $size = $entry->getSize();
            
            // Очакваме размера след декомпресия да е в допустимите граници
            expect($size < archive_Setup::get('MAX_LEN'));
        } catch (ErrorException $e) {
            // Ако възникне грешка
            expect(false, 'Възникна грешка при свалянето на файла');
        }
        
        // Ако няма размер
        expect($size, 'Не е файл');
        
        try {
            // Вземаме пътя до файла в архива
            $path = $entry->getPath();
        } catch (ErrorException $e) {
            // Ако възникне грешка
            expect(false, 'Не може да се определи пътя до файла.');
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
     * @param string - Манипулатора на файла
     */
    protected function absorbFile($path)
    {
        try {
            // Екстрактваме файла от архива и връщаме пътя във файловата система
            $path = $this->extractEntry($path);
        } catch (ErrorException $e) {
            // Ако възникне грешка
            expect(false, 'Не може да се екстрактен файла от архива');
        }
        
        // Ако е файл
        if (is_file($path)) {
            
            // Абсорбираме файла
            $fh = fileman::absorb($path, 'archive');
        }
        
        // Изтриваме временната директория със съдържанието й
        core_Os::deleteDir($this->dir);
        
        return $fh;
    }
    
    
    /**
     * Екстрактваме файла от архива и връщаме пътя във файловата система
     *
     * @param string $path - Вътрешния път в архива
     */
    public function extractEntry($path)
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
        } while (in_array($newName, (array) $dirs));
        
        // Пътя на директорията
        $this->dir = $dir . '/' . $newName;
        
        // Създаваме директорията
        expect(core_Os::forceDir($this->dir), 'Не може да се създаде директория.');
        
        try {
            // Инициализираме директорията
            $this->inst->setOutputDirectory($this->dir);
        } catch (Archive_7z_Exception $e) {
            throw new core_exception_Expect($e->getMessage());
        }
    }
    
    
    /**
     * Компресира файл
     */
    public static function compressFile($src, $dest, $pass = null, $options = '')
    {
        expect(file_exists($src), $src);
        expect(is_readable($src), $src);
        
        
        if ($pass) {
            $p = "-p{$pass} -mem=AES256 ";
            escapeshellarg($p);
        } else {
            $p = '';
        }
        
        $srcEsc = escapeshellarg($src);
        $destEsc = escapeshellarg($dest);
        $tempEsc = escapeshellarg("{$dest}.tmp");
        
        $flagDelete = false;
        if (strpos($options, '-sdel') !== false) {
            $flagDelete = true;
            $options = str_replace('-sdel', '', $options);
        }
        
        $cmd = archive_Setup::get_ARCHIVE_7Z_PATH() . " a {$p}-tzip -mx1 -y {$options} {$tempEsc} {$srcEsc}";
        
        exec($cmd, $output, $return);
        
        if ($return != 0) {
            bp($cmd, $output, $return);
        }
        
        rename("{$dest}.tmp", $dest);
        
        if ($flagDelete) {
            unlink($src);
        }
        
        return $return;
    }
    
    
    /**
     * Декомпресира всико от даден архив в посочената директория
     */
    public static function uncompress($src, $dir, $pass = null, $options = '')
    {
        if ($pass) {
            $p = "-p{$pass} -mem=AES256 ";
            escapeshellarg($p);
        } else {
            $p = '';
        }
        
        $src = str_replace('\\', '/', $src);
        $dir = str_replace('\\', '/', $dir);
        
        $src = escapeshellarg($src);
        $dir = escapeshellarg($dir);
        
        
        $cmd = archive_Setup::get_ARCHIVE_7Z_PATH() . " e {$src} -o{$dir} {$p}-tzip -y {$options}";
        
        exec($cmd, $output, $return);
        
        return $return;
    }
}
