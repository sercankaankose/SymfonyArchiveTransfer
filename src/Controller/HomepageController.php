<?php

namespace App\Controller;

use App\Entity\Articles;
use App\Entity\Citations;
use App\Entity\Issues;
use App\Entity\Journal;

use App\Entity\Translations;
use App\Form\ArticleFormType;
use App\Form\ArticleFulltextAddFormType;
use App\Form\IssuesFormType;

use App\Params\ArticleStatusParam;
use App\Params\ArticleTypeParam;
use App\Params\IssueStatusParam;
use App\Params\RoleParam;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BreadCrumbService;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class HomepageController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BreadCrumbService $breadcrumbService;

    public function __construct(EntityManagerInterface $entityManager, BreadCrumbService $breadcrumbService)
    {
        $this->entityManager = $entityManager;
        $this->breadcrumbService = $breadcrumbService;
    }

    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        $breadcrumb = $this->breadcrumbService->createEmptyBreadcrumb();

        if ($this->getUser() === null) {
            $this->redirectToRoute('app_login');
        }
        return $this->render('homepage/index.html.twig', [
            'breadcrumb' => $breadcrumb,
        ]);
    }

// sayı listesi
    #[Route('/journal/{id}/issues', name: 'journal_issues')]
    public function journalIssues($id, FactoryInterface $factory): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);

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
        return $this->render('journal-number.html.twig', [
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
            'issues' => $issues,

        ]);
    }

// sayı ekleme
    #[Route('/journal/{id}/issue/add', name: 'journal_issue_add')]
    public function issueAdd($id, Request $request, FactoryInterface $factory): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);
        $journalname = $journal->getName();
        $breadcrumb = $this->breadcrumbService->createIssueAddBreadcrumb($factory, $journalname, $id);

        $newissue = new Issues();
        $newissue->setJournal($journal);
        $this->entityManager->persist($newissue);
        $form = $this->createForm(IssuesFormType::class, $newissue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($newissue);

            $journalId = $journal->getId();
            $issueId = $newissue->getId();
//            $pdfFile = $form->get('fulltext')->getData();
//            if ($pdfFile) {
//                $pdfFileName = $this->generateHashedFileName($pdfFile, $journalId, $issueId);
//                try {
//                    $pdfFile->move(
//                        $this->getParameter('kernel.project_dir') . '/public/uploads/pdf', $pdfFileName);
//                    $newissue->setFulltext('public/uploads/pdf/' . $pdfFileName);
//                } catch (FileException $e) {
//
//                    return new Response($e->getMessage());
//                }
//            } else {
//                $newissue->setFulltext(null);
//            }

            $xmlFile = $form->get('xml')->getData();
            if ($xmlFile) {
                $baseDirectory = $this->getParameter('kernel.project_dir') . '/var/journal/' . $journal->getId();
                if (!file_exists($baseDirectory)) {
                    mkdir($baseDirectory, 0755, true);
                }

                $xmlFileName = $this->generateHashedFileName($xmlFile, $journalId, $issueId);
//                $xmlPath = $baseDirectory . '/' . $xmlFileName;
                try {
                    $xmlFile->move($baseDirectory, $xmlFileName);
                    $newissue->setXml('var/journal/' . $journal->getId() . '/' . $xmlFileName);
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }
            }
            $newissue->setStatus(IssueStatusParam::WAITING);
            $this->entityManager->persist($newissue);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Yeni Sayı Oluşturulmuştur.'
            );
            return $this->redirectToRoute('journal_issues', ['id' => $id]);
        }
        return $this->render('journal_issue_add.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
        ]);
    }

// sayı durum kaydetme
    #[Route('journal/{id}/issue/save', name: 'issue_save')]
    public function issueSave($id): Response
    {
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();
        $articleEditReq = $this->entityManager->getRepository(Articles::class)->findOneBy([
            'issue' => $issue,
            'status' => ArticleStatusParam::EDIT_REQUIRED
        ]);
        $articleError = $this->entityManager->getRepository(Articles::class)->findOneBy([
            'issue' => $issue,
            'status' => ArticleStatusParam::ERROR
        ]);

        if ($articleEditReq) {
            $this->addFlash('danger', 'Düzenlenmemiş Makale Var');
            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
        }

        if ($articleError) {
            $this->addFlash('danger', 'Hatalı Makale Var');
            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
        }
        $issue->setStatus(IssueStatusParam::EDITED);
        $this->entityManager->persist($issue);
        $this->entityManager->flush();
        return $this->redirectToRoute('journal_issues', ['id' => $journal->getId()]);

    }

