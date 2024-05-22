<?php

namespace App\Controller;

use App\Entity\Articles;
use App\Entity\Citations;
use App\Entity\Issues;
use App\Entity\Journal;
use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\IssuesXmlFormType;
use App\Form\JournalFormType;
use App\Params\ArticleLanguageParam;
use App\Repository\JournalUserRepository;
use App\Util\TypeModifier;

use App\Entity\Translations;
use App\Form\ArticleFormType;
use App\Form\ArticleFulltextAddFormType;
use App\Form\IssuesFormType;
use App\Form\IssuesEditFormType;

use App\Params\ArticleStatusParam;
use App\Params\ArticleTypeParam;
use App\Params\IssueStatusParam;
use App\Params\RoleParam;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMElement;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BreadCrumbService;


class IssueArticleEditController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BreadCrumbService $breadcrumbService;
    private $security;
    private $journalUserRepository;

    public function __construct(EntityManagerInterface $entityManager, BreadCrumbService $breadcrumbService, Security $security, JournalUserRepository $journalUserRepository,)
    {
        $this->entityManager = $entityManager;
        $this->breadcrumbService = $breadcrumbService;
        $this->security = $security;
        $this->journalUserRepository = $journalUserRepository;
    }


    //    Dergi Düzenleme
    #[Route('/{role}/journal/edit/{id}', name: 'admin_journal_edit')]
    public function journalEdit($id, Request $request, $role, Security $security,): Response
    {
        $user = $security->getUser();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getUserIdentifier()]);
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);
        if (!$journal) {
            $this->addFlash('danger', 'Dergi Bulunamadı.');
            return $this->redirectToRoute('admin_journal_management');
        }
        if ($role == 'admin') {
            $breadcrumb = $this->breadcrumbService->createJournalEditBreadcrumb();
        } else {
            $breadcrumb = $this->breadcrumbService->createEditorJournalEditBreadcrumb();
        }

        $hasEditorRole = $this->journalUserRepository->userRoleInJournal($user, $journal, RoleParam::ROLE_EDITOR);

        if ($hasEditorRole == false && !$user->isIsAdmin()) {
            throw $this->createNotFoundException('Giriş Yetkiniz Yok.');
        }

        $form = $this->createForm(JournalFormType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Dergi bilgileri güncellendi.');
            if ($role == 'admin') {
                return $this->redirectToRoute('admin_journal_management');
            } else {
                return $this->redirectToRoute('editor_journal_management');

            }
        }


        return $this->render('admin/journal/journal_add_edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'name' => 'Dergi Düzenleme',
            'button' => 'Düzenlemeyi Kaydet'
        ]);
    }

//dergi xml çıktı
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


// sayı listesi
    #[Route('/journal/{id}/issues', name: 'journal_issues')]
    public function journalIssues($id, FactoryInterface $factory, Security $security,): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);
        $user = $this->security->getUser();
        if (!$journal) {
            $this->addFlash('danger', 'Dergi Bulunamadı.');
            if (in_array($this->getUser()->getRoles(), (array)RoleParam::ROLE_ADMIN)) {
                return $this->redirectToRoute('admin_journal_management');
            } else {
                return $this->redirectToRoute('app_homepage');
            }
        }
        $breadcrumb = $this->breadcrumbService->createJournalIssueBreadcrumb($factory, $journal->getName());
        $issues = $this->entityManager->getRepository(Issues::class)->findBy([
            'journal' => $journal
        ]);

        return $this->render('journal_issues.html.twig', [
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
            'issues' => $issues,
            'user' => $user,
            'role' => 'admin'

        ]);
    }

// sayı ekleme
    #[Route('/{role}/journal/{id}/issue/add', name: 'journal_issue_add')]
    public function issueAdd($id, Request $request, FactoryInterface $factory, $role): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);
        $journalname = $journal->getName();
        if ($role == 'admin') {
            $breadcrumb = $this->breadcrumbService->createIssueAddBreadcrumb($factory, $journalname, $id);
        } else {
            $breadcrumb = $this->breadcrumbService->createEditorIssueAddBreadcrumb($factory, $journalname, $id);
        }
        $newissue = new Issues();
        $newissue->setJournal($journal);
        $this->entityManager->persist($newissue);
        $form = $this->createForm(IssuesFormType::class, $newissue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($newissue);

            $journalId = $journal->getId();
            $issueId = $newissue->getId();

            $xmlFile = $form->get('xml')->getData();

            if ($xmlFile) {
//                $baseDirectory = $this->getParameter('kernel.project_dir') . '/var/journal/' . $journal->getId();
                $baseDirectory = '/usr/share/nginx/data/journal/' . $journal->getId();


                if (!file_exists($baseDirectory)) {
                    mkdir($baseDirectory, 0777, true);
                }
                $newissue->setStatus(IssueStatusParam::WAITING);

                $xmlFileName = $this->generateHashedFileName($xmlFile, $journalId, $issueId);
//                $xmlPath = $baseDirectory . '/' . $xmlFileName;
                try {
                    $xmlFile->move($baseDirectory, $xmlFileName);
//                    $newissue->setXml('var/journal/' . $journal->getId() . '/' . $xmlFileName);
                    $newissue->setXml('/usr/share/nginx/data/journal/' . $journal->getId() . '/' . $xmlFileName);
//		      $newissue->setXml('/data/journal/' . $journal->getId() . '/' . $xmlFileName);
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }
            } else {
                $newissue->setStatus(IssueStatusParam::EDIT_REQUIRED);
            }
            $this->entityManager->persist($newissue);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Yeni Sayı Oluşturulmuştur.'
            );
            if ($role == 'admin'){
                return $this->redirectToRoute('journal_issues', ['id' => $id]);
            }else{
                return $this->redirectToRoute('editor_journal_issues', ['id' => $id]);

            }
        }
        return $this->render('journal_issue_add.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
            'name' => $journalname . ' İçin Yeni Sayı',
            'button' => 'Yeni Sayı Ekle ',

        ]);
    }

