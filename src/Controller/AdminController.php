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
use App\Service\BreadCrumbService;
use App\Util\TypeModifier;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
    public function userManagement(): Response
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

    #[Route('/admin/assigned/{id}/{role}', name: 'admin_assigned_journal_delete')]
    public function adminAssignedJournalDeleteFunc($id, $role)
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
    public function journalManagement(): Response
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


//    Dergi Düzenleme
    #[Route('/admin/journal/edit/{id}', name: 'admin_journal_edit')]
    public function journalEdit($id, Request $request): Response
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


        return $this->render('admin/journal/journal_add_edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'name' => 'Dergi Düzenleme',
            'button' => 'Düzenlemeyi Kaydet'
        ]);
    }

    #[Route('journal/{id}/export', name: 'journal_export')]
    public function issueExport($id): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);
        $issues = $this->entityManager->getRepository(Issues::class)->findBy(['journal' => $journal, 'status' => IssueStatusParam::EDITED]);
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');
        $xmlDoc->formatOutput = true;
        $issueNode = $xmlDoc->createElement('journal');
        $journalName = $journal->getName();

        foreach ($issues as $issue) {
            $articles = $issue->getArticles();

            $articlesNode = $xmlDoc->createElement('issue');

            foreach ($articles as $article) {
//            $articlesNode->appendChild($articleNode);

                $articleType = $article->getType();
                $doi = $article->getDoi();
                $fPage = $article->getFirstPage();
                $lPage = $article->getLastPage();
                $primaryLang = $article->getPrimaryLanguage();
                $typeModifier = new TypeModifier();

                // <article> öğesini oluştur
                $articleNode = $xmlDoc->createElement('article');
//                $articleNode->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
//                $articleNode->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
//                $articleNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
                $articleNode->setAttribute('article-type', $articleType);
                $articleNode->setAttribute('dtd-version', '1.0');

                $frontNode = $xmlDoc->createElement('front');
                $journalMetaNode = $xmlDoc->createElement('journal-meta');

                $journalTitleGroupNode = $xmlDoc->createElement('journal-title-group');
                $journalMetaNode->appendChild($journalTitleGroupNode);

//issn
                $journalIssnNode = $xmlDoc->createElement('issn', $journal->getIssn());
                $journalIssnNode->setAttribute('pub-type', 'ppub');
                $journalMetaNode->appendChild($journalIssnNode);

// eissn
                $journalEissnNode = $xmlDoc->createElement('issn', $journal->getEIssn());
                $journalEissnNode->setAttribute('pub-type', 'epub');
                $journalMetaNode->appendChild($journalEissnNode);

//dergi adı
                $journalTitleNode = $xmlDoc->createElement('journal-title', $journalName);
                $journalTitleGroupNode->appendChild($journalTitleNode);

//journal meta buraya kadar

                $articleMetaNode = $xmlDoc->createElement('article-meta');
                $articleIdNode = $xmlDoc->createElement('article-id', $doi);
                $articleIdNode->setAttribute('pub-id-type', 'doi');
                $articleMetaNode->appendChild($articleIdNode);

                $articleTitleGroupNode = $xmlDoc->createElement('title-group');

                $translations = $article->getTranslations();

                foreach ($translations as $translation) {
                    // Ana dil ile aynı olan çeviriyi doğrudan <article-title> içine ekleyin
                    if ($primaryLang == $translation->getLocale()) {
                        $articleTitleNode = $xmlDoc->createElement('article-title', $translation->getTitle());
                        $articleTitleGroupNode->appendChild($articleTitleNode);
                    }
                }

                $hasTranslations = $translations->count() > 1;

// Ana dil ile aynı olmayan çevirileri <trans-title-group> içine ekleyin
                if ($hasTranslations) {
                    foreach ($translations as $translation) {
                        if ($primaryLang != $translation->getLocale()) { // Ana dil ile aynı olmayan çeviriler

                            $articleTransTitleGroupNode = $xmlDoc->createElement('trans-title-group');
                            $articleTransTitleGroupNode->setAttribute('xml:lang', $typeModifier->convertLanguageCode($translation->getLocale()));
                            $articleTransTitleNode = $xmlDoc->createElement('trans-title', $translation->getTitle());
                            $articleTransTitleGroupNode->appendChild($articleTransTitleNode);
                        }
                    }

                    $articleTitleGroupNode->appendChild($articleTransTitleGroupNode);
                }

                $articleMetaNode->appendChild($articleTitleGroupNode);


                //yazar sekmesi
                $contribGroupNode = $xmlDoc->createElement('contrib-group');
                $authors = $article->getAuthors();
                foreach ($authors as $author) {
                    $contribNode = $xmlDoc->createElement('contrib');
                    $contribNode->setAttribute('contrib-type', 'author');
//isim soyad
                    $nameNode = $xmlDoc->createElement('name');
                    $surnameNode = $xmlDoc->createElement('surname', $author->getLastname());
                    $nameNode->appendChild($surnameNode);
                    $givenNameNode = $xmlDoc->createElement('given-names', $author->getFirstname());
                    $nameNode->appendChild($givenNameNode);
                    $contribNode->appendChild($nameNode);
//institute
                    $affNode = $xmlDoc->createElement('aff', $author->getInstitute());

                    $contribNode->appendChild($affNode);

                    $emailNode = $xmlDoc->createElement('email', $author->getEmail());
                    $contribNode->appendChild($emailNode);
//orcid
                    $contribIdNode = $xmlDoc->createElement('contrib-id', 'https://orcid.org/' . $author->getOrcId());
                    $contribIdNode->setAttribute('contrib-id-type', 'orcid');
                    $contribNode->appendChild($contribIdNode);

                    $contribGroupNode->appendChild($contribNode);
                }
                //çevirmen sekmesi
                $translators = $article->getTranslators();
                foreach ($translators as $translator) {
                    $contribNode = $xmlDoc->createElement('contrib');
                    $contribNode->setAttribute('contrib-type', 'translator');
//isim soyad
                    $nameNode = $xmlDoc->createElement('name');
                    $surnameNode = $xmlDoc->createElement('surname', $translator->getLastname());
                    $nameNode->appendChild($surnameNode);
                    $givenNameNode = $xmlDoc->createElement('given-names', $translator->getFirstname());
                    $nameNode->appendChild($givenNameNode);
                    $contribNode->appendChild($nameNode);

//institute
                    $affNode = $xmlDoc->createElement('aff', $translator->getInstitute());
                    $contribNode->appendChild($affNode);
//orcid
                    $contribIdNode = $xmlDoc->createElement('contrib-id', 'https://orcid.org/' . $translator->getOrcId());
                    $contribIdNode->setAttribute('contrib-id-type', 'orcid');
                    $contribNode->appendChild($contribIdNode);

                    $contribGroupNode->appendChild($contribNode);
                }
                $articleMetaNode->appendChild($contribGroupNode);
//pubdate
                if ($article->getReceivedDate() && $article->getAcceptedDate()) {
                    $receivedDate = $article->getReceivedDate()->format('Y-m-d');
                    $historyNode = $xmlDoc->createElement('history');

                    // Received Date Node
                    $dateReceivedNode = $xmlDoc->createElement('date');
                    $dateReceivedNode->setAttribute('date-type', 'received');
                    $dateReceivedNode->setAttribute('iso-8601-date', $receivedDate);

                    // Day Node
                    $dayReceivedNode = $xmlDoc->createElement('day', $article->getReceivedDate()->format('d'));
                    $dateReceivedNode->appendChild($dayReceivedNode);

                    // Month Node
                    $monthReceivedNode = $xmlDoc->createElement('month', $article->getReceivedDate()->format('m'));
                    $dateReceivedNode->appendChild($monthReceivedNode);

                    // Year Node
                    $yearReceivedNode = $xmlDoc->createElement('year', $article->getReceivedDate()->format('Y'));
                    $dateReceivedNode->appendChild($yearReceivedNode);

                    // Append Received Date Node to history node
                    $historyNode->appendChild($dateReceivedNode);

                    // Accepted Date Node
                    $acceptedDate = $article->getAcceptedDate()->format('Y-m-d');
                    $dateAcceptedNode = $xmlDoc->createElement('date');
                    $dateAcceptedNode->setAttribute('date-type', 'accepted');
                    $dateAcceptedNode->setAttribute('iso-8601-date', $acceptedDate);

                    // Day Node
                    $dayAcceptedNode = $xmlDoc->createElement('day', $article->getAcceptedDate()->format('d'));
                    $dateAcceptedNode->appendChild($dayAcceptedNode);

                    // Month Node
                    $monthAcceptedNode = $xmlDoc->createElement('month', $article->getAcceptedDate()->format('m'));
                    $dateAcceptedNode->appendChild($monthAcceptedNode);

                    // Year Node
                    $yearAcceptedNode = $xmlDoc->createElement('year', $article->getAcceptedDate()->format('Y'));
                    $dateAcceptedNode->appendChild($yearAcceptedNode);

                    // Append Accepted Date Node to history node
                    $historyNode->appendChild($dateAcceptedNode);

                    // Append history node to appropriate parent node (like $articleMetaNode)
                    $articleMetaNode->appendChild($historyNode);
                }


                //volume
                $volumeNode = $xmlDoc->createElement('volume', $issue->getVolume());
                $articleMetaNode->appendChild($volumeNode);
//sayı
                $numberNode = $xmlDoc->createElement('issue', $issue->getNumber());
                $articleMetaNode->appendChild($numberNode);
//birinci sayfa
                $fpageNode = $xmlDoc->createElement('fpage', $fPage);
                $articleMetaNode->appendChild($fpageNode);
//ikinci sayfa
                $lpageNode = $xmlDoc->createElement('lpage', $lPage);
                $articleMetaNode->appendChild($lpageNode);

//abstract
                foreach ($translations as $translation) {
                    if ($article->getPrimaryLanguage() == $translation->getLocale()) {
                        $abstractNode = $xmlDoc->createElement('abstract');
                        $pNode = $xmlDoc->createElement('p', htmlspecialchars($translation->getAbstract(), ENT_XML1 | ENT_COMPAT, 'UTF-8'));
                        $abstractNode->appendChild($pNode);
                        $articleMetaNode->appendChild($abstractNode);
                    } else {
                        $transAbstractNode = $xmlDoc->createElement('trans-abstract');
                        $transAbstractNode->setAttribute('xml:lang', $typeModifier->convertLanguageCode($translation->getLocale()));
                        $pTransNode = $xmlDoc->createElement('p', htmlspecialchars($translation->getAbstract(), ENT_XML1 | ENT_COMPAT, 'UTF-8'));

//                    $pTransNode = $xmlDoc->createElement('p', $translation->getAbstract());
                        $transAbstractNode->appendChild($pTransNode);
                        $articleMetaNode->appendChild($transAbstractNode);
                    }
                }
                foreach ($translations as $translation) {
                    $kwdGroupNode = $xmlDoc->createElement('kwd-group');
                    $kwdGroupNode->setAttribute('xml:lang', $typeModifier->convertLanguageCode($translation->getLocale()));
                    foreach ($translation->getKeywords() as $keyword) {
                        $kwdNode = $xmlDoc->createElement('kwd', $keyword);
                        $kwdGroupNode->appendChild($kwdNode);
                    }
                    $articleMetaNode->appendChild($kwdGroupNode);
                }

                $frontNode->appendChild($articleMetaNode);

                $articleNode->appendChild($frontNode);

                $back = $xmlDoc->createElement('back');
                $refListNode = $xmlDoc->createElement('ref-list');
                foreach ($article->getCitations() as $citation) {
                    $refNode = $xmlDoc->createElement('ref');
                    $refNode->setAttribute('id', 'ref' . $citation->getRow());

                    $label = $xmlDoc->createElement('label', $citation->getRow());
                    $refNode->appendChild($label);
                    $mixedCitationNode = $xmlDoc->createElement('mixed-citation', htmlspecialchars($citation->getReferance(), ENT_XML1));
                    $refNode->appendChild($mixedCitationNode);

                    $refListNode->appendChild($refNode);

                }
                $back->appendChild($refListNode);
                $articleNode->appendChild($back);
                $articlesNode->appendChild($articleNode);
            }
            $issueNode->appendChild($articlesNode);
// <articles> kök öğesini XML dokümanına ekle
            $xmlDoc->appendChild($issueNode);
        }
// XML içeriğini bir değişkene atayın
        $xmlContent = $xmlDoc->saveXML();
        $fileName = 'exported_issues.xml';
        $file = fopen($fileName, 'w');
        fwrite($file, $xmlContent);
        fclose($file);

// Dosyayı indirme olarak kullanıcıya sun
        $response = new Response(file_get_contents($fileName));
        $response->headers->set('Content-Type', 'application/xml');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->sendHeaders();

        return $response;
    }


}






