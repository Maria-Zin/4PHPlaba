<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Form\CommentType;
use App\Message\AuthorNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/post/{post_id:post.id}/comment")]
#[IsGranted("ROLE_USER")]
final class CommentController extends AbstractController
{
    #[Route("/new", name: "app_comment_new", methods: ["GET", "POST"])]
    public function new(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $comment->setAuthor($user->getProfile());
            $comment->setPost($post);

            $entityManager->persist($comment);
            $entityManager->flush();

            $postAuthorEmail = $post->getProfile()?->getUser()?->getEmail();
            $commentAuthorEmail = $user->getEmail();

            if (
                $postAuthorEmail !== null &&
                $postAuthorEmail !== "" &&
                $postAuthorEmail !== $commentAuthorEmail
            ) {
                $messageBus->dispatch(
                    new AuthorNotificationMessage(
                        $postAuthorEmail,
                        "К вашему посту добавили комментарий",
                        sprintf(
                            "Пользователь %s добавил комментарий к вашему посту \"%s\".\n\nТекст комментария:\n%s",
                            $commentAuthorEmail ?? "unknown@example.com",
                            $post->getTitle() ?? "Без названия",
                            $comment->getContent() ?? "",
                        ),
                    ),
                );
            }

            return $this->redirectToRoute(
                "app_post_show",
                ["id" => $post->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render("comment/new.html.twig", [
            "comment" => $comment,
            "form" => $form,
        ]);
    }

    #[Route("/{id}/edit", name: "app_comment_edit", methods: ["GET", "POST"])]
    public function edit(
        Request $request,
        Comment $comment,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($comment->getAuthor() !== $user->getProfile()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                "app_post_show",
                ["id" => $comment->getPost()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render("comment/edit.html.twig", [
            "comment" => $comment,
            "form" => $form,
        ]);
    }

    #[Route("/{id}", name: "app_comment_delete", methods: ["POST"])]
    public function delete(
        Request $request,
        Comment $comment,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($comment->getAuthor() !== $user->getProfile()) {
            throw $this->createAccessDeniedException();
        }

        if (
            $this->isCsrfTokenValid(
                "delete" . $comment->getId(),
                $request->getPayload()->getString("_token"),
            )
        ) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute(
            "app_post_show",
            ["id" => $comment->getPost()->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }
}