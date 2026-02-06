<?php

namespace App\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Entity\Cadeaux;
use App\Entity\Edition;
use App\Entity\Equipes;
use App\Entity\Prix;
use App\Entity\Visites;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CadeauxCrudController extends AbstractCrudController
{

    public function __Construct(protected EntityManagerInterface $doctrine, protected RequestStack $requestStack, protected AdminUrlGenerator $adminUrlGenerator)
    {

    }

    #[Route("/admin/cadeaux/next/{id}", name: "admin_cadeaux_next")]
    public function nextCadeau(Cadeaux $cadeau): Response
    {
        $nextCadeau = $this->doctrine->getRepository(Cadeaux::class)->createQueryBuilder('c')
            ->where('c.montant < :montant')
            ->setParameter('montant', $cadeau->getmontant())
            ->orderBy('c.montant', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$nextCadeau) {
            $nextCadeau = $this->doctrine->getRepository(Cadeaux::class)->createQueryBuilder('c')
                ->orderBy('c.montant', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        $url = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($nextCadeau->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    #[Route("/admin/cadeaux/prev/{id}", name: "admin_cadeaux_prev")]
    public function prevCadeau(Cadeaux $cadeau): Response
    {
        $prevCadeau = $this->doctrine->getRepository(Cadeaux::class)->createQueryBuilder('c')
            ->where('c.montant > :montant')
            ->setParameter('montant', $cadeau->getmontant())
            ->orderBy('c.montant', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$prevCadeau) {
            $prevCadeau = $this->doctrine->getRepository(Cadeaux::class)->createQueryBuilder('c')
                ->orderBy('c.montant', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        $url = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($prevCadeau->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    public static function getEntityFqcn(): string
    {
        return Cadeaux::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $cadeau = '';
        if ($_REQUEST['crudAction'] == 'edit') {

            $cadeau = $this->doctrine->getRepository(Cadeaux::class)->find($_REQUEST['entityId'])->getLot();
        };

        return $crud->showEntityActionsInlined()
            ->setPageTitle('edit', 'Modifier le lot n° ' . $cadeau)
            ->setFormOptions(['attr' => ['id' => 'edit-Cadeaux-form']])
            ->overrideTemplates(['crud/index' => 'bundles/EasyAdminBundle/indexEntities.html.twig',
                'crud/edit' => 'bundles/EasyAdminBundle/editCadeaux.html.twig'])
            ->setEntityLabelInSingular('Cadeau')
            ->setEntityLabelInPlural('Cadeaux')
            ->setSearchFields(['lot', 'contenu', 'fournisseur', 'montant', 'raccourci']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $tableauExcel = Action::new('cadeaux_tableau_excel', 'Extraire un tableau Excel', 'fa fa_array')
            ->linkToRoute('cadeaux_tableau_excel')
            ->createAsGlobalAction();

        return $actions->update('index', Action::EDIT, function (Action $action) {
            return $action->setIcon('fa fa-pencil-alt')->setLabel(false);
        })
            ->update('index', Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash-alt')->setLabel(false);
            })
            ->add(Crud::PAGE_INDEX, $tableauExcel)
            ->add(Crud::PAGE_EDIT, 'index');
    }

    public function configureFields(string $pageName): iterable
    {
        $equipesSansCadeau = $this->doctrine->getRepository(Equipes::class)->createQueryBuilder('e')
            ->where('e.cadeau is NULL')
            ->getQuery()->getResult();
        $cadeauEquipe = null;
        if (isset($_REQUEST['entityId'])) {
            $id = $_REQUEST['entityId'];
            $cadeauEquipe = $this->doctrine->getRepository(Cadeaux::class)->findOneBy(['id' => $id]);
            $equipe = $cadeauEquipe->getEquipe();
            if (isset($equipe)) {
                $equipesSansCadeau[count($equipesSansCadeau)] = $equipe;//pour afficher la valeur de l'équipe dans le formulaire, elle est ajoutée à la fin de la liste
            }
        }
        $lots = [];
        $listnumLots = range(1, 26);
        foreach ($listnumLots as $lot) {

            $lots[$lot] = $lot;

        }


        $onchange = [
            'numlot' => null,
            'contenu' => null,
            'equipe' => null,
            'fournisseur' => null,
            'montant' => null,
            'raccourci' => null,

        ];

        if ($pageName == 'edit') {
            $id = $_REQUEST['entityId'];
            $cadeauEquipe = $this->doctrine->getRepository(Cadeaux::class)->findOneBy(['id' => $id]);
            $onchange = [
                'numlot' => 'changelotcadeau(this,' . $cadeauEquipe->getId() . ',"numlot")',
                'contenu' => 'changecontenucadeau(this,' . $cadeauEquipe->getId() . ',"contenu")',
                'fournisseur' => 'changefournisseurcadeau(this,' . $cadeauEquipe->getId() . ',"fournisseur")',
                'montant' => 'changemontantcadeau(this,' . $cadeauEquipe->getId() . ',"montant")',
                'raccourci' => 'changeraccourcicadeau(this,' . $cadeauEquipe->getId() . ',"raccourci")',
                'equipe' => 'changeequipecadeau(this,' . $cadeauEquipe->getId() . ',"equipe")'];
        }
        return [
            //$id = IntegerField::new('id', 'ID')->onlyOnIndex(),
            $contenu = TextField::new('contenu')->setFormTypeOptions([

                'attr' => ['onchange' => $onchange['contenu']],
            ]),

            $lot = IntegerField::new('lot', 'lot n°')->setFormType(ChoiceType::class)
                ->setFormTypeOptions([
                    'choices' => $lots,
                    'attr' => ['onchange' => $onchange['numlot']],
                ]),
            $equipe = AssociationField::new('equipe')->setFormType(EntityType::class)
                ->setFormTypeOptions(
                    [
                        'class' => Equipes::class,
                        'choices' => $equipesSansCadeau,
                        'attr' => ['onchange' => $onchange['equipe']],
                    ]
                ),
            //$id = IntegerField::new('id', 'id')->hideOnIndex()->hideWhenCreating()
            //    ->setFormType(HiddenType::class),
            $fournisseur = TextField::new('fournisseur')->setFormTypeOptions([

                'attr' => ['onchange' => $onchange['fournisseur']],
            ]),
            $montant = MoneyField::new('montant')->setFormTypeOptions([
                'attr' => ['onchange' => $onchange['montant'], 'style' => 'width: 50px'],
            ])
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            $raccourci = TextField::new('raccourci')->setFormTypeOptions([

                'attr' => ['onchange' => $onchange['raccourci']],
            ]),


        ];

    }


    #[Route("/Admin/CadeauxCrud/cadeaux_tableau_excel", name: "cadeaux_tableau_excel")]
    public function cadeauxstableauexcel()
    {

        $listEquipes = $this->doctrine->getRepository(Equipes::class)->createQueryBuilder('e')
            ->join('e.equipeinter', 'eq')
            ->addOrderBy('eq.lettre', 'ASC')
            ->getQuery()->getResult();
        $listeCadeaux = $this->doctrine->getRepository(Cadeaux::class)->findAll();
        $edition = $this->requestStack->getSession()->get('edition');
        if (date('now') < $this->requestStack->getSession()->get('dateouverturesite')) {
            $edition = $this->doctrine->getRepository(Edition::class)->findOneBy(['ed' => $edition->getEd() - 1]);
        }
        $liste_cadeaux = [];
        $i = 0;
        foreach ($listEquipes as $equipe) {

            $liste_cadeaux[$i] = $equipe->getCadeau();
            $i = $i + 1;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Olymphys")
            ->setLastModifiedBy("Olymphys")
            ->setTitle("CN - " . $edition->getEd() . "e -Tableau destiné au comité")
            ->setSubject("Tableau destiné au comité")
            ->setDescription("Office 2007 XLSX liste des cadeaux")
            ->setKeywords("Office 2007 XLSX")
            ->setCategory("Test result file");

        $sheet = $spreadsheet->getActiveSheet();
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V'] as $letter) {
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        $ligne = 1;
        $sheet->setCellValue('A' . $ligne, 'Num lot')
            ->setCellValue('B' . $ligne, 'equipe')
            ->setCellValue('F' . $ligne, 'contenu')
            ->setCellValue('D' . $ligne, 'fournisseur')
            ->setCellValue('C' . $ligne, 'montant')
            ->setCellValue('E' . $ligne, 'raccourci');;


        $ligne += 1;
        foreach ($listeCadeaux as $cadeau) {

            $sheet->setCellValue('A' . $ligne, $cadeau->getLot())
                ->setCellValue('B' . $ligne, $cadeau->getEquipe())
                ->setCellValue('F' . $ligne, $cadeau->getContenu())
                ->setCellValue('D' . $ligne, $cadeau->getFournisseur())
                ->setCellValue('C' . $ligne, $cadeau->getMontant() . ' €')
                ->setCellValue('E' . $ligne, $cadeau->getRaccourci());

            $ligne += 1;
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="cadeaux.xls"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        //$writer= PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        //$writer =  \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        // $writer =IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_end_clean();
        $writer->save('php://output');


    }

    #[Route("/cadeaux/changelot", name: "changelot")]
    public function changelot(\Symfony\Component\HttpFoundation\Request $request)//: Response
    {
        $idlot = $request->request->get('idlot');
        $valeur = $request->request->get('lot');
        $cadeau = $this->doctrine->getRepository(Cadeaux::class)->find($idlot);
        $cadeau->setLot($valeur);
        $this->doctrine->persist($cadeau);
        $this->doctrine->flush();

        $url = $this->adminUrlGenerator->setAction(Action::EDIT)
            ->setDashboard(DashboardController::class)
            ->setController(CadeauxCrudController::class)
            ->setEntityId($idlot)
            ->generateUrl();

        return $this->redirect($url);
    }

    #[Route("/cadeaux/changecadeau", name: "changecadeau")]
    public function changecadeau(\Symfony\Component\HttpFoundation\Request $request)//: Response
    {
        $idlot = $request->request->get('idlot');
        $type = $request->request->get('type');
        $cadeau = $this->doctrine->getRepository(Cadeaux::class)->find($idlot);
        if ($type == 'contenu') {
            $valeur = $request->request->get('contenu');
            $cadeau->setContenu($valeur);
        }
        if ($type == 'numlot') {
            $valeur = $request->request->get('numlot');
            $valeur != '' ? $cadeau->setLot($valeur) : $cadeau->setLot(null);
        }
        if ($type == 'equipe') {
            $idEquipe = $request->request->get('idEquipe');
            $equipe = null;
            if ($idEquipe != null) {
                $equipe = $this->doctrine->getRepository(Equipes::class)->find(intval($idEquipe));
            }
            $cadeau->setEquipe($equipe);
        }
        if ($type == 'fournisseur') {
            $valeur = $request->request->get('fournisseur');
            $cadeau->setFournisseur($valeur);
        }
        if ($type == 'montant') {
            $valeur = $request->request->get('montant');
            $cadeau->setMontant($valeur);
        }
        if ($type == 'raccourci') {
            $valeur = $request->request->get('raccourci');
            $cadeau->setRaccourci($valeur);
        }

        $this->doctrine->persist($cadeau);
        $this->doctrine->flush();

        $url = $this->adminUrlGenerator->setAction(Action::EDIT)
            ->setDashboard(DashboardController::class)
            ->setController(CadeauxCrudController::class)
            ->setEntityId($idlot)
            ->generateUrl();

        return $this->redirect($url);
    }


}
