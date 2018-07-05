<?php

/**
 * Вектор
 */
class cad2_Vector
{
    public function __construct($x, $y, $type = 'cartesian', $angleUnit = 'rad')
    {
        if ($type == 'polar') {
            if ($angleUnit != 'rad') {
                $x = deg2rad($x);
            }
            $this->x = $y * cos($x);
            $this->y = $y * sin($x);
            $this->a = $x;
            $this->r = $y;
        } else {
            $this->x = $x;
            $this->y = $y;
            $this->a = $this->getA($x, $y);
            $this->r = sqrt($this->x * $this->x + $this->y * $this->y);
        }
    }

    private function getA($x, $y)
    {
        if ($x == 0 && $y == 0) {
            
            return 0;
        }

        if ($x == 0) {
            if ($y > 0) {
                
                return pi() / 2;
            }

            return pi() + pi() / 2;
        }

        if ($y == 0) {
            if ($x > 0) {
                
                return 0;
            }

            return pi();
        }

        $a = atan(abs($y / $x));

        if ($x > 0 && $y > 0) {
            
            return $a;
        }

        if ($x < 0 && $y > 0) {
            
            return pi() - $a;
        }

        if ($x < 0 && $y < 0) {
            
            return pi() + $a;
        }

        return 2 * pi() - $a;
    }


    public function neg()
    {
        return new cad2_Vector(-$this->x, -$this->y);
    }


    public function add($v)
    {
        return new cad2_Vector($this->x + $v->x, $this->y + $v->y);
    }
}
