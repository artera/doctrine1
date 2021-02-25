<?php
/*
 *  $Id: Memcache.php 7490 2010-03-29 19:53:27Z jwage $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Memcache cache driver
 *
 * @package    Doctrine
 * @subpackage Cache
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       www.doctrine-project.org
 * @since      1.0
 * @version    $Revision: 7490 $
 * @author     Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Cache_Memcache extends Doctrine_Cache_Driver
{
    /**
     * @var Memcache $memcache     memcache object
     */
    protected $memcache = null;

    /**
     * constructor
     *
     * @param array $options associative array of cache driver options
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('memcache')) {
            throw new Doctrine_Cache_Exception('In order to use Memcache driver, the memcache extension must be loaded.');
        }
        parent::__construct($options);

        if (isset($options['servers'])) {
            $value = $options['servers'];
            if (isset($value['host'])) {
                // in this case, $value seems to be a simple associative array (one server only)
                $value = [0 => $value]; // let's transform it into a classical array of associative arrays
            }
            $this->setOption('servers', $value);
        }

        $this->memcache = new Memcache;

        foreach ($this->options['servers'] as $server) {
            if (!array_key_exists('persistent', $server)) {
                $server['persistent'] = true;
            }
            if (!array_key_exists('port', $server)) {
                $server['port'] = 11211;
            }
            $this->memcache->addServer($server['host'], $server['port'], $server['persistent']);
        }
    }

    /**
     * Test if a cache record exists for the passed id
     *
     * @param  string $id                cache id
     * @param  bool   $testCacheValidity
     * @return mixed  Returns either the cached data or false
     */
    protected function doFetch(string $id, bool $testCacheValidity = true)
    {
        return $this->memcache->get($id);
    }

    protected function doContains(string $id): bool
    {
        return (bool) $this->memcache->get($id);
    }

    protected function doSave(string $id, $data, ?int $lifeTime = null): bool
    {
        $flag = $this->options['compression'] ? MEMCACHE_COMPRESSED : 0;
        return $this->memcache->set($id, $data, $flag, $lifeTime ?: 0);
    }

    /**
     * Remove a cache record directly. This method is implemented by the cache
     * drivers and used in Doctrine_Cache_Driver::delete()
     *
     * @param  string $id cache id
     * @return boolean true if no problem
     */
    protected function doDelete(string $id): bool
    {
        return $this->memcache->delete($id);
    }

    /**
     * Fetch an array of all keys stored in cache
     *
     * @return array Returns the array of cache keys
     */
    protected function getCacheKeys(): array
    {
        $keys     = [];
        $allSlabs = $this->memcache->getExtendedStats('slabs');

        foreach ($allSlabs as $server => $slabs) {
            foreach (array_keys($slabs) as $slabId) {
                $dump = $this->memcache->getExtendedStats('cachedump', (int) $slabId);
                foreach ($dump as $entries) {
                    if ($entries) {
                        $keys = array_merge($keys, array_keys($entries));
                    }
                }
            }
        }
        return $keys;
    }
}
