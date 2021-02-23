<?php declare(strict_types = 1);
namespace Doctrine1\PHPStan;

use PhpParser\Node\Arg;
use PHPStan\Type\Type;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\StringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;

class TableHydrationDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /** @param Arg[] $args */
    public function findArgInMethodCall(array $args, string $name): ?Arg
    {
        foreach ($args as $arg) {
            if ($arg->name?->name === $name) {
                return $arg;
            }
        }
        return null;
    }

    public function getClass(): string
    {
        return \Doctrine_Table::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'findAll';
    }

    /** @param ParameterReflection[] $parameters */
    private function findArg(string $name, MethodCall $methodCall, array $parameters): ?Arg
    {
        // find the hydrate_array argument by name or position
        foreach ($methodCall->args as $arg) {
            if ($arg->name?->name === $name) {
                return $arg;
            }
        }

        foreach ($parameters as $position => $param) {
            if ($param->getName() === $name) {
                if (count($methodCall->args) > $position) {
                    return $methodCall->args[$position];
                }
                return null;
            }
        }

        return null;
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $parametersAcceptor = \PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->args,
            $methodReflection->getVariants()
        );
        $returnType = $parametersAcceptor->getReturnType();

        if (!$returnType instanceof UnionType) {
            return $returnType;
        }

        $vartype = $scope->getType($methodCall->var);
        if ($vartype instanceof ThisType) {
            // $this
            $className = $vartype->getBaseClass();
        } elseif ($vartype instanceof ObjectType) {
            // object var
            $className = $vartype->getClassName();
        } else {
            // fallback to default return type detection
            return $returnType;
        }

        // find the hydrate_array argument by name or position
        $arg = $this->findArg('hydrate_array', $methodCall, $parametersAcceptor->getParameters());

        if ($arg === null) {
            // argument not used, imply default of false
            $hydrate_array = false;
        } else {
            // argument used, read value if static
            $argType = $scope->getType($arg->value);
            if ($argType instanceof ConstantBooleanType) {
                $hydrate_array = $argType->getValue();
            } else {
                return $returnType;
            }
        }

        foreach ($returnType->getTypes() as $type) {
            if ($type instanceof ArrayType) {
                if ($hydrate_array) {
                    return $type;
                }
                return TypeCombinator::remove($returnType, $type);
            }
        }

        return $returnType;
    }
}
