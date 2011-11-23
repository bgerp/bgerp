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
    
    var $title = "Форматиране за файлове от EF";

    var $loadList = 'plg_RowTools,plg_Sorting';

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('fileName', 'varchar', 'caption=Файл');
        $this->FLD('type', 'enum(class=Клас,var=Свойство,function=Функция)', 'caption=Ресурс->Тип');
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

            if(!$form->gotErrors()) {


                $files = (object) $this->readAllFiles($src);
                
                foreach($files->files as $f) {
                    
                    $destination = str_replace("\\", "/", $dst . $f);
                    $dsPos = strrpos($destination, "/");
                    $dir = substr($destination, 0, $dsPos);
                    
                    if(!is_dir($dir)) mkdir($dir, 0777, TRUE);
                    
                    // Ако класа е със суфикс от приетите от фреймуърка, той се обработва ("разхубавява")
                    if( strpos($f, '.class.php') || strpos($f, '.inc.php') ) {
                        
                        $beautifier = cls::get('php_BeautifierM');
                        
                        $res .= $beautifier->file($src . $f, $destination);

                     } else {
                        copy($src . $f, $destination);
                    }
                }
                
                return new Redirect(array($this));
            }
        }

        return $this->renderWrapping($form->renderHtml());
    }
    

    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('Форматиране...', array($mvc, 'Process'));
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
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root. DIRECTORY_SEPARATOR;
        
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