<?php

defIfNot('CAD2_MAX_CANVAS_SIZE', 1000);

/**
 *
 */
abstract class cad2_Canvas extends core_BaseClass {
    

    /**
     * Текущи атрибути на лементите
     */
    public $attr = array();


    /**
     * Допустими имена на атрибути
     */
    protected $alowedAttributes = array('stroke', 'stroke-width', 'stroke-opacity', 'stroke-dasharray', 'stroke-linecap', 
        'fill', 'fill-opacity', 'fill-rule', 'font-size', 'font-weight', 'font-family', 'text-color', 'stroke-color-name', 'fill-color-name', 'text-color-name');


    /**
     * Масив със 'режещи' отсечки
     */
    private $cuttingSegments = array();


    /**
     * Флаг, дали записваме режещите отсечки
     */
    private $saveCuttingSegments = FALSE;

    
    /**
     * Параметри за чертане
     */
	public $p = array();


    /**
     * Задава размера на страницата
     */
	abstract public function setPaper($width = 210, $height = 297, $paddingTop = 10, $paddingRight = 10, $paddingBottom = 10, $paddingLeft = 10);


    /**
     * Връща текущата позиция, в мм
     */
    abstract public function getCP();


    /**
     * Започва нов път (поредица от линии и премествания)
     */
	abstract public function startPath($attr = array());


    /**
     * Премества текущата позиция на посочените координати
     * без да рисува линия
     */
	abstract public function moveTo($x, $y, $absolute = FALSE);


    /**
     * Рисува линия до посочените координати
     */
	abstract public function doLineTo($x, $y, $absolute = FALSE);


    /**
     * Изчертава крива на Безие с посочените координати
     */
	abstract public function curveTo($x1, $y1, $x2, $y2, $x, $y, $absolute = FALSE);


    /**
     * Изписва текст
     */
    abstract public function writeText($x, $y, $text, $rotation = 0, $absolute = TRUE, $link = NULL);
 

    /**
     * Затваря текущия път или под-път
     */
	abstract public function closePath($close = TRUE);
	

    /**
     * Отваря нова група
     */
    abstract public function openGroup($attr = array());
    

    /**
     * Затваряне на група
     */
	abstract public function closeGroup();


    /**
     * Отваря нова група
     */
    abstract public function openTransform($attr = array());


    /**
     * Затваряне на група
     */
    abstract public function closeTransform();


    /**
     * Отваря нов слой
     */
    abstract public function openLayer($name = NULL);


    /**
     * Затваряне на слой
     */
    abstract public function closeLayer();
	
	
	/**
	 * Отваря новa шарка
	 */
	abstract public function openPattern($attr = array());
	
	
	/**
	 * Затваряне на шарка
	 */
	abstract public function closePattern();
	
	
	/**
	 * Отваряне на дефиниции
	 */
	abstract public function openDefinitions($attr = array());

	
	/**
	 * Затваряне на дефиниции
	 */
	abstract public function closeDefinitions();


    /**
     * Отваряне на дефиниции за линеен градиент
     */
    abstract public function openGradient($attr = array());


    /**
     * Затваряне на дефиниции за линеен градиент
     */
    abstract public function closeGradient();


    /**
     * Задаване на стъпка от градиента
     */
    abstract public function addStop($attr = array());
    
    /**
     * Задава текуща стойност на посочения атрибит
     */
    abstract public function setAttr($name, $value);
 

    /**
     * Връща стойността на посочения атрибут
     */
    abstract public function getAttr($name);
 	

    /**
     * Връща XML текста на SVG чертежа
     */
    abstract public function render();
	

    /*=================================================================================================
    /*
    /* ПОМОЩНИ ФУНКЦИИ
    /*
    /**************************************************************************************************
    
    /**
     * Предефинираме lineTo, за да записваме режещите отсечки
     */
    public function lineTo($x, $y, $absolute = FALSE)
    {
        if($this->saveCuttingSegments) {
            $s = new stdClass();
            list($s->Ax, $s->Ay) = $this->getCP();
        }

        $this->doLineTo($x, $y, $absolute);
        
        if($this->saveCuttingSegments) {
            list($s->Bx, $s->By) = $this->getCP();
            $this->cuttingSegments[] = $s;
        }
    }
 

    /**
     * Задаваме, че следват режещи отсечки
     */
    function startCuttingSegments()
    {
        $this->saveCuttingSegments = TRUE;
    }


    /**
     * Задаваме, че режещите отсечки спират
     */
    function stopCuttingSegments()
    {
        $this->saveCuttingSegments = FALSE;
    }

    
    /**
     * Дадена е отсечка АБ. Тази сънкция връща $y-ка в точката $x за тази отсечка.
     */
    static function getYatX($Ax, $Ay, $Bx, $By, $x)
    {
        // функцията не е детерминирана за вертикални отсечки
        if($Ax == $Bx) return FALSE;

        // Условие за пресичане
        if( min($Ax,$Bx) > $x || $x > max($Ax, $Bx)) return FALSE;
        
        // Коефициент `a`
        $a = ($Ay-$By) / ($Ax-$Bx);

        // Коефициент `b`
        $b = ($Ax*$By - $Ay*$Bx) / ($Ax-$Bx);

        $y = $a * $x + $b;

        return $y;
    }


    /**
     * Връща пресечните точки на режащата отсечка в дадения $x
     */
    function getCuttingIntersection($x1, $y1, $x2, $y2)
    {   
        $res = array();
        $last = FALSE;
        foreach($this->cuttingSegments as $l) {
            if($p = self::getIntersection($l->Ax, $l->Ay, $l->Bx, $l->By, $x1, $y1, $x2, $y2)) {
                $a = self::d($x1, $y1);
                $b = $a->add($p->neg());
                $p->dist = $b->r;

                if(isset($lastP) && round($lastP->x,2) == round($p->x,2) && round($lastP->y,2) == round($p->y,2)) continue;
                
                $p->baseX = $x1;
                $p->baseY = $y1;

                $res[] = $p;

                $lastP = clone($p);
            }
        }
  
        usort($res, 'eprod_Svg::cmpRadius');

        return $res;
    }
    
    
    /**
     * Помощна функция за сравняване
     */
    private static function cmpRadius($a, $b)
    {
        return pow($a->x - $a->baseX, 2) + pow($a->y - $a->baseY, 2) > pow($b->x - $b->baseX, 2) + pow($b->y - $b->baseY, 2);
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

        $A = self::d($x0, $y0);
        $B = self::d($x1, $y1);
        $C = self::d($x, $y);

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
     * Изчертава арка
     */
    function arcTo($x1, $y1, $r, $absolute = FALSE)
    {
    	cad2_ArcTo::draw($this, $x1, $y1, $r, $absolute);
    }
    
    
    /**
     * Задаване на начупена линия
     */
    function jaggedLineTo($x1, $y1, $md = 1, $td = NULL, $spacer = 0, $absolute = FALSE)
    {
    	cad2_JaggedLine::draw($this, $x1, $y1, $md, $td, $spacer, $absolute);
    }


 	/**
	 * Преобразуване от градуси към радиани
	 */
	function gradusToRad($gradus)
	{
		return $gradus*(pi()/180);
	}
	
	
	/**
	 * Преобразуване от радиани към градуси
	 */
	function radToGradus($rad)
	{
		return $rad*180/pi();
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

    
    
}