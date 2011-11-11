<?php


defIfNot('EF_DOWNLOAD_ROOT', '_dl_' );


/**
 *  @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_DIR', EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . EF_DOWNLOAD_ROOT );


/**
 *  @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_PREFIX_LEN', 6);


/**
 * Клас 'fileman_Download' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    fileman
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fileman_Download extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pathLen = 6;
    
    
    /**
     *  Заглавие на модула
     */
    var $title = 'Сваляния';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // Файлов манипулатор - уникален 8 символно/цифров низ, започващ с буква.
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD( "fileName", "varchar(255)", 'notNull,caption=Име');
        
        $this->FLD( "prefix", "varchar(" . EF_DOWNLOAD_PREFIX_LEN . ")",
        array('notNull' => TRUE, 'caption' => 'Префикс'));
        
        // Име на файла
        $this->FLD( "fileId",
        "key(mvc=fileman_Files)",
        array('notNull' => TRUE, 'caption' => 'Файл'));
        
        // Крайно време за сваляне
        $this->FLD( "expireOn",
        "datetime",
        array('caption' => 'Активен до') );
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,Files=fileman_Files,fileman_Wrapper,Buckets=fileman_Buckets');
        
        // Индекси
        $this->setDbUnique('prefix');
    }
    
    
    /**
     * Връща URL за сваляне на файла с валидност publicTime часа
     */
    function getDownloadUrl($fh, $lifeTime = 1)
    {
        // Намираме записа на файла
        $fRec = $this->Files->fetchByFh($fh);
        
        if(!$fRec) return FALSE;
        
        // Генерираме името на директорията - префикс
        do {
            $rec->prefix = $this->Files->getUniqId(EF_DOWNLOAD_PREFIX_LEN);
        } while($this->fetch("#prefix = '{$rec->prefix}'"));
        
        // Задаваме името на файла за сваляне - същото, каквото файла има в момента
        $rec->fileName = $fRec->name;
        
        // Създаваме директорията - префикс
        if(!is_dir(EF_DOWNLOAD_DIR)) {
            mkdir(EF_DOWNLOAD_DIR, 0777, TRUE);
        }
        
        if(!is_dir(EF_DOWNLOAD_DIR . '/' . $rec->prefix)) {
            mkdir(EF_DOWNLOAD_DIR . '/' . $rec->prefix, 0777, TRUE);
        }
        
        // Вземаме пътя до данните на файла
        $originalPath = $this->Files->fetchByFh($fRec->fileHnd, 'path');
        
        // Генерираме пътя до файла (hard link) който ще се сваля
        $downloadPath = EF_DOWNLOAD_DIR . '/' . $rec->prefix . '/' . $rec->fileName;
        
        // Създаваме хард-линк или копираме
        if(!function_exists( 'link' ) || !@link($originalPath, $downloadPath)) {
            if(!@copy($originalPath, $downloadPath)) {
                error("Не може да бъде копиран файла|* : '{$originalPath}' =>  '{$downloadPath}'");
            }
        }
        
        // Задаваме id-то на файла
        $rec->fileId = $fRec->id;
        
        // Задаваме времето, в което изтича възможността за сваляне
        $rec->expiredOn = dt::timestamp2Mysql(time() + $lifetime*3600);
        
        // Записваме информацията за свалянето, за да можем по-късно по Cron да
        // премахнем линка за сваляне
        $this->save($rec);
        
        // Връщаме линка за сваляне
        return sbf(EF_DOWNLOAD_ROOT . '/' . $rec->prefix . '/' . $rec->fileName, '', TRUE);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_Download()
    {
        $fh = Request::get('fh');
        
        $fRec = $this->Files->fetchByFh($fh);
        
        $this->Files->requireRightFor('download', $fRec);
        
        redirect( $this->getDownloadUrl($fh, 1) );
    }
    
    
    /**
     *  Извиква се след SetUp-а на таблицата за модела
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        if(!is_dir(EF_DOWNLOAD_DIR)) {
            if( !mkdir(EF_DOWNLOAD_DIR, 0777, TRUE) ) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') .
                ' "' . EF_DOWNLOAD_DIR . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' .
                EF_DOWNLOAD_DIR . '"</font';
            }
        }
    }
    
    
    /**
     * Връща html <а> линк за сваляне на файла
     */
    function getDownloadLink($fh)
    { 
        // Намираме записа на файла
        $fRec = $this->Files->fetchByFh($fh);
        
        if(!$fRec) return FALSE;
                
    	if( ($dotPos = mb_strrpos($fRec->name, '.')) !== FALSE ) {
            $ext = mb_substr($fRec->name, $dotPos + 1);
        } else {
        	$ext = '';
        }
        
        $icon = "fileman/icons/{$ext}.png";
        
        if (!is_file(getFullPath($icon))) {
        	$icon = "fileman/icons/default.png";
        }
        
        $attr['class'] = 'linkWithIcon';
        $attr['target'] = '_blank';
        $attr['style'] = 'background-image:url(' . sbf($icon) . ');';

        if ($this->Files->haveRightFor('download', $fRec)) {
        	//Генерираме връзката
			$link = ht::createLink($fRec->name, array($this, 'Download', 'fh' => $fh), NULL, $attr);
        } else {
        	//Генерираме името с иконата
			$link = "<span class='linkWithIcon'; style=" . $attr['style'] . "> {$fRec->name} </span>";
        }
        
        return $link;
    }
}