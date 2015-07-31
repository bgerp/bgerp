<?php


/**
 *
 */
class cad_SvgCanvas extends core_BaseClass {
    
    /**
     * Колко вътрешни svg единици отговарят на 1 мм
     */
    var $pixPerMm;
    

    /**
     * Масив с XML обекти - тагове
     */
	var $contents = array();

    
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

        $this->minY = $conf->CAD_MAX_CANVAS_SIZE * $pixPerMm;
		$this->maxX = - $conf->CAD_MAX_CANVAS_SIZE * $pixPerMm;
		$this->maxY = - $conf->CAD_MAX_CANVAS_SIZE * $pixPerMm;
		$this->minX = $conf->CAD_MAX_CANVAS_SIZE * $pixPerMm;

        $this->setCP($this->width - $this->paddingRight, $this->height - $this->paddingBottom, TRUE);
        $this->setCP($this->paddingLeft, $this->paddingTop, TRUE); 

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
        
        $conf = core_Packs::getConfig('cad');

        expect((min($this->minY, $this->minX) > (0 - $conf->CAD_MAX_CANVAS_SIZE * $this->pixPerMm)) &&
               (max($this->maxY, $this->maxX) < $conf->CAD_MAX_CANVAS_SIZE * $this->pixPerMm) ,
               'Размерът е извън допъстимите граници',
               $this,
               $conf->CAD_MAX_CANVAS_SIZE * $this->pixPerMm
            );
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
	private function setCP($x, $y, $absolute = FALSE)
    {   
        if($absolute) {
            $this->x = $x;
            $this->y = $y;
        } else {
            $this->x += $x;
            $this->y += $y;
        }

        $this->fitPoint($this->x, $this->y);
	}


    /**
     * Връща текущата позиция
     */
    function getCP()
    {
        return array($this->x / $this->pixPerMm, $this->y / $this->pixPerMm);
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
     * Започва нов път (поредица от линии и премествания)
     */
	public function startPath($attr = array())
    {
        $path = $this->content[] = new stdClass();
        $path->name = 'path';
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

        $m = $absolute ? ' M' : ' m';

        $path->attr['d'] .= " {$m}{$x},{$y}";

        $this->setCP($x, $y, $absolute);
	}


    /**
     * Рисува линия до посочените координати
     */
	function lineTo($x, $y, $absolute = FALSE)
    {
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

        $A = new cad_Vector($x0, $y0);
        $B = new cad_Vector($x1, $y1);
        $C = new cad_Vector($x, $y);

        $AB = $B->add($A->neg());
        $BC = $C->add($B->neg());
        $BA = $AB->neg();
        
        $m = abs($r * tan(($BC->a - $AB->a)/2));
 
        $M = $B->add(new cad_Vector($BA->a, $m, 'polar'));
        $N = $B->add(new cad_Vector($BC->a, $m, 'polar'));
        
        $c = 4/3*(M_SQRT2-1);
        
        $MB = $B->add($M->neg());
        
        $Mc = $M->add( new cad_Vector($MB->a, $MB->r * $c, 'polar'));

        $NB = $B->add($N->neg());
        $Nc = $N->add( new cad_Vector($NB->a, $NB->r * $c, 'polar'));


        if(round($A->x, 5) != round($M->x, 5) || round($A->y, 5) != round($M->y, 5)) {
            $this->lineTo($M->x, $M->y, TRUE);
        }

        $this->curveTo($Mc->x, $Mc->y, $Nc->x, $Nc->y, $N->x, $N->y, TRUE);
        
        if(round($C->x, 5) != round($N->x, 5) || round($C->y, 5) != round($N->y, 5)) {
            $this->lineTo($C->x, $C->y, TRUE);
        }
        
    }


    function arcTo($x1, $y1, $r, $absolute = FALSE, $debug = FALSE) 
    {
        // Вземаме абсолютните координати на началната
        list($x0, $y0)  = $this->getCP();

        // Правим координатите абсолютни
        if(!$absolute) {
            $x1 += $x0;
            $y1 += $y0;
        }

        $A = new cad_Vector($x0, $y0);
        $B = new cad_Vector($x1, $y1);
        $AB = $B->add($A->neg());
        
        $M = $A->add(new cad_Vector($AB->a, $AB->r/2, 'polar'));
        
        $dist = $r * $r - $AB->r/2 * $AB->r/2;

        if($dist < 0) {
            $m = 0; 
            $r = ($AB->r / 2) * abs($r)/$r;
        } else {
            $m = sqrt($dist);
        }
 
        $C = $M->add( new cad_Vector($AB->a - pi()/2 + ($r<0 ? pi() : 0), $m, 'polar'));
 
        $CA = $A->add($C->neg());
        $CB = $B->add($C->neg());
 
        if($CA->a > $CB->a ) {
            if($CA->a - $CB->a > 2*pi()) {
                for($a = $CA->a; $a >= $CB->a + 2*pi(); $a = pi()/100) {
                    $X = $C->add( new cad_Vector($a, abs($r), 'polar'));
                    $this->lineTo($X->x, $X->y, TRUE);
                }
            } else {
                for($a = $CA->a; $a >= $CB->a; $a -= pi()/100) {
                    $X = $C->add( new cad_Vector($a, abs($r), 'polar'));
                    $this->lineTo($X->x, $X->y, TRUE);
                }
            }
        } else {

            if($CB->a - $CA->a > pi()) {
                for($a = $CA->a + 2*pi(); $a >= $CB->a; $a -= pi()/100) {
                    $X = $C->add( new cad_Vector($a, abs($r), 'polar'));
                    $this->lineTo($X->x, $X->y, TRUE);
                }
            } else {
                for($a = $CA->a; $a <= $CB->a; $a += pi()/100) {
                    $X = $C->add( new cad_Vector($a, abs($r), 'polar'));
                    $this->lineTo($X->x, $X->y, TRUE);
                }
            }

        }

        $this->lineTo($x1, $y1, TRUE);

    }






    /**
     * Затваря текущия път или под-път
     */
	function closePath()
    {
        $path = $this->getCurrentPath();
        
        $path->attr['d'] .= ' z';
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
                            $vals = explode(' ', trim($val));
                            $vals = self::toPix($vals);
                            $val  = implode(' ', $vals);
                            break;
                    }

                    $attrStr .= " " . $name . "=\"" . $val . "\"";
                }
            }
            
            if(!isset($tag->body)) {
                if ($tag->haveBody) {
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


}