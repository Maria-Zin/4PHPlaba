<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_USER")]
class HelloController extends AbstractController
{
    #[Route(path: "/hello")]
    public function hello(Request $request): Response
    {
        $requestIp = $request->getClientIp();
        $httpMethod = $request->getMethod();
        return new Response("Hello, $requestIp. Current method: $httpMethod");
    }

    #[Route("/hello/{name}")]
    public function greet(string $name, Request $request): Response
    {
        $baseUri = $request->getUri();
        return new Response("Hello $name. BaseURI: $baseUri");
    }

    #[Route("/hello/lucky/number", name: "app_generate_lucky_number")]
    public function generateLuckyNumber(): Response
    {
        return $this->redirectToRoute("app_profile");
    }

    #[Route("/hello/lucky/number/odd/{maxValue}")]
    public function getOddLuckyNumber(int $maxValue = 100): Response
    {
        return $this->redirectToRoute("app_profile");
    }

    #[Route("/hello/lucky/number/even")]
    public function getEvenLuckyNumber(): Response
    {
        return $this->redirectToRoute("app_profile");
    }
}