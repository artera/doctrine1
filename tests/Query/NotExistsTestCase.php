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
 * Doctrine_Query_JoinCondition_TestCase
 *
 * @package     Doctrine
 * @author      Jay Klehr <jay@diablomedia.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_NotExists_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables = array('User', 'Group', 'GroupUser');

        parent::prepareTables();
    }

    public function prepareData()
    {
    }

    public function testInitData()
    {
    }

    public function testQueryDoesNotSeparateNotAndExists()
    {
        $q = new Doctrine_Query();

        $q->select('u.id')
          ->from('User u')
          ->where('NOT EXISTS (SELECT g.id FROM Group g)');

        $this->assertEqual($q->getSqlQuery(), 'SELECT e.id AS e__id FROM entity e WHERE (NOT EXISTS (SELECT e2.id AS e2__id FROM entity e2 WHERE (e2.type = 1)) AND (e.type = 0))');
    }
}
