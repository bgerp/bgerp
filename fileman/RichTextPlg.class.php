<?php



/**
 * Клас 'fileman_RichTextPlg' - Добавя функционалност за поставяне на файлове в type_RichText
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_RichTextPlg extends core_Plugin
{
    
    
    /**
     * Регулярен израз за намиране на файлове в richText
     */
    static $pattern = "/\[file=(?'fileHnd'[a-z0-9]{4,32})\](?'fileName'.*?)\[\/file\]/is";
    
    
    /**
     * Добавя бутон за качване на файлове
     */
    function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        $id = $attr['id'];
        
        if($mvc->params['bucket']) {
            
            $callbackName = 'placeFile_' . $id;
            
            $callback = "function {$callbackName}(fh, fName) { 
                var ta = get$('{$id}');
                rp(\"\\n\" + '[file=' + fh + ']' + fName + '[/file]', ta);
                return true;
            }";
            
            if(Mode::is('screenMode', 'narrow')) {
                $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            } else {
                $args = 'width=400,height=320,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            }
            
            $bucketId = fileman_Buckets::fetchField("#name = '" . $mvc->params['bucket'] . "'", 'id');
            $url = fileman_Files::getUrLForAddFile($bucketId, $callbackName);
            $js = "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
            
            $fileUpload = new ET("<a class=rtbutton title='Прикачен файл' onclick=\"{$js}\">файл</a>");
            
            $fileUpload->appendOnce($callback, 'SCRIPTS');
            
            $toolbarArr->add($fileUpload, 'TBL_GROUP2');
        }
    }
    
    
    /**
     * Обработваме лементите [file=..]...[/file]
     * o [file=fileHandler]upload_name[/file] - хипервръзка сочеща прикачен файл
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
        // Обработваме [file=?????] ... [/file] елементите, които  съдържат връзки към файлове
        $this->mvc = $mvc;
        $html = preg_replace_callback(static::$pattern, array($this, '_catchFile'), $html);
    }
    
    
    /**
     * Заменя елементите [file=?????]......[/link]
     */
    function _catchFile($match)
    {
        $title = $match['fileName'];
        $fh = $match['fileHnd'];
        $place = $this->mvc->getPlace();
        
        if(Mode::is('text', 'plain')) {
            
            //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            //Линк към файла
            $link = toUrl(array('fileman_Download', 'Download', 'fh' => $fh), $isAbsolute);
            
            //Добаваме линка към файла
            $res = "File: $title ( $link )";
        } else {
            $link = fileman_Download::getDownloadLink($fh);
            if (is_object($link)) {
                $content = $link->getContent();
            } else {
                $content = $link;
            }
            $this->mvc->_htmlBoard[$place] = $content;
            $res = "[#{$place}#]";
        }
        
        return  $res;
    }
    
    
    /**
     * Връща линкнатите файлове от RichText-а
     */
    static function getFiles($rt)
    {
        preg_match_all(static::$pattern, $rt, $matches);
        
        $files = array();
        
        if(count($matches['fileHnd'])) {
            foreach($matches['fileHnd'] as $id => $fh) {
                $files[$fh] = strip_tags($matches['fileName'][$id]);
            }
        }

        // Намираме всички линкове, които имат линкове към единичния изглед на файловете
        preg_match_all(type_Richtext::$urlPattern, $rt, $matches);
        
        // Събирме двата масива
        $files += static::getFilesFromUrlMatches($matches);        
        
        return $files;
    }
    
    
    /**
     * Връща масив с файловете
     * 
     * @param array $matches - Масив със съвпаденията
     * 
     * @return $files - Масив с манипулатора на файла и мето му
     */
    static function getFilesFromUrlMatches($matches) 
    {
        // Масива, който ще се връща
        $files = array();
        
        // Обхождаме всички открити резултата
        foreach ((array)$matches[0] as $match) {
            
            // Вземаме URL'то
            $url = rtrim($match, ',.;');
    
            if(!stripos($url, '://') && (stripos($url, 'www.') === 0)) {
                $url = 'http://' . $url;
            }

            // Ескейпваме
            $result = core_Url::escape($url);
    
            // Проверяваме дали е локално
            if( core_Url::isLocal($url, $rest) ) {
                
                // Парсираме URL' то и вземаме параметрите
                $params = type_Richtext::parseInternalUrl($rest);
                
                // Ако е файл от fileman
                if ($params['Ctr'] == 'fileman_files' && $params['Act'] == 'single' && $params['id']) {
                    
                    // Вземаме данните за файла
                    $fRec = fileman_Files::fetchByFh($params['id']);

                    // Добавяме в масивa
                    $files[$fRec->fileHnd] = fileman_Files::getVerbal($fRec, 'name');
                }
            }
        }
        
        return $files;
    }
}