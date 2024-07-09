<?php

namespace App\Command;

use App\Entity\Articles;
use App\Entity\Authors;
use App\Entity\Citations;
use App\Entity\Issues;
use App\Entity\Journal;
use App\Entity\Translations;
use App\Params\ArticleStatusParam;
use App\Params\IssueStatusParam;
use App\Params\JournalStatusParam;
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
        $lockFile = sys_get_temp_dir() . '/ParseXml.lock';

        if (file_exists($lockFile)) {
            $output->writeln('Komut zaten çalışıyor.');
            return Command::FAILURE;
        }

        touch($lockFile);
        try {
            $journal = $this->entityManager->getRepository(Journal::class)->findOneBy(['status' => JournalStatusParam::WAITING]);
            if (!$journal) {
                $issue = $this->entityManager->getRepository(Issues::class)->findOneBy(['status' => IssueStatusParam::WAITING]);

                if (!$issue) {
                    $output->writeln('Bekleyen bir sayı bulunamadı.');
                    return Command::SUCCESS;
                } else {
                    $issue->setStatus(IssueStatusParam::EDIT_REQUIRED);
                    $this->entityManager->persist($issue);
                    $this->entityManager->flush();
                }
            }
            if ($journal) {
                $xmlFile = $journal->getXml();
            } else {
                $xmlFile = $issue->getXml();
            }


            if (!$xmlFile || !file_exists($xmlFile)) {
                $output->writeln('XML dosyası bulunamadı veya geçersiz');
                if ($journal) {
                    $journal->setStatus(JournalStatusParam::ERROR);
                    $this->entityManager->persist($journal);

                } else {
                    $issue->setErrors(['Xml Hatalı']);
                    $issue->setStatus(IssueStatusParam::ERROR);
                    $this->entityManager->persist($issue);
                }
                $this->entityManager->flush();
                return Command::FAILURE;
            }
            $xmlContent = file_get_contents($xmlFile);
            $xmlContent = str_replace('&', '&amp;', $xmlContent);


            $encoder = new XmlEncoder();
            $data = $encoder->decode($xmlContent, 'xml');
            if ($journal) {

                if ($this->validateJournalXml($data, $journal)) {

                    $output->writeln('XML başarılı şekilde veritabanına aktarılmıştır.');
                } else {
                    $journal->setStatus(JournalStatusParam::ERROR);
                    $this->entityManager->persist($journal);
                    $this->entityManager->flush();
                    return Command::FAILURE;
                }
            } else {
                if ($this->validateXml($data, $issue)) {

                    $output->writeln('XML başarılı şekilde veritabanına aktarılmıştır.');
                } else {
                    $issue->setStatus(IssueStatusParam::ERROR);
                    $this->entityManager->persist($issue);
                    $this->entityManager->flush();
                    return Command::FAILURE;
                }
            }

        } catch (\Exception $e) {
            $output->writeln('Beklenmedik bir hata oluştu: ' . $e->getMessage());
            return Command::FAILURE;
        } finally {
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
        return Command::SUCCESS;
    }

    protected function validateJournalXml($data, $journal)
    {
        // Yeni sayı oluşturma işlemi
        if (isset($data['issue'])) {
            $journal->setStatus(JournalStatusParam::TRANSFERSTAGE);
            $this->entityManager->persist($journal);
            foreach ($data['issue'] as $issueData) {
                $this->entityManager->flush();
                $newIssue = new Issues();
                $newIssue->setJournal($journal);

                // Sayı verilerini ayarla
                if (isset($issueData['volume'])) {
                    $newIssue->setVolume($issueData['volume']);
                }
                if (isset($issueData['year'])) {
                    $newIssue->setYear($issueData['year']);
                }
                if (isset($issueData['number'])) {
                    $newIssue->setNumber($issueData['number']);
                }

                if (isset($issueData['articles'])) {
                    if (empty($issueData['articles']['article'])) {
                        // Eğer articles etiketi boşsa
                        $newIssue->setStatus(IssueStatusParam::ERROR);
                    } else {
                        // Articles etiketi doluysa
                        if ($this->validateXml($issueData['articles'], $newIssue)) {
                            // Bu alanda başka işlemler yapılabilir.
                        }
                    }
                } else {
                    $newIssue->setStatus(IssueStatusParam::ERROR);
                }

                $this->entityManager->persist($newIssue);
                $this->entityManager->flush();
            }
            $journal->setStatus(JournalStatusParam::TRANSFERRED);
            $this->entityManager->persist($journal);
            $this->entityManager->flush();
            return true;
        } else {
            return false;
        }
    }



    protected function validateXml($data, $issue)
    {
        /** @var Issues $issue */
        if (isset($data['article'])) {
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
                            $newarticle->setStatus(ArticleStatusParam::ERROR);
                            $issue->setStatus(IssueStatusParam::ERROR);

                            $newarticle->setErrors(['PDF Hatalı.']);
                        } else {
                            $newarticle->setFulltext($destinationPath);
                        }
                    } else {
                        array_push($issueErrors, 'fulltext bulunamadı.');
                        $newarticle->setStatus(ArticleStatusParam::ERROR);
                        $issue->setStatus(IssueStatusParam::ERROR);
                        $newarticle->setErrors(['PDF Hatalı.']);

                    }

                    $firstpage = isset($article['firstpage']) ? $article['firstpage'] : '';
                    if (is_numeric($firstpage) || intval($firstpage) == $firstpage) {
                        $newarticle->setFirstPage($article['firstpage']);
                    }
                    $lastpage = isset($article['lastpage']) ? $article['lastpage'] : '';
                    if (is_numeric($lastpage) || intval($lastpage) == $lastpage) {
                        $newarticle->setLastPage($article['lastpage']);
                    }
                    $primaryLang = isset($article['primary-language']) ? trim($article['primary-language']) : '';

                    if (!is_string($primaryLang) || empty($primaryLang)) {
                        $newarticle->setPrimaryLanguage('tr');
                    } else {
                        $newarticle->setPrimaryLanguage($primaryLang);
                    }

                    $doi = isset($article['doi']) ? trim($article['doi']) : '';
                    if (is_string($doi) || $doi) {
                        $newarticle->setDoi($doi);
                    }

                    if (!empty($issueErrors)) {
                        $issue->setErrors($issueErrors);
                    }

                    //------------------------------------------------------------
                    // Validate translations
                    if (isset($article['translations'])) {
                        $this->validateTranslation($article['translations'], $newarticle, $errors);
                    }
                    // Validate authors
                    if (isset($article['authors'])) {
                        $this->validateAuthors($article['authors'], $newarticle, $errors);
                    }
                    //validate referance
                    if (isset($article['citations'])) {
                        $this->validateCitations($article['citations'], $newarticle, $errors);

                    }
