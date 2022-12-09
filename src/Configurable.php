<?php

namespace Doctrine1;

abstract class Configurable
{
    /**
     * @var ?Configurable $parent   the parent of this component
     */
    protected $parent;


    // EXPORT =================================================================
    protected ?int $exportFlags = null;

    public function getExportFlags(): int
    {
        return $this->exportFlags ?? $this->parent?->getExportFlags() ?? Core::EXPORT_ALL;
    }

    public function setExportFlags(?int $value): void
    {
        $this->exportFlags = $value;
    }

    // PORTABILITY ============================================================
    protected ?int $portability = null;

    public function getPortability(): int
    {
        return $this->portability ?? $this->parent?->getPortability() ?? Core::PORTABILITY_NONE;
    }

    public function setPortability(?int $value): void
    {
        $this->portability = $value;
    }

    // VALIDATE ===============================================================
    protected int|bool|null $validate = null;

    public function getValidate(): int|bool
    {
        return $this->validate ?? $this->parent?->getValidate() ?? Core::VALIDATE_NONE;
    }

    public function setValidate(int|bool|null $value): void
    {
        $this->validate = $value;
    }

    // DECIMAL PLACES =========================================================
    protected ?int $decimalPlaces = null;

    public function getDecimalPlaces(): int
    {
        return $this->decimalPlaces ?? $this->parent?->getDecimalPlaces() ?? 2;
    }

    public function setDecimalPlaces(?int $value): void
    {
        $this->decimalPlaces = $value;
    }

    // LIMIT ==================================================================
    protected ?Limit $limit = null;

    public function getLimit(): Limit
    {
        return $this->limit ?? $this->parent?->getLimit() ?? Limit::Records;
    }

    public function setLimit(?Limit $value): void
    {
        $this->limit = $value;
    }

    // QUERY CACHE LIFESPAN ===================================================
    protected ?int $queryCacheLifespan = null;

    public function getQueryCacheLifespan(): ?int
    {
        return $this->queryCacheLifespan ?? $this->parent?->getQueryCacheLifespan() ?? null;
    }

    public function setQueryCacheLifespan(?int $value): void
    {
        $this->queryCacheLifespan = $value;
    }

    // RESULT CACHE LIFESPAN ==================================================
    protected ?int $resultCacheLifespan = null;

    public function getResultCacheLifespan(): ?int
    {
        return $this->resultCacheLifespan ?? $this->parent?->getResultCacheLifespan() ?? null;
    }

    public function setResultCacheLifespan(?int $value): void
    {
        $this->resultCacheLifespan = $value;
    }

    // MAX IDENTIFIER LENGTH ==================================================
    protected ?int $maxIdentifierLength = null;

    public function getMaxIdentifierLength(): ?int
    {
        return $this->maxIdentifierLength ?? $this->parent?->getMaxIdentifierLength() ?? null;
    }

    public function setMaxIdentifierLength(?int $value): void
    {
        $this->maxIdentifierLength = $value;
    }

    // DEFAULT IDENTIFIER OPTIONS =============================================
    protected ?array $defaultIdentifierOptions = null;

    public function getDefaultIdentifierOptions(): array
    {
        return $this->defaultIdentifierOptions ?? $this->parent?->getDefaultIdentifierOptions() ?? [];
    }

    public function setDefaultIdentifierOptions(?array $value): void
    {
        $this->defaultIdentifierOptions = $value;
    }

    // DEFAULT CHARSET ========================================================
    protected ?string $charset = null;

    public function getCharset(): ?string
    {
        return $this->charset ?? $this->parent?->getCharset() ?? null;
    }

    public function setCharset(?string $value): void
    {
        $this->charset = $value;
    }

    // DEFAULT COLLATE ========================================================
    protected ?string $collate = null;

    public function getCollate(): ?string
    {
        return $this->collate ?? $this->parent?->getCollate() ?? null;
    }

    public function setCollate(?string $value): void
    {
        $this->collate = $value;
    }

    // DEFAULT SEQUENCE =======================================================
    protected ?string $defaultSequence = null;

    public function getDefaultSequence(): ?string
    {
        return $this->defaultSequence ?? $this->parent?->getDefaultSequence() ?? null;
    }

