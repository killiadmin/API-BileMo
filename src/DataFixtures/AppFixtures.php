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
        //Adding a Default Admin Account
        $userAdmin = new User();
        $userAdmin->setCompany('BileMo Administrator');
        $userAdmin->setEmail('support@bilemo.fr');
        $userAdmin->setPassword($this->passwordHasher->hashPassword($userAdmin, 'password'));
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $manager->persist($userAdmin);

        // Instantiating the Faker generator
        $faker = Factory::create();

        // Creation of 50 buyers with admin account
        $this->generationDatasBuyers($manager, $faker, $userAdmin);

        // Creation of 100 products
        for ($i = 0; $i < 100; $i++) {
            $product = new Product();
            $product->setLibelle($faker->word);
            $product->setDescription($faker->paragraph);
            $product->setPrice($faker->randomFloat(2, 0, 1000));
            $manager->persist($product);
        }

        // Creation of 50 clients with 20 users associated
        for ($i = 0; $i < 50; $i++) {
            $userClient = new User();
            $userClient->setCompany($faker->company);
            $userClient->setEmail($faker->unique()->safeEmail);
            $userClient->setPassword($this->passwordHasher->hashPassword($userClient, "password"));
            $userClient->setRoles(['ROLE_ADMIN']);
            $manager->persist($userClient);

            // Creation of 50 buyers
            $this->generationDatasBuyers($manager, $faker, $userClient);
        }

        $manager->flush();
    }

    /**
     * Generates dummy buyer data and persists it to the database.
     *
     * @param $manager
     * @param $fakerData
     * @param $userType
     * @return void
     */
    private function generationDatasBuyers($manager, $fakerData, $userType): void
    {
        for ($j = 0; $j < 20; $j++) {
            $buyer = new Buyer();
            $buyer->setFirstname($fakerData->firstName);
            $buyer->setLastname($fakerData->lastName);
            $buyer->setEmail($fakerData->unique()->safeEmail);
            $buyer->setAddress($fakerData->address());
            $buyer->setPhone($fakerData->phoneNumber());
            $buyer->setCompanyAssociated($userType);
            $manager->persist($buyer);
        }
    }
}
