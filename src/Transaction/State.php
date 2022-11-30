<?php

namespace Doctrine1\Transaction;

enum State: string
{
    case SLEEP = 'open';
    case ACTIVE = 'active';
    case BUSY = 'busy';
}
