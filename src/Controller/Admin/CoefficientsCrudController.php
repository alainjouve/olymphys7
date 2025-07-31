<?php

namespace App\Controller\Admin;


use App\Entity\Coefficients;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;


class CoefficientsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coefficients::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ])
            ->setPageTitle(Crud::PAGE_EDIT, 'modifier les coefficients');

    }

    public function configureFields(string $pageName): iterable
    {
        return [
            'demarche',
            'origin',
            'oral',
            'repquestions',
            'exper',
            'wgroupe',
            'ecrit'
        ];
    }
    public function configureActions(Actions $actions): Actions
    {
        return $actions->update('index', Action::EDIT, function  (Action $action) {
            return $action->setIcon('fa fa-pencil-alt')->setLabel(false);})
            ->update('index', Action::DELETE, function  (Action $action) {
                return $action->setIcon('fa fa-trash-alt')->setLabel(false);});
    }
}