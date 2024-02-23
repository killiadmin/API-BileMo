<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
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
            $client = new User();
            $client->setCompany($faker->company);
            $client->setEmail($faker->unique()->safeEmail);
            $client->setPassword($faker->password());
            $client->setRoles(['ROLES_ADMIN']);
            $manager->persist($client);
        }

        $manager->flush();
    }
}
