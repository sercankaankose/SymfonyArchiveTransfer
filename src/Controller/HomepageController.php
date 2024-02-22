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
use App\Params\IssueStatusParam;
use App\Params\RoleParam;
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

    #[Route('journal/issue/{id}/articles', name: 'articles_list')]
    public function articleList($id, FactoryInterface $factory): Response
    {
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();

        $breadcrumb = $this->breadcrumbService->createArticle_listBreadcrumb($factory, $journal->getName(), $issue->getNumber(), $journal->getId());

        $text = "Abbott, R. A., Croudace, T. J., Ploubidis, G. B., Kuh, D., Richards, M., & Huppert, F. A. (2008). The
relationship between early personality and midlife psychological well-being: Evidence from
a UK birth cohort study. Social Psychiatry and Psychiatric Epidemiology, 43, 679-687. https://
doi.org/10.1007/s00127-008-0355-8
Andreassen, C. S., Griffiths, M. D., Pallesen, S., Bilder, R. M., Torsheim, T., & Aboujaoude, E. (2015).
The Bergen Shopping Addiction Scale: Reliability and validity of a brief screening test. Frontiers in Psychology, 6, 1374. DOI: 10.3389/fpsyg.2015.01374.
Aston, J., Vipond, O., Virgin, K., & Youssouf, O. (2020). Retail e-commerce and COVID-19: How online shopping opened doors while many were closing. Statistics Canada= Statistique Canada.
https://www150.statcan.gc.ca/n1/en/pub/45-28. DOI: 0001/2020001/article/00064-eng.
pdf?st=k6eLZftr.
Atak, H. (2013). On-Maddeli Kişilik Ölçeği›nin Türk Kültürü’ne Uyarlanması. Archives of Neuropsychiatry/Noropsikiatri Arsivi, 50(4), 312-319. DOI: 10.4274/npa.y6128.
Averill, J. R., & More, T. A. (1993). Happiness. In M. Lewis & J. M. Haviland (Eds.), Handbook of
Emotions (pp. 617–629). The Guilford Press.
Bozdağ, Y., & Alkar, Ö. Y. (2018). Bergen Alışveriş Bağımlılığı Ölçeği’nin kompülsif çevrimiçi satın
alma davranışına uyarlanması. Bağımlılık Dergisi, 19(2), 23-34.
Brunelle, C., & Grossman, H. (2022). Predictors of online compulsive buying: The role of personality and mindfulness. Personality and Individual Differences, 185, 111237. DOI: 10.1016/j.
paid.2021.111237.
Chida, Y., & Steptoe, A. (2008). Positive psychological well-being and mortality: a quantitative 
review of prospective observational studies. Psychosomatic Medicine, 70(7), 741-756. DOI:
10.1097/PSY.0b013e31818105ba.
Claes, L., & Müller, A. (2017). Resisting temptation: is compulsive buying an expression of personality deficits? Current Addiction Reports, 4(3), 237-245. DOI: 10.1007/s40429-017-0152-0.
Costa Jr, P. T., & McCrae, R. R. (1992). Four ways five factors are basic. Personality and Individual
Differences, 13(6), 653-665. DOI: 10.1016/0191-8869(92)90236-I.
De Neve, J. E., Diener, E., Tay, L. & Xuereb, C. (2013). The objective benefits of subjective well-being. World Happiness Report. http://eprints.lse.ac.uk/51669/1/dp1236.pdf.
DeYoung, C. G.(2015).Cybernetic big five theory.Journal of Research in Personality, 56, 33– 58.DOI:
10.1016/j.jrp.2014.07.004.
Diener, E., Kesebir, P., & Tov, W. (2009). Happiness. Handbook of Individual Differences in Social
Behavior, 147-160.
Diener, E., Ng, W., Harter, J., & Arora, R. (2010). Wealth and happiness across the world: Material
prosperity predicts life evaluation, whereas psychosocial prosperity predicts positive feeling. Journal of Personality and Social Psychology, 99(1), 52-61.DOI: 10.1037/a0018066.
Diener, E., Heintzelman, S. J., Kushlev, K., Tay, L., Wirtz, D., Lutes, L. D., & Oishi, S. (2017). Findings all psychologists should know from the new science on subjective well-being. Canadian Psychology/psychologie canadienne, 58(2), 87- 105. DOI: http:// dx.doi.org/10.1037/
cap0000063.
Dolan, P., Peasgood, T., & White, M. (2008). Do we really know what makes us happy? A review of
the economic literature on the factors associated with subjective well-being. Journal of Economic Psychology, 29(1), 94-122. DOI: 10.1016/j.joep.2007.09.001.
Dubey, M. J., Ghosh, R., Chatterjee, S., Biswas, P., Chatterjee, S., & Dubey, S. (2020). COVID-19
and addiction. Diabetes & Metabolic Syndrome: Clinical Research & Reviews, 14(5), 817-823.
DOI: 10.1016/j.dsx.2020.06.008.
Faber, R., J. & O’Guinn, T.,C. (1992). A Clinical Screener for Compulsive Buying, Journal of Consumer Research, 19(3), 459–469. DOI: 10.1086/209315.
Gosling, S. D., Rentfrow, P. J., & Swann Jr, W. B. (2003). A very brief measure of the Big-Five personality domains. Journal of Research in Personality, 37(6), 504-528. DOI: 10.1016/S0092-
6566(03)00046-1.
Griffiths, M.D. & Larkin, M. (2004). Conceptualizing addiction: The case for a “complex systems”
account, Addiction Research & Theory, 12(2), 99-102, DOI: 10.1080/1606635042000193211.
Griffiths, M. (2005). A ‘components’ model of addiction within a biopsychosocial framework, Journal of Substance Use, 10(4), 191-197, DOI: 10.1080/14659890500114359.
Griffiths, M. D., Andreassen, C. S., Pallesen, S., Bilder, R. M., Torsheim, T., & Aboujaoude, E. (2016).
When is a new scale not a new scale? The case of the Bergen Shopping Addiction Scale and
the Compulsive Online Shopping Scale. International Journal of Mental Health and Addiction, 14(6), 1107-1110. DOI: 10.1007/s11469-016-9711-1.
Harnish, R. J., Roche, M. J., & Bridges, K. R. (2021). Predicting compulsive buying from pathological personality traits, stressors, and purchasing behavior. Personality and Individual Differences, 177, 110821. DOI: 10.1016/j.paid.2021.110821.
Helliwell, J. F., Layard, R., Sachs, J. D., & Neve, J. E. D. (2021). World Happiness Report 2021.
https://www.wellbeingintlstudiesrepository.org/cgi/viewcontent.cgi?article=1004&context=hw_happiness.
Hone, L. C., Jarden, A., Schofield, G. M. ve Duncan, S. (2014). Measuring flourishing: The impact of
operational definitions on the prevalence of high levels of wellbeing. International Journal of
Wellbeing, 4(1), 62-90. DOI:10.5502/ijw.v4i1.4.
Horváth, C., & Adıgüzel, F. (2018). Shopping enjoyment to the extreme: Hedonic shopping motivations and compulsive buying in developed and emerging markets. Journal of Business
Research, 86, 300–310. DOI: 10.1016/j.jbusres.2017.07.013.
John, OP, Naumann, L., & Soto, CJ (2008). Paradigm shift to the integrative Big Five taxonomy:
History, measurement, and conceptual issues. In OP John, RW Robins, & L. A. Pervin (Eds.),
Handbook of personality: Theory and research (pp. 114-158). New York, NY: Guilford.
Joshanloo, M. (2023). Reciprocal relationships between personality traits and psychological well-being. British Journal of Psychology, 114(1), 54-69.DOI: 10.1111/bjop.12596.
Khamis, H. & Kepler, M. (2010). Sample size in multiple regression: 20 + 5k. Journal of Applied
Statistical Science. 17. 505-517.
Koch, J., Frommeyer, B., & Schewe, G. (2020). Online shopping motives during the COVID-19
pandemic- lessons from the crisis. Sustainability, 12(24), 10247. https://doi.org/10.3390/
su122410247
Lucidi, F., Girelli, L., Chirico, A., Alivernini, F., Cozzolino, M., Violani, C., & Mallia, L. (2019). Personality traits and attitudes toward traffic safety predict risky behavior across young, adult,
and older drivers. Frontiers in Psychology, 10, 536. DOI:10.3389/fpsyg.2019.00536.
Lyubomirsky, S., King, L., & Diener, E. (2005). The benefits of frequent positive affect: Does
happiness lead to success?. Psychological Bulletin, 131(6), 803-885.DOI: 10.1037/0033-
2909.131.6.803.
McCrae, R. R., & Costa, P. T. (1991). Adding liebe und arbeit: The full five-factor model and well-Being. Personality and Social Psychology Bulletin, 17(2), 227–232. DOI:
10.1177/014616729101700217
McElroy, S.,L, Keck, P.E., Pope, H.,G., Smith, J.,M., & Strakowski, S.,M. (1994). Compulsive buying:
A report of 20 cases. Journal of Clinical Psychiatry, 55(6), 242-8. PMID: 8071278.
Mikołajczak-Degrauwe, K., Brengman, M., Wauters, B., & Rossi, G. (2012). Does personality affect
compulsive buying? An application of the big five personality model. In Psychology-Selected
Papers. IntechOpen.
Mowen, J. C., & Spears, N. (1999). Understanding compulsive buying among college students: A
hierarchical approach. Journal of Consumer Psychology, 8(4), 407-430.
Mowen, J. (2000). The 3M model of motivation and personality: Theory and empirical applications
to consumer behavior. Boston, Dordrecht, London: Kluwer Academic Publishers.
Müeller, A., Claes, L., Mitchell, J. E., Wonderlich, S. A., Crosby, R. D., & De Zwaan, M. (2010). Personality prototypes in individuals with compulsive buying based on the Big Five Model. Behaviour Research and Therapy, 48(9), 930-935. DOI: 10.1016/j.brat.2010.05.020.
Müller, A., Mitchell, J. E., & de Zwaan, M. (2015). Compulsive buying. The American Journal on Addictions, 24(2), 132-137. DOI: 10.1111/ajad.12111.
Müller, A., Claes, L., Birlin, A., Georgiadou, E., Laskowski, N. M., Steins-Loeber, S., Brand, M. & de
Zwaan, M. (2021). Associations of buying-shopping disorder symptoms with identity confusion, materialism, and socially undesirable personality features in a community sample. European Addiction Research, 27(2), 142-150. DOI:10.1159/000511078.
Niedermoser, D. W., Petitjean, S., Schweinfurth, N., Wirz, L., Ankli, V., Schilling, H., Zueger, C., Meyer, M., Poespodihardjo, R., Wiesbeck, G., & Walter, M. (2021). Shopping addiction: A brief
review. Practice Innovations, 6(3), 199–207. DOI:10.1037/pri0000152.
Otero-López, J.M., Santiago, M.J., & Castro, M., C. (2021). Big Five Personality Traits, Coping Strategies and Compulsive Buying in Spanish University Students. International Journal of Environ-
mental Research and Public Health. 18(2):821- . DOI:10.3390/ijerph18020821.
Pontin, E., Schwannauer, M., Tai, S. ve Kinderman, P. (2013). A UK validation of a general measure
of subjective well-being: the modified BBC subjective well-being scale (BBC-SWB). Health and
Quality of Life Outcomes, 11(1), 150. DOI: 10.1186/1477-7525-11-150.
Rook, D.W., & Fisher, R.J. (1995). Normative influences on impulsive buying behavior, Journal of
Consumer Research, 22(3), 305-313. DOI: 10.1086/209452.
Ryan, R. M., & Deci, E. L. (2000). Self-determination theory and the facilitation of intrinsic motivation, social development, and well-being. American Psychologist, 55(1), 68-78. DOI:
10.1037/0003-066X.55.1.68.
Ryff, C. D. (1989). Happiness is everything, or is it? Explorations on the meaning of psychological
well-being. Journal of Personality and Social Psychology, 57(6), 1069-1081.
San-Martín S, Jimenez N, Camarero C, San-José R. (2020). The Path between Personality, Self-Efficacy, and Shopping Regarding Games Apps. Journal of Theoretical and Applied Electronic
Commerce Research. 15(2), 59-75. DOI: 10.4067/S0718-18762020000200105.
Scherhorn, G., Reisch, L. A., & Raab, G. (1990). Addictive buying in West Germany: An empirical
study. Journal of Consumer Policy, 13(4), 355-387. DOI:10.1007/BF00412336.
Schmitt, D. P., Allik, J., McCrae, R. R., & Benet-Martínez, V. (2007). The geographic distribution
of Big Five personality traits: Patterns and profiles of human self-description across 56 nations. Journal of Cross-Cultural Psychology, 38(2), 173-212. DOI: 10.1177/0022022106297299.
Seligman, M.E.P. (2011). Flourish: A visionary new understanding of happiness and well-being. New
York: Free Press.
Soper, D.S. (2022). A-priori Sample Size Calculator for Multiple Regression [Software]. Available
from https://www.danielsoper.com/statcalc.
Tabachnick, B. G., & Fidell, L. S., (2019). Using Multivariate Statistics.7th Ed. Boston, MA: Pearson.
Tarka, P., Kukar-Kinney, M., & Harnish, R. J. (2022). Consumers’ personality and compulsive buying behavior: The role of hedonistic shopping experiences and gender in mediating-moderating relationships. Journal of Retailing and Consumer Services, 64, 102802. DOI: 10.1016/j.
jretconser.2021.102802.
Uzarska, A., Czerwiński, S.K. & Atroszko, P.A. (2021). Measurement of shopping addiction and
its relationship with personality traits and well-being among Polish undergraduate students. Current Psychology. DOI: 10.1007/s12144-021-01712-9.
Ünübol, B., Ünsalver, B.Ö., Ünübol, H. et al. The prevalence and psychological relation of problem shopping: data from a large-scale sample from Turkey. BMC Psychol 10, 1 (2022). https://doi.org/10.1186/
s40359-021-00711-6.
Wang, C.C., & Yang, H.W. (2008). Passion for online shopping: the influence of personality and compulsive
buying. Social Behavior and Personality, 36(5), 693-706. DOI: 10.2224/sbp.2008.36.5.693.";
//        dd($text);
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
            $this->entityManager->flush();
            $this->addFlash('success', 'Makale bilgileri güncellendi.');

            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
        }
        return $this->render('article_edit.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'pdfFile' => $pdfFileName,
            'article' => $article,
        ]);
    }

    #[Route('journal/{id}/issue/save', name: 'issue_save')]
    public function issueSave($id): Response
    {
        $issue = $this->entityManager->getRepository(Issues::class)->find($id);
        $journal = $issue->getJournal();
        $article = $this->entityManager->getRepository(Articles::class)->findOneBy([
           'issue'=>$issue,
           'status'=> ArticleStatusParam::EDIT_REQUIRED
        ]);

        if ($article){
            $this->addFlash('danger','Düzenlenmemiş Makale Var');
            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
        }
        $issue->setStatus(IssueStatusParam::EDITED);
        $this->entityManager->persist($issue);
        $this->entityManager->flush();
        return $this->redirectToRoute('journal_issues', ['id' => $journal->getId()]);

    }

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
            return $this->redirectToRoute('article_edit', ['id' => $article->getId()]);
        } else {
            return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);

        }

    }

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
        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->addFlash('success', 'Makale Pdf Hatası Gönderilmiştir.');

        return $this->redirectToRoute('articles_list', ['id' => $issue->getId()]);
    }

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
