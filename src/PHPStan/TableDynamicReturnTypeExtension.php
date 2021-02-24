<?php declare(strict_types = 1);
namespace Doctrine1\PHPStan;

use PHPStan\Type\Type;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ObjectTypeMethodReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;

class TableDynamicReturnTypeExtension extends AbstractExtension implements DynamicMethodReturnTypeExtension, MethodsClassReflectionExtension, BrokerAwareExtension
{
    private \PHPStan\Broker\Broker $broker;

    public function setBroker(\PHPStan\Broker\Broker $broker): void
    {
        $this->broker = $broker;
    }

    public function getClass(): string
    {
        return \Doctrine_Table::class;
    }

    private function isMethodNameSupported(string $name): bool
    {
        return $name === 'find' || $name === 'findAll'
            || (str_starts_with($name, 'findOneBy') && strlen($name) > 9)
            || (str_starts_with($name, 'findBy') && strlen($name) > 6);
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->isMethodNameSupported($methodReflection->getName());
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $class = $this->getClass();
        return $this->isMethodNameSupported($methodName) && (
            $classReflection->getName() === $class
            || $this->broker->hasClass($class) && $classReflection->isSubclassOf($class)
        );
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $tableAncestor = $classReflection->getAncestorWithClassName(\Doctrine_Table::class);
        if ($tableAncestor === null) {
            throw new \PHPStan\ShouldNotHappenException();
        }

        $templateTypeMap = $tableAncestor->getActiveTemplateTypeMap();
        $recordClassType = $templateTypeMap->getType('T');
        if ($recordClassType === null) {
            throw new \PHPStan\ShouldNotHappenException();
        }

        $recordAsArray = new ArrayType(new StringType(), new MixedType());

        if (str_starts_with($methodName, 'findBy')) {
            $collectionClassType = new GenericObjectType(\Doctrine_Collection::class, [$recordClassType]);
            $by = substr($methodName, 6);
            $returnType = TypeCombinator::union($collectionClassType, new ArrayType(new IntegerType(), $recordAsArray));
        } elseif (str_starts_with($methodName, 'findOneBy')) {
            $by = substr($methodName, 9);
            $returnType = TypeCombinator::union($recordClassType, $recordAsArray, new NullType());
        } else {
            throw new \PHPStan\ShouldNotHappenException();
        }

        // find out how many parameters are needed by looking at the method name
        $parameters = preg_split('/And|Or/', $by) ?: [];
        return new MagicTableMethodReflection($classReflection, $methodName, $returnType, $parameters);
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
        $parameters = $parametersAcceptor->getParameters();

        // find the hydrate_array argument by name or position
        $hydrateArg = $this->findArg('hydrate_array', $methodCall, $parameters);

        if ($hydrateArg === null) {
            // argument not used, imply default of false
            $hydrate_array = false;
        } else {
            // argument used, read value if static
            $argType = $scope->getType($hydrateArg->value);
            if ($argType instanceof ConstantBooleanType) {
                $hydrate_array = $argType->getValue();
            } else {
                return $returnType;
            }
        }

        $allowedObjectType = null;

        if (!$hydrate_array && $methodReflection->getName() === 'find') {
            $allowedObjectType = new ObjectType(\Doctrine_Record::class);

            $nameArg = $this->findArg('name', $methodCall, $parameters);
            if ($nameArg !== null) {
                // argument used, read value if static
                $argType = $scope->getType($nameArg->value);
                if (!$argType instanceof NullType) {
                    $allowedObjectType = null;
                }
            }
        }

        $types = [];
        foreach ($returnType->getTypes() as $type) {
            if ($type instanceof ArrayType) {
                if ($hydrate_array) {
                    $types[] = $type;
                }
            } elseif ($type instanceof ObjectType) {
                if (!$hydrate_array && ($allowedObjectType === null || $allowedObjectType->isSuperTypeOf($type)->yes())) {
                    $types[] = $type;
                }
            } else {
                $types[] = $type;
            }
        }
        return TypeCombinator::union(...$types);
    }
}
