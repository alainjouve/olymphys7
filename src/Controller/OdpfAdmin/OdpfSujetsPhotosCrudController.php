<?php

namespace App\Controller\OdpfAdmin;

use App\Entity\Odpf\OdpfSujetsPhotos;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OdpfSujetsPhotosCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OdpfSujetsPhotos::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined()
            ->setPageTitle('index', 'Type de sujet des photos')
            ->setPageTitle('new', 'Nouveau type de sujet des photos')
            ->setPageTitle('edit', 'Modification d\'un type de sujet des photos')
            ->overrideTemplates(['crud/index'=>'bundles/EasyAdminBundle/indexEntities.html.twig']);
    }
    public function configureActions(Actions $actions): Actions
    {
        return $actions->update('index','new', function (Action $action) {
            return $action->setLabel('Nouveau');})
            ->update('index', Action::DELETE,function  (Action $action) {
                return $action->setIcon('fa fa-trash-alt')->setLabel(false);}
            )
            ->update('index', Action::EDIT,function  (Action $action) {
                return $action->setIcon('fa fa-pencil-alt')->setLabel(false);}
            )
            ->update('new', Action::SAVE_AND_ADD_ANOTHER,function  (Action $action) {
                return $action->setLabel('Sauvegarder puis créer un nouveau sujet');}
            )
            ->add('new',Action::INDEX)
            ->update('new',Action::INDEX,function  (Action $action) {
                return $action->setLabel('Annuler');})
            ->add('edit',Action::INDEX);
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('libelle', 'Libellé'),

        ];
    }

}
