<?php

namespace App\Command;

use App\Entity\Articles;
use App\Entity\Authors;
use App\Entity\Citations;
use App\Entity\Issues;
use App\Entity\Translations;
use App\Params\ArticleStatusParam;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Cache\Exception\LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'ParseXml',
    description: 'xml dosyasını
      Parçala',
)]
class ParseXmlCommand extends Command
{
    private $entityManager;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;


        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Xml Dosyasını Veritabanına aktarma');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $issue = $this->entityManager->getRepository(Issues::class)->findOneBy(['status' => 'waiting']);

        if (!$issue) {
            $output->writeln('Bekleyen bir sayı bulunamadı.');
            return Command::SUCCESS;
        }
        $xmlFile = $issue->getXml();
//        $xmlFile = '/home/vboxuser/ArchiveTransfer/'.$xmlFile;

        if (!$xmlFile || !file_exists($xmlFile)) {
            $output->writeln('XML dosyası bulunamadı veya geçersiz');
            $issue->setErrors(['Xml Hatalı']);
            $issue->setStatus(ArticleStatusParam::ERROR);
            $this->entityManager->persist($issue);
            $this->entityManager->flush();
            return Command::FAILURE;
        }
        $xmlContent = file_get_contents($xmlFile);
        $xmlContent = str_replace('&', '&amp;', $xmlContent);


        $encoder = new XmlEncoder();
        $data = $encoder->decode($xmlContent, 'xml');

        if ($this->validateXml($data, $issue)) {
            $output->writeln('XML başarılı şekilde veritabanına aktarılmıştır.');
        } else {
            $issue->setStatus('XmlValidateFail');
            $this->entityManager->persist($issue);
            $this->entityManager->flush();
            return Command::FAILURE;
        }


