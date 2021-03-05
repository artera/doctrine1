<?php declare(strict_types = 1);
namespace Doctrine1\PHPStan;

use PHPStan\Type\Type;
use PHPStan\Type\ThisType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ObjectTypeMethodReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;

class QueryDynamicReturnTypeExtension extends AbstractExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return \Doctrine_Query::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['from', 'fetchOne', 'execute']);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->args,
            $methodReflection->getVariants()
        );
        $returnType = $parametersAcceptor->getReturnType();
        $parameters = $parametersAcceptor->getParameters();
        $methodName = $methodReflection->getName();

        if ($methodName === 'from') {
            $fromArg = $this->findArg('from', $methodCall, $parameters);
            if ($fromArg === null) {
                return $returnType;
            }
            $fromArg = $scope->getType($fromArg->value);
            return $this->getFromReturnType($fromArg, $returnType);
        }

        if (!$returnType instanceof UnionType) {
            return $returnType;
        }

        // find the hydrationMode argument by name or position
        $hydrateArg = $this->findArg('hydrationMode', $methodCall, $parameters);

        if ($hydrateArg === null) {
            // argument not used, imply default of false
            $hydrationMode = \Doctrine_Core::HYDRATE_RECORD;
        } else {
            // argument used, read value if static
            $argType = $scope->getType($hydrateArg->value);
            if ($argType instanceof ConstantIntegerType) {
                $hydrationMode = $argType->getValue();
            } elseif ($argType instanceof NullType) {
                $hydrationMode = \Doctrine_Core::HYDRATE_RECORD;
            } else {
                return $returnType;
            }
        }

        if (!in_array($hydrationMode, [\Doctrine_Core::HYDRATE_RECORD, \Doctrine_Core::HYDRATE_ARRAY, \Doctrine_Core::HYDRATE_SCALAR, \Doctrine_Core::HYDRATE_ON_DEMAND])) {
            return $returnType;
        }

        if ($methodName === 'execute') {
            return $this->getExecuteReturnType($returnType, $hydrationMode);
        }
        return $this->getFetchOneReturnType($returnType, $hydrationMode);
    }

    protected function getFromReturnType(Type $from, Type $returnType): Type
    {
        if (!$returnType instanceof ThisType || !$from instanceof ConstantStringType) {
            return $returnType;
        }

        $from = $from->getValue();
        if (!preg_match('/^[a-z0-9_]+/i', $from, $matches)) {
            return $returnType;
        }
        $from = $matches[0];

        if (!class_exists($from)) {
            return $returnType;
        }

        $objectType = $returnType->getStaticObjectType();
        if (!$objectType instanceof GenericObjectType) {
            return $returnType;
        }

        return new GenericObjectType($this->getClass(), [new ObjectType($from)]);
    }

    protected function getExecuteReturnType(UnionType $returnType, int $hydrationMode): Type
    {
        $objectType = null;
        if ($hydrationMode === \Doctrine_Core::HYDRATE_RECORD) {
            $objectType = new ObjectType(\Doctrine_Collection::class);
        } elseif ($hydrationMode === \Doctrine_Core::HYDRATE_ON_DEMAND) {
            $objectType = new ObjectType(\Doctrine_Collection_OnDemand::class);
        }

        $types = [];
        foreach ($returnType->getTypes() as $type) {
            if ($type instanceof NullType) {
                $types[] = $type;
                continue;
            }

            if ($objectType !== null) {
                if ($type instanceof ObjectType && $objectType->isSuperTypeOf($type)->yes()) {
                    $types[] = $type;
                }
            } elseif ($hydrationMode === \Doctrine_Core::HYDRATE_ARRAY || $hydrationMode === \Doctrine_Core::HYDRATE_SCALAR) {
                if ($type instanceof ArrayType) {
                    $types[] = $type;
                }
            }
        }
        return TypeCombinator::union(...$types);
    }

    protected function getFetchOneReturnType(UnionType $returnType, int $hydrationMode): Type
    {
        if ($hydrationMode === \Doctrine_Core::HYDRATE_ON_DEMAND) {
            return $returnType;
        }

        $types = [];
        foreach ($returnType->getTypes() as $type) {
            if ($type instanceof NullType) {
                $types[] = $type;
                continue;
            }

            if ($hydrationMode === \Doctrine_Core::HYDRATE_RECORD) {
                if ($type instanceof ObjectType) {
                    $types[] = $type;
                }
            } elseif ($hydrationMode === \Doctrine_Core::HYDRATE_ARRAY) {
                if ($type instanceof ArrayType) {
                    $types[] = $type;
                }
            } elseif ($hydrationMode === \Doctrine_Core::HYDRATE_SCALAR) {
                if (!$type instanceof ArrayType && !$type instanceof ObjectType) {
                    $types[] = $type;
                }
            }
        }
        return TypeCombinator::union(...$types);
    }
}
