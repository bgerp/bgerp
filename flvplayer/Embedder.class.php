<?php

defIfNot('FLVPLAYER_PATH', sbf("flvplayer/1.6.0/player_flv_maxi.swf"));

/**
 * 
 * Генерира необходимият код за плейване на flv файлове
 * @author mitko
 *
 */
class flvplayer_Embedder
{
	function render($flvFile, $width, $height, $startImage, $params=array())
	{
		$swfObj = cls::createObject('swf_Object');
		
		$swfObj->setSwfFile(FLVPLAYER_PATH);
		
		$html = new ET("<a href='[#altVideoFile#]'" .
						"style='background-color: black;'>" .
						"<img src='[#startImage#]' width=[#width#] height=[#height#]></a>
					");

		$flashvars = array(
		     'flv' => $flvFile,
			 'startimage' => $startImage,
			 'width' => $width,
			 'height'=> $height
		);
		
		$swfObj->setAlternativeContent($html);
		$swfObj->setWidth($width);
		$swfObj->setHeight($height);
		$swfObj->setFlashvars($flashvars);
		
		return $swfObj->getContent();
	}	
}
