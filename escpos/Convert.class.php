<?php


/**
 * Конвертор на вътрешен markup към esc/pos команди за отпечатване
 *
 * @category  bgerp
 * @package   escprint
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class escpos_Convert extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Конвертор на вътрешен markup към esc/pos команди за отпечатване';
    
    
    /**
     * 
     */
    public $canAdd = 'no_one';
    
    
    /**
     * 
     */
    public $canDelete = 'no_one';
    
    
    /**
     * 
     */
    public $canEdit = 'no_one';
    
    
    /**
     *
     */
    public $canList = 'no_one';
    


    /**
     * Процесира текст от ПринтМаркъп ESC команди и ascii текст
     */
    public static function process($markup, $driver = 'escpos_driver_Html')
    {
        $driver = cls::get($driver);

        $s = str_replace(array("\n", "\r", "\t"), array(), $markup);
     
        $elArr = explode('<', $s);
        
        $lines = array();
        $i = 0;
        $l = '';
 
        foreach($elArr as $el) {

            $col  = 0;
            $bold    = FALSE;
            $underline = '';
            $font = '';
            $tab  = $driver->getSpace();
            $width = $driver->getWidth();
            
            if(strpos($el, '>') !== FALSE) {
                list($tag, $text) = explode('>', $el);

                $textLen = mb_strlen($text);

                if(strlen($tag)) {
                    $cmd = strtolower($tag{0});
                    $attr = substr($tag, 1);
                    $attrArr = explode(' ', trim($attr));
                    foreach($attrArr as $a) {

                        if(!trim($a)) continue;

                        if(is_numeric($a)) {
                            $col = (int) $a;
                            continue;
                        }

                        if($a == 'b' || $a == 'B') {
                            $bold = TRUE;
                            continue;
                        }

                        if($a == 'u' ) {
                            $underline = 1;
                            continue;
                        }

                        if($a == 'U' ) {
                            $underline = 2;
                            continue;
                        }
                        
                        if($a == 'F' || $a == 'f') {
                            $font = $a;
                            $width = $driver->getWidth($font);
                            continue;
                        }

                        if($a == '.' || $a == '_' || $a == '=' || $a == '-') {
                            $tab = $a;
                            continue;
                        }

                        expect(FALSE, "Непознат атрибут", $a, $el);
                    }

                    $fontTxt = $driver->getFont($font, $bold, $underline);
                    $fontPad = $driver->getFont($font, $bold, FALSE);

                    $fontEnd = $driver->getFontEnd();
                    $newLine = $driver->GetNewLine();
                    
                    $text = self::hyphenText($text, $newLine, $width);
                    $textArr = explode($newLine, $text);
                    
                    foreach ($textArr as $text) {
                        $textLen = mb_strlen($text);
                        switch($cmd) {
                            // Нова линия
                            case 'p':
                                $res .= $l;
                                // Код за преместване на хартията
                                $l = $newLine . $fontTxt . $driver->encode($text) . $fontEnd;
                                $lLen = mb_strlen($text);
                                break;
                            case 'c':
                                $res .= $l;
                                // Код за преместване на хартията
                                $r = (int) (($width-$textLen)/2);
    							
                                $r = max($r, 0);
                                if($r) {
                                    $pad = str_repeat($tab , $r);
                                } else {
                                    $pad = '';
                                }
                                $l = $newLine . $fontPad . $pad . $fontTxt . $driver->encode($text) .$fontEnd;
                                $lLen = $r + $textLen;
                                break;
                            case 'l':
                                $r = $col - $lLen;
                                $r = max($r, 0);
                                if($r) {
                                    $pad = str_repeat($tab , $r);
                                } else {
                                    $pad = '';
                                }
    							
                                $l .=  $fontPad . $pad . $fontTxt .  $driver->encode($text) . $fontEnd;
                                $lLen += $r + $textLen;
                                break;
    							
                            case 'r':
                                $r = $col - $lLen - $textLen;
    							
                                $r = max($r, 0);
                                if($r) {
                                    $pad = str_repeat($tab , $r);
                                } else {
                                    $pad = '';
                                }
                                $l .= $fontPad . $pad . $fontTxt .  $driver->encode($text) . $fontEnd;
                                $lLen = $r + $textLen;
                                break;
                            default:
                                expect(FALSE, "Непозната команда", $cmd, $el);
    						
                        }
                    }
                    
                }
            } else {
                $l .= $el;
            }
        }

        $res .= $l;

        return $res;
    }
    
    
    /**
     * 
     * @param unknown $tpl
     * @param string $driver
     * 
     * @return string
     */
    public static function getContent($tpl, $driver = 'escpos_driver_Html')
    {
        if (!($tpl instanceof core_ET)) {
            $tpl = new ET($tpl);
        }
        
        $driver = cls::get($driver);
        
        $driver->placePrintData($tpl);
        
        $content = $tpl->getContent();
        
        return $content;
    }
    
    
    /**
     * 
     * @param string $text
     * @param string $nl
     * @param integer $lineMaxLen
     * 
     * @return string
     */
    public static function hyphenText($text, $nl, $lineMaxLen)
    {
        if (!trim($text)) return $text;
        
        $textLen = mb_strlen($text);
        
        if ($textLen <= $lineMaxLen) return $text;
        
        $delimiter = ' ';
        
        $lastSpacePos = $bestLastSpacePos = mb_strrpos($text, $delimiter);
        
        // Правим опит да намерим най-добрия разделител
        if ($bestLastSpacePos !== FALSE) {
            $cnt = 0;
            $lText = $text;
            $l = 0;
            while (TRUE) {
                
                // Ако лявата част е по-малка от максимума, няма смисъл повече да се режи
                $lText = mb_substr($text, 0, $bestLastSpacePos);
                if ((mb_strlen($lText) <= $lineMaxLen) && ($l++ == 1)) break;
                
                // Определяме нова най-добра позиция
                $newBestLastSpacePos = mb_strrpos($lText, $delimiter);
                
                if ($newBestLastSpacePos === FALSE) break;
                
                $lText = mb_substr($text, 0, $newBestLastSpacePos);
                $rText = mb_substr($text, $newBestLastSpacePos+1);
                
                // Ако дясната част става по-дълга от лявата, пак прекъсваме
//                 if (mb_strlen($rText) > mb_strlen($lText)) break;
                
                // Ако дясната част е по-голяма от максималната дължина, пак се прекъсва
                if (mb_strlen($rText) > $lineMaxLen) break;
                
                // Промянеме най-добрата позиция
                $bestLastSpacePos = $newBestLastSpacePos;
                
                if ($cnt++ > 20) break;
            }
            
            $lastSpacePos = $bestLastSpacePos;
        }
        
        if ($lastSpacePos !== FALSE) {
            
            $lText = mb_substr($text, 0, $lastSpacePos);
            $rText = mb_substr($text, $lastSpacePos+1);
            
            $lText = self::hyphenText($lText, $nl, $lineMaxLen);
            $rText = self::hyphenText($rText, $nl, $lineMaxLen);
            
            $text = $lText . $nl . $rText;
        } else {
            // Ако е дълъг стринг, без прекъсване и само 1-2 символа ще се пренасят, тогава го разделяме на две
            if ($textLen <= ($lineMaxLen + 2)) {
                $halfLen = ceil($lineMaxLen / 2);
                
                $lText = mb_strcut($text, 0, $halfLen);
                $rText = mb_strcut($text, $halfLen);
                
                $lText = self::hyphenText($lText, $nl, $lineMaxLen);
                $rText = self::hyphenText($rText, $nl, $lineMaxLen);
                
                $text = $lText . $nl . $rText;
            }
        }
        
        return $text;
    }
    
    
    /**
     * Тестване на печата
     */
    function act_Test()
    {
        $test = "<c F b>Фактура №123/28.02.17" .
        "<p><r32 =>" .
        "<p b>1.<l3 b>Кисело мляко" .
        "<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
        "<p b>2.<l3 b>Хляб \"Добруджа\"" . "<l f> | годност: 03.03" .
        "<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
        "<p b>3.<l3 b>Минерална вода" .
        "<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
        "<p><r32 =>" .
        "<p><r29 F b>Общо: 34.23 лв.";

        if (Request::get('p')) {
            $res = self::process($test, 'escpos_driver_Ddp250');
            echo $res;
            shutdown();
        }

        return self::process($test);
    }
}
