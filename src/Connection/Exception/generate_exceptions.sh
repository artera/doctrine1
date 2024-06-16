#!/bin/bash

base_dir="."

declare -A exceptions=(
    ["DynamicSQLError"]=""
    ["InvalidTargetTypeSpecification"]=""
    ["InvalidSchemaNameListSpecification"]=""
    ["ResignalWhenHandlerNotActive"]=""
    ["InvalidSQLInvokedProcedureReference"]=""
    ["SQLXMLMappingError"]=""
    ["InvalidTransformGroupNameSpecification"]=""
    ["TargetTableDisagreesWithCursorSpecification"]=""
    ["AttemptToAssignToNonUpdatableColumn"]=""
    ["AttemptToAssignToOrderingColumn"]=""
    ["ProhibitedStatementEncounteredDuringTriggerExecution"]=""
    ["InvalidForeignServerSpecification"]=""
    ["PassThroughSpecificCondition"]=""
    ["XQueryError"]=""
    ["InvalidCharacterSetName"]=""
    ["InvalidConnectionName"]=""
    ["InvalidCollationName"]=""
    ["InvalidSQLStatementIdentifier"]=""
    ["InvalidSQLDescriptorName"]=""
    ["InvalidConditionNumber"]=""
    ["CursorSensitivityException"]=""
    ["AmbiguousCursorName"]=""
    ["UnhandledUserDefinedException"]=""
    ["OLBSpecificError"]=""
    ["DatalinkException"]=""
    ["CLISpecificCondition"]=""
    ["RDASpecificCondition"]=""
)

create_dir() {
    local dir_path="$1"
    if [[ ! -d "$dir_path" ]]; then
        mkdir -p "$dir_path"
    fi
}

create_file() {
    local file_path="$1"
    local namespace="$2"
    local class_name="$3"
    local parent_class="$4"

    echo "<?php

namespace $namespace;

use $namespace;

class $class_name extends $parent_class
{
}" > "$file_path"
}

for parent_class in "${!exceptions[@]}"; do
    exception_path="$base_dir/$parent_class.php"
    create_file "$exception_path" "Doctrine1\\Connection\\Exception" "$parent_class" "Exception"

    for subclass in ${exceptions[$parent_class]}; do
        [[ -z "$subclass" ]] && continue

        parent_namespace="Doctrine1\\Connection\\Exception"
        sub_namespace="$parent_namespace\\$parent_class"

        subclass_dir="$base_dir/$parent_class"
        create_dir "$subclass_dir"

        subclass_path="$subclass_dir/$subclass.php"
        create_file "$subclass_path" "$sub_namespace" "$subclass" "$parent_class"
    done
done
