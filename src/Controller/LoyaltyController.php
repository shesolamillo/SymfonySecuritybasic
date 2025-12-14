<?php

namespace App\Controller;

use App\Entity\Loyalty;
use App\Form\LoyaltyType;
use App\Repository\LoyaltyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/loyalty')]
final class LoyaltyController extends AbstractController
{
    #[Route(name: 'app_loyalty_index', methods: ['GET'])]
    public function index(LoyaltyRepository $loyaltyRepository): Response
    {
        return $this->render('loyalty/index.html.twig', [
            'loyalties' => $loyaltyRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_loyalty_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $loyalty = new Loyalty();

        if ($this->isGranted('ROLE_USER')) {
            $loyalty->setUser($this->getUser());
        }



        $form = $this->createForm(LoyaltyType::class, $loyalty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($loyalty);
            $entityManager->flush();

            return $this->redirectToRoute('app_loyalty_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('loyalty/new.html.twig', [
            'loyalty' => $loyalty,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_loyalty_show', methods: ['GET'])]
    public function show(Loyalty $loyalty): Response
    {
        return $this->render('loyalty/show.html.twig', [
            'loyalty' => $loyalty,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_loyalty_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Loyalty $loyalty, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LoyaltyType::class, $loyalty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_loyalty_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('loyalty/edit.html.twig', [
            'loyalty' => $loyalty,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_loyalty_delete', methods: ['POST'])]
    public function delete(Request $request, Loyalty $loyalty, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$loyalty->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($loyalty);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_loyalty_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/loyalty/my', name: 'app_loyalty_my')]
    public function my(LoyaltyRepository $repo): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get all loyalty entries for the logged-in user
        $loyalties = $repo->findBy(['user' => $user], ['createdAt' => 'DESC']);

        // Compute total points
        $total = array_reduce($loyalties, function ($sum, $row) {
            return $sum + $row->getPoints();
        }, 0);

        return $this->render('loyalty/my.html.twig', [
            'loyalties' => $loyalties,
            'total' => $total,
            'user' => $user
        ]);
    }

    #[Route('/overview', name: 'app_loyalty_overview')]
    public function overview(LoyaltyRepository $repo): Response
    {
        $loyalties = $repo->findAll();

        // Total unique members
        $uniqueUsers = [];
        foreach ($loyalties as $l) {
            $uniqueUsers[$l->getUser()->getId()] = true;
        }
        $totalMembers = count($uniqueUsers);

        // Total points awarded
        $totalPoints = array_reduce($loyalties, fn($s, $l) => $s + $l->getPoints(), 0);

        // Redeemed points = negative values
        $redeemed = array_reduce($loyalties, fn($s, $l) =>
            $l->getPoints() < 0 ? $s + abs($l->getPoints()) : $s, 0
        );

        // Active tiers count
        $tiers = array_unique(array_map(fn($l) => $l->getRewardType(), $loyalties));
        $activeTiers = count($tiers);

        return $this->render('loyalty/overview.html.twig', [
            'loyalties'     => $loyalties,
            'totalMembers'  => $totalMembers,
            'totalPoints'   => $totalPoints,
            'redeemed'      => $redeemed,
            'activeTiers'   => $activeTiers,
        ]);
    }


}
