<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }


    public function load(ObjectManager $manager): void
    {   
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@gmail.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            '123456'
        );
        $admin->setPassword($hashedPassword);

         $manager->persist($admin);
        


        $staff = new User();
        $staff->setUsername('staff');
        $staff->setEmail('staff@gmail.com');
        $staff->setRoles(['ROLE_STAFF']);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $staff,
            '123456'
        );
        $staff->setPassword($hashedPassword);

        $manager->persist($staff);


        $staff = new User();
        $staff->setUsername('user');
        $staff->setEmail('user@gmail.com');
        $staff->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $staff,
            '123456'
        );
        $staff->setPassword($hashedPassword);

        $manager->persist($staff);



        
        $manager->flush();


    }
}
