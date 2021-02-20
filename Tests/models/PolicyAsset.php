<?php
class PolicyAsset extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('policy_number', 'integer', 11);
        $this->hasColumn('value', 'float', 10, ['notblank' => true,]);
    }

    public function setUp(): void
    {
        $this->hasOne(
            'Policy',
            ['foreign' => 'policy_number',
            'local'   => 'policy_number']
        );
        $this->index('policy_number_index', ['fields' => ['policy_number']]);
    }
}
