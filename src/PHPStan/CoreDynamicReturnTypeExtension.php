<?php

declare(strict_types=1);

namespace Doctrine1\PHPStan;

use PHPStan\Type\Type;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Reflection\MethodReflection;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;

class CoreDynamicReturnTypeExtension extends AbstractExtension implements DynamicStaticMethodReturnTypeExtension
{
    private string $namespace = '\\';
    private string $tableFormat = '%sTable';

    public function __construct(?string $namespace = null, ?string $tableFormat = null)
    {
        if ($namespace !== null) {
            $this->namespace = $namespace;
        }
        if ($tableFormat !== null) {
            $this->tableFormat = $tableFormat;
        }
    }

    public function getClass(): string
    {
        return \Doctrine1\Core::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getTable';
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        if (count($args)) {
            $componentNameArg = $scope->getType($args[0]->value);

            $tableClasses = [];
            foreach ($componentNameArg->getConstantStrings() as $constantComponentName) {
                $basename = $constantComponentName->getValue();
                if (($pos = strrpos($basename, '\\')) !== false) {
                    $basename = substr($basename, $pos + 1);
                }
                $tableClass = \Doctrine1\Lib::namespaceConcat($this->namespace, sprintf($this->tableFormat, $basename), true);
                $tableClasses[] = new ObjectType($tableClass);
            }
            if (count($tableClasses) > 0) {
                return TypeCombinator::union(...$tableClasses);
            }
        }

        return \PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();
    }
}
