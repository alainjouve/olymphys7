<?php

namespace App\Controller\Admin;



use App\Entity\Orgacia;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


class OrgaciaCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Orgacia::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $centre =AssociationField::new('centre');
        $user = AssociationField::new('user');
        if (Crud::PAGE_INDEX === $pageName) {
            return [$user, $centre];
        }
        elseif (Crud::PAGE_NEW === $pageName) {
            return [$user, $centre];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$user, $centre];
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
    public function configureCrud(Crud $crud): Crud {
        return $crud->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ]);
    }
}