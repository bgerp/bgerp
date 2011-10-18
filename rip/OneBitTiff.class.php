<?php


/**
 * Конвертиране към еднобитов TIFF
 */
class rip_OneBitTiff
{
	
	
	var $interfaces = 'rip_FileProcessingIntf'; 
	
	
	/**
	 * Конвертира файловете в еднобитов tif
	 */
	function processFile($fileId, $id)
	{
		$process = cls::get('rip_Process');
		$fh = $process->getFh($fileId);
		$clicheSize = $process->getSize($fileId);
		$outName = $process->newName($fh, 'obt');
		
		$script = new fconv_Script();
		$outPath = $script->tempDir . $outName;
		$script->setFile('INPUTF', "{$fh}");
    	$script->setFile('OUTF', "{$outPath}");
    	$script->lineExec("gm convert [#INPUTF#] -density 2400 -monochrome -compress LZW [#OUTF#]");
    	$script->callBack('rip_Process::copyFiles');
    	$script->outFileName = $outName;
    	$script->inFileName = $process->getFileName($fh);
    	$script->currentDir = rip_Directory::getCurrent();
    	$script->fileId = $fileId;
    	$script->processId = $id;
    	$script->clicheSize = $clicheSize;
    	$script->run();
	}
}