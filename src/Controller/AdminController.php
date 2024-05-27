<?php

namespace App\Controller;

use App\Entity\Issues;
use App\Entity\Journal;
use App\Entity\JournalUser;
use App\Entity\Role;
use App\Entity\User;
use App\Form\JournalUserAssigmentType;
use App\Params\IssueStatusParam;
use App\Params\RoleParam;
use App\Form\JournalFormType;
use App\Form\RegistrationFormType;
use App\Repository\JournalUserRepository;
use App\Service\BreadCrumbService;
use App\Util\TypeModifier;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SYSTEM_OPERATOR')]
class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BreadCrumbService $breadcrumbService;
    private $journalUserRepository;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security, BreadCrumbService $breadcrumbService, JournalUserRepository $journalUserRepository)
    {
        $this->entityManager = $entityManager;
        $this->breadcrumbService = $breadcrumbService;
        $this->journalUserRepository = $journalUserRepository;
        $this->security = $security;
    }

    #[Route('/admin', name: 'dashboard_admin')]
    public function index(): Response
    {
        $user = $this->security->getUser();
        $breadcrumb = $this->breadcrumbService->createAdminDashboardBreadcrumb();

        return $this->render('admin/index.html.twig', [
            'breadcrumb' => $breadcrumb,
            'user' => $user,
        ]);
    }

    //-----------------------------------------------------------------
    //KULLANICI İŞLEMLERİ

//    kullanıcı paneli
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/user-management', name: 'admin_user_management')]
    public function userManagement(): Response
    {
        $allusers = $this->entityManager->getRepository(User::class)->findAll();
        $breadcrumb = $this->breadcrumbService->createUserManagementBreadcrumb();


        return $this->render('admin/user/user_management.html.twig', [
            'users' => $allusers,
            'breadcrumb' => $breadcrumb,

        ]);
    }


//    kullanıcı ekleme
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/user/add', name: 'admin_user_add')]
    public function userAdd(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $newuser = new User();
        $form = $this->createForm(RegistrationFormType::class, $newuser);
        $breadcrumb = $this->breadcrumbService->createUserAddBreadcrumb();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newuser->setPassword(
                $userPasswordHasher->hashPassword(
                    $newuser,
                    $form->get('plainPassword')->getData()
                )
            );
            $this->entityManager->persist($newuser);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Yeni Kullanıcı Oluşturulmuştur.'
            );
            return $this->redirectToRoute('admin_user_add');
        }
        return $this->render('admin/user/new-user-add.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,


        ]);

    }


