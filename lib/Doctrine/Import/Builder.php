<?php
/*
 *  $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
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
 * Doctrine_Import_Builder
 *
 * Import builder is responsible of building Doctrine_Record classes
 * based on a database schema.
 *
 * @package    Doctrine
 * @subpackage Import
 * @link       www.doctrine-project.org
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since      1.0
 * @version    $Revision: 7490 $
 * @author     Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author     Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @author     Nicolas Bérard-Nault <nicobn@php.net>
 * @author     Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Import_Builder extends Doctrine_Builder
{
    /**
     * Path where to generated files
     *
     * @var string $_path
     */
    protected $_path = '';

    /**
     * Class prefix for generated packages
     *
     * @var string
     */
    protected $_packagesPrefix = 'Package';

    /**
     * Path to generate packages
     *
     * @var string
     */
    protected $_packagesPath = '';

    /**
     * Name of folder to generate packages in
     *
     * @var string
     */
    protected $_packagesFolderName = 'packages';

    /**
     * File suffix to use when writing class definitions
     *
     * @var string $_suffix
     */
    protected $_suffix = '.php';

    /**
     * Bool true/false for whether or not to generate base classes
     *
     * @var boolean $_generateBaseClasses
     */
    protected $_generateBaseClasses = true;

    /**
     * Bool true/false for whether or not to generate child table classes
     *
     * @var boolean $_generateTableClasses
     */
    protected $_generateTableClasses = false;

    /**
     * Prefix to use for generated base classes
     *
     * @var string
     */
    protected $_baseClassPrefix = 'Base';

    /**
     * Directory to put the generate base classes in
     *
     * @var string $_baseClassesDirectory
     */
    protected $_baseClassesDirectory = 'generated';

    /**
     * Base class name for generated classes
     *
     * @var string
     */
    protected $_baseClassName = 'Doctrine_Record';

    /**
     * Base table class name for generated classes
     *
     * @var string
     */
    protected $_baseTableClassName = 'Doctrine_Table';

    /**
     * Format to use for generating the model table classes
     *
     * @var string
     */
    protected $_tableClassFormat = '%sTable';

    /**
     * Prefix to all generated classes
     *
     * @var string
     */
    protected $_classPrefix = null;

    /**
     * Whether to use the class prefix for the filenames too
     *
     * @var boolean
     **/
    protected $_classPrefixFiles = true;

    /**
     * Whether or not to generate PEAR style directories and files
     *
     * @var boolean
     */
    protected $_pearStyle = false;

    /**
     * The package name to use for the generated php docs
     *
     * @var string
     */
    protected $_phpDocPackage = '##PACKAGE##';

    /**
     * The subpackage name to use for the generated php docs
     *
     * @var string
     */
    protected $_phpDocSubpackage = '##SUBPACKAGE##';

    /**
     * Full name of the author to use for the generated php docs
     *
     * @var string
     */
    protected $_phpDocName = '##NAME##';

    /**
     * Email of the author to use for the generated php docs
     *
     * @var string
     */
    protected $_phpDocEmail = '##EMAIL##';

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $manager = Doctrine_Manager::getInstance();
        if ($tableClass = $manager->getAttribute(Doctrine_Core::ATTR_TABLE_CLASS)) {
            $this->_baseTableClassName = $tableClass;
        }
        if ($classPrefix = $manager->getAttribute(Doctrine_Core::ATTR_MODEL_CLASS_PREFIX)) {
            $this->_classPrefix = $classPrefix;
        }
        if ($tableClassFormat = $manager->getAttribute(Doctrine_Core::ATTR_TABLE_CLASS_FORMAT)) {
            $this->_tableClassFormat = $tableClassFormat;
        }
    }

    /**
     * setTargetPath
     *
     * @param  string $path the path where imported files are being generated
     * @return void
     */
    public function setTargetPath($path)
    {
        if ($path) {
            if (!$this->_packagesPath) {
                $this->setOption('packagesPath', $path . DIRECTORY_SEPARATOR . $this->_packagesFolderName);
            }

            $this->_path = $path;
        }
    }

    /**
     * generateBaseClasses
     *
     * Specify whether or not to generate classes which extend from generated base classes
     *
     * @param  boolean $bool
     * @return boolean $bool
     */
    public function generateBaseClasses($bool = null)
    {
        if ($bool !== null) {
            $this->_generateBaseClasses = $bool;
        }

        return $this->_generateBaseClasses;
    }

    /**
     * generateTableClasses
     *
     * Specify whether or not to generate children table classes
     *
     * @param  boolean $bool
     * @return boolean $bool
     */
    public function generateTableClasses($bool = null)
    {
        if ($bool !== null) {
            $this->_generateTableClasses = $bool;
        }

        return $this->_generateTableClasses;
    }

    /**
     * getTargetPath
     *
     * @return string       the path where imported files are being generated
     */
    public function getTargetPath()
    {
        return $this->_path;
    }

    /**
     * setOptions
     *
     * @param  array $options
     * @return void
     */
    public function setOptions($options)
    {
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $this->setOption($key, $value);
            }
        }
    }

    /**
     * setOption
     *
     * @param  string $key
     * @param  string $value
     * @return void
     */
    public function setOption($key, $value)
    {
        $name = 'set' . Doctrine_Inflector::classify($key);

        if (method_exists($this, $name)) {
            $this->$name($value);
        } else {
            $key        = '_' . $key;
            $this->$key = $value;
        }
    }

    /*
     * Build the table definition of a Doctrine_Record object
     *
     * @param  string $table
     * @param  array  $tableColumns
     */
    /**
     * @return string|null
     */
    public function buildTableDefinition(array $definition)
    {
        if (isset($definition['inheritance']['type']) && ($definition['inheritance']['type'] == 'simple' || $definition['inheritance']['type'] == 'column_aggregation')) {
            return null;
        }

        $ret = [];

        if (isset($definition['inheritance']['type']) && $definition['inheritance']['type'] == 'concrete') {
            $ret[] = '        parent::setTableDefinition();';
        }

        if (isset($definition['tableName']) && !empty($definition['tableName'])) {
            $ret[] = "        \$this->setTableName('{$definition['tableName']}');";
        }

        if (isset($definition['columns']) && is_array($definition['columns']) && !empty($definition['columns'])) {
            $ret[] = $this->buildColumns($definition['columns']);
        }

        if (isset($definition['indexes']) && is_array($definition['indexes']) && !empty($definition['indexes'])) {
            $ret[] = $this->buildIndexes($definition['indexes']);
        }

        if (isset($definition['attributes']) && is_array($definition['attributes']) && !empty($definition['attributes'])) {
            $ret[] = $this->buildAttributes($definition['attributes']);
        }

        if (isset($definition['options']) && is_array($definition['options']) && !empty($definition['options'])) {
            $ret[] = $this->buildOptions($definition['options']);
        }

        if (isset($definition['checks']) && is_array($definition['checks']) && !empty($definition['checks'])) {
            $ret[] = $this->buildChecks($definition['checks']);
        }

        if (isset($definition['inheritance']['subclasses']) && !empty($definition['inheritance']['subclasses'])) {
            $subClasses = [];
            foreach ($definition['inheritance']['subclasses'] as $className => $def) {
                $className              = $this->_classPrefix . $className;
                $subClasses[$className] = $def;
            }
            $ret[] = "        \$this->setSubClasses({$this->varExport($subClasses)});";
        }

        $code = implode("\n", $ret);
        $code = trim($code);

        return <<<PHP

            public function setTableDefinition()
            {
                $code
            }
        PHP;
    }

    /**
     * buildSetUp
     *
     * @param  array $definition
     * @return string|null
     */
    public function buildSetUp(array $definition)
    {
        $ret = [];

        if (isset($definition['relations']) && is_array($definition['relations']) && !empty($definition['relations'])) {
            foreach ($definition['relations'] as $name => $relation) {
                $class = $relation['class'] ?? $name;
                $alias = (isset($relation['alias']) && $relation['alias'] !== $this->_classPrefix . $relation['class']) ? ' as ' . $relation['alias'] : '';

                if (!isset($relation['type'])) {
                    $relation['type'] = Doctrine_Relation::ONE;
                }

                if ($relation['type'] === Doctrine_Relation::ONE) {
                    $row = "        \$this->hasOne('{$class}{$alias}'";
                } else {
                    $row = "        \$this->hasMany('{$class}{$alias}'";
                }

                $a = [];

                if (isset($relation['refClass'])) {
                    $a[] = '\'refClass\' => ' . $this->varExport($relation['refClass']);
                }

                if (isset($relation['refClassRelationAlias'])) {
                    $a[] = '\'refClassRelationAlias\' => ' . $this->varExport($relation['refClassRelationAlias']);
                }

                if (isset($relation['deferred']) && $relation['deferred']) {
                    $a[] = '\'default\' => ' . $this->varExport($relation['deferred']);
                }

                if (isset($relation['local']) && $relation['local']) {
                    $a[] = '\'local\' => ' . $this->varExport($relation['local']);
                }

                if (isset($relation['foreign']) && $relation['foreign']) {
                    $a[] = '\'foreign\' => ' . $this->varExport($relation['foreign']);
                }

                if (isset($relation['onDelete']) && $relation['onDelete']) {
                    $a[] = '\'onDelete\' => ' . $this->varExport($relation['onDelete']);
                }

                if (isset($relation['onUpdate']) && $relation['onUpdate']) {
                    $a[] = '\'onUpdate\' => ' . $this->varExport($relation['onUpdate']);
                }

                if (isset($relation['cascade']) && $relation['cascade']) {
                    $a[] = '\'cascade\' => ' . $this->varExport($relation['cascade']);
                }

                if (isset($relation['equal']) && $relation['equal']) {
                    $a[] = '\'equal\' => ' . $this->varExport($relation['equal']);
                }

                if (isset($relation['owningSide']) && $relation['owningSide']) {
                    $a[] = '\'owningSide\' => ' . $this->varExport($relation['owningSide']);
                }

                if (isset($relation['foreignKeyName']) && $relation['foreignKeyName']) {
                    $a[] = '\'foreignKeyName\' => ' . $this->varExport($relation['foreignKeyName']);
                }

                if (isset($relation['orderBy']) && $relation['orderBy']) {
                    $a[] = '\'orderBy\' => ' . $this->varExport($relation['orderBy']);
                }

                if (!empty($a)) {
                    $a = implode('', array_map(fn($v) => "\n            $v,", $a));
                    $row .= ", [$a\n        ]";
                }

                $row .= ");\n";

                $sortBy = $relation['alias'] ?? $relation['class'] ?? $name;
                $ret[$sortBy] = $row;
            }

            ksort($ret);
            $ret = array_values($ret);
        }

        if (isset($definition['listeners']) && is_array($definition['listeners']) && !empty($definition['listeners'])) {
            $ret[] = $this->buildListeners($definition['listeners']);
        }

        $code = implode("\n", $ret);
        $code = trim($code);

        // If we have some code for the function then lets define it and return it
        if ($code) {
            return <<<PHP
                public function setUp()
                {
                    parent::setUp();
                    $code
                }
            PHP;
        }

        return null;
    }

    /**
     * Build php code for record checks
     *
     * @param  array $checks
     * @return string $build
     */
    public function buildChecks($checks)
    {
        $build = '';
        foreach ($checks as $check) {
            $build .= "        \$this->check('$check');\n";
        }
        return $build;
    }

    /**
     * buildColumns
     *
     * @param  array $columns
     * @return string|null
     */
    public function buildColumns(array $columns)
    {
        $manager = Doctrine_Manager::getInstance();
        $refl    = new ReflectionClass($this->_baseClassName);

        $build = null;
        foreach ($columns as $name => $column) {
            // An alias cannot passed via column name and column alias definition
            if (isset($column['name']) && stripos($column['name'], ' as ') && isset($column['alias'])) {
                throw new Doctrine_Import_Exception(
                    sprintf('When using a column alias you cannot pass it via column name and column alias definition (column: %s).', $column['name'])
                );
            }

            // Update column name if an alias is provided
            if (isset($column['alias']) && !isset($column['name'])) {
                $column['name'] = $name . ' as ' . $column['alias'];
            }

            $columnName = isset($column['name']) ? $column['name']:$name;
            if ($manager->getAttribute(Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE)) {
                $e          = explode(' as ', $columnName);
                $fieldName  = isset($e[1]) ? $e[1] : $e[0];
                $classified = Doctrine_Inflector::classify($fieldName);
                $getter     = 'get' . $classified;
                $setter     = 'set' . $classified;

                if ($refl->hasMethod($getter) || $refl->hasMethod($setter)) {
                    throw new Doctrine_Import_Exception(
                        sprintf('When using the attribute ATTR_AUTO_ACCESSOR_OVERRIDE you cannot use the field name "%s" because it is reserved by Doctrine. You must choose another field name.', $fieldName)
                    );
                }
            }
            $build .= '        ' . '$this->hasColumn(\'' . $columnName . '\', \'' . $column['type'] . '\'';

            if ($column['length']) {
                $build .= ', ' . $column['length'];
            } else {
                $build .= ', null';
            }

            $options = $column;

            // Remove name, alltypes, ntype. They are not needed in options array
            unset($options['name']);
            unset($options['alltypes']);
            unset($options['ntype']);

            // Remove notnull => true if the column is primary
            // Primary columns are implied to be notnull in Doctrine
            if (isset($options['primary']) && $options['primary'] == true && (isset($options['notnull']) && $options['notnull'] == true)) {
                unset($options['notnull']);
            }

            // Remove default if the value is 0 and the column is a primary key
            // Doctrine defaults to 0 if it is a primary key
            if (isset($options['primary']) && $options['primary'] == true && (isset($options['default']) && $options['default'] == 0)) {
                unset($options['default']);
            }

            foreach ($options as $key => $value) {
                if (is_null($value) || (is_array($value) && empty($value))) {
                    unset($options[$key]);
                }
            }

            if (is_array($options) && !empty($options)) {
                $build .= ', ' . $this->varExport($options, 8);
            }

            $build .= ");\n";
        }

        return $build;
    }

    /*
     * Build the accessors
     *
     * @param  string $table
     * @param  array  $columns
     */
    /**
     * @return string
     */
    public function buildAccessors(array $definition)
    {
        $accessors = [];
        foreach (array_keys($definition['columns']) as $name) {
            $accessors[] = $name;
        }

        foreach ($definition['relations'] as $relation) {
            $accessors[] = $relation['alias'];
        }

        $ret = '';
        foreach ($accessors as $name) {
            $classifiedName = Doctrine_Inflector::classify(Doctrine_Inflector::tableize($name));

            $ret .= <<<PHP
            public function get$classifiedName(\$load = true)
            {
                return \$this->get('$name', \$load);
            }

            public function set$classifiedName(\$$name, \$load = true)
            {
                return \$this->set('$name', \$$name, \$load);
            }
            PHP;
        }

        return $ret;
    }

    /*
     * Build the phpDoc for a class definition
     *
     * @param  array  $definition
     */
    /**
     * @return string
     */
    public function buildPhpDocs(array $definition)
    {
        $ret = [];

        if (!empty($definition['is_base_class']) || !$this->generateBaseClasses()) {
            $retProperties = [];
            foreach ($definition['columns'] as $name => $column) {
                $name = $column['name'] ?? $name;
                // extract column name & field name
                $parts = preg_split('/\s+as\s+/i', $name, 2) ?: [$name];
                $fieldName = trim($parts[1] ?? $name);
                $name = trim($parts[0] ?? $name);

                switch (strtolower($column['type'])) {
                    case 'boolean':
                    case 'integer':
                    case 'float':
                    case 'string':
                    case 'array':
                    case 'object':
                    default:
                        $type = strtolower($column['type']);
                        break;
                    case 'decimal':
                        $type = 'float';
                        break;
                    case 'set':
                        $type = 'string[]';
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
                        $type = 'string';
                        break;
                }

                // Add "null" union types for columns that aren't marked as notnull = true
                // But not our primary columns, as they're notnull = true implicitly in Doctrine
                if (empty($column['notnull']) && empty($column['primary'])) {
                    $type .= '|null';
                }

                $retProperties[$fieldName] = "@property $type \$$fieldName";
            }

            ksort($retProperties);
            $ret = array_merge($ret, array_values($retProperties));

            if (isset($definition['relations']) && !empty($definition['relations'])) {
                $retProperties = [];

                foreach ($definition['relations'] as $relation) {
                    $fieldName = $relation['alias'];
                    if (isset($relation['type']) && $relation['type'] == Doctrine_Relation::MANY) {
                        $fieldType = "Doctrine_Collection<{$relation['class']}>";
                    } else {
                        $fieldType = $this->_classPrefix . $relation['class'];
                    }
                    $retProperties[$fieldName] = "@property $fieldType \$$fieldName";
                }

                ksort($retProperties);
                $ret = array_merge($ret, array_values($retProperties));
            }

            $extends = $definition['inheritance']['extends'] ?? $this->_baseClassName;
            $genericOver = sprintf($this->_tableClassFormat, $definition['topLevelClassName']);
            $ret[] = "@phpstan-extends $extends<$genericOver>";
        }

        return ' ' . trim(implode("\n", array_map(fn($l) => rtrim(" * $l"), $ret)));
    }

    /**
     * emit a behavior assign
     *
     * @param  int         $level
     * @param  string      $name
     * @param  string|null $option
     * @return string assignation code
     */
    private function emitAssign($level, $name, $option)
    {
        // find class matching $name
        $classname = $name;
        return '        $' . strtolower($name) . "$level = new $classname($option);\n";
    }

    /**
     * emit an addChild
     *
     * @param  int    $level
     * @param  string $parent
     * @param  string $name
     * @return string addChild code
     */
    private function emitAddChild($level, $parent, $name)
    {
        return '        $' . strtolower($parent) . ($level - 1) . '->addChild($' . strtolower($name) . "$level);\n";
    }

    /**
     * Build php code for adding record listeners
     *
     * @param  array $listeners
     * @return string $build
     */
    public function buildListeners($listeners)
    {
        $build = '';

        foreach ($listeners as $name => $options) {
            if (!is_array($options) && $options !== null) {
                $name    = $options;
                $options = null;
            }

            $useOptions = empty($options['useOptions']) ? '[]' : '$this->getTable()->getOptions()';
            $class = $options['class'] ?? $name;

            $build .= "    \$this->addListener(new $class($useOptions), '$name');\n";
        }

        return $build;
    }

    /**
     * buildAttributes
     *
     * @param  array $attributes
     * @return string
     */
    public function buildAttributes(array $attributes)
    {
        $build = "\n";
        foreach ($attributes as $key => $value) {
            $values = [];
            if (is_bool($value)) {
                $values[] = $value ? 'true' : 'false';
            } else {
                if (!is_array($value)) {
                    $value = [$value];
                }

                foreach ($value as $attr) {
                    $const = 'Doctrine_Core::' . strtoupper($key) . '_' . strtoupper($attr);
                    if (defined($const)) {
                        $values[] = $const;
                    } else {
                        $values[] = "'$attr'";
                    }
                }
            }

            $string = implode(' ^ ', $values);
            $build .= '        $this->setAttribute(Doctrine_Core::ATTR_' . strtoupper($key) . ", $string);\n";
        }

        return $build;
    }

    /**
     * buildTableOptions
     *
     * @param  array $options
     * @return string
     */
    public function buildOptions(array $options)
    {
        $build = '';
        foreach ($options as $name => $value) {
            $build .= "        \$this->option('$name', {$this->varExport($value)});\n";
        }
        return $build;
    }

    /**
     * buildIndexes
     *
     * @param  array $indexes
     * @return string
     */
    public function buildIndexes(array $indexes)
    {
        $build = '';
        foreach ($indexes as $indexName => $definitions) {
            $build .= "\n        \$this->index('$indexName', {$this->varExport($definitions)});";
        }
        return $build;
    }

    /**
     * buildToString
     *
     * @param  array $definition
     * @return string
     */
    public function buildToString(array $definition)
    {
        if (empty($definition['toString'])) {
            return '';
        }

        return <<<PHP

            public function __toString(): string
            {
                return (string) \$this->{$definition['toString']};
            }
        PHP;
    }

    /**
     * buildDefinition
     *
     * @param  array $definition
     * @return string
     */
    public function buildDefinition(array $definition)
    {
        if (!isset($definition['className'])) {
            throw new Doctrine_Import_Builder_Exception('Missing class name.');
        }
        $abstract  = isset($definition['abstract']) && $definition['abstract'] === true ? 'abstract ':null;
        $className = $definition['className'];
        $extends   = isset($definition['inheritance']['extends']) ? $definition['inheritance']['extends']:$this->_baseClassName;

        $tableDefinitionCode = '';
        $setUpCode           = '';

        if (empty($definition['no_definition'])) {
            $tableDefinitionCode = $this->buildTableDefinition($definition);
            $setUpCode           = $this->buildSetUp($definition);
        }

        if ($tableDefinitionCode && $setUpCode) {
            $setUpCode = "\n$setUpCode";
        }

        $setUpCode .= $this->buildToString($definition);

        return <<<PHP
        /**
        {$this->buildPhpDocs($definition)}
         */
        {$abstract}class $className extends $extends
        {{$tableDefinitionCode}
        $setUpCode
        }
        PHP;
    }

    /**
     * buildRecord
     *
     * @param  array $definition
     * @return void
     */
    public function buildRecord(array $definition)
    {
        if (!isset($definition['className'])) {
            throw new Doctrine_Import_Builder_Exception('Missing class name.');
        }

        $definition['topLevelClassName'] = $definition['className'];

        if ($this->generateBaseClasses()) {
            $definition['is_package'] = (isset($definition['package']) && $definition['package']) ? true:false;

            if ($definition['is_package']) {
                $e                          = explode('.', trim($definition['package']));
                $definition['package_name'] = $e[0];

                $definition['package_path'] = !empty($e) ? implode(DIRECTORY_SEPARATOR, $e):$definition['package_name'];
            }
            // Top level definition that extends from all the others
            $topLevel = $definition;
            unset($topLevel['tableName']);

            // If we have a package then we need to make this extend the package definition and not the base definition
            // The package definition will then extends the base definition
            $topLevel['inheritance']['extends'] = (isset($topLevel['package']) && $topLevel['package']) ? $this->_packagesPrefix . $topLevel['className']:$this->_baseClassPrefix . $topLevel['className'];
            $topLevel['no_definition']          = true;
            $topLevel['generate_once']          = true;
            $topLevel['is_main_class']          = true;
            unset($topLevel['connection']);

            // Package level definition that extends from the base definition
            if (isset($definition['package'])) {
                $packageLevel                           = $definition;
                $packageLevel['className']              = $topLevel['inheritance']['extends'];
                $packageLevel['inheritance']['extends'] = $this->_baseClassPrefix . $topLevel['className'];
                $packageLevel['no_definition']          = true;
                $packageLevel['abstract']               = true;
                $packageLevel['override_parent']        = true;
                $packageLevel['generate_once']          = true;
                $packageLevel['is_package_class']       = true;
                unset($packageLevel['connection']);

                $packageLevel['tableClassName']              = sprintf($this->_tableClassFormat, $packageLevel['className']);
                $packageLevel['inheritance']['tableExtends'] = isset($definition['inheritance']['extends']) ? sprintf($this->_tableClassFormat, $definition['inheritance']['extends']):$this->_baseTableClassName;

                $topLevel['tableClassName']              = sprintf($this->_tableClassFormat, $topLevel['topLevelClassName']);
                $topLevel['inheritance']['tableExtends'] = sprintf($this->_tableClassFormat, $packageLevel['className']);
            } else {
                $topLevel['tableClassName']              = sprintf($this->_tableClassFormat, $topLevel['className']);
                $topLevel['inheritance']['tableExtends'] = isset($definition['inheritance']['extends']) ? sprintf($this->_tableClassFormat, $definition['inheritance']['extends']):$this->_baseTableClassName;
            }

            $baseClass                    = $definition;
            $baseClass['className']       = $this->_getBaseClassName($baseClass['className']);
            $baseClass['abstract']        = true;
            $baseClass['override_parent'] = false;
            $baseClass['is_base_class']   = true;

            $this->writeDefinition($baseClass);

            if (!empty($packageLevel)) {
                $this->writeDefinition($packageLevel);
            }

            $this->writeDefinition($topLevel);
        } else {
            $this->writeDefinition($definition);
        }
    }

    /**
     * @param  string $className
     * @return string
     */
    protected function _getBaseClassName($className)
    {
        return $this->_baseClassPrefix . $className;
    }

    /**
     * @param  string $className
     * @param  array  $definition
     * @param  array  $options
     * @return string
     */
    public function buildTableClassDefinition($className, $definition, $options = [])
    {
        $extends = isset($options['extends']) ? $options['extends']:$this->_baseTableClassName;
        if ($extends !== $this->_baseTableClassName) {
            $extends = $this->_classPrefix . $extends;
        }

        $code = <<<PHP
            /**
             * Returns an instance of this class.
             */
            public static function getInstance(): $className
            {
                \$table = Doctrine_Core::getTable('{$definition['className']}');
                assert(\$table instanceof $className);
                return \$table;
            }
        PHP;

        $docBlock = '';
        if (isset($definition['topLevelClassName'])) {
            $docBlock = sprintf('@phpstan-extends %s<%s>', $extends, $definition['topLevelClassName']);
            $docBlock = <<<PHP

            /**
            $docBlock
            */
            PHP;
        }

        return <<<PHP
        <?php
        $docBlock
        class $className extends $extends
        \{
        $code
        }
        PHP;
    }

    /**
     * writeTableClassDefinition
     *
     * @param  string $path
     * @param  array  $options
     * @return void
     */
    public function writeTableClassDefinition(array $definition, $path, $options = [])
    {
        if ($prefix = $this->_classPrefix) {
            $className = $prefix . $definition['tableClassName'];
            if ($this->_classPrefixFiles) {
                $fileName = $className . $this->_suffix;
            } else {
                $fileName = $definition['tableClassName'] . $this->_suffix;
            }
            $writePath = $path . DIRECTORY_SEPARATOR . $fileName;
        } else {
            $className = $definition['tableClassName'];
            $fileName  = $className . $this->_suffix;
        }

        if ($this->_pearStyle) {
            $writePath = $path . DIRECTORY_SEPARATOR . str_replace('_', '/', $fileName);
        } else {
            $writePath = $path . DIRECTORY_SEPARATOR . $fileName;
        }

        $content = $this->buildTableClassDefinition($className, $definition, $options);

        Doctrine_Lib::makeDirectories(dirname($writePath));

        Doctrine_Core::loadModel($className, $writePath);

        if (!file_exists($writePath)) {
            file_put_contents($writePath, $content);
        }
    }

    /**
     * Return the file name of the class to be generated.
     *
     * @param  string $originalClassName
     * @param  array  $definition
     * @return string
     */
    protected function _getFileName($originalClassName, $definition)
    {
        if ($this->_classPrefixFiles) {
            $fileName = $definition['className'] . $this->_suffix;
        } else {
            $fileName = $originalClassName . $this->_suffix;
        }

        if ($this->_pearStyle) {
            $fileName = str_replace('_', '/', $fileName);
        }

        return $fileName;
    }

    /**
     * writeDefinition
     *
     * @param  array $definition
     * @return void
     */
    public function writeDefinition(array $definition)
    {
        $originalClassName = $definition['className'];
        if ($prefix = $this->_classPrefix) {
            $definition['className'] = $prefix . $definition['className'];
            if (isset($definition['connectionClassName'])) {
                $definition['connectionClassName'] = $prefix . $definition['connectionClassName'];
            }
            $definition['topLevelClassName'] = $prefix . $definition['topLevelClassName'];
            if (isset($definition['inheritance']['extends'])) {
                $definition['inheritance']['extends'] = $prefix . $definition['inheritance']['extends'];
            }
        }

        $definitionCode = $this->buildDefinition($definition);

        if ($prefix) {
            $definitionCode = str_replace("this->hasOne('", "this->hasOne('$prefix", $definitionCode);
            $definitionCode = str_replace("this->hasMany('", "this->hasMany('$prefix", $definitionCode);
            $definitionCode = str_replace("'refClass' => '", "'refClass' => '$prefix", $definitionCode);
        }

        $fileName = $this->_getFileName($originalClassName, $definition);

        $packagesPath = $this->_packagesPath ?: $this->_path;

        // If this is a main class that either extends from Base or Package class
        if (!empty($definition['is_main_class'])) {
            // If is package then we need to put it in a package subfolder
            if (!empty($definition['is_package'])) {
                $writePath = $this->_path . DIRECTORY_SEPARATOR . $definition['package_name'];
                // Otherwise lets just put it in the root of the path
            } else {
                $writePath = $this->_path;
            }

            if ($this->generateTableClasses()) {
                $this->writeTableClassDefinition($definition, $writePath, ['extends' => $definition['inheritance']['tableExtends']]);
            }
        } elseif (!empty($definition['is_package_class'])) {
            // If is the package class then we need to make the path to the complete package
            if (isset($definition['package_custom_path'])) {
                $writePath = $definition['package_custom_path'];
            } else {
                $writePath = $packagesPath . DIRECTORY_SEPARATOR . $definition['package_path'];
            }

            if ($this->generateTableClasses()) {
                $this->writeTableClassDefinition($definition, $writePath, ['extends' => $definition['inheritance']['tableExtends']]);
            }
        } elseif (!empty($definition['is_base_class'])) {
            // If it is the base class of the doctrine record definition
            // If it is a part of a package then we need to put it in a package subfolder
            if (!empty($definition['is_package'])) {
                $basePath  = $this->_path . DIRECTORY_SEPARATOR . $definition['package_name'];
                $writePath = $basePath . DIRECTORY_SEPARATOR . $this->_baseClassesDirectory;
                // Otherwise lets just put it in the root generated folder
            } else {
                $writePath = $this->_path . DIRECTORY_SEPARATOR . $this->_baseClassesDirectory;
            }
        }

        // If we have a writePath from the if else conditionals above then use it
        $writePath = $writePath ?? $this->_path;
        Doctrine_Lib::makeDirectories($writePath);
        $writePath .= DIRECTORY_SEPARATOR . $fileName;

        $code = "<?php\n";

        if (!empty($definition['connection'])) {
            $code .= "// Connection Component Binding\n";
            $code .= "Doctrine_Manager::getInstance()->bindComponent('{$definition['connectionClassName']}', '{$definition['connection']}');\n";
        }

        $code .= "\n$definitionCode";

        Doctrine_Lib::makeDirectories(dirname($writePath));

        if (isset($definition['generate_once']) && $definition['generate_once'] === true) {
            if (!file_exists($writePath)) {
                $bytes = file_put_contents($writePath, $code);
            }
        } else {
            $bytes = file_put_contents($writePath, $code);
        }

        if (isset($bytes) && $bytes === false) {
            throw new Doctrine_Import_Builder_Exception("Couldn't write file " . $writePath);
        }

        Doctrine_Core::loadModel($definition['className'], $writePath);
    }
}