        return Command::SUCCESS;
    }

    protected function validateXml($data, $issue)
    {
        foreach ($data['article'] as $article) {
            try {
                $errors = [];
                $issueErrors = [];
                $newarticle = new Articles();
                $newarticle->setIssue($issue);
                $newarticle->setJournal($issue->getJournal());

                if (isset($article['fulltext-file']) && is_string($article['fulltext-file'])) {
                    $fulltextFileUrl = $article['fulltext-file'];
                    $destinationPath = $this->validateClient($fulltextFileUrl, $issue, $issueErrors);
                    if (empty($fulltextFileUrl) || !$destinationPath) {
                        array_push($issueErrors, 'Makale eklenemedi: Tam metin dosyası bulunamadı veya geçersiz.');
                        $issue->setErrors($issueErrors);
                        $this->entityManager->persist($issue);
                        continue;
                    }

                    $newarticle->setFulltext($destinationPath);
                } else {
                    array_push($issueErrors, 'fulltext bulunamadı.');
                    continue;
                }

                $firstpage = isset($article['firstpage']) ? $article['firstpage'] : '';
                if (!is_numeric($firstpage) || intval($firstpage) != $firstpage) {
                    array_push($issueErrors, "ilk sayfa bilgisi hatalı.");
                } else {
                    $newarticle->setFirstPage($article['firstpage']);
                }
                $lastpage = isset($article['lastpage']) ? $article['lastpage'] : '';
                if (!is_numeric($lastpage) || intval($lastpage) != $lastpage) {
                    array_push($issueErrors, "son sayfa bilgisi hatalı.");

                } else {
                    $newarticle->setLastPage($article['lastpage']);
                }

                $primaryLang = isset($article['primary-language']) ? trim($article['primary-language']) : '';
                if (!is_string($primaryLang) || empty($primaryLang)) {
                    error_log('birincil dil hatalı');
                    array_push($issueErrors, "Birincil dil bilgisini gözden geçirin.");

//                } else {
//                    switch (strtolower($primaryLang)) {
//                        case 'tr':
//                            return '001';
//                        case 'en':
//                            return '002';
//                        case 'ge':
//                            return '003';
//                        case 'es':
//                            return '004';
//                        case 'ar':
//                            return '005';
//                        case 'ru':
//                            return '006';
//                        case 'fa':
//                            return '007';
//                        default:
//                            return '';
//                    }
                }
                if (empty($primaryLang)) {
                    array_push($issueErrors, "Geçersiz birincil dil: " . $primaryLang);
                    $newarticle->setPrimaryLanguage('001');
                } else {
                    $newarticle->setPrimaryLanguage($primaryLang);
                }

                $doi = isset($article['doi']) ? trim($article['doi']) : '';
                if (!is_string($doi) || empty($doi)) {
                    array_push($issueErrors, "doi bilgisi hatalı. ");
                } else {
                    $newarticle->setDoi($article['doi']);
                }

                if (!empty($issueErrors)) {
                    $issue->setErrors($issueErrors);
                }
                //------------------------------------------------------------
                // Validate translations
                foreach ($article['translations'] as $translations) {
                    $this->validateTranslation($translations, $newarticle, $errors);
                }
                // Validate authors
                foreach ($article['authors'] as $authors) {
                    $this->validateAuthors($authors, $newarticle, $errors);
                }
                //validate referance
                foreach ($article['citations'] as $citations) {
                    $this->validateCitations($citations, $newarticle, $errors);
                }
                if (!empty($errors)) {
                    $newarticle->setErrors($errors);
                }

                $issue->setStatus(ArticleStatusParam::EDIT_REQUIRED);
                $newarticle->setStatus(ArticleStatusParam::EDIT_REQUIRED);
                $this->entityManager->persist($newarticle);
                $this->entityManager->persist($issue);
                $this->entityManager->flush();

            } catch (LogicException $e) {
                $issue->setErrors([$e->getMessage()]);
                error_log('Makale doğrulama hatası: ' . $e->getMessage());
                continue;
            }
        }
        $this->entityManager->flush();
        return true;
    }

    private function validateTranslation($translations, $newarticle, &$errors)
    {
        foreach ($translations as $translation) {

            $newtranslation = new Translations();
            $newtranslation->setArticle($newarticle);


            $locale = isset($translation['locale']) ? trim($translation['locale']) : '';
            if (empty($locale) || !is_string($locale)) {
                array_push($errors, "makale dili hatalı veya eksik. ");
            } else {
                $newtranslation->setLocale($translation['locale']);
            }

            $title = isset($translation['title']) ? trim($translation['title']) : '';
            if (empty($title) || !is_string($title)) {
                array_push($errors, "makale başlığı hatalı veya eksik. ");
            } else {
                $newtranslation->setTitle($translation['title']);
            }

            $abstract = isset($translation['abstract']) ? trim($translation['abstract']) : '';
            if (empty($abstract) || !is_string($abstract)) {
                array_push($errors, "makale özeti hatalı veya eksik. ");
            } else {
                $newtranslation->setAbstract($translation['abstract']);
            }

            $keywords = isset($translation['keywords']) ? trim($translation['keywords']) : '';
            if (empty($keywords) || !is_string($keywords)) {
                array_push($errors, "makale anahtar kelimeleri hatalı veya eksik. ");
            } else {
                $keywordsArray = explode(',', $translation['keywords']);
                $newtranslation->setKeywords($keywordsArray);
            }

            $this->entityManager->persist($newtranslation);
        }

    }

    private function validateAuthors($authors, $newarticle, &$errors)
    {
        foreach ($authors as $author) {
            $newauthor = new Authors();
            $newauthor->setArticle($newarticle);

            $firstname = isset($author['firstname']) ? trim($author['firstname']) : '';
            if (empty($firstname) || !is_string($firstname)) {
                array_push($errors, "yazar ismi hatalı veya eksik.");
            } else {
                $newauthor->setFirstname($author['firstname']);
            }

            $lastname = isset($author['lastname']) ? trim($author['lastname']) : '';
            if (empty($lastname) || !is_string($lastname)) {
                array_push($errors, "yazar soyismi hatalı veya eksik.");
            } else {
                $newauthor->setLastname($author['lastname']);
            }

            $institute = isset($author['institute']) ? trim($author['institute']) : '';
            if (empty($institute) || !is_string($author['institute'])) {
                array_push($errors, "kurum ismi hatalı veya eksik. ");
            } else {
                $newauthor->setInstitute($author['institute']);
            }

            $orcId = isset($author['orcId']) ? trim($author['orcId']) : '';
            if (empty($orcId) || !preg_match('/\d{4}-\d{4}-\d{4}-\d{4}$/', $orcId)) {
                array_push($errors, "orc Id hatalı veya eksik. ");
            } else {
                $newauthor->setOrcId($author['orcId']);
            }

            $email = isset($author['email']) ? trim($author['email']) : '';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "email hatalı veya eksik. ");
            } else {
                $newauthor->setEmail($author['email']);
            }

            $part = isset($author['part']) ? trim($author['part']) : '';
            if (empty($part) || !is_string($author['part'])) {
                array_push($errors, "yazar rolü hatalı veya eksik. ");
            } else {
                $newauthor->setPart($author['part']);
            }

            $this->entityManager->persist($newauthor);
        }
    }

    private function validateCitations($citations, $newarticle, &$errors)
    {
        foreach ($citations as $citation) {
            $newcitation = new Citations();
            $newcitation->setArticle($newarticle);

            $value = isset($citation['value']) ? trim($citation['value']) : '';
            $value = str_replace(["\r", "\n"], '', $value);
            if (empty($value) || !is_string($value)) {
                array_push($errors, "referans sayısı hatalı veya eksik. ");
            } else {
                $newcitation->setReferance($citation['value']);
            }

            $row = isset($citation['row']) ? trim($citation['row']) : '';
            if (!is_numeric($row) || intval($row) != $row) {
                array_push($errors, "referans hatalı veya eksik. ");
            } else {
                $newcitation->setRow(intval($citation['row']));
            }

            $this->entityManager->persist($newcitation);
        }
    }

    private function validateClient($fulltextFile, $issue, $issueErrors)
    {
        try {
            sleep(1);
            $client = new Client();
            $response = $client->get($fulltextFile, ['verify' => false, 'headers' => ['Accept' => 'application/pdf']]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                error_log('Tam metin URL\'i geçerli değil veya 200 OK durumu alınamadı.');
                return false;
            }
            $pdfContent = $response->getBody()->getContents();
            /** @var Issues $issue */

            $journalId = $issue->getJournal()->getId();
            $issueId = $issue->getId();
            $issueYear = $issue->getYear();
            $issueNumber = $issue->getNumber();

            $uniqName = bin2hex(random_bytes(4));

            $fileName = sprintf('%s-%s-%s-%s-%s.pdf', $journalId, $issueId, $issueYear, $issueNumber, $uniqName);
            $fileName = pathinfo($fileName, PATHINFO_FILENAME);

            $uploadPath = 'var/journal/';

            $journalFolder = $uploadPath . $journalId;
            $filesystem = new Filesystem();

            if (!$filesystem->exists($journalFolder)) {
                $filesystem->mkdir($journalFolder);
            }

            $issueFolder = $journalFolder . '/' . $issueId;
            if (!$filesystem->exists($issueFolder)) {
                $filesystem->mkdir($issueFolder);
            }

            $destinationPath = $issueFolder . '/' . $fileName;

            if (pathinfo($destinationPath, PATHINFO_EXTENSION) !== 'pdf') {
                $destinationPath .= '.pdf';
            }
            file_put_contents($destinationPath, $pdfContent);

            return $destinationPath;
        } catch (RequestException $e) {
            error_log('HTTP isteği sırasında bir hata oluştu: ' . $e->getMessage());
            array_push($issueErrors, 'HTTP isteği sırasında bir hata oluştu: ' . $e->getMessage());
            $issue->setErrors($issueErrors);
            $this->entityManager->persist($issue);
            return false;
        }
    }
}