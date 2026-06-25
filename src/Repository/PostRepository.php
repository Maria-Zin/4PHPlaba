<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return list<Post>
     */
    public function getPostsByProfile(Profile $profile): array
    {
        return $this->createQueryBuilder("p")
            ->andWhere("p.profile = :profile")
            ->setParameter("profile", $profile)
            ->orderBy("p.id", "DESC")
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Post>
     */
    public function getAllPostsOrdered(): array
    {
        return $this->createQueryBuilder("p")
            ->leftJoin("p.profile", "pr")
            ->addSelect("pr")
            ->leftJoin("pr.user", "u")
            ->addSelect("u")
            ->orderBy("p.id", "DESC")
            ->getQuery()
            ->getResult();
    }

    public function savePost(Post $post): void
    {
        $em = $this->getEntityManager();
        $em->persist($post);
        $em->flush();
    }

    public function deletePost(Post $post): void
    {
        $em = $this->getEntityManager();
        $em->remove($post);
        $em->flush();
    }

    public function getPostWithMaxComments()
    {
        $qb = $this->createQueryBuilder("p");
        $qb->select("p");
        $qb->addSelect("COUNT(c.id) AS HIDDEN cnt");
        $qb->leftJoin("p.comments", "c");
        $qb->groupBy("p.id");
        $qb->orderBy("cnt", "DESC");
        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Возвращается пост с минимальным (но не нулевым) количеством комментариев
     */
    public function getPostWithMinComments()
    {
        $qb = $this->createQueryBuilder("p");
        $qb->leftJoin("p.comments", "c");
        $qb->groupBy("p.id");
        $qb->having("COUNT(c.id) > 0");
        $qb->orderBy("COUNT(c.id)", "ASC");
        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Возвращает список постов, у которых комментариев больше среднего
     * ПОДСКАЗКА: Среднее количество комментариев можно выполнить отдельным запросом или подзапросом
     */
    public function getPostsWithCommentsGreaterThanAverage(): array
    {
        $postCount = (int) $this->createQueryBuilder("p")
            ->select("COUNT(p.id)")
            ->getQuery()
            ->getSingleScalarResult();

        if ($postCount === 0) {
            return [];
        }

        $totalComments = (int) $this->createQueryBuilder("p")
            ->select("COUNT(c.id)")
            ->leftJoin("p.comments", "c")
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder("p");
        $qb->select("p");
        $qb->addSelect("COUNT(c.id) AS HIDDEN cnt");
        $qb->leftJoin("p.comments", "c");
        $qb->groupBy("p.id");
        $qb->having("COUNT(c.id) * :postCount > :totalComments");
        $qb->setParameter("postCount", $postCount);
        $qb->setParameter("totalComments", $totalComments);
        $qb->orderBy("cnt", "DESC");

        return $qb->getQuery()->getResult();
    }
}