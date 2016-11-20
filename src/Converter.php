<?php

namespace SZonov\LibreOfficeConverter;

/**
 * Convert files between different formats, which supports LibreOffice
 * LibreOffice binary should be installed
 *
 * Class Converter
 * @package SZonov\LibreOfficeConverter
 */
class Converter
{
    /**
     * Path to LibreOffice binary 'soffice' or 'soffice.bin'
     * @var null|string
     */
    private $bin;

    /**
     * Source file for converting
     * @var string
     */
    private $source;

    /**
     * Converted file
     *
     * @var string
     */
    private $destination;

    /**
     * Parts of command line
     *
     * Different LibreOffice versions or even the same version but installation of different OSes
     * can have different command line options.
     * To fix it without class rewriting use setter .. setCommand()
     *
     * @see getCommand()
     * @see setCommand()
     *
     * @var array
     */
    private $command = array(
        '%bin%',
        '--headless',
        '--convert-to', '%convert_to%',
        '--outdir', '%outdir%',
        '%source%',
    );

    /**
     * Temporary directory, where files processed.
     * Then converted file moved to $destination
     *
     * @see setTempPath()
     * @see getTempPath()
     *
     * @var null|string
     */
    private $tempPath;

    public function __construct($bin = null)
    {
        $this->bin = ($bin === null) ? $this->findBin() : $bin;
        if (!$this->bin)
            throw new ConverterException("soffice binary not defined");
    }

    /**
     * Setter for $tempPath
     *
     * @param string $tempPath
     * @return $this
     */
    public function setTempPath($tempPath)
    {
        $this->tempPath = $tempPath;
        return $this;
    }

    /**
     * Getter for $tempPath
     *
     * @return string
     */
    public function getTempPath()
    {
        if ($this->tempPath === null)
            $this->tempPath = (isset($_SERVER['TMPDIR'])) ? $_SERVER['TMPDIR'] : '/tmp';

        return $this->tempPath;
    }

    /**
     * Setter for $command
     *
     * @param array $command
     * @return $this
     */
    public function setCommand(array $command)
    {
        $this->command = $command;
        return $this;
    }

    /**
     * Getter for $command
     * @return array
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Setup source file for converting
     *
     * @param string $file
     * @return $this
     */
    public function from($file)
    {
        $this->source = $file;
        return $this;
    }

    /**
     * Setup location of destination file
     *
     * @param string $file
     * @return $this
     */
    public function to($file)
    {
        $this->destination = $file;
        return $this;
    }

    /**
     * Make converting using filter (if isset) and returns
     * - true - success operation,
     * - false - failed operation
     *
     * @param null|string $filter
     * @return bool
     * @throws ConverterException
     */
    public function convert($filter = null)
    {
        if (!$this->source || !file_exists($this->source))
            throw new ConverterException('File does not exist [' . $this->source.']');

        if (!$this->destination)
            throw new ConverterException('Output file does not set');

        // set output directory as random subdirectory under temporary path
        $outdir = rtrim($this->getTempPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . uniqid(rand(), true);

        // create output directory
        $this->mkdir($outdir);

        $convert_to = pathinfo($this->destination, PATHINFO_EXTENSION);
        $outfile    = $outdir . DIRECTORY_SEPARATOR . pathinfo($this->source, PATHINFO_FILENAME) . '.' . $convert_to;

        // if isset filter - use it, example of filter 'writer_pdf_Export'
        if ($filter !== null)
            $convert_to .= ':' . $filter;

        // Substitutions for command line placeholders
        $replacement = array(
            'bin'        => $this->bin,
            'convert_to' => $convert_to,
            'outdir'     => $outdir,
            'source'     => $this->source
        );

        $cmd = $this->makeCommand($replacement);

        shell_exec($cmd);

        $result = false;

        if (file_exists($outfile)) {
            $this->mkdir(dirname($this->destination));
            $result = rename($outfile, $this->destination);
        }

        // remove temporary sub directory
        rmdir($outdir);

        return $result;
    }

    /**
     * Get command string ready for shell_exec
     *
     * @param array $replacement
     * @return mixed
     */
    protected function makeCommand($replacement)
    {
        return preg_replace_callback('/%\w+%/', function($matches) use ($replacement) {
            $key = trim($matches[0], '%');
            return isset($replacement[$key]) ? escapeshellarg($replacement[$key]) : '';
        }, join(' ', $this->getCommand()));
    }

    /**
     * Create directory, if does not exists yet
     *
     * @param string $dir
     * @return mixed
     */
    protected function mkdir($dir)
    {
        if (!is_dir($dir))
            mkdir($dir, 0777, true);
        return $dir;
    }

    /**
     * Try to find LibreOffice binary in usual paths
     *
     * @return null|string
     */
    protected function findBin()
    {
        $dirs = array(
            '/usr/bin/',
            '/usr/lib/libreoffice/program/',
            '/Applications/LibreOffice.app/Contents/MacOS/'
        );
        foreach ($dirs as $dir)
        {
            if ( file_exists($f = $dir . 'soffice'))
                return $f;

            if ( file_exists($f = $dir . 'soffice.bin'))
                return $f;
        }
        return null;
    }
}