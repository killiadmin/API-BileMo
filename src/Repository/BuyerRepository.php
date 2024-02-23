<?php

namespace App\Repository;

use App\Entity\Buyer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Buyer>
 *
 * @method Buyer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Buyer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Buyer[]    findAll()
 * @method Buyer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BuyerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Buyer::class);
    }
}
