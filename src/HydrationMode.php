<?php

namespace Doctrine1;

enum HydrationMode: int
{
    case None = 0;
    case Record = 1;
    case Array = 2;
    case ArrayShallow = 3;
    case Scalar = 4;
    case SingleScalar = 5;
    case OnDemand = 6;
}
