<?php

defIfNot('CAD2_MAX_CANVAS_SIZE', 1000);

/**
 *
 */
class cad2_SvgCanvas extends core_BaseClass {
    
    /**
     * Колко вътрешни svg единици отговарят на 1 мм
     */
    var $pixPerMm;
    

    /**
     * Масив с XML обекти - тагове
     */
	var $contents = array();
    
    
    /**
     * Текущи атрибути на лементите
     */
    var $attr = array();
    var $alowedAttributes = array('stroke', 'stroke-width', 'stroke-opacity', 'stroke-dasharray', 'stroke-linecap', 'fill', 'fill-opacity', 'font-size', 'font-weight', 'font-family', 'text-color');


    /**
     * Параметри на страницата
     */
    var $width;
    var $height;
    var $paddingTop;
    var $paddingRight;
    var $paddingBottom;
    var $paddingLeft;


    /**
     * Текущи параметри на молива, запълването и шрифта
     * stroke, stroke-width, stroke-linecap, stroke-dasharray
     * fill-color, fill-opacity
     * font-face, font-size, font-weight

     */

	function __construct($width = 210, $height = 297, $pixPerMm = 10, $paddingTop = 10, $paddingRight = 10, $paddingBottom = 10, $paddingLeft = 10)
    {   
        // Отношение между милиметри и svg пиксели
        $this->pixPerMm = $pixPerMm;
        
        // Кодиране
		$this->encoding = "UTF-8";
        
        // Задаваме размерите и отстъпите на страницата
        list($this->width, 
             $this->height,
             $this->paddingTop,
             $this->paddingRight,
             $this->paddingBottom,
             $this->paddingLeft,
            ) = self::toPix($width, $height, $paddingTop, $paddingRight, $paddingBottom, $paddingLeft);
        
        $conf = core_Packs::getConfig('cad');

		$this->minX = $paddingLeft * $pixPerMm;
		$this->maxX = ($width - $paddingLeft) * $pixPerMm;
        $this->minY = $paddingTop * $pixPerMm;
		$this->maxY = ($height - $paddingBottom) * $pixPerMm;

        $this->setCP($this->width - $this->paddingRight, $this->height - $this->paddingBottom, TRUE);
        $this->setCP($this->paddingLeft, $this->paddingTop, TRUE);

        $this->setAttr('stroke', 'black');
        $this->setAttr('stroke-width', 0.2);
        $this->setAttr('fill', 'none');
        $this->setAttr('font-size', 40); 
        $this->setAttr('font-family', 'Courier');
	}


    /**
     * Задава точка от чертежа, която трябва да се побере на хартията.
     * Тази точка евентуално може да разшири автоматично
     * изчислявания размер на хартията
     */
	private function fitPoint($x, $y)
    {
        // В svg pixels
		$this->minY = min($y, $this->minY);
		$this->maxX = max($x, $this->maxX);
		$this->maxY = max($y, $this->maxY);
		$this->minX = min($x, $this->minX);
 

        
      //  $conf = core_Packs::getConfig('cad2');

      //  expect((min($this->minY, $this->minX) > (0 - $conf->CAD2_MAX_CANVAS_SIZE * $this->pixPerMm)) &&
      //         (max($this->maxY, $this->maxX) < $conf->CAD2_MAX_CANVAS_SIZE * $this->pixPerMm) ,
      //         'Размерът е извън допъстимите граници',
      //         $this,
       //        $conf->CAD2_MAX_CANVAS_SIZE * $this->pixPerMm
      //      );
	}


    /**
     * Конвертира координатите от милиметри към единиците на viewport
     */
	private function toPix()
    {   
        $args = func_get_args();

        foreach($args as $val) {
            $res[] = round($this->pixPerMm * (double)$val);
        }

        return $res;
	}
    

    /**
     * Задава текушата точка
     */
	private function setCP($x, $y, $absolute = FALSE, $fitPoint = TRUE)
    {   
        if($absolute) {
            $this->x = $x;
            $this->y = $y;
        } else {
            $this->x += $x;
            $this->y += $y;
        }
        
        if($fitPoint) {
            $this->fitPoint($this->x, $this->y);
        }
	}


