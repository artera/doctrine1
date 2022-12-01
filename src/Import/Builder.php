<?php

namespace Doctrine1\Import;

use Doctrine1\Core;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ValueGenerator;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;

class Builder
{
    /**
     * Path where to generated files
     */
    protected string $path = '';

    /**
     * File suffix to use when writing class definitions
     */
    protected string $suffix = '.php';

    /**
     * Bool true/false for whether or not to generate base classes
     */
    protected bool $generateBaseClasses = true;

    /**
     * Bool true/false for whether or not to generate child table classes
     */
    protected bool $generateTableClasses = false;

    /**
     * Prefix to use for generated base classes
     */
    protected string $baseClassPrefix = 'Base';

    /**
     * Directory to put the generate base classes in
     */
    protected string $baseClassesDirectory = 'generated';

    /**
     * Base class name for generated classes
     * @phpstan-var class-string<\Doctrine1\Record>
     */
    protected string $baseClassName = \Doctrine1\Record::class;

    /**
     * Base table class name for generated classes
     * @phpstan-var class-string<\Doctrine1\Table>
     */
    protected string $baseTableClassName = \Doctrine1\Table::class;

    /**
     * Format to use for generating the model table classes
     */
    protected string $tableClassFormat = '%sTable';

    /**
     * Prefix to all generated classes
     */
    protected string $classPrefix = '';

    public function __construct()
    {
        $manager = \Doctrine1\Manager::getInstance();
        if ($tableClass = $manager->getTableClass()) {
            $this->baseTableClassName = $tableClass;
        }
        if ($classPrefix = $manager->getModelClassPrefix()) {
            $this->classPrefix = $classPrefix;
        }
        if ($tableClassFormat = $manager->getTableClassFormat()) {
            $this->tableClassFormat = $tableClassFormat;
        }
    }

    /**
     * @param string $path the path where imported files are being generated
     */
    public function setTargetPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Specify whether or not to generate classes which extend from generated base classes
     */
    public function generateBaseClasses(?bool $bool = null): bool
    {
        if ($bool !== null) {
            $this->generateBaseClasses = $bool;
        }

        return $this->generateBaseClasses;
    }

    /**
     * Specify whether or not to generate children table classes
     */
    public function generateTableClasses(?bool $bool = null): bool
    {
        if ($bool !== null) {
            $this->generateTableClasses = $bool;
        }

        return $this->generateTableClasses;
    }

    /**
     * @return string the path where imported files are being generated
     */
    public function getTargetPath(): string
    {
        return $this->path;
    }

    public function setOptions(array $options): void
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    public function setOption(string $key, mixed $value): void
    {
        $name = 'set' . \Doctrine1\Inflector::classify($key);

        if (method_exists($this, $name)) {
            $this->$name($value);
        } else {
            $this->$key = $value;
        }
    }

    /**
     * Build the table definition of a \Doctrine1\Record object
     */
    public function buildTableDefinition(ClassGenerator $gen, array $definition, string $prefix = ''): void
    {
        $type = $definition['inheritance']['type'] ?? null;
        if ($type === 'simple' || $type === 'column_aggregation') {
            return;
        }

        $ret = [];

        if ($type === 'concrete') {
            $ret[] = 'parent::setTableDefinition();';
        }

        if (!empty($definition['tableName'])) {
            $ret[] = "\$this->setTableName({$this->varExport($definition['tableName'])});";
        }

        if (!empty($definition['columns']) && is_array($definition['columns'])) {
            $ret[] = $this->buildColumns($definition['columns']);
        }

        if (!empty($definition['indexes']) && is_array($definition['indexes'])) {
            $ret[] = $this->buildIndexes($definition['indexes']);
        }

        if (!empty($definition['attributes']) && is_array($definition['attributes'])) {
            $ret[] = $this->buildAttributes($definition['attributes']);
        }

        if (!empty($definition['options']) && is_array($definition['options'])) {
            $ret[] = $this->buildOptions($definition['options']);
        }

        if (!empty($definition['checks']) && is_array($definition['checks'])) {
            $ret[] = $this->buildChecks($definition['checks']);
        }

        if (!empty($definition['inheritance']['subclasses']) && is_array($definition['inheritance']['subclasses'])) {
            $subClasses = [];
            foreach ($definition['inheritance']['subclasses'] as $className => $def) {
                $className = $this->classPrefix . $className;
                $subClasses[$className] = $def;
            }
            $ret[] = "\$this->setSubClasses({$this->varExport($subClasses)});";
        }

        $code = implode("\n", $ret);
        $code = trim($code);

        $method = new MethodGenerator('setTableDefinition', body: $code);
        $method->setReturnType('void');
        $gen->addMethodFromGenerator($method);
    }