// sayı dışa aktarımı
    #[Route('journal/issue/{id}/export', name: 'issue_export')]
    public function issueExport($id,): Response
    {
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();
        $articles = $issue->getArticles();
        $journalName = $journal->getName();
        $journalIssn = $journal->getIssn();
        $journalEissn = $journal->getEissn();
        $publisher = $journal->getPublisher();
        // XML dom belgesi oluştur
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');
        $xmlDoc->formatOutput = true;

// <articles> kök öğesini oluştur
        $articlesNode = $xmlDoc->createElement('articles');

// Her bir makale için döngü oluştur
        foreach ($articles as $article) {
            $articleType = $article->getType();
            $doi = $article->getDoi();
            $fPage = $article->getFirstPage();
            $lPage = $article->getLastPage();
            $primaryLang = $article->getPrimaryLanguage();

            // <article> öğesini oluştur
            $articleNode = $xmlDoc->createElement('article');
            $articleNode->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
            $articleNode->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
            $articleNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $articleNode->setAttribute('article-type', $articleType); //type switchcase ile değiştirelecek
            $articleNode->setAttribute('dtd-version', '1.0');

            $frontNode = $xmlDoc->createElement('front');
            $journalMetaNode = $xmlDoc->createElement('journal-meta');

            $journalTitleGroupNode = $xmlDoc->createElement('journal-title-group');
            $journalMetaNode->appendChild($journalTitleGroupNode);

//issn
            $journalIssnNode = $xmlDoc->createElement($journalIssn);
            $journalIssnNode->setAttribute('pub-type','ppub');
            $journalMetaNode->appendChild($journalIssnNode);

// eissn
            $journalEissnNode = $xmlDoc->createElement($journalEissn);
            $journalEissnNode->setAttribute('pub-type','epub');
            $journalMetaNode->appendChild($journalEissnNode);

//dergi adı
            $journalTitleNode = $xmlDoc->createElement('journal-title', $journalName);
            $journalTitleGroupNode->appendChild($journalTitleNode);
//yayıncı
            $journalPublisherNode = $xmlDoc->createElement('publisher');
            $journalPublisherNameNode = $xmlDoc->createElement('publisher-name', $publisher);
            $journalPublisherNode->appendChild($journalPublisherNameNode);
//journal meta buraya kadar

            $articleMetaNode = $xmlDoc->createElement('article-meta');
            $articleIdNode = $xmlDoc->createElement('article-id', $doi);
            $articleIdNode->setAttribute('pub-id-type', 'doi');
            $articleMetaNode->appendChild($articleIdNode);

            $articleTitleGroupNode = $xmlDoc->createElement('title-group');

//makale başlıkları burada
            $translations = $article->getTranslations();
            foreach ($translations as $translation) {
                $articleTitleNode = $xmlDoc->createElement('article-title', $translation->getTitle());
            }
            $articleTitleGroupNode->appendChild($articleTitleNode);

        //yazar sekmesi
            $contribGroupNode = $xmlDoc->createElement('contrib-group');
            $authors = $article->getAuthors();
            foreach ($authors as $author) {
                $contribNode = $xmlDoc->createElement('contrib');
                $contribNode->setAttribute('contrib-type', 'author');
                $contribGroupNode->appendChild($contribNode);
//isim soyad
                $nameNode = $xmlDoc->createElement('name');
                $surnameNode = $xmlDoc->createElement('surname',$author->getLastname());
                $nameNode->appendChild($surnameNode);
                $givenNameNode = $xmlDoc->createElement('given-names',$author->getFirstname());
                $nameNode->appendChild($givenNameNode);
//institute
                $affNode = $xmlDoc->createElement('aff',$author->getInstitute());
                $nameNode->appendChild($affNode);
//orcid
                $contribIdNode = $xmlDoc->createElement('contrib-id',$author->getOrcId());
                $contribIdNode->setAttribute('contrib-id-type','orcid');
                $nameNode->appendChild($contribIdNode);
            }
        //çevirmen sekmesi
            $translators = $article->getTranslators();
            foreach ($translators as $translator) {
                $contribNode = $xmlDoc->createElement('contrib');
                $contribNode->setAttribute('contrib-type', 'translator');
                $contribGroupNode->appendChild($contribNode);
//isim soyad
                $nameNode = $xmlDoc->createElement('name');
                $surnameNode = $xmlDoc->createElement('surname',$translator->getLastname());
                $nameNode->appendChild($surnameNode);
                $givenNameNode = $xmlDoc->createElement('given-names',$translator->getFirstname());
                $nameNode->appendChild($givenNameNode);
//institute
                $affNode = $xmlDoc->createElement('aff',$translator->getInstitute());
                $nameNode->appendChild($affNode);
//orcid
                $contribIdNode = $xmlDoc->createElement('contrib-id',$translator->getOrcId());
                $contribIdNode->setAttribute('contrib-id-type','orcid');
                $nameNode->appendChild($contribIdNode);
            }

            $articleNode->appendChild($frontNode);

            // Diğer XML öğelerini oluştur ve <front> öğesine ekle...

            // Makale <article> öğesini <articles> kök öğesine ekle
            $articlesNode->appendChild($articleNode);
        }

// <articles> kök öğesini XML dokümanına ekle
        $xmlDoc->appendChild($articlesNode);

// XML içeriğini bir değişkene atayın
        $xmlContent = $xmlDoc->saveXML();
        $fileName = 'exported_articles.xml';

        $response = new Response(file_get_contents($fileName));
        $response->headers->set('Content-Type', 'application/xml');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->sendHeaders();

        return $response;


        $articleXml = '<articles>';

        foreach ($articles as $article) {
            $articleType = $article->getType();
            $doi = $article->getDoi();
            $fPage = $article->getFirstPage();
            $lPage = $article->getLastPage();
            $primaryLang = $article->getPrimaryLanguage();

            $articleXml .= '<article xmlns:mml="http://www.w3.org/1998/Math/MathML" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" article-type="' . $articleType . '" dtd-version="1.0">';
            $articleXml .= '<front>';
            $articleXml .= '<journal-meta>';
//            $articleXml .= '<journal-id>-<journal-id/>'; //journal id nedir
            $articleXml .= '<journal-title-group>';
            $articleXml .= '<journal-title>' . $journalName . '</journal-title>';
            $articleXml .= '</journal-title-group>';
            $articleXml .= '<issn pub-type="ppub">' . $journalIssn . '</issn>';
            $articleXml .= '<issn pub-type="epub">' . $journalEissn . '</issn>';
            $articleXml .= '<publisher>';
            $articleXml .= '<publisher-name>' . $publisher . '</publisher-name>';
            $articleXml .= '</publisher>';
            $articleXml .= '</journal-meta>';
            $articleXml .= '<article-meta>';
            $articleXml .= '<article-id pub-id-type="doi">' . $doi . '</article-id>';
            $articleXml .= '<article-categories>';
            $articleXml .= '<subj-group>';
            $articleXml .= '<subject>-</subject>';
            $articleXml .= '</subj-group>';
            $articleXml .= '</article-categories>';
            $articleXml .= '<title-group>';

            $translations = $article->getTranslations();
            foreach ($translations as $translation) {
                $articleTitle = $translation->getTitle();
                $articleTitle = htmlspecialchars($articleTitle, ENT_XML1);
                $articleXml .= '<article-title>' . $articleTitle . '</article-title>';

            }
            $articleXml .= '</title-group>';

            $articleXml .= '<contrib-group>';

            $authors = $article->getAuthors();
            foreach ($authors as $author) {
                $authorName = $author->getFirstname();
                $authorLastName = $author->getLastname();
                $authorOrcId = $author->getOrcId();
                $authorInstitute = $author->getInstitute();

                $articleXml .= '<contrib contrib-type="author">';
                $articleXml .= '<name>';
                $articleXml .= '<surname><![CDATA[' . $authorLastName . ']]></surname>';
                $articleXml .= '<given-names><![CDATA[' . $authorName . ']]></given-names>';
                $articleXml .= '</name>';
                $articleXml .= '<aff>' . $authorInstitute . '</aff>';
                $articleXml .= '<contrib-id contrib-id-type="orcid">' . $authorOrcId . '</contrib-id>';
                $articleXml .= '</contrib>';
            }
            $translators = $article->getTranslators();
            if ($translators) {

                foreach ($translators as $translator) {
                    $translatorName = $translator->getFirstname();
                    $translatorLastName = $translator->getLastname();
                    $translatorOrcId = $translator->getOrcId();
                    $translatorInstitute = $translator->getInstitute();

                    $articleXml .= '<contrib contrib-type="translator">';
                    $articleXml .= '<name>';
                    $articleXml .= '<surname><![CDATA[' . $translatorLastName . ']]></surname>';
                    $articleXml .= '<given-names><![CDATA[' . $translatorName . ']]></given-names>';
                    $articleXml .= '</name>';
                    $articleXml .= '<aff>' . $translatorInstitute . '</aff>';
                    $articleXml .= '<contrib-id contrib-id-type="orcid">' . $translatorOrcId . '</contrib-id>';
                    $articleXml .= '</contrib>';
                }
            }
            $articleXml .= '</contrib-group>';

            //bu kısımda date kısmı gelecek düzelt
            //***-*-*-*-*--*--------

            $articleXml .= '<abstract><![CDATA[';
            foreach ($translations as $translation) {
                $abstract = $translation->getAbstract();
                $abstract = htmlspecialchars($abstract, ENT_XML1);

                $articleXml .= '<p>' . $abstract . '</p>';

            }
            $articleXml .= ']]></abstract>';

            $articleXml .= '<kwd-group>';
            foreach ($translations as $translation) {

                foreach ($translation->getKeywords() as $keyword) {
                    $articleXml .= '<kwd>' . $keyword . '</kwd>';
                }
            }
            $articleXml .= '</kwd-group>';
            $articleXml .= '</article-meta>';
            $articleXml .= '</front>';
            $articleXml .= '<back>';
            $articleXml .= '<ref-list>';

            $citations = $article->getCitations();

            foreach ($citations as $citation) {

                $articleXml .= '<ref id="ref' . $citation->getRow() . '">';
                $articleXml .= '<label>' . $citation->getRow() . '</label>';
                $articleXml .= '<mixed-citation>' . $citation->getReferance() . '</mixed-citation>';
                $articleXml .= '</ref>';
            }

            $articleXml .= '</ref-list>';
            $articleXml .= '</back>';
            $articleXml .= '</article>';
            $articleXml .= '';
        }
        $articleXml .= '</articles>';

        $xmlContent = $articleXml;


// Dosyayı oluştur
        $fileName = 'exported_articles.xml';
        $file = fopen($fileName, 'w');
        fwrite($file, $xmlContent);
        fclose($file);
//switchcase ile article typeı düzenle

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
    public function articleList($id, FactoryInterface $factory): Response
    {
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

        ]);
    }

    #[Route('article/edit/{id}', name: 'article_edit')]
    public function article_edit($id, Request $request, FactoryInterface $factory): Response
    {

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
        $breadcrumb = $this->breadcrumbService->createArticleEditBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId());
        $path = 'var' . '/' . 'journal' . '/' . $journal->getId() . '/' . $issue->getId();
        $pdfFileName = trim($article->getFulltext(), $path);
        $pdfFileName = $article->getFulltext();

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

            $article->setStatus(ArticleStatusParam::EDITED);
            $this->entityManager->persist($article);
            $this->entityManager->flush();
            $this->addFlash('success', 'Makale bilgileri güncellendi.');

            if ($request->request->has('save_and_skip')) {

                return $this->redirectToRoute('article_save_skip', ['id' => $article->getId()]);
            }
            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
        }
        return $this->render('article_edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'pdfFile' => $pdfFileName,
            'article' => $article,
        ]);
    }