    /**
     * Връща текущата позиция
     */
    function getCP()
    {
        return array($this->x / $this->pixPerMm, $this->y / $this->pixPerMm);
    }


    /**
     * Задава текущия прозорец
     */
    function setWindow($x, $y, $w, $h)
    {
        $this->window = array($x, $y, $x+$w, $y+$h);
    }

    /**
     * Премахва текущия прозорец
     */
    function unsetWindow()
    {
        $this->window = NULL;
    }


    /**
     * Проверява дали точката се намира в прозореца
     */
    function isInWindow($x, $y)
    {
        if(!$this->window) return TRUE;
        
        list($x1,$y1,$x2,$y2) = $this->window;

        if(min($x1,$x2) <= $x && max($x1,$x2) >= $x && min($y1,$y2) <= $y && max($y1,$y2) >= $y) {

            return TRUE;
        }

        return FALSE;
    }


    /**
     * Намира точка в която зададената отсечка сече прозореца
     */
    function getWindowIntersection($Ax, $Ay, $Bx, $By)
    {      
        if(!$this->window) return TRUE;
        
        list($x1,$y1,$x2,$y2) = $this->window;
        $p = $this->getIntersection($Ax, $Ay, $Bx, $By, $x1, $y1, $x1, $y2);
        if($p) return $p;

        $p = $this->getIntersection($Ax, $Ay, $Bx, $By, $x2, $y1, $x2, $y2);
        if($p) return $p;
      
        $p = $this->getIntersection($Ax, $Ay, $Bx, $By, $x1, $y1, $x2, $y1);
        if($p) return $p;

        $p = $this->getIntersection($Ax, $Ay, $Bx, $By, $x1, $y2, $x2, $y2);
        if($p) return $p;

        if($this->isInWindow($Ax, $Ay)) {
            return self::d($Ax, $Ay);
        }

        if($this->isInWindow($Bx, $By)) {

             return self::d($Bx, $By);
        }
    }


    /**
     * Връща тага на текущия път
     * Очаква той да е последния добавен в съдържанието
     */
    private function getCurrentPath()
    {
        $path = $this->content[count($this->content)-1];

        expect($path->name == 'path');
        
        return $path;
    }


    /**
     * Задава текуща стойност на посочения атрибит
     */
    function setAttr($name, $value)
    {
        expect(in_array($name, $this->alowedAttributes), $name);

        $this->attr[$name] = $value;
    }


    /**
     * Връща стойността на посочения атрибут
     */
    function getAttr($name)
    {
        expect(in_array($name, $this->alowedAttributes), $name);

        return $this->attr[$name];
    }

    

    /**
     * Започва нов път (поредица от линии и премествания)
     */
	public function startPath($attr = array())
    {
        $path = $this->content[] = new stdClass();
        $path->name = 'path';
        
        setIfNot($attr['stroke'], $this->getAttr('stroke'));
        setIfNot($attr['stroke-width'], $this->getAttr('stroke-width'));
        setIfNot($attr['stroke-opacity'], $this->getAttr('stroke-opacity'));
        setIfNot($attr['stroke-dasharray'], $this->getAttr('stroke-dasharray'));
        setIfNot($attr['stroke-linecap'], $this->getAttr('stroke-linecap'));

        setIfNot($attr['fill'], $this->getAttr('fill'));
        setIfNot($attr['fill-opacity'], $this->getAttr('fill-opacity'));


        $path->attr = $attr;

        return $path;
	}


    /**
     * Премества текущата позиция на посочените координати
     * без да рисува линия
     */
	function moveTo($x, $y, $absolute = FALSE)
    {   

        $path = $this->getCurrentPath();

        list($x, $y) = self::toPix($x, $y);

        
        $this->setCP($x, $y, $absolute);
        
        if(!$absolute) {
            $x1 = $x0 + $x;
            $y1 = $y0 + $y;
        } else {
            $x1 = $x;
            $y1 = $y;
        }
        
        if(!$this->isInWindow($x1, $y1)) return;

        $m = $absolute ? ' M' : ' m';

        $path->attr['d'] .= " {$m}{$x},{$y}";

	}


