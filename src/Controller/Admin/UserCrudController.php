<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\CustomEditionFilter;
use App\Controller\Admin\Filter\CustomEquipeFilter;
use App\Controller\Admin\Filter\CustomRolesFilter;
use App\Entity\User;
use App\Form\Type\Admin\CustomRolesFilterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use PhpOffice\PhpSpreadsheet\Shared\PasswordHasher;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserCrudController extends AbstractCrudController
{
    private AdminContextProvider $adminContextProvider;
    private ManagerRegistry $doctrine;
    private UserPasswordHasherInterface $passwordEncoder;

    public function __construct(AdminContextProvider $adminContextProvider, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordEncoder)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->doctrine = $doctrine;
        $this->passwordEncoder = $passwordEncoder;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    /* public function configureFilters(Filters $filters): Filters
     {
         return $filters
             ->add(CustomRolesFilter::new('roles'));

     }*/

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->overrideTemplates([
                'crud/index' => 'bundles/EasyAdminBundle/indexEntities.html.twig',
                'crud/edit' => 'bundles/EasyAdminBundle/editUsers.html.twig',
                'crud/new' => 'bundles/EasyAdminBundle/newUsers.html.twig',
            ])
            ->setSearchFields(['id', 'username', 'roles', 'email', 'token', 'uai', 'nom', 'prenom', 'adresse', 'ville', 'code', 'phone', 'civilite']);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile('css/password-visibility.css')
            ->addJsFile('js/password-visibility.js');
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IntegerField::new('id', 'ID');
        $email = TextField::new('email');
        $username = TextField::new('username');
        $nomPrenom = TextareaField::new('nomPrenom');
        $roles = ArrayField::new('roles');
        $rolesedit = ChoiceField::new('roles')->setChoices(['ROLES_ADMIN' => 'ROLES_ADMIN',
            'ROLE_SUPER_ADMIN' => 'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN' => 'ROLE_ADMIN',
            'ROLE_PROF' => 'ROLE_PROF',
            'ROLE_JURY' => 'ROLE_JURY',
            'ROLE_JURYCIA' => 'ROLE_JURYCIA',
            'ROLE_ORGACIA' => 'ROLE_ORGACIA',
            'ROLE_COMITE' => 'ROLE_COMITE',
            'ROLE_SECRETARIAT_JURY' => 'ROLE_SECRETARIAT_JURY'])
            ->setFormTypeOption('multiple', true);
        $autorisationListeDiffusion = BooleanField::new(
            'autorisationListeDiffusion',
            'J’accepte de recevoir des messages de diffusion d’Olymphys, notamment les informations importantes concernant les OdPF'
        )
            ->setFormTypeOptions(['required' => false]);

        if ($pageName === Crud::PAGE_NEW) {
            $autorisationListeDiffusion->setFormTypeOption('data', true);
        }


        return [
            IntegerField::new('id')->setFormTypeOption('disabled', true),
            TextField::new('prenom'),
            TextField::new('nom'),
            TextField::new('email'),
            TextField::new('username'),
            ArrayField::new('roles')->onlyOnIndex(),
            ChoiceField::new('roles')->setChoices(['ROLES_ADMIN' => 'ROLES_ADMIN',
                'ROLE_SUPER_ADMIN' => 'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN' => 'ROLE_ADMIN',
                'ROLE_PROF' => 'ROLE_PROF',
                'ROLE_JURY' => 'ROLE_JURY',
                'ROLE_JURYCIA' => 'ROLE_JURYCIA',
                'ROLE_ORGACIA' => 'ROLE_ORGACIA',
                'ROLE_COMITE' => 'ROLE_COMITE',
                'ROLE_SECRETARIAT_JURY' => 'ROLE_SECRETARIAT_JURY'])
                ->setFormTypeOption('multiple', true)->onlyOnForms(),
            AssociationField::new('centrecia')
                ->setHelp('Ne doit être rempli que pour les comptes organisateurs locaux des cia')
                ->setFormTypeOptions(['required' => false])
                ->onlyOnForms(),
            TextField::new('uai')->onlyOnIndex(),
            Field::new('plainPassword', 'Mot de passe')
                ->setFormType(PasswordType::class)
                ->setFormTypeOption('always_empty', true)
                ->setFormTypeOption('attr', ['autocomplete' => 'new-password'])
                ->setFormTypeOption('row_attr', ['class' => 'user-plain-password-row'])
                ->onlyOnForms(),
            AssociationField::new('uaiId', 'UAI')->setFormTypeOptions(['required' => false])->onlyOnForms(),
            BooleanField::new('autorisationInscription')->setFormTypeOptions(['required' => false])->onlyOnForms(),
            $autorisationListeDiffusion->onlyOnForms(),
            $contact = TextField::new('contact')->hideOnIndex(),//Doit être différente de l'adresse email identifiant du compte
            $isActive = Field::new('isActive'),
            $adresse = TextField::new('adresse'),
            $ville = TextField::new('ville'),
            $code = TextField::new('code'),
            $phone = TextField::new('phone'),
            $createdAt = DateTimeField::new('createdAt'),
            $updatedAt = DateTimeField::new('updatedAt'),
            $lastVisit = DateTimeField::new('lastVisit'),
            $civilite = TextField::new('civilite'),
            //$autorisationphotos = AssociationField::new('autorisationphotos'),
            $token = TextField::new('token'),
            $passwordRequestedAt = DateTimeField::new('passwordRequestedAt'),

        ];


    }

    public function configureActions(Actions $actions): Actions
    {
        $addUsers = Action::new('addUsers', 'Ajouter des users', 'fa fa_users')
            // if the route needs parameters, you can define them:
            // 1) using an array
            ->linkToRoute('secretariatadmin_charge_user')
            ->createAsGlobalAction();
        $exportUsers = Action::new('exportUsersExcel', 'Exporter tous les utilisateurs (Excel)', 'fa fa-file-excel')
            ->createAsGlobalAction()
            ->linkToCrudAction('exportUsersExcel');


        $actions = $actions
            ->update('index', Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash-alt')->setLabel(false);
            }
            )
            ->update('index', Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-pencil-alt')->setLabel(false);
            }
            )
            ->add(Crud::PAGE_EDIT, Action::INDEX, 'Retour à la liste')
            ->add(Crud::PAGE_NEW, Action::INDEX, 'Retour à la liste')
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update('index', Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel(false);
            }
            )
            ->add(Crud::PAGE_INDEX, $addUsers)
            ->add(Crud::PAGE_INDEX, $exportUsers)
            ->setPermission($exportUsers, 'ROLE_SUPER_ADMIN');
        return $actions;
    }

    public function exportUsersExcel(): Response
    {
        $users = $this->doctrine->getRepository(User::class)->createQueryBuilder('user')
            ->leftJoin('user.uaiId', 'uai')
            ->addSelect('uai')
            ->leftJoin('user.centrecia', 'centre')
            ->addSelect('centre')
            ->leftJoin('user.autorisationphotos', 'autorisationPhoto')
            ->addSelect('autorisationPhoto')
            ->orderBy('user.id', 'ASC')
            // ->addOrderBy('user.prenom', 'ASC')
            ->getQuery()
            ->toIterable();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Utilisateurs');

        $headers = [
            'ID',
            'Prénom',
            'Nom',
            'Adresse e-mail',
            'Identifiant',
            'Rôles',
            'Compte actif',
            'Fichier d’autorisation photo',
            'Code UAI',
            'Établissement',
            'Ville',
            'Académie',
            'Contact',
            'Adresse',
            'Ville',
            'Code postal',
            'Téléphone',
            'Civilité',
            'Centre CIA',
            'Newsletter',
            'Autorisation d’inscription',
            'Autorisation de diffusion',
            'Créé le',
            'Mis à jour le',
            'Dernière visite',
            'Demande de réinitialisation le',
        ];

        foreach ($headers as $columnIndex => $header) {
            $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1) . '1';
            $sheet->setCellValueExplicit($coordinate, $header, DataType::TYPE_STRING);
        }

        $row = 2;
        foreach ($users as $user) {
            $uai = $user->getUaiId();
            $centre = $user->getCentrecia();
            $values = [
                $user->getId(),
                $user->getPrenom(),
                $user->getNom(),
                $user->getEmail(),
                $user->getUsername(),
                implode(', ', $user->getRoles()),
                $this->formatBooleanPourExcel($user->getIsActive()),
                $user->getAutorisationphotos()?->getFichier(),
                $uai?->getUai() ?? $user->getUai(),
                $uai?->getNom(),
                $uai?->getCommune(),
                strtoupper($uai?->getAcademie() ?? ''),
                $user->getContact(),
                $user->getAdresse(),
                $user->getVille(),
                $user->getCode(),
                $user->getPhone(),
                $user->getCivilite(),
                $centre?->getCentre(),
                $this->formatBooleanPourExcel($user->getNewsletter()),
                $this->formatBooleanPourExcel($user->isAutorisationInscription()),
                $this->formatBooleanPourExcel($user->isAutorisationListeDiffusion()),
                $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
                $user->getLastVisit()?->format('Y-m-d H:i:s'),
                $user->getPasswordRequestedAt()?->format('Y-m-d H:i:s'),
            ];

            foreach ($values as $columnIndex => $value) {
                $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1) . $row;
                if ($columnIndex === 0 && $value !== null) {
                    $sheet->setCellValue($coordinate, $value);
                    continue;
                }

                $sheet->setCellValueExplicit($coordinate, (string)($value ?? ''), DataType::TYPE_STRING);
            }
            ++$row;
        }

        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . max(1, $row - 1));
        foreach (range(1, count($headers)) as $columnIndex) {
            $sheet->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex)
            )->setAutoSize(true);
        }

        $filename = 'utilisateurs_' . date('Y-m-d_H-i-s') . '.xlsx';
        $response = new StreamedResponse(static function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename)
        );
        $response->headers->set('Cache-Control', 'max-age=0, private');

        return $response;
    }

    private function formatBooleanPourExcel(?bool $value): string
    {
        return match ($value) {
            true => 'Oui',
            false => 'Non',
            null => '',
        };
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getUaiId() !== null) {
            $uai = $entityInstance->getUaiId()->getUai();

            $entityInstance->setUai($uai);
        } else {
            $entityInstance->setUai(null);
        }

        if ($entityInstance->getPlainPassword() != null) {
            $hashpassword = $this->passwordEncoder->hashPassword($entityInstance, $entityInstance->getPlainPassword());
            $entityInstance->setPassword($hashpassword);

        }

        parent::updateEntity($entityManager, $entityInstance); // TODO: Change the autogenerated stub
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getUaiId() !== null) {
            $uai = $entityInstance->getUaiId()->getUai();

            $entityInstance->setUai($uai);
        }
        $hashpassword = $this->passwordEncoder->hashPassword($entityInstance, $entityInstance->getPlainPassword());
        $entityInstance->setPassword($hashpassword);

        parent::persistEntity($entityManager, $entityInstance); // TODO: Change the autogenerated stub
    }


}
