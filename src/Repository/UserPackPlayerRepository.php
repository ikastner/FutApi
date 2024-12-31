<?php

namespace App\Repository;

use App\Entity\UserPackPlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserPackPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPackPlayer::class);
    }

    public function findPlayersByUserId(int $userId): array
    {
        return $this->createQueryBuilder('upp')
            ->innerJoin('upp.player', 'player')
            ->addSelect('player')
            ->where('upp.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}