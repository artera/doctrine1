<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketDC95Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testClassDoesNotExistBeforeImport()
    {
        $this->assertFalse(class_exists('Base_DC95_Article'));
        $this->assertFalse(class_exists('Base_DC95_Article_Category'));
        $this->assertFalse(class_exists('DC95_Article'));
        $this->assertFalse(class_exists('DC95_Article_Category'));
    }

    public function testClassExistsAfterImport()
    {
        \Doctrine_Core::setModelsDirectory(__DIR__ . '/DC95/models');

        $import = new \Doctrine_Import_Schema();
        $import->setOptions(
            [
            'pearStyle'            => true,
            'baseClassesDirectory' => null,
            'baseClassPrefix'      => 'Base_',
            'classPrefix'          => 'DC95_',
            'classPrefixFiles'     => true
            ]
        );
        $modelsPath = __DIR__ . '/DC95/models';
        $import->importSchema(__DIR__ . '/DC95/schema.yml', 'yml', $modelsPath);

        \Doctrine_Core::setModelsDirectory(null);
        \Doctrine_Lib::removeDirectories(__DIR__ . '/DC95/models');
    }
}