    public function setDefaultSequence(?string $value): void
    {
        $this->defaultSequence = $value;
    }

    // DEFAULT TABLE ENGINE ===================================================
    protected ?string $defaultMySQLEngine = null;

    public function getDefaultMySQLEngine(): string
    {
        return $this->defaultMySQLEngine ?? $this->parent?->getDefaultMySQLEngine() ?? MySQLEngine::InnoDB->value;
    }

    public function setDefaultMySQLEngine(MySQLEngine|string|null $value): void
    {
        $this->defaultMySQLEngine = $value instanceof MySQLEngine ? $value->value : $value;
    }

    // AUTO FREE QUERY OBJECTS ================================================
    protected ?bool $autoFreeQueryObjects = null;

    public function getAutoFreeQueryObjects(): bool
    {
        return $this->autoFreeQueryObjects ?? $this->parent?->getAutoFreeQueryObjects() ?? false;
    }

    public function setAutoFreeQueryObjects(?bool $value): void
    {
        $this->autoFreeQueryObjects = $value;
    }

    // AUTO ACCESSOR_OVERRIDE =================================================
    protected ?bool $autoAccessorOverride = null;

    public function getAutoAccessorOverride(): bool
    {
        return $this->autoAccessorOverride ?? $this->parent?->getAutoAccessorOverride() ?? false;
    }

    public function setAutoAccessorOverride(?bool $value): void
    {
        $this->autoAccessorOverride = $value;
    }

    // CASCADE SAVES ==========================================================
    protected ?bool $cascadeSaves = null;

    public function getCascadeSaves(): bool
    {
        return $this->cascadeSaves ?? $this->parent?->getCascadeSaves() ?? true;
    }

    public function setCascadeSaves(?bool $value): void
    {
        $this->cascadeSaves = $value;
    }

    // HYDRATE OVERWRITE ======================================================
    protected ?bool $loadReferences = null;

    public function getLoadReferences(): bool
    {
        return $this->loadReferences ?? $this->parent?->getLoadReferences() ?? true;
    }

    public function setLoadReferences(?bool $value): void
    {
        $this->loadReferences = $value;
    }

    // HYDRATE OVERWRITE ======================================================
    protected ?bool $hydrateOverwrite = null;

    public function getHydrateOverwrite(): bool
    {
        return $this->hydrateOverwrite ?? $this->parent?->getHydrateOverwrite() ?? true;
    }

    public function setHydrateOverwrite(?bool $value): void
    {
        $this->hydrateOverwrite = $value;
    }

    // MODEL NAMESPACE ========================================================
    protected ?string $modelNamespace = null;

    public function getModelNamespace(): string
    {
        return $this->modelNamespace?? $this->parent?->getModelNamespace() ?? '\\';
    }

    public function setModelNamespace(?string $value): void
    {
        $this->modelNamespace = $value;
    }

    // QUERY CLASS ============================================================
    /** @var class-string<Query>|null */
    protected ?string $queryClass = null;

    /** @return class-string<Query> */
    public function getQueryClass(): string
    {
        return $this->queryClass ?? $this->parent?->getQueryClass() ?? Query::class;
    }

    /** @param class-string<Query>|null $value */
    public function setQueryClass(?string $value): void
    {
        $this->queryClass = $value;
    }

    // COLLECTION CLASS =======================================================
    /** @var class-string<Collection>|null */
    protected ?string $collectionClass = null;

    /** @return class-string<Collection> */
    public function getCollectionClass(): string
    {
        return $this->collectionClass ?? $this->parent?->getCollectionClass() ?? Collection::class;
    }

    /** @param class-string<Collection>|null $value */
    public function setCollectionClass(?string $value): void
    {
        $this->collectionClass = $value;
    }

    // TABLE CLASS ============================================================
    /** @var class-string<Table>|null */
    protected ?string $tableClass = null;

    /** @return class-string<Table> */
    public function getTableClass(): string
    {
        return $this->tableClass ?? $this->parent?->getTableClass() ?? Table::class;
    }

    /** @param class-string<Table>|null $value */
    public function setTableClass(?string $value): void
    {
        $this->tableClass = $value;
    }

