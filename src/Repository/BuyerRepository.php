<?php

namespace App\Repository;

use App\Entity\Buyer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
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

    /**
     * @extends ServiceEntityRepository<Buyer>
     *
     * @method Buyer|null find($id, $lockMode = null, $lockVersion = null)
     * @method Buyer|null findOneBy(array $criteria, array $orderBy = null)
     * @method Buyer[]    findAll()
     * @method Buyer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
     */
    public function findAllWithPagination(int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('b')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $query = $qb->getQuery();
        $query->setFetchMode(Buyer::class, 'company_associated', ClassMetadata::FETCH_EAGER);
        return $query->getResult();
    }
}
