<?php



/**
 * Родител на обекти за работа с цветове
 *
 *
 * @category  vendors
 * @package   color
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class color_Object {
    
    
    /**
     * @todo Чака за документация...
     */
    var $r;
    
    
    /**
     * @todo Чака за документация...
     */
    var $g;
    
    
    /**
     * @todo Чака за документация...
     */
    var $b;
    
    
    /**
     * Конструктор на обекта
     */
    function __construct($value, $g = NULL, $b = NULL)
    {
        $this->value = $value;
        
        // Поддържани формати
        // #39c
        // #3399cc
        // 3399cc
        // 39c
        // 123,230,120 (RGB)
        // 123,120,100,99  (CMYK)
        // PMS 485C
        $value = trim(strtolower($value));
    
        if(is_numeric($value) && is_numeric($g) && is_numeric($b)) {  
            $this->r = $value;
            $this->g = $g;
            $this->b = $b;
            
            return;
        }
       
        if($this->hexToRgb($value, $this->r, $this->g, $this->b)) { 
            return;
        }
        
        if($hexColor = $this->getNamedColor($value)) { 
            if($this->hexToRgb($hexColor, $this->r, $this->g, $this->b)) {
                return;
            }
        }
        
        $this->error = "Непознат цвят|* {$value}";
    }
    
    
    /**
     * Преобразува hex цвят към RGB
     */
    static function hexToRgb($hexColor, &$r, &$g, &$b)
    {
        if($hexColor{0} == '#') $hexColor = substr($hexColor, 1);
        
        if(preg_match("/[0-9a-f]{3}([0-9a-f]{3})/i", $hexColor) || preg_match("/[0-9a-f]{3}/i", $hexColor)) {
            if(strlen($hexColor) == 3) {
                $r = hexdec($hexColor{0} . $hexColor{0});
                $g = hexdec($hexColor{1} . $hexColor{1});
                $b = hexdec($hexColor{2} . $hexColor{2});
            } elseif(strlen($hexColor) == 6) {
                $r = hexdec($hexColor{0} . $hexColor{1});
                $g = hexdec($hexColor{2} . $hexColor{3});
                $b = hexdec($hexColor{4} . $hexColor{5});
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function randInit($min = 0, $max = 255)
    {
        $this->r = rand(0, 255);
        $this->g = rand(0, 255);
        $this->b = rand(0, 255);
    }
    
    
    /**
     * Намира дистанцията между между текущия и посочения цвят
     */
    function getDistance($color)
    {
        if(is_scalar($color)) {
            $color = new color_Object($color);
        }
        
        return sqrt(($color->r - $this->r) * ($color->r - $this->r) +
            ($color->g - $this->g) * ($color->g - $this->g) +
            ($color->b - $this->b) * ($color->b - $this->b));
    }
    
    
    /**
     * Връща hex представянето на цвета
     */
    function getHex($prefix = '#')
    {
        $r = $this->r;
        $g = $this->g;
        $b = $this->b;
        
        $r = dechex($r<0 ? 0 : ($r>255 ? 255 : $r));
        $g = dechex($g<0 ? 0 : ($g>255 ? 255 : $g));
        $b = dechex($b<0 ? 0 : ($b>255 ? 255 : $b));
        
        $color = (strlen($r) < 2 ? '0' : '') . $r;
        $color .= (strlen($g) < 2 ? '0' : '') . $g;
        $color .= (strlen($b) < 2 ? '0' : '') . $b;
        
        return $prefix . $color;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function resize($rate, $add = 0)
    {
        $this->r = min(round($this->r * $rate + $add), 255);
        $this->g = min(round($this->g * $rate + $add), 255);
        $this->b = min(round($this->b * $rate + $add), 255);
    }


    /**
     *
     */
    function setGradient($color, $rate)
    {
        $dR = $this->r - $color->r;
        $dG = $this->g - $color->g;
        $dB = $this->b - $color->b;

        $this->r = round($this->r - $rate * $dR);
        $this->g = round($this->g - $rate * $dG);
        $this->b = round($this->b - $rate * $dB);
    }


    /**
     *
     */
    function __toString()
    {
        return $this->getHex();
    }
    

    /**
     * Преобразува hex цвят към RGB
     */
    public static function hexToRgbArr($hexColor)
    {
        if($color = self::getNamedColor($hexColor)) {
            $hexColor = $color;
        }

        if($hexColor{0} == '#') $hexColor = substr($hexColor, 1);
        
        if(preg_match("/[0-9a-f]{3}([0-9a-f]{3})/i", $hexColor) || preg_match("/[0-9a-f]{3}/i", $hexColor)) {
            if(strlen($hexColor) == 3) {
                $r = hexdec($hexColor{0} . $hexColor{0});
                $g = hexdec($hexColor{1} . $hexColor{1});
                $b = hexdec($hexColor{2} . $hexColor{2});

                return array($r, $g, $b);

            } elseif(strlen($hexColor) == 6) {
                $r = hexdec($hexColor{0} . $hexColor{1});
                $g = hexdec($hexColor{2} . $hexColor{3});
                $b = hexdec($hexColor{4} . $hexColor{5});

                return array($r, $g, $b);
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
        
        return TRUE;
    }



    /**
     * Връща hex код на цвят според HTML името му
     */
    public static function getNamedColor($color)
    {
        static $hexColors;

        if(!$hexColors) {
            $hexColors = array('aliceblue'=>'f0f8ff', 'amethyst'=>'9966cc',
                'antiquewhite'=>'faebd7', 'aqua'=>'00ffff', 'aquamarine'=>'7fffd4',
                'azure'=>'f0ffff', 'beige'=>'f5f5dc', 'bisque'=>'ffe4c4', 'black'=>'000000',
                'blanchedalmond'=>'ffebcd', 'blue'=>'0000ff', 'blueviolet'=>'8a2be2',
                'brown'=>'a52a2a', 'burlywood'=>'deb887', 'cadetblue'=>'5f9ea0',
                'chartreuse'=>'7fff00', 'chocolate'=>'d2691e', 'coral'=>'ff7f50',
                'cornflowerblue'=>'6495ed', 'cornsilk'=>'fff8dc', 'crimson'=>'dc143c',
                'cyan'=>'00ffff', 'darkblue'=>'00008b', 'darkcyan'=>'008b8b',
                'darkgoldenrod'=>'b8860b', 'darkgray'=>'a9a9a9', 'darkgreen'=>'006400',
                'darkkhaki'=>'bdb76b', 'darkmagenta'=>'8b008b', 'darkolivegreen'=>'556b2f',
                'darkorange'=>'ff8c00', 'darkorchid'=>'9932cc', 'darkred'=>'8b0000',
                'darksalmon'=>'e9967a', 'darkseagreen'=>'8fbc8f', 'darkslateblue'=>'483d8b',
                'darkslategray'=>'2f4f4f', 'darkturquoise'=>'00ced1', 'darkviolet'=>'9400d3',
                'deeppink'=>'ff1493', 'deepskyblue'=>'00bfff', 'dimgray'=>'696969',
                'dodgerblue'=>'1e90ff', 'feldspar'=>'d19275', 'firebrick'=>'b22222',
                'floralwhite'=>'fffaf0', 'forestgreen'=>'228b22', 'fuchsia'=>'ff00ff',
                'gainsboro'=>'dcdcdc', 'ghostwhite'=>'f8f8ff', 'gold'=>'ffd700',
                'goldenrod'=>'daa520', 'gray'=>'808080', 'green'=>'008000',
                'greenyellow'=>'adff2f', 'honeydew'=>'f0fff0', 'hotpink'=>'ff69b4',
                'indianred '=>'cd5c5c', 'indigo '=>'4b0082', 'ivory'=>'fffff0', 'khaki'=>'f0e68c',
                'lavender'=>'e6e6fa', 'lavenderblush'=>'fff0f5', 'lawngreen'=>'7cfc00',
                'lemonchiffon'=>'fffacd', 'lightblue'=>'add8e6', 'lightcoral'=>'f08080',
                'lightcyan'=>'e0ffff', 'lightgoldenrodyellow'=>'fafad2', 'lightgrey'=>'d3d3d3',
                'lightgreen'=>'90ee90', 'lightpink'=>'ffb6c1', 'lightsalmon'=>'ffa07a',
                'lightseagreen'=>'20b2aa', 'lightskyblue'=>'87cefa', 'lightslateblue'=>'8470ff',
                'lightslategray'=>'778899', 'lightsteelblue'=>'b0c4de', 'lightyellow'=>'ffffe0',
                'lime'=>'00ff00', 'limegreen'=>'32cd32', 'linen'=>'faf0e6', 'magenta'=>'ff00ff',
                'maroon'=>'800000', 'mediumaquamarine'=>'66cdaa', 'mediumblue'=>'0000cd',
                'mediumorchid'=>'ba55d3', 'mediumpurple'=>'9370d8', 'mediumseagreen'=>'3cb371',
                'mediumslateblue'=>'7b68ee', 'mediumspringgreen'=>'00fa9a',
                'mediumturquoise'=>'48d1cc', 'mediumvioletred'=>'c71585',
                'midnightblue'=>'191970', 'mintcream'=>'f5fffa', 'mistyrose'=>'ffe4e1',
                'moccasin'=>'ffe4b5', 'navajowhite'=>'ffdead', 'navy'=>'000080',
                'oldlace'=>'fdf5e6', 'olive'=>'808000', 'olivedrab'=>'6b8e23', 'orange'=>'ffa500',
                'orangered'=>'ff4500', 'orchid'=>'da70d6', 'palegoldenrod'=>'eee8aa',
                'palegreen'=>'98fb98', 'paleturquoise'=>'afeeee', 'palevioletred'=>'d87093',
                'papayawhip'=>'ffefd5', 'peachpuff'=>'ffdab9', 'peru'=>'cd853f', 'pink'=>'ffc0cb',
                'plum'=>'dda0dd', 'powderblue'=>'b0e0e6', 'purple'=>'800080', 'red'=>'ff0000',
                'rosybrown'=>'bc8f8f', 'royalblue'=>'4169e1', 'saddlebrown'=>'8b4513',
                'salmon'=>'fa8072', 'sandybrown'=>'f4a460', 'seagreen'=>'2e8b57',
                'seashell'=>'fff5ee', 'sienna'=>'a0522d', 'silver'=>'c0c0c0', 'skyblue'=>'87ceeb',
                'slateblue'=>'6a5acd', 'slategray'=>'708090', 'snow'=>'fffafa',
                'springgreen'=>'00ff7f', 'steelblue'=>'4682b4', 'tan'=>'d2b48c', 'teal'=>'008080',
                'thistle'=>'d8bfd8', 'tomato'=>'ff6347', 'turquoise'=>'40e0d0', 'violet'=>'ee82ee',
                'violetred'=>'d02090', 'wheat'=>'f5deb3', 'white'=>'ffffff',
                'whitesmoke'=>'f5f5f5', 'yellow'=>'ffff00', 'yellowgreen'=>'9acd32');
        }
        
        return $hexColors[strtolower($color)];
    }
}