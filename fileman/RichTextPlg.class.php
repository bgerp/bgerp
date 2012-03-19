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