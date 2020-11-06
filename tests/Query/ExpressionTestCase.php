<?php
/* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
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
 * Doctrine_Query_Expression_TestCase
 *
 * @package  Doctrine
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author   Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @version  $Revision$
 * @category Object Relational Mapping
 * @link     www.doctrine-project.org
 * @since    1.0
 */
class Doctrine_Query_Expression_TestCase extends Doctrine_UnitTestCase
{
    public function testUnknownExpressionInSelectClauseThrowsException()
    {
        // Activate portability all
        $this->conn->setAttribute(Doctrine_Core::ATTR_PORTABILITY, Doctrine_Core::PORTABILITY_ALL);

        try {
            $q = Doctrine_Query::create()
                ->parseDqlQuery("SELECT SOMEUNKNOWNFUNC(u.name, ' ', u.loginname) FROM User u");

            $sql = $q->getSqlQuery();

            $this->fail('SOMEUNKNOWNFUNC() should throw an Exception, but actually it passed and generated the SQL: ' . $sql);
        } catch (Doctrine_Query_Exception $e) {
            $this->pass();
        }

        // Reassign old portability mode
        $this->conn->setAttribute(Doctrine_Core::ATTR_PORTABILITY, Doctrine_Core::PORTABILITY_ALL);
    }


    public function testUnknownExpressionInSelectClauseDoesntThrowException()
    {
        // Deactivate portability expression mode
        $this->conn->setAttribute(Doctrine_Core::ATTR_PORTABILITY, Doctrine_Core::PORTABILITY_ALL ^ Doctrine_Core::PORTABILITY_EXPR);

        try {
            $q = Doctrine_Query::create()
                ->parseDqlQuery("SELECT SOMEUNKNOWNFUNC(u.name, ' ', u.loginname) FROM User u");

            $sql = $q->getSqlQuery();

            $this->pass();
        } catch (Doctrine_Query_Exception $e) {
            $this->fail('SOMEUNKNOWNFUNC() should pass, but actually it fail with message: ' . $e->getMessage());
        }

        // Reassign old portability mode
        $this->conn->setAttribute(Doctrine_Core::ATTR_PORTABILITY, Doctrine_Core::PORTABILITY_ALL);
    }

    public function testUnknownColumnWithinFunctionInSelectClauseThrowsException()
    {
        $q = new Doctrine_Query();

        try {
            $q->parseDqlQuery('SELECT CONCAT(u.name, u.unknown) FROM User u');

            $q->execute();
            $this->fail();
        } catch (Doctrine_Query_Exception $e) {
            $this->pass();
        }
    }

    public function testConcatIsSupportedInSelectClause()
    {
        $q = new Doctrine_Query();

        $q->parseDqlQuery('SELECT u.id, CONCAT(u.name, u.loginname) FROM User u');

        $this->assertEqual($q->getSqlQuery(), 'SELECT e.id AS e__id, CONCAT(e.name, e.loginname) AS e__0 FROM entity e WHERE (e.type = 0)');
    }

    public function testConcatInSelectClauseSupportsLiteralStrings()
    {
        $q = new Doctrine_Query();

        $q->parseDqlQuery("SELECT u.id, CONCAT(u.name, 'The Man') FROM User u");

        $this->assertEqual($q->getSqlQuery(), "SELECT e.id AS e__id, CONCAT(e.name, 'The Man') AS e__0 FROM entity e WHERE (e.type = 0)");
    }

    public function testConcatInSelectClauseSupportsMoreThanTwoArgs()
    {
        $q = new Doctrine_Query();

        $q->parseDqlQuery("SELECT u.id, CONCAT(u.name, 'The Man', u.loginname) FROM User u");

        $this->assertEqual($q->getSqlQuery(), "SELECT e.id AS e__id, CONCAT(e.name, 'The Man', e.loginname) AS e__0 FROM entity e WHERE (e.type = 0)");
    }

    public function testNonPortableFunctionsAreSupported()
    {
        $query = new Doctrine_Query();
        // we are using stored procedure here, so adjust portability settings
        $this->conn->setAttribute(Doctrine_Core::ATTR_PORTABILITY, Doctrine_Core::PORTABILITY_ALL ^ Doctrine_Core::PORTABILITY_EXPR);

        $lat    = '13.23';
        $lon    = '33.23';
        $radius = '33';

        $query->select("l.*, GeoDistKM(l.lat, l.lon, $lat, $lon) distance")
            ->from('Location l')
            ->where('l.id <> ? AND l.lat > ?', [1, 0])
            ->having("distance < $radius")
            ->orderby('distance ASC')
            ->groupby('l.id')
            ->limit(5);

        $this->assertEqual($query->getSqlQuery(), 'SELECT l.id AS l__id, l.lat AS l__lat, l.lon AS l__lon, GeoDistKM(l.lat, l.lon, 13.23, 33.23) AS l__0 FROM location l WHERE (l.id <> ? AND l.lat > ?) GROUP BY l.id HAVING l__0 < 33 ORDER BY l__0 ASC LIMIT 5');

        $this->conn->setAttribute(Doctrine_Core::ATTR_PORTABILITY, Doctrine_Core::PORTABILITY_ALL);
    }
}
