<?php
class CheckConstraintTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('price', 'decimal', 2, ['max' => 5000, 'min' => 100]);
        $this->hasColumn('discounted_price', 'decimal', 2);
        $this->check('price > discounted_price');
    }
}
