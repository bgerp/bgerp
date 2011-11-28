<?php
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
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class php_Formater extends core_Manager
{
    
    var $title = "Форматиране за файлове от EF/bgERP/vendors";

    var $loadList = 'plg_RowTools,plg_Sorting,plg_Sorting,plg_Search';
    
    var $searchFilds = 'fileName, name, type, oldComment';
    
    var $arr2;

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
             $form->FNC('src', 'enum(' . EF_APP_PATH . ',' . EF_EF_PATH . ',' . EF_VENDORS_PATH .')', 'caption=Директории->Оригинален код,input');
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

            if(!$form->gotErrors()) { //?


                $files = (object) $this->readAllFiles($src);
                
 
                set_time_limit(120);
                
                foreach($files->files as $f) { 

                    
                    $destination = str_replace("\\", "/", $dst . $f);
                    $dsPos = strrpos($destination, "/"); //?
                    $dir = substr($destination, 0, $dsPos);
                    
                    if(!is_dir($dir)) mkdir($dir, 0777, TRUE);
                    
                    // Ако класа е със суфикс от приетите от фреймуърка, той се обработва ("разхубавява")
                    if( strpos($f, '.class.php') || strpos($f, '.inc.php') ) {
                        //if( strpos($f, '.class.php')){
                        $beautifier = cls::get('php_BeautifierM');
                        
                        $res .= $beautifier->file($src . $f, $destination); //?
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
						
						foreach ($arr as $key => $value){
						
						       if(($value == 1) && ($arrF[$key] == 1)){
						       		bp($key,$arr,$arrF);
						  }
						}
                        
						
                     } else {
                        copy($src . $f, $destination);
                    }
                }
                //arsort($arr);
              // bp($arrF);
            
                return new Redirect(array($this)); //?
            }
        }

        return $this->renderWrapping($form->renderHtml()); //?
    }
    

    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('Форматиране...', array($mvc, 'Process'));
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
                while (FALSE !== ($file = readdir($handle))) {  //?
                    if ($file == '.' || $file == '..' || $file == '.git') {
                        continue;
                    }
                    $file = $dir.$file;
                    
                    if (is_dir($file)) {
                        $directory_path = $file . DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                        $files['dirs'][] = $directory_path; //?
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
