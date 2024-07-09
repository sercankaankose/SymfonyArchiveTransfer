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
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);

        return $menu;
    }

    // kullanıcı paneli
    public function createUserManagementBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);
        $menu->addChild('Kullanıcılar', ['route' => 'admin_user_management']);

        return $menu;
    }

    //dergiye kullanıcı atama
    public function createJournalAssigmentBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);
        $menu->addChild('Kullanıcılar', ['route' => 'admin_user_management']);
        $menu->addChild('Kullanıcı Atama', ['route' => '']);
        return $menu;

    }

    // admin dergi paneli
    public function createJournalManagementBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);
        $menu->addChild('Dergiler', ['route' => 'admin_journal_management']);

        return $menu;
    }

    //yeni kullanıcı oluşturma
    public function createUserAddBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);
        $menu->addChild(' Kullanıcılar', ['route' => 'admin_user_management']);
        $menu->addChild('Yeni Kullanıcı', ['route' => 'admin_user_add']);

        return $menu;
    }

    //kullanıcının atandığı dergiler
    public function createUserAssigned_listBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);
        $menu->addChild('Kullanıcılar', ['route' => 'admin_user_management']);
        $menu->addChild('Kullancının Görev Aldığı Dergiler');

        return $menu;
    }

    //kullanıcı düzenleme
    public function createUserEditBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);
        $menu->addChild('Kullanıcılar', ['route' => 'admin_user_management']);
        $menu->addChild('Kullanıcı Düzenleme');

        return $menu;
    }

    // yeni dergi ekleme
    public function createJournalAddBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);
        $menu->addChild('Dergiler', ['route' => 'admin_journal_management']);
        $menu->addChild('Yeni Dergi', ['route' => 'admin_journal_add']);

        return $menu;
    }

    // admin dergi düzenleme
    public function createJournalEditBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);
        $menu->addChild('Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild('Düzenle');

        return $menu;
    }
    public function createJournalCheckExportBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Panel', ['route' => 'dashboard_admin']);
        $menu->addChild('Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild('Dışa Aktarım Kontrol');

        return $menu;
    }

    //  editör dergi düzenleme
    public function createEditorJournalEditBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild('Editörlük Yaptığım Dergiler ', ['route' => 'editor_journal_management']);
        $menu->addChild('Düzenle');

        return $menu;
    }
    public function createEditorCheckExportBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild('Editörlük Yaptığım Dergiler ', ['route' => 'editor_journal_management']);
        $menu->addChild('Dışa Aktarım Kontrolü');

        return $menu;
    }
    public function createOperatorJournalEditBreadcrumb()
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild('Operatörlük Yaptığım Dergiler ', ['route' => 'editor_journal_management']);
        $menu->addChild('Dışa Aktarım Kontrolü');

        return $menu;
    }

    // dergi Sayıları
    public function createJournalIssueBreadcrumb(FactoryInterface $factory, $journalName)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Panel ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName . ' Sayıları');

        return $menu;
    }

    // Sayıdaki makaleler
    public function createArticleListBreadcrumb(FactoryInterface $factory, $journalName, $number, $id)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Panel ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName . ' Sayıları ', ['route' => 'journal_issues', 'routeParameters' => ['id' => $id]]);
        $menu->addChild(' ' . $number . '. Sayı ' . 'Makaleleri');

        return $menu;
    }

    // makale düzenleme
    public function createArticleEditBreadcrumb(FactoryInterface $factory, $journalName, $number, $issueId, $journalId)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Panel ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName . ' Sayıları ', ['route' => 'journal_issues', 'routeParameters' => ['id' => $journalId]]);
        $menu->addChild($number . '. Sayı ' . 'Makaleleri', ['route' => 'articles_list', 'routeParameters' => ['id' => $issueId]]);
        $menu->addChild('Makale Düzenleme');

        return $menu;
    }

    //operator makale düzenleme
    public function createOperatorArticleEditBreadcrumb(FactoryInterface $factory, $journalName, $number, $issueId, $journalId)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild(' Operatörlük Yaptığım Dergiler ', ['route' => 'operator_journal_management']);
        $menu->addChild($journalName . ' Sayıları', ['route' => 'operator_journal_issues', 'routeParameters' => ['id' => $journalId]]);
        $menu->addChild($number . '. Sayı ' . 'Makaleleri', ['route' => 'operator_articles_list', 'routeParameters' => ['id' => $issueId]]);
        $menu->addChild('Makale Düzenleme');

        return $menu;
    }

    // editör makale düzenleme
    public function createEditorArticleEditBreadcrumb(FactoryInterface $factory, $journalName, $number, $issueId, $journalId)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild(' Editörlük Yaptığım Dergiler ', ['route' => 'editor_journal_management']);
        $menu->addChild($journalName . ' Sayıları', ['route' => 'editor_journal_issues', 'routeParameters' => ['id' => $journalId]]);
        $menu->addChild($number . '. Sayı ' . 'Makaleleri', ['route' => 'editor_articles_list', 'routeParameters' => ['id' => $issueId]]);
        $menu->addChild('Makale Düzenleme');

        return $menu;
    }

    //yeni makale ekleme
    public function createArticleAddBreadcrumb(FactoryInterface $factory, $journalName, $number, $issueId, $journalId)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Panel ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName . ' ' . $number . '. Sayısı', ['route' => 'journal_issues', 'routeParameters' => ['id' => $journalId]]);
        $menu->addChild('Makaleler', ['route' => 'articles_list', 'routeParameters' => ['id' => $issueId]]);
        $menu->addChild('Makale oluştur');

        return $menu;
    }

    //editor yeni makale ekleme
    public function createEditorArticleAddBreadcrumb(FactoryInterface $factory, $journalName, $number, $issueId, $journalId,$last)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild(' Editörlük Yaptığım Dergiler ', ['route' => 'editor_journal_management']);
        $menu->addChild($journalName . ' Sayıları', ['route' => 'editor_journal_issues', 'routeParameters' => ['id' => $journalId]]);
        $menu->addChild($number . '. Sayı ' . 'Makaleleri', ['route' => 'editor_articles_list', 'routeParameters' => ['id' => $issueId]]);
        $menu->addChild($last);
        return $menu;
    }

    //pdf düzenleme
    public function createArticlePdfUploadBreadcrumb(FactoryInterface $factory, $journalName, $number, $issueId, $journalId, $articleId)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(' Panel ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName . ' ' . $number . '. Sayısı', ['route' => 'journal_issues', 'routeParameters' => ['id' => $journalId]]);
        $menu->addChild('Makaleler', ['route' => 'articles_list', 'routeParameters' => ['id' => $issueId]]);
        $menu->addChild('Pdf Güncelleme');

        return $menu;
    }

