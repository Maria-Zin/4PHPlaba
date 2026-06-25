<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\CommentReaction;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentReaction>
 */
class CommentReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentReaction::class);
    }

    public function getReactionsByCommentIdForPost(Post $post, User $user): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('IDENTITY(r.comment) AS commentId');
        $qb->addSelect('SUM(CASE WHEN r.value = 1 THEN 1 ELSE 0 END) AS likes');
        $qb->addSelect('SUM(CASE WHEN r.value = -1 THEN 1 ELSE 0 END) AS dislikes');
        $qb->addSelect('MAX(CASE WHEN r.author = :user THEN r.value ELSE 0 END) AS my');
        $qb->innerJoin('r.comment', 'c');
        $qb->andWhere('c.post = :post');
        $qb->setParameter('post', $post);
        $qb->setParameter('user', $user);
        $qb->groupBy('commentId');

        $rows = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['commentId'];
            $result[$id] = [
                'likes' => (int) $row['likes'],
                'dislikes' => (int) $row['dislikes'],
                'my' => (int) $row['my'],
            ];
        }

        return $result;
    }

    /**
     * Топ комментариев по сумме лайков и дизлайков (один запрос к comment + reactions).
     *
     * @return list<array{comment: Comment, likes: int, dislikes: int}>
     */
    public function getTopCommentsByReactionCount(int $limit = 5): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('c');
        $qb->addSelect('SUM(CASE WHEN r.value = 1 THEN 1 ELSE 0 END) AS likes');
        $qb->addSelect('SUM(CASE WHEN r.value = -1 THEN 1 ELSE 0 END) AS dislikes');
        $qb->addSelect(
            'SUM(CASE WHEN r.value = 1 THEN 1 ELSE 0 END) + SUM(CASE WHEN r.value = -1 THEN 1 ELSE 0 END) AS HIDDEN totalReactions'
        );
        $qb->from(Comment::class, 'c');
        $qb->innerJoin('c.reactions', 'r');
        $qb->groupBy('c.id');
        $qb->orderBy('totalReactions', 'DESC');
        $qb->setMaxResults($limit);

        $result = [];
        foreach ($qb->getQuery()->getResult() as $row) {
            $result[] = [
                'comment' => $row[0],
                'likes' => (int) $row['likes'],
                'dislikes' => (int) $row['dislikes'],
            ];
        }

        return $result;
    }
}