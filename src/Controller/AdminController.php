<?php

namespace App\Controller;

use App\Entity\Issues;
use App\Entity\Journal;
use App\Entity\JournalUser;
use App\Entity\Role;
use App\Entity\User;
use App\Form\JournalUserAssigmentType;
use App\Params\RoleParam;
use App\Form\JournalFormType;
use App\Form\RegistrationFormType;
use App\Service\BreadCrumbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BreadCrumbService $breadcrumbService;

    public function __construct(EntityManagerInterface $entityManager, BreadCrumbService $breadcrumbService)
    {
        $this->entityManager = $entityManager;
        $this->breadcrumbService = $breadcrumbService;
    }

    #[Route('/admin', name: 'dashboard_admin')]
    public function index(): Response
    {

        $breadcrumb = $this->breadcrumbService->createAdminDashboardBreadcrumb();

        return $this->render('admin/index.html.twig', [
            'breadcrumb' => $breadcrumb,

        ]);
    }

    //-----------------------------------------------------------------
    //KULLANICI İŞLEMLERİ


//    kullanıcı paneli
    #[Route('/admin/user-management', name: 'admin_user_management')]
    public function user_management(): Response
    {
        $allusers = $this->entityManager->getRepository(User::class)->findBy(["is_admin" => false]);
        $breadcrumb = $this->breadcrumbService->createUserManagementBreadcrumb();


        return $this->render('admin/user/user_management.html.twig', [
            'users' => $allusers,
            'breadcrumb' => $breadcrumb,

        ]);
    }


//    kullanıcı ekleme
    #[Route('/admin/user/add', name: 'admin_user_add')]
    public function user_add(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
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
            $role = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_USER]);
            $newuser->addRoles($role);
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
    #[Route('/admin/user/passive/{id}', name: 'admin_user_pasive')]
    public function user_passive_func($id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        $user->setIsActive(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->addFlash('success', 'Kullanıcı Pasifleştirme İşlemi Başarılı');

        return $this->redirectToRoute('admin_user_management');

    }

//    kullanıcı aktifleştirme Func
    #[Route('/admin/user/active/{id}', name: 'admin_user_active')]
    public function user_active_func($id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        $user->setIsActive(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->addFlash('success', 'Kullanıcı Etkin Hale Getirilmiştir.');

        return $this->redirectToRoute('admin_user_management');
    }

    // kullanıcıya dergi atama işlemi
    #[Route('/admin/assigment/journal', name: 'admin_assigment_journal')]
    public function User_Journal_Assign(Request $request): Response
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


    #[Route('/admin/assigned/{id}', name: 'admin_assigned_journal_list')]
    public function admin_assigned_journal($id)
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

    #[Route("/search_user", name: "search_user")]
    public function searchusers(Request $request)
    {
        $searchTerm = $request->query->get('term');

        $users = $this->entityManager->getRepository(User::class)->searchByName($searchTerm);

        $userNames = [];
        foreach ($users as $user) {
            $userNames[] = $user->getEmail();
        }

        return new JsonResponse($userNames);
    }

    #[Route('/admin/assigned/{id}/{role}', name: 'admin_assigned_journal_delete')]
    public function admin_assigned_journal_delete_func($id, $role)
    {
        $journalUser = $this->entityManager->getRepository(JournalUser::class)->find($id);
        $role = $this->entityManager->getRepository(Role::class)->find($role);

        $user = $journalUser->getPerson();
        $journalUser->removeRole($role);

        $journalUserRoles = $journalUser->getRole();
        if (count($journalUserRoles) === 0) {
            $this->entityManager->remove($journalUser);
        }

        $otherJournalUsers = $this->entityManager->getRepository(JournalUser::class)->findBy([
            'person' => $user,
        ]);
        $rolesIds = [];

        /** @var JournalUser $journalUser */
        foreach ($otherJournalUsers as $journalUser) {
            if (count($journalUser->getRole()) > 0) {
                foreach ($journalUser->getRole() as $role) {
                    $rolesIds[] = $role->getId();
                }
            }
        }
        if (!in_array($rolesIds, (array)($role->getId()))) {
            $user->removeRole($role);
        }
        $this->entityManager->flush();


        $this->addFlash('success', 'Kullanıcının Dergideki Rolü Kaldırılmıştır.');
        return $this->redirectToRoute('admin_assigned_journal_list', ['id' => $user->getId()]);
    }

//-----------------------------------------------------------------
    //DERGİ İŞLEMLERİ

//Dergi paneli
    #[Route('/admin/journal-management', name: 'admin_journal_management')]
    public function journal_management(): Response
    {

        $all_journal = $this->entityManager->getRepository(Journal::class)->findAll();
        $breadcrumb = $this->breadcrumbService->createJournalManagementBreadcrumb();
        return $this->render('admin/journal/journal_management.html.twig', [
            'journals' => $all_journal,
            'breadcrumb' => $breadcrumb,
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
    #[Route('/admin/journal/add', name: 'admin_journal_add')]
    public function journal_add(Request $request): Response
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
        return $this->render('admin/journal/new-journal-add.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,

        ]);

    }


//    Dergi Silme Func
    #[Route('/admin/journal/delete/{id}', name: 'admin_journal_delete')]
    public function journal_delete_func($id): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);

        if (!$journal) {
            $this->addFlash('danger', 'Dergi Bulunamadı.');
            return $this->redirectToRoute('admin_journal_management');
        }

        $this->entityManager->remove($journal);
        $this->entityManager->flush();
        $this->addFlash('success', 'Dergi Silme İşlemi Başarılı');

        return $this->redirectToRoute('admin_journal_management');
    }


//    Dergi Düzenleme
    #[Route('/admin/journal/edit/{id}', name: 'admin_journal_edit')]
    public function journal_edit($id, Request $request): Response
    {
        $breadcrumb = $this->breadcrumbService->createJournalEditBreadcrumb();

        $journal = $this->entityManager->getRepository(Journal::class)->find($id);
        if (!$journal) {
            $this->addFlash('danger', 'Dergi Bulunamadı.');
            return $this->redirectToRoute('admin_journal_management');
        }
        $form = $this->createForm(JournalFormType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Dergi bilgileri güncellendi.');

            return $this->redirectToRoute('admin_journal_edit', ['id' => $id]);
        }


        return $this->render('admin/journal/journal-edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,

        ]);
    }




}






