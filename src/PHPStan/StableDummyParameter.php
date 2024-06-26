<?php declare(strict_types = 1);

/** Copied from phpstan-src/src/Reflection/Php/DummyParameter.php */

namespace Doctrine1\PHPStan;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

class StableDummyParameter implements ParameterReflection
{

	private PassedByReference $passedByReference;

	public function __construct(private string $name, private Type $type, private bool $optional, ?PassedByReference $passedByReference, private bool $variadic, private ?Type $defaultValue)
	{
		$this->passedByReference = $passedByReference ?? PassedByReference::createNo();
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function isOptional(): bool
	{
		return $this->optional;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function passedByReference(): PassedByReference
	{
		return $this->passedByReference;
	}

	public function isVariadic(): bool
	{
		return $this->variadic;
	}

	public function getDefaultValue(): ?Type
	{
		return $this->defaultValue;
	}
}
