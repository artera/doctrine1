<?php
namespace Tests;

class ParserTest extends DoctrineUnitTestCase
{
    public function testGetParserInstance()
    {
        $instance = \Doctrine_Parser::getParser('Yml');
        $this->assertInstanceOf(\Doctrine_Parser_Yml::class, $instance);
    }

    public function testFacadeLoadAndDump()
    {
        \Doctrine_Parser::dump(['test' => 'good job', 'test2' => true, ['testing' => false]], 'yml', 'test.yml');
        $array = \Doctrine_Parser::load('test.yml', 'yml');

        $this->assertEquals($array, ['test' => 'good job', 'test2' => true, ['testing' => false]]);
        unlink('test.yml');
    }

    public function testParserSupportsEmbeddingPhpSyntax()
    {
        $parser = \Doctrine_Parser::getParser('Yml');
        $yml    = "---
test: good job
test2: true
testing: <?php echo 'false'.\"\n\"; ?>
w00t: not now
";
        $data = $parser->doLoad($yml);

        $array = $parser->loadData($data);

        $this->assertEquals($array, ['test' => 'good job', 'test2' => true, 'testing' => false, 'w00t' => 'not now']);
    }

    public function testParserWritingToDisk()
    {
        $parser = \Doctrine_Parser::getParser('Yml');
        $parser->doDump('test', 'test.yml');

        $this->assertEquals('test', file_get_contents('test.yml'));
        unlink('test.yml');
    }

    public function testParserReturningLoadedData()
    {
        $parser = \Doctrine_Parser::getParser('Yml');
        $result = $parser->doDump('test');

        $this->assertEquals('test', $result);
    }

    public function testLoadFromString()
    {
        $yml = "---
test: good job
test2: true
testing: <?php echo 'false'.\"\n\"; ?>
w00t: not now
";

        $array = \Doctrine_Parser::load($yml, 'yml');

        $this->assertEquals($array, ['test' => 'good job', 'test2' => true, 'testing' => false, 'w00t' => 'not now']);
    }
}
