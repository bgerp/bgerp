<?php


/**
 * Дефинира разрешените домейни за използване на услугата.
 */
defIfNot('EF_ALLOWED_DOMAINS', 0);


/**
 * Дефинира име на папка в която ще се съхраняват данните
 */
defIfNot('DOCVIEW_TEMP_DIR', EF_TEMP_PATH . "/docview/");


/**
 * Клас 'docview_Viewer' - За разглеждане на изображения посредством zoom.it
 *
 *
 * @category   Experta Framework
 * @package    docview
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n *
 * @since      v 0.1
 */
class docview_Viewer extends core_Manager {
    
	
	/**
	 * Масив, в който се записват всички fileman handler' и
	 */
    var $handler = array();
    
    
    /**
     * Разширение на изходния файл
     */
    var $outExtension;
    
    
    /**
     * Реалното разширение на входния файл, взето от mime type
     */
    var $inExtension;
    
    /**
     *  Заглавие на страницата
     */
    var $title = 'Разглеждане на документи';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('url', 'varchar', 'caption=Линк');
        $this->FLD('inHnd', 'varchar(8)', 'caption=Входен манипулатор');
        $this->FLD('outHnd', 'varchar(8)', 'caption=Изходен манупулатор');
        $this->FLD('inExt', 'varchar(8)', 'caption=Разширение на входния файл');
        $this->FLD('outExt', 'varchar(8)', 'caption=Разширение на изходния файл');
        $this->FLD('dataId', 'int', 'caption=Идентификатор на файла');
        $this->FLD('createdOn', 'date', 'caption=Дата на създаване');
        $this->FLD('ready', 'int', 'caption=Завършена обработка');
        $this->FLD('zoomitHnd', 'blob(70000)', 'caption=Zoomit обект');
        
