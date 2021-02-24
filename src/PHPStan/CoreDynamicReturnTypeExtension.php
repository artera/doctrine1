<?php declare(strict_types = 1);
namespace Doctrine1\PHPStan;

use PHPStan\Type\Type;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Reflection\MethodReflection;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;

class CoreDynamicReturnTypeExtension extends AbstractExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return \Doctrine_Core::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getTable';
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
    {
        if (count($methodCall->args)) {
            $componentNameArg = $scope->getType($methodCall->args[0]->value);

            if ($componentNameArg instanceof ConstantStringType) {
                // TODO: handle different table class-name formats
                $tableClass = "{$componentNameArg->getValue()}Table";
                return new ObjectType($tableClass);
            }
        }

        return \PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->args,
            $methodReflection->getVariants()
        )->getReturnType();
    }
}