//sayı düzenleme
    #[Route('/{role}/journal/{id}/issue/edit', name: 'journal_issue_edit')]
    public function issueEdit($id, Request $request, FactoryInterface $factory, $role): Response
    {
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();
        $journalname = $journal->getName();
        $journalId = $journal->getId();
        if ($role == 'admin') {
            $breadcrumb = $this->breadcrumbService->createIssueEditBreadcrumb($factory, $journalname, $journalId);

        } else {
            $breadcrumb = $this->breadcrumbService->createEditorIssueEditBreadcrumb($factory, $journalname, $journalId);
        }

        $form = $this->createForm(IssuesEditFormType::class, $issue);
        $form->handleRequest($request);
        $name = $journal->getName() . ' ' . $issue->getNumber() . '. Sayısı Düzenleme';

        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($issue);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Sayı Düzenlenmiştir.'
            );
            if ($role == 'admin') {
                return $this->redirectToRoute('journal_issues', ['id' => $journalId]);
            }else{
                return $this->redirectToRoute('editor_journal_issues', ['id' => $journalId]);

            }
        }
        return $this->render('journal_issue_edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
            'name' => $name,
            'button' => 'Düzenle'
        ]);
    }

    //xml değiştirme
    #[Route('/{role}/journal/{id}/issue/xml/edit', name: 'journal_issue_xml_edit')]
    public function issueXmlEdit($id, Request $request, FactoryInterface $factory, $role): Response
    {
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();
        $journalname = $journal->getName();
        $journalId = $journal->getId();
        $issueId = $issue->getId();
        if ($role == 'admin') {
            $breadcrumb = $this->breadcrumbService->createIssueEditBreadcrumb($factory, $journalname, $journalId);

        } else {
            $breadcrumb = $this->breadcrumbService->createEditorIssueEditBreadcrumb($factory, $journalname, $journalId);
        }
        $form = $this->createForm(IssuesXmlFormType::class);
        $form->handleRequest($request);
        $name = $journal->getName() . ' ' . $issue->getNumber() . '. Sayısı Xml Düzenleme';

        if ($form->isSubmitted() && $form->isValid()) {
            $xmlFile = $form->get('xml')->getData();

            if ($issue->getStatus() === IssueStatusParam::EDIT_REQUIRED && $issue->getStatus() === IssueStatusParam::EDITED) {
                $this->addFlash('danger', 'Bu sayının aktarımı gerçekleşmiş.');
                return $this->redirectToRoute('journal_issues', ['id' => $journalId]);
            }
            $existingXmlPath = $issue->getXml();
            if ($existingXmlPath && file_exists($existingXmlPath)) {
                unlink($existingXmlPath);
            }
            if ($xmlFile) {
//                $baseDirectory = $this->getParameter('kernel.project_dir') . '/var/journal/' . $journal->getId();
                $baseDirectory = '/usr/share/nginx/data/journal/' . $journal->getId();

                if (!file_exists($baseDirectory)) {
                    mkdir($baseDirectory, 0777, true);
                }

                $xmlFileName = $this->generateHashedFileName($xmlFile, $journalId, $issueId);
                try {
                    $xmlFile->move($baseDirectory, $xmlFileName);
//                    $issue->setXml('var/journal/' . $journal->getId() . '/' . $xmlFileName);
                    $issue->setXml('/usr/share/nginx/data/journal/' . $journal->getId() . '/' . $xmlFileName);

                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Lütfen bir XML dosyası seçin.');
                return $this->redirectToRoute('journal_issue_xml_edit', ['id' => $id]);
            }

            $issue->setStatus(IssueStatusParam::WAITING);

            $this->entityManager->persist($issue);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Xml Dosyası Yenilenmiştir.'
            );

            if ($role == 'admin') {
                return $this->redirectToRoute('journal_issues', ['id' => $journalId]);
            }else{
                return $this->redirectToRoute('editor_journal_issues', ['id' => $journalId]);

            }
        }
        return $this->render('journal_issue_xml_edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
            'name' => $name,
            'button' => 'Değiştir'
        ]);
    }

    //sayı silme
    #[Route('/{role}/journal/issue/{id}/delete', name: 'journal_issue_delete')]
    public function deleteIssue($id, $role): Response
    {
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);

        if (!$issue) {
            $this->addFlash('danger', 'Sayı bulunamadı.');
            return $this->redirectToRoute('journal_issues');
        }

        $journalId = $issue->getJournal()->getId();
        $xmlFilePath = $issue->getXml();

        if ($xmlFilePath && file_exists($xmlFilePath)) {
            unlink($xmlFilePath);
        }

        $articles = $issue->getArticles();
        if ($articles) {
            foreach ($articles as $article) {
                // Article'a bağlı tüm translationları al
                $translations = $article->getTranslations();
                $authors = $article->getAuthors();
                $translators = $article->getTranslators();
                $citations = $article->getCitations();

                $pdfFilePath = $article->getFulltext();

                if ($pdfFilePath && file_exists($pdfFilePath)) {
                    unlink($pdfFilePath);
                }
                $pdfFolderPath = substr($pdfFilePath, 0, strrpos($pdfFilePath, '/'));

                // Klasörü kontrol et ve eğer boşsa sil
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

                // Article'ı sil
                $this->entityManager->remove($article);
            }
        }
        // Issues'ı sil
        $this->entityManager->remove($issue);
        //$this->entityManager->persist();
        $this->entityManager->flush();

        $this->addFlash('success', 'Sayı ve bağlı makaleler başarıyla silindi.');
        if ($role == 'admin') {
            return $this->redirectToRoute('journal_issues', ['id' => $journalId]);

        } else {
            return $this->redirectToRoute('editor_journal_issues', ['id' => $journalId]);
        }
    }


