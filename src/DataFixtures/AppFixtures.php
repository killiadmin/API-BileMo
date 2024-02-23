<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Buyer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Instantiating the Faker generator
        $faker = Factory::create();
        // Creation of 20 products
        for ($i = 0; $i < 20; $i++) {
            $product = new Product();
            $product->setLibelle($faker->word);
            $product->setDescription($faker->paragraph);
            $product->setPrice($faker->randomFloat(2, 0, 1000));
            $manager->persist($product);
        }

        // Creation of 20 clients with 10 users associated
        for ($i=0; $i < 20; $i++) {
            $user = new User();
            $user->setCompany($faker->company);
            $user->setEmail($faker->unique()->safeEmail);
            $user->setPassword($this->passwordHasher->hashPassword($user, "password"));
            $user->setRoles(['ROLE_ADMIN']);
            $manager->persist($user);

            for ($j=0; $j < 10; $j++) {
                $buyer = new Buyer();
                $buyer->setFirstname($faker->firstName);
                $buyer->setLastname($faker->lastName);
                $buyer->setEmail($faker->unique()->safeEmail);
                $buyer->setAddress($faker->address());
                $buyer->setPhone($faker->phoneNumber());
                $buyer->setCompanyAssociated($user);
                $manager->persist($buyer);
            }
        }

        $manager->flush();
    }
}
