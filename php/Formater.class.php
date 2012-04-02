<?php



/**
 * Обща директория на bgerp, vendors, ef. Използва се за едновременно форматиране на трите пакета.
 */
defIfNot('EF_ALL_PATH', EF_ROOT_PATH . '/all');


/**
 * Лиценз на пакета
 */
define(LICENSE, 3);


/**
 * Версията на пакета
 */
define(VERSION, 0.1);

/**
 * @todo Чака за документация...
 */
defIfNot(TITLE, '
/*****************************************************************************
 *                                                                           *
 *      Примерен конфигурационен файл за системата                           *
 *                                                                           *
 *      След като се попълнят стойностите на константите, този файл          *
 *      трябва да бъде записан в [conf] директорията под име:                *
 *      [име на приложението].cfg.php                                        *
 *                                                                           *
 *****************************************************************************/ ');


/**
 * @todo Чака за документация...
 */
defIfNot(DBCONF, '

/*****************************************************************************
 *                                                                           *
 * Параметри за връзка с базата данни                                        *
 *                                                                           *
 *****************************************************************************/ 

// Сървъра за на базата данни
   DEFINE(\'EF_DB_HOST\', \'localhost\');
 
// Кодировка на забата данни
   DEFINE(\'EF_DB_CHARSET\', \'utf8\');

/*****************************************************************************
 *                                                                           *
 * Пътища до някои важни части от системата                                  *
 *                                                                           *
 *****************************************************************************/ 

// Път по подразбиране за пакетите от \'vendors\'
 # DEFINE(\'EF_VENDORS_PATH\', EF_ROOT_PATH . \'/vendors\');

// Път по подразбиране за пакетите от \'private\'
 # DEFINE(\'EF_PRIVATE_PATH\', EF_ROOT_PATH . \'/private\');

// Базова директория, където се намират по-директориите за
// временните файлове. По подразбиране е в
// EF_ROOT_PATH/temp
 # DEFINE( \'EF_TEMP_BASE_PATH\', \'PATH_TO_FOLDER\');

// Базова директория, където се намират по-директориите за
// потребителски файлове. По подразбиране е в
// EF_ROOT_PATH/uploads
 # DEFINE( \'EF_UPLOADS_BASE_PATH\', \'PATH_TO_FOLDER\');

// Твърдо, фиксирано име на мениджъра с контролерните функции. 
// Ако се укаже, цялотоможе да има само един такъв 
// мениджър функции. Това е удобство за специфични приложения, 
// при които не е добре името на мениджъра да се вижда в URL-то
 # DEFINE(\'EF_CTR_NAME\', \'FIXED_CONTROLER\');

// Твърдо, фиксирано име на екшън (контролерна функция). 
// Ако се укаже, от URL-то се изпускат екшъните.
 # DEFINE(\'EF_ACT_NAME\', \'FIXED_CONTROLER\');

// Базова директория, където се намират приложенията
 # DEFINE(\'EF_APP_BASE_PATH\', \'PATH_TO_FOLDER\');

// Директорията с конфигурационните файлове
 # DEFINE(\'EF_CONF_PATH\', EF_ROOT_PATH . \'/conf\');
');

/**
 * @todo Чака за документация...
 */
defIfNot(MANDATORY, '
// Името на приложението. Използва се за определяне на други константи
   DEFINE(\'EF_APP_NAME\', );

// Лого на фирмата
   DEFINE(\'BGERP_COMPANY_LOGO\', );

// Име на базата данни. По подразбиране е същото, като името на приложението
   DEFINE(\'EF_DB_NAME\', );

// Потребителско име. По подразбиране е същото, като името на приложението
   DEFINE(\'EF_DB_USER\', );

// По-долу трябва да се постави реалната парола за връзка
// с базата данни на потребителят дефиниран в предходния ред
   DEFINE(\'EF_DB_PASS\', ); 
   
// Секретен ключ използван за кодиране в рамките на системата
// Той трябва да е различен, за различните инсталации на системата
// Моля сменето стойността, ако правите нова инсталация.
// След като веднъж е установен, този параметър не трябва да се променя
   DEFINE(\'EF_SALT\', );

// Имейла по подразбиране
   DEFINE(\'BGERP_DEFAULT_EMAIL_FROM\', );

// Домейн  по подразбиране
   DEFINE(\'BGERP_DEFAULT_EMAIL_DOMAIN\', );

// Пощенска кутия по подразбиране
   DEFINE(\'BGERP_DEFAULT_EMAIL_USER\', );

// Хост по подразбиране
   DEFINE(\'BGERP_DEFAULT_EMAIL_HOST\', );

// Парола по подразбиране
   DEFINE(\'BGERP_DEFAULT_EMAIL_PASSWORD\', );
   ');


/**
 * @todo Чака за документация...
 */
defIfNot(CAPTIONEF, '
/*****************************************************************************
 *                                                                           *
 * Конфигурация на EF                                                        *
 *                                                                           *
 *****************************************************************************/ ');


/**
 * @todo Чака за документация...
 */
defIfNot(CAPTIONBGERP, '
/*****************************************************************************
 *                                                                           *
 * Конфигурация на BGERP                                                     *
 *                                                                           *
 *****************************************************************************/ ');


/**
 * @todo Чака за документация...
 */
defIfNot(CAPTIONVENDORS, '
/*****************************************************************************
 *                                                                           *
 * Конфигурация на VENDORS                                                   *
 *                                                                           *
 *****************************************************************************/ ');

/**
 * @todo Чака за документация...
 */
defIfNot(CAPTION, '
/*****************************************************************************
 *                                                                           *
 * Конфигурация на всички пакети                                             *
 *                                                                           *
 *****************************************************************************/ ');

/**
 * @todo Чака за документация...
 */
defIfNot(CAPTIONNULL, '
/*****************************************************************************
 *                                                                           *
 * Задължително въведете стойности на следните константи                     *
 *                                                                           *
 *****************************************************************************/ ');


/**
 * Клас 'php_Formater' - Форматер за приложения на EF
 *
 * Форматира кода на файлове, включени във ЕП, приложението, vendors, private и др.
 *
 *
 * @category  vendors
 * @package   php
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class php_Formater extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Форматиране за файлове от EF/bgERP/vendors";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools,plg_Sorting,plg_Sorting,plg_Search';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'fileName, name, type, oldComment';
    
    
    /**
     * Описание на модела
     */
    function description()
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
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Process()
    {
        requireRole('admin');
        expect(isDebug());
        
        $form = cls::get('core_Form');
        
        if(defined('EF_PRIVATE_PATH')) {
            $form->FNC('src', 'enum(' . EF_APP_PATH . ',' . EF_EF_PATH . ',' . EF_VENDORS_PATH . ',' . EF_PRIVATE_PATH . ')', 'caption=Директории->Източник,input,mandatory');
        } else {
            $form->FNC('src', 'enum(' . EF_APP_PATH . ',' . EF_EF_PATH . ',' . EF_VENDORS_PATH . ', ' . EF_ALL_PATH . ')', 'caption=Директории->Оригинален код,input');
        }
        
        $form->FNC('dst', 'varchar', 'caption=Директории->За форматирания код,recently,input,mandatory,width=100%');
        
        $form->title = "Посочете пътищата за оригиналния и форматирания код";
        
        $form->toolbar->addSbBtn("Форматирай");
        
        $form->input();
        
        if($form->isSubmitted()) {
            
            $src = $form->rec->src . '/';
            $dst = rtrim($form->rec->dst, '/') . '/';
            
            if(!is_dir($dst)) {
                $form->setWarning('dst', "Директорията <b>{$dst}</b> не съществува. Да бъде ли създадена?");
            }
            
            if(!$form->gotErrors()) {
                
                $files = (object) $this->readAllFiles($src);
                
                set_time_limit(540);
                
                // Създаване на файл
                /*$con = file_get_contents('/var/www/ef_root/dictionary-new.php');

                $handle = fopen("/var/www/ef_root/dictionary-new.php", "w+");
                fwrite($handle, $con);*/
                
                //Генериране на масиви за заместването
                $string = trim(file_get_contents("/var/www/ef_root/dictionary.txt", "r"));
                $lines = explode("\n", $string);
                set_time_limit(500);
                
                $d = '[^а-яА-Яa-zA-Z0-9]';
                
                foreach($lines as $l){
                    if (!mb_strlen(trim($l))) bp($l);
                    list($from1, $to1) = explode('->', $l, 2);
                    
                    $from = trim($from1);
                    $from = mb_strtoupper(mb_substr($from, 0, 1)) . mb_substr($from, 1);
                    
                    $to = trim($to1);
                    $to = mb_strtoupper(mb_substr($to, 0, 1)) . mb_substr($to, 1);
                    
                    $massFrom[] = "/({$d})" . $from . "({$d})/u";
                    $massTo[] =  $to;
                    
                    //Включване в речника на думи с малка буква 
                    $r = str::getRand();
                    $massRandomTo[] = '\1' . $r . '\2';
                    $massRandomFrom[] =   $r;
                    
                    $from = trim($from1);
                    $from = mb_strtolower(mb_substr($from, 0, 1)) . mb_substr($from, 1);
                    
                    $to = trim($to1);
                    $to = mb_strtolower(mb_substr($to, 0, 1)) . mb_substr($to, 1);
                    
                    $massFrom[] = "/({$d})" . $from . "({$d})/u";
                    $massTo[] =  $to;
                    
                    $q = str::getRand();
                    $massRandomTo[] = '\1' . $q . '\2';
                    $massRandomFrom[] =   $q;
                }
                
                foreach($files->files as $f) {
                    
                    //  if(stripos($f, 'core/Array') === FALSE) continue;
                    
                    $destination = str_replace("\\", "/", $dst . $f);
                    $dsPos = strrpos($destination, "/");
                    $dir = substr($destination, 0, $dsPos);
                    
                    if(!is_dir($dir)) mkdir($dir, 0777, TRUE);
                    
                    // Ако класа е със суфикс от приетите от фреймуърка, той се обработва ("разхубавява")
                    if(strpos($f, '.class.php') || strpos($f, '.inc.php')) {
                        // if(strpos($f, '.inc.php')) {
                        
                        $str = file_get_contents($src . $f);
                        
                        $str = preg_replace($massFrom, $massRandomTo, $str);
                        $str = str_replace($massRandomFrom, $massTo, $str);
                        
                        // Премахване на всички силволи, букви и цифри различни от кирилицата
                        /* $pattern = '/[^а-яА-Я]+/u';
                        
                        $new = preg_replace($pattern, " ", $str);
                        $words = explode(' ', trim($new));
                        
                        foreach($words as $w){
                            $string = $w."\n";
                            fwrite($handle, $string);
                        }*/
                        //Записваме файловете с поправените грешки и ги подаваме за "разхубавяване"
                        
                        /* if(strpos($src, '/bgerp/')){
                            $hand = "/var/www/ef_root/bgerp";
                            $hand2 = "/var/www/ef_root/bgerp/$f";
                            $dir2 = str_replace("/var/www/ef_root/fbgerp", $hand, $dir);
                            if(!is_dir($dir2)) 
                               mkdir($dir2, 0777, TRUE);
                            
                        }elseif(strpos($src, '/ef/')){
                            $hand = "/var/www/ef_root/ef";
                            $hand2 = "/var/www/ef_root/ef/$f";
                            $dir3 = str_replace("/var/www/ef_root/fef", $hand, $dir);
                            if(!is_dir($dir3)) 
                               mkdir($dir3, 0777, TRUE);
                            
                        }elseif(strpos($src, '/vendors/')){
                            $hand = "/var/www/ef_root/vendors";
                            $hand2 = "/var/www/ef_root/vendors/$f";
                            $dir4 = str_replace("/var/www/ef_root/fvendors", $hand, $dir);
                            if(!is_dir($dir4)) 
                               mkdir($dir4, 0777, TRUE);
                        }*/
                        
                        //Записваме файловете с поправените грешки и ги подаваме за "разхубавяване"
                        file_put_contents($destination, $str);
                        
                        $lines = count(explode("\n", $str));
                        $symbol = mb_strlen(trim($str));
                        
                        // Колко линии код има в пакета заедно с празните редове?
                        $this->lines += $lines;
                        
                        // Колко символа има в пакета заедно с празните редове?
                        $this->symbol += $symbol;
                        
                        $commLines = explode("\n", $str);
                        $dComm = 0;
                        
                        foreach ($commLines as $comm){
                            if(strpos($comm, "/**") || strpos($comm, "*/") || strpos($comm, "*") || strpos($comm, "//")) {
                                $dComm ++;
                                $docComm = $lines - $dComm;
                            }
                        }
                        $this->docComm += $docComm;
                        
                        // Колко линии коментари има в пакета заедно с празните редове?
                        $this->dComm += $dComm;
                        
                        $beautifier = cls::get('php_BeautifierM');
                        
                        $res .= $beautifier->file($src . $f, $destination);
                        
                        if (is_array($beautifier->arr)) {
                            foreach ($beautifier->arr as $key => $value) {
                                $arr[$key] = $arr[$key] + $value;
                            }
                        }
                        
                        if (is_array($beautifier->arrF)) {
                            foreach ($beautifier->arrF as $key => $value) {
                                $arrF[$key] = $arrF[$key] + $value;
                            }
                        }
                    } else {
                        copy($src . $f, $destination);
                    }
                }
                
                // fclose($handle);
                
                foreach ($arr as $key => $value){
                    
                    if(($value && !$arrF[$key])){
                        
                        $onlyDef[$key] = $key;
                    }
                }
                
                //  bp($onlyDef,$arr,$arrF);
                // die;
                return new Redirect(array($this), "Обработени $this->lines линии код<br>
                                                   Има $this->dComm линии коментар<br>
                                                   $this->docComm линии код без коментари<br>
                                                   $this->symbol символа");
            }
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Създаване на нови docComment коментари на всички класове
     */
    function act_Class()
    {
        
        $year = date(Y);
        
        //Заявка към базата данни
        $query = $this->getQuery();
        
        while ($rec = $query->fetch("#type = 'class'")) {
            
            $id = $rec->id;
            $type = $rec->type;
            $file = $rec->fileName;
            $name = $rec->name;
            
            //Разделяне на коментара на редове     
            $lines = explode("\n", $rec->newComment);
            $commArr = array();
            
            foreach($lines as $l) {
                $l = trim($l);
                
                if($l{0} == '@') {
                    list($key, $value) = explode(' ', $l, 2);
                    $commArr[$key] = $value;
                } else {
                    //Кратък коментар
                    $shortComment = $lines[0];
                    
                    if(($lines[1] != "") && (strpos($l, '@', 0))){
                        $shortComment .= "" . $lines[1];
                        
                        //$shortComment = trim($shortComment);
                    
                    }
                    
                    if (($l !== "") && ($l{0} !== '@') && ($l !== trim($shortComment))){
                        //Обширен коментар
                        $extensiveComment .= $l . "\n";
                        
                        //$extensiveComment = trim($extensiveComment);
                    } elseif ($l == trim($shortComment)){
                        $extensiveComment = "";
                    }
                }
            }
            
            //Взимаме името на автора
            $author = trim($commArr['@author']);
            
            //Проверяваме коя папка искаме да форматираме - bgerp, ef, vendors, all(всички папки)
            
            $str =  "";
            $str1 = "/var/www/ef_root/";
            $category = strtok(substr_replace($rec->fileName, $str, 0, strlen($str1)), "/");       //$category
            $package = strtok(substr_replace(strstr(substr_replace($rec->fileName, $str, 0, strlen($str1)), "/"), $str, 0, 1), "/");       //$package
            unset($commArr['@category']);
            unset($commArr['@package']);
            unset($commArr['@author']);
            unset($commArr['@copyright']);
            unset($commArr['@license']);
            unset($commArr['@since']);
            unset($commArr['@version']);
            unset($commArr['@subpackage']);
            
            // Правим ново форматиране на всеки клас
            
            $classComment = $shortComment . "\n" . "\n";
            
            if($extensiveComment != ""){
                $classComment .= $extensiveComment . "\n" . "\n" ;
            } else $classComment .= "\n";
            $classComment .= '@category  ' . $category . "\n";
            $classComment .= '@package   ' . $package . "\n";
            $classComment .= '@author    ' . $author . "\n";
            $classComment .= '@copyright 2006 - ' . $year .  ' Experta OOD' . "\n";
            $classComment .= '@license   GPL ' . LICENSE . "\n";
            $classComment .= '@since     v ' . VERSION . "\n";
            
            foreach ($commArr as $key=>$new){
                $lenght = strlen($key);
                
                if ($lenght == 4){
                    $classComment .= $key . "       " . trim($new) . "\n" ;
                } elseif($lenght == 5) {
                    $classComment .= $key . "      " . trim($new) . "\n" ;
                }else{
                    $classComment .= $key . "     " . trim($new) . "\n" ;
                }
            }
            
            $rec->id = $id;
            $rec->fileName = $file;
            $rec->type = $type;
            $rec->name = $name;
            $rec->newComment = $classComment;
            
            php_Formater::save($rec);
        }
        
        return new Redirect(array($this, '?id=&Cmd[default]=1&search=&search=&type=class&Cmd[default]=Филтрирай'));
    }
    
    
    /**
     * Генериране на bgerp.template.cfg файл с всичкидефинирани с defIfNot
     */
    function act_Const()
    {
        
        $query = $this->getQuery();
        
        //Посочваме, кой файл ще отворим за четене и запис
        $handle = fopen("/var/www/ef_root/fbgerp/_docs/conf/bgerp.template.cfg.php", "w");
        
        $queryClass = $this->getQuery();
        $query->orderBy('#fileName', 'ASC');
        
        //Правим заявка да селектираме всички записи от поле "type" имащи стойност "defIfNot"
        while ($rec = $query->fetch("#type = 'defIfNot'")) {
            
            //Масив от имената на всички файлове, съдържащи константи дефинирани с "defIfNot"
            $fileConst[] = $rec->fileName;
            
            //Обработваме името на файла(целия път до файла)
            $str =  "";
            $str1 = "/var/www/ef_root/";
            $captions = strtok(substr_replace($rec->fileName, $str, 0, strlen($str1)), "/");
            $captions .= "/" . strtok(substr_replace(strstr(substr_replace($rec->fileName, $str, 0, strlen($str1)), "/"), $str, 0, 1), "/");
            $captions .= "/" . strtok(substr(str_replace($str1, "", str_replace($captions, "", $rec->fileName)), 1), ".");
            
            // Двумерен масив с първи ключ част от името на файла, втори - константите в този файл
            // дефинирани с defIfNot и стойност коментара на константата
            if(strpos($rec->fileName, '/ef/') !== FALSE){
                $const[$captions][$rec->newComment][$rec->name] = $rec->value;
                
                //if($rec->value == NULL){
                //    $null[$captions][$rec->newComment][$rec->name] = $rec->value;
                //    unset($const[$captions][$rec->newComment][$rec->name]);
                
                //}
            
            }elseif(strpos($rec->fileName, '/bgerp/') !== FALSE){
                $constBgerp[$captions][$rec->newComment][$rec->name] = $rec->value;
                
                // if($rec->value == NULL){
                //    $nullBgerp[$captions][$rec->newComment][$rec->name] = $rec->value;
                //    unset($constBgerp[$captions][$rec->newComment][$rec->name]);
                
                // }
            
            }elseif(strpos($rec->fileName, '/vendors/') !== FALSE){
                $constVendors[$captions][$rec->newComment][$rec->name] = $rec->value;
                
                // if($rec->value == NULL){
                //    $nullVendors[$captions][$rec->newComment][$rec->name] = $rec->value;
                //    unset($constVendors[$captions][$rec->newComment][$rec->name]);
                
                // }
            }elseif(strpos($rec->fileName, '/all/') !== FALSE){
                $constAll[$captions][$rec->newComment][$rec->name] = $rec->value;
                
                // if($rec->value == NULL){
                //    $nullAll[$captions][$rec->newComment][$rec->name] = $rec->value;
                //    unset($constAll[$captions][$rec->newComment][$rec->name]);
                
                //}
            }
        }
        
        //Правим заявка да селектираме всички записи от поле "type" имащи стойност "class"   
        while ($rec = $queryClass->fetch("#type = 'class'")) {
            
            //Масив от имената на всички файлове, съдържащи описание на клас
            $fileClass[] = $rec->fileName;
            
            //Разделяне на коментара на редове     
            $lines[$rec->fileName] = explode("\n", $rec->newComment);
            
            foreach($fileConst as $fConst){
                $constFile = $fConst;
                
                foreach($fileClass as $fClass){
                    $classFile = $fClass;
                    
                    if($constFile == $classFile){
                        
                        //Вземаме краткия коментар от описанието на
                        $shortComment[$fConst] = $lines[$classFile][0];
                        
                        if($lines[$classFile][1] != " "){
                            $shortComment[$fConst] .= $lines[$classFile][1];
                        }
                    }
                }
            }
        }
        
        //Заглавието на файла
        $title = TITLE . "\n" . "\n" . "\n";
        fwrite($handle, $title);
        
        //Каре с надпис "Задължително..."
        $captionNull = CAPTIONNULL . "\n";
        fwrite($handle, $captionNull);
        
        $mandatory = MANDATORY . "\n" ;
        fwrite($handle, $mandatory);
        
        //Записваме всички константи, които имат $value == NULL        
        /* if(count($null) > 0){
        foreach($null as $key1=>$value1){
    
            if ($key1)
            foreach($value1 as $k1=>$v1){
                
                $comments = str_replace("\n", "\n" . '// ', trim($k1));
                $comment = '// ' . $comments . "\n";
               
                foreach($v1 as $kl1=>$vl1){
              
                $ek = count($k1);
                $names = strtoupper(trim(str_replace("'", "", $kl1)));
                $name = strtoupper(trim(str_replace("\"", "", $names)));
                $values = $vl1;
                
                $string2 = $comment;
                
                $string2 .= ' # DEFINE(\'' . $name . '\', ' . $values . ');' . "\n" . "\n" . "\n";
          
                fwrite($handle, $string2);
                }
            }
        }
        }
     
        if(count($nullBgerp) > 0){
        foreach($nullBgerp as $key1=>$value1){
    
          //  if ($key1)
            foreach($value1 as $k1=>$v1){
                
                $comments = str_replace("\n", "\n" . '// ', trim($k1));
                $comment = '// ' . $comments . "\n";
               
                foreach($v1 as $kl1=>$vl1) {
              
                $ek = count($k1);
                $names = strtoupper(trim(str_replace("'", "", $kl1)));
                $name = strtoupper(trim(str_replace("\"", "", $names)));
                $values = $vl1;
                
                $string2 = $comment;
                
                $string2 .= ' # DEFINE(\'' . $name . '\', ' . $values . ');' . "\n" . "\n" . "\n";
          
                fwrite($handle, $string2);
                }
            }
        }
        }
        
        if(count($nullVendors) > 0){
        foreach($nullVendors as $key1=>$value1){
    
            if ($key1)
            foreach($value1 as $k1=>$v1){
                
                $comments = str_replace("\n", "\n" . '// ', trim($k1));
                $comment = '// ' . $comments . "\n";
               
                foreach($v1 as $kl1=>$vl1){
              
                $ek = count($k1);
                $names = strtoupper(trim(str_replace("'", "", $kl1)));
                $name = strtoupper(trim(str_replace("\"", "", $names)));
                $values = $vl1;
                
                $string2 = $comment;
                
                $string2 .= ' # DEFINE(\'' . $name . '\', ' . $values . ');' . "\n" . "\n" . "\n";
          
                fwrite($handle, $string2);
                }
            }
        }
        }*/
        
        //Забити константи за базата данни
        $conf = DBCONF . "\n" . "\n" . "\n";
        fwrite($handle, $conf);
        
        //Заглавие за пакета ЕФ
        $captionEf = CAPTIONEF . "\n" . "\n" . "\n";
        fwrite($handle, $captionEf);
        
        //Оформяме новия файл
        
        foreach($const as $key=>$value){
            
            $n = 0;
            $m = 0;
            $k = 0;
            $y = '/var/www/ef_root/' . $key . '.class.php';
            
            if ($key)
            $n = mb_strlen(trim($shortComment[$y]));
            
            $string = '/*****************************************************************************' . "\n";
            $caption = $key;
            $number = mb_strlen($string);
            $numCaption = mb_strlen($caption);
            
            $a = str_repeat(" ", abs($number - $numCaption) - 5);
            $b = @str_repeat(" ", abs($number - $n) - 5);
            $string .= ' *                                                                           *' . "\n";
            
            if($n >= $number){
                $com = explode("-", trim($shortComment[$y]));
                $m = mb_strlen(trim($com[0]));
                $k = mb_strlen(trim($com[1]));
                $c = str_repeat(" ", abs($number - $m) - 5);
                $d = str_repeat(" ", abs($number - $k) - 5);
                $string .= ' * ' . trim($com[0]) . $c . '*' . "\n";
                
                if($com[1] != "" && $k <= $number) {
                    $string .= ' * ' . trim($com[1]) . $d . '*' . "\n";
                } else {
                    $com1 = explode(",", trim($com[1]));
                    $m1 = mb_strlen(trim($com1[0]));
                    $k1 = mb_strlen(trim($com1[1]));
                    $c1 = str_repeat(" ", abs($number - $m1) - 5);
                    $d1 = str_repeat(" ", abs($number - $k1) - 5);
                    $string .= ' * ' . trim($com1[0]) . $c1 . '*' . "\n";
                    $string .= ' * ' . trim($com1[1]) . $d1 . '*' . "\n";
                }
            } else
            $string .= ' * ' . trim($shortComment[$y]) . $b . '*' . "\n";
            
            $string .= ' *                                                                           *' . "\n";
            $string .= ' * ' . $caption . $a . '*' . "\n";
            $string .= ' *                                                                           *' . "\n";
            $string .= ' *****************************************************************************/' . "\n";
            $string .= "\n";
            
            fwrite($handle, $string);
            
            foreach($value as $k=>$v){
                $comments = str_replace("\n", "\n" . '// ', trim($k));
                $comment = '// ' . $comments . "\n";
                
                foreach($v as $kl=>$vl){
                    
                    $ek = count($k);
                    $names = strtoupper(trim(str_replace("'", "", $kl)));
                    $name = strtoupper(trim(str_replace("\"", "", $names)));
                    $values = $vl;
                    
                    $string1 = $comment;
                    
                    $string1 .= ' # DEFINE(\'' . $name . '\', ' . $values . ');' . "\n" . "\n" . "\n";
                    fwrite($handle, $string1);
                }
            }
        }
        
        //Заглавие за пакета БГерп
        $captionBgerp = CAPTIONBGERP . "\n" . "\n" . "\n";
        fwrite($handle, $captionBgerp);
        
        foreach($constBgerp as $key=>$value){
            
            $n = 0;
            $m = 0;
            $k = 0;
            $y = '/var/www/ef_root/' . $key . '.class.php';
            
            if ($key)
            $n = mb_strlen(trim($shortComment[$y]));
            
            $string = '/*****************************************************************************' . "\n";
            $caption = $key;
            $number = mb_strlen($string);
            $numCaption = mb_strlen($caption);
            
            $a = str_repeat(" ", abs($number - $numCaption) - 5);
            $b = @str_repeat(" ", abs($number - $n) - 5);
            $string .= ' *                                                                           *' . "\n";
            
            if($n >= $number){
                $com = explode("-", trim($shortComment[$y]));
                $m = mb_strlen(trim($com[0]));
                $k = mb_strlen(trim($com[1]));
                $c = str_repeat(" ", abs($number - $m) - 5);
                $d = str_repeat(" ", abs($number - $k) - 5);
                $string .= ' * ' . trim($com[0]) . $c . '*' . "\n";
                
                if($com[1] != "" && $k <= $number) {
                    $string .= ' * ' . trim($com[1]) . $d . '*' . "\n";
                } else {
                    $com1 = explode(",", trim($com[1]));
                    $m1 = mb_strlen(trim($com1[0]));
                    $k1 = mb_strlen(trim($com1[1]));
                    $c1 = str_repeat(" ", abs($number - $m1) - 5);
                    $d1 = str_repeat(" ", abs($number - $k1) - 5);
                    $string .= ' * ' . trim($com1[0]) . $c1 . '*' . "\n";
                    $string .= ' * ' . trim($com1[1]) . $d1 . '*' . "\n";
                }
            } else
            $string .= ' * ' . trim($shortComment[$y]) . $b . '*' . "\n";
            
            $string .= ' *                                                                           *' . "\n";
            $string .= ' * ' . $caption . $a . '*' . "\n";
            $string .= ' *                                                                           *' . "\n";
            $string .= ' *****************************************************************************/' . "\n";
            $string .= "\n";
            fwrite($handle, $string);
            
            foreach($value as $k=>$v){
                $comments = str_replace("\n", "\n" . '// ', trim($k));
                $comment = '// ' . $comments . "\n";
                
                foreach($v as $kl=>$vl){
                    
                    $ek = count($k);
                    $names = strtoupper(trim(str_replace("'", "", $kl)));
                    $name = strtoupper(trim(str_replace("\"", "", $names)));
                    $values = $vl;
                    
                    $string1 = $comment;
                    
                    if($value == " "){
                        $string1 .= ' # DEFINE(\'' . $name . ')' . ', );' . "\n" . "\n" . "\n";
                    }else
                    $string1 .= ' # DEFINE(\'' . $name . '\', ' . $values . ');' . "\n" . "\n" . "\n";
                    fwrite($handle, $string1);
                }
            }
        }
        
        //Заглавие за пакета Вендорс
        $captionVendors = CAPTIONVENDORS . "\n" . "\n" . "\n";
        fwrite($handle, $captionVendors);
        
        foreach($constVendors as $key=>$value){
            $n = 0;
            $m = 0;
            $k = 0;
            $y = '/var/www/ef_root/' . $key . '.class.php';
            
            if ($key)
            $n = mb_strlen(trim($shortComment[$y]));
            
            $string = '/*****************************************************************************' . "\n";
            $caption = $key;
            $number = mb_strlen($string);
            $numCaption = mb_strlen($caption);
            
            $a = str_repeat(" ", abs($number - $numCaption) - 5);
            $b = @str_repeat(" ", abs($number - $n) - 5);
            $string .= ' *                                                                           *' . "\n";
            
            if($n >= $number){
                $com = explode("-", trim($shortComment[$y]));
                $m = mb_strlen(trim($com[0]));
                $k = mb_strlen(trim($com[1]));
                $c = str_repeat(" ", abs($number - $m) - 5);
                $d = str_repeat(" ", abs($number - $k) - 5);
                $string .= ' * ' . trim($com[0]) . $c . '*' . "\n";
                
                if($com[1] != "" && $k <= $number) {
                    $string .= ' * ' . trim($com[1]) . $d . '*' . "\n";
                } else {
                    $com1 = explode(",", trim($com[1]));
                    $m1 = mb_strlen(trim($com1[0]));
                    $k1 = mb_strlen(trim($com1[1]));
                    $c1 = str_repeat(" ", abs($number - $m1) - 5);
                    $d1 = str_repeat(" ", abs($number - $k1) - 5);
                    $string .= ' * ' . trim($com1[0]) . $c1 . '*' . "\n";
                    $string .= ' * ' . trim($com1[1]) . $d1 . '*' . "\n";
                }
            } else
            $string .= ' * ' . trim($shortComment[$y]) . $b . '*' . "\n";
            
            $string .= ' *                                                                           *' . "\n";
            $string .= ' * ' . $caption . $a . '*' . "\n";
            $string .= ' *                                                                           *' . "\n";
            $string .= ' *****************************************************************************/' . "\n";
            $string .= "\n";
            fwrite($handle, $string);
            
            foreach($value as $k=>$v){
                $comments = str_replace("\n", "\n" . '// ', trim($k));
                $comment = '// ' . $comments . "\n";
                
                foreach($v as $kl=>$vl){
                    
                    $ek = count($k);
                    $names = strtoupper(trim(str_replace("'", "", $kl)));
                    $name = strtoupper(trim(str_replace("\"", "", $names)));
                    $values = $vl;
                    
                    $string1 = $comment;
                    
                    $string1 .= ' # DEFINE(\'' . $name . '\', ' . $values . ');' . "\n" . "\n" . "\n";
                    fwrite($handle, $string1);
                }
            }
        }
        
        fclose($handle);
        
        return new Redirect(array($this), "Успешно конфигурирахте новия <i>bgerp.template.cfg.php</i> файл ");
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Форматиране...', array($mvc, 'Process'));
        $data->toolbar->addBtn('Тест', array('php_Test', 'Tester'));
        $data->toolbar->addBtn('Класове', array($mvc, 'Class'));
        $data->toolbar->addBtn('Константи', array($mvc, 'Const'));
    }
    
    
    /**
     * Форма за търсене по дадена ключова дума
     */
    static function on_AfterPrepareListFilter($mvs, &$res, $data)
    {
        $data->listFilter->showFields = 'search, type';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->input('search, type', 'silent');
        
        if($type = $data->listFilter->rec->type){
            $data->query->where("#type = '{$type}'");
        }
    }
    
    
    /**
     * Връща масив със всички поддиректории и файлове от посочената начална директория
     *
     * array(
     * 'files' => [],
     * 'dirs'  => [],
     * )
     * @param string $root
     * @result array
     */
    function readAllFiles($root = '.')
    {
        $files = array('files'=>array(), 'dirs'=>array());
        $directories = array();
        $last_letter = $root[strlen($root)-1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;       //?
        $directories[] = $root;
        
        while (sizeof($directories)) {
            
            $dir = array_pop($directories);
            
            if ($handle = opendir($dir)) {
                while (FALSE !== ($file = readdir($handle))) {
                    if ($file == '.' || $file == '..' || $file == '.git') {
                        continue;
                    }
                    $file = $dir . $file;
                    
                    if (is_dir($file)) {
                        $directory_path = $file . DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                        $files['dirs'][] = $directory_path;
                    } elseif (is_file($file)) {
                        $files['files'][] = str_replace($root, "", $file);
                    }
                }
                closedir($handle);
            }
        }
        
        return $files;
    }
}