//    kullanıcı pasifleştirme Func
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/user/passive/{id}', name: 'admin_user_pasive')]
    public function userPassiveFunc($id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        $user->setIsActive(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->addFlash('success', 'Kullanıcı Pasifleştirme İşlemi Başarılı');

        return $this->redirectToRoute('admin_user_management');

    }

//    kullanıcı aktifleştirme Func
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/user/active/{id}', name: 'admin_user_active')]
    public function userActiveFunc($id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        $user->setIsActive(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->addFlash('success', 'Kullanıcı Etkin Hale Getirilmiştir.');

        return $this->redirectToRoute('admin_user_management');
    }

    // kullanıcıya dergi atama işlemi
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/assigment/journal', name: 'admin_assigment_journal')]
    public function UserJournalAssign(Request $request): Response
    {
        $breadcrumb = $this->breadcrumbService->createJournalAssigmentBreadcrumb();

        $form = $this->createForm(JournalUserAssigmentType::class);
        $form->handleRequest($request);

        $role_editor = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_EDITOR]);
        $role_operator = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_OPERATOR]);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $usermail = $data['Kullanici'];
            $journalname = $data['Dergi'];
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $usermail]);
            $journal = $this->entityManager->getRepository(Journal::class)->findOneBy(['name' => $journalname]);

            if ($journal === null) {
                $this->addFlash('danger', 'Bu İsimde Dergi Bulunamadı');
                return $this->redirectToRoute('admin_assigment_journal');
            }
            if ($user === null) {
                $this->addFlash('danger', 'Böyle bir kullanıcı Bulunamadı');
                return $this->redirectToRoute('admin_assigment_journal');
            }
            $existingRecord = $this->entityManager->getRepository(JournalUser::class)->findOneBy([
                'journal' => $journal,
                'person' => $user,
            ]);

            if ($existingRecord) {
                $existingRoles = $existingRecord->getRole()->toArray();

                if ($data['ROLE_EDITOR'] && !in_array($role_editor, $existingRoles, true)) {
                    $existingRecord->addRole($role_editor);
                }

                if ($data['ROLE_OPERATOR'] && !in_array($role_operator, $existingRoles, true)) {
                    $existingRecord->addRole($role_operator);
                }

                if ($existingRecord->getRole()->contains($role_editor) || $existingRecord->getRole()->contains($role_operator)) {
                    $this->entityManager->flush();
                } else {
                    $this->entityManager->persist($existingRecord);
                    $this->entityManager->flush();
                }
            } else {
                $journalUser = new JournalUser();

                if ($data['ROLE_EDITOR']) {
                    $journalUser->addRole($role_editor);
                    $user->addRoles($role_editor);
                }
                if ($data['ROLE_OPERATOR']) {
                    $journalUser->addRole($role_operator);
                    $user->addRoles($role_operator);
                }
                $journalUser->setPerson($user);
                $journalUser->setJournal($journal);
                $this->entityManager->persist($journalUser);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            $this->addFlash('success', 'Dergiye Atama İşlemi Yapılmıştır.');
            return $this->redirectToRoute('admin_assigment_journal');
        }

        return $this->render('admin/user/user_journal_assigment.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,

        ]);
    }

    //sistem operatörü atama
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/assigment/{id}/system-operator', name: 'admin_system_operator_assing')]
    public function userSystemOperatorAssign($id): Response
    {
        $roleSystemOperator = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_SYSTEM_OPERATOR]);
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if ($user === null) {
            $this->addFlash('danger', 'Böyle bir kullanıcı Bulunamadı');
            return $this->redirectToRoute('admin_user_management');
        }

        $user->addRoles($roleSystemOperator);
        $user->setIsAdmin(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->addFlash('success', $user->getName() . ' ' . $user->getSurname() . ' Sistem Operatörü Olarak Atanmıştır.');

        return $this->redirectToRoute('admin_user_management');
    }

    //sistem operatörü yetki silme
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/delete/{id}/role', name: 'admin_system_operator_delete')]
    public function userSystemOperatorDelete($id): Response
    {
        $roleSystemOperator = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_SYSTEM_OPERATOR]);