    public function buildSetUp(ClassGenerator $gen, array $definition): void
    {
        $ret = [];

        if (!empty($definition['relations']) && is_array($definition['relations'])) {
            foreach ($definition['relations'] as $name => $relation) {
                $class = $relation['class'] ?? $name;
                $alias = (isset($relation['alias']) && $relation['alias'] !== $this->classPrefix . $relation['class']) ? " as {$relation['alias']}" : '';

                if (!isset($relation['type'])) {
                    $relation['type'] = \Doctrine1\Relation::ONE;
                }

                if ($relation['type'] === \Doctrine1\Relation::ONE) {
                    $hasMethod = 'hasOne';
                } else {
                    $hasMethod = 'hasMany';
                }

                $relExport = array_intersect_key($relation, array_flip([
                    'refClass',
                    'refClassRelationAlias',
                    'local',
                    'foreign',
                    'onDelete',
                    'onUpdate',
                    'cascade',
                    'equal',
                    'owningSide',
                    'foreignKeyName',
                    'orderBy',
                    'deferred',
                ]));

                if (!empty($relExport['refClass'])) {
                    $relExport['refClass'] = $this->classPrefix . $relExport['refClass'];
                }

                $row = "\$this->$hasMethod({$this->varExport($this->classPrefix . $class . $alias)}, {$this->varExport($relExport)});\n";

                $sortBy = $relation['alias'] ?? $relation['class'] ?? $name;
                $ret[$sortBy] = $row;
            }

            ksort($ret);
            $ret = array_values($ret);
        }

        if (!empty($definition['listeners']) && is_array($definition['listeners'])) {
            $ret[] = $this->buildListeners($definition['listeners']);
        }

        $code = implode("\n", $ret);
        $code = trim($code);

        if (!empty($code)) {
            $method = new MethodGenerator('setUp', body: "parent::setUp();\n$code");
            $method->setReturnType('void');
            $gen->addMethodFromGenerator($method);
        }
    }

    /**
     * Build php code for record checks
     */
    public function buildChecks(array $checks): string
    {
        $build = '';
        foreach ($checks as $check) {
            $build .= "\$this->check({$this->varExport($check)});\n";
        }
        return $build;
    }

    public function buildColumns(array $columns): ?string
    {
        $manager = \Doctrine1\Manager::getInstance();
        $refl = new \ReflectionClass($this->baseClassName);

        $build = null;
        foreach ($columns as $name => $column) {
            // An alias cannot passed via column name and column alias definition
            if (isset($column['name']) && stripos($column['name'], ' as ') && isset($column['alias'])) {
                throw new \Doctrine1\Import\Exception(
                    sprintf('When using a column alias you cannot pass it via column name and column alias definition (column: %s).', $column['name'])
                );
            }

            // Update column name if an alias is provided
            if (isset($column['alias']) && !isset($column['name'])) {
                $column['name'] = "{$name} as {$column['alias']}";
            }

            $columnName = $column['name'] ?? $name;
            if ($manager->getAutoAccessorOverride()) {
                $e          = explode(' as ', $columnName);
                $fieldName  = $e[1] ?? $e[0];
                $classified = \Doctrine1\Inflector::classify($fieldName);
                $getter     = "get$classified";
                $setter     = "set$classified";

                if ($refl->hasMethod($getter) || $refl->hasMethod($setter)) {
                    throw new \Doctrine1\Import\Exception(
                        sprintf('When using the attribute setAutoAccessorOverride() you cannot use the field name "%s" because it is reserved by Doctrine. You must choose another field name.', $fieldName)
                    );
                }
            }

            $build .= "\$this->hasColumn({$this->varExport($columnName)}, {$this->varExport($column['type'])}, {$this->varExport($column['length'])}";

            $options = $column;

            // Remove name, alltypes, ntype. They are not needed in options array
            unset($options['name']);
            unset($options['alltypes']);
            unset($options['ntype']);


            if (!empty($options['primary'])) {
                // Remove notnull => true if the column is primary
                // Primary columns are implied to be notnull in Doctrine
                if (!empty($options['notnull'])) {
                    unset($options['notnull']);
                }

                // Remove default if the value is 0 and the column is a primary key
                // Doctrine defaults to 0 if it is a primary key
                if (isset($options['default']) && $options['default'] == 0) {
                    unset($options['default']);
                }
            }

            foreach ($options as $key => $value) {
                if ($value === null || (is_array($value) && empty($value))) {
                    unset($options[$key]);
                }
            }

            if (is_array($options) && !empty($options)) {
                $build .= ', ' . $this->varExport($options);
            }

            $build .= ");\n";
        }

        return $build;
    }