// sayı dışa aktarımı
    #[Route('journal/issue/{id}/export', name: 'issue_export')]
    public function journalExport($id,): Response
    {
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();
        $articles = $issue->getArticles();
        $journalName = $journal->getName();
        $journalIssn = $journal->getIssn();
        $journalEissn = $journal->getEissn();
//        $publisher = $journal->getPublisher();
        // XML dom belgesi oluştur
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');
        $xmlDoc->formatOutput = true;

// <articles> kök öğesini oluştur
        $articlesNode = $xmlDoc->createElement('articles');

// Her bir makale için döngü oluştur
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
//            $articleNode->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
//            $articleNode->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
//            $articleNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
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
// <articles> kök öğesini XML dokümanına ekle
        $xmlDoc->appendChild($articlesNode);

// XML içeriğini bir değişkene atayın
        $xmlContent = $xmlDoc->saveXML();
        $fileName = 'exported_articles.xml';
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

// makale liste
    #[Route('journal/issue/{id}/articles', name: 'articles_list')]
    public function articleList($id, FactoryInterface $factory, Security $security): Response
    {
        $user = $this->security->getUser();
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();
        $breadcrumb = $this->breadcrumbService->createArticleListBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $journal->getId());
        if (!$journal && !$issue) {
            $this->addFlash('danger', 'Dergi ya da sayı Bulunamadı.');
            if (in_array($this->getUser()->getRoles(), (array)RoleParam::ROLE_ADMIN)) {
                return $this->redirectToRoute('journal_issues', ['id' => $journal->getId()]);
            } else {
                return $this->redirectToRoute('app_homepage');
            }
        }
        $articles = $this->entityManager->getRepository(Articles::class)->findBy([
            'issue' => $issue
        ]);

        return $this->render('articles_list.html.twig', [
            'breadcrumb' => $breadcrumb,
            'articles' => $articles,
            'issues' => $issue,
            'journal' => $journal,
            'user' => $user,
            'role' => 'admin'

        ]);
    }

    //makale export
    #[Route('/article/{id}/export', name: 'article_export')]
    public function articleExport($id,): Response
    {
        $article = $this->entityManager->getRepository(Articles::class)->find($id);

        $issue = $article->getIssue();
        $journal = $issue->getJournal();
        $articles = $issue->getArticles();
        $journalName = $journal->getName();

        // XML dom belgesi oluştur
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');
        $xmlDoc->formatOutput = true;

// <articles> kök öğesini oluştur
        $articlesNode = $xmlDoc->createElement('article');

// Her bir makale için döngü oluştur
        $articleType = $article->getType();
        $doi = $article->getDoi();
        $fPage = $article->getFirstPage();
        $lPage = $article->getLastPage();
        $primaryLang = $article->getPrimaryLanguage();
        $typeModifier = new TypeModifier();

        // <article> öğesini oluştur
        $articleNode = $xmlDoc->createElement('article');
//            $articleNode->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
//            $articleNode->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
//            $articleNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $articlesNode->setAttribute('article-type', $articleType);
        $articlesNode->setAttribute('dtd-version', '1.0');

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
            if ($author->getOrcId()) {
                $contribIdNode = $xmlDoc->createElement('contrib-id', 'https://orcid.org/' . $author->getOrcId());
                $contribIdNode->setAttribute('contrib-id-type', 'orcid');
                $contribNode->appendChild($contribIdNode);
            }
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
            if ($translator->getOrcId()) {
                $contribIdNode = $xmlDoc->createElement('contrib-id', 'https://orcid.org/' . $translator->getOrcId());
                $contribIdNode->setAttribute('contrib-id-type', 'orcid');
                $contribNode->appendChild($contribIdNode);
            }
            $contribGroupNode->appendChild($contribNode);
        }
        $articleMetaNode->appendChild($contribGroupNode);
