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
 * Doctrine_UnitOfWork_TestCase
 *
 * @package  Doctrine
 * @author   Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category Object Relational Mapping
 * @link     www.doctrine-project.org
 * @since    1.0
 * @version  $Revision$
 */
class Doctrine_UnitOfWork_TestCase extends Doctrine_UnitTestCase
{
    private $correct  = ['Task', 'ResourceType', 'Resource', 'Assignment', 'ResourceReference'];
    private $correct2 = [
              0 => 'Resource',
              1 => 'Task',
              2 => 'ResourceType',
              3 => 'Assignment',
              4 => 'ResourceReference',
            ];
    public function testbuildFlushTree()
    {
        $task = new Task();

        $tree = $this->unitOfWork->buildFlushTree(['Task']);
        $this->assertEqual($tree, ['Task']);

        $tree = $this->unitOfWork->buildFlushTree(['Task','Resource']);
        $this->assertEqual($tree, ['Task', 'Resource', 'Assignment']);

        $tree = $this->unitOfWork->buildFlushTree(['Task', 'Assignment', 'Resource']);
        $this->assertEqual($tree, ['Task', 'Resource', 'Assignment']);

        $tree = $this->unitOfWork->buildFlushTree(['Assignment', 'Task', 'Resource']);
        $this->assertEqual($tree, ['Resource', 'Task', 'Assignment']);
    }
    public function testbuildFlushTree2()
    {
        $this->correct = ['Forum_Category','Forum_Board','Forum_Thread'];

        $tree = $this->unitOfWork->buildFlushTree(['Forum_Board']);
        $this->assertEqual($tree, ['Forum_Board']);

        $tree = $this->unitOfWork->buildFlushTree(['Forum_Category','Forum_Board']);
        $this->assertEqual($tree, ['Forum_Category', 'Forum_Board']);
    }
    public function testBuildFlushTree3()
    {
        $this->correct = ['Forum_Category','Forum_Board','Forum_Thread','Forum_Entry'];

        $tree = $this->unitOfWork->buildFlushTree(['Forum_Entry','Forum_Board']);
        $this->assertEqual($tree, ['Forum_Entry','Forum_Board']);

        $tree = $this->unitOfWork->buildFlushTree(['Forum_Board','Forum_Entry']);
        $this->assertEqual($tree, ['Forum_Board','Forum_Entry']);
    }
    public function testBuildFlushTree4()
    {
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board']);
        $this->assertEqual($tree, ['Forum_Board', 'Forum_Thread']);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread']);
        $this->assertEqual($tree, ['Forum_Board','Forum_Thread']);
    }
    public function testBuildFlushTree5()
    {
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread','Forum_Entry']);
        $this->assertEqual($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Board','Forum_Entry','Forum_Thread']);
        $this->assertEqual($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
    }
    public function testBuildFlushTree6()
    {
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Entry','Forum_Board','Forum_Thread']);
        $this->assertEqual($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Entry','Forum_Thread','Forum_Board']);
        $this->assertEqual($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
    }
    public function testBuildFlushTree7()
    {
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board','Forum_Entry']);
        $this->assertEqual($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Thread','Forum_Entry','Forum_Board']);
        $this->assertEqual($tree, ['Forum_Board','Forum_Thread','Forum_Entry']);
    }
    public function testBuildFlushTree8()
    {
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread','Forum_Category']);
        $this->assertEqual($tree, ['Forum_Category','Forum_Board','Forum_Thread']);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Category','Forum_Thread','Forum_Board']);
        $this->assertEqual($tree, ['Forum_Category','Forum_Board','Forum_Thread']);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board','Forum_Category']);
        $this->assertEqual($tree, ['Forum_Category','Forum_Board','Forum_Thread']);
    }
    public function testBuildFlushTree9()
    {
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread','Forum_Category','Forum_Entry']);
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Board','Forum_Thread','Forum_Entry','Forum_Category']);
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Board','Forum_Category','Forum_Thread','Forum_Entry']);
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree10()
    {
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Entry','Forum_Thread','Forum_Board','Forum_Category']);
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Entry','Forum_Thread','Forum_Category','Forum_Board']);
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Entry','Forum_Category','Forum_Board','Forum_Thread']);
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree11()
    {
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Thread','Forum_Category','Forum_Board','Forum_Entry']);
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Thread','Forum_Entry','Forum_Category','Forum_Board']);
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Thread','Forum_Board','Forum_Entry','Forum_Category']);
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree12()
    {
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Category','Forum_Entry','Forum_Board','Forum_Thread']);
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Category','Forum_Thread','Forum_Entry','Forum_Board']);
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(['Forum_Category','Forum_Board','Forum_Thread','Forum_Entry']);
        $this->assertEqual($tree, $this->correct);
    }
}
