<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profilePicture',FileType::class,[
                'label'=>"Photo de profil",
                'multiple'=>false,
                'mapped'=>false,
                'required'=>false
            ])
            ->add('pseudo',TextType::class,[
                "required"=>true,
                'label'=>'Pseudo*'
            ])
            ->add('nickname',TextType::class,[
                "required"=>false,
                'label'=>'Surnom'
            ])
            ->add('prizeList',TextareaType::class,[
                'label'=>'Présentez-vous',
                'required'=>false
            ])
            ->add('email', EmailType::class,[
                'label'=>'Email*',
                'required'=>true,
            ])
            ->add('password',PasswordType::class,[
                "required"=>true,
                'label'=>'Mot de passe*'
            ])
            ->add('confirmPassword',PasswordType::class,[
                "required"=>true,
                'label'=>'Confirmation du mot de passe*'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
