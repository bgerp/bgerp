<?php



/**
 * Метаинформация за всички дела в български съдилища
 *
 *
 * @category  vendors
 * @package   legalact
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2012 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class legalact_Acts extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Издадени актове от български съдилища";
   
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'legalact_Wrapper,plg_Sorting,plg_Search, plg_Printing';
    

    var $searchFields = 'actNumber, caseNumber, actText, motiveText';
    

    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,admin,legalact';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'no_one';
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'legalact/tpl/ActSingleLayout.shtml';    

    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,admin,legalact';
    
	var $listFields = 'act=Акт,case=Дело,motiveDate=,highCourt=Висш съд,caseKind=,caseNumber=,
        startDate=,judge=,legalDate=,actKind=,actNumber=,actYear=,court=,typeOfDocument=,yearHigherCourt=,
        outNumber=,resultOfAppeal=';
    
    var $oldClassName = 'legalact_Cases';

    /**
     * Описание на модела
     */
    function description()
    {	
		// Съд
        $this->FLD('court', 'varchar(100)', 'caption=Съд');
		
        // Акт
        $this->FLD('actKind', 'varchar(20)', 'caption=Акт->Вид');
        $this->FLD('actNumber', 'int(6)', 'caption=Акт->Номер');
        $this->FLD('actYear', 'int(6)', 'caption=Акт->Година');
        $this->FLD('actLink', 'int(3)', 'caption=Акт->Връзки');
        $this->FLD('actTextPath', 'varchar', 'caption=Акт->Текст');

		// Дело
        $this->FLD('caseKind', 'varchar(100)', 'caption=Дело->Вид');
        $this->FLD('caseNumber', 'int', 'caption=Дело->Номер');
        $this->FLD('startDate', 'date', 'caption=Дело->Начало');
        $this->FLD('judge', 'varchar(255)', 'caption=Дело->Съдия');
        $this->FLD('legalDate', 'date', 'caption=Дело->Ход');
		
        
		// Мотиви
		$this->FLD('motiveDate', 'date', 'caption=Мотиви->Дата');
        $this->FLD('motiveLink', 'int(3)', 'caption=Мотиви->Връзка');
        $this->FLD('motiveTextPath', 'varchar', 'caption=Мотиви->Текст');
		
		// Висша инстанция
		$this->FLD('highCourt', 'varchar(100)', 'caption=Висша инстанция->Съд');
		$this->FLD('typeOfDocument', 'varchar(100)', 'caption=Висша инстанция->Документ');
        $this->FLD('outNumber', 'int(6)', 'caption=Висша инстанция->Изх. No');
        $this->FLD('yearHigherCourt', 'int(6)', 'caption=Висша инстанция->Година');
		$this->FLD('sendDate', 'date', 'caption=Висша инстанция->Дата');
        $this->FLD('resultOfAppeal', 'varchar(100)', 'caption=Висша инстанция->Резултат');

        set_time_limit(160);
    }


	/**
	 * Преобразуване от вътрешно представяне към вербално
	 */
	function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
	{
        if($fields['-list']) {
            $url = toUrl(array($mvc, 'single', $rec->id, 'highlight' => Request::get('search')));
            $actTpl = new ET("
            <div class='actKind'><a href='{$url}'><b>[#actKind#]</b></a></div>
            <div class='court'><div class='court'>[#court#]</div><!--ET_END court-->
            <div class='actYear'><!--ET_BEGIN actNumber-->[#actNumber#]/<!--ET_END actNumber-->[#actYear#]</div>
            <!--ET_BEGIN motiveDate--><div class='motive'>Дата на мотивите: [#motiveDate#]</div><!--ET_END motiveDate-->
            ");

            $row->act = $actTpl->placeObject($row)->getContent();

            $row->case = 
                $row->caseKind . '<br>' .
                $row->caseNumber .  '/' . $row->startDate . '<br>' .
                $row->judge . 
                (trim($rec->legalDate) ? ('<br>' . "Даден ход на: " . $row->legalDate) : '');

            $hcTpl = new ET("
                <!--ET_BEGIN highCourt--><div class='court'>[#highCourt#]</div><!--ET_END highCourt-->
                <!--ET_BEGIN typeOfDocument--><div class='actKind'>[#typeOfDocument#]</div><!--ET_END typeOfDocument-->
                <!--ET_BEGIN yearHigherCourt-->
                    <div>
                        <!--ET_BEGIN outNumber-->[#outNumber#]/<!--ET_END outNumber-->
                        [#yearHigherCourt#]
                    </div>
                <!--ET_END yearHigherCourt-->
                <!--ET_BEGIN resultOfAppeal-->[#resultOfAppeal#]<!--ET_END resultOfAppeal-->
                ");

            $row->highCourt = $hcTpl->placeObject($row)->getContent();

        }

        if($fields['-single']) {
             $text = cls::get('type_Text');

             if($rec->actTextPath) {
                 $rec->actTextPath = html_entity_decode ( file_get_contents($rec->actTextPath), ENT_QUOTES, 'UTF-8');
                 $row->actTextPath = $text->toVerbal($rec->actTextPath);
             }
             
             if($rec->motiveTextPath) {
                 // echo("<pre>" . file_get_contents($rec->motiveTextPath) . "</pre>"); die;
                 $rec->motiveTextPath = html_entity_decode ( file_get_contents($rec->motiveTextPath), ENT_QUOTES, 'UTF-8');
                 $row->motiveTextPath = $text->toVerbal($rec->motiveTextPath);
             } else {
                 $row->motiveTextPath = NULL;
                 $row->motiveDate = NULL;
             }

             if($h = Request::get('highlight')) {
                 $color = '#ffff66';
                 $h = preg_quote($h);
                 if($row->actTextPath) {
                    $row->actTextPath = preg_replace("|($h)|ui" , "<span style=\"background:".$color.";\"><b>$1</b></span>" , $row->actTextPath);
                 }
                if($row->motiveTextPath) {
                    $row->motiveTextPath = preg_replace("|($h)|ui" , "<span style=\"background:".$color.";\"><b>$1</b></span>" , $row->motiveTextPath);
                 }

             }

        }
	}



    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
       // Добавяме поле във формата за търсене
 
        $data->listFilter->view = 'horizontal';

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');

        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'search';

        $data->listFilter->input('search', 'silent');

        $data->query->orderBy('startDate', 'DESC');
    }


    /**
     * Връща ключовите думи за един запис
     */
    function GetSearchKeywords($rec)
    {
        if($rec->actTextPath) {
            $text = file_get_contents($rec->actTextPath);
        }

        if($rec->motiveTextPath) {
            $text .= ' ' . file_get_contents($rec->motiveTextPath);
        }

        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        $searchKeywords =  plg_Search::normalizeText($text);

        $searchKeywords =  implode(' ', array_keys(array_flip(explode(' ', $searchKeywords))));

        $searchKeywords = $rec->actNumber . '/' . $rec->actYear . ' ' . $searchKeywords;

        return $searchKeywords;
    }
    

	/**
     * Изпълнява се след SetupMVC
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {   
        if(haveRole('admin')) {
            $data->toolbar->addBtn('Импортирай файловете', array($mvc, 'Import'), 'class=btn-import');
        }
    }

    
    /**
	 * Импортиране съдържанието на файловете
	 */
	function act_Import()
	{
        $cfg = core_Packs::getConfig('legalact');

		$root = $cfg->LEGALACT_DOCS_ROOT;
        
        ini_set('memory_limit', '2024M');
        
        set_time_limit(7200);
        
        core_Debug::$isLogging = FALSE;

        $dirContent = $this->readAllFiles($root);

 		foreach($dirContent['files'] as $i => $fn)
        {
            $filePath = $root . $fn;

            $fileArr = explode("/", $fn);

            $baseName = $fileArr[count($fileArr)-1];

            list($name, $ext) = explode('.', $baseName);

            list($id, $type) = explode('_', $name);

            expect(is_numeric($id));
            expect($type == 'a' || $type == 'm');
 
            unset($rec);
            $rec = $this->fetch($id, '*', FALSE);
            
            
            if(($j++) % 5000 == 23) gc_collect_cycles();

            if($type == 'a') {
                if($rec->actTextPath && !Request::get('force')) continue;
                $rec->actTextPath = $filePath;

            } else {
                if($rec->motiveTextPath && !Request::get('force')) continue;
                $rec->motiveTextPath = $filePath;
            }
            
            $this->save($rec);
                        
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
        
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;        //?
        
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
                        $files['files'][] = str_replace('\\', '/', str_replace($root, "", $file));

                        if(count($files['files']) > 1000) return $files;
                    }
                }
                closedir($handle);
            }
        }
        
        return $files;
    }

 }