<?php


defIfNot('EF_DOWNLOAD_ROOT', '_dl_' );


/**
 *  @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_DIR', EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . EF_DOWNLOAD_ROOT );


/**
 *  @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_PREFIX_PTR', '$*****');


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
        
        $this->FLD( "prefix", "varchar(" . strlen(EF_DOWNLOAD_PREFIX_PTR) . ")",
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
        $fRec = fileman_Files::fetchByFh($fh);
        
        if(!$fRec) return FALSE;
        
        $time = dt::timestamp2Mysql(time() + $lifeTime * 3600);
        
        //Ако имаме линк към файла, тогава използваме същия линк
        $dRec = $this->fetch("#fileId = '{$fRec->id}'");
        if ($dRec) {
        	$dRec->expireOn = $time;
        	
        	$link = sbf(EF_DOWNLOAD_ROOT . '/' . $dRec->prefix . '/' . $dRec->fileName, '', TRUE);
        	
        	$this->save($dRec);
        	
        	return $link;
        }
        
        // Генерираме името на директорията - префикс
        do {
            $rec->prefix = str::getRand(EF_DOWNLOAD_PREFIX_PTR);
        } while(self::fetch("#prefix = '{$rec->prefix}'"));
         
        // Задаваме името на файла за сваляне - същото, каквото файла има в момента
        $rec->fileName = $fRec->name;
        
        // Създаваме директорията - префикс
        if(!is_dir(EF_DOWNLOAD_DIR . '/' . $rec->prefix)) {
            mkdir(EF_DOWNLOAD_DIR . '/' . $rec->prefix, 0777, TRUE);
        }
        
        // Вземаме пътя до данните на файла
        $originalPath = fileman_Files::fetchByFh($fRec->fileHnd, 'path');
        
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
        $rec->expireOn = $time;
        
        // Записваме информацията за свалянето, за да можем по-късно по Cron да
        // премахнем линка за сваляне
        self::save($rec);
		
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
     * Изтрива линковете, които не се използват и файловете им
     */
    function clearOldLinks()
    {
    	$now = dt::timestamp2Mysql(time());
    	$Fconv = cls::get('fconv_Processes');  	
    	$query = self::getQuery();
		$query->where("#expireOn < '{$now}'");
		
		$htmlRes .= "<hr />";
		
		$count = $query->count();
		
		if (!$count) {
			$htmlRes .= "\n<li style='color:green'> Няма записи за изтриване.</li>";
		} else {
			$htmlRes .= "\n<li'> Трябва да се изтрият {$count} записа.</li>";
		}
		
		while ($rec = $query->fetch()) {
			
			$htmlRes .= "<hr />";
			
			$dir = EF_SBF_PATH . '/' . EF_DOWNLOAD_ROOT . '/' . $rec->prefix;
						
			if (self::delete("#id = '{$rec->id}'")) {
				$htmlRes .= "\n<li> Deleted record #: $rec->id</li>";
				
				if ($Fconv->deleteDir($dir)) {
					$htmlRes .= "\n<li> Deleted dir: $rec->prefix</li>";
				} else {
					$htmlRes .= "\n<li style='color:red'> Can' t delete dir: $rec->prefix</li>";
				}
				
			} else {
				$htmlRes .= "\n<li style='color:red'> Can' t delete record #: $rec->id</li>";
			}
		}
    	
    	return $htmlRes;
    }
    
    
    /**
     * Стартиране на процеса за изтриване на ненужните файлове
     */
    function act_ClearOldLinks()
    {
    	$clear = $this->clearOldLinks();
    	
    	return $clear;
    }
    
    
    /**
     * Стартиране на процеса за изтриване на ненужните файлове по крон
     */
	function cron_ClearOldLinks()
    {
    	$clear = $this->clearOldLinks();
    	
    	return $clear;
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
        
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId = 'ClearOldLinks';
        $rec->description = 'Изчиства старите линкове за сваляне';
        $rec->controller = $this->className;
        $rec->action = 'ClearOldLinks';
        $rec->period = 100;
        $rec->offset = 0;
        $rec->delay = 0;
     // $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да изчиства линкове и директории, с изтекъл срок.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да изчиства линкове и директории, с изтекъл срок.</li>";
        }
        
        return $res;
        
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