        $this->setDbUnique('url');
    }
    
    
    /**
     * Сваля файла от url' то, което му е подадедено
     * Ако е необходимо конвертира файла
     * И показва файла със zoom.it или flexpaper
     */
    function act_Render()
    {
    	$url = Request::get("url");
    	
    	if ((!isset($url)) || (!mb_strlen($url))) {
    		
    		return "Не сте въвели URL.";
    	} 
    	
    	if (!URL::isValidUrl($url)) {
    		
    		return "Въвели сте грешно URL.";
    	}
    	
    	if (EF_ALLOWED_DOMAINS) {
    		$allowedDomains = arr::Make(EF_ALLOWED_DOMAINS);
    		$parseUrl = URL::parseUrl($url);
    		if (!in_array($parseUrl['domain'], $allowedDomains)) {
    			
    			return "Не е позволено да се разглеждата линкове от този домейн: {$parseUrl['domain']}";
    		}
    	}
    	
    	/**
    	 * Сваля файла и го качва във fileman.
    	 * Проверява дали файла вече е качен във fileman.
    	 */
    	
    	$names = $this->getNameFromLink($url);
    	
    	$arr = array(
    		'url' => $url,
    		'fileName' => DOCVIEW_TEMP_DIR.$names['fileName']
    	);
    	
    	$this->download($arr);
    	
    	$this->inExtension = $this->mimeContentType($arr['fileName']);
    	
   		if (!$this->inExtension) {
   			
			$tpl = new ET();
			$tpl->append('Избраният от вас файл е с разширение, което е забранено за използване.', 'PAGE_CONTENT');
			
			@unlink($arr['fileName']);
			
			return $tpl;
		}
		
		$this->handler['inHnd'] = $this->insertFileman($arr['fileName']);
		
		$filemanFiles = cls::get('fileman_Files');
    	$dataId = $filemanFiles->fetchByFh($this->handler['inHnd'], 'dataId');
    	
    	
    	$tpl = new ET();
    	$tpl->appendOnce("\n".'<meta http-equiv="refresh" content="10">', "HEAD");
    	$tpl->append('Моля изчакайте...', PAGE_CONTENT);
    	
    	$rec = self::fetch(array("#dataId = '[#1#]'", $dataId));
    	
    	if ($rec) {
    		
    		@unlink($arr['fileName']);
    		
    		if(!isset($rec->ready)) {
    			
    			return $tpl;
    		}
    		
    		if (isset($rec->zoomitHnd)) {
    			$obj = json_decode($rec->zoomitHnd);
    		
    			return $obj->embedHtml;
    		}
    		if (isset($rec->outExt)) {
	    		if ($rec->outExt == 'swf') {
	    			$tpl = new ET();
	    			$tpl->append(flexpaper_Render::View($rec->outHnd), 'PAGE_CONTENT');
	    			
	    			return $tpl;
	    		}	
    		}
    	}
    	
    	
    	/**
    	 * Ако разширението на файла e pdf, тогава се проверяват броя на страниците
    	 * Ако броят на страниците е >1 тогава изходното разширение ще е swf, 
    	 * в противен случай ще е png
    	 */
		if ($this->inExtension == 'pdf') {
			$pages = $this->pdfPages($arr['fileName']);
    		$this->outExtension =  ".png";
    		
			if ($pages > 1) {
				$this->outExtension =  ".swf";
			}
		}
    	
    	$outFileName = DOCVIEW_TEMP_DIR.$this->addNewExtension($arr['fileName']);
    	
    	
    	/**
    	 * Записваме данните в таблицата
    	 */
    	$rec = new stdClass();
		$rec->url = $url;
		$rec->inHnd = $this->handler['inHnd'];
		$rec->outExt = str_ireplace('.', '', $this->outExtension);
		$rec->inExt = $this->inExtension;
		$rec->createdOn = dt::verbal2mysql();
		$rec->dataId = $dataId;
 		docview_Viewer::save($rec);
    	
		$convertData = array (
			'fileName' => $arr['fileName'],
			'outFileName' => $outFileName,
			'viewerId' => $rec->id,
			'outExtension' => str_ireplace('.', '', $this->outExtension)
		);		
		
		$notConvert = array (
			'viewerId' => $rec->id,
			'inHnd' => $this->handler['inHnd'],
			'fileName' => $arr['fileName'],
			'inExtension' => $this->inExtension,
			'inHnd' => $this->handler['inHnd']
		);
		
		switch ($this->inExtension) {
			
			case 'pdf':
				$this->scriptConvertFromPdf($convertData);
			break;
			
			case 'svg':
			case 'tiff':
			case 'jpeg':
			case 'png':
				$returnedData = $this->notConvert($notConvert);
				if (isset($returnedData)) {
					$obj = json_decode($returnedData);
	    		
	    			return $obj->embedHtml;
				} 
	    		
			break;
			
			default:
				
			break;
		}
		
  		return $tpl;
    }

    
    /**
     * Генерира и извиква скрипта за конвертиране на файлове
     */
    function scriptConvertFromPdf($convertData) {
    	
    	$script = new fconv_Script();
    	$script->setFile('INPUTF', "{$convertData['fileName']}");
    	switch ($convertData['outExtension']) {
    		case 'png':
		    	$script->setProgram('gs','/usr/bin/gs-904-linux_x86_64');
		    	$script->lineExec("gs -sDEVICE=png16m -dGraphicsAlphaBits=4 -dTextAlphaBits=4 -sOutputFile={$convertData['outFileName']} -dBATCH -r200 -dNOPAUSE [#INPUTF#]");
		    	$script->callBack('docview_Viewer::zoomIt');
    		break;
    		
    		case 'swf':
    			$script->lineExec("pdf2swf -T 9 [#INPUTF#] -o {$convertData['outFileName']}");
    			$script->callBack('docview_Viewer::zoomIt');
    		break;
    		
    		default:
    			
    			return FALSE;
    		break;
    	}
    	
    	$script->viewerId = $convertData['viewerId'];
    	$script->outFileName = $convertData['outFileName'];
    	$script->fileName = $convertData['fileName'];
    	$script->outExtension = $convertData['outExtension'];
  		$script->run();
    	
    }
    
    
    /**
     * Получава управлението то callBack' а
     * Вкарва във fileman png картинката и след това изтрива временните файлове
     * Добавя в таблицата информация от zoom.it за съответния линк
     */
    function zoomIt($script)
    {
    	$rec = new stdClass();
    	$rec->id = $script->viewerId;
    	
    	$this->handler['outHnd'] = $this->insertFileman($script->outFileName);
    	$rec->outHnd = $this->handler['outHnd']; 
    	
	    switch ($script->outExtension) {
				
			case 'swf':
				
			break;
			
			case 'svg':
			case 'tiff':
			case 'jpeg':
			case 'png':
				$Files = cls::get('fileman_Download');
    			$filePath = $Files->getDownloadUrl($this->handler['outHnd']);
    			
				$this->handler['zoomitHnd'] = file_get_contents("http://api.zoom.it/v1/content/?url={$filePath}");
				$rec->zoomitHnd = $this->handler['zoomitHnd'];
			break;
			
			default:
				
			break;
		}
    	
    	@unlink($script->outFileName);
    	@unlink($script->fileName);
    	$rec->ready = 1;
		docview_Viewer::save($rec);
		
    	return TRUE;
    }
    
    
    /**
     * Показване на файлове, които не изискват конвертиране
     */
	function notConvert($notConvert) {
		$rec = new stdClass();
    	$rec->id = $notConvert['viewerId'];
    	$rec->ready = 1;
		$rec->outExt = NULL;
		switch ($notConvert['inExtension']) {
				
			case 'svg':
			case 'tiff':
			case 'jpeg':
			case 'png':
				$Files = cls::get('fileman_Download');
    			$filePath = $Files->getDownloadUrl($notConvert['inHnd']);
    			
				$this->handler['zoomitHnd'] = file_get_contents("http://api.zoom.it/v1/content/?url={$filePath}");
				$rec->zoomitHnd = $this->handler['zoomitHnd'];
			break;
			
			default:
				
			break;
		}
		
		docview_Viewer::save($rec);
		@unlink($notConvert['fileName']);
		
    	return $this->handler['zoomitHnd'];
    	
		
	}
    
    
    /**
     * Намира броя на страниците на pdf документа
     */
    function pdfPages($fileName)
    {
		exec("pdfinfo \"{$fileName}\"", $returnedData, $isCorrect);
		
		if (!$isCorrect) {
			$countArray = count($returnedData);
			for ($i = 0; $i < $countArray; $i++) {
				$pos = mb_strripos($returnedData[$i], 'Pages');
				if ($pos !== FALSE) {
					$pattern = '/[^0-9]/';
					$pages = preg_replace($pattern, '', $returnedData[$i]);
				}
			}
		}
		
		return $pages;
    }
    
    
    /**
     * Получава името и пътя от съоветното URL
     */
    function getNameFromLink($url) 
    {
    	$path_parts = pathinfo($url);
		$fileName = $path_parts['basename'];
		$filePath = $url;
    	$script = new fconv_Script(DOCVIEW_TEMP_DIR);
    	$fileName = $script->getUniqName($fileName, $filePath);
    	$names['fileName'] = $fileName;
    	
    	return $names;
    }
    
    
    /**
     * Смъква файла от URL' то във временната директория
     */
    function download($arr)
    {
    	$tpl2 = new ET();
    	$tpl2->content = 'curl "[#url#]" -o "[#fileName#]"';
    	$tpl2->placeObject($arr);
		$v = exec($tpl2, $revValue, $isCorrect);
		
		return;   	
    }
    
    
    /**
     * Вмъква посочения файл във fileman
     */
    function insertFileman($fileName)
    {
    	if (!file_exists($fileName)) {
    		
    		return FALSE;
    	}
    	
    	$fileman = cls::get('fileman_Files');
    	$id = $fileman->addNewFile($fileName, 'Docview', $fileName);
    		
    	return $id;
    	
    	
    }
    
    
    /**
     * Проверява и добавя ново разширение на файла
     */
    function addNewExtension($fileName)
    {
    	$path_parts = pathinfo($fileName);
    	$base_name = $path_parts['basename'];
    	
    	if( ($dotPos = mb_strrpos($base_name, '.')) !== FALSE ) {
            $firstName = mb_substr($base_name, 0, $dotPos);
        } else {
        	$pos = mb_strrpos($base_name, '/');
            $firstName = $base_name;
        }
        $outFileName = $firstName . $this->outExtension;
        $script = new fconv_Script(DOCVIEW_TEMP_DIR);
    	$outFileName = $script->getUniqName($outFileName, DOCVIEW_TEMP_DIR);
        
    	return $outFileName;
    }
    
    /**
     * Определя mime типа на файла, и връща неговото разширение
     */
	function mimeContentType($filename) {

        $mimeTypes = array(

//            'txt' => 'text/plain',
//            'html' => 'text/html',
//            'css' => 'text/css',
//            'js' => 'application/javascript',
//            'json' => 'application/json',
//            'xml' => 'application/xml',
//            'swf' => 'application/x-shockwave-flash',
//            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
//            'gif' => 'image/gif',
//            'bmp' => 'image/bmp',
//            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'svg' => 'image/svg+xml',

            // archives
//            'zip' => 'application/zip',
//            'rar' => 'application/x-rar-compressed',
//            'exe' => 'application/x-msdownload',
 //           'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
 //           'mp3' => 'audio/mpeg',
 //           'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
 //           'psd' => 'image/vnd.adobe.photoshop',
 //           'ps' => 'application/postscript',

            // ms office
//            'doc' => 'application/msword',
//            'rtf' => 'application/rtf',
//            'xls' => 'application/vnd.ms-excel',
//            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
//            'odt' => 'application/vnd.oasis.opendocument.text',
//            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $fileType = exec("file --mime-type \"{$filename}\"");
      	$spacePos = mb_strrpos($fileType, ' ') + 1;
      	$strLen = strlen($fileType);
      	$fileMimeType = mb_substr($fileType, $spacePos);
      	
        $mimeType = array_search($fileMimeType, $mimeTypes);
        
        return $mimeType;
    }
 
    
	/**
     * Изпълнява се след създаването на таблицата
     */
	function on_AfterSetupMVC($mvc, $res)
    {
        if(!is_dir(DOCVIEW_TEMP_DIR)) {
            if( !mkdir(DOCVIEW_TEMP_DIR, 0777, TRUE) ) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . DOCVIEW_TEMP_DIR . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . DOCVIEW_TEMP_DIR . '"</font>';
            }
        } else {
        	$res .= '<li>' . tr('Директорията съществува: ') . ' <font color=black>"' . DOCVIEW_TEMP_DIR . '"</font>';
        }
        
        return $res;
    }
    
}