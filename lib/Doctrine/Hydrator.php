<?php
/*
 *  $Id: Hydrate.php 3192 2007-11-19 17:55:23Z romanb $
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
 * Its purpose is to populate object graphs.
 *
 * @package    Doctrine
 * @subpackage Hydrate
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       www.doctrine-project.org
 * @since      1.0
 * @version    $Revision: 3192 $
 * @author     Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Hydrator
{
    protected static int $_totalHydrationTime = 0;

    protected array $_hydrators;

    protected ?string $_rootAlias = null;

    /** @phpstan-var int|class-string<Doctrine_Hydrator_Abstract> */
    protected int|string $_hydrationMode = Doctrine_Core::HYDRATE_RECORD;

    /**
     * @phpstan-var array<string, array{table: Doctrine_Table, map: ?string, parent?: string, relation?: Doctrine_Relation, ref?: bool, agg?: array<string, string>}>
     */
    protected array $_queryComponents = [];

    public function __construct()
    {
        $this->_hydrators = Doctrine_Manager::getInstance()->getHydrators();
    }

    /**
     * Get the hydration mode
     *
     * @phpstan-return int|class-string<Doctrine_Hydrator_Abstract>
     * @return int|string $hydrationMode One of the Doctrine_Core::HYDRATE_* constants
     */
    public function getHydrationMode(): int|string
    {
        return $this->_hydrationMode;
    }

    /**
     * Set the hydration mode
     *
     * @phpstan-param int|class-string<Doctrine_Hydrator_Abstract> $hydrationMode
     * @param int|string $hydrationMode One of the Doctrine_Core::HYDRATE_* constants or
     *                             a string representing the name of the hydration
     *                             mode or or an instance of the hydration class
     */
    public function setHydrationMode(int|string $hydrationMode): void
    {
        $this->_hydrationMode = $hydrationMode;
    }

    /**
     * Set the array of query components
     *
     * @param array $queryComponents
     */
    public function setQueryComponents(array $queryComponents): void
    {
        $this->_queryComponents = $queryComponents;
    }

    /**
     * Get the array of query components
     *
     * @phpstan-return array<string, array{table: Doctrine_Table, map: ?string, parent?: string, relation?: Doctrine_Relation, ref?: bool}>
     */
    public function getQueryComponents(): array
    {
        return $this->_queryComponents;
    }

    /**
     * Get the name of the driver class for the passed hydration mode
     *
     * @phpstan-param int|class-string<Doctrine_Hydrator_Abstract>|null $mode
     * @phpstan-return Doctrine_Hydrator_Abstract|class-string of Doctrine_Hydrator_Abstract
     */
    public function getHydratorDriverClassName(int|string|null $mode = null): string|Doctrine_Hydrator_Abstract
    {
        if ($mode === null) {
            $mode = $this->_hydrationMode;
        }

        if (!isset($this->_hydrators[$mode])) {
            throw new Doctrine_Hydrator_Exception('Invalid hydration mode specified: ' . $this->_hydrationMode);
        }

        return $this->_hydrators[$mode];
    }

    /**
     * Get an instance of the hydration driver for the passed hydration mode
     * @phpstan-param int|class-string<Doctrine_Hydrator_Abstract> $mode
     */
    public function getHydratorDriver(int|string $mode, array $tableAliases): Doctrine_Hydrator_Abstract
    {
        $driverClass = $this->getHydratorDriverClassName($mode);
        if (is_object($driverClass)) {
            if (!$driverClass instanceof Doctrine_Hydrator_Abstract) {
                throw new Doctrine_Hydrator_Exception('Invalid hydration class specified: ' . get_class($driverClass));
            }
            $driver = $driverClass;
            $driver->setQueryComponents($this->_queryComponents);
            $driver->setTableAliases($tableAliases);
            $driver->setHydrationMode($mode);
        } else {
            $driver = new $driverClass($this->_queryComponents, $tableAliases, $mode);
        }

        return $driver;
    }

    /**
     * Hydrate the query statement in to its final data structure by one of the
     * hydration drivers.
     */
    public function hydrateResultSet(Doctrine_Connection_Statement $stmt, array $tableAliases): mixed
    {
        $driver = $this->getHydratorDriver($this->_hydrationMode, $tableAliases);
        return $driver->hydrateResultSet($stmt);
    }
}