    /**
     * Build the phpDoc for a class definition
     */
    public function buildPhpDocs(ClassGenerator $gen, array $columns, array $relations, string $topLevelClass): void
    {
        $docBlock = new DocBlockGenerator();
        $docBlock->setWordWrap(false);

        foreach ($columns as $name => &$column) {
            $name = $column['name'] ?? $name;
            // extract column name & field name
            $parts = preg_split('/\s+as\s+/i', $name, 2) ?: [$name];
            $column['field_name'] = trim($parts[1] ?? $name);
        }

        uasort($columns, fn ($a, $b) => $a['name'] <=> $b['name']);
        foreach ($columns as &$column) {
            $types = [];
            switch (strtolower($column['type'])) {
                case 'boolean':
                case 'integer':
                case 'float':
                case 'string':
                case 'array':
                case 'object':
                default:
                    $types[] = strtolower($column['type']);
                    break;
                case 'decimal':
                    $types[] = 'float';
                    break;
                case 'set':
                    $types[] = 'string[]';
                    break;
                case 'json':
                case 'blob':
                case 'clob':
                case 'timestamp':
                case 'time':
                case 'date':
                case 'datetime':
                case 'enum':
                case 'gzip':
                    $types[] = 'string';
                    break;
            }

            // Add "null" union types for columns that aren't marked as notnull = true
            // But not our primary columns, as they're notnull = true implicitly in Doctrine
            if (empty($column['notnull']) && empty($column['primary'])) {
                $types[] = 'null';
            }

            $docBlock->setTag(new PropertyTag($column['field_name'], $types));
        }

        usort($relations, fn ($a, $b) => $a['alias'] <=> $b['alias']);
        foreach ($relations as $relation) {
            $fieldName = $relation['alias'];
            $types = [];
            if (isset($relation['type']) && $relation['type'] == \Doctrine1\Relation::MANY) {
                $types[] = "\Doctrine1\Collection<{$relation['class']}>";
            } else {
                $types[] = $this->classPrefix . $relation['class'];

                $column = $columns[$relation['local']];
                if (empty($column['notnull']) && empty($column['primary'])) {
                    $types[] = 'null';
                }
            }
            $docBlock->setTag(new PropertyTag($fieldName, $types));
        }

        $genericOver = sprintf($this->tableClassFormat, $topLevelClass);
        $docBlock->setTag([
            'name' => 'phpstan-extends',
            'content' => "{$gen->getExtendedClass()}<{$genericOver}>",
        ]);

        $gen->setDocBlock($docBlock);
    }

    public function buildListeners(array $listeners): string
    {
        $build = '';

        foreach ($listeners as $name => $options) {
            if (!is_array($options) && $options !== null) {
                $name = $options;
                $options = null;
            }

            $useOptions = empty($options['useOptions']) ? '[]' : '$this->getTable()->getOptions()';
            $class = $options['class'] ?? $name;

            $build .= "\$this->addListener(new $class($useOptions), {$this->varExport($name)});\n";
        }

        return $build;
    }

