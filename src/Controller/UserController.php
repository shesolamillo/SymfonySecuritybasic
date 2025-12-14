<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;




#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

   #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $user->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile picture
            $pictureFile = $form->get('profilePicture')->getData();
            if ($pictureFile) {
                $newFilename = uniqid().'.'.$pictureFile->guessExtension();
                $pictureFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/profile',
                    $newFilename
                );
                $user->setProfilePicture($newFilename);
            }

             // Hash password
                $hashed = $passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($hashed);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        // Start session
        $session = $request->getSession();
        $note = $session->get('user_note_' . $user->getId(), ''); // get existing note or empty

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile picture
            $pictureFile = $form->get('profilePicture')->getData();

            if ($pictureFile) {
                $newFilename = uniqid().'.'.$pictureFile->guessExtension();
                $pictureFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/profile',
                    $newFilename
                );
                $user->setProfilePicture($newFilename);
            }

            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashed = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashed);
            }


            // Handle session-only note
            $submittedNote = $request->request->get('note', '');
            $session->set('user_note_' . $user->getId(), $submittedNote);

            $entityManager->flush();

            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'note' => $note, // pass current session note to template
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
