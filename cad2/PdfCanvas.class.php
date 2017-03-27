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


    // именовани цветове
    public $colorNames = array(
        '#0000fe' => 'MeasureLine',
        '#020301' => 'ContourDark',
        '#ABAAAC' => 'ContourLight',
        '#000301' => 'InnerLineDark',
        '#EAEEEF' => 'InnerLineLight',
        '#606100' => 'FoldingLineDark',
        '#feff9e' => 'FoldingLineLight',
        '#735858' => 'PatternLineDark',
        '#cebfbe' => 'PatternLineLight',
        '#FDFED7' => 'LegendFill'
        );

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
    public function writeText($x, $y, $text, $rotation = 0, $absolute = TRUE, $link = NULL)
    {   
        $this->toAbs($x, $y, $absolute);
        $e = (object) array('tag' => 'text', 'x' => $x, 'y' => $y, 'rotation' => $rotation, 'text' => $text, 'attr' => $this->attr, 'link' => $link);

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
    public function openTransform($trans = array(), $attr = array())
    {
        $this->closePath();
        $attr['_transform'] = $trans;
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
 
        defIfNot('PDF_FONT_NAME_MAIN', 'dejavuserifcondensed');

        defIfNot('PDF_FONT_NAME_DATA', 'dejavuserifcondensed');

 
        // create new PDF document
        $this->pdf = new tcpdf_Instance('', 'mm', array($width, $height), TRUE, 'UTF-8', FALSE);

        // set document information
        $this->pdf->SetCreator('bgERP');
        $this->pdf->SetAuthor(core_Users::getCurrent('names'));
        $this->pdf->SetTitle('CadProduct');
        $this->pdf->SetSubject('TCPDF Tutorial');
        $this->pdf->SetKeywords('Layout');
        $this->pdf->setPrintHeader(FALSE);
        $this->pdf->setPrintFooter(FALSE);
        $this->pdf->SetAutoPageBreak(FALSE);
        
        // set default font subsetting mode
        $this->pdf->setFontSubsetting(FALSE);
        

        // set font
        $this->pdf->AddFont('dejavuserifcondensed');
        $this->pdf->SetFont('dejavuserifcondensed');
 

        $this->pdf->StartPage();

        foreach($this->contents as $e) {
            $func = 'do' . $e->tag;
            $this->{$func}($e);
        }

        $res = $this->pdf->getPDFData();
        $this->pdf->endPage();

        return $res;
    }



    /**
     * Чертае път в PDF-а, според данните в елемента
     */
    function doPath($e)
    {   
        $this->pdf->StartTransform();
        $this->pdf->Rotate(0, 0, 0);

        $this->pdf->SetLineStyle(array(
                'width' => $e->attr['stroke-width'],
                'cap' => $e->attr['stroke-linecap'],
                'join' => 'bevel',
                'dash' => $e->attr['stroke-dasharray'],
                'color' => self::hexToCmyk($e->attr['stroke'], array(0, 0, 0, 100))));
     

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

            if($d[0] == 'M' && !$start) {
                $start = TRUE;
                $startX = $d[1];
                $startY = $d[2];
            }
        }
 
        if($e->close) {
            if($fill == 'FD') {
                $fill = 'fd';
            }
            if($fill == 'S') {
                $fill = 's';
            }
        } else {
            if($start) {
                $e->data[] = array('M', $startX, $startY);
            }
        }

        $this->pdf->drawPath($e->data, $fill, array(), $fillColor);

        $this->pdf->StopTransform();
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
        if(is_array($e->attr['_transform'])) {
            foreach($e->attr['_transform'] as $tArr) {
                switch($tArr[0]) {
                    case 'scale':
                        if(!isset($tArr[2])) {
                            $tArr[2] = $tArr[1];
                        }
                        $this->pdf->scale($tArr[1]*100, $tArr[1]*100, 0, 0); 
                        break;
                    case 'translate': 
                        $this->pdf->translate($tArr[1], $tArr[2]); 
                        break;

                    case 'rotate':
                        if(!isset($tArr[2])) {
                            $tArr[3] = $tArr[2] = 0;
                        }
                        $this->pdf->rotate(-$tArr[1], $tArr[2], $tArr[3]); 
                        break;
                    default:
                        // Неподдържана трансформация
                        expect(FALSE, $tArr[0]);
                }
            }
            unset($e->attr['_transform']);
        }
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
        $attr = $e->attr;

        $e->rotation = -$e->rotation;
        
        if($e->rotation) {
          //  $e->x -= 2;
          //  $e->y += 3;
        }

        $this->pdf->StartTransform();
        $this->pdf->Rotate(0, 0, 0);

        if($e->rotation) {
            $this->pdf->Rotate($e->rotation, $e->x + $this->addX, $e->y + $this->addY);
        }
        
        $size = $attr['font-size']/3.75;
 

        $e->y -= 0.35 * $size;

        list($font, ) = explode(',', $attr['font-family']);
        
        $font = trim('dejavusans');

        $style = '';
        if($attr['font-weight'] == 'bold') {
            $style = 'B';
        }
         if($attr['font-weight'] == 'italic') {
            $style = 'I';
        }

        $this->pdf->SetFont($font, $style,  $attr['font-size']/3.5, '', FALSE);
        
        $opacity = $attr['text-opacity'];
        if(!$opacity) {
            $opacity = 1;
        }
        $this->pdf->SetAlpha($opacity);

        if($color = self::hexToCmyk($attr['text-color'], array(0, 0, 0, 100))) {
            $this->pdf->SetTextColorArray($color);
        }

        $this->pdf->Text($e->x + $this->addX, $e->y + $this->addY, $e->text, FALSE, FALSE, TRUE, 0, 0, '', FALSE, $e->link);

        $this->pdf->StopTransform();
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
     * Преобразува hex цвят към CMYK
     */
    function hexToCmyk($hexColor, $default = array(0, 0, 0, 0))
    {
        if($hexColor == 'transparent' || $hexColor == 'none' || $hexColor === NULL || $hexColor === '') {
            return $default;
        }
 
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
      
        $res = array($cyan/2.55, $magenta/2.55, $yellow/2.55, $black/2.55);

        if($name = $this->colorNames[$hexColor]) {
            $res[4] = $name;
        }

        return $res;
    }


}