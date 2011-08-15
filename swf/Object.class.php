<?php

/**
 *  class swf_Object
 *  
 *	Предоставя възможностите на пакета SWFObject2
 */
class swf_Object extends core_BaseClass {

	var $url;
	
	var $altHTML;
	
	var $width;
	
	var $height;
	
	var $minFlashVersion = '9.0.0';
	
	var $uniqId = 'SWFObject2_AlternativeContent';
	
	
	/**
	 * Задава Url към .swf файла
	 * @param string $url
	 */
	function setSwfFile($url)
	{
		$this->url = $url;
	}
	
	/**
	 * 
	 * Задава алтернативен html, който ще се показва в случай на липса на JS или Flash
	 * @param string $html
	 */
	function setAlternativeContent($html)
	{
		$this->altHTML = $html;
	}
	
	/**
	 * 
	 * Задава ширина 
	 * @param integer $width
	 */
	function setWidth($width)
	{
		$this->width = $width;
	}
	
	/**
	 * 
	 * Задава височина 
	 * @param integer $height
	 */
	function setHeight($height)
	{
		$this->height = $height;
	}
	
	/**
	 * 
	 * Задава минимално изискваната версия на flash
	 * @param string $version
	 */
	function setMinFlashVersion($version)
	{
		$this->minFlashVersion = $version;
	}
	
	/**
	 * 
	 * Задава параметрите, както е показано в документацията на swfobject
	 * @param array $params
	 */
	function setParams($params)
	{
		
	}
	
	/**
	 * 
	 * Задава параметрите, които ще бъдат предадени на обекта
	 * @param array $flashvars
	 */
	function setFlashvars($flashvars)
	{
		
	}
	
	/**
	 * 
	 * Връща шаблон, в който:
	 * в него е включено зареждане на скрипта на swfobject
	 * в началото е алтернативното съдържание, оградено в <div> с уникален id;
	 * javaScript в който се извиква метода на библиотеката
	 */
	function getContent()
	{
		$installSwfPath = sbf('swf/2.2/expressInstall.swf');
		$swfobjectJsPath = sbf('swf/2.2/swfobject.js');
		$tpl = new ET (
		   "<div id=[#uniqId#]>[#altHTML#]</div>
			<script type=\"text/javascript\" src={$swfobjectJsPath}></script>
			<script type=\"text/javascript\">
				swfobject.embedSWF([#url#], \"[#uniqId#]\", \"[#width#]\", \"[#height#]\", \"[#minFlashVersion#]\", {$installSwfPath});
			</script>");
		
		$tpl->placeObject($this);
		
		return $tpl->getContent();
	}
}