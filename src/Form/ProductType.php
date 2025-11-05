<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bridge\Doctrine\Form\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;


class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')

            ->add('price', NumberType::class, [
                'label' => 'Price (₱)',
                'attr' => [
                    'placeholder' => '0.00',
                    'min' => 0,       // prevents negative input in the browser
                    'step' => '0.01', // allows decimals
                ],
                'required' => true,  // ensures the admin cannot leave it empty
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Price is required',
                    ]),
                    new \Symfony\Component\Validator\Constraints\Positive([
                        'message' => 'Price must be a positive number',
                    ]),
                ],
            ])

        
            ->add('createdAt', null, [
                'widget' => 'single_text'
            ])
            
            ->add('image', FileType::class, [
                'label' => 'Product Image (JPG/PNG)',
                'mapped' => false, 
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPG or PNG image',
                    ])
                ],
            ])

            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
            ])




        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
