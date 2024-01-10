<?php

namespace App\Controller;

use App\Entity\Articles;
use App\Entity\Citations;
use App\Entity\Issues;
use App\Entity\Journal;

use App\Entity\Translations;
use App\Form\ArticleFormType;
use App\Form\IssuesFormType;

use App\Params\ArticleStatusParam;
use App\Params\ArticleTypeParam;
use App\Params\ROLE_PARAM;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BreadCrumbService;


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

    #[Route('/journal/{id}/issues', name: 'journal_issues')]
    public function journal_issues($id, FactoryInterface $factory): Response
    {
        $journal = $this->entityManager->getRepository(Journal::class)->find($id);

        if (!$journal) {
            $this->addFlash('danger', 'Dergi Bulunamadı.');
            if (in_array($this->getUser()->getRoles(), (array)ROLE_PARAM::ROLE_ADMIN)) {
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


    #[Route('/journal/{id}/issue/add', name: 'journal_issue_add')]
    public function issue_add($id, Request $request, FactoryInterface $factory): Response
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
            $pdfFile = $form->get('fulltext')->getData();
            if ($pdfFile) {
                $pdfFileName = $this->generateHashedFileName($pdfFile, $journalId, $issueId);
                try {
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/pdf', $pdfFileName);
                    $newissue->setFulltext('public/uploads/pdf/' . $pdfFileName);
                } catch (FileException $e) {

                    return new Response($e->getMessage());
                }
            } else {
                $newissue->setFulltext(null);
            }

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
            $newissue->setStatus('waiting');
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

    #[Route('journal/issue/{id}/articles', name: 'articles_list')]
    public function articleList($id, FactoryInterface $factory): Response
    {
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();

        $breadcrumb = $this->breadcrumbService->createArticle_listBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $journal->getId());

        if (!$journal && !$issue) {
            $this->addFlash('danger', 'Dergi ya da sayı Bulunamadı.');
            if (in_array($this->getUser()->getRoles(), (array)ROLE_PARAM::ROLE_ADMIN)) {
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

        $breadcrumb = $this->breadcrumbService->createArticleEditBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $issue->getId(), $journal->getId());
        $path = 'var' . '/' . 'journal' . '/' . $journal->getId() . '/' . $issue->getId();
        $pdfFileName = trim($article->getFulltext(), $path);
        $pdfFileName = $article->getFulltext();
        if (!$journal && !$issue && !$article) {
            $this->addFlash('danger', 'Dergi, sayı veya makale hatalı.');
            return $this->redirectToRoute('admin_journal_management');
        }
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

            if ($data->getType() == ArticleTypeParam::TRANSLATE){
                foreach ($data->getTranslators() as $translator){
                    $this->entityManager->persist($translator);
                }
            }else {
                foreach ($article->getTranslators() as $translator){
                    $this->entityManager->remove($translator);
                }
            }

            $citations = $data->getCitations();

            foreach ($article->getCitations() as $existingCitation) {
                if (!$citations->contains($existingCitation)) {
                    $this->entityManager->remove($existingCitation);
                }
            }
            $index= 1;
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

            $this->entityManager->flush();
            $this->addFlash('success', 'Makale bilgileri güncellendi.');

            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
        }

        return $this->render('article_edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'pdfFile' => $pdfFileName,
            'article' => $article

        ]);
    }

    #[Route('article/{id}/{status}', name: 'article_pdf_error')]
    public function article_pdf_error($id, $status): Response
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
        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->addFlash('success', 'Makale Pdf Hatası Gönderilmiştir.');

        return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
    }

    #[Route('/article/{id}/delete/error', name: 'article_pdf_error_delete')]
    public function article_pdf_error_delete($id): Response
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