//pubdate
        if ($article->getReceivedDate() && $article->getAcceptedDate()) {
            $receivedDate = $article->getReceivedDate()->format('Ymd');
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
            $acceptedDate = $article->getAcceptedDate()->format('Ymd');
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

        $articlesNode->appendChild($frontNode);

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
        $articlesNode->appendChild($back);

// <articles> kök öğesini XML dokümanına ekle
        $xmlDoc->appendChild($articlesNode);

// XML içeriğini bir değişkene atayın
        $xmlContent = $xmlDoc->saveXML();
//        $journalSlug = $this->convertToSlug($journal->getName());
//        $articleSlug = $this->convertToSlug($article->getTranslations()->findFirst());
        $fileName = 'exported_article.xml';
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

    //makale düzenleme
    #[Route('/{role}/article/edit/{id}', name: 'article_edit')]
    public function article_edit($id, $role, Request $request, FactoryInterface $factory, Security $security): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $article = $this->entityManager->getRepository(Articles::class)->find($id);
        $issue = $article->getIssue();
        $journal = $article->getJournal();
        if (!$article) {
            throw $this->createNotFoundException('Makale bulunamadı: ' . $id);
        }
        if (!$journal && !$issue && !$article) {
            $this->addFlash('danger', 'Dergi, sayı veya makale hatalı.');
            return $this->redirectToRoute('admin_journal_management');
        }
        if ($role == 'operator') {
            $breadcrumb = $this->breadcrumbService->createOperatorArticleEditBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId());
        } elseif ($role == 'editor') {
            $breadcrumb = $this->breadcrumbService->createEditorArticleEditBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId());
        } elseif ($role == 'admin') {
            $breadcrumb = $this->breadcrumbService->createArticleEditBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId());
        } else {
            throw $this->createNotFoundException('rol bulunamadı');
        }
        $path = 'var' . '/' . 'journal' . '/' . $journal->getId() . '/' . $issue->getId();
        $pdfFileName = trim($article->getFulltext(), $path);
        $pdfFileName = $article->getFulltext();

        $filePath = '/usr/share/nginx/data/';
        $pdfFileName = str_replace($filePath, '', $pdfFileName);

        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);
        /** @var Articles $data */
        $data = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {

            /**  bu kısımda veritabanındaki üstverinin formda olup olmadığını kontrol ediyoruz eğer yok ise siliyoruz **/
            $existingTranslations = $article->getTranslations();
            foreach ($existingTranslations as $existingTranslation) {
                $this->entityManager->remove($existingTranslation);
            }

            foreach ($data->getTranslations() as $newTranslation) {
                if (!empty($newTranslation->getTitle())) {

                    $title = html_entity_decode($newTranslation->getTitle(), ENT_QUOTES | ENT_HTML5);
                    $abstract = html_entity_decode($newTranslation->getAbstract(), ENT_QUOTES | ENT_HTML5);

                    $title = str_replace(["                    ", "\r", "&#13;", "\n", '&#13;'], ' ', $title);
                    $abstract = str_replace(["                    ", "\r", "&#13;", "\n", '&#13;'], ' ', $abstract);
                    $title = str_replace('&rsquo;', "'", $title);
                    $abstract = str_replace('&rsquo;', "'", $abstract);
                    $newTranslation->setTitle($title);
                    $newTranslation->setAbstract($abstract);

                    $newTranslation->setArticle($article);
                    $this->entityManager->persist($newTranslation);
                }
            }

            foreach ($data->getAuthors() as $newAuthor) {
                $this->entityManager->persist($newAuthor);
            }

            /**  bu kısımda veritabanındaki yazarın formda olup olmadığını kontrol ediyoruz eğer yok ise siliyoruz **/
            $existingAuthors = $article->getAuthors();
            foreach ($existingAuthors as $existingAuthor) {
                if (!$data->getAuthors()->contains($existingAuthor) && empty($existingAuthor->getAuthorName())) {
                    $this->entityManager->remove($existingAuthor);
                }
            }

            if ($data->getType() == ArticleTypeParam::TRANSLATE) {
                foreach ($data->getTranslators() as $translator) {
                    $this->entityManager->persist($translator);
                }
            } else {
                foreach ($article->getTranslators() as $translator) {
                    $this->entityManager->remove($translator);
                }
            }

            $citations = $data->getCitations();

            foreach ($article->getCitations() as $existingCitation) {
                if (!$citations->contains($existingCitation)) {
                    $this->entityManager->remove($existingCitation);
                }
            }
            $index = 1;
            foreach ($citations as $newCitation) {
                if (!empty($newCitation->getReferance())) {
                    $newCitation->setArticle($article);
                    $referance = str_replace(["                    ", "&#13;", "\r", "\n", '&#13;'], '', $newCitation->getReferance());
                    $referance = str_replace('&rsquo;', "'", $referance);

                    $newCitation->setReferance($referance);

                    $newCitation->setRow($index);
                    $index++;
                    if (!$newCitation->getId()) {
                        $this->entityManager->persist($newCitation);
                    }
                }
            }

            $this->entityManager->flush();
            $citat = $this->entityManager->getRepository(Citations::class)->findBy(['article' => null]);
            foreach ($citat as $value) {
                $this->entityManager->remove($value);
            }

            $article->setEditor($user);
            $utc_now = new \DateTime('now', new \DateTimeZone('UTC'));

            $now = $utc_now->setTimezone(new \DateTimeZone('Europe/Istanbul'));
            $article->setModificationDate($now);

            $issue->setStatus(IssueStatusParam::EDIT_REQUIRED);
            $article->setStatus(ArticleStatusParam::EDITED);
            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $articleEdited = $this->entityManager->getRepository(Articles::class)->findBy([
                'issue' => $issue,
                'status' => ArticleStatusParam::EDITED,
            ]);
            $articleCount = $this->entityManager->getRepository(Articles::class)->findBy([
                'issue' => $issue,
            ]);
            if (count($articleCount) > 0 && count($articleCount) === count($articleEdited)) {
                $issue->setStatus(IssueStatusParam::EDITED);
                $this->entityManager->persist($issue);
                $this->entityManager->flush();
                $this->addFlash('success', 'Tüm Makaleler Güncellendi.');
                if ($role == 'admin') {
                    return $this->redirectToRoute('journal_issues', ['id' => $journal->getId()]);
                } elseif ($role == 'editor') {
                    return $this->redirectToRoute('editor_journal_issues', ['id' => $journal->getId()]);
                } else {
                    return $this->redirectToRoute('operator_journal_issues', ['id' => $journal->getId()]);
                }
            }
            $this->entityManager->persist($issue);
            $this->entityManager->flush();
            $this->addFlash('success', 'Makale bilgileri güncellendi.');

            if ($request->request->has('save_and_skip')) {

                return $this->redirectToRoute('article_save_skip', ['id' => $article->getId(), 'role' => $role]);
            }
            if ($role == 'admin') {
                return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
            } elseif ($role == 'editor') {
                return $this->redirectToRoute('editor_articles_list', ['id' => $issue->getId()]);
            } else {
                return $this->redirectToRoute('operator_articles_list', ['id' => $issue->getId()]);

            }
        }
        return $this->render('article_edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'pdfFile' => $pdfFileName,
            'article' => $article,
            'role' => $role,
            'user' => $user,
        ]);
    }


//sonraki makaleye geçmek için
    #[Route('/article/save-skip/{id}/{role}', name: 'article_save_skip')]
    public function articleSaveSkip($id, $role): Response
    {
        $article = $this->entityManager->getRepository(Articles::class)->find($id);
        $issue = $article->getIssue();
        $nextArticle = $this->entityManager->getRepository(Articles::class)->findOneBy([
            'issue' => $issue,
            'status' => ArticleStatusParam::EDIT_REQUIRED,
        ]);

        if ($nextArticle) {
            return $this->redirectToRoute('article_edit', ['id' => $nextArticle->getId(), 'role' => $role]);
        }
        $this->addFlash('success', 'Makale Tamamlanmıştır.');

        if ($role == 'admin') {
            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
        } elseif ($role == 'editor') {
            return $this->redirectToRoute('editor_articles_list', ['id' => $issue->getId()]);

        } else {
            return $this->redirectToRoute('operator_articles_list', ['id' => $issue->getId()]);

        }

    }

