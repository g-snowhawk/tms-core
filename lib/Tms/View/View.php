<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms;

/**
 * View methods.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class View implements View_Interface
{
    /**
     * Directory name
     */
    const TEMPLATE_DIR_NAME = 'templates';

    /**
     * Template extention
     */
    const TEMPLATE_EXTENTION = '.tpl';

    /**
     * Template Engine.
     *
     * @var Twig_Environment
     */
    private $engine;

    /**
     * Templates directory.
     *
     * @var string
     */
    private $template_dir;
    private $cache_dir;

    /**
     * Template name.
     *
     * @var string
     */
    private $template_name = 'default.tpl';

    /**
     * Twig loader
     *
     * @var object
     */
    private $loader;
    private $context;

    /**
     * Object Constructor.
     *
     * @param string $path      Templates directory path
     * @param bool   $debugmode Debug mode
     * @param mixed  $cache_dir Cache directory path
     */
    public function __construct($path = null, $debugmode = false, $cache_dir = false)
    {
        $this->setPaths($path);

        // Check cache directory
        if ($debugmode === false) {
            if (is_null($cache_dir)) {
                $cache_dir = dirname(filter_input(INPUT_SERVER, 'SCRIPT_FILENAME')).'/cache';
            }
            if (!file_exists($cache_dir)) {
                try {
                    \P5\File::mkdir($cache_dir);
                } catch (\ErrorException $e) {
                    trigger_error("Can't create directory `$cache_dir'", E_USER_ERROR);
                }
            }
        }
        $this->cache_dir = $cache_dir;

        // Using TWIG
        $this->context = ['cache' => $cache_dir, 'debug' => $debugmode];
        $this->resetEngine();
    }

    public function __clone()
    {
        $globals = $this->param();
        $this->resetEngine();
        if (is_array($globals)) {
            foreach ($globals as $key => $value) {
                $this->bind($key, $value);
            }
        }
    }

    public function resetEngine()
    {
        $this->loader = new \Twig_Loader_Filesystem($this->template_dir);
        $this->engine = new \Twig_Environment($this->loader, $this->context);

        if ($this->context['debug']) {
            $this->engine->addExtension(new \Twig_Extension_Debug());
        }

        // Markdown Extension
        $markdown = new \cebe\markdown\GithubMarkdown();
        $markdown->enableNewlines = true;
        $this->engine->addExtension(new View\Markdown\Extension($markdown));
        $this->engine->addExtension(new \Twig_Extension_StringLoader());
    }

    /**
     * Binding the parameters.
     *
     * @param string $name
     * @param mexed  $value
     *
     * @return mixed
     */
    public function bind($name, $value)
    {
        return $this->engine->addGlobal($name, $value);
    }

    /**
     * Get the parameters from template engine.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function param($name = null)
    {
        $params = $this->engine->getGlobals();
        if (is_null($name)) {
            return $params;
        }

        return (isset($params[$name])) ? $params[$name] : null;
    }

    /**
     * Rendering the template.
     *
     * @param string $template
     * @param bool   $source
     * @param bool   $load_string
     *
     * @return mixed
     */
    public function render($template = null, $source = false, $load_string = false)
    {
        if (is_null($template)) {
            $template = $this->template_name;
        }
        if ($load_string) {
            $this->loader = new \Twig_Loader_Array([$this->template_name => $template]);
            $template = $this->template_name;
            $this->engine->setLoader($this->loader);
        }

        $this->bind('session', $_SESSION);

        try {
            $html = $this->engine->render($template);
        } catch (\Exception $e) {
            throw new ViewException($e->getMessage(), $e->getCode(), $e);
        }

        // format HTML
        $formatter = new \P5\Html\Format('  ', false);
        $html = $formatter->start($html);

        if ($source) {
            $this->resetEngine();

            return $html;
        }

        \P5\Http::responseHeader('X-Frame-Options', 'SAMEORIGIN');
        \P5\Http::responseHeader('X-XSS-Protection', '1');
        \P5\Http::responseHeader('X-Content-Type-Options', 'nosniff');

        echo $html;
        exit;
    }

    /**
     * Returns the paths to the templates.
     *
     * @see Twig_Loader_Filesystem::getPaths()
     *
     * @return array
     */
    public function getPaths()
    {
        if (!is_null($this->loader) && get_class($this->loader) === 'Twig_Loader_Filesystem') {
            return $this->engine->getLoader()->getPaths();
        }
    }

    /**
     * Set template path
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->loader->setPaths($path);
    }

    /**
     * Set template paths
     *
     * @param mixed $path
     */
    public function setPaths($path = null)
    {
        $directories = explode(PATH_SEPARATOR, ini_get('include_path'));
        $paths = (array)$path;
        foreach ($directories as $directory) {
            if (false !== $dir = realpath("$directory/" . self::TEMPLATE_DIR_NAME)) {
                if (preg_match("/^\.+$/", $directory)) {
                    array_unshift($paths, $dir);
                } else {
                    $paths[] = $dir;
                }
            }
        }
        $this->template_dir = $paths;
        if (!is_null($this->loader) && get_class($this->loader) === 'Twig_Loader_Filesystem') {
            $this->loader->setPaths($this->template_dir);
        }
    }

    /**
     * Adds a path where templates are stored.
     *
     * @see Twig_Loader_Filesystem::addPath()
     */
    public function addPath($path)
    {
        if (!is_null($this->loader) && get_class($this->loader) === 'Twig_Loader_Filesystem') {
            $this->engine->getLoader()->addPath($path);
        }
    }

    /**
     * Prepends a path where templates are stored.
     *
     * @see Twig_Loader_Filesystem::prependPath()
     */
    public function prependPath($path)
    {
        if (!is_null($this->loader) && get_class($this->loader) === 'Twig_Loader_Filesystem') {
            $this->engine->getLoader()->prependPath($path);
        }
    }

    /**
     * Check template exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists($name)
    {
        return $this->engine->getLoader()->exists($name);
    }

    /**
     * Cache directory
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cache_dir;
    }

    /**
     * Clear cache generated by Twig template engine
     *
     * @param string $template_path
     *
     * @return bool
     */
    public function clearCache($template_path) 
    {
        if (!file_exists($template_path)) {
            return true;
        }
        $cache = $this->engine->getCache(false);
        $this->setPath(dirname($template_path));
        $name = basename($template_path);
        $cache_file = $cache->generateKey($name, $this->engine->getTemplateClass($name));
        if (!file_exists($cache_file)) {
            return true;
        }
        return \P5\File::rmdirs(dirname($cache_file), true);
    }

    /**
     * Clear cache directory
     *
     * @return bool
     */
    public function clearAllCaches()
    {
        $cache_dir = $this->getCacheDir();
        if (empty($cache_dir) || !file_exists($cache_dir)) {
            return true;
        }
        return \P5\File::rmdirs($cache_dir, true);
    }
}
