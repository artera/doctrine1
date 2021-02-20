<?php
class Policy extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('policy_number', 'integer', 11, ['unique' => true]);
    }

    public function setUp(): void
    {
        $this->hasMany(
            'PolicyAsset as PolicyAssets',
            ['local'   => 'policy_number',
            'foreign' => 'policy_number']
        );
        $this->index('policy_number_index', ['fields' => ['policy_number']]);
    }
}