//yeni makale ekleme
    #[Route ('/{role}/article/{id}/new', name: 'article_add')]
    public function articleAdd($id, Request $request, FactoryInterface $factory, $role): Response
    {

        $issue = $this->entityManager->getRepository(Issues::class)->find($id);

        $journal = $issue->getJournal();
        if ($role == 'admin') {
            $breadcrumb = $this->breadcrumbService->createArticleAddBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId());
        } else {
            $breadcrumb = $this->breadcrumbService->createEditorArticleAddBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId(), 'Yeni Makale Ekle');
        }

        $newArticle = new Articles();
        $form = $this->createForm(ArticleFulltextAddFormType::class, $newArticle);
        $form->handleRequest($request);
        $data = $form->getData();


        if ($form->isSubmitted() && $form->isValid()) {
            $newArticle->setPrimaryLanguage('tr');
            $this->entityManager->persist($newArticle);
            $this->entityManager->flush();


            $journalId = $issue->getJournal()->getId();
            $issueId = $issue->getId();
            $issueYear = $issue->getYear();
            $issueNumber = $issue->getNumber();

//translation ekle
            $uniqName = bin2hex(random_bytes(4));
            $fileName = sprintf('%s-%s-%s-%s-%s.pdf', $journalId, $issueId, $issueYear, $issueNumber, $uniqName);

            $uploadPath = '/usr/share/nginx/data/journal/';

            $journalFolder = $uploadPath . $journalId;
            $issueFolder = $journalFolder . '/' . $issueId;
            $destinationPath = $issueFolder . '/' . $fileName;

            try {
                // Hedef dizinleri oluştur
                if (!file_exists($journalFolder)) {
                    mkdir($journalFolder, 0777, true);
                }
                if (!file_exists($issueFolder)) {
                    mkdir($issueFolder, 0777, true);
                }
                $pdfFile = $form->get('fulltext')->getData();
                if ($pdfFile) {
                    // Dosyayı taşı
                    $pdfFile->move(
                        $issueFolder,
                        $fileName
                    );
                    $newArticle->setFulltext($destinationPath);
                } else {
                    $newArticle->setFulltext(null);
                }
            } catch (FileException $e) {
                return new Response($e->getMessage());
            }


//            $newArticle->setFulltext($destinationPath);
            $newArticle->setJournal($journal);
            $newArticle->setIssue($issue);
            $newArticle->setStatus(ArticleStatusParam::EDIT_REQUIRED);
            $newTranslation = new Translations();
            $newTranslation->setArticle($newArticle);
            $newTranslation->setTitle("Başlık Ekleyiniz");
            $issue->setStatus(IssueStatusParam::EDIT_REQUIRED);
            $this->entityManager->persist($issue);
            $this->entityManager->persist($newArticle);
            $this->entityManager->persist($newTranslation);
            $this->entityManager->flush();
            //-*-------------------------------------------

            return $this->redirectToRoute('article_edit', ['id' => $newArticle->getId(), 'role' => $role]);

        }

        return $this->render('article_add.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
            'name' => 'Yeni Makale Ekle',
            'button' => 'Yeni Makale Ekle'
        ]);
    }

//pdf değiştirme
    #[Route ('/{role}/article/{id}/pdf-change', name: 'article_Pdf_Change')]
    public function articlePdfChange($id, Request $request, FactoryInterface $factory, $role,): Response
    {
        $article = $this->entityManager->getRepository(Articles::class)->find($id);
        $issue = $article->getIssue();
        $journal = $issue->getJournal();

        if ($role == 'admin') {
            $breadcrumb = $this->breadcrumbService->createArticlePdfUploadBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId(), $article->getId());

        } else {
            $breadcrumb = $this->breadcrumbService->createEditorArticleAddBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId(), 'PDF değiştir');

        }
        $form = $this->createForm(ArticleFulltextAddFormType::class);
        $form->handleRequest($request);
        $data = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setPrimaryLanguage(ArticleLanguageParam::TURKCE);
            $this->entityManager->persist($article);
            $this->entityManager->flush();
            //bu kısma article dosyasının nereye kaydedileceği gelecek

            $journalId = $journal->getId();
            $issueId = $issue->getId();
            $issueYear = $issue->getYear();
            $issueNumber = $issue->getNumber();


            $filesystem = new Filesystem();

            $oldFilePath = $article->getFulltext();
            if ($oldFilePath) { // Eğer eski dosya varsa
                $directory = dirname($oldFilePath);
                $fileName = basename($oldFilePath);

                try {
                    $filesystem->remove(
                        $oldFilePath
                    );
                } catch (FileException $e) {
                    // Hata durumunda işlem yap
                    return new Response($e->getMessage());
                }
            }

            $uniqName = bin2hex(random_bytes(4));
            $fileName = sprintf('%s-%s-%s-%s-%s.pdf', $journalId, $issueId, $issueYear, $issueNumber, $uniqName);

//            $uploadPath = 'var/journal/';
            $uploadPath = '/usr/share/nginx/data/journal/';

            $journalFolder = $uploadPath . $journalId;
            $issueFolder = $journalFolder . '/' . $issueId;
            $destinationPath = $issueFolder . '/' . $fileName;

// Yeni dosyayı ekle
            try {

                $pdfFile = $form->get('fulltext')->getData();
                if ($pdfFile) {
                    // Dosyayı taşı
                    $pdfFile->move(
                        $issueFolder,
                        $fileName
                    );
                    // Dosya adını ve yolunu veritabanına kaydet
                    $article->setFulltext($destinationPath);
                } else {
                    $article->setFulltext(null);
                }
            } catch (FileException $e) {
                return new Response($e->getMessage());
            }

            $article->setStatus(ArticleStatusParam::EDIT_REQUIRED);

            $issue->setStatus(IssueStatusParam::EDIT_REQUIRED);
            $this->entityManager->persist($article);
            $this->entityManager->persist($issue);

            $this->entityManager->flush();
            return $this->redirectToRoute('article_edit', ['id' => $article->getId(), 'role' => $role]);

        }

        return $this->render('article_add.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
            'name' => 'Pdf Güncelleme',
            'button' => 'Pdf\'i Güncelle'
        ]);
    }

//    #[Route('/fetch-article', name: 'fetch_article_data', methods: ['GET'])]
//    public function articleDoiCheck(Request $request): JsonResponse
//    {
//        $doi = $request->query->get('doi');
//        if (!$doi) {
//            return new JsonResponse(['error' => 'DOI parameter is missing'], Response::HTTP_BAD_REQUEST);
//        }
//        $response = $this->httpClient->request('GET', 'https://api.crossref.org/works/' . $doi);
//        $content = $response->getContent();
//        $data = json_decode($content, true);
//        $title = $data['message']['title'][0] ?? null;
//        $abstract = $data['message']['abstract'] ?? null;
//
//        // Başlık ve özet bilgileri varsa JSON yanıt oluştur
//        if ($title && $abstract) {
//            $responseData = [
//                'title' => $title,
//                'abstract' => $abstract
//            ];
//            return new JsonResponse($responseData);
//        } else {
//            // Başlık veya özet bilgisi bulunamazsa hata yanıtı oluştur
//            return new JsonResponse(['error' => 'Title or abstract not found.'], JsonResponse::HTTP_NOT_FOUND);
//        }
//    }


