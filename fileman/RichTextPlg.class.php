<?php


/**
 * Клас 'fileman_RichTextPlg' - Добавя функционалност за поставяне на файлове в type_Richtext
 *
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_RichTextPlg extends core_Plugin
{
    /**
     * Регулярен израз за намиране на файлове в richText
     */
    public static $pattern = "/\[file=(?'fileHnd'[a-z0-9]{4,32})\](?'fileName'.*?)\[\/file\]/is";
    
    
    /**
     * Добавя бутон за качване на файлове
     */
    public function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        $id = $attr['id'];
        
        if ($mvc->params['bucket']) {
            $windowName = $callbackName = 'placeFile_' . $id;
            
            $callback = "function {$callbackName}(fh, fName) { 
                var ta = get$('{$id}');
                rp('[file=' + fh + ']' + fName + '[/file]', ta, 1);
                
                return true;
            }";
            
            if (Mode::is('screenMode', 'narrow')) {
                $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            } else {
                $args = 'width=400,height=530,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            }
            
            $bucketId = fileman_Buckets::fetchField("#name = '" . $mvc->params['bucket'] . "'", 'id');
            $url = fileman_Files::getUrLForAddFile($bucketId, $callbackName);
            $js = "localStorage.removeItem('disabledRowArr'); openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
            
            // Ако е регистриран потребител
            if (haveRole('user')) {
                $placeHolder = 'filesAndDoc';
                $btnTitle = 'Файл';
            } else {
                $placeHolder = 'simpleToolbar';
                $btnTitle = 'Прикачи';
            }
            
            $fileUpload = new ET("<a class=rtbutton title='" . tr('Прикачване на файл') . "' onclick=\"{$js}\">" . tr($btnTitle) . '</a>');
            
            $fileUpload->appendOnce($callback, 'SCRIPTS');

//            $toolbarArr->add($fileUpload, 'TBL_GROUP2');
            
            // Добавяне в групата за добавяне на файлове
            $toolbarArr->add($fileUpload, $placeHolder, 1000.345);
        }
    }
    
    
    /**
     * Обработваме лементите [file=..]...[/file]
     * o [file=fileHandler]upload_name[/file] - хипервръзка сочеща прикачен файл
     */
    public function on_AfterCatchRichElements($mvc, &$html)
    {
        // Обработваме [file=?????] ... [/file] елементите, които  съдържат връзки към файлове
        $this->mvc = $mvc;
        $html = preg_replace_callback(static::$pattern, array($this, '_catchFile'), $html);
    }
    
    
    /**
     * Заменя елементите [file=?????]......[/link]
     */
    public function _catchFile($match)
    {
        $title = $match['fileName'];
        $fh = $match['fileHnd'];
        $place = $this->mvc->getPlace();
        
        $link = fileman_Files::getLink($fh, $title);
        
        if ($link) {
            if (is_object($link)) {
                $content = $link->getContent();
            } else {
                $content = $link;
            }
        } else {
            $content = $this->mvc->getNotAccessMsg();
        }
        
        $this->mvc->_htmlBoard[$place] = $content;
        $res = "[#{$place}#]";
        
        return  $res;
    }
    
    
    /**
     * Съобщението, което ще се показва ако нямаме достъп до обекта
     */
    public static function on_AfterGetNotAccessMsg($mvc, $res)
    {
        $res = tr('Липсващ обект');
    }
    
    
    /**
     * Връща линкнатите файлове от RichText-а
     */
    public static function getFiles($rt)
    {
        preg_match_all(static::$pattern, $rt, $matches);
        
        $files = array();
        
        if (count($matches['fileHnd'])) {
            foreach ($matches['fileHnd'] as $id => $fh) {
                $files[$fh] = strip_tags($matches['fileName'][$id]);
            }
        }
        
        // Намираме всички линкове, които имат линкове към единичния изглед на файловете
        preg_match_all(type_Richtext::URL_PATTERN, $rt, $matches);
        
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
    public static function getFilesFromUrlMatches($matches)
    {
        // Масива, който ще се връща
        $files = array();
        
        // Обхождаме всички открити резултата
        foreach ((array) $matches[0] as $match) {
            
            // Вземаме URL'то
            $url = rtrim($match, ',.;');
            
            if (!stripos($url, '://') && (stripos($url, 'www.') === 0)) {
                $url = 'http://' . $url;
            }
            
            // Ескейпваме
            $result = core_Url::escape($url);
            
            // Проверяваме дали е локално
            if (core_Url::isLocal($url, $rest)) {
                
                // Парсираме URL' то и вземаме параметрите
                $params = type_Richtext::parseInternalUrl($rest);
                
                // Ако е файл от fileman
                if (($params !== false) && (strtolower($params['Ctr']) == 'fileman_files' && strtolower($params['Act']) == 'single' && $params['id'])) {
                    
                    // Ако е id
                    if (is_numeric($params['id'])) {
                        
                        // Вземаме данните за файла
                        $fRec = fileman_Files::fetch($params['id']);
                        
                        // Добавяме в масива
                        $files[$fRec->fileHnd] = fileman_Files::getVerbal($fRec, 'name');
                    }
                }
            }
        }
        
        return $files;
    }
}
