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

        // consider all possible hydration modes
        $hydrationModes = [];

        if ($hydrateArg === null) {
            // argument not used, imply default of false
            $hydrationModes[] = HydrationMode::Record;
        } else {
            // argument used, read value if static
            $argType = $scope->getType($hydrateArg->value);

            if ($argType->isNull()->yes()) {
                $hydrationModes[] = HydrationMode::Record;
            } else {
                foreach ($argType->getEnumCases() as $argEnumCase) {
                    $hydrationMode = HydrationMode::Record;
                    $hydrationModeName = $argEnumCase->getEnumCaseName();
                    foreach (HydrationMode::cases() as $case) {
                        if ($case->name === $hydrationModeName) {
                            $hydrationMode = $case;
                            break;
                        }
                    }
                    $hydrationModes[] = $hydrationMode;
                }
            }
        }

        if (!count($hydrationModes)) {
            return $returnType;
        }

        $selfType = $scope->getType($methodCall->var);
        if ($methodName === 'execute') {
            $returnType = array_map(fn($mode) => $this->getExecuteReturnType($selfType, $returnType, $mode), $hydrationModes);
        } else {
            $returnType = array_map(fn($mode) => $this->getFetchOneReturnType($selfType, $returnType, $mode), $hydrationModes);
        }

        return TypeCombinator::union(...$returnType);
    }

    protected function getFromReturnType(Type $selfType, ?Type $from, Type $returnType): Type
    {
        $fromStrings = $from?->getConstantStrings() ?? [];

        // @phpstan-ignore phpstanApi.instanceofType
        if (!$selfType instanceof GenericObjectType || ($from !== null && empty($fromStrings))) {
            return $returnType;
        }

        // @phpstan-ignore phpstanApi.instanceofType
        if ($returnType instanceof GenericObjectType) {
            $templateTypes = $returnType->getTypes();
        } else {
            $templateTypes = $selfType->getTypes();
        }

        if ($from !== null) {
            $from = [];
            foreach ($fromStrings as $fromString) {
                $fromString = $fromString->getValue();
                if (!preg_match('/^\s*([a-z0-9_\\\\]+)/i', $fromString, $matches)) {
                    continue;
                }
                $fromString = $matches[1];

                if (!class_exists($fromString)) {
                    $fromString = \Doctrine1\Lib::namespaceConcat($this->namespace, $fromString);
                }

                if (!class_exists($fromString)) {
                    continue;
                }

                $from[] = new ObjectType($fromString);
            }

            if (empty($from)) {
                return $returnType;
            }

            $templateTypes[0] = TypeCombinator::union(...$from);
        }

        return new GenericObjectType($selfType->getClassName(), $templateTypes);
    }

    protected function getExecuteReturnType(Type $selfType, UnionType $returnType, HydrationMode $hydrationMode): Type
    {
        $select = true;
        // @phpstan-ignore phpstanApi.instanceofType
        if ($selfType instanceof GenericObjectType) {
            $types = $selfType->getTypes();
            if (count($types) > 1 && $types[1]->isObject()->yes()) {
                foreach ($types[1]->getObjectClassNames() as $queryTypeTemplate) {
                    if ($queryTypeTemplate !== \Doctrine1\Query\Type\Select::class) {
                        $select = false;
                        break;
                    }
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
            if ($type->isNull()->yes()) {
                $types[] = $type;
                continue;
            }

            if ($objectType !== null) {
                if ($objectType->isSuperTypeOf($type)->yes()) {
                    $types[] = $type;
                }
            } elseif ($hydrationMode === HydrationMode::Array || $hydrationMode === HydrationMode::Scalar) {
                $types = array_merge($types, $type->getArrays());
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
            if ($type->isNull()->yes()) {
                $types[] = $type;
                continue;
            }

            if ($hydrationMode === HydrationMode::Record) {
                if ($type->isObject()->yes()) {
                    $types[] = $type;
                }
            } elseif ($hydrationMode === HydrationMode::Array) {
                $types = array_merge($types, $type->getArrays());
            } elseif ($hydrationMode === HydrationMode::Scalar) {
                if ($type->isScalar()->yes() || $type->isScalar()->maybe()) {
                    $types[] = $type;
                }
            }
        }

        return TypeCombinator::union(...$types);
    }
}
