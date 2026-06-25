<?php

namespace App\Service;

use App\Repository\CommentReactionRepository;
use App\Repository\CommentRepository;
use App\Repository\ProfileRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    public function __construct(
        private PostRepository $postRepository,
        private CommentRepository $commentRepository,
        private CommentReactionRepository $commentReactionRepository,
        private ProfileRepository $profileRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{
     *     commentsCount: int,
     *     maxCommentsPost: object|null,
     *     minCommentsPost: object|null,
     *     postsGreaterThanAvg: array,
     *     commentWithMaxContent: object|null,
     *     topReactedComments: array,
     *     topProfiles: array,
     *     profilesWithPostsNoComments: array
     * }
     */
    public function getStatistics(): array
    {
        $query = $this->entityManager->createQuery('SELECT COUNT(c.id) FROM App\Entity\Comment c');
        /** @var int $commentsCount */
        $commentsCount = (int) $query->getSingleScalarResult();
        
        $maxCommentsPost = $this->postRepository->getPostWithMaxComments();
        $minCommentsPost = $this->postRepository->getPostWithMinComments();
        $postsGreaterThanAvg = $this->postRepository->getPostsWithCommentsGreaterThanAverage();
        $commentWithMaxContent = $this->commentRepository->getCommentWithMaxContent();
        $topReactedComments = $this->commentReactionRepository->getTopCommentsByReactionCount(5);
        $topProfiles = $this->profileRepository->getTopProfilesWithTotalCommentInTheirPosts(5);
        $profilesWithPostsNoComments = $this->profileRepository->getProfilesWithPostsAndWithoutComments();

        return [
            "commentsCount" => $commentsCount,
            "maxCommentsPost" => $maxCommentsPost,
            "minCommentsPost" => $minCommentsPost,
            "postsGreaterThanAvg" => $postsGreaterThanAvg,
            "commentWithMaxContent" => $commentWithMaxContent,
            "topReactedComments" => $topReactedComments,
            "topProfiles" => $topProfiles,
            "profilesWithPostsNoComments" => $profilesWithPostsNoComments,
        ];
    }
}