<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class,[
                "required"=>true,
                'label'=>'Titre'
            ])
            ->add('content',TextareaType::class,[
                "required"=>true,
                'label'=>"Contenu de l'article"
            ])
            ->add('image',FileType::class,[
                'label'=>"Vous pouvez ajouter une image à l'article",
                'multiple'=>false,
                'mapped'=>false,
                'required'=>false
            ])
            //->add('createdAt')
            //->add('User')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