//        $roleAdmin = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_ADMIN]);
        $user = $this->entityManager->getRepository(User::class)->find($id);


        if ($user === null) {
            $this->addFlash('danger', 'Böyle bir kullanıcı Bulunamadı');
            return $this->redirectToRoute('admin_user_management');
        }
        if ($user->getEmail() === 'sadik.guler@yt.com.tr') {
            $this->addFlash('danger', 'Bu kullanıcının yetkileri silinemez');
            return $this->redirectToRoute('admin_user_management');
        }

        $user->removeRole($roleSystemOperator);
        $user->setIsAdmin(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();


        $this->addFlash('success', $user->getName() . ' ' . $user->getSurname() . 'Sistem Operatörü Yetkisi Alınmıştır.');
        return $this->redirectToRoute('admin_user_management');
    }

    // kullanıcının atandığı dergiler
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/assigned/{id}', name: 'admin_assigned_journal_list')]
    public function adminAssignedJournal($id)
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        $breadcrumb = $this->breadcrumbService->createUserAssigned_listBreadcrumb();

        $journalUser = $this->entityManager->getRepository(JournalUser::class)->findBy([
            'person' => $user
        ]);
        $role_editor = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_EDITOR]);
        $role_Operator = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_OPERATOR]);


        return $this->render('admin/user/assignedJournal.html.twig', [
            'user' => $user,
            'journals' => $journalUser,
            'breadcrumb' => $breadcrumb,
            'editor' => $role_editor,
            'operator' => $role_Operator,
        ]);
    }

    //ajax dergi
    #[Route("/search_journals", name: "search_journals")]
    public function searchJournals(Request $request)
    {
        $searchTerm = $request->query->get('term');

        $journals = $this->entityManager->getRepository(Journal::class)->searchByName($searchTerm);

        $journalNames = [];
        foreach ($journals as $journal) {
            $journalNames[] = $journal->getName();
        }

        return new JsonResponse($journalNames);
    }

    //ajax kullanıcı
    #[Route("/search_user", name: "search_user")]
    public function searchUsers(Request $request)
    {
        $searchTerm = $request->query->get('term');

        $users = $this->entityManager->getRepository(User::class)->searchByName($searchTerm);

        $userNames = [];
        foreach ($users as $user) {
            $userNames[] = $user->getEmail();
        }

        return new JsonResponse($userNames);
    }

    //atanmış rol silme
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/assigned/{id}/{role}', name: 'admin_assigned_journal_delete')]
    public function adminAssignedJournalDeleteFunc($id, $role)
    {
        $journalUser = $this->entityManager->getRepository(JournalUser::class)->find($id);
        $roleEntity = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => $role]);

        $user = $journalUser->getPerson();
        $journalUser->removeRole($roleEntity);

        if ($journalUser->getRole()->isEmpty()) {
            $this->entityManager->remove($journalUser);
        }
        $this->entityManager->flush();
        $roleStillAssigned = false;
        if ($role === RoleParam::ROLE_OPERATOR) {
            $otherJournalUsersWithRole = $this->entityManager->getRepository(JournalUser::class)->findByRoleOperator($user);
        } elseif ($role === RoleParam::ROLE_EDITOR) {
            $otherJournalUsersWithRole = $this->entityManager->getRepository(JournalUser::class)->findByRoleEditor($user);
        } else {
            $otherJournalUsersWithRole = [];
        }

        if (!empty($otherJournalUsersWithRole)) {
            $roleStillAssigned = true;
        }
        if (!$roleStillAssigned) {
            $user->removeRole($roleEntity);
        }

        $this->entityManager->flush();

//        $otherJournalUsers = $this->entityManager->getRepository(JournalUser::class)->findBy([
//            'person' => $user,
//
//        ]);
//        $rolesIds = [];
//
//        /** @var JournalUser $journalUser */
//        foreach ($otherJournalUsers as $journalUser) {
//            if (count($journalUser->getRole()) > 0) {
//                foreach ($journalUser->getRole() as $role) {
//                    $rolesIds[] = $role->getId();
//                }
//            }
//        }
//        if (!in_array($rolesIds, (array)($role->getId()))) {
//            $user->removeRole($role);
//        }
        $this->entityManager->flush();


        $this->addFlash('success', 'Kullanıcının Dergideki Rolü Kaldırılmıştır.');
        return $this->redirectToRoute('admin_assigned_journal_list', ['id' => $user->getId()]);
    }

//-----------------------------------------------------------------
    //DERGİ İŞLEMLERİ

//Dergi listesi paneli
    #[Route('/admin/journal-management', name: 'admin_journal_management')]
    public function journalManagement(Security $security): Response
    {
        $user = $security->getUser();
        $all_journal = $this->entityManager->getRepository(Journal::class)->findAll();
        $breadcrumb = $this->breadcrumbService->createJournalManagementBreadcrumb();
        return $this->render('admin/journal/journal_management.html.twig', [
            'journals' => $all_journal,
            'user' => $user,
            'breadcrumb' => $breadcrumb,
            'role' => 'admin'
        ]);
    }