// article pdf hata bildirme
    #[Route('/{role}/article/{id}/{status}', name: 'article_pdf_error')]
    public function articlePdfError($id, $status, $role): Response
    {
        $article = $this->entityManager->getRepository(Articles::class)->find($id);
        $article->setStatus(ArticleStatusParam::ERROR);

        switch ($status) {
            case '0':
                $errorText = 'Dosya Görüntülenmiyor';
                break;
            case '1':
                $errorText = 'Makale Değil';
                break;
            case '2':
                $errorText = 'Yazı Bozuk Kopyalanıyor';
                break;
            case '3':
                $errorText = 'Yazı Seçilmiyor';
                break;
            default:
                $errorText = 'Diğer';
                break;
        }

        $article->setErrors([$errorText]);
        $issue = $article->getIssue();
        $issue->setStatus(IssueStatusParam::EDIT_REQUIRED);
        $user = $this->security->getUser();
        $article->setEditor($user);
        $utc_now = new \DateTime('now', new \DateTimeZone('UTC'));

        $now = $utc_now->setTimezone(new \DateTimeZone('Europe/Istanbul'));
        $article->setModificationDate($now);
        $this->entityManager->persist($issue);
        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->addFlash('success', 'Makale Pdf Hatası Gönderilmiştir.');

        if ($role == 'admin') {
            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
        } elseif ($role == 'editor') {
            return $this->redirectToRoute('editor_articles_list', ['id' => $issue->getId()]);
        } else {
            return $this->redirectToRoute('operator_articles_list', ['id' => $issue->getId()]);

        }
    }

    // article pdf hata silme
    #[Route('/{role}/article/{id}/delete/error', name: 'article_pdf_error_delete')]
    public function articlePdfErrorDelete($id, $role): Response
    {
        $article = $this->entityManager->getRepository(Articles::class)->find($id);
        $article->setStatus(ArticleStatusParam::EDIT_REQUIRED);
        $article->setErrors([]);
        $article->setModificationDate(null);
        $article->setEditor(null);

        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->addFlash('success', 'Makale Hatası Geri Alınmıştır.');

        return $this->redirectToRoute('article_edit', ['id' => $article->getId(), 'role' => $role]);
    }

// makale silme
    #[Route('/{role}/article/all/{id}/delete', name: 'article_delete')]
    public function articleDeleteFunc($id, $role,): Response
    {
        $article = $this->entityManager->getRepository(Articles::class)->find($id);
        $issue = $article->getIssue();
        $translations = $article->getTranslations();
        $translators = $article->getTranslators();
        $authors = $article->getAuthors();
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
        $filesystem = new Filesystem();

        $pdfFilePath = $article->getFulltext();
        if ($pdfFilePath) {

            try {
                $filesystem->remove(
                    $pdfFilePath
                );
            } catch (FileException $e) {
                return new Response($e->getMessage());
            }
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

        $this->entityManager->remove($article);
        $this->entityManager->flush();

//        $this->addFlash('success', 'Makale Başarıyla Silindi');
        $articleEdited = $this->entityManager->getRepository(Articles::class)->findBy([
            'issue' => $issue,
            'status' => ArticleStatusParam::EDITED,
        ]);
        $articleCount = $this->entityManager->getRepository(Articles::class)->findBy([
            'issue' => $issue,
        ]);
        if (count($articleCount) > 0 && count($articleCount) === count($articleEdited)) {
            $issue->setStatus(IssueStatusParam::EDITED);
            $this->entityManager->persist($issue);
            $this->entityManager->flush();
            $this->addFlash('success', 'Sayı Dışa Aktarıma Hazır.');
        }
        if ($role == 'admin') {
            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
        } else {
            return $this->redirectToRoute('editor_articles_list', ['id' => $issue->getId()]);
        }
    }

    //pdf görüntüleme
    #[Route('/article/pdf/{filename}', name: 'article_pdf', requirements: ['filename' => '.+'])]
    public function showPdfAction($filename)
    {
        $pdfDirectory = '/usr/share/nginx/data/journal/';
        $pdfPath = $filename;
        if (!file_exists($pdfPath)) {
            throw $this->createNotFoundException('The file does not exist');
        }

        $sanitizedFilename = str_replace(['/', '\\'], '_', $filename);

        $response = new BinaryFileResponse($pdfPath);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $sanitizedFilename,
            iconv('UTF-8', 'ASCII//TRANSLIT', $sanitizedFilename)
        );

        return $response;
    }

    //şifre değiştirme
    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getUserIdentifier()]);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        // Formun kullanıldığı yer
        $form = $this->createForm(ChangePasswordFormType::class);

        $form->handleRequest($request);
        $breadcrumb = $this->breadcrumbService->createEmptyBreadcrumb();

        if ($form->isSubmitted() && $form->isValid()) {
            $isPasswordValid = $passwordHasher->isPasswordValid($user, $form->get('currentPassword')->getData());

            if (!$isPasswordValid) {
                $this->addFlash('danger', 'Mevcut Şifrenizi Yanlış Girdiniz.');
            } else {
                $encodedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $this->addFlash('success', 'Şifre Başarıyla Değiştirildi.');

                $user->setPassword($encodedPassword);
                $this->entityManager->flush();
                if (in_array(RoleParam::ROLE_ADMIN, $user->getRoles())) {
                    return $this->redirectToRoute('dashboard_admin');
                } else {
                    return $this->redirectToRoute('app_homepage');
                }


            }
        }

        return $this->render('change_password.html.twig', [
            'changePasswordForm' => $form->createView(),
            'breadcrumb' => $breadcrumb,

        ]);
    }

    private function generateHashedFileName(UploadedFile $file, $journalId, $issueId): string
    {
        $uniqName = uniqid(10);

        $extension = $file->guessExtension();

        $newFileName = sprintf('%s-%s-%s.%s', $journalId, $issueId, $uniqName, $extension);

        return $newFileName;
    }

    function convertToSlug($text)
    {
        $trChars = array('Ç' => 'C', 'ç' => 'c', 'Ğ' => 'G', 'ğ' => 'g', 'İ' => 'I', 'ı' => 'i', 'Ö' => 'O', 'ö' => 'o', 'Ş' => 'S', 'ş' => 's', 'Ü' => 'U', 'ü' => 'u');

        $text = str_replace(array_keys($trChars), array_values($trChars), $text);
        $text = preg_replace('/\s+/', '-', $text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9-]/', '', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');

        return $text;
    }
