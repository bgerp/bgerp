<?php



/**
 * Помощен клас за създаване на обекти - цветови модели.
 * С него може да се конвертират стойностите от един модел в друг
 *
 *
 * @category  vendors
 * @package   color
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class color_Colors
{
    
    
    /**
     * Какъв е цветовия модел
     */
    private $type;
    
    
    /**
     * Тук се складират стойностите на класа
     * @var array
     */
    public $values;
    
    
    /**
     * Инстанцира обект в съответния цветови модел
     * След това трябва да му се заредят стойностите
     * @param enum $type - цветовия модел
     *                   Допустими типове (rgb, cmyk, hsv, cielab, xyz)
     */
    public static function get($type)
    {
        expect(in_array($type, array('rgb','cmyk','hsv','cielab','xyz','hsl')));
        $Class = cls::get(get_called_class());
        $Class->type = $type;
        
        return $Class;
    }
    
    
    /**
     * Задава стойности на цветовия модел
     * @param mixed      $v1 - първа стойност
     * @param mixed      $v2 - втора стойност
     * @param mixed      $v3 - трета стойност
     * @param mixed/NULL $v4 - само за CMYK
     */
    public function setValues($v1, $v2, $v3, $v4 = null)
    {
        switch ($this->type) {
            case 'rgb':
                $this->values = $this->getRGB($v1, $v2, $v3);
                break;
            case 'cmyk':
                $this->values = $this->getCMYK($v1, $v2, $v3, $v4);
                break;
            case 'hsv':
                $this->values = $this->getHSV($v1, $v2, $v3);
                break;
            case 'hsl':
                $this->values = $this->getHSL($v1, $v2, $v3);
                break;
            case 'cielab':
                $this->values = $this->getLAB($v1, $v2, $v3);
                break;
            case 'xyz':
                $this->values = $this->getXYZ($v1, $v2, $v3);
                break;
        }
    }
    
    
    /**
     * Задава стойностите на XYZ
     * @param double $v1 - x
     * @param double $v2 - y
     * @param double $v3 - z
     */
    private function getXYZ($v1, $v2, $v3)
    {
        if ($v1 <= 0) {
            $v1 = 0;
        }
        if ($v2 <= 0) {
            $v2 = 0;
        }
        if ($v3 <= 0) {
            $v3 = 0;
        }
         
        if ($v1 > 100) {
            $v1 = 100;
        }
        if ($v2 > 100) {
            $v2 = 100;
        }
        if ($v3 > 100) {
            $v3 = 100;
        }
        
        return array('x' => $v1, 'y' => $v2, 'z' => $v3);
    }
    
    
    /**
     * Задава стойностите на RGB
     * @param double $v1 - Red
     * @param double $v2 - Green
     * @param double $v3 - Blue
     */
    private function getRGB($v1, $v2, $v3)
    {
        if ($v1 <= 0) {
            $v1 = 0;
        }
        if ($v2 <= 0) {
            $v2 = 0;
        }
        if ($v3 <= 0) {
            $v3 = 0;
        }
         
        if ($v1 > 255) {
            $v1 = 255;
        }
        if ($v2 > 255) {
            $v2 = 255;
        }
        if ($v3 > 255) {
            $v3 = 255;
        }

        return array('r' => $v1, 'g' => $v2, 'b' => $v3);
    }
    
    
    /**
     * Задава стойностите на HSV
     * @param double $v1 - Cyan
     * @param double $v2 - Magenta
     * @param double $v3 - Yellow
     * @param double $v4 - Black
     */
    private function getCMYK($v1, $v2, $v3, $v4)
    {
        if ($v1 <= 0) {
            $v1 = 0;
        }
        if ($v2 <= 0) {
            $v2 = 0;
        }
        if ($v3 <= 0) {
            $v3 = 0;
        }
        if ($v4 <= 0) {
            $v4 = 0;
        }
         
        if ($v1 > 100) {
            $v1 = 100;
        }
        if ($v2 > 100) {
            $v2 = 100;
        }
        if ($v3 > 100) {
            $v3 = 100;
        }
        if ($v4 > 100) {
            $v4 = 100;
        }
        
        return array('c' => $v1, 'm' => $v2, 'y' => $v3, 'k' => $v4);
    }
    
    
    /**
     * Задава стойностите на HSV
     * @param double $v1 - Hue
     * @param double $v2 - Saturation
     * @param double $v3 - Value
     */
    private function getHSV($v1, $v2, $v3)
    {
        if ($v1 <= 0) {
            $v1 = 0;
        }
        if ($v2 <= 0) {
            $v2 = 0;
        }
        if ($v3 <= 0) {
            $v3 = 0;
        }
         
        if ($v1 > 360) {
            $v1 = 360;
        }
        if ($v2 > 100) {
            $v2 = 100;
        }
        if ($v3 > 100) {
            $v3 = 100;
        }
        
        return array('h' => $v1, 's' => $v2, 'v' => $v3);
    }
    
    
    /**
     * Задава стойностите на HSL
     * @param double $v1 - Hue
     * @param double $v2 - Saturation
     * @param double $v3 - Lightness
     */
    private function getHSL($v1, $v2, $v3)
    {
        if ($v1 <= 0) {
            $v1 = 0;
        }
        if ($v2 <= 0) {
            $v2 = 0;
        }
        if ($v3 <= 0) {
            $v3 = 0;
        }
         
        if ($v1 > 360) {
            $v1 = 360;
        }
        if ($v2 > 100) {
            $v2 = 100;
        }
        if ($v3 > 100) {
            $v3 = 100;
        }
        
        return array('h' => $v1, 's' => $v2, 'l' => $v3);
    }
    
    
    /**
     * Задава CIE-L*ab стойностите
     * @param int    $v1 - L*
     * @param double $v2 - a*
     * @param double $v3 - b*
     */
    private function getLAB($v1, $v2, $v3)
    {
        if ($v1 <= 0) {
            $v1 = 0;
        }
        if ($v2 <= -128) {
            $v2 = -128;
        }
        if ($v3 <= -128) {
            $v3 = -128;
        }
         
        if ($v1 > 100) {
            $v1 = 100;
        }
        if ($v2 > 128) {
            $v2 = 128;
        }
        if ($v3 > 128) {
            $v3 = 128;
        }
        
        return array('l' => $v1, 'a' => $v2, 'b' => $v3);
    }
    
    
    /**
     * Конвертира от всеки модел към RGB
     */
    public function toRGB()
    {
        expect(count($this->values));
        switch ($this->type) {
            case 'rgb':
                $this->values = array('r' => $this->values['r'],
                                      'g' => $this->values['g'],
                                      'b' => $this->values['b']);
                break;
            case 'cmyk':
                $this->values = $this->cmykToRgb($this->values);
                break;
            case 'hsv':
                $this->values = $this->hsvToRgb($this->values);
                break;
            case 'hsl':
                $this->values = $this->hslToRgb($this->values);
                break;
            case 'cielab':
                $r = $this->values;
                $this->values = $this->cielabToXYZ($this->values);
                $this->values = $this->xyzToRgb($this->values);
                break;
            case 'xyz':
                $this->values = $this->xyzToRgb($this->values);
        }
        
        $this->type = 'rgb';
    }
    
    
    /**
     * Конвертира от всеки модел към XYZ, преминавайки през RGB
     */
    public function toXYZ()
    {
        $this->toRGB();
        $this->type = 'xyz';
        $r = ($this->values['r'] / 255);
        $g = ($this->values['g'] / 255);
        $b = ($this->values['b'] / 255);
        
        $r = ($r > 0.04045) ? pow(($r + 0.055) / 1.055, 2.4) : $r / 12.92;
        $g = ($g > 0.04045) ? pow(($g + 0.055) / 1.055, 2.4) : $g / 12.92;
        $b = ($b > 0.04045) ? pow(($b + 0.055) / 1.055, 2.4) : $b / 12.92;
        
        $r = $r * 100;
        $g = $g * 100;
        $b = $b * 100;
        
        //Observer. = 2°, Illuminant = D65
        $x = $r * 0.4124 + $g * 0.3576 + $b * 0.1805;
        $y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
        $z = $r * 0.0193 + $g * 0.1192 + $b * 0.9505;
        
        $this->values = array('x' => $x, 'y' => $y, 'z' => $z);
    }
    
    
    /**
     * Конвертира към CIE-L*ab, преминавайки през XYZ и RGB при нужда
     */
    public function toCielab()
    {
        if ($this->type != 'xyz') {
            $this->toRGB();
            $this->toXYZ();
        } elseif ($this->type == 'cielab') {
            return;
        }
        
        $x = $this->values['x'] / 95.047;     //ref_X =  95.047   Observer= 2°, Illuminant= D65
        $y = $this->values['y'] / 100;        //ref_Y = 100.000
        $z = $this->values['z'] / 108.883;    //ref_Z = 108.883
        
        $x = ($x > 0.008856) ? pow($x, 1 / 3) : (7.787 * $x) + (16 / 116);
        $y = ($y > 0.008856) ? pow($y, 1 / 3) : (7.787 * $y) + (16 / 116);
        $z = ($z > 0.008856) ? pow($z, 1 / 3) : (7.787 * $z) + (16 / 116);
        
        $l = (116 * $y) - 16;
        $a = 500 * ($x - $y);
        $b = 200 * ($y - $z);
        
        $this->type = 'cielab';
        $this->values = array('l' => $l, 'a' => $a, 'b' => $b);
    }
    
    
    /**
     * Конвертира от CIE-L*ab до XYZ
     */
    private function cielabToXYZ($values)
    {
        $y = ($values['l'] + 16.0) / 116.0;
        $y3 = pow($y, 3.0);
        $x = ($values['a'] / 500.0) + $y;
        $x3 = pow($x, 3.0);
        $z = $y - ($values['b'] / 200.0);
        $z3 = pow($z, 3.0);

        $y = ($y3 > 0.008856) ? $y3 : ($y - (16.0 / 116.0)) / 7.787;
        $x = ($x3 > 0.008856) ? $x3 : ($x - (16.0 / 116.0)) / 7.787;
        $z = ($z3 > 0.008856) ? $z3 : ($z - (16.0 / 116.0)) / 7.787;

        $x = $x * 95.047;
        $y = $y * 100;
        $z = $z * 108.883;
      
        return array('x' => $x, 'y' => $y, 'z' => $z);
    }
    
    
    /**
     * Конвертира от XYZ към RGB
     */
    private function xyzToRgb($values)
    {
        $x = $values['x'] / 100;
        $y = $values['y'] / 100;
        $z = $values['z'] / 100;
        
        $r = $x * 3.2406 + $y * -1.5372 + $z * -0.4986;
        $g = $x * -0.9689 + $y * 1.8758 + $z * 0.0415;
        $b = $x * 0.0557 + $y * -0.2040 + $z * 1.0570;
        
        
        if ($r > 0.0031308) {
            $r = 1.055 * pow($r, (1 / 2.4)) - 0.055;
        } else {
            $r = 12.92 * $r;
        }
        if ($g > 0.0031308) {
            $g = 1.055 * pow($g, (1 / 2.4)) - 0.055;
        } else {
            $g = 12.92 * $g;
        }
        if ($b > 0.0031308) {
            $b = 1.055 * pow($b, (1 / 2.4)) - 0.055;
        } else {
            $b = 12.92 * $b;
        }
        
        $r = ($r < 0) ? 0 : $r;
        $g = ($g < 0) ? 0 : $g;
        $b = ($b < 0) ? 0 : $b;
          
        $r = round($r * 255, 2);
        $g = round($g * 255, 2);
        $b = round($b * 255, 2);
        
        $r = ($r >= 255) ? 255 : $r;
        $g = ($g >= 255) ? 255 : $g;
        $b = ($b >= 255) ? 255 : $b;
          
        return array('r' => $r, 'g' => $g, 'b' => $b);
    }
    
    
    /**
     * Конвертира от CMYK към RGB
     */
    private function cmykToRgb($values)
    {
        $c = ($values['c'] > 1) ? $values['c'] / 100.0 : $values['c'];
        $m = ($values['m'] > 1) ? $values['m'] / 100.0 : $values['m'];
        $y = ($values['y'] > 1) ? $values['y'] / 100.0 : $values['y'];
        $k = ($values['k'] > 1) ? $values['k'] / 100.0 : $values['k'];
        
        $r = 1 - min(array(1, $c * (1 - $k) + $k));
        $g = 1 - min(array(1, $m * (1 - $k) + $k));
        $b = 1 - min(array(1, $y * (1 - $k) + $k));
        
        $r = $r * 255.0;
        $g = $g * 255.0;
        $b = $b * 255.0;
        
        return array('r' => $r, 'g' => $g, 'b' => $b);
    }
    
    
    /**
     * Конвертира от HSV към RGB
     */
    private function hsvToRgb($values)
    {
        $h = $values['h'] / 360.0;
        $s = ($values['s'] > 1) ? $values['s'] / 100.0 : $values['s'];
        $v = ($values['v'] > 1) ? $values['v'] / 100.0 : $values['v'];
             
        if ($s == 0) {
            $r = $v * 255.0;
            $g = $v * 255.0;
            $b = $v * 255.0;
        } else {
            $vH = $h * 6;
            $vI = floor($vH);
            $v1 = $v * (1 - $s);
            $v2 = $v * (1 - $s * ($vH - $vI));
            $v3 = $v * (1 - $s * (1 - ($vH - $vI)));
            if ($vI == 0) {
                $vR = $v;
                $vG = $v3;
                $vB = $v1;
            } elseif ($vI == 1) {
                $vR = $v2;
                $vG = $v;
                $vB = $v1;
            } elseif ($vI == 2) {
                $vR = $v1;
                $vG = $v;
                $vB = $v3;
            } elseif ($vI == 3) {
                $vR = $v1;
                $vG = $v2;
                $vB = $v;
            } elseif ($vI == 4) {
                $vR = $v3;
                $vG = $v1;
                $vB = $v;
            } else {
                $vR = $v;
                $vG = $v1;
                $vB = $v2;
            }
                 
            $r = $vR * 255.0;
            $g = $vG * 255.0;
            $b = $vB * 255.0;
        }
        
        $r = round($r);
        $g = round($g);
        $b = round($b);

        return array('r' => $r, 'g' => $g, 'b' => $b);
    }
    
    
    /**
     * Конвертира от HSL към RGB
     */
    private function hslToRgb($values)
    {
        $h = $values['h'] / 360;
        $s = $values['s'];
        $l = $values['l'];
        
        if ($s == 0) {
            $r = $g = $b = $l * 255;
        } else {
            $v2 = ($l < 0.5) ? $l * (1 + $s) : ($l + $s) - ($s * $l);
            $v1 = 2 * $l - $v2;
            
            $r = 255 * $this->hue2Rgb($v1, $v2, $h + (1 / 3));
            $g = 255 * $this->hue2Rgb($v1, $v2, $h);
            $b = 255 * $this->hue2Rgb($v1, $v2, $h - (1 / 3));
           
            return array('r' => $r, 'g' => $g, 'b' => $b);
        }
    }
    
    
    /**
     * Помощна ф-я при конвертирането от HSL към RGB
     */
    private function hue2Rgb($v1, $v2, $vH)
    {
        if ($vH < 0) {
            ++$vH;
        }
        if ($vH > 1) {
            --$vH;
        }
        if ((6 * $vH) < 1) {
            
            return ($v1 + ($v2 - $v1) * 6 * $vH);
        }
        if ((2 * $vH) < 1) {
            
            return ($v2);
        }
        if ((3 * $vH) < 2) {
            
            return ($v1 + ($v2 - $v1) * ((2 / 3) - $vH) * 6);
        }

        return $v1;
    }
    
    
    /**
     * Връща стойност от обекта
     */
    public function getValue($v)
    {
        expect(isset($this->values[$v]));

        return $this->values[$v];
    }
    
    
    /**
     * Обръща цвета във rgb подходящ за използване в уеб
     */
    public function getForWeb()
    {
        $this->toRGB();
        $r = (int) $this->getValue('r');
        $g = (int) $this->getValue('g');
        $b = (int) $this->getValue('b');

        return "rgb({$r}, {$g}, {$b})";
    }
    
    
    /**
     * Обръща цвета във rgb подходящ за използване в уеб
     */
    public function getImg($width = 1, $height = 1)
    {
        $this->toRGB();
        $r = (int) $this->getValue('r');
        $g = (int) $this->getValue('g');
        $b = (int) $this->getValue('b');
        
        $url = color_Renderer::getResourceUrl($width = 1, $height = 1, $r, $g, $b);

        return ht::createElement('img', array('src' => $url));
    }
    
    
    /**
     * Конвертира от всичко към  HSL (минавайки през HSV)
     */
    public function toHsl()
    {
        if ($this->type == 'hsl') {
            return;
        }
        $this->toHSV();
        $this->type = 'hsl';
        
        $h = $this->values['h'];
        $s = $this->values['s'];
        $v = $this->values['v'];
        
        $H = $h;
        $L = (2 - $s) * $v;
        $S = $s * $v;
        $S /= ($L <= 1) ? ($L) : 2 - ($L);
        $L /= 2;
        
        $this->values = array('h' => $H, 's' => $S, 'l' => $L);
    }
    
    
    /**
     * Конвертира от всичко към  CMYK (минавайки през CMY)
     */
    public function toCmyk($k = 1)
    {
        if ($this->type == 'cmyk') {
            return;
        }
        $this->toRGB();
        $this->type = 'cmyk';
        
        if ($k < 0) {
            $k = 0;
        }
        if ($k > 1) {
            $k = 1;
        }
        $c = 1 - ($this->values['r'] / 255);
        $m = 1 - ($this->values['g'] / 255);
        $y = 1 - ($this->values['b'] / 255);
        
        if ($c < $k) {
            $k = $c;
        }
        if ($m < $k) {
            $k = $m;
        }
        if ($y < $k) {
            $k = $y;
        }
        if ($k == 1) {
            $c = $m = $y = 0;
        } else {
            $c = ($c - $k) / (1 - $k);
            $m = ($m - $k) / (1 - $k);
            $y = ($y - $k) / (1 - $k);
        }
        
        $this->values = array('c' => $c, 'm' => $m, 'y' => $y, 'k' => $k);
    }
    
    
    /**
     * Конвертира от всичко до HSV (h и v са между 0-1)
     */
    public function toHsv()
    {
        $this->toRGB();
        $this->type = 'hsv';
        
        $r = ($this->values['r'] / 255);
        $g = ($this->values['g'] / 255);
        $b = ($this->values['b'] / 255);
        
        $min = min(array($r, $g, $b)) ;
        $max = max(array($r, $g, $b));
        $delMax = $max - $min;
        
        if ($delMax == 0) {
            $s = $h = 0;
        } else {
            $d = ($r == $min) ? $g - $b : (($b == $min) ? $r - $g : $b - $r);
            $h = ($r == $min) ? 3 : (($b == $min) ? 1 : 5);
            $h = 60 * ($h - $d / ($max - $min));
            $s = $delMax / $max;
            $v = $max;
        }
        
        $this->values = array('h' => $h, 's' => $s, 'v' => $v);
    }


    /**
     * Сравнява два цвята
     *
     * @param  string $color1 - първи цвят
     * @param  string $color2 - втори цвят
     * @return int    - Ако $color2 е по-светъл: -1. Ако са еднакво светли: 0. Ако $color1 е по-светъл: 1
     */
    public static function compareColorLightness($color1, $color2)
    {
        $colorObj = new color_Object($color1);
        list($r1, $g1, $b1) = array($colorObj->r, $colorObj->g, $colorObj->b);

        $colorObj = new color_Object($color2);
        list($r2, $g2, $b2) = array($colorObj->r, $colorObj->g, $colorObj->b);

        $colorDivider = sqrt(($r1 * $r1 + $g1 * $g1 + $b1 * $b1) / ($r2 * $r2 + $g2 * $g2 + $b2 * $b2));

        if ($colorDivider > 1) {
            
            return 1;
        } elseif ($colorDivider < 1) {
            
            return -1;
        } else {
            
            return 0;
        }
    }
}
