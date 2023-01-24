<?php

namespace Doctrine1\Import;

use Doctrine1\Collection;
use Doctrine1\Core;
use Doctrine1\EnumSetImplementation;
use Doctrine1\Inflector;
use Doctrine1\Lib;
use Doctrine1\Manager;
use Doctrine1\Record;
use Doctrine1\Column;
use Doctrine1\Column\Type;
use Doctrine1\Relation;
use Doctrine1\Table;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ValueGenerator;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use Laminas\Code\Generator\EnumGenerator\Cases\BackedCases;
use Laminas\Code\Generator\EnumGenerator\EnumGenerator;
use Laminas\Code\Generator\EnumGenerator\Name;

class Builder
{
    /**
     * Path where to generated files
     */
    protected string $path = '';

    /**
     * Bool true/false for whether or not to generate base classes
     */
    protected bool $generateBaseClasses = true;

    /**
     * Bool true/false for whether or not to generate child table classes
     */
    protected bool $generateTableClasses = false;

    /**
     * Format to use for generated base classes
     */
    protected string $baseClassFormat = 'Base%s';

    /**
     * Base class name for generated classes
     * @phpstan-var class-string<Record>
     */
    protected string $baseClassName = Record::class;

    /**
     * Base table class name for generated classes
     * @phpstan-var class-string<Table>
     */
    protected string $baseTableClassName = Table::class;

    /**
     * Format to use for generating the model table classes
     */
    protected string $tableClassFormat = '%sTable';

    /**
     * Method to use when implementing enum columns
     */
    protected EnumSetImplementation $enumImplementation = EnumSetImplementation::String;

    /**
     * Method to use when implementing set columns
     */
    protected EnumSetImplementation $setImplementation = EnumSetImplementation::String;

    /**
     * Format to user for generated model classes
     */
    protected string $namespace = '\\';

    public function __construct()
    {
        $conn = Manager::connection();
        if ($tableClass = $conn->getTableClass()) {
            $this->baseTableClassName = $tableClass;
        }
        if ($namespace = $conn->getModelNamespace()) {
            $this->namespace = $namespace;
        }
        if ($tableClassFormat = $conn->getTableClassFormat()) {
            $this->tableClassFormat = $tableClassFormat;
        }
        $this->enumImplementation = $conn->getEnumImplementation();
        $this->setImplementation = $conn->getSetImplementation();
    }

    public function getFullModelClassName(string $name): string
    {
        return Lib::namespaceConcat($this->namespace, $name);
    }

    /** @phpstan-return ($full is true ? class-string<Record> : string) */
    public function getBaseClassName(string $name, bool $full = true): string
    {
        $name = sprintf($this->baseClassFormat, $name);
        if (!$full) {
            return $name;
        }
        return Lib::namespaceConcat($this->namespace, $name);
    }

    public function getTableClassName(string $name, bool $full = true): string
    {
        $name = sprintf($this->tableClassFormat, $name);
        if (!$full) {
            return $name;
        }
        return Lib::namespaceConcat($this->namespace, $name);
    }

    protected function classNameToFileName(string $name): string
    {
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, trim($name, '\\')) . '.php';

        $namespacePath = str_replace('\\', DIRECTORY_SEPARATOR, trim($this->namespace, '\\')) . DIRECTORY_SEPARATOR;
        if (str_starts_with($filename, $namespacePath)) {
            $filename = ltrim(substr($filename, strlen($namespacePath)), DIRECTORY_SEPARATOR);
        }

