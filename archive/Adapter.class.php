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
        // Вземаме съдържанието
        $entriesArr = $this->getEntries();
        
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
            throw new ErrorException($e->getMessage(), $e->getCode());
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
        expect($fh, 'Не може да се вземе файла', $path, $size, $entry);
        
        return $fh;
    }
    
    
    /**
     * Задаване на парола на архива
     *
     * @param string $pass
     */
    public function setPassword($pass)
    {
        $this->inst->setPassword($pass);
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
            throw new ErrorException('Не може да се извлече файла от архива', $e->getCode());
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
            throw new ErrorException($e->getMessage(), $e->getCode());
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
        $tSrc = rtrim($src, '*');
        expect(file_exists($tSrc) || is_dir($tSrc), $src);
        expect(is_readable($tSrc), $src);

        if ($pass) {
            $p = "-p" . escapeshellarg($pass) . " -mem=AES256 ";
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





    //*********************************************************************************************************

    /**
     * Backward compatible wrapper.
     * Supports a limited subset of 7z flags in $options (allowlisted).
     *
     * Examples of supported:
     *   -sdel
     *   -tzip / -t7z
     *   -mx1 / -mx=1 / -mx=9
     *   -ms=off / -ms=on   (7z only)
     *   -mhe=on / -mhe=off (7z only; effective only when pass is set)
     *   -y / -y-           (yes / no)
     *   -bd -bso0 -bsp0    (extras)
     */
    public static function compressFileNew(string $src, string $dest, $pass = null, string $options = ''): int
    {
        $opt = self::parse7zOptions($options);

        // If caller asked ZIP explicitly, keep it
        return self::compressFileEx($src, $dest, $pass, $opt);
    }

    /**
     * Parse legacy $options string into structured options (safe allowlist).
     */
    private static function parse7zOptions(string $options): array
    {
        $opt = [
            'format' => 'zip',     // keep your old default behavior unless overridden
            'level'  => 1,
            'solid'  => null,
            'encryptHeaders' => true,
            'deleteSrc' => false,
            'yes' => true,
            'extra' => [],
        ];

        $tokens = preg_split('/\s+/', trim($options)) ?: [];
        foreach ($tokens as $t) {
            if ($t === '') continue;

            // special flag handled outside 7z
            if ($t === '-sdel') {
                $opt['deleteSrc'] = true;
                continue;
            }

            // format
            if ($t === '-tzip') { $opt['format'] = 'zip'; continue; }
            if ($t === '-t7z')  { $opt['format'] = '7z';  continue; }

            // compression level: -mx1, -mx=1, -mx=9
            if (preg_match('/^-mx(?:=)?([0-9])$/', $t, $m)) {
                $opt['level'] = (int)$m[1];
                continue;
            }

            // solid: only meaningful for 7z
            if ($t === '-ms=on')  { $opt['solid'] = true;  continue; }
            if ($t === '-ms=off') { $opt['solid'] = false; continue; }

            // header encryption (7z only)
            if ($t === '-mhe=on')  { $opt['encryptHeaders'] = true;  continue; }
            if ($t === '-mhe=off') { $opt['encryptHeaders'] = false; continue; }

            // yes/no prompts
            if ($t === '-y')   { $opt['yes'] = true;  continue; }
            if ($t === '-y-')  { $opt['yes'] = false; continue; } // (nonstandard, but handy if you want)
            // NOTE: if you prefer, remove -y- support.

            // allowlisted "extra" args
            if (in_array($t, ['-bd', '-bso0', '-bsp0'], true)) {
                $opt['extra'][] = $t;
                continue;
            }

            // Anything else is rejected (safer than silently passing it to shell)
            throw new RuntimeException("Unsupported/unsafe 7z option: {$t}");
        }

        // normalize
        if ($opt['level'] < 0) $opt['level'] = 0;
        if ($opt['level'] > 9) $opt['level'] = 9;

        return $opt;
    }



    /**
     * Compress a file/dir/glob using 7-Zip.
     *
     * @param string      $src   File/dir/glob (e.g. /path/*.csv)
     * @param string      $dest  Destination archive file
     * @param string|null $pass  Password (optional)
     * @param array       $opt   Options:
     *   - format: '7z'|'zip' (default: '7z')
     *   - level: 0..9 (default: 1)
     *   - solid: bool|null (7z only; null = leave default)
     *   - encryptHeaders: bool (7z only; default: true when pass set)
     *   - deleteSrc: bool (default: false)
     *   - yes: bool (default: true)
     *   - extra: string[] (default: []) allowlisted extra args
     */
    public static function compressFileEx(string $src, string $dest, $pass = null, array $opt = []): int
    {
        $tSrc = rtrim($src, '*');
        if (!(file_exists($tSrc) || is_dir($tSrc))) {
            throw new RuntimeException("Missing source: {$src}");
        }
        if (!is_readable($tSrc)) {
            throw new RuntimeException("Unreadable source: {$src}");
        }

        $format = $opt['format'] ?? '7z';
        if (!in_array($format, ['7z', 'zip'], true)) {
            throw new RuntimeException("Unsupported format: {$format}");
        }

        $level = (int)($opt['level'] ?? 1);
        if ($level < 0 || $level > 9) {
            throw new RuntimeException("Compression level must be 0..9");
        }

        $deleteSrc = (bool)($opt['deleteSrc'] ?? false);
        $yes       = (bool)($opt['yes'] ?? true);

        $destTmp = "{$dest}.tmp";
        $exe = archive_Setup::get_ARCHIVE_7Z_PATH();
 
        $tokens = [];
        $tokens[] = 'a';

        // Password + header encryption
        if ($pass !== null && $pass !== '') {
            // 7z expects -pPASS as a single token. We safely create that token.
            $tokens[] = escapeshellarg('-p' . $pass);

            $encryptHeaders = (bool)($opt['encryptHeaders'] ?? true);
            if ($format === '7z' && $encryptHeaders) {
                $tokens[] = '-mhe=on';
            }
        }

        $tokens[] = '-t' . $format;
        $tokens[] = '-mx=' . $level;

        // Solid only for 7z
        if ($format === '7z' && array_key_exists('solid', $opt) && $opt['solid'] !== null) {
            $tokens[] = $opt['solid'] ? '-ms=on' : '-ms=off';
        }

        if ($yes) {
            $tokens[] = '-y';
        }

        // Allowlisted extra args (keep yours)
        $allowedExtras = ['-bd', '-bso0', '-bsp0'];
        $extra = $opt['extra'] ?? [];
        if (!is_array($extra)) $extra = [];
        foreach ($extra as $x) {
            if (!in_array($x, $allowedExtras, true)) {
                throw new RuntimeException("Disallowed 7z arg: {$x}");
            }
            $tokens[] = $x;
        }

        // Dest and src must be escaped (paths, globs)
        $tokens[] = escapeshellarg($destTmp);
        $tokens[] = escapeshellarg($src);

        $cmd = self::buildCmd($exe, $tokens, true);
 
        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            throw new RuntimeException("7z failed ({$rc}): " . implode("\n", $output));
        }

        if (!rename($destTmp, $dest)) {
            throw new RuntimeException("Cannot rename {$destTmp} -> {$dest}");
        }

        if ($deleteSrc) {
            @unlink($src);
        }

        return $rc;
    }


    public static function uncompressNew(string $src, string $dir, $pass = null, string $options = ''): int
    {
        $opt = self::parse7zUncompressOptions($options);
        return self::uncompressEx($src, $dir, $pass, $opt);
    }


    /**
     * Decompress an archive using 7-Zip.
     *
     * Default: flatten extract (NO directories) to reduce Zip Slip / path traversal risk.
     */
    public static function uncompressEx(string $src, string $dir, $pass = null, array $opt = []): int
    {
        if (!file_exists($src) || !is_readable($src)) {
            throw new RuntimeException("Missing or unreadable archive: {$src}");
        }

        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException("Cannot create destination dir: {$dir}");
        }

        $yes      = (bool)($opt['yes'] ?? true);
        $keepDirs = (bool)($opt['keepDirs'] ?? false); // default flatten (security)

        $exe = archive_Setup::get_ARCHIVE_7Z_PATH();

        $tokens = [];
        $tokens[] = $keepDirs ? 'x' : 'e';

        if ($pass !== null && $pass !== '') {
            $tokens[] = escapeshellarg('-p' . $pass);
        }
        if ($yes) {
            $tokens[] = '-y';
        }

        $allowedExtras = ['-bd', '-bso0', '-bsp0'];
        $extra = $opt['extra'] ?? [];
        if (!is_array($extra)) $extra = [];
        foreach ($extra as $x) {
            if (!in_array($x, $allowedExtras, true)) {
                throw new RuntimeException("Disallowed 7z arg: {$x}");
            }
            $tokens[] = $x;
        }

        $tokens[] = escapeshellarg($src);
        // -oPATH is a single token in 7z; keep it single and escaped safely
        $tokens[] = '-o' . escapeshellarg($dir);

        $cmd = self::buildCmd($exe, $tokens, true);

        exec($cmd, $output, $rc);
        if ($rc !== 0) {
            throw new RuntimeException("7z extract failed ($cmd, {$rc}): " . implode("\n", $output));
        }

        return $rc;
    }



    private static function parse7zUncompressOptions(string $options): array
    {
        $opt = [
            'yes' => true,
            'keepDirs' => false, // IMPORTANT default: flatten
            'extra' => [],
        ];

        $tokens = preg_split('/\s+/', trim($options)) ?: [];
        foreach ($tokens as $t) {
            if ($t === '') continue;

            if ($t === '-y')  { $opt['yes'] = true;  continue; }
            if ($t === '-y-') { $opt['yes'] = false; continue; }

            // Explicitly allow directories
            if ($t === '-dirs' || $t === '-x') { $opt['keepDirs'] = true; continue; }

            // Explicitly force flatten (optional)
            if ($t === '-flat' || $t === '-e') { $opt['keepDirs'] = false; continue; }

            if (in_array($t, ['-bd', '-bso0', '-bsp0'], true)) {
                $opt['extra'][] = $t;
                continue;
            }

            throw new RuntimeException("Unsupported/unsafe 7z uncompress option: {$t}");
        }

        return $opt;
    }


    /**
     * Build a cross-platform command line for exec().
     * Escapes each argument (including the exe path) using escapeshellarg().
     *
     * IMPORTANT:
     * - Do NOT use escapeshellcmd() on Windows; it may produce ^"...
     * - Keep flags as plain tokens; escape only tokens that can contain user data.
     */
    private static function buildCmd(string $exe, array $tokens, bool $redirectStderr = true): string
    {
        $parts = [$exe];
        foreach ($tokens as $t) {
            $parts[] = $t;
        }
        $cmd = implode(' ', $parts);
        if ($redirectStderr) {
            $cmd .= ' 2>&1';
        }
        return $cmd;
    }


}
