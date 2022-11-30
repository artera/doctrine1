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
        \Doctrine1\Core::setModelsDirectory(__DIR__ . '/DC95/models');

        $import = new \Doctrine1\Import\Schema();
        $import->setOptions(
            [
            'baseClassesDirectory' => '',
            'baseClassPrefix'      => 'Base_',
            'classPrefix'          => 'DC95_',
            ]
        );
        $modelsPath = __DIR__ . '/DC95/models';
        $import->importSchema([__DIR__ . '/DC95/schema.yml'], 'yml', $modelsPath);

        \Doctrine1\Core::setModelsDirectory(null);
        \Doctrine1\Lib::removeDirectories(__DIR__ . '/DC95/models');
    }
}
