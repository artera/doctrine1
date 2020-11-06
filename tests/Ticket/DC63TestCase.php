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
 * Doctrine_Ticket_DC63_TestCase
 *
 * @package  Doctrine
 * @author   Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category Object Relational Mapping
 * @link     www.doctrine-project.org
 * @since    1.0
 * @version  $Revision$
 */
class Doctrine_Ticket_DC63_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables[] = 'Ticket_DC63_User';
        parent::prepareTables();
    }

    public function testTest()
    {
        $sql = Doctrine_Core::generateSqlFromArray(['Ticket_DC63_User']);
        $this->assertEqual($sql[0], 'CREATE TABLE ticket__d_c63__user (id INTEGER, email_address VARCHAR(255) UNIQUE, username VARCHAR(255) NOT NULL UNIQUE, password VARCHAR(255), PRIMARY KEY(id, username))');
    }
}

class Ticket_DC63_User extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('id', 'integer', null, ['primary' => true]);
        $this->hasColumn('email_address', 'string', 255, ['unique' => false]);
        $this->hasColumn('username', 'string', 255);
        $this->hasColumn('password', 'string', 255, ['primary' => true]);

        $this->setColumnOptions(['username', 'email_address'], ['unique' => true]);
        $this->setColumnOptions(['username'], ['primary' => true]);
        $this->setColumnOptions(['password'], ['primary' => false]);
        $this->setColumnOption('username', 'notnull', true);
    }
}
