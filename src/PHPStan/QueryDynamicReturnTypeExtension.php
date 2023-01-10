<?php

declare(strict_types=1);

namespace Doctrine1\PHPStan;

use Doctrine1\HydrationMode;
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
use PHPStan\Type\Enum\EnumCaseObjectType;
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
    private string $namespace = '\\';

    public function __construct(?string $namespace = null)
    {
        if ($namespace !== null) {
            $this->namespace = $namespace;
        }
    }

    public function getClass(): string
    {
        return \Doctrine1\AbstractQuery::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['from', 'select', 'delete', 'update', 'fetchOne', 'execute']);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        );
        $returnType = $parametersAcceptor->getReturnType();
        $parameters = $parametersAcceptor->getParameters();
        $methodName = $methodReflection->getName();

        if (in_array($methodName, ['from', 'select', 'delete', 'update'])) {
            $fromArg = $this->findArg('from', $methodCall, $parameters);
            if ($fromArg !== null) {
                $fromArg = $scope->getType($fromArg->value);
            }
            return $this->getFromReturnType($scope->getType($methodCall->var), $fromArg, $returnType);
        }

        if (!$returnType instanceof UnionType) {
            return $returnType;
        }

        // find the hydrationMode argument by name or position
        $hydrateArg = $this->findArg('hydrationMode', $methodCall, $parameters);

        if ($hydrateArg === null) {
            // argument not used, imply default of false
            $hydrationMode = HydrationMode::Record;
        } else {
            // argument used, read value if static
            $argType = $scope->getType($hydrateArg->value);
            if ($argType instanceof EnumCaseObjectType) {
                $hydrationMode = HydrationMode::Record;
                $hydrationModeName = $argType->getEnumCaseName();
                foreach (HydrationMode::cases() as $case) {
                    if ($case->name === $hydrationModeName) {
                        $hydrationMode = $case;
                        break;
                    }
                }
            } elseif ($argType instanceof NullType) {
                $hydrationMode = HydrationMode::Record;
            } else {
                return $returnType;
            }
        }

        if (!in_array($hydrationMode, [HydrationMode::Record, HydrationMode::Array, HydrationMode::Scalar, HydrationMode::OnDemand])) {
            return $returnType;
        }

        $selfType = $scope->getType($methodCall->var);
        if ($methodName === 'execute') {
            return $this->getExecuteReturnType($selfType, $returnType, $hydrationMode);
        }
        return $this->getFetchOneReturnType($selfType, $returnType, $hydrationMode);
    }

    protected function getFromReturnType(Type $selfType, ?Type $from, Type $returnType): Type
    {
        if (!$selfType instanceof GenericObjectType || ($from !== null && !$from instanceof ConstantStringType)) {
            return $returnType;
        }

        if ($returnType instanceof GenericObjectType) {
            $templateTypes = $returnType->getTypes();
        } else {
            $templateTypes = $selfType->getTypes();
        }

        if ($from !== null) {
            $from = $from->getValue();
            if (!preg_match('/^\s*([a-z0-9_\\\\]+)/i', $from, $matches)) {
                return $returnType;
            }
            $from = $matches[1];

            if (!class_exists($from)) {
                $from = \Doctrine1\Lib::namespaceConcat($this->namespace, $from);
            }

            if (!class_exists($from)) {
                return $returnType;
            }

            $templateTypes[0] = new ObjectType($from);
        }

        return new GenericObjectType($selfType->getClassName(), $templateTypes);
    }

    protected function getExecuteReturnType(Type $selfType, UnionType $returnType, HydrationMode $hydrationMode): Type
    {
        $select = true;
        if ($selfType instanceof GenericObjectType) {
            $types = $selfType->getTypes();
            if (count($types) > 1 && $types[1] instanceof ObjectType) {
                $queryTypeTemplate = $types[1]->getClassname();
                if ($queryTypeTemplate !== \Doctrine1\Query\Type\Select::class) {
                    $select = false;
                }
            }
        }

        if (!$select) {
            return new IntegerType();
        }

        $objectType = null;
        if ($hydrationMode === HydrationMode::Record) {
            $objectType = new ObjectType(\Doctrine1\Collection::class);
        } elseif ($hydrationMode === HydrationMode::OnDemand) {
            $objectType = new ObjectType(\Doctrine1\Collection\OnDemand::class);
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
            } elseif ($hydrationMode === HydrationMode::Array || $hydrationMode === HydrationMode::Scalar) {
                if ($type instanceof ArrayType) {
                    $types[] = $type;
                }
            }
        }
        return TypeCombinator::union(...$types);
    }

    protected function getFetchOneReturnType(Type $selfType, UnionType $returnType, HydrationMode $hydrationMode): Type
    {
        if ($hydrationMode === HydrationMode::OnDemand) {
            return $returnType;
        }

        $types = [];
        foreach ($returnType->getTypes() as $type) {
            if ($type instanceof NullType) {
                $types[] = $type;
                continue;
            }

            if ($hydrationMode === HydrationMode::Record) {
                if ($type instanceof ObjectType) {
                    $types[] = $type;
                }
            } elseif ($hydrationMode === HydrationMode::Array) {
                if ($type instanceof ArrayType) {
                    $types[] = $type;
                }
            } elseif ($hydrationMode === HydrationMode::Scalar) {
                if (!$type instanceof ArrayType && !$type instanceof ObjectType) {
                    $types[] = $type;
                }
            }
        }
        return TypeCombinator::union(...$types);
    }
}
