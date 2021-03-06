<?php namespace ParaTest\Runners\PHPUnit;

/**
 * Class Configuration
 *
 * Stores information about the phpunit xml
 * configuration being used to run tests
 *
 * @package ParaTest\Runners\PHPUnit
 */
class Configuration
{
    /**
     * Path to the configuration file
     *
     * @var string
     */
    protected $path;

    /**
     * @var \SimpleXMLElement
     */
    protected $xml;

    /**
     * A collection of datastructures
     * build from the <testsuite> nodes inside of a
     * PHPUnit configuration
     *
     * @var array
     */
    protected $suites = array();

    public function __construct($path)
    {
        $this->path = $path;
        if(file_exists($path)) {
            $before = libxml_disable_entity_loader(false);
            $this->xml = simplexml_load_file($path);
            libxml_disable_entity_loader($before);
        }
    }

    /**
     * Get the bootstrap PHPUnit configuration attribute
     *
     * @return string The bootstrap attribute or empty string if not set
     */
    public function getBootstrap()
    {
        if($this->xml)
            return (string)$this->xml->attributes()->bootstrap;
        else
            return '';
    }

    /**
     * Returns the path to the phpunit configuration
     * file
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return the contents of the <testsuite> nodes
     * contained in a PHPUnit configuration
     *
     * @return SuitePath[]
     */
    public function getSuites()
    {
        if(!$this->xml) return null;
        $suites = array();
        $nodes = $this->xml->xpath('//testsuite');
        while(list(, $node) = each($nodes)) {
            foreach ($node->directory as $dir) {
                foreach ($this->getSuitePaths((string) $dir) as $path) {
                    $suites[(string)$node['name']][] = new SuitePath($path, $dir->attributes()->suffix);
                }
            }
        }
        return $suites;
    }

    /**
     * Return the path of the directory
     * that contains the phpunit configuration
     *
     * @return string
     */
    public function getConfigDir()
    {
        return dirname($this->path) . DIRECTORY_SEPARATOR;
    }

    /**
     * Returns a suite paths relative to the config file
     *
     * @param $path
     * @return array|string[]
     */
    public function getSuitePaths($path)
    {
        $real = realpath($this->getConfigDir() . $path);

        if ($real !== false) {
            return array($real);
        }

        if ($this->isGlobRequired($path)) {
            $paths = array();
            foreach (glob($this->getConfigDir() . $path, GLOB_ONLYDIR) as $path) {
                if (($path = realpath($path)) !== false) {
                    $paths[] = $path;
                }
            }

            return $paths;
        }

        throw new \RuntimeException("Suite path $path could not be found");
    }

    /**
     * Returns true if path needs globbing (like a /path/*-to/string)
     *
     * @param string $path
     * @return bool
     */
    public function isGlobRequired($path)
    {
        return strpos($path, '*') !== false;
    }

    /**
     * Converting the configuration to a string
     * returns the configuration path
     *
     * @return string
     */
    public function __toString()
    {
        return $this->path;
    }
}