    public function buildAttributes(array $attributes): string
    {
        $build = "\n";
        foreach ($attributes as $key => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }

            $values = [];
            foreach ($value as $attr) {
                if (is_string($attr)) {
                    $const = Core::class . '::' . strtoupper($key) . '_' . strtoupper($attr);
                    if (defined($const)) {
                        $values[] = '\\' . $const;
                        continue;
                    }
                }
                $values[] = $this->varExport($attr);
            }

            $values = implode(' ^ ', $values);

            $build .= match ($key) {
                'coll_key' => "\$this->setCollectionKey($values);\n",
                'idxname_format' => "\$this->setIndexNameFormat($values);\n",
                'seqname_format' => "\$this->setSequenceNameFormat($values);\n",
                'tblname_format' => "\$this->setTableNameFormat($values);\n",
                'fkname_format' => "\$this->setForeignKeyNameFormat($values);\n",
                'quote_identifier' => "\$this->setQuoteIdentifier($values);\n",
                'seqcol_name' => "\$this->setSequenceColumnName($values);\n",
                'use_dql_callbacks' => "\$this->setUseDqlCallbacks($values);\n",
                'export' => "\$this->setExportFlags($values);\n",
                'portability' => "\$this->setPortability($values);\n",
                'decimal_places' => "\$this->setDecimalPlaces($values);\n",
                'validate' => "\$this->setValidate($values);\n",
                'limit' => "\$this->setLimit($values);\n",
                'use_native_set' => "\$this->setUseNativeSet($values);\n",
                'use_native_enum' => "\$this->setUseNativeEnum($values);\n",
                'hydrate_overwrite' => "\$this->setHydrateOverwrite($values);\n",
                'query_cache_lifespan' => "\$this->setQueryCacheLifespan($values);\n",
                'result_cache_lifespan' => "\$this->setResultCacheLifespan($values);\n",
                'max_identifier_length' => "\$this->setMaxIdentifierLength($values);\n",
                'charset' => "\$this->setCharset($values);\n",
                'collate' => "\$this->setCollate($values);\n",
                'default_sequence' => "\$this->setDefaultSequence($values);\n",
                'default_column_options' => "\$this->setDefaultColumnOptions($values);\n",
                'default_identifier_options' => "\$this->setDefaultIdentifierOptions($values);\n",
                'auto_free_query_objects' => "\$this->setAutoFreeQueryObjects($values);\n",
                'load_references' => "\$this->setLoadReferences($values);\n",
                'auto_accessor_override' => "\$this->setAutoAccessorOverride($values);\n",
                'cascade_saves' => "\$this->setCascadeSaves($values);\n",
                'query_class' => "\$this->setQueryClass($values);\n",
                'collection_class' => "\$this->setCollectionClass($values);\n",
                'table_class' => "\$this->setTableClass($values);\n",
                'table_class_format' => "\$this->setTableClassFormat($values);\n",
                'model_class_prefix' => "\$this->setModelClassPrefix($values);\n",
                default => '',
            };
        }

