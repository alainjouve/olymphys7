<?php

namespace App\Controller\Admin;

use App\Entity\Repartprix;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RepartprixCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Repartprix::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ])
            ->setPageTitle(Crud::PAGE_EDIT, 'modifier la répartition')
            ->setSearchFields(['id', 'niveau', 'montant', 'nbreprix']);
    }

    public function configureFields(string $pageName): iterable
    {
        $niveau = TextField::new('niveau');
        $nbreprix = IntegerField::new('nbreprix');
        $id = IntegerField::new('id', 'ID');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$niveau, $nbreprix];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $niveau, $nbreprix];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$niveau, $nbreprix];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$niveau, $nbreprix];
        }
    }
    public function configureActions(Actions $actions): Actions{
        return $actions->update('index', Action::DELETE,function  (Action $action) {
            return $action->setIcon('fa fa-trash-alt')->setLabel(false);}
        )
            ->update('index', Action::EDIT,function  (Action $action) {
                return $action->setIcon('fa fa-pencil-alt')->setLabel(false);}
            );
    }
}