//                if (!empty($errors)) {
////                    $newarticle->setErrors($errors);
//                }
                    if (!$issue->getStatus(IssueStatusParam::ERROR)) {
                        $issue->setStatus(IssueStatusParam::EDIT_REQUIRED);
                    }
                    if (!$newarticle->getFulltext()) {
                        $newarticle->setStatus(ArticleStatusParam::ERROR);
                    } else {
                        $newarticle->setStatus(ArticleStatusParam::EDIT_REQUIRED);
                    }
                    $this->entityManager->persist($newarticle);
                    $this->entityManager->persist($issue);
                    $this->entityManager->flush();

                } catch
                (LogicException $e) {
                    $issue->setErrors([$e->getMessage()]);

                    error_log('Makale doğrulama hatası: ' . $e->getMessage());
                    continue;
                }
            }
        } else {

            return false;
        }

        $this->entityManager->flush();
        return true;
    }

    private
    function validateTranslation($translations, $newarticle, &$errors)
    {
        if (isset($translations['translation'])) {
            if (isset($translations['translation']['locale'])) {
                $translations = [$translations['translation']];
            } else {
                $translations = $translations['translation'];
            }
        }
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
                $newtranslation->setTitle($this->specialChar($translation['title']));
            }

            $abstract = isset($translation['abstract']) ? trim($translation['abstract']) : '';
            if (empty($abstract) || !is_string($abstract)) {
                array_push($errors, "makale özeti hatalı veya eksik. ");
            } else {
                $newtranslation->setAbstract($this->specialChar($translation['abstract']));
            }

            $keywords = isset($translation['keywords']) ? trim($translation['keywords']) : '';
            if (empty($keywords) || !is_string($keywords)) {
                array_push($errors, "makale anahtar kelimeleri hatalı veya eksik. ");
            } else {
                $keywordsArray = explode(',', $translation['keywords']);
                $newtranslation->setKeywords($this->specialChar($keywordsArray));
            }

            $this->entityManager->persist($newtranslation);
        }
    }

    private
    function validateAuthors($authors, $newarticle, &$errors)
    {
        foreach ($authors as $author) {
            $newauthor = new Authors();
            $newauthor->setArticle($newarticle);

            $firstname = isset($author['firstname']) ? trim($author['firstname']) : '';
            if (empty($firstname) || !is_string($firstname)) {
                array_push($errors, "yazar ismi hatalı veya eksik.");
            } else {
                $newauthor->setFirstname($this->specialChar($author['firstname']));
            }

            $lastname = isset($author['lastname']) ? trim($author['lastname']) : '';
            if (empty($lastname) || !is_string($lastname)) {
                array_push($errors, "yazar soyismi hatalı veya eksik.");
            } else {
                $newauthor->setLastname($this->specialChar($author['lastname']));
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

//            $part = isset($author['part']) ? trim($author['part']) : '';
//            if (empty($part) || !is_string($author['part'])) {
//                array_push($errors, "yazar rolü hatalı veya eksik. ");
//            } else {
//$newauthor->setPart($author['part']);
//            }

            $this->entityManager->persist($newauthor);
        }
    }

    private
    function validateCitations($citations, $newarticle, &$errors)
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

    private
    function validateClient($fulltextFile, $issue, $issueErrors)
    {
        try {
            sleep(1);
            $client = new Client();
            $response = $client->get($fulltextFile, ['verify' => false, 'headers' => ['Accept' => 'application/pdf']]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
//                error_log('Tam metin URL\'i geçerli değil veya 200 OK durumu alınamadı.');
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

            $uploadPath = '/usr/share/nginx/data/journal/';

            $journalFolder = $uploadPath . $journalId;
            $filesystem = new Filesystem();

            if (!$filesystem->exists($journalFolder)) {
                $filesystem->mkdir($journalFolder, 0777);
            }

            $issueFolder = $journalFolder . '/' . $issueId;
            if (!$filesystem->exists($issueFolder)) {
                $filesystem->mkdir($issueFolder, 0777);
            }

            $destinationPath = $issueFolder . '/' . $fileName;

            if (pathinfo($destinationPath, PATHINFO_EXTENSION) !== 'pdf') {
                $destinationPath .= '.pdf';
            }
            file_put_contents($destinationPath, $pdfContent);

            return $destinationPath;
        } catch (RequestException $e) {
//            array_push($issueErrors, 'HTTP isteği sırasında bir hata oluştu: ' . $e->getMessage());
//            $issue->setErrors($issueErrors);
            $this->entityManager->persist($issue);
            return false;
        }
    }

    function specialChar($text)
    {
        return str_replace(
            array("&#13;", '&rdquo;', '&ldquo;', "\r\n", '&#13;', '&ndash;', '', '&nbsp;', ' ', '&amp;', '\u0026amp;', '&Agrave;', '&Aacute;', '&Acirc;', '&Atilde;', '&Auml;', '&Aring;', '&agrave;', '&aacute;', '&acirc;', '&atilde;', '&auml;', '&aring;', '&AElig;', '&aelig;', '&szlig;', '&Ccedil;', '&ccedil;', '&Egrave;', '&Eacute', '&Ecirc;', '&Euml;', '&egrave;', '&eacute;', '&ecirc;', '&euml;', '&#131;', '&Igrave;', '&Iacute;', '&Icirc;', '&Iuml;', '&igrave;', '&iacute;', '&icirc;', '&iuml;', '&Ntilde;', '&ntilde;', '&Ograve;', '&Oacute;', '&Ocirc;', '&Otilde;', '&Ouml;', '&ograve;', '&oacute;', '&ocirc;', '&otilde;', '&ouml;', '&Oslash;', '&oslash;', '&#140;', '&#156;', '&#138;', '&#154;', '&Ugrave;', '&Uacute;', '&Ucirc;', '&Uuml;', '&ugrave;', '&uacute;', '&ucirc;', '&uuml;', '&#181;', '&#215;', '&Yacute;', '&#159;', '&yacute;', '&yuml;', '&#176;', '&lt;', '&gt;', '&#177;', '&#171;', '&#187;', '&#161;', '&#xD6;', '&#xFC;', '&#xE7;', '&#x131;', '&#x11F;', '&#x130;', '&#x15F;'),
            array('', '”', '“', " ", '', '-', '', '', '', '&', '&', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'à', 'á', 'â', 'ã', 'ä', 'å', 'Æ', 'æ', 'ß', 'Ç', 'ç', 'È', 'É', 'Ê', 'Ë', 'è', 'é', 'ê', 'ë', 'ƒ', 'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï', 'Ñ', 'ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'ò', 'ó', 'ô', 'õ', 'ö', 'Ø', 'ø', 'Œ', 'œ', 'Š', 'š', 'Ù', 'Ú', 'Û', 'Ü', 'ù', 'ú', 'û', 'ü', 'µ', '×', 'Ý', 'Ÿ', 'ý', 'ÿ', '°', '<', '>', '±', '«', '»', 'i', 'Ö', 'ü', 'ç', 'ı', 'ğ', 'İ', 'ş'), $text);

    }
}
