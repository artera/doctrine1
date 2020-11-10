<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketNjeroTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public static function prepareTables(): void
    {
        static::$tables[] = 'CoverageCodeN';
        static::$tables[] = 'PolicyCodeN';
        static::$tables[] = 'LiabilityCodeN';
        static::$tables[] = 'PolicyN';
        static::$tables[] = 'RateN';

        parent::prepareTables();
    }

    public function testHasOneMultiLevelRelations()
    {
        $policy_code              = new \PolicyCodeN();
        $policy_code->code        = 1;
        $policy_code->description = 'Special Policy';
        $policy_code->save();

        $coverage_code              = new \CoverageCodeN();
        $coverage_code->code        = 1;
        $coverage_code->description = 'Full Coverage';
        $coverage_code->save();

        $coverage_code              = new \CoverageCodeN();
        $coverage_code->code        = 3; // note we skip 2
        $coverage_code->description = 'Partial Coverage';
        $coverage_code->save();

        $liability_code              = new \LiabilityCodeN();
        $liability_code->code        = 1;
        $liability_code->description = 'Limited Territory';
        $liability_code->save();

        $rate                 = new \RateN();
        $rate->policy_code    = 1;
        $rate->coverage_code  = 3;
        $rate->liability_code = 1;
        $rate->total_rate     = 123.45;
        $rate->save();

        $policy                = new \PolicyN();
        $policy->rate_id       = 1;
        $policy->policy_number = '123456789';
        $policy->save();

        $q = new \Doctrine_Query();

        // If I use
        // $p = $q->from('PolicyN p')
        // this test passes, but there is another issue just not reflected in this test yet, see "in my app" note below

        $q->from('PolicyN p, p.RateN r, r.PolicyCodeN y, r.CoverageCodeN c, r.LiabilityCodeN l')
            ->where('(p.id = ?)', ['1']);

        $p = $q->execute()->getFirst();

        $this->assertEquals($p->rate_id, 1);
        $this->assertEquals($p->RateN->id, 1);
        $this->assertEquals($p->RateN->policy_code, 1);
        $this->assertEquals($p->RateN->coverage_code, 3); // fail
        $this->assertEquals($p->RateN->liability_code, 1);

        $c  = $p->RateN->coverage_code;
        $c2 = $p->RateN->CoverageCodeN->code;
        $c3 = $p->RateN->coverage_code;

        $this->assertEquals($c, $c2); // fail
        $this->assertEquals($c, $c3); // in my app this fails as well, but I can't reproduce this
        // echo "Values " . serialize(array($c, $c2, $c3));
    }
}