    /**
     * Рисува линия до посочените координати
     */
	function lineTo($x, $y, $absolute = FALSE)
    {
        list($x0, $y0) = $this->getCP();
        
        if(!$absolute) {
            $x1 = $x0 + $x;
            $y1 = $y0 + $y;
        } else {
            $x1 = $x;
            $y1 = $y;
        }
        
        if(!$this->isInWindow($x0, $y0) && !$this->isInWindow($x1, $y1)) {
            //if($iW = self::getWindowIntersection($x0, $y0, $x1, $y1)) {
           //     expect(count() ==2, $x, $y, $x0, $y0, $iW);
           // } else {
                list($x, $y) = self::toPix($x, $y);

                $this->setCP($x, $y, $absolute, FALSE);

                return;
          //  }
        } elseif(!$this->isInWindow($x0, $y0) && $this->isInWindow($x1, $y1)) {
            $iW = self::getWindowIntersection($x0, $y0, $x1, $y1);
            if($iW) { 
                $this->moveTo($iW->x, $iW->y, TRUE);
                //expect($this->isInWindow($iW->x, $iW->y), $this->window, $iW);
                $x = $x1;
                $y = $y1;
                $absolute = TRUE;
            }
        } elseif($this->isInWindow($x0, $y0) && !$this->isInWindow($x1, $y1)) {
            $iW = self::getWindowIntersection($x0, $y0, $x1, $y1);
            if($iW) {
                $x = $iW->x;
                $y = $iW->y;
                $absolute = TRUE;
            }
        }
//expect( $x !=0 || $y != 0, $this->isInWindow($x0, $y0) ,  $this->isInWindow($x1, $y1));
        $path = $this->getCurrentPath();
        list($x, $y) = self::toPix($x, $y);
        $l = $absolute ? 'L' : 'l';
        $path->attr['d'] .= " {$l}{$x},{$y}";
        $this->setCP($x, $y, $absolute);
	}


    /**
     * Изчертава крива на Безие с посочените координати
     */
	function curveTo($x1, $y1, $x2, $y2, $x, $y, $absolute = FALSE)
    {
        $path = $this->getCurrentPath();
        
        list($x1, $y1, $x2, $y2, $x, $y) = self::toPix($x1, $y1, $x2, $y2, $x, $y);

        $c = $absolute ? 'C' : 'c';

		$path->attr['d'] .= " {$c}{$x1},{$y1} {$x2},{$y2} {$x},{$y}";
 
		$this->setCP($x1, $y1, $absolute);
        $this->setCP($x2, $y2, $absolute);
		$this->setCP($x, $y, $absolute);
	}
    
    
    /**
     * Чертае закръгляне до посочената точка
     */
    function roundTo($x1, $y1, $x, $y, $r, $absolute = FALSE)
    {
        // Вземаме абсолютните координати на началната
        list($x0, $y0)  = $this->getCP();

        // Правим координатите абсолютни
        if(!$absolute) {
            $x1 += $x0;
            $y1 += $y0;
            $x += $x0;
            $y += $y0;
        }

        $A = new cad2_Vector($x0, $y0);
        $B = new cad2_Vector($x1, $y1);
        $C = new cad2_Vector($x, $y);

        $AB = $B->add($A->neg());
        $BC = $C->add($B->neg());
        $BA = $AB->neg();
        
        $m = abs($r * tan(($BC->a - $AB->a)/2));
 
        $M = $B->add($this->p($BA->a, $m));
        $N = $B->add($this->p($BC->a, $m));
        
        $c = 4/3*(M_SQRT2-1);
        
        $MB = $B->add($M->neg());
        
        $Mc = $M->add($this->p($MB->a, $MB->r * $c));

        $NB = $B->add($N->neg());
        $Nc = $N->add($this->p($NB->a, $NB->r * $c));


        if(round($A->x, 5) != round($M->x, 5) || round($A->y, 5) != round($M->y, 5)) {
            $this->lineTo($M->x, $M->y, TRUE);
        }

        $this->curveTo($Mc->x, $Mc->y, $Nc->x, $Nc->y, $N->x, $N->y, TRUE);
        
        if(round($C->x, 5) != round($N->x, 5) || round($C->y, 5) != round($N->y, 5)) {
            $this->lineTo($C->x, $C->y, TRUE);
        }
        
    }



