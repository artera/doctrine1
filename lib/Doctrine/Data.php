<?php

class Doctrine_Data
{
    /**
     * array of formats data can be in
     *
     * @phpstan-var string[]
     */
    protected array $formats = ['csv', 'yml', 'xml'];

    /**
     * the default and current format we are working with
     *
     * @var string
     */
    protected string $format = 'yml';

    /**
     * single directory/yml file
     */
    protected string|array|null $directory = null;

    /**
     * specified array of models to use
     * @phpstan-var class-string<Doctrine_Record>[]
     */
    protected array $models = [];

    /**
     * whether or not to export data to individual files instead of 1
     */
    protected bool $exportIndividualFiles = false;

    /**
     * Set the current format we are working with
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * Get the current format we are working with
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Get array of available formats
     *
     * @phpstan-return string[]
     */
    public function getFormats(): array
    {
        return $this->formats;
    }

    /**
     * Set the array/string of directories or yml file paths
     */
    public function setDirectory(string|array $directory): void
    {
        $this->directory = $directory;
    }

    /**
     * Get directory for dumping/loading data from and to
     */
    public function getDirectory(): string|array|null
    {
        return $this->directory;
    }

    /**
     * Set the array of specified models to work with
     */
    public function setModels(array $models): void
    {
        $this->models = $models;
    }

    /**
     * Get the array of specified models to work with
     * @phpstan-return class-string<Doctrine_Record>[]
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * Set/Get whether or not to export individual files
     */
    public function exportIndividualFiles(?bool $bool = null): bool
    {
        if ($bool !== null) {
            $this->exportIndividualFiles = $bool;
        }
        return $this->exportIndividualFiles;
    }

    /**
     * Interface for importing data from fixture files to Doctrine models
     */
    public function importData(string $directory, string $format = 'yml', array $models = [], bool $append = false): void
    {
        $import = new Doctrine_Data_Import($directory);
        $import->setFormat($format);
        $import->setModels($models);

        $import->doImport($append);
    }

    /**
     * Check if a fieldName on a Doctrine_Record is a relation, if it is we return that relationData
     */
    public function isRelation(Doctrine_Record $record, string $fieldName): ?array
    {
        $relations = $record->getTable()->getRelations();

        foreach ($relations as $relation) {
            $relationData = $relation->toArray();

            if ($relationData['local'] === $fieldName) {
                return $relationData;
            }
        }

        return null;
    }

    /**
     * Purge all data for loaded models or for the passed array of Doctrine_Records
     */
    public function purge(?array $models = null): void
    {
        if ($models) {
            $models = Doctrine_Core::filterInvalidModels($models);
        } else {
            $models = Doctrine_Core::getLoadedModels();
        }

        $connections = [];
        foreach ($models as $model) {
            $connections[Doctrine_Core::getTable($model)->getConnection()->getName()][] = $model;
        }

        foreach ($connections as $connection => $models) {
            $models = Doctrine_Manager::getInstance()->getConnection($connection)->unitOfWork->buildFlushTree($models);
            $models = array_reverse($models);
            foreach ($models as $model) {
                Doctrine_Core::getTable($model)->createQuery()->delete()->execute();
            }
        }
    }
}
