<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, '%entity_label_singular% listing')
            ->setPageTitle(Crud::PAGE_EDIT, 'Edit %entity_label_singular% (#%entity_short_id%)')
            ->setPageTitle(Crud::PAGE_DETAIL, '%entity_label_singular% (#%entity_short_id%)')
            ->setSearchFields(['id', 'username', 'roles', 'email', 'surname', 'forename', 'stravaID', 'passwordRequestToken', 'komootRefreshToken', 'komootTokenExpiry', 'komootID', 'stravaRefreshToken', 'stravaTokenExpiry', 'preferredProvider']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IntegerField::new('id', 'ID');
        $username = TextField::new('username');
        $name = TextField::new('name');
        $email = EmailField::new('email');
        $stravaID = TextField::new('stravaID', 'Strava ID');
        $passwordRequestToken = TextField::new('passwordRequestToken');
        $requestTokenExpiry = DateTimeField::new('requestTokenExpiry');
        $rides = AssociationField::new('rides');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $username, $name, $email, $stravaID];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $username, $email, $stravaID, $rides];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$id, $username, $name, $email, $stravaID];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$id, $username, $name, $email, $stravaID, $passwordRequestToken, $requestTokenExpiry];
        }
    }
}
