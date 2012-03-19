<?php



/**
 * Клас 'fileman_RichTextPlg' - Добавя функционалност за поставяне на файлове в type_RichText
 *
 *
 * @category  all
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_RichTextPlg extends core_Plugin {

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
        $html = preg_replace_callback("/\[file(=([a-z0-9]{4,32})|)\](.*?)\[\/file\]/is", array($this, '_catchFile'), $html);

    }
    
    /**
     * Заменя елементите [file=?????]......[/link]
     */
    function _catchFile($match)
    {
        $title = $match[3];
        $fh = $match[2];
        $place = $this->mvc->getPlace();

        if(Mode::is('text', 'plain')) {
            $res = "File: $title";
        } else {
            $link = fileman_Download::getDownloadLink($fh, 'absolute');
            $this->mvc->_htmlBoard[$place] = $link->getContent();
            $res = "__{$place}__";
        }

        return  $res;
    }

  
    /**
     * Връща линкнатите файлове от RichText-а
     */
    function getFiles($rt)
    {
        preg_match_all("/\[file=([A-Za-z0-9]*)\](.*?)\[\/file\]/i", $rt, $matches);
        
        $files = array();
        
        if(count($matches[1])) {
            foreach($matches[1] as $id => $fh) {
                $files[$fh] = strip_tags($matches[2][$id]);
            }
        }
        
        return $files;
    }
}