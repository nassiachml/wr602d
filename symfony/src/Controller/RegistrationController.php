<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        SubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $em
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $subscriptions = $subscriptionRepository->findActive();
        if (empty($subscriptions)) {
            $this->addFlash('error', 'Aucune offre d\'abonnement disponible.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', '');
            $password = $request->request->get('password', '');
            $subscriptionId = (int) $request->request->get('subscription');

            $subscription = $subscriptionRepository->find($subscriptionId);
            if (!$subscription) {
                $this->addFlash('error', 'Abonnement invalide.');
                return $this->render('registration/register.html.twig', ['subscriptions' => $subscriptions]);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setSubscription($subscription);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Inscription réussie. Connectez-vous.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'subscriptions' => $subscriptions,
        ]);
    }
}
