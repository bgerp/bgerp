<?php


/**
 * Обща директория на bgerp, vendors, ef. Използва се за едновременно форматиране на трите пакета.
 */
defIfNot('EF_ALL_PATH', EF_ROOT_PATH . '/bgerp');


/**
 * Лиценз на пакета
 */
define('LICENSE', 3);


/**
 * Версията на пакета
 */
define('VERSION', 0.1);

defIfNot('PHP_PATH', core_Os::getPhpCmd());


/**
 * Клас 'refactor_Formater' - Форматер за приложения на EF
 *
 * Форматира кода на файлове, включени във ЕП, приложението, vendors, private и др.
 *
 *
 * @category  vendors
 * @package   php
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class refactor_Formater extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Форматиране за файлове от EF/bgERP/vendors';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools,plg_Sorting,plg_Sorting,plg_Search, refactor_Wrapper';
    
    
    /**
     * полета от БД по които ще се търси
     */
    public $searchFields = 'fileName, name, type, oldComment';
    
    
    /**
     * Масив с всички дефинирани функции
     *
     * @var array
     */
    public $arr;
    
    
    /**
     * Масив с всички използвани функции
     *
     * @var array
     */
    public $arrF;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('fileName', 'varchar', 'caption=Файл');
        $this->FLD('type', 'enum(0=&nbsp;,
                                class=Клас,
                                var=Свойство,
                                function=Функция,
                                const=Константа,
                                static_function=Статична функция,
                                public_function=Публична функция,
                                private_function=Частна функция,
                                protected_function=Защитена функция,
                                public_static_function=Публично статична функция,
                                static_public_function=Статично публична функция,
                                private_static_function=Частна статична функция,
                                static_private_function=Статично частна функция,
                                define=Дефинирана константа,
                                defIfNot=Вътрешна константа)', 'caption=Ресурс->Тип');
        $this->FLD('name', 'varchar', 'caption=Ресурс->Име');
        $this->FLD('value', 'text', 'caption=Ресурс->Стойност');
        $this->FLD('oldComment', 'text', 'caption=Коментар->Стар');
        $this->FLD('newComment', 'text', 'caption=Коментар->Нов');
    }
    
    
    public function act_Process()
    {
        requireRole('admin');
        expect(isDebug());
        
        $scope = (Request::get('scope') == 'all') ? 'all' : 'changed';
        $dry = Request::get('dry') != 'no';
        
        $files = array();
        
        $repos = core_App::getRepos();
        foreach (array_keys($repos) as $r) {
            if ($scope == 'all') {
                foreach ($this->readAllFiles($r) as $f) {
                    $files[] = $f;
                }
            } else {
                foreach (git_Lib::getDiffFiles($r, $log) as $f) {
                    $files[] = $r . '/' . $f;
                }
            }
        }
        
        // Филтрираме само файловете, които ни интересуват
        $includePtr = '/\\.class\\.php/';
        $files = array_filter($files, function ($file) use ($includePtr, $excludePtr) {
            
            return preg_match($includePtr, $file);
        });
        
        return $this->renderWrapping($this->showProcess($files));
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function showProcess($files)
    {
        set_time_limit(count($files) + 30);
        
        // Променливи за метрика на кода
        $linesCnt = $filesCnt = $update = $skip = 0;
        
        $logs = array();
        
        // Масив с всички фрази, които подлежат на превод
        $phrases = array();
        
        foreach ($files as $file) {
            $logs[$file] = array();
            $res = &$logs[$file];
            
            // Ако има грешки във файла, показваме ги и пропускаме по-нататъшните обработки
            if ($err = self::detectErrors($file)) {
                $res[] = $err;
                continue;
            }
            
            // Вземаме времето за последна модификация на файла
            $lastTime = filemtime($file);
            $fileStrOrg = file_get_contents($file);
            
            // Оправяме кодирането на файла
            self::csFixer($file, $dry);
            
            // Зареждаме файла
            $fileStr = file_get_contents($file);
            $lines = explode("\n", $fileStr);
            $linesCnt += count($lines);
            $filesCnt++;
            
            // Парсиране на файла
            $parser = cls::get('refactor_PhpParser');
            $parser->loadText($fileStr);
            
            // Проверка да не би да има думи, които съдържат едновременно кирилица и латиница
            $res = array_merge($res, self::checkCyrLat($lines, $file));
            
            // Поставя, премахва и подравнява празни лиции
            $parser->padEmptyLines();
            
            // Извлича текстовете, които подлежат на превод
            $parser->getTrTexts($phrases);
            
            // Ако има промени - записваме файла
            $source = $parser->getText();
            if ($fileStrOrg != $source) {
                if ($dry) {
                    $res[] = 'Трябва са се префорамтира';
                    $skip++;
                    @touch($file, $lastTime, $lastTime);
                } else {
                    $res[] = 'Беше преформатиран';
                    file_put_contents($file, $source);
                    $update++;
                }
            } else {
                $skip++;
                file_put_contents($file, $source);
                @touch($file, $lastTime, $lastTime);
            }
        }
        
        // Показваме логовите съобщения, свързани с файловете
        foreach ($logs as $file => $res) {
            $fileEdit = core_debug::getEditLink($file);
            $html .= "\n<li>Файл: <strong>{$fileEdit}</strong>";
            if (count($res)) {
                $html .= '</li>';
                $html .= "\n<ul><li>" . implode("</li>\n<li>", $res) . "</li>\n</ul>";
            } else {
                $html .= '- <strong>OK</strong></li>';
            }
        }
        
        // Отделя и показва тези фрази, които не са преведени
        //$html .= self::showUntranslated($phrases);
        if (count($phrases)) {
            $html .= '<li><strong>Фрази, които не са преведени:</strong></li><ul>';
            core_Lg::push('en');
            foreach ($phrases as $p => $cnt) {
                $p1 = mb_strtolower(mb_substr($p, 0, 1)) . mb_substr($p, 1);
                if (tr($p) == $p && tr($p1) == $p1) {
                    $html .= "<li>{$p}</li>";
                }
            }
            core_Lg::pop();
            $html .= '</ul>';
        }
        
        // Показваме статистика за обработените файлове
        $html .= "<li><strong>Общо: {$linesCnt} линии в {$filesCnt} файла, променени ${update} файла, пропуснати ${skip}</strong></li>";
        
        $toolbar = cls::get('core_Toolbar');
        $toolbar->addBtn('Проверка - променени', array($this, 'process', 'scope' => 'changed', 'dry' => 'yes'));
        $toolbar->addBtn('Оправяне - променени', array($this, 'process', 'scope' => 'changed', 'dry' => 'no'));
        $toolbar->addBtn('Проверка - всички', array($this, 'process', 'scope' => 'all', 'dry' => 'yes'));
        $toolbar->addBtn('Оправяне - всички', array($this, 'process', 'scope' => 'all', 'dry' => 'no'), 'warning=Наистина ли искате да преформатирате всички файлове?');
        
        return "<h2>Стандарти за кодиране</h2><ul>{$html}</ul>" . '<div></div><br>' . $toolbar->renderHtml();
    }
    
    
    /**
     * Проверява за наличие на синтактични грешки във файла
     */
    public function detectErrors($file)
    {
        // perform the lint check
        $cmd = PHP_PATH . ' -d display_errors=1 -l ' . escapeshellarg($file);
        
        exec($cmd, $output, $exitCode);
        
        if (preg_match('#^No syntax errors detected in#', $output[0]) !== 1) {
            
            return '<font color=red>' . str_replace($file, '', $output[1]) . '</font>';
        }
    }
    
    
    /**
     * Фиксира стила за кодиране в посочения файл
     *
     * @param string $filePath
     *
     * @see https://cs.sensiolabs.org/
     */
    public static function csFixer($filePath, $dry = false)
    {
        expect(defined('PHP_PATH') && defined('PHP_CS_FIXER_PATH'), PHP_PATH, PHP_CS_FIXER_PATH);
        
        $cmd = PHP_PATH . ' ' . PHP_CS_FIXER_PATH . ' ' . ($dry ? '--dry-run ' : '') .
               '--rules=@PSR2,phpdoc_align,phpdoc_indent,binary_operator_spaces,blank_line_before_return,cast_spaces,align_multiline_comment,array_indentation,' .
               'phpdoc_scalar,phpdoc_separation,combine_consecutive_issets,explicit_string_variable,function_typehint_space,lowercase_static_reference,' .
               'no_blank_lines_after_phpdoc,no_empty_phpdoc,no_empty_statement,no_mixed_echo_print,no_spaces_around_offset,no_useless_else,no_useless_return,' .
               'no_whitespace_before_comma_in_array,simplified_null_return,single_quote,standardize_increment,standardize_not_equals,trim_array_spaces  fix ' .
                '"'. $filePath . '"';
        
        exec($cmd, $output, $exitCode);
        
        $res = false;
        if (strpos($output[0], $filePath) !== true) {
            $res = true;
        }
        
        return $res;
    }
    
    
    /**
     * Проверява дали в оригиналния текст има стоящи една до друга
     * букви на латиница и кирилица
     *
     * @param $lines array
     *
     * @return array грешки
     */
    public static function checkCyrLat($lines, $file)
    {
        $res = array();
        foreach ($lines as $i => $l) {
            if (preg_match('/([a-z][а-я]|[а-я][a-z])/iu', $l, $matches) && !preg_match("/(preg_|pattern|CyrLat|\-zа\-)/iu", $l)) {
                if ($matches[1]{0} == 'n' && strpos($l, '\\' . $matches[1]) !== false) {
                    continue;
                }
                $line = $i + 1;
                $lineEdit = core_debug::getEditLink($file, $line, $line);
                
                $res[] = "<font color=red>Грешка кир/lat на линия {$lineEdit}: " . str_replace($matches[1], '<b style="color:#800">' .$matches[1] . '</b>', trim($l)) . '</font>';
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща масив със всички поддиректории и файлове от посочената начална директория
     *
     * array(
     * 'files' => [],
     * 'dirs'  => [],
     * )
     *
     * @param string $root
     * @result array
     */
    public static function readAllFiles($root = '.', $patern = '', $exclPattern = '')
    {
        $files = array('files' => array(), 'dirs' => array());
        $directories = array();
        $last_letter = $root[strlen($root) - 1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;        //?
        $directories[] = $root;
        
        while (sizeof($directories)) {
            $dir = array_pop($directories);
            
            if ($handle = opendir($dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file == '.' || $file == '..' || $file == '.git') {
                        continue;
                    }
                    
                    $file = str_replace('\\', '/', $dir . $file);
                    
                    if (is_dir($file)) {
                        $directory_path = $file . DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                        $files['dirs'][] = $directory_path;
                    } elseif (is_file($file)) {
                        if ($patern && !preg_match($patern, $file)) {
                            continue;
                        }
                        if ($exclPattern && preg_match($exclPattern, $file)) {
                            continue;
                        }
                        
                        $files['files'][] = $file;
                    }
                }
                closedir($handle);
            }
        }
        
        return $files['files'];
    }
    
    public function act_Test()
    {
        requireRole('debug');
        
        ini_set('memory_limit', '256M');
        $allFiles = refactor_Formater::readAllFiles(EF_ALL_PATH);
        
        // Филтрираме само файловете, които ни интересуват
        $includePtr = '/\\.class\\.php/';
        $files = array_filter($allFiles, function ($file) use ($includePtr, $excludePtr) {
            
            return preg_match($includePtr, $file);
        });
        
        // Подготвя масива с тоукън константите
        $tConstTxt = getFileContent('refactor/data/php_tokens.txt');
        $tConstLines = explode("\n", $tConstTxt);
        $tConstArr = array();
        $t = new stdClass();
        
        foreach ($tConstLines as $tConst) {
            $tConst = trim($tConst);
            
            if (defined($tConst)) {
                $t->tConstArr[constant($tConst)] = $tConst;
            }
        }
        
        $functionArr = array();
        $fArr = array();
        
        foreach ($files as $f) {
            $fileStr = getFileContent($f);
            
            // Зареждаме кода
            $t->code = $fileStr;
            $t->lines = explode("\n", $fileStr);
            
            // Парсираме кода
            $tokens = token_get_all($fileStr);
            expect(is_array($tokens), $tokens);
            
            $class = '';
            for ($i = 0; $i <= count($tokens); $i++) {
                // 361 == T_CLASS && 382 == T_WHITESPACE
                if ($tokens[$i][0] == 361 && $tokens[$i + 1][0] == 382) {
                    $class = $tokens[$i + 2][1];
                }
                
                // 346 == T_FUNCTION
                if ($tokens[$i][0] == 346) {
                    // 382 == T_WHITESPACE
                    if ($tokens[$i + 1][0] == 382 && $tokens[$i + 3][0] == '(') {
                        /* if($tokens[$i+2][0] == 319 && $tokens[$i+2][1] == 'description'){
                             //378 == docComent
                         }*/
                        
                        // 319 == string
                        if ($tokens[$i + 2][0] == 319) {
                            $name = $tokens[$i + 2][1];
                            $after = 'on_After' . strtoupper(substr($name, 0, 1)) . substr($name, 1, -1);
                            $before = 'on_Before' . strtoupper(substr($name, 0, 1)) . substr($name, 1, -1);
                            
                            //if(strrchr($name, "_")) {
                            if (substr($name, -1) == '_') {
                                //$fArr[$class][][$name] = $name;
                                $fArr[$class][$i][$after] = $name;
                                $fArr[$class][$i][$before] = $name;
                                if (!array_key_exists($class, $functionArr)) {
                                    $functionArr[$class] =
                                    (object) array('function' => $name);
                                } else {
                                    $obj = &$functionArr[$class];
                                    $obj->function .= ','.$name;
                                }
                                
                                //$functionArr[$name] = $name;
                            }
                        }
                    }
                }
                
                // 346 == T_FUNCTION
                if ($tokens[$i][0] == 346) {
                    // 382 == T_WHITESPACE
                    if ($tokens[$i + 1][0] == 382 && $tokens[$i + 3][0] == '(') {
                        if ($tokens[$i + 2][0] == 319 && $tokens[$i + 2][1] == 'description') {
                            //378 == docComent
                            while ($tokens[$i][0] != 378) {
                                expect(false, $tokens[$i - 2]);
                            }
                        }
                    }
                }
            }
        }
        
        expect(false, $fArr);
    }
    
    
    /**
     * Показва, кои полета от базата не се използват (няма ги в моделите)
     */
    public function act_Fields()
    {
        requireRole('debug');
        
        $mvcArr = core_Classes::getOptionsByInterface('core_ManagerIntf');
        $extra = array();
        
        foreach ($mvcArr as $classId => $className) {
            $inst = null;
            $inst = cls::get($className);
            
            if (!$inst->dbTableName || !$this->db->tableexists($inst->dbTableName)) {
                continue;
            }
            
            $fields = $this->db->getFields($inst->dbTableName);
            
            $model = array();
            foreach ($inst->fields as $name => $obj) {
                if ($obj->kind != 'FLD') {
                    continue;
                }
                $f = str::phpToMysqlName($name);
                $model[$f] = $f;
            }
            
            foreach ($fields as $name => $object) {
                $f = str::phpToMysqlName($name);
                
                if (!$model[$f]) {
                    $extra[$inst->dbTableName . '::' . $f] = 1;
                }
            }
        }
        
        expect(false, $extra);
    }
}
