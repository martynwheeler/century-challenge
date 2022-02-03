<?php

namespace App\Controller\Admin;

use App\Entity\Ride;
use App\Entity\User;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'easyadmin')]
    public function index(): Response
    {
        // redirect to some CRUD controller
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());

        // you can also redirect to different pages depending on the current user
        //if ('jane' === $this->getUser()->getUserIdentifier()) {
        //    return $this->redirect('...');
        //}

        // you can also render some template to display a proper Dashboard
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Century Challenge Admin Page');
    }

    public function configureCrud(): Crud
    {
        return Crud::new();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Public Homepage', 'fas fa-home', 'homepage');
        yield MenuItem::linkToCrud('Users', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Ride', 'fas fa-biking', Ride::class);
        yield MenuItem::linkToCrud('Scheduler', 'fa fa-calendar', ScheduledCommand::class);
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        // Usually it's better to call the parent method because that gives you a
        // user menu with some menu items already created ("sign out", "exit impersonation", etc.)
        // if you prefer to create the user menu from scratch, use: return UserMenu::new()->...
        return parent::configureUserMenu($user)
            // you can also pass an email address to use gravatar's service
            ->setGravatarEmail($user->getEmail())

            // you can use any type of menu item, except submenus
            ->addMenuItems([
                MenuItem::linkToRoute('My Profile', 'fa fa-id-card', 'displayrides', ['username' => $this->getUser()->getUserIdentifier()]),
                MenuItem::linkToRoute('Settings', 'fa fa-user-cog', 'editprofile', ['username' => $this->getUser()->getUserIdentifier()]),
            ]);
    }
}
