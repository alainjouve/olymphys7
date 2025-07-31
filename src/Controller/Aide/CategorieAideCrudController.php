<?php

namespace App\Controller\Aide;

use App\Controller\OdpfAdmin\AdminCKEditorField;
use App\Entity\AideEnLigne;
use App\Entity\CategorieAide;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;


class CategorieAideCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CategorieAide::class;
    }
    public function configureCrud(Crud $crud): Crud    {
        return $crud->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ])
            ->setPageTitle('index','Catégories de l\'aide');

    }
    public function configureFields(string $pageName): iterable
    {

        return [
            yield TextField::new('intitule', 'Intitulé'),
            yield ChoiceField::new('permission')->setChoices([
                'ROLE_SUPER_ADMIN' => 'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN' => 'ROLE_ADMIN',
                'ROLE_COMITE' => 'ROLE_COMITE',
                'ROLE_PROF' => 'ROLE_PROF',
                'ROLE_JURY' => 'ROLE_JURY',
                'ROLE_JURYCIA' => 'ROLE_JURYCIA',
            ])


        ];
    }


}
