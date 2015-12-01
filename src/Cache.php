<?php

namespace Hex\FileCache;

class Cache
{
    /**
     * @var \DateInterval
     */
    private $expirationInterval = null;

    /**
     * @var string
     */
    private $file;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param string $class
     * @param string|\DateInterval $expirationInterval
     */
    public function __construct($class, $expirationInterval = null)
    {
        $this->setCacheStorage($class);
        $this->setExpirationInterval($expirationInterval);
    }

    /**
     * Sets a key value pair, with expiration date if necessary
     *
     * @param string $key
     * @param mixed $data
     * @param null $expiration
     */
    public function set($key, $data, $expiration = null)
    {
        $item['data'] = $data;
        if (is_null($expiration)) {
            if ($this->expirationInterval instanceof \DateInterval) {
                $expiration = (new \DateTime())->add($this->expirationInterval);
                $item['expiration'] = $expiration;
            }
        } elseif (is_string($expiration)) {
            $item['expiration'] = new \DateTime($expiration);
        } elseif ($expiration instanceof \DateTime) {
            $item['expiration'] = $expiration;
        }
        $this->cache[$key] = serialize($item);
        $this->update();
    }

    /**
     * @param string $key
     * @return null|string
     */
    public function get($key)
    {
        if (isset($this->cache[$key])) {
            $item = unserialize($this->cache[$key]);
            if (!isset($item['expiration'])) {
                return $item['data'];
            } else {
                if ($item['expiration'] > new \DateTime()) {
                    return $item['data'];
                } else {
                    unset($this->cache[$key]);
                    $this->update();
                }
            }
        }
        return null;
    }

    /**
     * @param string $key
     */
    public function delete($key)
    {
        unset($this->cache[$key]);
        $this->update();
    }

    public function warmup()
    {
        foreach ($this->cache as $key => $item) {
            if(unserialize($item)['expiration'] < new \DateTime()){
                unset($this->cache[$key]);
            }
        }
        $this->update();
    }

    public function clear()
    {
        $this->cache = [];
        $this->update();
    }

    /**
     * @param string|\DateInterval $expirationInterval
     */
    private function setExpirationInterval($expirationInterval)
    {
        if ($expirationInterval instanceof \DateInterval) {
            $this->expirationInterval = $expirationInterval;
        }
        if (is_string($expirationInterval)) {
            $this->expirationInterval = new \DateInterval($expirationInterval);
        }
    }

    /**
     * @param string $class
     * @throws \Exception
     */
    private function setCacheStorage($class)
    {
        $this->file = (new \ReflectionClass($class))->getFileName();
        $storage = new $class;
        if (!method_exists($storage, 'getCache')) {
            $this->update();
        } else {
            $this->cache = call_user_func($class . '::getCache');
        }
    }

    private function update()
    {
        $content = file_get_contents($this->file);
        $export = var_export($this->cache, true);
        $replace = preg_replace('/{(.*)}/s', '{' . PHP_EOL . ' public static function getCache(){ return ' . $export . ';}' . PHP_EOL . '}', $content);
        file_put_contents($this->file, $replace);
    }
}
