<?php

defIfNot('EF_ALLOWED_DOMAINS', 0);


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
     *  Заглавие на страницата
     */
    var $title = 'Разглеждане на документи';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('url', 'varchar', 'caption=Линк');
        $this->FLD('pdfHnd', 'varchar(8)', 'caption=PDF');
        $this->FLD('pngHnd', 'varchar(8)', 'caption=PNG');
        $this->FLD('zoomitHnd', 'blob(70000)', 'caption=Zoomit');
        
        $this->setDbUnique('url');
    }
    
    
    /**
     * Сваля файла от url' то, което му е подадедено
     * Конвертира файла
     * И показва файла със zoom.it
     */
    function act_Render()
    {
    	$url = Request::get("url");
    	
    	if ((!isset($url)) || (!mb_strlen($url))) {
    		
    		return "Не сте въвели URL.";
    	} 
    	
    	if (!filter_var($url, FILTER_VALIDATE_URL)) {
    		
    		return "Въвели сте грешно URL.";
    	}
    	
    	if (EF_ALLOWED_DOMAINS) {
    		$allowedDomains = arr::Make(EF_ALLOWED_DOMAINS);
    		$parseUrl = URL::parseUrl($url);
    		if (!in_array($parseUrl['domain'], $allowedDomains)) {
    			
    			return "Не е позволено да се разглеждата линкове от този домейн: {$parseUrl['domain']}";
    		}
    	}
    	
    	
    	$tpl2 = new ET();
    	$tpl2->appendOnce("\n".'<meta http-equiv="refresh" content="10">', "HEAD");
    	$tpl2->append('Моля изчакайте...', PAGE_CONTENT);
    	
    	setIfNot($this->tempDir, EF_TEMP_PATH . "/docview/");
    	setIfNot($this->outExtension, ".png");
    	
    	@mkdir($this->tempDir, 0777, TRUE);
    	
    	$rec = self::fetch(array("#url = '[#1#]'", $url));
    	
    	if ($rec) {
    		if (!isset($rec->zoomitHnd)) {
    			
    			return $tpl2;
    		}
    		$obj = json_decode($rec->zoomitHnd);
    		
    		return $obj->embedHtml;
    	}
    	
    	$names = $this->getNameFromLink($url);
    	
    	$arr = array(
    		'url' => $url,
    		'fileName' => $this->tempDir.$names['fileName']
    	);
    	
    	$this->download($arr);
    	$outFileName = $this->tempDir.$this->addNewExtension($arr['fileName']);
    	
    	$rec = new stdClass();
		$rec->url = $url;
		$rec->pdfHnd = $this->handler['pdfHnd'];
		docview_Viewer::save($rec);
		
    	$script = new fconv_Script();
    	$script->setFile('INPUTF', "{$arr['fileName']}");
    	$script->lineExec("gs -sDEVICE=png16m -dGraphicsAlphaBits=4 -dTextAlphaBits=4 -sOutputFile={$outFileName} -dBATCH -r200 -dNOPAUSE [#INPUTF#]");
    	$script->callBack('docview_Viewer::zoomIt');
    	$script->viewerId = $rec->id;
    	$script->outFileName = $outFileName;
    	$script->fileName = $arr['fileName'];
  		$script->run();
  	bp(sbf($script->outFileName, '', TRUE));
  		return $tpl2;
    }
    
    
    /**
     * Получава управлението то callBack' а
     * Вкарва във fileman png картинката и след това изтрива временните файлове
     * Добавя в таблицата информация от zoom.it за съответния линк
     */
    function zoomIt($script)
    {
    	$this->handler['pngHnd'] = $this->insertFileman($script->outFileName);
    	
    	$Files = cls::get('fileman_Files');
    	$filePath = sbf($script->outFileName, '', TRUE);
    	
    	$this->handler['zoomitHnd'] = file_get_contents("http://api.zoom.it/v1/content/?url={$filePath}");
    	
    	@unlink($script->outFileName);
    	@unlink($script->fileName);
    	
    	$rec = new stdClass();
    	$rec->id = $script->viewerId;
    	$rec->pngHnd = $this->handler['pngHnd'];
    	$rec->zoomitHnd = $this->handler['zoomitHnd'];
		docview_Viewer::save($rec);
		
    	return TRUE;
    }
    
    
    /**
     * Получава името и пътя от съоветното URL
     */
    function getNameFromLink($url) 
    {
    	$path_parts = pathinfo($url);
		$fileName = $path_parts['basename'];
		$filePath = $url;
    	$script = new fconv_Script($this->tempDir);
    	$fileName = $script->getUniqName($fileName, $filePath);
    	$names['fileName'] = $fileName;
    	
    	return $names;
    }
    
    
    /**
     * Смъква файла от URL' то във временната директория
     */
    function download($arr)
    {
    	$tpl = new ET();
    	$tpl->content = 'curl "[#url#]" -o "[#fileName#]"';
    	$tpl->placeObject($arr);
		$v = exec($tpl);
		
		$this->handler['pdfHnd'] = $this->insertFileman($arr['fileName']);
			
		return $this->handler;    	
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
        $script = new fconv_Script($this->tempDir);
    	$outFileName = $script->getUniqName($outFileName, $this->tempDir);
        
    	return $outFileName;
    }
    
    
    
    
    
    
}