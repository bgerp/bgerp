<?php

defIfNot('CAD2_MAX_CANVAS_SIZE', 1000);

/**
 *
 */
class cad2_SvgCanvas extends cad2_Canvas {
    
    /**
     * Колко вътрешни svg единици отговарят на 1 мм
     */
    var $pixPerMm;
    

    /**
     * Масив с XML обекти - тагове
     */
	var $contents = array();
    
    //
    public $addX = 10;
    public $addY = 10;
    
    /**
     * Параметри на страницата
     */
    public $width;
    public $height;

    // Падинги на страницата
    public $paddingTop;
    public $paddingRight;
    public $paddingBottom;
    public $paddingLeft;

    /**
     * Брояч на слоевете
     */
    private $layersCnt;

    /**
     * Текущи параметри на молива, запълването и шрифта
     * stroke, stroke-width, stroke-linecap, stroke-dasharray
     * fill-color, fill-opacity
     * font-face, font-size, font-weight
     */
	function __construct()
    {   
        // Отношение между милиметри и svg пиксели
        $this->pixPerMm = 2.5;
        
        // Кодиране
		$this->encoding = "UTF-8";
 
		$this->minX = 0;
		$this->maxX = $width * $pixPerMm;
        $this->minY = 0;
		$this->maxY = $height * $pixPerMm;


        $this->setAttr('stroke', 'black');
        $this->setAttr('stroke-width', 0.2);
        $this->setAttr('fill', 'none');
        $this->setAttr('font-size', 40); 
        $this->setAttr('font-family', 'Courier');
	}


    /**
     * Задава размерите и падингите на хартията
     */
    public function setPaper ($width = 210, $height = 297, $paddingTop = 10, $paddingRight = 10, $paddingBottom = 10, $paddingLeft = 10)
    {
        // Задаваме размерите и отстъпите на страницата
        list($this->width, 
             $this->height,
             $this->paddingTop,
             $this->paddingRight,
             $this->paddingBottom,
             $this->paddingLeft,
            ) = self::toPix($width, $height, $paddingTop, $paddingRight, $paddingBottom, $paddingLeft);

        $this->setCP($this->width, $this->height, TRUE);
        $this->setCP(0, 0, TRUE);
    }


    /**
     * Задава точка от чертежа, която трябва да се побере на хартията.
     * Тази точка евентуално може да разшири автоматично
     * изчислявания размер на хартията
     */
	protected function fitPoint($x, $y)
    {
        // В svg pixels
		$this->minY = min($y, $this->minY);
		$this->maxX = max($x, $this->maxX);
		$this->maxY = max($y, $this->maxY);
		$this->minX = min($x, $this->minX);
 
	}


    /**
     * Конвертира координатите от милиметри към единиците на viewport
     */
	protected function toPix()
    {   
        $args = func_get_args();

        foreach($args as $val) {
            $res[] = round($this->pixPerMm * (double)$val, 1);
        }

        return $res;
	}
    

    /**
     * Задава текушата точка
     */
	protected function setCP($x, $y, $absolute = FALSE, $fitPoint = TRUE)
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
     * Задава текуща стойност на посочения атрибит
     */
    public function setAttr($name, $value)
    {
        expect(in_array($name, $this->alowedAttributes), $name);

        $this->attr[$name] = $value;
    }


    /**
     * Връща стойността на посочения атрибут
     */
    public function getAttr($name)
    {
        expect(in_array($name, $this->alowedAttributes), $name);

        return $this->attr[$name];
    }

    

