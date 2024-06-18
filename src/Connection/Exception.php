<?php

namespace Doctrine1\Connection;

use Doctrine1\Connection;
use PDOException;

class Exception extends \Doctrine1\Exception
{
    public function __construct(PDOException $e, protected Connection $conn, protected ?string $query = null)
    {
        assert($e->errorInfo !== null);
        parent::__construct($e->errorInfo[2], $e->errorInfo[1], $e);
    }

    public function getSQLStateSubclass(): string
    {
        $e = $this->getPrevious();
        assert($e !== null);
        assert($e instanceof PDOException);
        assert($e->errorInfo !== null);
        return substr($e->errorInfo[0], 2);
    }

    public function getDriverErrorMessage(): string
    {
        $e = $this->getPrevious();
        assert($e !== null);
        assert($e instanceof PDOException);
        assert($e->errorInfo !== null);
        return $e->errorInfo[2];
    }

    public function getDriverCode(): null|Mysql\ErrorCode|Sqlite\ErrorCode
    {
        if ($this->conn instanceof Mysql) {
            $driverCodesEnum = Mysql\ErrorCode::class;
        } elseif ($this->conn instanceof Sqlite) {
            $driverCodesEnum = Sqlite\ErrorCode::class;
        }
        if (isset($driverCodesEnum)) {
            $e = $this->getPrevious();
            assert($e !== null);
            assert($e instanceof PDOException);
            assert($e->errorInfo !== null);
            return $driverCodesEnum::tryFrom($e->errorInfo[1]);
        }
        return null;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * Creates an instance of a custom SQL exception class based on the SQL state code from a PDOException.
     *
     * This method maps SQL state codes to specific custom exception classes for more structured error handling.
     *
     * @param PDOException $e The original PDOException.
     * @param Connection $conn The database connection driver.
     * @param string $query The query that originated the exception.
     * @return self An instance of a specific SQL exception class based on the SQL state code.
     */
    public static function fromPDO(PDOException $e, Connection $conn, ?string $query = null): self
    {
        assert($e->errorInfo !== null);
        $exClass = null;
        $sqlState = $e->errorInfo[0];

        if ($conn instanceof Mysql) {
            // If driver code matches enum, override sql state class.
            // This is done because the returned sqlstate on mysql
            // does not always respect its own documentation
            $code = Mysql\ErrorCode::tryFrom($e->errorInfo[1]);
            $exClass = match ($code) {
                Mysql\ErrorCode::ER_FUNCTION_NOT_DEFINED => Exception\SyntaxErrorOrAccessRuleViolation\UndefinedFunction::class,
                Mysql\ErrorCode::ER_RESERVED_SYNTAX, Mysql\ErrorCode::ER_RESERVED_TABLESPACE_NAME => Exception\SyntaxErrorOrAccessRuleViolation\ReservedName::class,
                Mysql\ErrorCode::ER_DB_CREATE_EXISTS => Exception\SyntaxErrorOrAccessRuleViolation\DuplicateDatabase::class,
                Mysql\ErrorCode::ER_TABLE_MUST_HAVE_A_VISIBLE_COLUMN => Exception\SyntaxErrorOrAccessRuleViolation\InvalidTableDefinition::class,
                Mysql\ErrorCode::ER_BAD_DB_ERROR => Exception\InvalidCatalogName::class,
                Mysql\ErrorCode::ER_CANT_DROP_FIELD_OR_KEY => Exception\SyntaxErrorOrAccessRuleViolation\UndefinedObject::class,
                Mysql\ErrorCode::ER_CHECK_CONSTRAINT_VIOLATED => Exception\IntegrityConstraintViolation\CheckViolation::class,
                Mysql\ErrorCode::ER_DUP_ENTRY,
                Mysql\ErrorCode::ER_DUP_ENTRY_WITH_KEY_NAME,
                Mysql\ErrorCode::ER_DUP_UNKNOWN_IN_INDEX,
                Mysql\ErrorCode::ER_DUP_LIST_ENTRY
                    => Exception\IntegrityConstraintViolation\UniqueViolation::class,
                Mysql\ErrorCode::ER_ROW_IS_REFERENCED,
                Mysql\ErrorCode::ER_ROW_IS_REFERENCED_2,
                Mysql\ErrorCode::ER_NO_REFERENCED_ROW,
                Mysql\ErrorCode::ER_NO_REFERENCED_ROW_2
                    => Exception\IntegrityConstraintViolation\ForeignKeyViolation::class,
                Mysql\ErrorCode::ER_BAD_NULL_ERROR, Mysql\ErrorCode::ER_WARN_NULL_TO_NOTNULL => Exception\IntegrityConstraintViolation\NotNullViolation::class,
                Mysql\ErrorCode::ER_WINDOW_NO_GROUP_ORDER_UNUSED,
                Mysql\ErrorCode::ER_FIELD_IN_GROUPING_NOT_GROUP_BY,
                Mysql\ErrorCode::ER_GROUPING_ON_TIMESTAMP_IN_DST,
                Mysql\ErrorCode::ER_TOO_MANY_GROUP_BY_MODIFIER_BRANCHES,
                Mysql\ErrorCode::ER_WRONG_FIELD_WITH_GROUP_V2,
                Mysql\ErrorCode::ER_MIX_OF_GROUP_FUNC_AND_FIELDS_V2,
                Mysql\ErrorCode::ER_INVALID_GROUP_FUNC_USE
                    => Exception\SyntaxErrorOrAccessRuleViolation\GroupingError::class,
                default => null,
            };
        } elseif ($conn instanceof Sqlite) {
            $code = Sqlite\ErrorCode::tryFrom($e->errorInfo[1]);
            if ($code === Sqlite\ErrorCode::ERROR) {
                if (str_starts_with($e->errorInfo[2], "no such table")) {
                    $exClass = Exception\SyntaxErrorOrAccessRuleViolation\UndefinedTable::class;
                } elseif (str_starts_with($e->errorInfo[2], "no such index")) {
                    $exClass = Exception\SyntaxErrorOrAccessRuleViolation\UndefinedObject::class;
                } elseif (preg_match('/^no such column| - column not present in both tables$/', $e->errorInfo[2])) {
                    $exClass = Exception\SyntaxErrorOrAccessRuleViolation\UndefinedColumn::class;
                } elseif (preg_match('/^index .* already exists$/', $e->errorInfo[2])) {
                    $exClass = Exception\SyntaxErrorOrAccessRuleViolation\DuplicateObject::class;
                } elseif (preg_match('/^\d+ values for \d+ columns$|: syntax error$/', $e->errorInfo[2])) {
                    $exClass = Exception\SyntaxErrorOrAccessRuleViolation\SyntaxError::class;
                }
            }
        }

        if ($exClass !== null) {
            return new $exClass($e, $conn, $query);
        }

        $sqlStateClass = substr($sqlState, 0, 2);
        $exClass = match ($sqlState) {
            "0100C" => Exception\Warning\DynamicResultSetsReturned::class,
            "01008" => Exception\Warning\ImplicitZeroBitPadding::class,
            "01003" => Exception\Warning\NullValueEliminatedInSetFunction::class,
            "01007" => Exception\Warning\PrivilegeNotGranted::class,
            "01006" => Exception\Warning\PrivilegeNotRevoked::class,
            "01004" => Exception\Warning\StringDataRightTruncation::class,
            "01P01" => Exception\Warning\DeprecatedFeature::class,

            "02001" => Exception\NoData\NoAdditionalDynamicResultSetsReturned::class,

            "08003" => Exception\ConnectionException\ConnectionDoesNotExist::class,
            "08006" => Exception\ConnectionException\ConnectionFailure::class,
            "08001" => Exception\ConnectionException\SQLClientUnableToEstablishSQLConnection::class,
            "08004" => Exception\ConnectionException\SQLServerRejectedEstablishmentOfSQLConnection::class,
            "08007" => Exception\ConnectionException\TransactionResolutionUnknown::class,
            "08P01" => Exception\ConnectionException\ProtocolViolation::class,

            "0F001" => Exception\LocatorException\InvalidLocatorSpecification::class,

            "0LP01" => Exception\InvalidGrantor\InvalidGrantOperation::class,

            "0Z002" => Exception\DiagnosticsException\StackedDiagnosticsAccessedWithoutActiveHandler::class,

            "2202E" => Exception\DataException\ArraySubscriptError::class,
            "22021" => Exception\DataException\CharacterNotInRepertoire::class,
            "22008" => Exception\DataException\DatetimeFieldOverflow::class,
            "22012" => Exception\DataException\DivisionByZero::class,
            "22005" => Exception\DataException\ErrorInAssignment::class,
            "2200B" => Exception\DataException\EscapeCharacterConflict::class,
            "22022" => Exception\DataException\IndicatorOverflow::class,
            "22015" => Exception\DataException\IntervalFieldOverflow::class,
            "2201E" => Exception\DataException\InvalidArgumentForLogarithm::class,
            "22014" => Exception\DataException\InvalidArgumentForNtileFunction::class,
            "22016" => Exception\DataException\InvalidArgumentForNthValueFunction::class,
            "2201F" => Exception\DataException\InvalidArgumentForPowerFunction::class,
            "2201G" => Exception\DataException\InvalidArgumentForWidthBucketFunction::class,
            "22018" => Exception\DataException\InvalidCharacterValueForCast::class,
            "22007" => Exception\DataException\InvalidDatetimeFormat::class,
            "22019" => Exception\DataException\InvalidEscapeCharacter::class,
            "2200D" => Exception\DataException\InvalidEscapeOctet::class,
            "22025" => Exception\DataException\InvalidEscapeSequence::class,
            "22P06" => Exception\DataException\NonstandardUseOfEscapeCharacter::class,
            "22010" => Exception\DataException\InvalidIndicatorParameterValue::class,
            "22023" => Exception\DataException\InvalidParameterValue::class,
            "22013" => Exception\DataException\InvalidPrecedingOrFollowingSize::class,
            "2201B" => Exception\DataException\InvalidRegularExpression::class,
            "2201W" => Exception\DataException\InvalidRowCountInLimitClause::class,
            "2201X" => Exception\DataException\InvalidRowCountInResultOffsetClause::class,
            "2202H" => Exception\DataException\InvalidTablesampleArgument::class,
            "2202G" => Exception\DataException\InvalidTablesampleRepeat::class,
            "22009" => Exception\DataException\InvalidTimeZoneDisplacementValue::class,
            "2200C" => Exception\DataException\InvalidUseOfEscapeCharacter::class,
            "2200G" => Exception\DataException\MostSpecificTypeMismatch::class,
            "22004" => Exception\DataException\NullValueNotAllowed::class,
            "22002" => Exception\DataException\NullValueNoIndicatorParameter::class,
            "22003" => Exception\DataException\NumericValueOutOfRange::class,
            "2200H" => Exception\DataException\SequenceGeneratorLimitExceeded::class,
            "22026" => Exception\DataException\StringDataLengthMismatch::class,
            "22001" => Exception\DataException\StringDataRightTruncation::class,
            "22011" => Exception\DataException\SubstringError::class,
            "22027" => Exception\DataException\TrimError::class,
            "22024" => Exception\DataException\UnterminatedCString::class,
            "2200F" => Exception\DataException\ZeroLengthCharacterString::class,
            "22P01" => Exception\DataException\FloatingPointException::class,
            "22P02" => Exception\DataException\InvalidTextRepresentation::class,
            "22P03" => Exception\DataException\InvalidBinaryRepresentation::class,
            "22P04" => Exception\DataException\BadCopyFileFormat::class,
            "22P05" => Exception\DataException\UntranslatableCharacter::class,
            "2200L" => Exception\DataException\NotAnXmlDocument::class,
            "2200M" => Exception\DataException\InvalidXmlDocument::class,
            "2200N" => Exception\DataException\InvalidXmlContent::class,
            "2200S" => Exception\DataException\InvalidXmlComment::class,
            "2200T" => Exception\DataException\InvalidXmlProcessingInstruction::class,
            "22030" => Exception\DataException\DuplicateJsonObjectKeyValue::class,
            "22031" => Exception\DataException\InvalidArgumentForSQLJsonDatetimeFunction::class,
            "22032" => Exception\DataException\InvalidJsonText::class,
            "22033" => Exception\DataException\InvalidSQLJsonSubscript::class,
            "22034" => Exception\DataException\MoreThanOneSQLJsonItem::class,
            "22035" => Exception\DataException\NoSQLJsonItem::class,
            "22036" => Exception\DataException\NonNumericSQLJsonItem::class,
            "22037" => Exception\DataException\NonUniqueKeysInAJsonObject::class,
            "22038" => Exception\DataException\SingletonSQLJsonItemRequired::class,
            "22039" => Exception\DataException\SQLJsonArrayNotFound::class,
            "2203A" => Exception\DataException\SQLJsonMemberNotFound::class,
            "2203B" => Exception\DataException\SQLJsonNumberNotFound::class,
            "2203C" => Exception\DataException\SQLJsonObjectNotFound::class,
            "2203D" => Exception\DataException\TooManyJsonArrayElements::class,
            "2203E" => Exception\DataException\TooManyJsonObjectMembers::class,
            "2203F" => Exception\DataException\SQLJsonScalarRequired::class,
            "2203G" => Exception\DataException\SQLJsonItemCannotBeCastToTargetType::class,

            "23001" => Exception\IntegrityConstraintViolation\RestrictViolation::class,
            "23502" => Exception\IntegrityConstraintViolation\NotNullViolation::class,
            "23503" => Exception\IntegrityConstraintViolation\ForeignKeyViolation::class,
            "23505" => Exception\IntegrityConstraintViolation\UniqueViolation::class,
            "23514" => Exception\IntegrityConstraintViolation\CheckViolation::class,
            "23P01" => Exception\IntegrityConstraintViolation\ExclusionViolation::class,

            "25001" => Exception\InvalidTransactionState\ActiveSQLTransaction::class,
            "25002" => Exception\InvalidTransactionState\BranchTransactionAlreadyActive::class,
            "25008" => Exception\InvalidTransactionState\HeldCursorRequiresSameIsolationLevel::class,
            "25003" => Exception\InvalidTransactionState\InappropriateAccessModeForBranchTransaction::class,
            "25004" => Exception\InvalidTransactionState\InappropriateIsolationLevelForBranchTransaction::class,
            "25005" => Exception\InvalidTransactionState\NoActiveSQLTransactionForBranchTransaction::class,
            "25006" => Exception\InvalidTransactionState\ReadOnlySQLTransaction::class,
            "25007" => Exception\InvalidTransactionState\SchemaAndDataStatementMixingNotSupported::class,
            "25P01" => Exception\InvalidTransactionState\NoActiveSQLTransaction::class,
            "25P02" => Exception\InvalidTransactionState\InFailedSQLTransaction::class,
            "25P03" => Exception\InvalidTransactionState\IdleInTransactionSessionTimeout::class,

            "28P01" => Exception\InvalidAuthorizationSpecification\InvalidPassword::class,

            "2BP01" => Exception\DependentPrivilegeDescriptorsStillExist\DependentObjectsStillExist::class,

            "2F005" => Exception\SQLRoutineException\FunctionExecutedNoReturnStatement::class,
            "2F002" => Exception\SQLRoutineException\ModifyingSQLDataNotPermitted::class,
            "2F003" => Exception\SQLRoutineException\ProhibitedSQLStatementAttempted::class,
            "2F004" => Exception\SQLRoutineException\ReadingSQLDataNotPermitted::class,

            "38001" => Exception\ExternalRoutineException\ContainingSQLNotPermitted::class,
            "38002" => Exception\ExternalRoutineException\ModifyingSQLDataNotPermitted::class,
            "38003" => Exception\ExternalRoutineException\ProhibitedSQLStatementAttempted::class,
            "38004" => Exception\ExternalRoutineException\ReadingSQLDataNotPermitted::class,

            "39001" => Exception\ExternalRoutineInvocationException\InvalidSQLStateReturned::class,
            "39004" => Exception\ExternalRoutineInvocationException\NullValueNotAllowed::class,
            "39P01" => Exception\ExternalRoutineInvocationException\TriggerProtocolViolated::class,
            "39P02" => Exception\ExternalRoutineInvocationException\SrfProtocolViolated::class,
            "39P03" => Exception\ExternalRoutineInvocationException\EventTriggerProtocolViolated::class,

            "3B001" => Exception\SavepointException\InvalidSavepointSpecification::class,

            "40002" => Exception\TransactionRollback\TransactionIntegrityConstraintViolation::class,
            "40001" => Exception\TransactionRollback\SerializationFailure::class,
            "40003" => Exception\TransactionRollback\StatementCompletionUnknown::class,
            "40P01" => Exception\TransactionRollback\DeadlockDetected::class,

            "42601" => Exception\SyntaxErrorOrAccessRuleViolation\SyntaxError::class,
            "42501" => Exception\SyntaxErrorOrAccessRuleViolation\InsufficientPrivilege::class,
            "42846" => Exception\SyntaxErrorOrAccessRuleViolation\CannotCoerce::class,
            "42803" => Exception\SyntaxErrorOrAccessRuleViolation\GroupingError::class,
            "42P20" => Exception\SyntaxErrorOrAccessRuleViolation\WindowingError::class,
            "42P19" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidRecursion::class,
            "42830" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidForeignKey::class,
            "42602" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidName::class,
            "42622" => Exception\SyntaxErrorOrAccessRuleViolation\NameTooLong::class,
            "42939" => Exception\SyntaxErrorOrAccessRuleViolation\ReservedName::class,
            "42804" => Exception\SyntaxErrorOrAccessRuleViolation\DatatypeMismatch::class,
            "42P18" => Exception\SyntaxErrorOrAccessRuleViolation\IndeterminateDatatype::class,
            "42P21" => Exception\SyntaxErrorOrAccessRuleViolation\CollationMismatch::class,
            "42P22" => Exception\SyntaxErrorOrAccessRuleViolation\IndeterminateCollation::class,
            "42809" => Exception\SyntaxErrorOrAccessRuleViolation\WrongObjectType::class,
            "428C9" => Exception\SyntaxErrorOrAccessRuleViolation\GeneratedAlways::class,
            "42703", "42S22" => Exception\SyntaxErrorOrAccessRuleViolation\UndefinedColumn::class,
            "42883" => Exception\SyntaxErrorOrAccessRuleViolation\UndefinedFunction::class,
            "42P01", "42S02" => Exception\SyntaxErrorOrAccessRuleViolation\UndefinedTable::class,
            "42P02" => Exception\SyntaxErrorOrAccessRuleViolation\UndefinedParameter::class,
            "42704", "42S12" => Exception\SyntaxErrorOrAccessRuleViolation\UndefinedObject::class,
            "42701", "42S21" => Exception\SyntaxErrorOrAccessRuleViolation\DuplicateColumn::class,
            "42P03" => Exception\SyntaxErrorOrAccessRuleViolation\DuplicateCursor::class,
            "42P04" => Exception\SyntaxErrorOrAccessRuleViolation\DuplicateDatabase::class,
            "42723" => Exception\SyntaxErrorOrAccessRuleViolation\DuplicateFunction::class,
            "42P05" => Exception\SyntaxErrorOrAccessRuleViolation\DuplicatePreparedStatement::class,
            "42P06" => Exception\SyntaxErrorOrAccessRuleViolation\DuplicateSchema::class,
            "42P07", "42S01" => Exception\SyntaxErrorOrAccessRuleViolation\DuplicateTable::class,
            "42712" => Exception\SyntaxErrorOrAccessRuleViolation\DuplicateAlias::class,
            "42710", "42S11" => Exception\SyntaxErrorOrAccessRuleViolation\DuplicateObject::class,
            "42702" => Exception\SyntaxErrorOrAccessRuleViolation\AmbiguousColumn::class,
            "42725" => Exception\SyntaxErrorOrAccessRuleViolation\AmbiguousFunction::class,
            "42P08" => Exception\SyntaxErrorOrAccessRuleViolation\AmbiguousParameter::class,
            "42P09" => Exception\SyntaxErrorOrAccessRuleViolation\AmbiguousAlias::class,
            "42P10" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidColumnReference::class,
            "42611" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidColumnDefinition::class,
            "42P11" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidCursorDefinition::class,
            "42P12" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidDatabaseDefinition::class,
            "42P13" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidFunctionDefinition::class,
            "42P14" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidPreparedStatementDefinition::class,
            "42P15" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidSchemaDefinition::class,
            "42P16" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidTableDefinition::class,
            "42P17" => Exception\SyntaxErrorOrAccessRuleViolation\InvalidObjectDefinition::class,

            "53100" => Exception\InsufficientResources\DiskFull::class,
            "53200" => Exception\InsufficientResources\OutOfMemory::class,
            "53300" => Exception\InsufficientResources\TooManyConnections::class,
            "53400" => Exception\InsufficientResources\ConfigurationLimitExceeded::class,

            "54001" => Exception\ProgramLimitExceeded\StatementTooComplex::class,
            "54011" => Exception\ProgramLimitExceeded\TooManyColumns::class,
            "54023" => Exception\ProgramLimitExceeded\TooManyArguments::class,

            "55006" => Exception\ObjectNotInPrerequisiteState\ObjectInUse::class,
            "55P02" => Exception\ObjectNotInPrerequisiteState\CantChangeRuntimeParam::class,
            "55P03" => Exception\ObjectNotInPrerequisiteState\LockNotAvailable::class,
            "55P04" => Exception\ObjectNotInPrerequisiteState\UnsafeNewEnumValueUsage::class,

            "57014" => Exception\OperatorIntervention\QueryCanceled::class,
            "57P01" => Exception\OperatorIntervention\AdminShutdown::class,
            "57P02" => Exception\OperatorIntervention\CrashShutdown::class,
            "57P03" => Exception\OperatorIntervention\CannotConnectNow::class,
            "57P04" => Exception\OperatorIntervention\DatabaseDropped::class,
            "57P05" => Exception\OperatorIntervention\IdleSessionTimeout::class,

            "58030" => Exception\SystemError\IoError::class,
            "58P01" => Exception\SystemError\UndefinedFile::class,
            "58P02" => Exception\SystemError\DuplicateFile::class,

            "F0001" => Exception\ConfigFileError\LockFileExists::class,

            "HV005" => Exception\ForeignDataWrapperError\FdwColumnNameNotFound::class,
            "HV002" => Exception\ForeignDataWrapperError\FdwDynamicParameterValueNeeded::class,
            "HV010" => Exception\ForeignDataWrapperError\FdwFunctionSequenceError::class,
            "HV021" => Exception\ForeignDataWrapperError\FdwInconsistentDescriptorInformation::class,
            "HV024" => Exception\ForeignDataWrapperError\FdwInvalidAttributeValue::class,
            "HV007" => Exception\ForeignDataWrapperError\FdwInvalidColumnName::class,
            "HV008" => Exception\ForeignDataWrapperError\FdwInvalidColumnNumber::class,
            "HV004" => Exception\ForeignDataWrapperError\FdwInvalidDataType::class,
            "HV006" => Exception\ForeignDataWrapperError\FdwInvalidDataTypeDescriptors::class,
            "HV091" => Exception\ForeignDataWrapperError\FdwInvalidDescriptorFieldIdentifier::class,
            "HV00B" => Exception\ForeignDataWrapperError\FdwInvalidHandle::class,
            "HV00C" => Exception\ForeignDataWrapperError\FdwInvalidOptionIndex::class,
            "HV00D" => Exception\ForeignDataWrapperError\FdwInvalidOptionName::class,
            "HV090" => Exception\ForeignDataWrapperError\FdwInvalidStringLengthOrBufferLength::class,
            "HV00A" => Exception\ForeignDataWrapperError\FdwInvalidStringFormat::class,
            "HV009" => Exception\ForeignDataWrapperError\FdwInvalidUseOfNullPointer::class,
            "HV014" => Exception\ForeignDataWrapperError\FdwTooManyHandles::class,
            "HV001" => Exception\ForeignDataWrapperError\FdwOutOfMemory::class,
            "HV00P" => Exception\ForeignDataWrapperError\FdwNoSchemas::class,
            "HV00J" => Exception\ForeignDataWrapperError\FdwOptionNameNotFound::class,
            "HV00K" => Exception\ForeignDataWrapperError\FdwReplyHandle::class,
            "HV00Q" => Exception\ForeignDataWrapperError\FdwSchemaNotFound::class,
            "HV00R" => Exception\ForeignDataWrapperError\FdwTableNotFound::class,
            "HV00L" => Exception\ForeignDataWrapperError\FdwUnableToCreateExecution::class,
            "HV00M" => Exception\ForeignDataWrapperError\FdwUnableToCreateReply::class,
            "HV00N" => Exception\ForeignDataWrapperError\FdwUnableToEstablishConnection::class,

            "P0001" => Exception\PlPgSQLError\RaiseException::class,
            "P0002" => Exception\PlPgSQLError\NoDataFound::class,
            "P0003" => Exception\PlPgSQLError\TooManyRows::class,
            "P0004" => Exception\PlPgSQLError\AssertFailure::class,

            "XX001" => Exception\InternalError\DataCorrupted::class,
            "XX002" => Exception\InternalError\IndexCorrupted::class,

            default => match ($sqlStateClass) {
                "00" => Exception\SuccessfulCompletion::class,
                "01" => Exception\Warning::class,
                "02" => Exception\NoData::class,
                "03" => Exception\SQLStatementNotYetComplete::class,
                "07" => Exception\DynamicSQLError::class,
                "08" => Exception\ConnectionException::class,
                "09" => Exception\TriggeredActionException::class,
                "0A" => Exception\FeatureNotSupported::class,
                "0B" => Exception\InvalidTransactionInitiation::class,
                "0D" => Exception\InvalidTargetTypeSpecification::class,
                "0E" => Exception\InvalidSchemaNameListSpecification::class,
                "0F" => Exception\LocatorException::class,
                "0K" => Exception\ResignalWhenHandlerNotActive::class,
                "0L" => Exception\InvalidGrantor::class,
                "0M" => Exception\InvalidSQLInvokedProcedureReference::class,
                "0N" => Exception\SQLXMLMappingError::class,
                "0P" => Exception\InvalidRoleSpecification::class,
                "0S" => Exception\InvalidTransformGroupNameSpecification::class,
                "0T" => Exception\TargetTableDisagreesWithCursorSpecification::class,
                "0U" => Exception\AttemptToAssignToNonUpdatableColumn::class,
                "0V" => Exception\AttemptToAssignToOrderingColumn::class,
                "0W" => Exception\ProhibitedStatementEncounteredDuringTriggerExecution::class,
                "0X" => Exception\InvalidForeignServerSpecification::class,
                "0Y" => Exception\PassThroughSpecificCondition::class,
                "0Z" => Exception\DiagnosticsException::class,
                "10" => Exception\XQueryError::class,
                "20" => Exception\CaseNotFound::class,
                "21" => Exception\CardinalityViolation::class,
                "22" => Exception\DataException::class,
                "23" => Exception\IntegrityConstraintViolation::class,
                "24" => Exception\InvalidCursorState::class,
                "25" => Exception\InvalidTransactionState::class,
                "26" => Exception\InvalidSQLStatementName::class,
                "27" => Exception\TriggeredDataChangeViolation::class,
                "28" => Exception\InvalidAuthorizationSpecification::class,
                "2B" => Exception\DependentPrivilegeDescriptorsStillExist::class,
                "2C" => Exception\InvalidCharacterSetName::class,
                "2D" => Exception\InvalidTransactionTermination::class,
                "2E" => Exception\InvalidConnectionName::class,
                "2F" => Exception\SQLRoutineException::class,
                "2H" => Exception\InvalidCollationName::class,
                "30" => Exception\InvalidSQLStatementIdentifier::class,
                "33" => Exception\InvalidSQLDescriptorName::class,
                "34" => Exception\InvalidCursorName::class,
                "35" => Exception\InvalidConditionNumber::class,
                "36" => Exception\CursorSensitivityException::class,
                "38" => Exception\ExternalRoutineException::class,
                "39" => Exception\ExternalRoutineInvocationException::class,
                "3B" => Exception\SavepointException::class,
                "3C" => Exception\AmbiguousCursorName::class,
                "3D" => Exception\InvalidCatalogName::class,
                "3F" => Exception\InvalidSchemaName::class,
                "40" => Exception\TransactionRollback::class,
                "42" => Exception\SyntaxErrorOrAccessRuleViolation::class,
                "44" => Exception\WithCheckOptionViolation::class,
                "45" => Exception\UnhandledUserDefinedException::class,
                "46" => Exception\OLBSpecificError::class,
                "53" => Exception\InsufficientResources::class,
                "54" => Exception\ProgramLimitExceeded::class,
                "55" => Exception\ObjectNotInPrerequisiteState::class,
                "57" => Exception\OperatorIntervention::class,
                "58" => Exception\SystemError::class,
                "72" => Exception\SnapshotFailure::class,
                "F0" => Exception\ConfigFileError::class,
                "HW" => Exception\DatalinkException::class,
                "HV" => Exception\ForeignDataWrapperError::class,
                "HY" => Exception\CLISpecificCondition::class,
                "HZ" => Exception\RDASpecificCondition::class,
                "P0" => Exception\PlPgSQLError::class,
                "XX" => Exception\InternalError::class,
                default => self::class,
            },
        };
        return new $exClass($e, $conn, $query);
    }
}
