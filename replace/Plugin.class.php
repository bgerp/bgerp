<?php
/**
 * Плъгин за заместване на парчета текст в type_Richtext
 *
 *
 * @category  vendors
 * @package   oembed
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class replace_Plugin extends core_Plugin
{
    public function on_BeforeCatchRichElements($mvc, &$html)
    {
        $matches = array();
        
        if (preg_match("/^\#replace\s(.+)/iu", $html, $matches)) {
            if ($matches[1]) {
                $groups = $matches[1];
                $html = substr($html, strlen($matches[0]) + 1);
                $replace = replace_Dictionary::getTexts($groups);
                if (is_array($replace)) {
                    $rand = Mode::getProcessKey();
                    foreach ($replace as $from => $to) {
                        $fromArr[] = $from;
                        $toArr[] = $to;
                        $midArr[] = '{' . $rand . count($toArr) . '}';
                    }
                    
                    if (is_array($fromArr)) {
                        $html = str_replace($fromArr, $midArr, $html);
                        $html = str_replace($midArr, $toArr, $html);
                    }
                }
            }
        }
    }
}
