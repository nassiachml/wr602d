<?php

namespace App\Tests\Entity;

use App\Entity\Subscription;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $subscription = new Subscription();
        $name = 'Premium';
        $description = '50 PDF / jour';
        $maxPdfsPerDay = 50;
        $price = 9.99;
        $isActive = true;

        $subscription->setName($name);
        $subscription->setDescription($description);
        $subscription->setMaxPdfsPerDay($maxPdfsPerDay);
        $subscription->setPrice($price);
        $subscription->setIsActive($isActive);

        $this->assertEquals($name, $subscription->getName());
        $this->assertEquals($description, $subscription->getDescription());
        $this->assertEquals($maxPdfsPerDay, $subscription->getMaxPdfsPerDay());
        $this->assertEquals($price, $subscription->getPrice());
        $this->assertTrue($subscription->isActive());
    }
}