    /**
     * Изписва текст
     */
    function writeText($x, $y, $text, $rotation = 0, $absolute = TRUE)
    {
        
        if(!$absolute) {
            list($x0, $y0) = $this->getCP();
            $x = $x + $x0;
            $y = $y + $y0;
        }

        list($x, $y) = self::toPix($x, $y);
        
        if($rotation != 0) {
            $gr = $this->content[] = new stdClass();
            $gr->name = 'g';
            $gr->attr = array();
            $gr->haveBody = TRUE;
        }

        $tx = $this->content[] = new stdClass();
        $tx->name = 'text';
        $tx->attr = array();
        $tx->body = $text;
        
        if($rotation != 0) {
            $grEnd = $this->content[] = new stdClass();
            $grEnd->name = '/g';
        }
 
 		if( $family = $this->getAttr('font-family') ) {
			$style .= " font-family:{$family};";
		}

 		if( $weight = $this->getAttr('font-weight') ) {
			$style .= " font-weight:{$weight};";
		} 

        $tx->attr = array('x' => $x, 'y' => $y, 'style' => $style);

		if( $color = $this->getAttr('text-color') ) {
			$tx->attr['fill'] = $color;
		}

        if( $size = $this->getAttr('font-size') ) {
			$tx->attr['font-size'] = $size;
		}
        
        if( $family = $this->getAttr('font-family') ) {
			$tx->attr['font-family'] = $family;
		}

	    $this->setCP($x, $y, TRUE);
        
        if($rotation != 0) {
            $width = $size * strlen($text) * 0.3;
            $height = $size;

            $x1 = $x + cos(deg2rad($rotation+90))*$height;
            $y1 = $y + sin(deg2rad($rotation+90))*$height;
            
            $alpha = atan($height/$width);
            $l = sqrt($width*$width + $height*$height);

            $x2 = $x + cos(deg2rad($rotation)+$alpha)*$l;
            $y2 = $y + sin(deg2rad($rotation)+$alpha)*$l;

            $x3 = $x + cos(deg2rad($rotation))*$width;
            $y3 = $y + sin(deg2rad($rotation))*$width;

			$a = round(cos(deg2rad($rotation)),5);
			$b = round(sin(deg2rad($rotation)),5);
			$c = -$b;
			$d = $a;
			$gr->attr = array('transform' => "matrix($a, $b, $c, $d, ".(-$x*$a+$y*$b+$x).", ".(-$x*$b-$y*$a+$y).")");
		}
	}



    /**
     * Затваря текущия път или под-път
     */
	function closePath($close = TRUE)
    {
        $path = $this->getCurrentPath();
        
        if($close) {
            $path->attr['d'] .= ' z';
        }
	}
	

    /**
     * Отваря нова група
     */
    function openGroup($attr = array())
    {
        $group = $this->content[] = new stdClass();
        $group->name = 'g';
        $group->attr = $attr;
        $group->haveBody = TRUE;
	}
    

    /**
     * Затваряне на група
     */
	function closeGroup()
    {
        $groupEnd = $this->content[] = new stdClass();
        $groupEnd->name = '/g';
	}
	
	
	/**
	 * Отваря новa шарка
	 */
	function openPattern($attr = array())
	{
		$group = $this->content[] = new stdClass();
		$group->name = 'pattern';
		$group->attr = $attr;
		$group->haveBody = TRUE;
	}
	
	
	/**
	 * Затваряне на шарка
	 */
	function closePattern()
	{
		$groupEnd = $this->content[] = new stdClass();
		$groupEnd->name = '/pattern';
	}
	
	
	/**
	 * Отваряне на дефиниции
	 */
	function openDefinitions($attr = array())
	{
        $defs = $this->content[] = new stdClass();
        $defs->name = 'defs';
        $defs->attr = $attr;
        $defs->haveBody = TRUE;
	}

	
	/**
	 * Затваряне на дефиниции
	 */
	function closeDefinitions()
	{
		$groupEnd = $this->content[] = new stdClass();
		$groupEnd->name = '/defs';
	}
	
	
    /**
     * Връща XML текста на SVG чертежа
     */
    function render()
    {
        // Параметрите на viewbox
		$top    = $this->minY - $this->paddingTop;
        $right  = $this->maxX + $this->paddingRight;
        $bottom = $this->maxY + $this->paddingBottom;
		$left   = $this->minX - $this->paddingLeft;
 
        // Динамично изчислените размери на страницата
		$width  = max($this->width,  $right - $left);
		$height = max($this->height, $bottom - $top);

        // Размерите в mm
        $widthMm  = $width  / $this->pixPerMm;
        $heightMm = $height / $this->pixPerMm;

 		$res .= "<svg width=\"{$widthMm}mm\" height=\"{$heightMm}mm\" viewBox=\"{$left} {$top} {$width} {$height}\"" .
                "\n        version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n";

        // Генериране на съдържанието
        foreach($this->content as $tag) {
            $res .= $this->getXML($tag);
        }
        
        $res .= "</svg>\n";

        return $res;
    }


