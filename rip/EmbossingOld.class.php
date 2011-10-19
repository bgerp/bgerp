<?php


/**
 * Ембосиране / старо
 */
class rip_EmbossingOld
{
	
	
	var $interfaces = 'rip_FileProcessingIntf'; 
	
	
	/**
	 * Ембосира тифовете
	 */
	function processFile($fileId, $id, $dirId)
	{
		$process = cls::get('rip_Process');
		$fh = $process->getFh($fileId);
		$clicheSize = $process->getSize($fileId);
		$outName = $process->newName($fh, 'embossedOld');
		ini_set('memory_limit', '2000M');
		
		$script = new fconv_Script();
		$outPath = $script->tempDir . $outName;
		$script->setFile('INPUTF', "{$fh}");
    	$script->setFile('OUTF', "{$outPath}");
    	$script->lineExec("/usr/local/bin/tiff-convert [#INPUTF#] [#OUTF#]");
    	$script->callBack('rip_Process::copyFiles');
    	$script->outFileName = $outName;
    	$script->inFileName = $process->getFileName($fh);
    	if ($dirId) {
    		$script->currentDir = $dirId;
    	} else {
    		$script->currentDir = rip_Directory::getCurrent();
    	}
    	$script->fileId = $fileId;
    	$script->processId = $id;
    	$script->clicheSize = $clicheSize;
    	$script->run();
	}
}