<?php


/**
 * FastImage - Because sometimes you just want the size!
 * Based on the Ruby Implementation by Steven Sykes (https://github.com/sdsykes/fastimage)
 *
 * Copyright (c) 2012 Tom Moor
 * Tom Moor, http://tommoor.com
 *
 * MIT Licensed
 *
 * @version 0.1
 */
class thumb_FastImageSize
{
    private $strpos = 0;
    private $str;
    private $type;
    private $handle;
    
    
    public function __construct($uri = null)
    {
        if ($uri) {
            $this->load($uri);
        }
    }
    
    
    public function load($uri)
    {
        if ($this->handle) {
            $this->close();
        }
        
        $this->handle = fopen($uri, 'r');
    }
    
    
    public function close()
    {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
            $this->type = null;
            $this->str = null;
        }
    }
    
    
    public function getSize()
    {
        $this->strpos = 0;
        if ($this->getType()) {
            $pSize = $this->parseSize();
            
            if (!$pSize || !is_array($pSize)) {
                
                return false;
            }
            
            return array_values($pSize);
        }
        
        return false;
    }
    
    
    public function getType()
    {
        $this->strpos = 0;

        if (!$this->type) {

            switch ($this->getChars(2)) {
                case 'BM':

                    return $this->type = 'bmp';
                case 'GI':

                    return $this->type = 'gif';
                case 'RI':
//                    (substr($chars, 0, 4) !== 'RIFF' || substr($chars, 8, 4) !== 'WEBP')

                    return $this->type = 'webp';
                case chr(0xFF).chr(0xd8):

                    return $this->type = 'jpeg';
                case chr(0x89).'P':

                    return $this->type = 'png';
                default:

                    return false;
            }
        }
        
        return $this->type;
    }
    
    
    private function parseSize()
    {
        $this->strpos = 0;
        
        switch ($this->type) {
            case 'png':
                
                return $this->parseSizeForPNG();
            case 'gif':
                
                return $this->parseSizeForGIF();
            case 'bmp':
                
                return $this->parseSizeForBMP();
            case 'jpeg':
                
                return $this->parseSizeForJPEG();
            case 'webp':

                return $this->parseSizeForWEBP();
        }
    }
    
    
    private function parseSizeForPNG()
    {
        $chars = $this->getChars(25);
        
        return unpack('N*', substr($chars, 16, 8));
    }


    private function parseSizeForWEBP()
    {
        // Прочитаме първите 30 байта от файла
        $chars = $this->getChars(30);

        // Проверяваме дали файлът е валиден WebP файл с "RIFF" и "WEBP" идентификатори
        if (substr($chars, 0, 4) !== 'RIFF' || substr($chars, 8, 4) !== 'WEBP') {

            return false;
        }

        // Идентифицираме типа на WebP файла
        $type = substr($chars, 12, 4);
        if ($type === 'VP8 ') {
            // VP8 формат
            // Размерите се намират на позиции 26-27 (ширина) и 28-29 (височина)

            return array( unpack('v', substr($chars, 26, 2))[1], unpack('v', substr($chars, 28, 2))[1] ); }
        elseif ($type === 'VP8L') {
            // VP8L формат
            // Размерите са част от 14-ия байт
            $b = unpack('C', substr($chars, 21, 1))[1]; $width = 1 + (($b & 0x3F) | ((unpack('C', substr($chars, 22, 1))[1] & 0xFF) << 6) | ((unpack('C', substr($chars, 23, 1))[1] & 0xFF) << 14)); $height = 1 + (((unpack('C', substr($chars, 23, 1))[1] & 0xF0) >> 4) | ((unpack('C', substr($chars, 24, 1))[1] & 0xFF) << 4) | ((unpack('C', substr($chars, 25, 1))[1] & 0xFF) << 12));

            return array($width, $height);
        } elseif ($type === 'VP8X') {
            // VP8X формат
            // Размерите се намират на позиции 24-25 (ширина) и 26-27 (височина)

            return array( unpack('V', substr($chars, 24, 3) . "\0")[1] + 1, unpack('V', substr($chars, 27, 3) . "\0")[1] + 1 );
        }

        return false;
    }

    
    private function parseSizeForGIF()
    {
        $chars = $this->getChars(11);
        
        return unpack('S*', substr($chars, 6, 4));
    }
    
    
    private function parseSizeForBMP()
    {
        $chars = $this->getChars(29);
        $chars = substr($chars, 14, 14);
        $type = unpack('C', $chars);
        
        return (reset($type) == 40) ? unpack('L*', substr($chars, 4)) : unpack('L*', substr($chars, 4, 8));
    }
    
    
    private function parseSizeForJPEG()
    {
        $state = null;
        $i = 0;
        while (true) {
            switch ($state) {
                default:
                    $this->getChars(2);
                    $state = 'started';
                    break;
                
                case 'started':
                    $b = $this->getByte();
                    if ($b === false) {
                        
                        return false;
                    }
                    
                    $state = $b == 0xFF ? 'sof' : 'started';
                    break;
                
                case 'sof':
                    $b = $this->getByte();
                    if (in_array($b, range(0xe0, 0xef))) {
                        $state = 'skipframe';
                    } elseif (in_array($b, array_merge(range(0xC0, 0xC3), range(0xC5, 0xC7), range(0xC9, 0xCB), range(0xCD, 0xCF)))) {
                        $state = 'readsize';
                    } elseif ($b == 0xFF) {
                        $state = 'sof';
                    } else {
                        $state = 'skipframe';
                    }
                    break;
                
                case 'skipframe':
                    $skip = $this->readInt($this->getChars(2)) - 2;
                    $state = 'doskip';
                    break;
                
                case 'doskip':
                    $this->getChars($skip);
                    $state = 'started';
                    break;
                
                case 'readsize':
                    $c = $this->getChars(7);
                    
                    return array($this->readInt(substr($c, 5, 2)), $this->readInt(substr($c, 3, 2)));
            }
        }
    }
    
    
    private function getChars($n)
    {
        $response = null;
        
        // do we need more data?
        if ($this->strpos + $n - 1 >= strlen($this->str)) {
            $end = ($this->strpos + $n);
            while (strlen($this->str) < $end && $response !== false) {
                // read more from the file handle
                $need = $end - ftell($this->handle);
                if ($response = fread($this->handle, $need)) {
                    $this->str .= $response;
                } else {
                    
                    return false;
                }
            }
        }
        
        $result = substr($this->str, $this->strpos, $n);
        $this->strpos += $n;
        
        return $result;
    }
    
    
    private function getByte()
    {
        $c = $this->getChars(1);
        $b = @unpack('C', $c);
        
        if (!$b || !is_array($b)) {
            
            return false;
        }
        
        return reset($b);
    }
    
    
    private function readInt($str)
    {
        $size = unpack('C*', $str);
        
        return ($size[1] << 8) + $size[2];
    }
    
    
    public function __destruct()
    {
        $this->close();
    }
}