//      yeni sayı
    public function createIssueAddBreadcrumb(FactoryInterface $factory, $journalName, $id, $name)
    {
        $menu = $factory->createItem('root');
        $menu->addChild(' Panel ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName, ['route' => 'journal_issues', 'routeParameters' => ['id' => $id]]);
        $menu->addChild($name);

        return $menu;
    }

    // editör yeni sayı
    public function createEditorIssueAddBreadcrumb(FactoryInterface $factory, $journalName, $id, $name)
    {
        $menu = $factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild(' Editörlük Yaptığım Dergiler ', ['route' => 'editor_journal_management']);
        $menu->addChild($journalName . ' Sayıları',['route' => 'editor_journal_issues', 'routeParameters' => ['id' => $id]]);
        $menu->addChild($name);

        return $menu;
    }

    //sayı düzenleme
    public function createIssueEditBreadcrumb(FactoryInterface $factory, $journalName, $id)
    {
        $menu = $factory->createItem('root');
        $menu->addChild(' Panel ', ['route' => 'dashboard_admin']);
        $menu->addChild(' Dergiler ', ['route' => 'admin_journal_management']);
        $menu->addChild($journalName, ['route' => 'journal_issues', 'routeParameters' => ['id' => $id]]);
        $menu->addChild('Sayı Düzenle');

        return $menu;
    }
    // editör sayı düzenleme
    public function createEditorIssueEditBreadcrumb(FactoryInterface $factory, $journalName, $id)
    {
        $menu = $factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild(' Editörlük Yaptığım Dergiler ', ['route' => 'editor_journal_management']);
        $menu->addChild($journalName . ' Sayıları',['route' => 'editor_journal_issues', 'routeParameters' => ['id' => $id]]);
        $menu->addChild('Sayı Düzenle');

        return $menu;
    }

    //kullancı operatörlük dergileri
    public function createOperatorManagementBreadcrumb(FactoryInterface $factory, $role)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild($role . ' Yaptığım Dergiler');

        return $menu;
    }

    //kullanıcı operatörlük dergi sayıları
    public function createUserIssuesBreadcrumb(FactoryInterface $factory, $journalName)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild(' Operatörlük Yaptığım Dergiler ', ['route' => 'operator_journal_management']);
        $menu->addChild($journalName . ' Sayıları');

        return $menu;
    }

    //kullanıcı editörlük dergi sayıları
    public function createEditorIssuesBreadcrumb(FactoryInterface $factory, $journalName)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild(' Editörlük Yaptığım Dergiler ', ['route' => 'editor_journal_management']);
        $menu->addChild($journalName . ' Sayıları');

        return $menu;
    }

    //kullanıcı makale listesi
    public function createUserArticleListBreadcrumb(FactoryInterface $factory, $journalName, $number, $id)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild(' Operatörlük Yaptığım Dergiler ', ['route' => 'operator_journal_management']);
        $menu->addChild($journalName . ' Sayıları', ['route' => 'operator_journal_issues', 'routeParameters' => ['id' => $id]]);
        $menu->addChild(' ' . $number . '. Sayı ' . 'Makaleleri');

        return $menu;
    }

    public function createEditorArticleListBreadcrumb(FactoryInterface $factory, $journalName, $number, $id)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('Ana Panel', ['route' => 'app_homepage']);
        $menu->addChild(' Editörlük Yaptığım Dergiler ', ['route' => 'editor_journal_management']);
        $menu->addChild($journalName . ' Sayıları', ['route' => 'editor_journal_issues', 'routeParameters' => ['id' => $id]]);
        $menu->addChild(' ' . $number . '. Sayı ' . 'Makaleleri');

        return $menu;
    }

    public function createEmptyBreadcrumb()
    {
        return $this->factory->createItem('root');
    }


}
