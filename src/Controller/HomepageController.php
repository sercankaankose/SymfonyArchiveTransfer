<?php

namespace App\Controller;

use App\Entity\Articles;
use App\Entity\Issues;
use App\Entity\Journal;
use App\Entity\JournalUser;
use App\Entity\User;
use App\Params\RoleParam;
use App\Repository\JournalUserRepository;
use App\Service\BreadCrumbService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BreadCrumbService $breadcrumbService;
    private journalUserRepository $journalUserRepository;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, BreadCrumbService $breadcrumbService, Security $security, JournalUserRepository $journalUserRepository)
    {
        $this->entityManager = $entityManager;
        $this->breadcrumbService = $breadcrumbService;
        $this->security = $security;
        $this->journalUserRepository = $journalUserRepository;

    }

    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        $breadcrumb = $this->breadcrumbService->createEmptyBreadcrumb();

        if ($this->getUser() === null) {
            $this->redirectToRoute('app_login');
        }
        return $this->render('Homepage/homepage.html.twig', [
            'breadcrumb' => $breadcrumb,
        ]);
    }

    // operatör işlemleri

    //operatör dergileri
    #[Route('/operator/journals', name: 'operator_journal_management')]
    public function operatorJournals(Security $security, FactoryInterface $factory,): Response
    {
        $breadcrumb = $this->breadcrumbService->createOperatorManagementBreadcrumb($factory, 'Operatörlük');
        $user = $security->getUser();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getUserIdentifier()]);
        if ( !$user->isIsActive())
        {
            $this->addFlash('danger', 'Hesabınız Pasifleştirilmiştir.');
            return $this->redirectToRoute('app_homepage');
        }
        $journalUsers = $this->journalUserRepository->findByRoleOperator($user);

        return $this->render('Homepage/user_journals.html.twig', [
            'breadcrumb' => $breadcrumb,
            'journalUsers' => $journalUsers,
            'user' => $user,
            'role' => 'operator'

        ]);
    }

    // operatör sayı listesi
    #[Route('/operator/journal/{id}/issues', name: 'operator_journal_issues')]
    public function operatorJournalIssues($id, FactoryInterface $factory, Security $security): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);
        $user = $this->security->getUser();
        $breadcrumb = $this->breadcrumbService->createUserIssuesBreadcrumb($factory, $journal->getName());
        $issues = $this->entityManager->getRepository(Issues::class)->findBy([
            'journal' => $journal
        ]);
        if (!$journal) {
            $this->addFlash('danger', 'Dergi Bulunamadı.');
            if (in_array($this->getUser()->getRoles(), (array)RoleParam::ROLE_OPERATOR)) {
                return $this->redirectToRoute('operator_journal_management');
            } else {
                $this->addFlash('danger', 'Bu Derginin Sayılarına Erişme Yetkin Yok');
                return $this->redirectToRoute('app_homepage');
            }
        }
        $hasEditorRole = $this->journalUserRepository->userRoleInJournal($user, $journal, RoleParam::ROLE_OPERATOR);
        if ( !$user->isIsActive())
        {
            $this->addFlash('danger', 'Hesabınız Pasifleştirilmiştir.');
            return $this->redirectToRoute('app_homepage');
        }
        if ($hasEditorRole == false && !$user->isIsAdmin()) {
            throw $this->createNotFoundException('Giriş Yetkiniz Yok.');
        }
        return $this->render('journal_issues.html.twig', [
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
            'issues' => $issues,
            'user' => $user,
            'role' => 'operator',

        ]);
    }

    // operatör makale listesi
    #[Route('operator/issue/{id}/articles', name: 'operator_articles_list')]
    public function operatorArticleList($id, FactoryInterface $factory, Security $security): Response
    {
        $user = $this->security->getUser();
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getUserIdentifier()]);

        $journal = $issue->getJournal();
        $breadcrumb = $this->breadcrumbService->createUserArticleListBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $journal->getId());
        if (!$journal && !$issue) {
            $this->addFlash('danger', 'Dergi ya da sayı Bulunamadı.');
            if (in_array($this->getUser()->getRoles(), (array)RoleParam::ROLE_ADMIN)) {
                return $this->redirectToRoute('journal_issues', ['id' => $journal->getId()]);
            } else {
                return $this->redirectToRoute('app_homepage');
            }
        }

        if ( !$user->isIsActive())
        {
            $this->addFlash('danger', 'Hesabınız Pasifleştirilmiştir.');
            return $this->redirectToRoute('app_homepage');
        }


        $hasEditorRole = $this->journalUserRepository->userRoleInJournal($user, $journal, RoleParam::ROLE_OPERATOR);

        if ($hasEditorRole == false && !$user->isIsAdmin()) {
            throw $this->createNotFoundException('Giriş Yetkiniz Yok.');
        }
        $articles = $this->entityManager->getRepository(Articles::class)->findBy([
            'issue' => $issue
        ]);

        return $this->render('Homepage/user_articles_list.html.twig', [
            'breadcrumb' => $breadcrumb,
            'articles' => $articles,
            'issues' => $issue,
            'journal' => $journal,
            'user' => $user,
            'role' => 'operator'

        ]);
    }

    // editör işlemleri

    //editör dergileri
    #[Route('/editor/journals', name: 'editor_journal_management')]
    public function moderatorJournals(Security $security, FactoryInterface $factory,): Response
    {
        $breadcrumb = $this->breadcrumbService->createOperatorManagementBreadcrumb($factory, 'Editörlük');
        $user = $security->getUser();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getUserIdentifier()]);

        $journalUsers = $this->journalUserRepository->findByRoleEditor($user);
        if ( !$user->isIsActive())
        {
            $this->addFlash('danger', 'Hesabınız Pasifleştirilmiştir.');
            return $this->redirectToRoute('app_homepage');
        }
        return $this->render('Homepage/user_journals.html.twig', [
            'breadcrumb' => $breadcrumb,
            'journalUsers' => $journalUsers,
            'user' => $user,
            'role' => 'editor'

        ]);
    }

    //editor sayı listesi
    #[Route('/editor/journal/{id}/issues', name: 'editor_journal_issues')]
    public function editorJournalIssues($id, FactoryInterface $factory, Security $security): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);
        $user = $this->security->getUser();
        $breadcrumb = $this->breadcrumbService->createEditorIssuesBreadcrumb($factory, $journal->getName());
        $issues = $this->entityManager->getRepository(Issues::class)->findBy([
            'journal' => $journal
        ]);
        if (!$journal) {
            $this->addFlash('danger', 'Dergi Bulunamadı.');
            if (in_array($this->getUser()->getRoles(), (array)RoleParam::ROLE_OPERATOR) or in_array($this->getUser()->getRoles(), (array)RoleParam::ROLE_EDITOR)) {
                return $this->redirectToRoute('editor_journal_management');
            } else {
                $this->addFlash('danger', 'Bu Derginin Sayılarına Erişme Yetkin Yok');
                return $this->redirectToRoute('app_homepage');
            }

        }

        if ( !$user->isIsActive())
        {
            $this->addFlash('danger', 'Hesabınız Pasifleştirilmiştir.');
            return $this->redirectToRoute('app_homepage');
        }
        $hasEditorRole = $this->journalUserRepository->userRoleInJournal($user, $journal, RoleParam::ROLE_EDITOR);

        if ($hasEditorRole == false && !$user->isIsAdmin()) {
            throw $this->createNotFoundException('Giriş Yetkiniz Yok.');
        }
        return $this->render('journal_issues.html.twig', [
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
            'issues' => $issues,
            'user' => $user,
            'role' => 'editor',

        ]);
    }

    //editör makale listesi
    #[Route('editor/issue/{id}/articles', name: 'editor_articles_list')]
    public function editorArticleList($id, FactoryInterface $factory, Security $security): Response
    {
        $user = $this->security->getUser();
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getUserIdentifier()]);

        $journal = $issue->getJournal();
        $breadcrumb = $this->breadcrumbService->createEditorArticleListBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $journal->getId());
        if (!$journal && !$issue) {
            $this->addFlash('danger', 'Dergi ya da sayı Bulunamadı.');
            if (in_array($this->getUser()->getRoles(), (array)RoleParam::ROLE_ADMIN)) {
                return $this->redirectToRoute('journal_issues', ['id' => $journal->getId()]);
            } else {
                return $this->redirectToRoute('app_homepage');
            }
        }
        if ( !$user->isIsActive())
        {
            $this->addFlash('danger', 'Hesabınız Pasifleştirilmiştir.');
            return $this->redirectToRoute('app_homepage');
        }
        $hasEditorRole = $this->journalUserRepository->userRoleInJournal($user, $journal, RoleParam::ROLE_EDITOR);

        if ($hasEditorRole == false && !$user->isIsAdmin()) {
            throw $this->createNotFoundException('Giriş Yetkiniz Yok.');
        }
        $articles = $this->entityManager->getRepository(Articles::class)->findBy([
            'issue' => $issue
        ]);

        return $this->render('Homepage/user_articles_list.html.twig', [
            'breadcrumb' => $breadcrumb,
            'articles' => $articles,
            'issues' => $issue,
            'journal' => $journal,
            'user' => $user,
            'role' => 'editor'

        ]);
    }
}