        return $filename;
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
        $name = Inflector::classify($key);
        $setter = "set$name";
        $property = lcfirst($name);

        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new \Doctrine1\Exception("Unknown property $property on ". $this::class);
        }
    }

    /**
     * Build the table definition of a \Doctrine1\Record object
     */
    public function buildTableDefinition(ClassGenerator $gen, Definition\Table $definition): void
    {
        if ($definition->inheritanceType === 'simple' || $definition->inheritanceType === 'column_aggregation') {
            return;
        }

        $ret = [];

        if ($definition->inheritanceType === 'concrete') {
            $ret[] = 'parent::setTableDefinition();';
        }

        if (!empty($definition->name)) {
            $ret[] = "\$this->setTableName({$this->varExport(Inflector::tableize($definition->name))});";
        }

        $ret[] = $this->buildColumns($gen, $definition->columns);
        $ret[] = $this->buildIndexes($definition->indexes);
        $ret[] = $this->buildAttributes($definition->attributes);
        $ret[] = $this->buildChecks($definition->checks);

        if (!empty($definition->subclasses)) {
            $subClasses = [];
            foreach ($definition->subclasses as $className => $def) {
                $className = $this->getFullModelClassName($className);
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

    public function buildSetUp(ClassGenerator $gen, Definition\Table $definition): void
    {
        $ret = [];

        foreach ($definition->relations as $relation) {
            $class = $relation->class . ($relation->alias !== $relation->class ? " as {$relation->alias}" : '');

            if ($relation->many) {
                $hasMethod = 'hasMany';
            } else {
                $hasMethod = 'hasOne';
            }

            $relExport = array_filter([
                'refClass' => $relation->refClass === null ? null : $this->getFullModelClassName($relation->refClass),
                'refClassRelationAlias' => $relation->refClassRelationAlias,
                'local' => $relation->local,
                'foreign' => $relation->foreign,
                'onDelete' => $relation->onDelete,
                'onUpdate' => $relation->onUpdate,
                'cascade' => $relation->cascade,
                'equal' => $relation->equal,
                'owningSide' => $relation->owningSide,
                'foreignKeyName' => $relation->foreignKeyName,
                'orderBy' => $relation->orderBy,
                'deferred' => $relation->deferred,
            ]);

            $row = "\$this->$hasMethod({$this->varExport($class)}, {$this->varExport($relExport)});\n";

            $sortBy = $relation->alias ?? $relation->class;
            $ret[$sortBy] = $row;
        }

        ksort($ret);
        $ret = array_values($ret);

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

    /**
     * @phpstan-param list<Column> $columns
     */
    public function buildColumns(ClassGenerator $gen, array $columns): string
    {
        $manager = Manager::getInstance();
        $refl = new \ReflectionClass($this->baseClassName);

        $build = '';

        foreach ($columns as $column) {
            if ($manager->getAutoAccessorOverride()) {
                $classified = \Doctrine1\Inflector::classify($column->fieldName);
                $getter     = "get$classified";
                $setter     = "set$classified";

                if ($refl->hasMethod($getter) || $refl->hasMethod($setter)) {
                    throw new Exception(
                        sprintf('When using the attribute setAutoAccessorOverride() you cannot use the field name "%s" because it is reserved by Doctrine. You must choose another field name.', $column->fieldName)
                    );
                }
            }

            $default = $column->hasDefault() ? $column->default : null;
            $values = $column->stringValues();

            if ($default !== null && in_array($column->type, [Type::Set, Type::Enum], true)) {
                $columnImplementation = $column->enumImplementation($manager);

                if ($column->type === Type::Set) {
                    $default = empty($default) ? [] : explode(',', $default);
                }

                if ($columnImplementation === EnumSetImplementation::Enum) {
                    $enumClassName = $column->enumClassName($manager, $gen->getNamespaceName() ?? $this->namespace);

                    if ($enumClassName !== null) {
                        $default = match ($column->type) {
                            Type::Enum => new ValueGenerator("\\$enumClassName::" . Inflector::classify($default), ValueGenerator::TYPE_CONSTANT),
                            Type::Set => array_map(fn ($v) => new ValueGenerator("\\$enumClassName::" . Inflector::classify($v), ValueGenerator::TYPE_CONSTANT), $default),
                        };
                        $values = $enumClassName;
                    }
                }
            }

            $build .= <<<EOF
            \$this->setColumn(new \Doctrine1\Column(
                name: {$this->varExport($column->name)},
                type: {$this->varExport($column->type)},
                length: {$this->varExport($column->length)},
                fieldName: {$this->varExport($column->alias())},
                owner: {$this->varExport($column->owner)},
                primary: {$this->varExport($column->primary)},
                default: {$this->varExport($default, arrayDepth: 1)},
                hasDefault: {$this->varExport($column->hasDefault())},
                notnull: {$this->varExport($column->notnull)},
                values: {$this->varExport($values, arrayDepth: 1)},
                autoincrement: {$this->varExport($column->autoincrement)},
                unique: {$this->varExport($column->unique)},
                protected: {$this->varExport($column->protected)},
                sequence: {$this->varExport($column->sequence)},
                zerofill: {$this->varExport($column->zerofill)},
                unsigned: {$this->varExport($column->unsigned)},
                scale: {$this->varExport($column->scale)},
                fixed: {$this->varExport($column->fixed)},
                comment: {$this->varExport($column->comment)},
                charset: {$this->varExport($column->charset)},
                collation: {$this->varExport($column->collation)},
                check: {$this->varExport($column->check)},
                min: {$this->varExport($column->min)},
                max: {$this->varExport($column->max)},
                extra: {$this->varExport($column->extra, arrayDepth: 1)},
                virtual: {$this->varExport($column->virtual)},
                meta: {$this->varExport($column->meta, arrayDepth: 1)},
                validators: {$this->varExport($column->validators, arrayDepth: 1)},
            ));

            EOF;
        }

        return $build;
    }

    /**
     * Build the phpDoc for a class definition
     */
    public function buildPhpDocs(ClassGenerator $gen, Definition\Table $definition): void
    {
        $manager = Manager::connection();

        $docBlock = new DocBlockGenerator();
        $docBlock->setWordWrap(false);

        $columns = $definition->columns;
        uasort($columns, fn ($a, $b) => $a->fieldName <=> $b->fieldName);
        foreach ($columns as $column) {
            $types = [];

            if (in_array($column->type, [Type::Enum, Type::Set], true)) {
                $columnImplementation = $column->enumImplementation($manager);
                $enumClassName = $column->enumClassName($manager, $gen->getNamespaceName() ?? $this->namespace);

                if (!empty($enumClassName) && $columnImplementation !== null) {
                    $enumCases = [];
                    foreach ($column->stringValues() as $v) {
                        $k = Inflector::classify($v);
                        if (is_string($v) && !empty($v) && !empty($k)) {
                            $enumCases[$k] = $v;
                        }
                    }

                    $enum = EnumGenerator::withConfig([
                        'name' => $enumClassName,
                        'backedCases' => [
                            'type' => 'string',
                            'cases' => $enumCases,
                        ],
                    ]);

                    $path = $this->path . DIRECTORY_SEPARATOR . $this->classNameToFileName($enumClassName);
                    Lib::makeDirectories(dirname($path));

                    if (file_exists($path)) {
                        $newCode = $enum->generate();
                        if (!preg_match("/(?:^\s*case [a-z0-9]+ = (?:'[^']+'|\d+);\n)+/im", $newCode, $matches)) {
                            throw new Exception("Could not match enum cases in generated code for $enumClassName");
                        }
                        $newCode = $matches[0];
                        if (($code = file_get_contents($path)) === false) {
                            throw new Exception("Couldn't read file $path");
                        }

                        // remove all cases from enum's code
                        $code = preg_replace("/\b\s*case\s+[a-z0-9]+\s*=\s*(?:'[^']+'|\d+)\s*;\s*/i", '', $code) ?? $code;

                        // add new enum cases at the top of the enum declaration
                        $code = preg_replace('/^(\s*enum\s+[a-z0-9]+\s*:\s*(?:int|string)\s*\{)(?:\s*(}))?/im', "\$1\n$newCode\$2", $code);
                    } else {
                        $code = "<?php\n\n{$enum->generate()}";
                    }

                    if (file_put_contents($path, $code) === false) {
                        throw new Exception("Couldn't write file $path");
                    }
                }

                $type = match ($columnImplementation) {
                    EnumSetImplementation::String => 'string',
                    EnumSetImplementation::PHPStan => implode('|', array_map([$this, 'varExport'], $column->stringValues())),
                    EnumSetImplementation::Enum => "\\$enumClassName",
                };

                if ($column->type === Type::Set) {
                    if ($columnImplementation === EnumSetImplementation::PHPStan) {
                        $type = "($type)";
                    }
                    $type .= '[]';
                }

                $types[] = $type;
            }

            switch ($column->type) {
                case Type::Enum:
                case Type::Set:
                    break;
                case Type::Boolean:
                case Type::Integer:
                case Type::Float:
                case Type::String:
                case Type::Array:
                case Type::Object:
                default:
                    $types[] = $column->type->value;
                    break;
                case Type::Decimal:
                    $types[] = 'float';
                    break;
                case Type::JSON:
                case Type::BLOB:
                case Type::Timestamp:
                case Type::Time:
                case Type::Date:
                case Type::DateTime:
                    $types[] = 'string';
                    break;
            }

            // Add "null" union types for columns that aren't marked as notnull = true
            // But not our primary columns, as they're notnull = true implicitly in Doctrine
            if (!$column->notnull && !$column->primary) {
                $types[] = 'null';
            }

            $docBlock->setTag(new PropertyTag($column->fieldName, $types));
        }

        $relations = $definition->relations;
        usort($relations, fn ($a, $b) => $a->alias <=> $b->alias);
        foreach ($relations as $relation) {
            $fieldName = $relation->alias;
            $types = [];
            if ($relation->many) {
                $types[] = '\\' . Collection::class . "<\\{$this->getFullModelClassName($relation->class)}>";
            } else {
                $types[] = '\\' . $this->getFullModelClassName($relation->class);

                foreach ($definition->columns as $column) {
                    if ($column->name === $relation->local) {
                        if (!$column->notnull && !$column->primary) {
                            $types[] = 'null';
                        }
                        break;
                    }
                }
            }
            $docBlock->setTag(new PropertyTag($fieldName, $types));
        }

        $genericOver = $this->getTableClassName($definition->topLevelClassName);
        $docBlock->setTag([
            'name' => 'phpstan-extends',
            'content' => "\\{$gen->getExtendedClass()}<\\{$genericOver}>",
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
                'default_identifier_options' => "\$this->setDefaultIdentifierOptions($values);\n",
                'auto_free_query_objects' => "\$this->setAutoFreeQueryObjects($values);\n",
                'load_references' => "\$this->setLoadReferences($values);\n",
                'auto_accessor_override' => "\$this->setAutoAccessorOverride($values);\n",
                'cascade_saves' => "\$this->setCascadeSaves($values);\n",
                'query_class' => "\$this->setQueryClass($values);\n",
                'collection_class' => "\$this->setCollectionClass($values);\n",
                'table_class' => "\$this->setTableClass($values);\n",
                'table_class_format' => "\$this->setTableClassFormat($values);\n",
                'model_class_format' => "\$this->setModelClassFormat($values);\n",
                default => '',
            };
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

    public function buildDefinition(string $className, Definition\Table $definition): string
    {
        $gen = new ClassGenerator();

        $gen->setName($className);
        $gen->setExtendedClass($definition->extends ?? $this->baseClassName);
        $gen->setAbstract($definition->isBaseClass);

        if (!$definition->isMainClass) {
            $this->buildTableDefinition($gen, $definition);
            $this->buildSetUp($gen, $definition);
        }

        if ($definition->isBaseClass || !$this->generateBaseClasses()) {
            $this->buildPhpDocs($gen, $definition);
        }

        return $gen->generate();
    }

    public function buildRecord(Definition\Table $definition): void
    {
        if ($this->generateBaseClasses()) {
            // Top level definition that extends from all the others
            $topLevel = clone $definition;

            // If we have a package then we need to make this extend the package definition and not the base definition
            // The package definition will then extends the base definition
            $topLevel->extends = $this->getBaseClassName($topLevel->className);
            $topLevel->tableExtends = $definition->extends ? $this->getTableClassName($definition->extends) : $this->baseTableClassName;
            $topLevel->isMainClass = true;

            $baseClass = clone $definition;
            $baseClass->isBaseClass = true;

            $this->writeDefinition($baseClass);

            $this->writeDefinition($topLevel);
        } else {
            $this->writeDefinition($definition);
        }
    }

    public function buildTableClassDefinition(string $className, Definition\Table $definition, ?string $extends = null): string
    {
        $extends = $definition->tableExtends ?? $this->baseTableClassName;
        if ($extends !== $this->baseTableClassName) {
            /** @var class-string<Table> */
            $extends = $this->getFullModelClassName($extends);
        }

        $docBlock = new DocBlockGenerator();
        $docBlock->setWordWrap(false);
        $docBlock->setTag([
            'name' => 'phpstan-extends',
            'content' => sprintf('\\%s<\\%s>', $extends, $this->getFullModelClassName($definition->topLevelClassName)),
        ]);

        $gen = new ClassGenerator($className, docBlock: $docBlock);
        $gen->setExtendedClass($extends);

        $getInstanceBody = sprintf('return \\%s::getTable(\\%s::class);', Core::class, $this->getFullModelClassName($definition->className));

        $method = new MethodGenerator('getInstance', body: $getInstanceBody);
        $method->setStatic(true);
        $method->setReturnType($className);
        $gen->addMethodFromGenerator($method);

        return $gen->generate();
    }

    public function writeTableClassDefinition(Definition\Table $definition, string $path): void
    {
        $className = $this->getTableClassName($definition->className);
        $fileName = $this->classNameToFileName($className);

        $path .= DIRECTORY_SEPARATOR . $fileName;
        Lib::makeDirectories(dirname($path));

        $code = $this->buildTableClassDefinition($className, $definition);

        if (!file_exists($path) && file_put_contents($path, "<?php\n\n$code") === false) {
            throw new Exception("Couldn't write file $path");
        }

        Core::loadModel($className, $path);
    }

    public function writeDefinition(Definition\Table $definition): void
    {
        $path = $this->path;

        $className = !$definition->isMainClass
            ? $this->getBaseClassName($definition->className)
            : $this->getFullModelClassName($definition->className);

        if ($definition->isMainClass && $this->generateTableClasses()) {
            $this->writeTableClassDefinition($definition, $path);
        }

        $fileName = $this->classNameToFileName($className);

        $path .= DIRECTORY_SEPARATOR . $fileName;
        Lib::makeDirectories(dirname($path));

        $code = $this->buildDefinition($className, $definition);

        if ((!$definition->isMainClass || !file_exists($path)) && file_put_contents($path, "<?php\n\n$code") === false) {
            throw new Exception("Couldn't write file $path");
        }

        Core::loadModel($className, $path);
    }

    private function varExport(mixed $var, bool $multiline = true, int $arrayDepth = 0): string
    {
        if ($var instanceof ValueGenerator) {
            $gen = $var;
        } else {
            $gen = new ValueGenerator($var, outputMode: $multiline ? ValueGenerator::OUTPUT_MULTIPLE_LINE : ValueGenerator::OUTPUT_SINGLE_LINE);
        }
        if ($arrayDepth > 0) {
            $gen->setArrayDepth($arrayDepth);
        }
        return (string) $gen;
    }
}