        return $build;
    }

    public function buildOptions(array $options): string
    {
        $build = '';
        foreach ($options as $name => $value) {
            $build .= "\$this->getTable()->$name = {$this->varExport($value)};\n";
        }
        return $build;
    }

    public function buildIndexes(array $indexes): string
    {
        $build = '';
        foreach ($indexes as $indexName => $definitions) {
            $build .= "\n\$this->index({$this->varExport($indexName)}, {$this->varExport($definitions)});";
        }
        return $build;
    }

    public function buildToString(ClassGenerator $gen, array $definition): void
    {
        if (empty($definition['toString'])) {
            return;
        }

        $method = new MethodGenerator('__toString', body: "(string) \$this->{$definition['toString']};");
        $method->setReturnType('string');
        $gen->addMethodFromGenerator($method);
    }

    public function buildDefinition(string $className, array $definition): string
    {
        $gen = new ClassGenerator();

        if ($this->classPrefix) {
            $definition['className'] = $this->classPrefix . $definition['className'];
            if (isset($definition['connectionClassName'])) {
                $definition['connectionClassName'] = $this->classPrefix . $definition['connectionClassName'];
            }
            $definition['topLevelClassName'] = $this->classPrefix . $definition['topLevelClassName'];
            if (isset($definition['inheritance']['extends'])) {
                $definition['inheritance']['extends'] = $this->classPrefix . $definition['inheritance']['extends'];
            }
        }

        $gen->setName($this->classPrefix . $className);
        $gen->setExtendedClass($definition['inheritance']['extends'] ?? $this->baseClassName);
        $gen->setAbstract($definition['abstract'] ?? false);

        if (empty($definition['no_definition'])) {
            $this->buildTableDefinition($gen, $definition, $this->classPrefix);
            $this->buildSetUp($gen, $definition);
        }

        $this->buildToString($gen, $definition);

        if (!empty($definition['is_base_class']) || !$this->generateBaseClasses()) {
            $this->buildPhpDocs($gen, $definition['columns'], $definition['relations'] ?? [], $definition['topLevelClassName']);
        }

        return $gen->generate();
    }

    public function buildRecord(array $definition): void
    {
        if (!isset($definition['className'])) {
            throw new \Doctrine1\Import\Builder\Exception('Missing class name.');
        }

        $definition['topLevelClassName'] = $definition['className'];

        if ($this->generateBaseClasses()) {
            // Top level definition that extends from all the others
            $topLevel = $definition;
            unset($topLevel['tableName']);

            // If we have a package then we need to make this extend the package definition and not the base definition
            // The package definition will then extends the base definition
            $topLevel['inheritance']['extends'] = $this->baseClassPrefix . $topLevel['className'];
            $topLevel['no_definition']          = true;
            $topLevel['generate_once']          = true;
            $topLevel['is_main_class']          = true;
            unset($topLevel['connection']);

            $topLevel['tableClassName']              = sprintf($this->tableClassFormat, $topLevel['className']);
            $topLevel['inheritance']['tableExtends'] = isset($definition['inheritance']['extends']) ? sprintf($this->tableClassFormat, $definition['inheritance']['extends']) : $this->baseTableClassName;

            $baseClass                    = $definition;
            $baseClass['className']       = $this->baseClassPrefix . $baseClass['className'];
            $baseClass['abstract']        = true;
            $baseClass['override_parent'] = false;
            $baseClass['is_base_class']   = true;

            $this->writeDefinition($baseClass);

            $this->writeDefinition($topLevel);
        } else {
            $this->writeDefinition($definition);
        }
    }

    public function buildTableClassDefinition(string $className, array $definition, array $options = []): string
    {
        /** @var class-string<\Doctrine1\Table> */
        $extends = $options['extends'] ?? $this->baseTableClassName;
        if ($extends !== $this->baseTableClassName) {
            /** @var class-string<\Doctrine1\Table> */
            $extends = $this->classPrefix . $extends;
        }

        $docBlock = null;
        if (isset($definition['topLevelClassName'])) {
            $docBlock = new DocBlockGenerator();
            $docBlock->setWordWrap(false);
            $docBlock->setTag([
                'name' => 'phpstan-extends',
                'content' => sprintf('%s<%s>', $extends, $definition['topLevelClassName']),
            ]);
        }

        $gen = new ClassGenerator($className, docBlock: $docBlock);
        $gen->setExtendedClass($extends);

        $getInstanceBody = <<<PHP
        return \Doctrine1\Core::getTable({$definition['className']}::class);
        PHP;

        $method = new MethodGenerator('getInstance', body: $getInstanceBody);
        $method->setStatic(true);
        $method->setReturnType($className);
        $gen->addMethodFromGenerator($method);

        return $gen->generate();
    }

    public function writeTableClassDefinition(array $definition, string $path, array $options = []): void
    {
        if ($this->classPrefix) {
            $className = $this->classPrefix . $definition['tableClassName'];
            $fileName = $className . $this->suffix;
        } else {
            $className = $definition['tableClassName'];
            $fileName  = $className . $this->suffix;
        }

        \Doctrine1\Lib::makeDirectories($path);
        $path .= DIRECTORY_SEPARATOR . $fileName;

        $code = $this->buildTableClassDefinition($className, $definition, $options);

        \Doctrine1\Lib::makeDirectories(dirname($path));

        if (!file_exists($path) && file_put_contents($path, "<?php\n\n$code") === false) {
            throw new \Doctrine1\Import\Builder\Exception("Couldn't write file $path");
        }

        \Doctrine1\Core::loadModel($className, $path);
    }

    public function writeDefinition(array $definition): void
    {
        $className = $definition['className'];
        $code = $this->buildDefinition($className, $definition);
        $fileName = $className . $this->suffix;
        $path = $this->path;

        if (!empty($definition['is_main_class'])) {
            if ($this->generateTableClasses()) {
                $this->writeTableClassDefinition($definition, $path, ['extends' => $definition['inheritance']['tableExtends']]);
            }
        } elseif (!empty($definition['is_base_class'])) {
            $path .= DIRECTORY_SEPARATOR . $this->baseClassesDirectory;
        }

        // If we have a writePath from the if else conditionals above then use it
        \Doctrine1\Lib::makeDirectories($path);
        $path .= DIRECTORY_SEPARATOR . $fileName;

        if (!empty($definition['connection'])) {
            $code .= "// Connection Component Binding\n";
            $code .= "\Doctrine1\Manager::getInstance()->bindComponent({$this->varExport($definition['connectionClassName'])}, {$this->varExport($definition['connection'])});\n";
        }

        if (empty($definition['generate_once']) || !file_exists($path)) {
            if (file_put_contents($path, "<?php\n\n$code") === false) {
                throw new \Doctrine1\Import\Builder\Exception("Couldn't write file $path");
            }
        }

        \Doctrine1\Core::loadModel($className, $path);
    }

    private function varExport(mixed $var): string
    {
        return (string) (new ValueGenerator($var));
    }
}