//    private function generateArticleXMLContent($article, $issue): DOMElement
//    {
//        $xmlDoc = new DOMDocument('1.0', 'UTF-8');
//        $xmlDoc->formatOutput = true;
//
//        $articlesNode = $xmlDoc->createElement('article');
//
//// Her bir makale için döngü oluştur
//        $articleType = $article->getType();
//        $doi = $article->getDoi();
//        $fPage = $article->getFirstPage();
//        $lPage = $article->getLastPage();
//        $primaryLang = $article->getPrimaryLanguage();
//        $journal = $issue->getJournal();
//        $journalName = $journal->getName();
//
//        $typeModifier = new TypeModifier();
//
//        // <article> öğesini oluştur
//        $articleNode = $xmlDoc->createElement('article');
//
//        $articlesNode->setAttribute('article-type', $articleType);
//        $articlesNode->setAttribute('dtd-version', '1.0');
//
//        $frontNode = $xmlDoc->createElement('front');
//        $journalMetaNode = $xmlDoc->createElement('journal-meta');
//
//        $journalTitleGroupNode = $xmlDoc->createElement('journal-title-group');
//        $journalMetaNode->appendChild($journalTitleGroupNode);
//
////issn
//        $journalIssnNode = $xmlDoc->createElement('issn', $journal->getIssn());
//        $journalIssnNode->setAttribute('pub-type', 'ppub');
//        $journalMetaNode->appendChild($journalIssnNode);
//
//// eissn
//        $journalEissnNode = $xmlDoc->createElement('issn', $journal->getEIssn());
//        $journalEissnNode->setAttribute('pub-type', 'epub');
//        $journalMetaNode->appendChild($journalEissnNode);
//
////dergi adı
//        $journalTitleNode = $xmlDoc->createElement('journal-title', $journalName);
//        $journalTitleGroupNode->appendChild($journalTitleNode);
//
////journal meta buraya kadar
//
//        $articleMetaNode = $xmlDoc->createElement('article-meta');
//        $articleIdNode = $xmlDoc->createElement('article-id', $doi);
//        $articleIdNode->setAttribute('pub-id-type', 'doi');
//        $articleMetaNode->appendChild($articleIdNode);
//
//        $articleTitleGroupNode = $xmlDoc->createElement('title-group');
//
//        $translations = $article->getTranslations();
//
//        foreach ($translations as $translation) {
//            // Ana dil ile aynı olan çeviriyi doğrudan <article-title> içine ekleyin
//            if ($primaryLang == $translation->getLocale()) {
//                $articleTitleNode = $xmlDoc->createElement('article-title', $translation->getTitle());
//                $articleTitleGroupNode->appendChild($articleTitleNode);
//            }
//        }
//
//        $hasTranslations = $translations->count() > 1;
//
//// Ana dil ile aynı olmayan çevirileri <trans-title-group> içine ekleyin
//        if ($hasTranslations) {
//            foreach ($translations as $translation) {
//                if ($primaryLang != $translation->getLocale()) { // Ana dil ile aynı olmayan çeviriler
//
//                    $articleTransTitleGroupNode = $xmlDoc->createElement('trans-title-group');
//                    $articleTransTitleGroupNode->setAttribute('xml:lang', $typeModifier->convertLanguageCode($translation->getLocale()));
//                    $articleTransTitleNode = $xmlDoc->createElement('trans-title', $translation->getTitle());
//                    $articleTransTitleGroupNode->appendChild($articleTransTitleNode);
//                }
//            }
//
//            $articleTitleGroupNode->appendChild($articleTransTitleGroupNode);
//        }
//
//        $articleMetaNode->appendChild($articleTitleGroupNode);
//
//
//        //yazar sekmesi
//        $contribGroupNode = $xmlDoc->createElement('contrib-group');
//        $authors = $article->getAuthors();
//        foreach ($authors as $author) {
//            $contribNode = $xmlDoc->createElement('contrib');
//            $contribNode->setAttribute('contrib-type', 'author');
////isim soyad
//            $nameNode = $xmlDoc->createElement('name');
//            $surnameNode = $xmlDoc->createElement('surname', $author->getLastname());
//            $nameNode->appendChild($surnameNode);
//            $givenNameNode = $xmlDoc->createElement('given-names', $author->getFirstname());
//            $nameNode->appendChild($givenNameNode);
//            $contribNode->appendChild($nameNode);
////institute
//            $affNode = $xmlDoc->createElement('aff', $author->getInstitute());
//
//            $contribNode->appendChild($affNode);
//
//            $emailNode = $xmlDoc->createElement('email', $author->getEmail());
//            $contribNode->appendChild($emailNode);
////orcid
//            if ($author->getOrcId()) {
//                $contribIdNode = $xmlDoc->createElement('contrib-id', 'https://orcid.org/' . $author->getOrcId());
//                $contribIdNode->setAttribute('contrib-id-type', 'orcid');
//                $contribNode->appendChild($contribIdNode);
//            }
//            $contribGroupNode->appendChild($contribNode);
//        }
//        //çevirmen sekmesi
//        $translators = $article->getTranslators();
//        foreach ($translators as $translator) {
//            $contribNode = $xmlDoc->createElement('contrib');
//            $contribNode->setAttribute('contrib-type', 'translator');
////isim soyad
//            $nameNode = $xmlDoc->createElement('name');
//            $surnameNode = $xmlDoc->createElement('surname', $translator->getLastname());
//            $nameNode->appendChild($surnameNode);
//            $givenNameNode = $xmlDoc->createElement('given-names', $translator->getFirstname());
//            $nameNode->appendChild($givenNameNode);
//            $contribNode->appendChild($nameNode);
//
////institute
//            $affNode = $xmlDoc->createElement('aff', $translator->getInstitute());
//            $contribNode->appendChild($affNode);
////orcid
//            if ($translator->getOrcId()) {
//                $contribIdNode = $xmlDoc->createElement('contrib-id', 'https://orcid.org/' . $translator->getOrcId());
//                $contribIdNode->setAttribute('contrib-id-type', 'orcid');
//                $contribNode->appendChild($contribIdNode);
//            }
//            $contribGroupNode->appendChild($contribNode);
//        }
//        $articleMetaNode->appendChild($contribGroupNode);
////pubdate
//        if ($article->getReceivedDate() && $article->getAcceptedDate()) {
//            $receivedDate = $article->getReceivedDate()->format('Ymd');
//            $historyNode = $xmlDoc->createElement('history');
//
//            // Received Date Node
//            $dateReceivedNode = $xmlDoc->createElement('date');
//            $dateReceivedNode->setAttribute('date-type', 'received');
//            $dateReceivedNode->setAttribute('iso-8601-date', $receivedDate);
//
//            // Day Node
//            $dayReceivedNode = $xmlDoc->createElement('day', $article->getReceivedDate()->format('d'));
//            $dateReceivedNode->appendChild($dayReceivedNode);
//
//            // Month Node
//            $monthReceivedNode = $xmlDoc->createElement('month', $article->getReceivedDate()->format('m'));
//            $dateReceivedNode->appendChild($monthReceivedNode);
//
//            // Year Node
//            $yearReceivedNode = $xmlDoc->createElement('year', $article->getReceivedDate()->format('Y'));
//            $dateReceivedNode->appendChild($yearReceivedNode);
//
//            // Append Received Date Node to history node
//            $historyNode->appendChild($dateReceivedNode);
//
//            // Accepted Date Node
//            $acceptedDate = $article->getAcceptedDate()->format('Ymd');
//            $dateAcceptedNode = $xmlDoc->createElement('date');
//            $dateAcceptedNode->setAttribute('date-type', 'accepted');
//            $dateAcceptedNode->setAttribute('iso-8601-date', $acceptedDate);
//
//            // Day Node
//            $dayAcceptedNode = $xmlDoc->createElement('day', $article->getAcceptedDate()->format('d'));
//            $dateAcceptedNode->appendChild($dayAcceptedNode);
//
//            // Month Node
//            $monthAcceptedNode = $xmlDoc->createElement('month', $article->getAcceptedDate()->format('m'));
//            $dateAcceptedNode->appendChild($monthAcceptedNode);
//
//            // Year Node
//            $yearAcceptedNode = $xmlDoc->createElement('year', $article->getAcceptedDate()->format('Y'));
//            $dateAcceptedNode->appendChild($yearAcceptedNode);
//
//            // Append Accepted Date Node to history node
//            $historyNode->appendChild($dateAcceptedNode);
//
//            // Append history node to appropriate parent node (like $articleMetaNode)
//            $articleMetaNode->appendChild($historyNode);
//        }
//
//
//        //volume
//        $volumeNode = $xmlDoc->createElement('volume', $issue->getVolume());
//        $articleMetaNode->appendChild($volumeNode);
////sayı
//        $numberNode = $xmlDoc->createElement('issue', $issue->getNumber());
//        $articleMetaNode->appendChild($numberNode);
////birinci sayfa
//        $fpageNode = $xmlDoc->createElement('fpage', $fPage);
//        $articleMetaNode->appendChild($fpageNode);
////ikinci sayfa
//        $lpageNode = $xmlDoc->createElement('lpage', $lPage);
//        $articleMetaNode->appendChild($lpageNode);
//
////abstract
//        foreach ($translations as $translation) {
//            if ($article->getPrimaryLanguage() == $translation->getLocale()) {
//                $abstractNode = $xmlDoc->createElement('abstract');
//                $pNode = $xmlDoc->createElement('p', htmlspecialchars($translation->getAbstract(), ENT_XML1 | ENT_COMPAT, 'UTF-8'));
//                $abstractNode->appendChild($pNode);
//                $articleMetaNode->appendChild($abstractNode);
//            } else {
//                $transAbstractNode = $xmlDoc->createElement('trans-abstract');
//                $transAbstractNode->setAttribute('xml:lang', $typeModifier->convertLanguageCode($translation->getLocale()));
//                $pTransNode = $xmlDoc->createElement('p', htmlspecialchars($translation->getAbstract(), ENT_XML1 | ENT_COMPAT, 'UTF-8'));
//
////                    $pTransNode = $xmlDoc->createElement('p', $translation->getAbstract());
//                $transAbstractNode->appendChild($pTransNode);
//                $articleMetaNode->appendChild($transAbstractNode);
//            }
//        }
//        foreach ($translations as $translation) {
//            $kwdGroupNode = $xmlDoc->createElement('kwd-group');
//            $kwdGroupNode->setAttribute('xml:lang', $typeModifier->convertLanguageCode($translation->getLocale()));
//            foreach ($translation->getKeywords() as $keyword) {
//                $kwdNode = $xmlDoc->createElement('kwd', $keyword);
//                $kwdGroupNode->appendChild($kwdNode);
//            }
//            $articleMetaNode->appendChild($kwdGroupNode);
//        }
//
//        $frontNode->appendChild($articleMetaNode);
//
//        $articlesNode->appendChild($frontNode);
//
//        $back = $xmlDoc->createElement('back');
//        $refListNode = $xmlDoc->createElement('ref-list');
//        foreach ($article->getCitations() as $citation) {
//            $refNode = $xmlDoc->createElement('ref');
//            $refNode->setAttribute('id', 'ref' . $citation->getRow());
//
//            $label = $xmlDoc->createElement('label', $citation->getRow());
//            $refNode->appendChild($label);
//            $mixedCitationNode = $xmlDoc->createElement('mixed-citation', htmlspecialchars($citation->getReferance(), ENT_XML1));
//            $refNode->appendChild($mixedCitationNode);
//
//            $refListNode->appendChild($refNode);
//
//        }
//        $back->appendChild($refListNode);
//        $articlesNode->appendChild($back);
//
//// <articles> kök öğesini XML dokümanına ekle
//        $xmlDoc->appendChild($articlesNode);
//
//        return $articlesNode;
//    }
}