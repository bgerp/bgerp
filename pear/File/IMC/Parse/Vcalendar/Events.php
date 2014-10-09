<?php
/*
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2008 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | All rights reserved.                                                 |
 * |                                                                      |
 * | Redistribution and use in source and binary forms, with or without   |
 * | modification, are permitted provided that the following conditions   |
 * | are met:                                                             |
 * |                                                                      |
 * | - Redistributions of source code must retain the above copyright     |
 * | notice, this list of conditions and the following disclaimer.        |
 * | - Redistributions in binary form must reproduce the above copyright  |
 * | notice, this list of conditions and the following disclaimer in the  |
 * | documentation and/or other materials provided with the distribution. |
 * | - Neither the name of the The PEAR Group nor the names of its        |
 * | contributors may be used to endorse or promote products derived from |
 * | this software without specific prior written permission.             |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE       |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
 * | POSSIBILITY OF SUCH DAMAGE.                                          |
 * +----------------------------------------------------------------------+
 *
 * PHP Version 5
 *
 * @category File_Formats
 * @package  File_IMC
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  SVN: $Id$
 * @link     http://pear.php.net/package/File_IMC
 */

/**
* File_IMC_Parse_Vcalendar_Events
*
* <code>
*   $parser = File_IMC::parse('vcalendar');
*   $parser->fromFile('path/to/sample.vcs');
*
*   $events = $parser->getEvents();
*
*   while ($events->valid()) {
*       $event = $events->current();
*       $events->next();
*   }
* </code>
*
* @category File_Formats
* @package  File_IMC
* @author   Till Klampaeckel <till@php.net>
* @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
* @version  Release: @package_version@
* @link     http://pear.php.net/package/File_IMC
*/
class File_IMC_Parse_Vcalendar_Events extends ArrayIterator
{
    protected $data;
    protected $index;

    public function __construct(array $data)
    {
        $this->data  = $data;
        $this->index = 0;
    }

    public function append($value)
    {
    }

    public function count()
    {
        return count($this->data);
    }

    public function current()
    {
        return new File_IMC_Parse_Vcalendar_Event($this->data[$this->index]);
    }

    /*
    public function get($index)
    {
        if (isset($this->data[$index])) {
            return $this->data[$index];
        }
        throw File_IMC_Exception("Invalid index.");
    }
    */

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        ++$this->index;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function seek($position)
    {
        $this->index = $position;
    }

    public function valid()
    {
        if (isset($this->data[$this->index])) {
            return true;
        }
        return false;
    }
}