    /**
     * Връща XML текста, съответстващ на обекта - таг
     */
    function getXML($tag)
    {
        if($tag->name) {
            if ($tag->attr && count($tag->attr)) {
                foreach ($tag->attr as $name => $val) {
                  
                    if(strlen($val) == 0) continue;

                    if (is_string($val)) {
                        $val = str_replace(array('&', "\""), array('&amp;', "&quot;"), $val);
                    }
                    
                    // Превръща някои атрибути във вътрешни мерни единици
                    switch($name) {
                        case 'size':
                        case 'stroke-width':
                            list($val) = self::toPix($val);
                            break;
                        case 'stroke-dasharray':
                            list($a, $b) = explode(',', trim($val));
                            $vals = self::toPix($a, $b);
                            $val  = implode(',', $vals);
                            break;
                    }

                    $attrStr .= " " . $name . "=\"" . $val . "\"";
                }
            }
            
            if(!isset($tag->body)) {
                if ($tag->haveBody || $tag->name{0} == '/') {
                    $element = "<{$tag->name}{$attrStr}>\n";
                } else {
                    $element = "<{$tag->name}{$attrStr}/>\n";
                }
            } else {
                $element = "<{$tag->name}{$attrStr}>{$tag->body}</{$tag->name}>\n";
            }

        } else {
            // Ако нямаме елемент, т.е. елемента е празен, връщаме само тялото
            $element = $body;
        }

        return $element;
    }


    /**
     * Връща вектор с посочените декартови координати
     */
    public static function d($x, $y)
    {
        $v = new cad2_Vector($x, $y);

        return $v;
    }


    /**
     * Връща вектор с посочените полярни координати
     */
    public static function p($a, $r)
    {
        $v = new cad2_Vector($a, $r, 'polar');

        return $v;
    }


    /**
     * Computes the intersection between two segments. 
     * @param x1 Starting point of Segment 1
     * @param y1 Starting point of Segment 1
     * @param x2 Ending point of Segment 1
     * @param y2 Ending point of Segment 1
     * @param x3 Starting point of Segment 2
     * @param y3 Starting point of Segment 2
     * @param x4 Ending point of Segment 2
     * @param y4 Ending point of Segment 2
     * @return Point where the segments intersect, or null if they don't
     */
    public static function getIntersection($x1,  $y1,  $x2,  $y2, $x3, $y3, $x4, $y4)
    {
        $x1 += 0.00001;
        $y1 -= 0.00002;
        
        $x3 += 0.00003;
        $y3 -= 0.00004;

        $d = ($x1-$x2)*($y3-$y4) - ($y1-$y2)*($x3-$x4);
        if ($d == 0) return null;
    
        $xi = (($x3-$x4)*($x1*$y2-$y1*$x2)-($x1-$x2)*($x3*$y4-$y3*$x4))/$d;
        $yi = (($y3-$y4)*($x1*$y2-$y1*$x2)-($y1-$y2)*($x3*$y4-$y3*$x4))/$d;


        if ($xi < min($x1,$x2) || $xi > max($x1,$x2)) return null;
        if ($xi < min($x3,$x4) || $xi > max($x3,$x4)) return null;

        return self::d(round($xi*10000)/10000, round($yi*10000)/10000);
    }
    
    
}