<?php

namespace App\Form;

use App\Entity\Loyalty;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;



class LoyaltyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('points')
            ->add('rewardType')
            ->add('rewardType', ChoiceType::class, [
                'choices' => [
                    'Bronze' => 'bronze',
                    'Silver' => 'silver',
                    'Gold' => 'gold',
                    'Platinum' => 'platinum',
                    'Voucher' => 'voucher',
                    'Free Item' => 'free_item',
                ],
                'placeholder' => 'Select Reward Type'
            ])

            ->add('createdAt', null, [
                'widget' => 'single_text'
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text'
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Loyalty::class,
        ]);
    }
}
