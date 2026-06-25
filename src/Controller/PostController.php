<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\PostRepository;
use App\Entity\Post;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\PostType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\CommentReactionRepository;

final class PostController extends AbstractController
{
    public function __construct(private PostRepository $postRepository) {}

    #[IsGranted("ROLE_USER")]
    #[Route("/post", name: "app_post")]
    public function index(): Response
    {
        return $this->render("post/index.html.twig", [
            "posts" => $this->postRepository->getAllPostsOrdered(),
            "pageTitle" => "Лента постов",
            "isMyPosts" => false,
        ]);
    }

    #[IsGranted("ROLE_USER")]
    #[Route("/post/my", name: "app_post_my")]
    public function myPosts(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        return $this->render("post/index.html.twig", [
            "posts" =>
                $profile === null
                    ? []
                    : $this->postRepository->getPostsByProfile($profile),
            "pageTitle" => "Мои посты",
            "isMyPosts" => true,
        ]);
    }

    #[IsGranted("ROLE_USER")]
    #[Route("/posts", name: "app_posts_all")]
    public function all(): Response
    {
        return $this->redirectToRoute("app_post");
    }

    #[IsGranted("ROLE_USER")]
    #[Route("/post/create", name: "app_post_new", methods: ["GET", "POST"])]
    public function createPost(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User */
            $user = $this->getUser();
            $profile = $user->getProfile();
            $post->setProfile($profile);

            $this->postRepository->savePost($post);
            return $this->redirectToRoute("app_post_my");
        }

        return $this->render("post/new.html.twig", ["form" => $form]);
    }

    #[IsGranted("ROLE_USER")]
    #[Route("/post/{id}/show", name: "app_post_show", methods: ["GET"])]
    public function show(
        Post $post,
        CommentReactionRepository $commentReactionRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $reactionsByCommentId = $commentReactionRepository->getReactionsByCommentIdForPost(
            $post,
            $user,
        );

        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment, [
            "action" => $this->generateUrl("app_comment_new", [
                "post_id" => $post->getId(),
            ]),
        ]);

        return $this->render("post/show.html.twig", [
            "post" => $post,
            "form" => $commentForm,
            "reactionsByCommentId" => $reactionsByCommentId,
        ]);
    }

    #[IsGranted("ROLE_USER")]
    #[Route("/post/{id}/edit", name: "app_post_edit", methods: ["GET", "POST"])]
    public function edit(Request $request, Post $post): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($post->getProfile() !== $user->getProfile()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->postRepository->savePost($post);
            return $this->redirectToRoute("app_post_my");
        }
        return $this->render("post/edit.html.twig", [
            "post" => $post,
            "form" => $form,
        ]);
    }

    #[IsGranted("ROLE_USER")]
    #[Route("/post/{id}/delete", name: "app_post_delete", methods: ["POST"])]
    public function delete(Post $post): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($post->getProfile() !== $user->getProfile()) {
            throw $this->createAccessDeniedException();
        }

        $this->postRepository->deletePost($post);

        return $this->redirectToRoute("app_post_my");
    }
}