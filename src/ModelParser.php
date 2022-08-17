<?php

namespace Dev1437\ModelParser;

use Exception;
use ReflectionClass;
use ReflectionEnum;
use ReflectionFunction;

class ModelParser
{
    private $modelReflection = null;
    private $modelInstance = null;

    public function __construct(private $model, private bool $ignoreHidden = false, private array $fieldFilter = [])
    {
        $this->modelReflection = new ReflectionClass($model);
        $this->modelInstance = $this->modelReflection->newInstance();
    }

    public function parse()
    {
        return [
            'model' => $this->model,
            'fields' => $this->getFields(),
            'relations' => $this->getRelations(),
            'mutators' => $this->getMutators(),
            'casts' => $this->getCasts(),
        ];
    }

    private function getCasts()
    {
        $casts = $this->modelInstance->getCasts();
        $outputCasts = [];
        foreach ($casts as $key => $value) {
            $outputCasts[$key] = [];

            if (!class_exists($value)) {
                $outputCasts[$key]['type'] = $value;
                $outputCasts[$key]['casted_as'] = 'primitive';
                continue;
            }
            
            $reflection = (new ReflectionClass($value));

            if (!$reflection->isEnum()) {
                $outputCasts[$key]['type'] = $value;
                $outputCasts[$key]['casted_as'] = 'class';
                continue;
            }

            $outputCasts[$key]['type'] = $value;
            $outputCasts[$key]['casted_as'] = 'enum';
            $enum = (new ReflectionEnum($value));

            $enumValues = [];
            foreach ($enum->getConstants() as $case) {
                $enumValues[$case->name] = $case->value;
            }

            $outputCasts[$key]['values'] = $enumValues;
        }

        return $outputCasts;
    }

    private function getMutators()
    {
        $methods = $this->modelReflection->getMethods();

        $mutators = $this->modelInstance->getMutatedAttributes();

        $camelMutators = [];
        foreach ($mutators as $value) {
            $camelMutators[str_replace('_', '', lcfirst(ucwords($value, '_')))] = $value;
        }

        $outputMutators = [];
        foreach ($methods as $method) {
            if (!array_key_exists($method->getName(), $camelMutators)) {
                continue;
            }

            $mutator = $camelMutators[$method->getName()];

            $attr = call_user_func($method->getClosure($this->modelInstance));

            if (is_null($attr->get)) {
                throw new Exception("ModelParser only supports new style Mutators. {$mutator} returned null get closure.");
            }

            $getter = new ReflectionFunction($attr->get);
            if (!$getter->hasReturnType()) {
                // warn user to add return type to closure
                throw new Exception("Unable to determine return type for $mutator Please add a return type to the get closure");
            }

            $returnType = $getter->getReturnType();
            $outputMutators[$mutator]['type'] = $returnType->getName();
            $outputMutators[$mutator]['nullable'] = $returnType->allowsNull();

            // If the attribute returns an enum, then add enum values in output
            if (!class_exists($returnType->getName())) {
                continue;
            }

            $rc = (new ReflectionClass($returnType->getName()));

            if (!$rc->isEnum()) {
                continue;
            }

            $enum = (new ReflectionEnum($returnType->getName()));

            $enumValues = [];
            foreach ($enum->getConstants() as $case) {
                $enumValues[$case->name] = $case->value;
            }

            $outputMutators[$mutator]['enum'] = $enumValues;
        }

        return $outputMutators;
    }

    private function getRelations()
    {
        $relationTypes = [
            "Illuminate\Database\Eloquent\Relations\HasOne",
            "Illuminate\Database\Eloquent\Relations\HasMany",
            "Illuminate\Database\Eloquent\Relations\BelongsTo",
            "Illuminate\Database\Eloquent\Relations\BelongsToMany",
            "Illuminate\Database\Eloquent\Relations\HasOneThrough",
            "Illuminate\Database\Eloquent\Relations\HasManyThrough",
            "Illuminate\Database\Eloquent\Relations\MorphOne",
            "Illuminate\Database\Eloquent\Relations\MorphMany",
            "Illuminate\Database\Eloquent\Relations\MorphToMany",
            "Illuminate\Database\Eloquent\Relations\MorphTo",
        ];

        $relations = [];
        $methods = $this->modelReflection->getMethods();
        foreach ($methods as $method) {
            // Relationships must have a return type to be seen by model parser
            if (!$method->hasReturnType()) {
                continue;
            }

            $returnType = $method->getReturnType()->getName();

            if (!in_array($returnType, $relationTypes)) {
                continue;
            }

            $relationType = explode('\\', $returnType);
            $relationType = $relationType[array_key_last($relationType)];

            $rc = new ReflectionClass(call_user_func($method->getClosure($this->modelInstance))->getModel());

            $relatedModel = explode('\\', $rc->name);
            $relatedModel = $relatedModel[array_key_last($relatedModel)];


            $relationship = call_user_func($method->getClosure($this->modelInstance));

            $relations[$method->getName()] = [
                'type' => $relationType,
                'model' => $relatedModel,
                'keys' => $this->getKeysForRelation($relationType, $relationship),
            ];

            if ($relationType === 'BelongsToMany' || $relationType === 'MorphToMany') {
                $columns = $this->getColumnList($relationship->getTable());

                $relations[$method->getName()]['pivot'] = [
                    'table' => $relationship->getTable(),
                    'columns' => [],
                ];

                foreach ($columns as $column) {
                    $dbColumn = $this->getTableColumn($relationship->getTable(), $column);
                    $type = $dbColumn->getType()->getName();
                    $nullable = $dbColumn->getNotNull();
                    $relations[$method->getName()]['pivot']['columns'][$column] = [
                        'type' => $type,
                        'nullable' => $nullable,
                    ];
                }
            }
        }

        return $relations;
    }

