<?php

declare(strict_types=1);

namespace Doctrine1\PHPStan;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;

class MagicTableMethodReflection implements MethodReflection
{
    private ClassReflection $declaringClass;

    private string $name;

    private Type $returnType;

    /** @var string[] */
    private array $parameters = [];

    /** @param string[] $parameters */
    public function __construct(ClassReflection $declaringClass, string $name, Type $returnType, array $parameters = [])
    {
        $this->declaringClass = $declaringClass;
        $this->name = $name;
        $this->returnType = $returnType;
        $this->parameters = $parameters;
    }

    public function getDeclaringClass(): \PHPStan\Reflection\ClassReflection
    {
        return $this->declaringClass;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrototype(): \PHPStan\Reflection\ClassMemberReflection
    {
        return $this;
    }

    public function getVariants(): array
    {
        $parameters = array_map(fn ($name) => new StableDummyParameter($name, new MixedType(), false, null, false, null), $this->parameters);
        $parameters[] = new StableDummyParameter('hydrateArray', new BooleanType(), true, null, false, new ConstantBooleanType(false));

        return [
            new FunctionVariant(TemplateTypeMap::createEmpty(), null, $parameters, false, $this->returnType),
        ];
    }

    public function isDeprecated(): \PHPStan\TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isFinal(): \PHPStan\TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): \PHPStan\TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): ?Type
    {
        return null;
    }

    public function hasSideEffects(): \PHPStan\TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
