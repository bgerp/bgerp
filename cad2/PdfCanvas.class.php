<?php

defIfNot('CAD2_MAX_CANVAS_SIZE', 1000);

/**
 *
 */
class cad2_PdfCanvas extends cad2_Canvas {
    
    /**
     * Текуща точка
     */
    var $cX;
    var $cY;
    
    public $pageStyle = array();
 

    /**
     * Масив с XML обекти - тагове
     */
	var $contents = array();
    
    
    /**
     * Текущи атрибути на лементите
     */
    var $attr = array();
    var $alowedAttributes = array('stroke', 'stroke-width', 'stroke-opacity', 'stroke-dasharray', 'stroke-linecap', 'fill', 'fill-opacity', 'fill-rule', 'font-size', 'font-weight', 'font-family', 'text-color');


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
     * Текущи параметри на молива, запълването и шрифта
     * stroke, stroke-width, stroke-linecap, stroke-dasharray
     * fill-color, fill-opacity
     * font-face, font-size, font-weight
     */
	function __construct()
    {   
	}


    /**
     * Задава размерите и падингите на хартията
     */
    public function setPaper($width = 210, $height = 297, $paddingTop = 10, $paddingRight = 10, $paddingBottom = 10, $paddingLeft = 10)
    {
        // Задаваме размерите и отстъпите на страницата
        list($this->width, 
             $this->height,
             $this->paddingTop,
             $this->paddingRight,
             $this->paddingBottom,
             $this->paddingLeft,
            ) = array($width, $height, $paddingTop, $paddingRight, $paddingBottom, $paddingLeft);

        $this->fitPoint($this->width, $this->height);
        $this->fitPoint(0, 0);
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
     * Задава текуща стойност на посочения атрибит
     */
    public function setAttr($name, $value = NULL)
    {
        if(is_array($name)) {
            foreach($name as $n => $v) {
                $this->setAttr($n, $v);
            }

            return;
        }
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
        $this->closePath();
        $this->setAttr($attr);
        $e = (object) array('tag' => 'path', 'attr' => $this->attr, 'data' => array());
        $this->contents[] = $e;
        $this->currentPath = count($this->contents) -1;
	}
    
    
    /**
     * Затваря текущия път или под-път
     */
	public function closePath($close = TRUE)
    {
        if($this->currentPath !== NULL) {
            $this->contents[$this->currentPath]->close = $close;
            $this->currentPath = NULL;
        }
	}


    /**
     * Премества текущата позиция на посочените координати
     * без да рисува линия
     */
	public function moveTo($x, $y, $absolute = FALSE)
    {   
        if($this->currentPath === NULL) {
            $this->startPath();
        }

        $this->toAbs($x, $y, $absolute);
        
        $path = $this->contents[$this->currentPath];

        $path->data[] = array('M', $x, $y);

        $this->fitPoint($x, $y);

        $this->setCP($x, $y);
	}


    /**
     * Рисува линия до посочените координати
     */
	public function doLineTo($x, $y, $absolute = FALSE)
    {
        if($this->currentPath === NULL) {
            $this->startPath();
        }

        $path = $this->contents[$this->currentPath];

        $this->toAbs($x, $y, $absolute);

        $path->data[] = array('L', $x, $y);

        $this->fitPoint($x, $y);

        $this->setCP($x, $y);
	}


    /**
     * Изчертава крива на Безие с посочените координати
     */
	public function curveTo($x1, $y1, $x2, $y2, $x, $y, $absolute = FALSE)
    {
        if($this->currentPath === NULL) {
            $this->startPath();
        }

        $path = $this->contents[$this->currentPath];

        $this->toAbs($x, $y, $absolute);
        $this->toAbs($x1, $y1, $absolute);
        $this->toAbs($x2, $y2, $absolute);

        $path->data[] = array('C', $x1, $y1, $x2, $y2, $x, $y);

        $this->fitPoint($x, $y);

        $this->setCP($x, $y);
	}


    /**
     * Изписва текст
     */
    public function writeText($x, $y, $text, $rotation = 0, $absolute = TRUE)
    {   
        $y -= 3.5;
        $x -= 1;
        $rotation = -$rotation;
        if($rotation) {
            $x -= 2;
            $y += 3;
        }
        $this->toAbs($x, $y, $absolute);
        $e = (object) array('tag' => 'text', 'x' => $x, 'y' => $y, 'rotation' => $rotation, 'text' => $text, 'attr' => $this->attr);

        $this->fitPoint($x, $y);

        $this->contents[] = $e;

	}




    /**
     * Отваря нова група
     */
    public function openGroup($attr = array())
    {
        $this->closePath();
        $e = (object) array('tag' => 'openGroup', 'attr' => $attr);
        $this->contents[] = $e;
	}
    

    /**
     * Затваряне на група
     */
	public function closeGroup()
    {
        $this->closePath();
        $e = (object) array('tag' => 'closeGroup');
        $this->contents[] = $e;
	}


    /**
     * Отваря нова група
     */
    public function openTransform($attr = array())
    {
        $this->closePath();
        $e = (object) array('tag' => 'openTransform', 'attr' => $attr);
        $this->contents[] = $e;
    }


    /**
     * Затваряне на група
     */
    public function closeTransform()
    {
        $this->closePath();
        $e = (object) array('tag' => 'closeTransform');
        $this->contents[] = $e;
    }


    /**
     * Отваря нов слой
     */
    public function openLayer($name = NULL)
    {
        $this->closePath();
        $e = (object) array('tag' => 'openLayer', 'name' => $name);
        $this->contents[] = $e;
    }


    /**
     * Затваряне на слой
     */
    public function closeLayer()
    {
        $this->closePath();
        $e = (object) array('tag' => 'closeLayer');
        $this->contents[] = $e;
    }
	
	/**
	 * Отваря новa шарка
	 */
	public function openPattern($attr = array())
	{
	}
	
	
	/**
	 * Затваряне на шарка
	 */
	public function closePattern()
	{
	}
	
	
	/**
	 * Отваряне на дефиниции
	 */
	public function openDefinitions($attr = array())
	{
	}

	
	/**
	 * Затваряне на дефиниции
	 */
	public function closeDefinitions()
	{
	}


    /**
     * Отваряне на дефиниции за линеен градиент
     */
    public function openGradient($attr = array())
    {
    }


    /**
     * Затваряне на дефиниции за линеен градиент
     */
    public function closeGradient()
    {
    }


    /**
     * Задаване на стъпка от градиента
     */
    public function addStop($attr = array())
    {
    }


 	
    /**
     * Връща XML текста на SVG чертежа
     */
    public function render()
    {   
        $width  = $this->maxX - $this->minX + $this->paddingLeft + $this->paddingRight;
        $height = $this->maxY - $this->minY + $this->paddingTop + $this->paddingBottom;
        
        $this->addX = $this->paddingLeft - $this->minX;
        $this->addY = $this->paddingTop  - $this->minY;
 
 
        // create new PDF document
        $this->pdf = new tcpdf_Instance('', 'mm', array($width, $height), true, 'UTF-8', false);

        // set document information
        $this->pdf->SetCreator('bgERP');
        $this->pdf->SetAuthor(core_Users::getCurrent('names'));
        $this->pdf->SetTitle('CadProduct');
        $this->pdf->SetSubject('TCPDF Tutorial');
        $this->pdf->SetKeywords('TCPDF, PDF, example, test, guide');
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->AddPage();
        $this->pdf->SetAutoPageBreak(FALSE);

        // set default monospaced font
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set default font subsetting mode
        $this->pdf->setFontSubsetting(FALSE);

        // set font
        $this->pdf->AddFont('dejavusans');

        $this->pdf->SetFont('dejavusans', '', 12);

        foreach($this->contents as $e) {
            $func = 'do' . $e->tag;
            $this->{$func}($e);
        }

        $res = $this->pdf->getPDFData();

        return $res;
    }



    /**
     * Чертае път в PDF-а, според данните в елемента
     */
    function doPath($e)
    {   
        $this->pdf->SetLineStyle(array(
                'width' => $e->attr['stroke-width'],
                'cap' => $e->attr['stroke-linecap'],
                'join' => 'bevel',
                //'dash' => $e->attr['stroke-dasharray'],
                'color' => self::hexToCmyk($e->attr['stroke'])));
        
        $fillColor = NULL;
        
        $fill = 'S';
 
        if($e->attr['fill'] != 'transparent' && $e->attr['fill'] != 'none') {
            
            $fill = 'FD';  
 
            if(isset($e->attr['fill'])) {
                $fillColor = self::hexToCmyk($e->attr['fill']);
            }
            
        }

        if($e->attr['fill'] == 'transparent' || $e->attr['fill'] == 'none') {
            $fillOpacity = 0;
        } elseif(isset($e->attr['fill-opacity'])) {
            $fillOpacity = (float) $e->attr['fill-opacity'];
        } else {
            $fillOpacity = 1;
        }

        if($e->attr['stroke'] == 'transparent' || $e->attr['stroke'] == 'none') {
            $strokeOpacity = 0;
        } elseif(isset($e->attr['stroke-opacity'])) {
            $strokeOpacity = (float) $e->attr['stroke-opacity'];
        } else {
            $strokeOpacity = 1;
        }

        $this->pdf->SetAlpha($strokeOpacity, 'Normal', $fillOpacity);
      
        foreach($e->data as &$d) {
            $d[1] += $this->addX;
            $d[2] += $this->addY;
            if(count($d) > 3) {
                $d[3] += $this->addX;
                $d[4] += $this->addY;
                $d[5] += $this->addX;
                $d[6] += $this->addY;
            }

            if($d[0] == 'M') {
                $startX = $d[1];
                $startY = $d[2];
                $start++;
            }
        }

        if($e->close && $start == 1) {
            $e->data[] = array('L', $startX, $startY);
        }
 
        if($e->close) {
            if($fill == 'FD') {
                $fill = 'fd';
            }
        }

        $this->pdf->drawPath($e->data, $fill, array(), $fillColor);
        $this->pdf->SetAlpha(1);
    }


    /**
     * Отваря група
     */
    function doOpenGroup($e)
    {
        $this->pdf->StartTransform();
    }
    

    /**
     *
     */
    function doCloseGroup($e)
    {
       $this->pdf->StopTransform();
    }


    /**
     * Отваря група
     */
    function doOpenTransform($e)
    {
        $this->pdf->StartTransform();
    }


    /**
     *
     */
    function doCloseTransform($e)
    {
        $this->pdf->StopTransform();
    }


    /**
     * Отваря група
     */
    function doOpenLayer($e)
    {
        $this->pdf->startLayer($e->name);
    }


    /**
     *
     */
    function doCloseLayer($e)
    {
        $this->pdf->endLayer();
    }


    /**
     * Показва текста в PDF-а, както е указан в елемента
     */
    function doText($e)
    {
        if($e->rotation) {
            $this->pdf->StartTransform();
            $this->pdf->Rotate($e->rotation, $e->x + $this->addX, $e->y + $this->addY);
        }
        
        $attr = $e->attr;
        
        list($font, ) = explode(',', $attr['font-family']);
        
        $font = trim('dejavusans');
 
        $this->pdf->SetFont($font, $attr['font-weight'] == 'bold' ? 'B' : '', $attr['font-size']/4);

        if($color = self::hexToCmyk($attr['text-color'], array(100,100,100,100))) {
            $this->pdf->SetTextColorArray($color);
        }

        $this->pdf->Text($e->x + $this->addX, $e->y + $this->addY, $e->text);

        if($e->rotation) {
            $this->pdf->StopTransform();
        }
    }


    /**
     * Връща текущата позиция
     */
    function getCP()
    {
        return array($this->cX, $this->cY);
    }


    /**
     * Връща текущата позиция
     */
    function setCP($x, $y)
    {
        $this->cX = $x;
        $this->cY = $y;
    }


    /**
     * Ако е необходимо, конвертира към абсолютни стойности дадената точка
     */
    function toAbs(&$x, &$y, $absolute)
    {
        if(!$absolute) {
            $x += $this->cX;
            $y += $this->cY;
        }

        $x = round($x, 6);
        $y = round($y, 6);
    }
    

    /**
     * Преобразува hex цвят към RGB
     */
    function hexToRgb($hexColor)
    {

        if($color = color_Object::getNamedColor($hexColor)) {
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
     * Преобразува hex цвят към CMYK
     */
    function hexToCmyk($hexColor, $default = array(0, 0, 0, 0))
    {
        if($hexColor == 'transparent' || $hexColor == 'none' || $hexColor === NULL || $hexColor === '') {
            return $default;
        }

        $rgb = self::hexToRgb($hexColor);

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
      
        return array($cyan/2.55, $magenta/2.55, $yellow/2.55, $black/2.55);
    }


}