<?php

namespace App\Controller\Admin;

use App\Entity\Centrescia;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CentresciaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Centrescia::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ])
            ->setSearchFields(['id', 'centre']);
    }

    public function configureFields(string $pageName): iterable
    {
        $centre = TextField::new('centre');
        $id = IntegerField::new('id', 'ID');
        $nbselectionnees = IntegerField::new('nbselectionnees');
        $orga1 = AssociationField::new('orga1');
        $orga2 = AssociationField::new('orga2');
        $jurycia = AssociationField::new('jurycia');
        $actif = BooleanField::new('actif', 'Actif');
        $lieu = TextField::new('lieu');
        $organisateur=TextField::new('organisateur');
        $verouClassement = BooleanField::new('verouClassement', 'verouClassement');
        if (Crud::PAGE_INDEX === $pageName) {
            return [$centre, $lieu,$organisateur,  $actif, $nbselectionnees, $verouClassement];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $actif, $centre, $lieu,$organisateur, $nbselectionnees, $verouClassement];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$centre, $lieu, $actif, $nbselectionnees,$organisateur, $verouClassement];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$centre, $lieu, $organisateur, $actif, $nbselectionnees, $verouClassement];
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update('index', Action::EDIT, function  (Action $action) {
                return $action->setIcon('fa fa-pencil-alt')->setLabel(false);})
            ->update('index', Action::DELETE, function  (Action $action) {
                return $action->setIcon('fa fa-trash-alt')->setLabel(false);})
            ->add(Crud::PAGE_EDIT, Action::INDEX, 'Retour à la liste')
            ->add(Crud::PAGE_NEW, Action::INDEX, 'Retour à la liste')
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update('index', Action::DETAIL, function  (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel(false);});
    }


}