    /**
     * Започва нов път (поредица от линии и премествания)
     */
	public function startPath($attr = array())
    {   
        $this->closePath(FALSE);

        $path = $this->content[] = new stdClass();
        $path->name = 'path';
        
        setIfNot($attr['stroke'], $this->getAttr('stroke'));
        setIfNot($attr['stroke-width'], $this->getAttr('stroke-width'));
        setIfNot($attr['stroke-opacity'], $this->getAttr('stroke-opacity'));
        setIfNot($attr['stroke-dasharray'], $this->getAttr('stroke-dasharray'));
        setIfNot($attr['stroke-linecap'], $this->getAttr('stroke-linecap'));
        
        setIfNot($attr['fill-rule'], $this->getAttr('fill-rule'));
        setIfNot($attr['fill'], $this->getAttr('fill'));
        setIfNot($attr['fill-opacity'], $this->getAttr('fill-opacity'));

        $path->attr = $attr;
        
        if($path->attr['fill'] == 'none' || $path->attr['fill'] == 'transparent') {
            $path->attr['fill-opacity'] = 0;
        }
        
        if($path->attr['stroke'] == 'transparent' || $path->attr['stroke'] == 'none') {
            $path->attr['stroke-width'] = 0;
            $path->attr['stroke'] = 'transparent';
        }

        return $path;
	}


    /**
     * Връща тага на текущия път
     * Очаква той да е последния добавен в съдържанието
     */
    protected function getCurrentPath()
    {
        $path = $this->content[count($this->content)-1];

        if($path->name == 'path') {
            
            return $path;
        }
    }



    /**
     * Затваря текущия път или под-път
     */
	public function closePath($close = TRUE)
    {
        $path = $this->getCurrentPath();
        
        if($path && (!$path->closed)) {
            
            if($close) {
                $path->data[] = array('z');
            }
            
            $path->closed = TRUE;
        }
	}


    /**
     * Премества текущата позиция на посочените координати
     * без да рисува линия
     */
	public function moveTo($x, $y, $absolute = FALSE)
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

        $m = $absolute ? 'M' : 'm';


