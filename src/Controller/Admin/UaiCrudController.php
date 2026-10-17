<?php

namespace App\Controller\Admin;

use App\Entity\Uai;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UaiCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Uai::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->overrideTemplate('crud/index', 'bundles/EasyAdminBundle/indexEntities.html.twig')
            ->setEntityLabelInSingular('Établissement UAI')
            ->setEntityLabelInPlural('Établissements UAI')
            ->setSearchFields(['uai', 'nom', 'appellationOfficielle', 'commune', 'academie', 'pays'])
            ->setDefaultSort(['uai' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IntegerField::new('id', 'ID')->hideOnForm();
        $uai = TextField::new('uai', 'Code UAI')
            ->setFormTypeOption('required', true);
        $nom = TextField::new('nom', 'Nom');
        $appellationOfficielle = TextField::new('appellationOfficielle', 'Appellation officielle');
        $denominationPrincipale = TextField::new('denominationPrincipale', 'Dénomination principale');
        $sigle = TextField::new('sigle', 'Sigle');
        $nature = TextField::new('nature', 'Nature');
        $commune = TextField::new('commune', 'Commune');
        $academie = TextField::new('academie', 'Académie');
        $pays = TextField::new('pays', 'Pays');
        $adresse = TextField::new('adresse', 'Adresse');
        $boitePostale = TextField::new('boitePostale', 'Boîte postale');
        $codePostal = TextField::new('codePostal', 'Code postal');
        $acheminement = TextField::new('acheminement', 'Acheminement');
        $coordonneeX = TextField::new('coordonneeX', 'Coordonnée X');
        $coordonneeY = TextField::new('coordonneeY', 'Coordonnée Y');
        $tel = TextField::new('tel', 'Téléphone');
        $courriel = TextField::new('courriel', 'Courriel');
        $proviseur = TextField::new('proviseur', 'Chef d’établissement');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $uai, $nom, $appellationOfficielle, $commune, $academie, $pays];
        }

        return [
            $id,
            $uai,
            $nom,
            $appellationOfficielle,
            $denominationPrincipale,
            $sigle,
            $nature,
            $commune,
            $academie,
            $pays,
            $adresse,
            $boitePostale,
            $codePostal,
            $acheminement,
            $coordonneeX,
            $coordonneeY,
            $tel,
            $courriel,
            $proviseur,
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, static function (Action $action): Action {
                return $action->setIcon('fa fa-pencil-alt')->setLabel(false);
            })
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static function (Action $action): Action {
                return $action->setIcon('fa fa-eye')->setLabel(false);
            })
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->add(Crud::PAGE_EDIT, Action::INDEX, 'Retour à la liste')
            ->add(Crud::PAGE_NEW, Action::INDEX, 'Retour à la liste');
    }
}
