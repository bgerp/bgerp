<?php


/**
 * Макимално разрешения брой елементи в масива за разликите
 * При по - големи стойности има риск да свършви паметта
 */
defIfNot('EF_LIB_DIFF_MAX_STACK_COUNT', 1150);


/**
 * Клас  'lib_Diff' - Визуализира разликите между две версии на HTML
 *
 *
 * @category  ef
 * @package   lib
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class lib_Diff
{
    /**
     * Списък с препинателни знаци, ескейпнати за регулярен израз
     */
    const PUNCTUATION = '\\.\\,\\?\\-\\!';
    
    
    /**
     * От стара и нова версия на HTML, генерира изглед с оцветветени разлики между тях
     *
     * @param old string star HTML
     * @param new string нов HTML
     */
    public static function getDiff(
        $old,
        $new,
        $insL = '<span class="ins">',
        $insR = '</span>',
        $delL = '<span class="del">',
        $delR = '</span>',
        $cngL = '<span title="#" class="cng">',
        $cngR = '</span>'
    ) {
        
        // Ако няма промени, няма смисъл от обработка
        if ($old == $new) {
            
            return (string) $new;
        }
        
        $oldArr = self::explodeHtml($old);
        $newArr = self::explodeHtml($new);
        $arrDiff = self::ses($oldArr, $newArr);
        
        // Ако процеса за открираване на разлики е спрял принудително
        if ($arrDiff === false) {
            
            // Връщаме предупреждение и най - новата версия
            return "<div class='formError' style='color:red;'>" . tr('Внимание! Има много разлики и не може да се изчислят.') . '</div>' . $new;
        }
        
        $out = $mode = $buf = '';
        
        foreach ($arrDiff as $e) {
            
            // Текст
            if (is_string($e)) {
                $out = new stdClass();
                $out->mode = 't';
                $out->str = $e;
                $res[] = $out;
                continue;
            }
            
            // Замяна
            while (count($e['d']) && count($e['i']) && (($ct = self::getCharType($e['d'])) == self::getCharType($e['i']))) {
                $kd = key($e['d']);
                $ki = key($e['i']);
                
                $out = new stdClass();
                if ($ct == 'tag') {
                    $out->mode = 't';
                    $out->str = $e['i'][$ki];
                } else {
                    $out->mode = 'c';
                    $out->str = $e['i'][$ki];
                    $out->del = $e['d'][$kd];
                }
                $res[] = $out;
                unset($e['d'][$kd], $e['i'][$ki]);
                
                $last = count($res) - 1;
                
                if (($last >= 2) && $res[$last]->mode == 'c' && $res[$last - 1]->mode == 't' && $res[$last - 2]->mode == 'c') {
                    $res[$last - 2]->str .= $res[$last - 1]->str . $res[$last]->str;
                    $res[$last - 2]->del .= $res[$last - 1]->str . $res[$last]->del;
                    unset($res[$last], $res[$last - 1]);
                }
            }
            
            
            // Изтриване
            if (count($e['d'])) {
                foreach ($e['d'] as $d) {
                    $out = new stdClass();
                    if ($d{0} == '<') {
                        continue;
                    }
                    $out->mode = 'd';
                    $out->str = $d;
                    
                    $res[] = $out;
                    continue;
                }
            }
            
            // Добавяне
            if (count($e['i'])) {
                foreach ($e['i'] as $i) {
                    $out = new stdClass();
                    if ($i{0} == '<') {
                        $out->mode = 't';
                        $out->str = $i;
                    } else {
                        $out->mode = 'i';
                        $out->str = $i;
                    }
                    $res[] = $out;
                    continue;
                }
            }
        }
        
        $mode = 't';
        $out = '';
        $res[] = (object) array('str' => '', 'mode' => '');
        
        foreach ($res as $s) {
            if ($mode != $s->mode) {
                if ($mode == 'd') {
                    $out .= $delR;
                }
                if ($mode == 'i') {
                    $out .= $insR;
                }
                if ($mode == 'c') {
                    $out .= $cngR;
                }
                if ($s->mode == 'd') {
                    $out .= $delL;
                }
                if ($s->mode == 'i') {
                    $out .= $insL;
                }
                if ($s->mode == 'c') {
                    $out .= str_replace('#', $s->del, $cngL);
                }
            }
            if (substr($out, -1) == '>' && substr($s->str, 0, 1) == ' ') {
                $s->str = '&nbsp;' . substr($s->str, 1);
            }
            $out .= $s->str;
            $mode = $s->mode;
        }
        
        return $out;
    }
    
    
    /**
     * Връща типа на знака
     */
    public static function getCharType($c)
    {
        if (is_array($c)) {
            $c = reset($c);
        }
        
        $c = mb_substr($c, 0, 1);
        
        if (preg_match("/[\s]+/", $c)) {
            
            return 'ws';
        } elseif (preg_match('/[' . self::PUNCTUATION . ']+/', $c)) {
            
            return 'dev';
        } elseif ($c == '<') {
            
            return 'tag';
        }
        
        return 'text';
    }
    
    
    /**
     * Връща конкатенация на елементите на масив,
     * с изключение на HTML таговете
     */
    private static function getTextFromArray($arr)
    {
        $out = '';
        
        foreach ($arr as $e) {
            if ($e{0} != '<') {
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
        $ptr = '/(<[^>]*>|[\\s]+|[' . self::PUNCTUATION . ']+|[^\\s' . self::PUNCTUATION . '\\<]+)/';
        
        preg_match_all($ptr, $html, $matches);
        
        return $matches[0];
    }
    
    
    /**
     * Намиране на най-краткия скрипт за редактиране (SES) чрез бърз алгоритъм от книгата
     * "An O(ND) Difference Algorithm and Its Variations" by Eugene W.Myers, 1986.
     *
     *
     * @param array $src Оригинален масив
     * @param array $dst Нов Масив
     *
     * @return array
     */
    public static function ses($src, $dst)
    {
        $cx = count($src);
        $cy = count($dst);
        
        $stack = array();
        $V = array(1 => 0);
        $end_reached = false;
        
        # Find LCS length
        for ($D = 0; $D < $cx + $cy + 1 && !$end_reached; $D++) {
            for ($k = -$D; $k <= $D; $k += 2) {
                $x = ($k == -$D || $k != $D && $V[$k - 1] < $V[$k + 1])
                    ? $V[$k + 1] : $V[$k - 1] + 1;
                $y = $x - $k;
                
                while ($x < $cx && $y < $cy && $src[$x] == $dst[$y]) {
                    $x++;
                    $y++;
                }
                
                $V[$k] = $x;
                
                if ($x == $cx && $y == $cy) {
                    $end_reached = true;
                    break;
                }
            }
            
            $stack[] = $V;
            
            // Ако броя на разликите е над допустимото, връщаме FALSE
            if (count($stack) > EF_LIB_DIFF_MAX_STACK_COUNT) {
                
                return false;
            }
        }
        $D--;
        
        # Recover edit path
        $res = array();
        for ($D = $D; $D > 0; $D--) {
            $V = array_pop($stack);
            $cx = $x;
            $cy = $y;
            
            # Try right diagonal
            $k++;
            $x = $V[$k];
            $y = $x - $k;
            $y++;
            
            while ($x < $cx && $y < $cy
            && isset($src[$x], $dst[$y]) && $src[$x] == $dst[$y]) {
                $x++;
                $y++;
            }
            
            if ($x == $cx && $y == $cy) {
                $x = $V[$k];
                $y = $x - $k;
                
                $res[] = array('i',$x,$y);
                continue;
            }
            
            # Right diagonal wasn't the solution, use left diagonal
            $k -= 2;
            $x = $V[$k];
            $y = $x - $k;
            $res[] = array('d',$x,$y);
        }
        
        $res = array_reverse($res);
        
        // Указател към не-сортирания резултат
        $p = 0;
        
        // Масив за форматирания резултат
        $r = array();
        
        // Хак за да работи следния случай:
        // lib_Diff::getDiff('Ново ново', 'Ново ново добавено ново');
        $src[] = '';
        
        // Подготовка на форматирания резултат
        foreach ($src as $i => $el) {
            if (($o = $res[$p][0]) && ($res[$p][1] == $i)) {
                $li = null;
                $flag = false;
                
                while (($res[$p][1] == $i) && ($res[$p] !== null)) {
                    if (!isset($li)) {
                        $li = count($r) - 1;
                        if (!is_array($r[$li])) {
                            $li++;
                            $r[$li] = array();
                        }
                    }
                    
                    if ($o == 'd') {
                        $r[$li]['d'][] = $src[$res[$p][1]];
                    } else {
                        expect($o == 'i');
                        $r[$li]['i'][] = $dst[$res[$p][2]];
                        if ($res[$p][0] != 'd' && !$flag) {
                            $r[] = $el;
                            $flag = true;
                        }
                    }
                    
                    $p++;
                }
            } else {
                $r[] = $el;
            }
        }
        
        return $r;
    }
}
