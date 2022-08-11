<?php
/*
 *  $Id: Iterator.php 7490 2010-03-29 19:53:27Z jwage $
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
 * Doctrine_Collection_Iterator
 * iterates through Doctrine_Collection
 *
 * @package    Doctrine
 * @subpackage Collection
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       www.doctrine-project.org
 * @since      1.0
 * @version    $Revision: 7490 $
 * @author     Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Collection_Iterator implements Iterator
{
    protected Doctrine_Collection $collection;
    protected array $keys;
    protected mixed $key;
    protected int $index;
    protected int $count;

    public function __construct(Doctrine_Collection $collection)
    {
        $this->collection = $collection;
        $this->keys       = $this->collection->getKeys();
        $this->count      = $this->collection->count();
    }

    /**
     * rewinds the iterator
     */
    public function rewind(): void
    {
        $this->index = 0;
        $i           = $this->index;
        if (isset($this->keys[$i])) {
            $this->key = $this->keys[$i];
        }
    }

    /**
     * returns the current key
     */
    public function key(): int
    {
        return $this->key;
    }

    /**
     * returns the current record
     * @return Doctrine_Record
     */
    public function current(): mixed
    {
        return $this->collection->get($this->key);
    }

    /**
     * advances the internal pointer
     */
    public function next(): void
    {
        $this->index++;
        $i = $this->index;
        if (isset($this->keys[$i])) {
            $this->key = $this->keys[$i];
        }
    }
}