    // TABLE CLASS FORMAT =====================================================
    protected ?string $tableClassFormat = null;

    public function getTableClassFormat(): string
    {
        return $this->tableClassFormat ?? $this->parent?->getTableClassFormat() ?? '%sTable';
    }

    public function setTableClassFormat(?string $value): void
    {
        $this->tableClassFormat = $value;
    }

    // ENUM IMPLEMENTATION ====================================================
    protected ?EnumSetImplementation $enumImplementation = null;

    public function getEnumImplementation(): EnumSetImplementation
    {
        return $this->enumImplementation ?? $this->parent?->getEnumImplementation() ?? EnumSetImplementation::String;
    }

    public function setEnumImplementation(?EnumSetImplementation $value): void
    {
        $this->enumImplementation = $value;
    }

    // SET IMPLEMENTATION =====================================================
    protected ?EnumSetImplementation $setImplementation = null;

    public function getSetImplementation(): EnumSetImplementation
    {
        return $this->setImplementation ?? $this->parent?->getSetImplementation() ?? EnumSetImplementation::String;
    }

    public function setSetImplementation(?EnumSetImplementation $value): void
    {
        $this->setImplementation = $value;
    }

    // QUOTE IDENTIFIER =======================================================
    protected ?bool $quoteIdentifier = null;

    public function getQuoteIdentifier(): bool
    {
        return $this->quoteIdentifier ?? $this->parent?->getQuoteIdentifier() ?? false;
    }

    public function setQuoteIdentifier(?bool $value): void
    {
        $this->quoteIdentifier = $value;
    }

    // USE NATIVE SET ========================================================
    protected ?bool $useNativeSet = null;

    public function getUseNativeSet(): bool
    {
        return $this->useNativeSet ?? $this->parent?->getUseNativeSet() ?? false;
    }

    public function setUseNativeSet(?bool $value): void
    {
        $this->useNativeSet = $value;
    }

    // USE NATIVE ENUM ========================================================
    protected ?bool $useNativeEnum = null;

    public function getUseNativeEnum(): bool
    {
        return $this->useNativeEnum ?? $this->parent?->getUseNativeEnum() ?? false;
    }

    public function setUseNativeEnum(?bool $value): void
    {
        $this->useNativeEnum = $value;
    }

    // USE DQL CALLBACKS ======================================================
    protected ?bool $useDqlCallbacks = null;

    public function getUseDqlCallbacks(): bool
    {
        return $this->useDqlCallbacks ?? $this->parent?->getUseDqlCallbacks() ?? false;
    }

    public function setUseDqlCallbacks(?bool $value): void
    {
        $this->useDqlCallbacks = $value;
    }

    // SEQUENCE COLUMN NAME ===================================================
    protected ?string $sequenceColumnName = null;

    public function getSequenceColumnName(): string
    {
        return $this->sequenceColumnName ?? $this->parent?->getSequenceColumnName() ?? 'id';
    }

    public function setSequenceColumnName(?string $value): void
    {
        $this->sequenceColumnName = $value;
    }

    // SEQUENCE NAME FORMAT ===================================================
    protected ?string $sequenceNameFormat = null;

    public function getSequenceNameFormat(): string
    {
        return $this->sequenceNameFormat ?? $this->parent?->getSequenceNameFormat() ?? '%s_seq';
    }

    public function setSequenceNameFormat(?string $value): void
    {
        if ($this instanceof Table) {
            throw new Exception('Sequence name format cannot be set at table level (only at connection or global level).');
        }
        $this->sequenceNameFormat = $value;
    }

    // INDEX NAME FORMAT ======================================================
    protected ?string $indexNameFormat = null;

    public function getIndexNameFormat(): string
    {
        return $this->indexNameFormat ?? $this->parent?->getIndexNameFormat() ?? '%s_idx';
    }

    public function setIndexNameFormat(?string $value): void
    {
        if ($this instanceof Table) {
            throw new Exception('Index name format cannot be set at table level (only at connection or global level).');
        }
        $this->indexNameFormat = $value;
    }

