<?php



/**
 * Клас  'lib_Diff' - Визуализира разликите между две версии на HTML
 *
 *
 * @category  ef
 * @package   lib
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class lib_Diff {
    
    /**
     * От стара и нова версия на HTML, генерира изглед с оцветветени разлики между тях
     *
     * @param old string star HTML
     * @param new string нов HTML
     */
    static function getDiff(
        $old, $new, 
        $insL = '<span class="ins">', $insR = '</span>', 
        $delL = '<span class="del">', $delR = '</span>', 
        $cngL = '<span title="#" class="cng">', $cngR = '</span>')
    {   

        $oldArr  = self::explodeHtml($old);
        $newArr  = self::explodeHtml($new);
        $arrDiff = self::getArrDiff($oldArr, $newArr);

        $out = $mode = $buf = '';

        foreach($arrDiff as $e) {
            
            // Текст
            if(is_string($e)) {
                $out = new stdClass();
                $out->mode = 't';
                $out->str  = $e;
                $res[] = $out;
                continue;
            }
            
            // Замяна
            if(count($e['d']) && count($e['i'])) {
                $deleted = self::getTextFromArray($e['d']);
                foreach($e['i'] as $i) {
                    $out = new stdClass();
                    if($i{0} == '<') {
                        $out->mode = 't';
                        $out->str  = $i;
                    } else {
                        $out->mode = 'c';
                        $out->str  = $i;
                        $out->del = $deleted;
                    }
                    $res[] = $out;
                    continue;
                }
                continue;
            }
            
            // Изтриване
            if(count($e['d'])) {
                foreach($e['d'] as $d) {
                    $out = new stdClass();
                    if($d{0} == '<') {
                        continue;
                    } else {
                        $out->mode = 'd';
                        $out->str  = $d;
                    }
                    $res[] = $out;
                    continue;
                }
                continue;
            }
            
            // Добавяне
            if(count($e['i'])) {
                foreach($e['i'] as $i) {
                    $out = new stdClass();
                    if($d{0} == '<') {
                        $out->mode = 't';
                        $out->str  = $i;
                    } else {
                        $out->mode = 'i';
                        $out->str  = $i;
                    }
                    $res[] = $out;
                    continue;
                }
                continue;
            }
        }
        
        $mode = 't'; $out = '';
        $res[] = (object) array('str' => '', 'mode' => '');

        foreach($res as $s) {
            if($mode != $s->mode) {
                if($mode == 'd') {
                    $out .= $delR;
                }
                if($mode == 'i') {
                    $out .= $insR;
                }
                if($mode == 'c') {
                    $out .= $cngR;
                }
                if($s->mode == 'd') {
                    $out .= $delL;
                }
                if($s->mode == 'i') {
                    $out .= $insL;
                }
                if($s->mode == 'c') {
                    $out .= str_replace('#', $s->del, $cngL);
                }
            }
            if(substr($out, -1) == '>' && substr($s->str, 0, 1) == ' ') {
                $s->str = '&nbsp;' . substr($s->str, 1);
            }
            $out .= $s->str;
            $mode = $s->mode;
        }

        return $out;
    }
    
    
    /**
     * Връща конкатенация на елементите на масив,
     * с изключение на HTML таговете
     */
    private static function getTextFromArray($arr)
    {
        $out = '';

        foreach($arr as $e) {
            if($e{0} != '<') {
                $out .= $e;
            }
        }

        return htmlentities($out, ENT_QUOTES, 'UTF-8');
    }


    /**
     * Разбива HTML на масив от думи, 
     */
    private static function explodeHtml($html)
    {
        $i = 0;
        $mode = 'text';
        $out = '';   
        while('' != ($c = str::nextChar($html, $i))) { 
            if($mode == 'tag') {
                $out .= $c;
                if($c == '>') {
                    $res[] = $out;
                    $out = '';
                    $mode = 'text';
                }
                
                continue;
            } elseif($c == '<') {
                if($out) {
                    $res[] = $out;
                }
                $out = $c;
                $mode = 'tag';
                continue;
            }
 
            $wsC = self::isDevider($c);
            $wsO = self::isDevider($out);

            if(($wsC && !$wsO) || (!$wsC && $wsO)) {
                $res[] = $out;
                $out = '';
            }

            $out .= $c;
        }

        return $res;
    }


    /**
     * Дали символа е разделите или интервал
     */
    private static function isDevider($c)
    {
        for($i = 0; $i < strlen($c); $i++) {
            if(!ctype_space($c{$i}) && strpos(",:;-!", $c{$i}) === FALSE) {

                return FALSE;
            }
        }

        return TRUE;
    }


    /**
     * Определяне на разликата между два масива
     */
    private static function getArrDiff($old, $new)
    {
        $matrix = array();
        $maxlen = 0;
        foreach($old as $oindex => $ovalue){
            $nkeys = array_keys($new, $ovalue);
            foreach($nkeys as $nindex){
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if($matrix[$oindex][$nindex] > $maxlen){
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
        return array_merge(
            self::getArrDiff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            self::getArrDiff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
    }

 }