//sonraki makaleye geçmek için
    #[Route('/article/save-skip/{id}', name: 'article_save_skip')]
    public function articleSaveSkip($id): Response
    {
        $article = $this->entityManager->getRepository(Articles::class)->find($id);
        $issue = $article->getIssue();
        $nextArticle = $this->entityManager->getRepository(Articles::class)->findOneBy([
            'issue' => $issue,
            'status' => ArticleStatusParam::EDIT_REQUIRED,
        ]);

        if ($nextArticle) {
            return $this->redirectToRoute('article_edit', ['id' => $nextArticle->getId()]);
        } else {
            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);

        }
    }

//yeni makale ekleme
    #[Route ('/article/{id}/new', name: 'article_add')]
    public function articleAdd($id, Request $request, FactoryInterface $factory,): Response
    {

        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();

        $newArticle = new Articles();
        $form = $this->createForm(ArticleFulltextAddFormType::class, $newArticle);
        $form->handleRequest($request);
        $data = $form->getData();
        $breadcrumb = $this->breadcrumbService->createArticleAddBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId());

        if ($form->isSubmitted() && $form->isValid()) {
            $newArticle->setPrimaryLanguage('001');
            $this->entityManager->persist($newArticle);
            $this->entityManager->flush();
            //bu kısma article dosyasının nereye kaydedileceği gelecek

            $journalId = $issue->getJournal()->getId();
            $issueId = $issue->getId();
            $issueYear = $issue->getYear();
            $issueNumber = $issue->getNumber();
//translation ekle


            $uniqName = bin2hex(random_bytes(4));
            $fileName = sprintf('%s-%s-%s-%s-%s.pdf', $journalId, $issueId, $issueYear, $issueNumber, $uniqName);

            $uploadPath = 'var/journal/';
            $journalFolder = $uploadPath . $journalId;
            $issueFolder = $journalFolder . '/' . $issueId;
            $destinationPath = $issueFolder . '/' . $fileName;

            $filesystem = new Filesystem();

            try {
                // Hedef dizinleri oluştur
                $filesystem->mkdir($journalFolder);
                $filesystem->mkdir($issueFolder);

                $pdfFile = $form->get('fulltext')->getData();
                if ($pdfFile) {
                    // Dosyayı taşı
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/' . $issueFolder,
                        $fileName
                    );
                    // Dosya adını ve yolunu veritabanına kaydet
                    $newArticle->setFulltext($destinationPath);
                } else {
                    $newArticle->setFulltext(null);
                }
            } catch (FileException $e) {
                // Hata durumunda işlem yap
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

            $this->redirectToRoute('article_edit', ['id' => $newArticle->getId()]);
        }

        return $this->render('article_add.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
        ]);
    }


    #[Route ('/article/{id}/pdf-change', name: 'article_Pdf_Change')]
    public function articlePdfChange($id, Request $request, FactoryInterface $factory,): Response
    {

        $article = $this->entityManager->getRepository(Articles::class)->find($id);
        $issue = $article->getIssue();
        $journal = $issue->getJournal();

        $form = $this->createForm(ArticleFulltextAddFormType::class);
        $form->handleRequest($request);
        $data = $form->getData();
        $breadcrumb = $this->breadcrumbService->createArticlePdfUploadBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId(), $article->getId());

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setPrimaryLanguage('001');
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
                        $this->getParameter('kernel.project_dir') . '/' . $directory . '/' . $fileName
                    );
                } catch (FileException $e) {
                    // Hata durumunda işlem yap
                    return new Response($e->getMessage());
                }
            }

            $uniqName = bin2hex(random_bytes(4));
            $fileName = sprintf('%s-%s-%s-%s-%s.pdf', $journalId, $issueId, $issueYear, $issueNumber, $uniqName);

            $uploadPath = 'var/journal/';
            $journalFolder = $uploadPath . $journalId;
            $issueFolder = $journalFolder . '/' . $issueId;
            $destinationPath = $issueFolder . '/' . $fileName;

