<?php
class QueryTest_User extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn(
            'username as username',
            'string',
            50,
            ['notnull']
        );
        $this->hasColumn('visibleRankId', 'integer', 4);
        $this->hasColumn('subscriptionId', 'integer', 4);
    }

    /**
     * Runtime definition of the relationships to other entities.
     */
    public function setUp(): void
    {
        $this->hasOne(
            'QueryTest_Rank as visibleRank',
            [
            'local' => 'visibleRankId', 'foreign' => 'id'
            ]
        );

        $this->hasOne(
            'QueryTest_Subscription',
            [
            'local' => 'subscriptionId', 'foreign' => 'id'
            ]
        );

        $this->hasMany(
            'QueryTest_Rank as ranks',
            [
            'local' => 'userId', 'foreign' => 'rankId', 'refClass' => 'QueryTest_UserRank'
            ]
        );
    }
}
