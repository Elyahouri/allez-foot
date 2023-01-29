<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/allemands', name: 'app_allemands')]
    public function allemands(): Response
    {
        return $this->render('home/allemands.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/footballistiquementExperts', name: 'app_reporters')]
    public function reporters(UserRepository $userRepository): Response
    {
        return $this->render('home/reporters.html.twig', [
            'users' => $userRepository->findReporters(),
            'controller_name' => 'HomeController'
        ]);
    }

    #[Route('/foot', name: 'app_foot')]
    public function foot(): Response
    {
        return $this->render('home/foot.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
