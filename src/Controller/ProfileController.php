<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'user' => $user
        ]);
    }

    #[Route('/profile/new', name: 'app_profile_new', methods: ['GET', 'POST'])]
    public function create(Request $request): Response 
    {
        $profile = new Profile();
        $form = $this->createForm(ProfileType::class, $profile);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
            {
                $profile->setUser($this->getUser());
                $this->entityManager->persist($profile);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_profile');
            }
        return $this->render(
            'profile/new.html.twig',
            ['form' => $form]
        );
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        /** @var User */

        $user = $this->getUser();
        $profile = $user->getProfile();
        $form = $this->createForm(ProfileType::class, $profile);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
            {
                $this->entityManager->flush();

                return $this->redirectToRoute('app_profile');
            }
        return  $this->render(
            'profile/edit.html.twig',
            [ 'form' => $form]
        );
    }

    #[Route('/profile/delete', name:'app_profile_delete', methods: ['POST'])]
    public function deleteProfile(): Response
    {
        /** @var User */
        $user = $this->getUser();
        $profile = $user->getProfile();

        $this->entityManager->remove($profile);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_profile');
    }
}