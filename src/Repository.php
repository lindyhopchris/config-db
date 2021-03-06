<?php namespace Jameswmcnab\ConfigDb;

use Illuminate\Support\Arr;
use Illuminate\Support\NamespacedItemResolver;

class Repository extends NamespacedItemResolver implements RepositoryInterface
{

    /**
     * @type array
     */
    protected $items = array();

    /**
     * @type \Jameswmcnab\ConfigDb\LoaderInterface
     */
    protected $loader;

    /**
     * @param  \Jameswmcnab\ConfigDb\LoaderInterface  $loader
     */
    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        $default = microtime(true);

        return $this->get($key, $default) !== $default;
    }

    /**
     * Determine if a configuration group exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGroup($key)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        return $this->loader->exists($group, $namespace);
    }

    /**
     * Get a single item or group of items by key.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return string|array
     */
    public function get($key, $default = null)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        // Configuration items are actually keyed by "collection", which is simply a
        // combination of each namespace and groups, which allows a unique way to
        // identify the arrays of configuration items for the particular files.
        $collection = $this->getCollection($group, $namespace);

        $this->load($group, $namespace, $collection);
        $value = $this->items[$collection];

        if (Arr::accessible($value)) {
            return Arr::get($value, $item, $default);
        }

        return (!is_null($value) && !$item) ? $value : $default;
    }

    /**
     * Save a single key => value pair into the database
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return bool
     */
    public function set($key, $value)
    {
        return $this->save($key, $value);
    }

    /**
     * Save a single key => value pair into the database
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return bool
     */
    public function save($key, $value)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        return $this->loader->save($group, $value);
    }

    /**
     * Load the configuration group for the key.
     *
     * @param  string  $group
     * @param  string  $namespace
     * @param  string  $collection
     * @return void
     */
    protected function load($group, $namespace, $collection)
    {
        // If we've already loaded this collection, we will just bail out since we do
        // not want to load it again. Once items are loaded a first time they will
        // stay kept in memory within this class and not loaded from disk again.
        if (isset($this->items[$collection])) {
            return;
        }

        $items = $this->loader->load($group, $namespace);

        $this->items[$collection] = $items;
    }

    /**
     * Get the collection identifier.
     *
     * @param  string  $group
     * @param  string  $namespace
     * @return string
     */
    protected function getCollection($group, $namespace = null)
    {
        $namespace = $namespace ?: '*';

        return $namespace.'::'.$group;
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    /**
     * Returns all registered namespaces with the config
     * loader.
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->loader->getNamespaces();
    }

    /**
     * Get the loader implementation.
     *
     * @return \Jameswmcnab\ConfigDb\LoaderInterface
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Set the loader implementation.
     *
     * @param  \Jameswmcnab\ConfigDb\LoaderInterface  $loader
     * @return void
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }
}
