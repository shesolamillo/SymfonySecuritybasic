<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User; // ✅ correct
use App\Entity\Loyalty;


class LoyaltyFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);


        $user = $manager->getRepository(User::class)->findOneBy([]);

        // Create loyalty points for the user
        $loyalty = new Loyalty();
        $loyalty->setUser($user);
        $loyalty->setUsername($user->getUsername());
        $loyalty->setPoints(100); // example points
        $loyalty->setRewardType('Discount'); // example reward type
        $loyalty->setCreatedAt(new \DateTimeImmutable());
        $loyalty->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($loyalty);




        $manager->flush();
    }
}
