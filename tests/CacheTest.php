<?php

namespace Hex\FileCache\Tests;

use Hex\FileCache\Cache;
use Hex\FileCache\Tests\Resources\Storage;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Cache
     */
    private $cache;

    public function setUp()
    {
        $this->cache = new Cache(Storage::class);
    }

    public function testSave()
    {
        $this->cache->set('save', ['Hello']);

        $this->assertTrue(is_array($this->cache->get('save')));
    }

    public function testDelete()
    {
        $this->cache->set('delete', ['Delete']);
        $this->cache->delete('delete');

        $this->assertNull($this->cache->get('delete'));
    }

    public function testExpiration()
    {
        $this->cache->set('expired', ['Ok'], '2010-10-10');
        $this->cache->set('expired2', ['OK2'], new \DateTime('2000-01-01'));

        $this->assertNull($this->cache->get('expired'));
        $this->assertNull($this->cache->get('expired2'));
    }

    public function testDefaultExpiration()
    {
        $cache = new Cache(Storage::class, 'PT2S');
        $cache->set('default', ['default']);

        $cache2 = new Cache(Storage::class, new \DateInterval('PT1S'));
        $cache2->set('default2', ['default2']);

        sleep(2);
        $this->assertNull($cache->get('default'));
        $this->assertNull($cache->get('default2'));
    }

    public function testWarmup()
    {
        $this->cache->set('warm', ['warm'], '1998-10-01');
        $this->cache->set('cold', ['cold'], '2001-06-17');
        $this->cache->warmup();

        $this->assertFalse(isset(Storage::getCache()['warm']));
        $this->assertFalse(isset(Storage::getCache()['cold']));
    }

    public function testsClear()
    {
        $this->cache->set('clear', ['clean'], '2009-12-02');
        $this->cache->clear();

        $this->assertNull($this->cache->get('clear'));
    }
}