//     ajax
//    #[Route('/ajax/list', name: 'example_ajax', methods: ['GET', 'POST'])]
//    public function exampleAjaxAction(Request $request): JsonResponse
//    {
//        $draw = $request->request->get('draw');
//        $start = $request->request->get('start');
//        $length = $request->request->get('length');
//        $search = $request->request->get('search')['value'] ?? '';
//        $order = $request->request->get('order')[0] ?? null;
//        $defaultColumn = 'name';
//
//        if ($order !== null) {
//            $column = $request->request->get('columns')[$order['column']]['data'];
//            $dir = $order['dir'];
//        } else {
//            $column = $defaultColumn;
//            $dir = 'asc';
//        }
//
//        $data = $this->entityManager->getRepository(Journal::class)
//            ->findBySearchAndSort($search, $column, $dir, $start, $length);
//
//        $totalRecords = $this->entityManager->getRepository(Journal::class)->getTotalRecords();
//        $filteredRecords = $this->entityManager->getRepository(Journal::class)
//            ->getFilteredRecords($search);
//
//        $output = [
//            'draw' => intval($draw),
//            'recordsTotal' => $totalRecords,
//            'recordsFiltered' => $filteredRecords,
//            'data' => [],
//        ];
//
//        foreach ($data as $item) {
//            $output['data'][] = [
//                'name' => $item->getName(),
//                'issn' => $item->getIssn(),
//                'eIssn' => $item->getEIssn(),
//            ];
//        }
//
//        return new JsonResponse($output);
//    }


//    Dergi Ekleme
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/journal/add', name: 'admin_journal_add')]
    public function journalAdd(Request $request): Response
    {
        $breadcrumb = $this->breadcrumbService->createJournalAddBreadcrumb();

        $newjournal = new Journal();
        $form = $this->createForm(JournalFormType::class, $newjournal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($newjournal);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Yeni Dergi Oluşturulmuştur.'
            );
            return $this->redirectToRoute('admin_journal_management');
        }
        return $this->render('admin/journal/journal_add_edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'name' => 'Yeni Dergi',
            'button' => 'Dergi Ekle'

        ]);

    }


//    Dergi Silme Func
    #[Route('/admin/journal/delete/{id}', name: 'admin_journal_delete')]
    public function journalDeleteFunc($id): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);

        if (!$journal) {
            $this->addFlash('danger', 'Dergi Bulunamadı.');
            return $this->redirectToRoute('admin_journal_management');
        }
        $issues = $journal->getIssues();
        foreach ($issues as $issue) {
            $articles = $issue->getArticles();
            $xmlFilePath = $issue->getXml();

            if ($xmlFilePath && file_exists($xmlFilePath)) {
                unlink($xmlFilePath);
            }
            foreach ($articles as $article) {
                $translations = $article->getTranslations();
                $authors = $article->getAuthors();
                $translators = $article->getTranslators();
                $citations = $article->getCitations();

                foreach ($translations as $translation) {
                    $this->entityManager->remove($translation);
                }
                foreach ($authors as $author) {
                    $this->entityManager->remove($author);
                }
                foreach ($translators as $translator) {
                    $this->entityManager->remove($translator);
                }
                foreach ($citations as $citation) {
                    $this->entityManager->remove($citation);
                }
                $pdfFilePath = $article->getFulltext();

                if ($pdfFilePath && file_exists($pdfFilePath)) {
                    unlink($pdfFilePath);
                }

                $pdfFolderPath = substr($pdfFilePath, 0, strrpos($pdfFilePath, '/'));

                if (is_dir($pdfFolderPath)) {
                    if (count(scandir($pdfFolderPath)) == 2) {
                        $filesystem = new Filesystem();
                        try {
                            $filesystem->remove($pdfFolderPath);
                        } catch (FileException $e) {
                            return new Response($e->getMessage());
                        }
                    }
                }
                $this->entityManager->flush();

                $this->entityManager->remove($article);
                $this->entityManager->flush();
            }
            $this->entityManager->remove($issue);
            $this->entityManager->flush();
        }
        $this->entityManager->remove($journal);
        $this->entityManager->flush();
        $this->addFlash('success', 'Dergi Silme İşlemi Başarılı');

        return $this->redirectToRoute('admin_journal_management');
    }


}






