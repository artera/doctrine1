<?php

namespace Doctrine1;

enum IdentifierType
{
    case Autoinc;
    case Sequence;
    case Natural;
    case Composite;
}
