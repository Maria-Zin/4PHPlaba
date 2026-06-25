<?php

namespace App\Controller;

use App\Service\UniversityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UniversityController extends AbstractController
{
    private UniversityService $universityService;

    public function __construct(UniversityService $universityService)
    {
        $this->universityService = $universityService;
    }

    #[Route('/universities/{name}', name: 'app_university')]
    public function index(string $name): Response
    {
        $universities = $this->universityService->getUniversitiesByName($name);

        return $this->render('university/index.html.twig', [
            'universities' => $universities,
        ]);
    }
}