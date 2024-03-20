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
     * @method Buyer[]    findBy(array $criteria, array $orderBy = null, int $limit = null, $offset = null)
     *
     * Retrieves a list of buyers with pagination.
     *
     * @param int $page The current page number.
     * @param int $limit The maximum number of results per page.
     *
     * @return array The list of buyers on the specified page.
     */
    public function findAllWithPagination(int $page, int $limit, int $idCompany): array
    {
        $qb = $this->createQueryBuilder('b')
            ->where('b.company_associated = :idCompany')
            ->setParameter('idCompany', $idCompany)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $query = $qb->getQuery();
        $query->setFetchMode(Buyer::class, 'company_associated', ClassMetadata::FETCH_EAGER);
        return $query->getResult();
    }
}
