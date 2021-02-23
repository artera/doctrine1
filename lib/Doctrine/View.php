<?php
/*
 *  $Id: View.php 7490 2010-03-29 19:53:27Z jwage $
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
 * Doctrine_View
 *
 * this class represents a database view
 *
 * @author     Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package    Doctrine
 * @subpackage View
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       www.doctrine-project.org
 * @since      1.0
 * @version    $Revision: 7490 $
 */
class Doctrine_View
{
    /**
     * SQL DROP constant
     */
    const DROP = 'DROP VIEW %s';

    /**
     * SQL CREATE constant
     */
    const CREATE = 'CREATE VIEW %s AS %s';

    /**
     * SQL SELECT constant
     */
    const SELECT = 'SELECT * FROM %s';

    /**
     * @var string $_name                the name of the view
     */
    protected $_name;

    /**
     * @var Doctrine_Query $_query       the DQL query object this view is hooked into
     */
    protected $_query;

    /**
     * @var Doctrine_Connection $_conn   the connection object
     */
    protected $_conn;

    /**
     * The view dql string
     */
    protected string $_dql;

    /**
     * The view sql string
     */
    protected ?string $_sql;

    /**
     * constructor
     *
     * @param Doctrine_Query $query
     * @param string         $viewName
     */
    public function __construct(Doctrine_Query $query, string $viewName)
    {
        $this->_name  = $viewName;
        $this->_query = $query;
        $this->_query->setView($this);
        $this->_conn = $query->getConnection();
        $this->_dql  = $query->getDql();
        $this->_sql  = $query->getSqlQuery();
    }

    /**
     * returns the associated query object
     *
     * @return Doctrine_Query
     */
    public function getQuery(): Doctrine_Query
    {
        return $this->_query;
    }

    /**
     * returns the name of this view
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * returns the connection object
     *
     * @return Doctrine_Connection
     */
    public function getConnection(): Doctrine_Connection
    {
        return $this->_conn;
    }

    /**
     * creates this view
     *
     * @throws Doctrine_View_Exception
     * @return void
     */
    public function create()
    {
        $sql = sprintf(self::CREATE, $this->_name, $this->_query->getSqlQuery());
        try {
            $this->_conn->execute($sql, $this->_query->getFlattenedParams());
        } catch (Doctrine_Exception $e) {
            throw new Doctrine_View_Exception($e->__toString());
        }
    }

    /**
     * drops this view from the database
     *
     * @throws Doctrine_View_Exception
     * @return void
     */
    public function drop(): void
    {
        try {
            $this->_conn->execute(sprintf(self::DROP, $this->_name));
        } catch (Doctrine_Exception $e) {
            throw new Doctrine_View_Exception($e->__toString());
        }
    }

    /**
     * returns a collection of Doctrine_Record objects
     * @phpstan-return array|bool|Doctrine_Collection<Doctrine_Record>|Doctrine_Collection_OnDemand<Doctrine_Record>|float|int|string
     */
    public function execute(): array|bool|Doctrine_Collection|Doctrine_Collection_OnDemand|float|int|string
    {
        return $this->_query->execute();
    }

    /**
     * returns the select sql for this view
     *
     * @return string
     */
    public function getSelectSql(): string
    {
        return sprintf(self::SELECT, $this->_name);
    }

    /**
     * Get the view sql string
     */
    public function getViewSql(): ?string
    {
        return $this->_sql;
    }

    /**
     * Get the view dql string
     */
    public function getViewDql(): string
    {
        return $this->_dql;
    }
}
