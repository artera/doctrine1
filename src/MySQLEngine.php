<?php

namespace Doctrine1;

/**
 * @see https://dev.mysql.com/doc/refman/8.0/en/show-engines.html
 */
enum MySQLEngine: string {
    case Archive = 'ARCHIVE';
    case Blackhole = 'BLACKHOLE';
    case MrgMyISAM = 'MRG_MYISAM';
    case Federated = 'FEDERATED';
    case MyISAM = 'MyISAM';
    case PerformanceSchema = 'PERFORMANCE_SCHEMA';
    case InnoDB = 'InnoDB';
    case Memory = 'MEMORY';
    case CSV = 'CSV';
    case RocksDB = 'ROCKSDB';
    case Aria = 'Aria';
    case Sequence = 'SEQUENCE';
}