    // FOREIGN KEY NAME FORMAT ================================================
    protected ?string $foreignKeyNameFormat = null;

    public function getForeignKeyNameFormat(): string
    {
        return $this->foreignKeyNameFormat ?? $this->parent?->getForeignKeyNameFormat() ?? '%s';
    }

    public function setForeignKeyNameFormat(?string $value): void
    {
        if ($this instanceof Table) {
            throw new Exception('Foreign key name format cannot be set at table level (only at connection or global level).');
        }
        $this->foreignKeyNameFormat = $value;
    }

    // QUERY CACHE ============================================================
    protected ?CacheInterface $queryCache = null;

    public function getQueryCache(): ?CacheInterface
    {
        return $this->queryCache ?? $this->parent?->getQueryCache();
    }

    public function setQueryCache(?CacheInterface $value): void
    {
        $this->queryCache = $value;
    }

    // RESULT CACHE ===========================================================
    protected ?CacheInterface $resultCache = null;

    public function getResultCache(): ?CacheInterface
    {
        return $this->resultCache ?? $this->parent?->getResultCache();
    }

    public function setResultCache(?CacheInterface $value): void
    {
        $this->resultCache = $value;
    }

    // LISTENER ===============================================================
    protected EventListenerInterface|Overloadable|null $listener = null;

    /**
     * @return $this
     */
    public function setEventListener(EventListener $listener): self
    {
        return $this->setListener($listener);
    }

    /**
     * @phpstan-param EventListenerInterface|Overloadable<EventListenerInterface> $listener
     * @return $this
     */
    public function addListener(EventListenerInterface|Overloadable $listener, ?string $name = null): self
    {
        if (!isset($this->listener)
            || !($this->listener instanceof EventListener\Chain)
        ) {
            $this->listener = new EventListener\Chain();
        }
        $this->listener->add($listener, $name);

        return $this;
    }

    /**
     * @phpstan-return EventListenerInterface|Overloadable<EventListenerInterface>
     * @throws EventListener\Exception
     */
    public function getListener(): EventListenerInterface|Overloadable
    {
        if (!isset($this->listener)) {
            if (isset($this->parent)) {
                return $this->parent->getListener();
            }
            throw new EventListener\Exception('Could not get a listener');
        }
        return $this->listener;
    }

    /**
     * @phpstan-param EventListenerInterface|Overloadable<EventListenerInterface> $listener
     * @return $this
     * @throws EventListener\Exception
     */
    public function setListener(EventListenerInterface|Overloadable|null $listener): self
    {
        $this->listener = $listener;
        return $this;
    }

    // RECORD LISTENER ========================================================
    protected Record\ListenerInterface|Overloadable|null $recordListener = null;

    /**
     * @phpstan-param Record\ListenerInterface|Overloadable<Record\ListenerInterface> $listener
     * @return $this
     */
    public function addRecordListener(Record\ListenerInterface|Overloadable $listener, ?string $name = null): self
    {
        if (!isset($this->recordListener)
            || !($this->recordListener instanceof Record\Listener\Chain)
        ) {
            $this->recordListener = new Record\Listener\Chain();
        }
        $this->recordListener->add($listener, $name);

        return $this;
    }

    /**
     * @phpstan-return Record\ListenerInterface|Overloadable<Record\ListenerInterface>
     * @throws EventListener\Exception
     */
    public function getRecordListener(): Record\ListenerInterface|Overloadable
    {
        if (!isset($this->recordListener)) {
            if (isset($this->parent)) {
                return $this->parent->getRecordListener();
            }
            throw new EventListener\Exception('Could not get a listener');
        }
        return $this->recordListener;
    }

    /**
     * @phpstan-param Record\ListenerInterface|Overloadable<Record\ListenerInterface> $listener
     * @return $this
     */
    public function setRecordListener(Record\ListenerInterface|Overloadable|null $listener): self
    {
        $this->recordListener = $listener;
        return $this;
    }

    // ========================================================================

    /**
     * sets a parent for this configurable component
     * the parent must be configurable component itself
     */
    public function setParent(Configurable $component): void
    {
        $this->parent = $component;
    }

    /**
     * returns the parent of this component
     *
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }
}
