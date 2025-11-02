<?php

namespace App\Form;

use App\Entity\Cia\ConseilsjuryCia;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConseilJuryCiaType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $pathpluginsWordcount = '../public/bundles/fosckeditor/plugins/wordcount/'; // with trailing slash sur le site
        $pathpluginsAutogrow = '../public/bundles/fosckeditor/plugins/autogrow/';
        if ($_SERVER['SERVER_NAME'] == '127.0.0.1' or $_SERVER['SERVER_NAME'] == 'localhost') {
            $pathpluginsWordcount = 'bundles/fosckeditor/plugins/wordcount/';// with trailing slash en local
            $pathpluginsAutogrow = 'bundles/fosckeditor/plugins/autogrow/';
        }
        $builder
            ->add('texte', CKEditorType::class, [
                'required' => false,
                'config' => array(
                    'extraPlugins' => 'wordcount,autogrow',),
                'plugins' => array(
                    'wordcount' => array(
                        'path' => $pathpluginsWordcount,
                        'filename' => 'plugin.js',
                    ),
                    'autogrow' => array(
                        'path' => $pathpluginsAutogrow,
                        'filename' => 'plugin.js',
                    ))
            ],



            )
            ->add('Enregistrer', SubmitType::class);

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults(array(
            'data_class' => ConseilsjuryCia::class
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'cyberjuryCia_conseils_juryCia';
    }


}