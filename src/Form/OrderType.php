<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderDate')
            ->add('status')
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a product',
            ])

            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email', // or username if naa ka
                'placeholder' => 'Select customer (User)',
            ])



            ->add('quantity', IntegerType::class, [
                'data' => 1,
                'mapped' => false,
                'attr' => [
                    'min' => 1
                ]
            ])


            ->add('handledBy', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'placeholder' => 'Select staff',
            ])

            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
            ])

            ->add('paymentStatus', ChoiceType::class, [
                'choices' => [
                    'Paid' => 'paid',
                    'Unpaid' => 'unpaid',
                ],
            ])


            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                ],
            ]);






        ;

       


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }





}
