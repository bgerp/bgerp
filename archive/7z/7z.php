<?php
/**
 *
 * This software is distributed under the GNU GPL v3.0 license.
 *
 * @author    Gemorroj
 * @copyright 2012 http://wapinet.ru
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 * @link      https://github.com/Gemorroj/Archive_7z
 * @version   0.1 alpha
 *
 */
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/Entry.php';

class Archive_7z
{
    /**
     * Overwrite All existing files
     *
     * @const string
     */
    const OVERWRITE_MODE_A = '-aoa';
    /**
     * Skip extracting of existing files
     *
     * @const string
     */
    const OVERWRITE_MODE_S = '-aos';
    /**
     * aUto rename extracting file (for example, name.txt will be renamed to name_1.txt)
     *
     * @const string
     */
    const OVERWRITE_MODE_U = '-aou';
    /**
     * auto rename existing file (for example, name.txt will be renamed to name_1.txt)
     *
     * @const string
     */
    const OVERWRITE_MODE_T = '-aot';

    /**
     * @var string
     */
    protected $cliNix = '/usr/local/bin/7z';
    /**
     * @var string
     */
    protected $cliWin = 'C:/Progra~1/7-Zip/7z.exe'; // %ProgramFiles%\7-Zip\7z.exe


    /**
     * @var string
     */
    private $cli;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $outputDirectory = './';

    /**
     * @var string
     */
    private $overwriteMode = self::OVERWRITE_MODE_A;


    private $headToken = '----------';
    private $listToken = '';


    /**
     * @param string $filename 7z archive filename
     *
     * @throws Archive_7z_Exception
     */
    public function __construct($filename)
    {
        if(substr(PHP_OS, 0, 3) === 'WIN') {
            if(defined('ARCHIVE_7Z_PATH') && ARCHIVE_7Z_PATH != '7z') {
                $cli = ARCHIVE_7Z_PATH;
            } else {
                $cli = $this->cliWin;
            }
        } else {
            if(defined('ARCHIVE_7Z_PATH')) {
                $cli = ARCHIVE_7Z_PATH;
            } else {
                $cli = $this->cliNix;
            }
        }

        $this->setFilename($filename)->setCli($cli);
    }


    /**
     * @param string $path
     *
     * @throws Archive_7z_Exception
     * @return Archive_7z
     */
    public function setCli($path)
    {
        $this->cli = str_replace('\\', '/', realpath($path));
 
        if (!$this->cli || (is_executable($this->cli) === false)) {

            throw new Archive_7z_Exception('Cli is not available-' . $this->cli . '-' . $path);
        }
  
        return $this;
    }


    /**
     * @param string $filename
     *
     * @throws Archive_7z_Exception
     * @return Archive_7z
     */
    public function setFilename($filename)
    {
        $this->filename = realpath($filename);

        if (is_readable($this->filename) === false) {
            throw new Archive_7z_Exception('Filename is not available');
        }

        return $this;
    }


    /**
     * @param string $directory
     *
     * @throws Archive_7z_Exception
     * @return Archive_7z
     */
    public function setOutputDirectory($directory = './')
    {
        $this->outputDirectory = realpath($directory);

        if (is_writable($this->outputDirectory) === false) {
            throw new Archive_7z_Exception('Output directory is not available');
        }

        return $this;
    }


    /**
     * @param string $password
     *
     * @throws Archive_7z_Exception
     * @return Archive_7z
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }


    /**
     * @param string $mode
     *
     * @throws Archive_7z_Exception
     * @return Archive_7z
     */
    public function setOverwriteMode($mode = Archive_7z::OVERWRITE_MODE_A)
    {
        $this->overwriteMode = $mode;

        if (in_array(
            $this->overwriteMode,
            array(
                self::OVERWRITE_MODE_A,
                self::OVERWRITE_MODE_S,
                self::OVERWRITE_MODE_T,
                self::OVERWRITE_MODE_U
            )
        ) === false
        ) {
            throw new Archive_7z_Exception('Overwrite mode is not available');
        }

        return $this;
    }


    /**
     * @return string
     */
    public function getCli()
    {
        return $this->cli;
    }


    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }


    /**
     * @return string
     */
    public function getOutputDirectory()
    {
        return $this->outputDirectory;
    }


    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }


    /**
     * @return string
     */
    public function getOverwriteMode()
    {
        return $this->overwriteMode;
    }


    /**
     * @return string
     */
    private function getCmdPrefix()
    {
        $res = '';

        if(substr(PHP_OS, 0, 3) != 'WIN') {
            $res .= 'LANG=en_US.UTF-8 LC_ALL=en_US.UTF-8 ';
        }

        $res .= '"' . escapeshellcmd($this->cli) . '"'; // fix for windows
 
        return $res;
    }


    /**
     * @return string
     */
    private function getCmdPostfix()
    {
        $cmd = '';
        if ($this->password !== null) {
            $cmd .= ' -p' . escapeshellarg($this->password);
        }

        return $cmd;
    }


    /**
     * @throws Archive_7z_Exception
     */
    public function extract()
    {
        $cmd = $this->getCmdPrefix() . ' x ' . escapeshellarg($this->filename) . ' ' . escapeshellcmd(
            $this->overwriteMode
        ) . ' -o' . escapeshellarg($this->outputDirectory) . ' ' . $this->getCmdPostfix();

        exec($cmd, $out, $rv);

        if ($rv !== 0) {
            throw new Archive_7z_Exception('Error! Exit code: ' . $rv);
        }
    }


    /**
     * @param string $file
     *
     * @throws Archive_7z_Exception
     */
    public function extractEntry($file)
    {
        $cmd = $this->getCmdPrefix() . ' x ' . escapeshellarg($this->filename) . ' ' . escapeshellcmd(
            $this->overwriteMode
        ) . ' -o' . escapeshellarg($this->outputDirectory) . ' ' . $this->getCmdPostfix() . ' ' . escapeshellarg(
            $file
        );

        exec($cmd, $out, $rv);

        if ($rv !== 0) {
            throw new Archive_7z_Exception('Error! Exit code: ' . $rv);
        }
    }


    /**
     * @param string $file
     *
     * @throws Archive_7z_Exception
     * @return string
     */
    public function getContent($file)
    {
        $cmd = $this->getCmdPrefix() . ' x ' . escapeshellarg($this->filename) . ' -so ' . escapeshellarg($file) . ' '
            . $this->getCmdPostfix();

        $out = shell_exec($cmd);

        if ($out === null) {
            throw new Archive_7z_Exception('Error!');
        }

        return $out;
    }


    /**
     * @throws Archive_7z_Exception
     * @return Archive_7z_Entry[]
     */
    public function getEntries()
    {
        $cmd = $this->getCmdPrefix() . ' l ' . escapeshellarg($this->filename) . ' -slt ' . $this->getCmdPostfix();

        exec($cmd, $out, $rv);

        if ($rv !== 0) {
            throw new Archive_7z_Exception('Error! Exit code: ' . $rv);
        }

        $list = array();
        foreach ($this->parseEntries($out) as $v) {
            $list[] = new Archive_7z_Entry($this, $v);
        }

        return $list;
    }


    /**
     * @param array $output
     *
     * @return array
     */
    private function parseEntries(array $output)
    {
        $head = true;
        $list = array();
        $i = 0;

        foreach ($output as $value) {
            if ($value === $this->headToken) {
                $head = false;
                continue;
            }

            if ($head === true) {
                continue;
            }

            if ($value === $this->listToken) {
                $i++;
                continue;
            }

            $list[$i][] = $value;
        }

        return $list;
    }
}
