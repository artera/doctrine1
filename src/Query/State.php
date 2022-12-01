<?php

namespace Doctrine1\Query;

enum State
{
    // A query object is in CLEAN state when it has NO unparsed/unprocessed DQL parts.
    case Clean;

    // A query object is in state DIRTY when it has DQL parts that have not yet been
    // parsed/processed.
    case Dirty;
}
