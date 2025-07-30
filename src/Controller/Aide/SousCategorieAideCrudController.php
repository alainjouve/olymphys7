<?php

namespace App\Controller\Aide;

use App\Controller\OdpfAdmin\AdminCKEditorField;
use App\Entity\AideEnLigne;
use App\Entity\CategorieAide;
use App\Entity\SousCategorieAide;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class SousCategorieAideCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SousCategorieAide::class;
    }
    public function configureCrud(Crud $crud): Crud    {
        return $crud->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ])
        ->setPageTitle('index','Sous-catégories de l\'aide');

    }
   public function configureFields(string $pageName): iterable
   {
       return [
           yield TextField::new('intitule', 'Intitule'),
           //ield AssociationField::new('categorie','Catégorie'),
           //yield AssociationField::new('sousCategorie','Sous-catégorie'),
           yield CollectionField::new('categorieAide','Catégories')->onlyOnIndex(),
           yield CollectionField::new('categorieAide')
               ->setEntryType(EntityType::class)
               ->setFormTypeOptions([
                   'entry_options' => [
                       'class'=>CategorieAide::class,
                       'choice_label'=>'intitule'],
                   'required' => false,
               ])
               ->renderExpanded()
               ->onlyOnForms(),



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
