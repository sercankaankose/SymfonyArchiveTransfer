<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OperatorController extends AbstractController
{
    #[Route('/operator', name: 'dashboard_operator')]
    public function index(): Response
    {
        return $this->render('operator/index.html.twig', [
            'controller_name' => 'OperatorController',
        ]);
    }
}
