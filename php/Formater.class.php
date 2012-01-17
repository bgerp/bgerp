<?php

/**
 * Обща директория на bgerp, vendors, ef. Използва се за едновремнно форматиране на трите пакета.
 */
defIfNot('EF_ALL_PATH', EF_ROOT_PATH . '/all');


define(LICENSE, 3);
define(VERSION, 0.1);


/**
 * Клас 'php_Formater' - Форматер за приложения на EF
 *
 * Форматира кода на файлове, включени във ЕП, приложението, vendors, private и др.
 *
 * @category   Experta Framework
 * @package    php
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @since      v 0.1
 */
class php_Formater extends core_Manager
{
    
    var $title = "Форматиране за файлове от EF/bgERP/vendors";

    var $loadList = 'plg_RowTools,plg_Sorting,plg_Sorting,plg_Search';
    
    var $searchFilds = 'fileName, name, type, oldComment';
    
    
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
        $this->FLD('oldComment', 'text', 'caption=Коментар->Стар');
        $this->FLD('newComment', 'text', 'caption=Коментар->Нов');
    }

    


    /**
     *  @todo Чака за документация...
     */
    function act_Process()
    {
        requireRole('admin');
        expect(isDebug());

        $form = cls::get('core_Form');
        
        if(defined('EF_PRIVATE_PATH')) {
            $form->FNC('src', 'enum(' . EF_APP_PATH . ',' . EF_EF_PATH . ',' . EF_VENDORS_PATH . ',' . EF_PRIVATE_PATH .')', 'caption=Директории->Източник,input,mandatory');
        } else {
             $form->FNC('src', 'enum(' . EF_APP_PATH . ',' . EF_EF_PATH . ',' . EF_VENDORS_PATH .', ' . EF_ALL_PATH .')', 'caption=Директории->Оригинален код,input');
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
                
                foreach($files->files as $f) { 
                    
                     //if( stripos($f, 'Avatarco') === FALSE) continue;
                    

                    $destination = str_replace("\\", "/", $dst . $f);
                    $dsPos = strrpos($destination, "/"); 
                    $dir = substr($destination, 0, $dsPos);
                    
                    if(!is_dir($dir)) mkdir($dir, 0777, TRUE);
                    
                    // Ако класа е със суфикс от приетите от фреймуърка, той се обработва ("разхубавява")
                    if( strpos($f, '.class.php') || strpos($f, '.inc.php') ) {
                
                $str = file_get_contents( $src . $f );
                
                $lines = count(explode("\n", $str));
                $symbol = mb_strlen(trim($str));
                

                // Колко линии код има в пакета заедно с празните редове?
                $this->lines += $lines;
                
                // Колко символа има в пакета заедно с празните редове?
                $this->symbol += $symbol;
                
                $commLines = explode("\n", $str);
                $dComm = 0;
                foreach ($commLines as $comm){
                	if(strpos($comm,"/**") || strpos($comm, "*/") || strpos($comm, "*") || strpos($comm, "//")) {
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
    
    
    
    
    function act_Class(){
    	
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
                            			if(($lines[1] != "" ) && (strpos($l, '@', 0)) ){
                            				$shortComment .= "".$lines[1];
                            				//$shortComment = trim($shortComment);
                            				
                            			}
                            			if (($l !== "") && ($l{0} !== '@') && ($l !== trim($shortComment))){
                            			//Обширен коментар
                            			$extensiveComment .= $l."\n";
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
                            	$category = strtok(substr_replace($rec->fileName, $str, 0, strlen($str1)), "/"); //$category
                            	$package = strtok(substr_replace(strstr(substr_replace($rec->fileName, $str, 0, strlen($str1)), "/"), $str, 0, 1), "/"); //$package
                            	
                            	/*$str2 = "/var/www/ef_root/all";
                            	$category = strtok(substr_replace($rec->fileName, $str, 0, strlen($str2)), "/"); //$category
                            	$package = strtok(substr_replace(strstr(substr_replace($rec->fileName, $str, 0, strlen($str2)+4), "/"), $str, 0, 1), "/"); //$package
                            	*/
                            	
                              
                            	
                            //bp($extensiveComment, $shortComment,$author,$commArr, $lines);
                            
                            unset($commArr['@category']);
                            unset($commArr['@package']);
                            unset($commArr['@author']);
                            unset($commArr['@copyright']);
                            unset($commArr['@license']);
                            unset($commArr['@since']);
                            //unset($commArr['@see']);
                            unset($commArr['@version']);
                            unset($commArr['@subpackage']);
                  
                             
                               // Правим ново форматиране на всеки клас
                           
                           $classComment = $shortComment. "\n" . "\n";
                           if($extensiveComment != ""){
                           $classComment .= $extensiveComment. "\n". "\n" ;
                           } else $classComment .= "\n";
                           $classComment .= '@category  '. $category. "\n";
                           $classComment .= '@package   '. $package. "\n";
                           $classComment .= '@author    '. $author. "\n";
                           $classComment .= '@copyright 2006 - ' . $year.  ' Experta OOD'."\n";
                           $classComment .= '@license   GPL '. LICENSE. "\n";
                           $classComment .= '@since     v '. VERSION . "\n";
                          // $classComment .= '@see'."\n";
                           foreach ($commArr as $key=>$new){
                           	$lenght = strlen($key);
                           	if ($lenght == 4){
                           		$classComment .= $key."       ".trim($new). "\n" ;
                           	} elseif($lenght == 5) {
                           	    $classComment .= $key."      ".trim($new). "\n" ;
                           	}else{
                           	    $classComment .= $key."     ".trim($new). "\n" ;
                           	}
                          
                           }
                           
                          

                           $rec->id = $id;
                           $rec->fileName = $file;
                           $rec->type = $type;
                           $rec->name = $name;
                           //$rec->oldComment = $classComment;
                           $rec->newComment = $classComment;
                         
                           php_Formater::save($rec);
                            }
                           
                        return new Redirect(array($this,'?id=&Cmd[default]=1&search=&search=&type=class&Cmd[default]=Филтрирай')); 
            
                            
    }
    
    
    
    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('Форматиране...', array($mvc, 'Process'));
        $data->toolbar->addBtn('Тест', array('php_Test', 'Tester'));
        $data->toolbar->addBtn('Класове', array($mvc, 'Class'));
    }
    
    
	/**
	 * 
	 * Форма за търсене по дадена ключова дума
	 */
    function on_AfterPrepareListFilter($mvs, $res, $data)
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
     *   'files' => [],
     *   'dirs'  => [],
     * )
     * @param string $root
     * @result array
     */
    function readAllFiles($root = '.')
    {
        $files = array('files'=>array(), 'dirs'=>array());
        $directories = array();
        $last_letter = $root[strlen($root)-1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root. DIRECTORY_SEPARATOR; //?
        
        $directories[] = $root;
        
        while (sizeof($directories)) {
            
            $dir = array_pop($directories);
            

            if ($handle = opendir($dir)) {
                while (FALSE !== ($file = readdir($handle))) {  
                    if ($file == '.' || $file == '..' || $file == '.git') {
                        continue;
                    }
                    $file = $dir.$file;
                    
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

     



