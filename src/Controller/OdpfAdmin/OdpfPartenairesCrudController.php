<?php

namespace App\Controller\OdpfAdmin;

use App\Entity\Odpf\OdpfPartenaires;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use PhpParser\Node\Expr\Yield_;

class OdpfPartenairesCrudController extends AbstractCrudController
{


    public static function getEntityFqcn(): string
    {
        return OdpfPartenaires::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->showEntityActionsInlined()
            ->overrideTemplates(['crud/index'=> 'bundles/EasyAdminBundle/indexEntities.html.twig', ]);
    }

    public function configureFields(string $pageName): iterable
    {
        $pathpluginsAutogrow = '../public/bundles/fosckeditor/plugins/autogrow/'; // with trailing slash sur le site
        if ($_SERVER['SERVER_NAME'] == '127.0.0.1' or $_SERVER['SERVER_NAME'] == 'localhost') {
            $pathpluginsAutogrow = 'bundles/fosckeditor/plugins/autogrow/';// with trailing slash en local
        }
       yield TextField::new('titre');
       yield TextField::new('choix');
        yield AdminCKEditorField::new('mecenes')->setFormTypeOptions([
            'config' => array(
                'extraPlugins' => 'autogrow',

            ),
            'plugins' => array(
                'autogrow' => array(
                    'path' => $pathpluginsAutogrow,
                    'filename' => 'plugin.js',
                    'autoGrowEnabled' => true,
                    'autoGrow_minHeight' => 200,
                    'autoGrow_maxHeight' => 600,
                    'autoGrow_bottomSpace' => 50
                ))])->onlyOnForms();
        yield AdminCKEditorField::new('donateurs')->setFormTypeOptions([
            'config' => array(
                'extraPlugins' => 'autogrow',

            ),
            'plugins' => array(
                'autogrow' => array(
                    'path' => $pathpluginsAutogrow,
                    'filename' => 'plugin.js',
                    'autoGrowEnabled' => true,
                    'autoGrow_minHeight' => 200,
                    'autoGrow_maxHeight' => 600,
                    'autoGrow_bottomSpace' => 50
                ))])->onlyOnForms();
        yield AdminCKEditorField::new('visites')->setFormTypeOptions([
            'config' => array(
                'extraPlugins' => 'autogrow',

            ),
            'plugins' => array(
                'autogrow' => array(
                    'path' => $pathpluginsAutogrow,
                    'filename' => 'plugin.js',
                    'autoGrowEnabled' => true,
                    'autoGrow_minHeight' => 200,
                    'autoGrow_maxHeight' => 600,
                    'autoGrow_bottomSpace' => 50
                ))])->onlyOnForms();
        yield AdminCKEditorField::new('cadeaux')->setFormTypeOptions([
            'config' => array(
                'extraPlugins' => 'autogrow',

                ),
            'plugins' => array(
                'autogrow' => array(
                    'path' => $pathpluginsAutogrow,
                    'filename' => 'plugin.js',
                    'autoGrowEnabled' => true,
                    'autoGrow_minHeight' => 200,
                    'autoGrow_maxHeight' => 600,
                    'autoGrow_bottomSpace' => 50
                ))])->onlyOnForms();
        yield AdminCKEditorField::new('cia')->setFormTypeOptions([
            'config' => array(
                'extraPlugins' => 'autogrow',

            ),
            'plugins' => array(
                'autogrow' => array(
                    'path' => $pathpluginsAutogrow,
                    'filename' => 'plugin.js',
                    'autoGrowEnabled' => true,
                    'autoGrow_minHeight' => 200,
                    'autoGrow_maxHeight' => 600,
                    'autoGrow_bottomSpace' => 50
                ))])->onlyOnForms();
        yield TextField::new('mecenes')->setTemplatePath('bundles/EasyAdminBundle/mecenesField.html.twig')->hideOnForm();
        yield TextField::new('donateurs')->setTemplatePath('bundles/EasyAdminBundle/donateursField.html.twig')->hideOnForm()    ;
        yield TextField::new('visites')->setTemplatePath('bundles/EasyAdminBundle/visitesField.html.twig')->hideOnForm()    ;
        yield TextField::new('cadeaux')->setTemplatePath('bundles/EasyAdminBundle/cadeauxField.html.twig')->hideOnForm()    ;
        yield TextField::new('cia')->setTemplatePath('bundles/EasyAdminBundle/ciaField.html.twig')->hideOnForm();

        yield DateTimeField::new('updatedAt');
        yield DateTimeField::new('updatedat', 'Mis à jour  le ');



    }

    public function configureActions(Actions $actions): \EasyCorp\Bundle\EasyAdminBundle\Config\Actions
    {
        $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->update('index', Action::DELETE,function  (Action $action) {
                return $action->setIcon('fa fa-trash-alt')->setLabel(false);}
            )
            ->update('index', Action::EDIT,function  (Action $action) {
                return $action->setIcon('fa fa-pencil-alt')->setLabel(false);}
            )
            ->update('index', Action::DETAIL,function  (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel(false);}
            )
        ;
        return $actions;
    }

}