        // $path->attr['d'] .= " {$m}{$x},{$y}";
        $path->data[] = array($m, $x, $y);
	}


    /**
     * Рисува линия до посочените координати
     */
	public function doLineTo($x, $y, $absolute = FALSE)
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

        $path = $this->getCurrentPath();
        list($x, $y) = self::toPix($x, $y);
        $l = $absolute ? 'L' : 'l';
        $this->setCP($x, $y, $absolute);

 
        // $path->attr['d'] .= " {$l}{$x},{$y}";

        $path->data[] = array($l, $x, $y);

	}


    /**
     * Изчертава крива на Безие с посочените координати
     */
	public function curveTo($x1, $y1, $x2, $y2, $x, $y, $absolute = FALSE)
    {
        $path = $this->getCurrentPath();
        
        list($x1, $y1, $x2, $y2, $x, $y) = self::toPix($x1, $y1, $x2, $y2, $x, $y);

        $c = $absolute ? 'C' : 'c';
 
		// $path->attr['d'] .= " {$c}{$x1},{$y1} {$x2},{$y2} {$x},{$y}";
 		$path->data[] = array($c, $x1, $y1, $x2, $y2, $x, $y);

		$this->setCP($x1, $y1, $absolute);
        $this->setCP($x2, $y2, $absolute);
		$this->setCP($x, $y, $absolute);
	}
    

    /**
     * Изписва текст
     */
    public function writeText($x, $y, $text, $rotation = 0, $absolute = TRUE, $link = NULL)
    {
        $this->closePath(FALSE);

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
            $size = ($size / 10) * $this->pixPerMm;
			$tx->attr['font-size'] = $size;
		}
        
        if( $family = $this->getAttr('font-family') ) {
			$tx->attr['font-family'] = $family;
		}

	    $this->setCP($x, $y, TRUE);
        
        if($rotation != 0) {
            $width = $size * strlen($text) * 0.3;
            $height = $size;


            // "matrix($a, $b, $c, $d, ".(-$x*$a+$y*$b+$x).", ".(-$x*$b-$y*$a+$y).")"
			$gr->transform = array($width, $height, $rotation, $x, $y);
		}
	}
	

    /**
     * Отваря нова група
     */
    public function openGroup($attr = array())
    {
        $group = $this->content[] = new stdClass();
        $group->name = 'g';
        $group->attr = $attr;
        $group->haveBody = TRUE;
	}
    

    /**
     * Затваряне на група
     */
	public function closeGroup()
    {
        $groupEnd = $this->content[] = new stdClass();
        $groupEnd->name = '/g';
	}


    /**
     * Отваря нова група
     */
    public function openTransform($transform = array(), $attr = array())
    {   
        $attr['_transform'] = $transform;

        return $this->openGroup($attr);
    }


    /**
     * Затваряне на група
     */
    public function closeTransform()
    {
        return $this->closeGroup();
    }


    /**
     * Отваря нов слой
     */
    public function openLayer($name = NULL)
    {
        $this->layersCnt++;

        $attr = array('inkscape:groupmode' => 'layer', 'id' => "layer" . $this->layersCnt, 'inkscape:label' => $name);

        $this->openGroup($attr);

        $tag = $this->content[] = new stdClass();
        $tag->name = 'title';
        $tag->attr = array();
        $tag->body = $name;
    }


    /**
     * Затваряне на слой
     */
    public function closeLayer()
    {
        return $this->closeGroup();
    }


	/**
	 * Отваря новa шарка
	 */
	public function openPattern($attr = array())
	{
		$group = $this->content[] = new stdClass();
		$group->name = 'pattern';
		$group->attr = $attr;
		$group->haveBody = TRUE;
	}
	
	
	/**
	 * Затваряне на шарка
	 */
	public function closePattern()
	{
		$groupEnd = $this->content[] = new stdClass();
		$groupEnd->name = '/pattern';
	}
	
	
	/**
	 * Отваряне на дефиниции
	 */
	public function openDefinitions($attr = array())
	{
        $defs = $this->content[] = new stdClass();
        $defs->name = 'defs';
        $defs->attr = $attr;
        $defs->haveBody = TRUE;
	}

	
	/**
	 * Затваряне на дефиниции
	 */
	public function closeDefinitions()
	{
		$groupEnd = $this->content[] = new stdClass();
		$groupEnd->name = '/defs';
	}


    /**
     * Отваряне на дефиниции за линеен градиент
     */
    public function openGradient($attr = array())
    {
        $defs = $this->content[] = new stdClass();
        $defs->name = 'linearGradient';
        $defs->attr = $attr;
        $defs->haveBody = TRUE;
    }


    /**
     * Затваряне на дефиниции за линеен градиент
     */
    public function closeGradient()
    {
        $groupEnd = $this->content[] = new stdClass();
        $groupEnd->name = '/linearGradient';
    }


    /**
     * Задаване на стъпка от градиента
     */
    public function addStop($attr = array())
    {
        $defs = $this->content[] = new stdClass();
        $defs->name = 'stop';
        $defs->attr = $attr;
    }


 	
    /**
     * Връща XML текста на SVG чертежа
     */
    public function render()
    {
        // Параметрите на viewbox
        $this->addX = -$this->minX + $this->paddingLeft;
        $this->addY = -$this->minY + $this->paddingTop;
 
		$left   = 0;
		$top    = 0;
        $right  = $this->maxX -$this->minX + $this->paddingRight + $this->paddingLeft;
        $bottom = $this->maxY -$this->minY + $this->paddingBottom + $this->paddingTop;
 
        // Динамично изчислените размери на страницата
		$width  = $right - $left ;
		$height = $bottom - $top ;
 
        if(!Mode::is('svgScale')) {
            // Размерите в mm
            $widthSvg  = ($width  / $this->pixPerMm) . 'mm';
            $heightSvg = ($height / $this->pixPerMm) . 'mm';
            $add = " width=\"{$widthSvg}\" height=\"{$heightSvg}\" ";
        } else {
           $add = "style=\"width:100%; height:100%; position: absolute;\"";
        }

 		$res .= "<svg {$add} viewBox=\"{$left} {$top} {$width} {$height}\"" .
                "\n xmlns:inkscape=\"http://www.inkscape.org/namespaces/inkscape\"  " .
                "\n version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n";

        // Генериране на съдържанието
        if(is_array($this->content)) {
            foreach($this->content as $tag) {
                $res .= $this->getXML($tag);
            }
        }
        
        $res .= "</svg>\n";

        return $res;
    }


    /**
     * Връща XML текста, съответстващ на обекта - таг
     */
    private function getXML($tag)
    {
        if($tag->name) {
            
            list($aX, $aY) = array($this->addX, $this->addY);

            if($tag->name == 'path') {
                if(!$tag->data) {
                    $tag->data = array();
                }
                foreach($tag->data as $cmd) {
                    
                    $cmdName = $cmd[0];

                    if($cmdName == 'L' || $cmdName == 'M') { 
                        $cmd[1] += $aX;
                        $cmd[2] += $aY; 
                    } elseif($cmdName == 'C') {
                        $cmd[1] += $aX;
                        $cmd[2] += $aY;
                        $cmd[3] += $aX;
                        $cmd[4] += $aY;
                        $cmd[5] += $aX;
                        $cmd[6] += $aY;
                    } 
 
                    if(count($cmd) == 7) {
                        $tag->attr['d'] .= " {$cmdName}{$cmd[1]},{$cmd[2]} {$cmd[3]},{$cmd[4]} {$cmd[5]},{$cmd[6]}";
                    } elseif(count($cmd) == 3 ) {
                        $tag->attr['d'] .= " {$cmdName}{$cmd[1]},{$cmd[2]}";  
                    } elseif(count($cmd) == 1 ) {
                        $tag->attr['d'] .= " {$cmdName}";
                    }
                }
            }

            if($tag->name == 'text') {
                list($aX, $aY) = array($this->addX, $this->addY);

                $tag->attr['x'] += $aX;
                $tag->attr['y'] += $aY;
            }

            if($tag->name == 'g') {

                if(is_array($tag->attr['_transform'])) {
                    foreach($tag->attr['_transform'] as $tArr) {
                        switch($tArr[0]) {
                            case 'scale':
                                if(!isset($tArr[2])) {
                                    $tArr[2] = $tArr[1];
                                }
                                list($tX, $tY) = array($tArr[1], $tArr[2]);
                                $tag->attr['transform'] .= "scale($tX, $tY) ";
                                break;
                            case 'translate':
                                list($tX, $tY) = self::toPix($tArr[1], $tArr[2]);
                                $tag->attr['transform'] .= "translate($tX, $tY) ";
                                break;

                            case 'rotate':
                                if(!isset($tArr[2])) {
                                    $tArr[3] = $tArr[2] = 0;
                                }
                                list($tX, $tY) = self::toPix($tArr[2], $tArr[3]);
                                $tag->attr['transform'] .= "rotate($tArr[1], $tX, $tY) ";
                                break;
                            default:
                                // Неподдържана трансформация
                                expect(FALSE, $tArr[0]);
                        }
                    }

                    unset($tag->attr['_transform']);
                }

                if(is_array($tag->transform)) {
                    
                    list($width, $height, $rotation, $x, $y) = $tag->transform;
                    
                    list($aX, $aY) = array($this->addX, $this->addY);

                    $x += $aX;
                    $y += $aY;

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
                    
                    $tag->attr['transform'] = "matrix($a, $b, $c, $d, ".(-$x*$a+$y*$b+$x).", ".(-$x*$b-$y*$a+$y).")";
                }
            }


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
                    		if($val != 'none') {
	                            list($a, $b) = explode(',', trim($val));
	                            $vals = self::toPix($a, $b);
	                            $val  = implode(',', $vals);
                        	}
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
     * Преобразува именован цвят цвят към hex
     */
    function getHex($name)
    {

        if($color = color_Object::getNamedColor($hexColor)) {
            $name = $color;
        }

        return $name;
    }


    /**
     * Преобразува hex цвят към CMYK
     */
    function getCmyk($hexColor)
    {

        $rgb = color_Object::hexToRgbArr($hexColor);

        if(!is_array($rgb)) return FALSE;

        $r = $rgb[0];
        $g = $rgb[1];
        $b = $rgb[2];
  
        $cyan    = 255 - $r;
        $magenta = 255 - $g;
        $yellow  = 255 - $b;
        $black   = min($cyan, $magenta, $yellow);
        $cyan    = sDiv(($cyan - $black), (255 - $black)) * 255;
        $magenta = sDiv(($magenta - $black), (255 - $black)) * 255;
        $yellow  = sDiv(($yellow  - $black), (255 - $black)) * 255;
      
        $res = array($cyan/255, $magenta/255, $yellow/255, $black/255);

        return implode(',', $res);
    }

    
}