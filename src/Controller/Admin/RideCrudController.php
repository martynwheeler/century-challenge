<?php

namespace App\Controller\Admin;

use App\Entity\Ride;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RideCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Ride::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ride')
            ->setEntityLabelInPlural('Ride')
            ->setPageTitle(Crud::PAGE_INDEX, '%entity_label_singular% listing')
            ->setPageTitle(Crud::PAGE_EDIT, 'Edit %entity_label_singular% (#%entity_short_id%)')
            ->setPageTitle(Crud::PAGE_DETAIL, '%entity_label_singular% (#%entity_short_id%)')
            ->setPageTitle(Crud::PAGE_NEW, 'Add Ride')
            ->setSearchFields(['id', 'km', 'average_speed', 'details', 'ride_id', 'source', 'user.forename', 'user.surname']);
    }

    public function configureFields(string $pageName): iterable
    {
        $user = AssociationField::new('user');
        $km = NumberField::new('km', 'KM');
        $averageSpeed = NumberField::new('average_speed', 'Average Speed');
        $date = DateTimeField::new('date');
        $dateAdded = DateTimeField::new('date_added', 'Date Added');
        $details = TextField::new('details');
        $rideId = TextareaField::new('ride_id', 'Ride ID');
        $clubRide = BooleanField::new('club_ride');
        $id = IntegerField::new('id', 'ID');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $user, $km, $averageSpeed, $date, $dateAdded];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $user, $km, $averageSpeed, $date, $dateAdded, $details, $rideId, $clubRide];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$user, $km, $averageSpeed, $date, $dateAdded, $details, $rideId, $clubRide];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$user, $km, $averageSpeed, $date, $dateAdded, $details, $rideId, $clubRide];
        }
    }
}
