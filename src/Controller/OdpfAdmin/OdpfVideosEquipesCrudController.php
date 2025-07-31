<?php

namespace App\Controller\OdpfAdmin;

use App\Entity\Odpf\OdpfVideosequipes;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OdpfVideosEquipesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OdpfVideosequipes::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [

            AssociationField::new('equipe'),
            TextField::new('lien'),
        ];
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