// Yeni dosyayı ekle
            try {

                $pdfFile = $form->get('fulltext')->getData();
                if ($pdfFile) {
                    // Dosyayı taşı
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/' . $issueFolder,
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
            return new RedirectResponse($this->generateUrl('article_edit', ['id' => $article->getId()]));

        }

        return $this->render('article_add.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'journal' => $journal,
        ]);
    }

// article pdf hata bildirme
    #[Route('article/{id}/{status}', name: 'article_pdf_error')]
    public function articlePdfError($id, $status): Response
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
                $errorText = 'Bilinmeyen Hata';
                break;
        }

        $article->setErrors([$errorText]);
        $issue = $article->getIssue();
        $issue->setStatus(IssueStatusParam::EDIT_REQUIRED);

        $this->entityManager->persist($issue);
        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->addFlash('success', 'Makale Pdf Hatası Gönderilmiştir.');

        return $this->redirectToRoute('article_Pdf_Change', ['id' => $article->getId()]);
    }

    // article pdf hata silme
    #[Route('/article/{id}/delete/error', name: 'article_pdf_error_delete')]
    public function articlePdfErrorDelete($id): Response
    {
        $article = $this->entityManager->getRepository(Articles::class)->find($id);
        $article->setStatus(ArticleStatusParam::EDIT_REQUIRED);
        $article->setErrors([]);

        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->addFlash('success', 'Makale Hatası Geri Alınmıştır.');

        return $this->redirectToRoute('article_edit', ['id' => $article->getId()]);
    }

    #[Route('/article/pdf/{filename}', name: 'article_pdf', requirements: ['filename' => '.+'])]
    public function showPdfAction($filename)
    {
        $pdfPath = $this->getParameter('pdf_directory') . '/' . $filename;
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


    private function generateHashedFileName(UploadedFile $file, $journalId, $issueId): string
    {
        $uniqName = uniqid(10);

        $extension = $file->guessExtension();

        $newFileName = sprintf('%s-%s-%s.%s', $journalId, $issueId, $uniqName, $extension);

        return $newFileName;
    }



//    #[Route('article/new/{id}', name: 'new_article')]
//    public function new_article($id, Request $request, FactoryInterface $factory): Response
//    {
//
//        $article = new Articles();
//        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
//        $journal = $article->getJournal();
//
//        $breadcrumb = $this->breadcrumbService->createArticleNewBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId());
//        $pdfFileName = trim($article->getFulltext(), 'var/uploads/articlepdf/');
//        $pdfFileName = $pdfFileName . 'pdf';
//        if (!$journal && !$issue && !$article ) {
//            $this->addFlash('danger', 'Dergi, sayı veya makale hatalı.');
//            return $this->redirectToRoute('admin_journal_management');
//        }
//        $form = $this->createForm(ArticleFormType::class, $article);
//        $form->handleRequest($request);
//        foreach ($article->getTranslations() as $newTranslation) {
//            if ($newTranslation->getId() === null) {
//                $this->entityManager->persist($newTranslation);
//            }
//        }
//        foreach ($article->getCitations() as $citation) {
//            if ($citation->getId() === null) {
//                $this->entityManager->persist($citation);
//            }
//        }
//        foreach ($article->getAuthors() as $author) {
//            if ($author->getId() === null) {
//                $this->entityManager->persist($author);
//            }
//        }
//        $translationsInArticle = $article->getTranslations();
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $language = 0;
//            foreach ($translationsInArticle as $translation) {
//                if ($article->getPrimaryLanguage() === $translation->getLocale()) {
//                    $language++;
//                }
//            }
//            if ($language !== 1) {
//                $this->addFlash('danger', 'Birincil Dil ve Makale dillerini kontrol edin');
//                return $this->redirectToRoute('article_edit', ['id' => $article->getId()]);
//            }
//            if ($article->getType() === ArticleTypeParam::TRANSLATE) {
//                $translaterExists = false;
//                foreach ($article->getAuthors() as $author) {
//                    if ($author->getPart() === AuthorPartParam::TRANSLATER) {
//                        $translaterExists = true;
//                        break;
//                    }
//                }
//                if (!$translaterExists) {
//                    $this->addFlash('danger', 'Makale Türü Çeviri, Çevirmen Eklemelisiniz.');
//                    return $this->redirectToRoute('article_edit', ['id' => $article->getId()]);
//                }
//            }
//            $allCitations = $form->get('citationsText')->getData();
//
//            if ($allCitations !== null) {
//                $existingCitations = $article->getCitations();
//
//                foreach ($existingCitations as $citation) {
//                    $this->entityManager->remove($citation);
//                }
//
//                $this->entityManager->flush();
//
//                $citationsArray = explode("\r\n\r\n", $allCitations);
//                $counter = 1;
//                foreach ($citationsArray as $citationText) {
//                    $citation = new Citations();
//                    $citation->setReferance($citationText);
//                    $citation->setRow($counter);
//
//
//                    $counter = $counter++;
//                    $article->addCitation($citation);
//
//                    $this->entityManager->persist($citation);
//                }
//
//            }
//            $this->entityManager->flush();
//
//            $this->addFlash('success', 'Makale bilgileri güncellendi.');
//
//            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
//        }
//
//
//        return $this->render('article_edit.html.twig', [
//            'form' => $form->createView(),
//            'breadcrumb' => $breadcrumb,
//            'pdfFile' => $pdfFileName,
//            'article' => $article
//
//        ]);
//    }
}
