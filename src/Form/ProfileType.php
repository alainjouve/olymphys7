<?php
//App/Form/ProfileType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    protected string $translationDomain = 'App/translations'; //

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, ['required' => true, 'label' => 'Votre nom', 'attr' => ["placeholder" => "Votre nom"]])
            ->add('prenom', TextType::class, ['required' => true, 'label' => 'Votre prénom', 'attr' => ['placeholder' => "Votre prenom"]])
            ->add('adresse', TextType::class, ['required' => true, 'label' => 'Votre adresse (numéro +rue)', 'attr' => ['placeholder' => "Votre adresse"]])
            ->add('email', EmailType::class, ['required' => true, 'label' => 'Votre email et identifiant, adresse académique afin de faciliter les imports de données depuis Adage si vous êtes professeur', 'attr' => ['placeholder' => "Votre email et identifiant"]])
            ->add('contact', EmailType::class, ['required' => false, 'label' => 'Adresse mail de contact autre que l\'adresse académique', 'attr' => ['placeholder' => "Adresse de contact"]])
            ->add('ville', TextType::class, ['required' => true, 'label' => 'Votre ville', 'attr' => ['placeholder' => "Ville"]])
            ->add('code', TextType::class, ['required' => true, 'label' => 'Votre code postal', 'attr' => ['placeholder' => "Code postal"]])
            ->add('phone', TextType::class, ['required' => false, 'label' => 'Votre téléphone, portable, si possible', 'attr' => ['placeholder' => 'tel']])
            ->add('uai', TextType::class, ['required' => false, 'label' => 'UAI de votre établissement, si vous comptez inscrire une équipe', 'attr' => ['placeholder' => 'UAI']])
            ->add('Modification', SubmitType::class, ['label' => 'Valider ces modifications']);;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // enable/disable CSRF protection for this form
            'csrf_protection' => true,
            // the name of the hidden HTML field that stores the token
            'csrf_field_name' => '_token',
            // an arbitrary string used to generate the value of the token
            // using a different string for each form improves its security
            'csrf_token_id' => 'task_item',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'user_registration';
    }
}
