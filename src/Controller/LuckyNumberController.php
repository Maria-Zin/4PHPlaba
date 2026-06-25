<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_USER")]
#[Route("/lucky/number")]
final class LuckyNumberController extends AbstractController
{
    #[Route(name: "app_lucky_number_index", methods: ["GET"])]
    #[Route("/new", name: "app_lucky_number_new", methods: ["GET", "POST"])]
    #[Route("/{id}", name: "app_lucky_number_show", methods: ["GET"])]
    #[
        Route(
            "/{id}/edit",
            name: "app_lucky_number_edit",
            methods: ["GET", "POST"],
        ),
    ]
    #[Route("/{id}", name: "app_lucky_number_delete", methods: ["POST"])]
    public function redirectToProfile(): Response
    {
        return $this->redirectToRoute("app_profile");
    }
}