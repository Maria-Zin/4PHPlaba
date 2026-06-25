<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_ADMIN")]
final class StatisticsController extends AbstractController
{
    #[Route("/admin/statistics", name: "app_statistics")]
    public function index(StatisticsService $statisticsService): Response
    {
        return $this->render(
            "statistics/index.html.twig",
            $statisticsService->getStatistics(),
        );
    }
}