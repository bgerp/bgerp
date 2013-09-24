<?php 


/**
 * Помощен мениджър за рендиране на документ
 *
 * @category  vendors
 * @package   color
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class color_Renderer extends core_Manager
{
    

    /**
     * Заглавие на модела
     */
    var $title = 'Рендатор на цветове';
    
    
    /**
     * Рендира html img с определен цвят
     */
    function act_Render()
    {
    	$w = Request::get('w', 'int');
    	$h = Request::get('h', 'int');
    	$r = Request::get('r', 'int');
        $g = Request::get('g', 'int');
        $b = Request::get('b', 'int');
        
        $im = @imagecreate($w, $h);
		$backgroundColor = imagecolorallocate($im, $r, $g, $b);
		imagepng($im);
		imagedestroy($im);
		shutdown();
    }
}