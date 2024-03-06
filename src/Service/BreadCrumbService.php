<?php

namespace App\Service;

use Knp\Menu\FactoryInterface;


class BreadCrumbService
{
    private $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }
    //admin paneli
    public function createAdminDashboardBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Admin Paneli', ['route' => 'dashboard_admin']);

        return $menu;
    }
    // kullanıcı paneli
    public function createUserManagementBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Admin Paneli', ['route' => 'dashboard_admin']);
        $menu->addChild('Kullanıcılar', ['route' => 'admin_user_management']);

        return $menu;
    }
    //dergiye kullanıcı atama
    public function createJournalAssigmentBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Admin Paneli', ['route' => 'dashboard_admin']);
        $menu->addChild('Kullanıcılar', ['route' => 'admin_user_management']);
        $menu->addChild('Kullanıcı Atama', ['route' => '']);
        return $menu;

    }
    // dergi paneli
    public function createJournalManagementBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Admin Paneli', ['route' => 'dashboard_admin']);
        $menu->addChild('Dergiler', ['route' => 'admin_journal_management']);

        return $menu;
    }
    //yeni kullanıcı oluşturma
    public function createUserAddBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Admin Paneli', ['route' => 'dashboard_admin']);
        $menu->addChild(' Kullanıcılar', ['route' => 'admin_user_management']);
        $menu->addChild('Yeni Kullanıcı', ['route' => 'admin_user_add']);

        return $menu;
    }
    //kullanıcının atandığı dergiler
    public function createUserAssigned_listBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Admin Paneli', ['route' => 'dashboard_admin']);
        $menu->addChild('Kullanıcılar', ['route' => 'admin_user_management']);
        $menu->addChild('Kullancının Görev Aldığı Dergiler');

        return $menu;
    }

    //kullanıcı düzenleme
public function createUserEditBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Admin Paneli', ['route' => 'dashboard_admin']);
        $menu->addChild('Kullanıcılar', ['route' => 'admin_user_management']);
        $menu->addChild('Kullanıcı Düzenleme');

        return $menu;
    }

    // yeni dergi ekleme
    public function createJournalAddBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Admin Paneli', ['route' => 'dashboard_admin']);
        $menu->addChild('Dergiler', ['route' => 'admin_journal_management']);
        $menu->addChild('Yeni Dergi', ['route' => 'admin_journal_add']);

        return $menu;
    }

    // dergi Sayıları
    public function createJournalEditBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Admin Paneli ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild('Düzenle');

        return $menu;
    }

    // dergi Sayıları
    public function createJournalIssueBreadcrumb(FactoryInterface $factory, $journalName)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Admin Paneli ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName.' Sayıları');

        return $menu;
    }
    // Sayıdaki makaleler
    public function createArticleListBreadcrumb(FactoryInterface $factory, $journalName, $number,$id)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Admin Paneli ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName.' Sayıları ',['route' => 'journal_issues', 'routeParameters' => ['id' => $id]]);
        $menu->addChild(' '.$number.'. Sayı '.'Makaleleri');

        return $menu;
    }
    // makale düzenleme
    public function createArticleEditBreadcrumb(FactoryInterface $factory, $journalName, $number,$issueId, $journalId)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Admin Paneli ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName.' Sayıları ',['route' => 'journal_issues', 'routeParameters' => ['id' => $journalId]]);
        $menu->addChild($number.'. Sayı '.'Makaleleri', ['route' => 'articles_list', 'routeParameters' => ['id' => $issueId]]);
        $menu->addChild('Makale Düzenleme');

        return $menu;
    }
    //yeni makale ekleme
    public function createArticleAddBreadcrumb(FactoryInterface $factory, $journalName, $number,$issueId, $journalId)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Admin Paneli ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName.' '.$number.'. Sayısı',['route' => 'journal_issues', 'routeParameters' => ['id' => $journalId]]);
        $menu->addChild('Makaleler', ['route' => 'articles_list', 'routeParameters' => ['id' => $issueId]]);
        $menu->addChild('Makale oluştur');

        return $menu;
    }

    public function createArticlePdfUploadBreadcrumb(FactoryInterface $factory, $journalName, $number,$issueId, $journalId,$articleId)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Admin Paneli ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName.' '.$number.'. Sayısı',['route' => 'journal_issues', 'routeParameters' => ['id' => $journalId]]);
        $menu->addChild('Makaleler', ['route' => 'articles_list', 'routeParameters' => ['id' => $issueId]]);
        $menu->addChild('Makale Düzenleme', ['route' => 'article_edit', 'routeParameters' => ['id' => $articleId]]);
        $menu->addChild('Pdf Güncelleme');

        return $menu;
    }

//      yeni sayı
    public function createIssueAddBreadcrumb(FactoryInterface $factory, $journalName, $id)
    {
        $menu = $factory->createItem('root');
        $menu->addChild(' Admin Paneli ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName, ['route' => 'journal_issues', 'routeParameters' => ['id' => $id]]);
        $menu->addChild('Sayı Ekle');

        return $menu;
    }

    public function createEmptyBreadcrumb()
    {
        return $this->factory->createItem('root');
    }



}
