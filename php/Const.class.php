<?php



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
   defIfNot(\'EF_DB_HOST\', \'localhost\');
 
// Кодировка на базата данни
   defIfNot(\'EF_DB_CHARSET\', \'utf8\');

/*****************************************************************************
 *                                                                           *
 * Пътища до някои важни части от системата                                  *
 *                                                                           *
 *****************************************************************************/ 

// Път по подразбиране за пакетите от \'vendors\'
 # defIfNot(\'EF_VENDORS_PATH\', EF_ROOT_PATH . \'/vendors\');

// Път по подразбиране за пакетите от \'private\'
 # defIfNot(\'EF_PRIVATE_PATH\', EF_ROOT_PATH . \'/private\');

// Базова директория, където се намират по-директориите за
// временните файлове. По подразбиране е в
// EF_ROOT_PATH/temp
 # defIfNot( \'EF_TEMP_BASE_PATH\', \'PATH_TO_FOLDER\');

// Базова директория, където се намират по-директориите за
// потребителски файлове. По подразбиране е в
// EF_ROOT_PATH/uploads
 # defIfNot( \'EF_UPLOADS_BASE_PATH\', \'PATH_TO_FOLDER\');

// Твърдо, фиксирано име на мениджъра с контролерните функции. 
// Ако се укаже, цялотоможе да има само един такъв 
// мениджър функции. Това е удобство за специфични приложения, 
// при които не е добре името на мениджъра да се вижда в URL-то
 # defIfNot(\'EF_CTR_NAME\', \'FIXED_CONTROLER\');
');

// Твърдо, фиксирано име на екшън (контролерна функция). 
// Ако се укаже, от URL-то се изпускат екшъните.
# defIfNot(\'EF_ACT_NAME\', \'FIXED_CONTROLER\');

// Базова директория, където се намират приложенията
# defIfNot(\'EF_APP_BASE_PATH\', \'PATH_TO_FOLDER\');

// Директорията с конфигурационните файлове
# defIfNot(\'EF_CONF_PATH\', EF_ROOT_PATH . \'/conf\');



/**
 * @todo Чака за документация...
 */
defIfNot(MANDATORY, '
// Името на приложението. Използва се за определяне на други константи
   defIfNot(\'EF_APP_NAME\', \'[#EF_APP_NAME#]\');

// Име на базата данни. По подразбиране е същото, като името на приложението
   defIfNot(\'EF_DB_NAME\', \'[#EF_DB_NAME#]\');

// Потребителско име. По подразбиране е същото, като името на приложението
   defIfNot(\'EF_DB_USER\', \'[#EF_DB_USER#]\');

// По-долу трябва да се постави реалната парола за връзка
// с базата данни на потребителят дефиниран в предходния ред
   defIfNot(\'EF_DB_PASS\', \'[#EF_DB_PASS#]\'); 
   
// Секретен ключ използван за кодиране в рамките на системата
// Той трябва да е различен, за различните инсталации на системата
// Моля сменето стойността, ако правите нова инсталация.
// След като веднъж е установен, този параметър не трябва да се променя
   defIfNot(\'EF_SALT\', \'[#EF_SALT#]\');
   
// "Подправка" за кодиране на паролите
   defIfNot(\'EF_USERS_PASS_SALT\', \'[#EF_USERS_PASS_SALT#]\');
   
// Имейла по подразбиране
   defIfNot(\'BGERP_DEFAULT_EMAIL_FROM\', \'[#BGERP_DEFAULT_EMAIL_FROM#]\');

// Домейн  по подразбиране
   defIfNot(\'BGERP_DEFAULT_EMAIL_DOMAIN\', \'[#BGERP_DEFAULT_EMAIL_DOMAIN#]\');

// Пощенска кутия по подразбиране
   defIfNot(\'BGERP_DEFAULT_EMAIL_USER\', \'[#BGERP_DEFAULT_EMAIL_USER#]\');

// Хост по подразбиране
   defIfNot(\'BGERP_DEFAULT_EMAIL_HOST\', \'[#BGERP_DEFAULT_EMAIL_HOST#]\');

// Парола по подразбиране
   defIfNot(\'BGERP_DEFAULT_EMAIL_PASSWORD\', \'[#BGERP_DEFAULT_EMAIL_PASSWORD#]\');
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
 * Клас 'php_Const' - Форматер за приложения на EF
 *
 * Създаване на теплейтен конфигурационен файл
 * bgerp.template.cfg.php
 *
 *
 * @category  vendors
 * @package   php
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class php_Const extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Създаване на конфигурационни файлове";
    
    
    var $loadList = 'php_Wrapper';
    
    /**
     * Описание на модела
     */
    function description()
    {
    
    }
    
    
    /**
     * Генериране на bgerp.template.cfg файл с всички дефинирани с defIfNot
     * константи
     */
    function act_Proces()
    {
        
    	
        $query = php_Formater::getQuery();
        
        //Посочваме, кой файл ще отворим за четене и запис
        $handle = fopen("/var/www/ef_root/fbgerp/_documentation/conf/bgerp.template.cfg.php", "w");
        
        $queryClass = php_Formater::getQuery();
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
            // Масива е за константи само то пакета ef
            if(strpos($rec->fileName, '/ef/') !== FALSE){
                $const[$captions][$rec->newComment][$rec->name] = $rec->value;
            }
            
            //Константите, които не искаме да се показват
            if ($rec->name == "'EF_APP_NAME'" ||
                $rec->name == "'EF_DB_NAME'" ||
                $rec->name == "'EF_DB_USER'" ||
                $rec->name == "'EF_DB_PASS'" ||
                $rec->name == "'EF_SALT'" ||
                $rec->name == "'EF_DB_HOST'" ||
                $rec->name == "'EF_DB_COLLATION'" ||
                $rec->name == "'EF_DB_CHARSET_CLIENT'" ||
                $rec->name == "'EF_ROLES_DEFAULT'" ||
                $rec->name == "'RICHTEXT_CACHE_TYPE'" ||
                $rec->name == "'USERREG_MIN_PASS'" ||
                $rec->name == "'USERREG_CACHE_TYPE'" ||
                $rec->name == "'USERREG_THANK_FOR_REG_MSG'" ||
                $rec->name == "'USERREG_THANK_FOR_RESET_PASS_MSG'" ||
                $rec->name == "'USERREG_THANK_FOR_RESET_PASS_MSG'" ||
                $rec->name == "'USERREG_ACTIVATION_ЕMAIL'" ||
                $rec->name == "'USERREG_RESET_PASS_ЕMAIL'" ||
                $rec->name == "'EF_MODE_SESSION_VAR'" ||
                $rec->name == "'$rec->fileName == 'EF_DB_TABLE_PREFIX'" ||
                $rec->name == "'EF_DB_CHARSET'"){
                
                unset($const[$captions][$rec->newComment][$rec->name]);
            }
            
            //Премахваме заглавията на класовете, които са останали без константи
            if ($rec->fileName == '/var/www/ef_root/ef/core/Db.class.php' ||
                $rec->fileName == '/var/www/ef_root/ef/type/Richtext.class.php' ||
                $rec->fileName == '/var/www/ef_root/ef/plg/Vid.class.php' ||
                $rec->fileName == '/var/www/ef_root/ef/plg/UserReg.class.php' ||
                $rec->fileName == '/var/www/ef_root/ef/core/Mode.class.php' ||
                $rec->fileName == '/var/www/ef_root/ef/core/Mvc.class.php' ||
                $rec->fileName == '/var/www/ef_root/ef/core/Roles.class.php'){
                unset($const[$captions]);
            }
            
            //Ако някоя константа няма стойност, я записваме в др. масив
            //if($rec->value == NULL){
            //    $null[$captions][$rec->newComment][$rec->name] = $rec->value;
            //    unset($const[$captions][$rec->newComment][$rec->name]);
            
            //}
            
            // Двумерен масив с първи ключ част от името на файла, втори - константите в този файл
            // дефинирани с defIfNot и стойност коментара на константата
            // Масива е за константи само то пакета bgerp    
            elseif(strpos($rec->fileName, '/bgerp/') !== FALSE){
                $constBgerp[$captions][$rec->newComment][$rec->name] = $rec->value;
                
                //Ако някоя константа няма стойност, я записваме в др. масив
                // if($rec->value == NULL){
                //    $nullBgerp[$captions][$rec->newComment][$rec->name] = $rec->value;
                //    unset($constBgerp[$captions][$rec->newComment][$rec->name]);
                
                // }
            }
            
            //Константите, които не искаме да се показват
            if($rec->name == "'BGERP_DEFAULT_EMAIL_FROM'" ||
                $rec->name == "'BGERP_DEFAULT_EMAIL_DOMAIN'" ||
                $rec->name == "'BGERP_DEFAULT_EMAIL_USER'" ||
                $rec->name == "'BGERP_DEFAULT_EMAIL_HOST'" ||
                $rec->name == "'BGERP_POSTINGS_HEADER_TEXT'" ||
                $rec->name == "'BGERP_DEFAULT_EMAIL_PASSWORD'"){
                
                unset($constBgerp[$captions][$rec->newComment][$rec->name]);
                
                //bp($constBgerp, $rec->name);
            }
            
            //Премахваме заглавията на класовете, които са останали без константи   
            if ($rec->fileName == '/var/www/ef_root/bgerp/email/Inboxes.class.php' ||
                strpos($rec->fileName, '/bgerp/tests') !== FALSE ||
                $rec->fileName == '/var/www/ef_root/bgerp/email/Outgoings.class.php'){
                unset($constBgerp[$captions]);
            }
            
            // Двумерен масив с първи ключ част от името на файла, втори - константите в този файл
            // дефинирани с defIfNot и стойност коментара на константата
            // Масива е за константи само то пакета bgerp      
            elseif(strpos($rec->fileName, '/vendors/') !== FALSE){
                $constVendors[$captions][$rec->newComment][$rec->name] = $rec->value;
                
                //Ако някоя константа няма стойност, я записваме в др. масив
                // if($rec->value == NULL){
                //    $nullVendors[$captions][$rec->newComment][$rec->name] = $rec->value;
                //    unset($constVendors[$captions][$rec->newComment][$rec->name]);
                
                // }
                
                //Премахваме заглавията на класовете, които са останали без константи
                if ($rec->fileName == '/var/www/ef_root/vendors/php/Const.class.php'){
                    unset($constVendors[$captions]);
                }
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
        $title = '<?php '. "\n" .TITLE . "\n" . "\n" . "\n";
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
        
        //Обхождаме константите на пакета еф
        foreach($const as $key=>$value){
            
            $n = 0;
            $m = 0;
            $k = 0;
            $y = '/var/www/ef_root/' . $key . '.class.php';
            
            if ($key)
            $n = mb_strlen(trim($shortComment[$y]));
            
            //Начало на антетката
            $string = '/*****************************************************************************' . "\n";
            $caption = $key;
            $number = mb_strlen($string);
            $numCaption = mb_strlen($caption);
            
            $a = str_repeat(" ", abs($number - $numCaption) - 5);
            $b = @str_repeat(" ", abs($number - $n) - 5);
            $string .= ' *                                                                           *' . "\n";
            
            //Проверка дали заглавието на файла може да се побере в антетката
            //Ако е голямо го разделяма на няколко реда
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
            
            //След краткия коментар на калса добавяме и заглавието на файла
            $string .= ' *                                                                           *' . "\n";
            $string .= ' * ' . $caption . $a . '*' . "\n";
            $string .= ' *                                                                           *' . "\n";
            $string .= ' *****************************************************************************/' . "\n";
            $string .= "\n";
            
            //Записваме антетката
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
                    
                    $string1 .= ' # defIfNot(\'' . $name . '\', ' . $values . ');' . "\n" . "\n" . "\n";
                    fwrite($handle, $string1);
                }
            }
        }
        
        //Повтаряме всичко за константите от пакета bgerp
        
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
                    
                    if($name == "BGERP_FIRST_PERIOD_START" || $name == "BGERP_FIRST_PERIOD_END"){
                        $string1 .= ' # defIfNot(\'' . $name . '\', ' . '\'[#'.$name.'#]\'' . ');' . "\n" . "\n" . "\n";
                    }
                    
                    elseif($value == " "){
                        $string1 .= ' # defIfNot(\'' . $name . ')' . ', );' . "\n" . "\n" . "\n";
                    }
                    else
                    $string1 .= ' # defIfNot(\'' . $name . '\', ' . $values . ');' . "\n" . "\n" . "\n";
                    fwrite($handle, $string1);
                }
            }
        }
        
        //Повтаряме всичко за константите от пакета vendors
        
        //Заглавие за пакета Вендорс
        $captionVendors = CAPTIONVENDORS . "\n" . "\n" . "\n";
        
        fwrite($handle, $captionVendors);
        
        foreach($constVendors as $key=>$value){
            if($key == 'vendors/php/Formater'){
                continue;
            }
            
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
                    
                    $string1 .= ' # defIfNot(\'' . $name . '\', ' . $values . ');' . "\n" . "\n" . "\n";
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
        $data->toolbar->addBtn('Генерирай', array($mvc, 'Proces'));
      
    }
}