    private function getKeysForRelation($relationType, $relationship)
    {
        if ($relationType === 'HasOne' || $relationType === 'HasMany') {
            return [
                'foreign_key' => $relationship->getForeignKeyName(),
                'local_key' => $relationship->getLocalKeyName(),
            ];
        } elseif ($relationType === 'MorphOne') {
            return [
                'foreign_key' => $relationship->getForeignKeyName(),
                'local_key' => $relationship->getLocalKeyName(),
                'morph_type' => $relationship->getMorphType(),
            ];
        } elseif ($relationType === 'BelongsToMany') {
            return [
                'pivot_foreign_key' => $relationship->getForeignPivotKeyName(),
                'pivot_related_key' => $relationship->getRelatedPivotKeyName(),
                'related_key' => $relationship->getRelatedKeyName(),
                'parent_key' => $relationship->getParentKeyName(),
            ];
        } elseif ($relationType === 'BelongsTo') {
            return [
                'foreign_key' => $relationship->getForeignKeyName(),
                'owner_key' => $relationship->getOwnerKeyName(),
            ];
        } elseif ($relationType === 'MorphTo') {
            return [
                'foreign_key' => $relationship->getForeignKeyName(),
                'morph_type' => $relationship->getMorphType(),
            ];
        } elseif ($relationType === 'MorphMany') {
            return [
                'foreign_key' => $relationship->getForeignKeyName(),
                'local_key' => $relationship->getLocalKeyName(),
                'morph_type' => $relationship->getMorphType(),
            ];
        } elseif ($relationType === 'MorphToMany') {
            return [
                'parent_key' => $relationship->getParentKeyName(),
                'related_key' => $relationship->getRelatedKeyName(),
                'pivot_foreign_key' => $relationship->getForeignPivotKeyName(),
                'pivot_related_key' => $relationship->getRelatedPivotKeyName(),
                'morph_type' => $relationship->getMorphType(),
            ];
        } elseif ($relationType === 'HasOneThrough' || $relationType === 'HasManyThrough') {
            return [
                'first_key' => $relationship->getFirstKeyName(),
                'second_key' => $relationship->getSecondLocalKeyName(),
                'local_key' => $relationship->getLocalKeyName(),
                'foreign_key' => $relationship->getForeignKeyName(),
            ];
        }
    }

    private function getFields()
    {
        $columns = [];

        foreach ($this->getColumnList($this->modelInstance->getTable()) as $column) {
            $columns[$column] = [];
        }

        if ($this->ignoreHidden) {
            $hidden = $this->modelInstance->getHidden();

            foreach ($columns as $column => $properties) {
                if (in_array($column, $hidden)) {
                    unset($columns[$column]);
                }
            }
        }

        foreach ($this->fieldFilter as $field) {
            if (array_key_exists($field, $columns)) {
                unset($columns[$field]);
            }
        }

        foreach ($columns as $column => $properties) {
            $tableColumn = $this->getTableColumn($this->modelInstance->getTable(), $column);

            $type = $tableColumn->getType()->getName();
            $nullable = !$tableColumn->getNotnull();
            $columns[$column]['type'] = $type;
            $columns[$column]['nullable'] = $nullable;
        }

        return $columns;
    }

    private function getTableColumn($table, $column)
    {
        return $this->modelInstance->getConnection()->getDoctrineColumn($table, $column);
    }

    private function getColumnList($table): array
    {
        return $this->modelInstance->getConnection()->getSchemaBuilder()->getColumnListing($table);
    }
}
