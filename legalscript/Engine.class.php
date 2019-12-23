<?php


/**
 * Клас 'legalscript_Engine' - Генериране на юридически текст по шаблон
 *
 *
 * @category  vendors
 * @package   legalscript
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class legalscript_Engine extends core_BaseClass
{
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        
        if ($this->path) {
            $this->script = file_get_contents(EF_APP_PATH . '/' . $this->path);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function render_($rec)
    {
        $lines = explode("\n", $this->script);
        
        $pFlag = true;     // Дали следващата линия да започва с параграф
        // Броячи (номератори) на нивата на влагане на параграфите
        // 0 - Без номериране
        // 1 - Раздел, не нулира Чл.
        // 2 - Чл. # - Член
        // 3 -   #) - Алинея
        // 4 -     а. - Буква
        // 5 -       - - Тере
        $pLevels = array(0, 0, 0, 0, 0, 0);
        
        $bgAlpha = ' абвгдежзийклмнопрстуфхцчшщъьюя';
        
        // Ниво на влагане за следващата линия
        $level = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                $pFlag = true;
                continue;
            }
            
            $words = explode(' ', $line);
            
            $w0 = $words[0];
            
            if (isset($words[1])) {
                $w1 = $words[1];
            }
            
            $label = false;
            
            if ($w0 == '#' || $w0 == '##' || $w0 == '###' || $w0 == '####' || $w0 == '#####') {
                $level = strlen($w0);
                
                unset($words[0]);
                
                if (substr($w1, 0, 2) == '::') {
                    $label = substr($w1, 2);
                    unset($words[1]);
                }
            } else {
                $level = 0;
            }
            
            $line = new ET(implode(' ', $words));
            
            $line->placeObject($rec);
            
            $line = $line->getContent(null, 'CONTENT', false, false);
            
            if (strpos($line, '[#') !== false) {
                $out[] = ($pFlag ? '<p>' : '') . "<span style='color:#ffcccc'>" . $line . '</span>';
                $pFlag = false;
                continue;
            }
            
            switch ($level) {
                case 0:
                    $prefix = '';
                    break;
                case 1:
                    $pLevels[$level]++;
                    $prefix = '<b>' . $this->numberToRoman($pLevels[1]) . '.</b>&nbsp;';
                    $pFlag = true;
                    break;
                case 2:
                    $pLevels[$level]++;
                    $pLevels[$level + 1] = $pLevels[$level + 2] = $pLevels[$level + 3] = 0;
                    $prefix = '<b>Чл.' . $pLevels[$level] . '.</b>&nbsp;';
                    $pFlag = true;
                    break;
                case 3:
                    $pLevels[$level]++;
                    $pLevels[$level + 1] = $pLevels[$level + 2] = 0;
                    $prefix = '&nbsp;&nbsp;<b>' . $pLevels[$level] . ')</b>&nbsp;';
                    $pFlag = true;
                    break;
                case 4:
                    $pLevels[$level]++;
                    $pLevels[$level + 1] = 0;
                    $prefix = '&nbsp;&nbsp;&nbsp;&nbsp;<b>' . mb_substr($bgAlpha, $pLevels[$level], 1) . '.</b>&nbsp;';
                    $pFlag = true;
                    break;
                case 5:
                    $pLevels[$level]++;
                    $prefix = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>-</b>&nbsp;';
                    $pFlag = true;
                    break;
            }
            
            if ($pFlag) {
                $prefix = "<p class='level-" . $level . "'>" . $prefix;
            }
            
            $pFlag = false;
            
            $line = $prefix . $line;
            
            $out[] = $line;
        }
        
        $html = implode("\n", $out);
        
        $tpl = new ET("<div class='legalscript'>{$html}</div>");
        
        $tpl->appendOnce('
           .legalscript p {
               font-size:1.05em;
               line-height:1.4em;
            }
           .legalscript .level-1 {
               font-weight:bold;
            }
           .legalscript .level-2 {
               margin-bottom: 2px;
               margin-top: 10px;
            }
           .legalscript .level-3 {
               margin-bottom: 2px;
               margin-top: 8px;
            }
            .legalscript .level-4 {
               margin-bottom: 2px;
               margin-top: 6px;
            }
            .legalscript .level-5 {
               margin-bottom: 2px;
               margin-top: 4px;
            }
            .legalscript h1 {
                font-size:1.5em;
            }
        ', 'STYLES');
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function numberToRoman($num, $type = 1)
    {
        if ($type == 1) {
            //upper character number
            // Make sure that we only use the integer portion of the value
            
            $n = intval($num);
            $result = '';
            
            // Declare a lookup array that we will use to traverse the number:
            $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
                'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
                'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
            
            foreach ($lookup as $roman => $value) {
                // Determine the number of matches
                $matches = intval($n / $value);
                
                // Store that many characters
                $result .= str_repeat($roman, $matches);
                
                // Substract that from the number
                $n = $n % $value;
            }
            
            // The Roman numeral should be built, return it
            return $result;
        } elseif ($type == 2) { //low character number
            
            // Make sure that we only use the integer portion of the value
            $n = intval($num);
            $result = '';
            
            // Declare a lookup array that we will use to traverse the number:
            
            $lookup = array('m' => 1000, 'cm' => 900, 'd' => 500, 'cd' => 400,
                'c' => 100, 'xc' => 90, 'l' => 50, 'xl' => 40,
                'x' => 10, 'ix' => 9, 'v' => 5, 'iv' => 4, 'i' => 1);
            
            foreach ($lookup as $roman => $value) {
                // Determine the number of matches
                $matches = intval($n / $value);
                
                // Store that many characters
                $result .= str_repeat($roman, $matches);
                
                // Substract that from the number
                $n = $n % $value;
            }
            
            // The Roman numeral should be built, return it
            return $result;
        }
    }
}
