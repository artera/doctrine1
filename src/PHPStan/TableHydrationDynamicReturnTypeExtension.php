<?php declare(strict_types = 1);
namespace Doctrine1\PHPStan;

use Amf\utils\iter;
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
        $arg = iter::find($methodCall->args, fn($a) => $a->name?->name === 'hydrate_array');

        if ($arg === false) {
            $position = iter::search($parametersAcceptor->getParameters(), fn($p) => $p->getName() === 'hydrate_array');
            if ($position !== false && count($methodCall->args) > $position) {
                $arg = $methodCall->args[$position];
            }
        }

        if ($arg === false) {
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

        $arrayType = iter::find($returnType->getTypes(), fn($t) => $t instanceof ArrayType);
        if ($arrayType === false) {
            return $returnType;
        }

        if ($hydrate_array) {
            return $arrayType;
        }

        return TypeCombinator::remove($returnType, $arrayType);
    }
}
