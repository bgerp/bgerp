<?php


/**
 * 
 * 
 * @category  vendors
 * @package   jqplot
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class jqplot_Setup extends core_ProtoSetup
{
	
	
	/**
	 * Връща CSS файлове за компактиране
	 * 
	 * @see core_ProtoSetup::getCommonCss()
	 * 
	 * @return string
	 */
	function getCommonCss()
	{
	    $files = parent::getCommonCss();
	    
	    if ($files) {
	        $files .= ', ';
	    }
	    
	    $files .= jqplot_Chart::resource('jquery.jqplot.css');
	    
	    return $files;
	}
	
	
	/**
	 * Връща JS файлове за компактиране
	 * 
	 * @see core_ProtoSetup::getCommonJs()
	 * 
	 * @return string
	 */
	function getCommonJs()
	{
	    $files = parent::getCommonJs();
	    
	    if ($files) {
	        $files .= ', ';
	    }
	    
	    $files .= jqplot_Chart::resource('jquery.jqplot.js');
	    $files .= ', ' . jqplot_Chart::resource('excanvas.js');
	    
	    return $files;
	}
}
