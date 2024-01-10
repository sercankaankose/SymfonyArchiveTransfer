<?php

namespace App\Controller;

use App\Params\ROLE_PARAM;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_redirect_login');
        }


        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

//    #[Route('/redirect/login', name: 'app_redirect_login')]
//    public function redirectLogin(AuthorizationCheckerInterface $authorizationChecker): Response
//    {
//        $user = $this->security->getUser();
//        if ($user) {
//            $roles = $user->getRoles();
//            if ($authorizationChecker->isGranted(ROLE_PARAM::ROLE_ADMIN)) {
//                return $this->redirectToRoute('dashboard_admin');
//            } elseif ($authorizationChecker->isGranted(ROLE_PARAM::ROLE_EDITOR)) {
//                return $this->redirectToRoute('dashboard_editor');
//            }
//        }
//
//        return $this->redirectToRoute('app_homepage');
//    }
}
