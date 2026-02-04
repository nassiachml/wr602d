<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, UserRepository $userRepository, MailerInterface $mailer, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        $sent = false;
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', '');
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));
                $em->flush();
                $resetLink = $request->getSchemeAndHttpHost() . $this->generateUrl('app_reset_password', ['token' => $token]);
                $mail = (new Email())
                    ->from('noreply@paperless.local')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html(sprintf('Bonjour,<br><br>Cliquez sur le lien pour réinitialiser votre mot de passe : <a href="%s">%s</a><br><br>Ce lien expire dans 1 heure.', $resetLink, $resetLink));
                $mailer->send($mail);
                $sent = true;
            } else {
                $this->addFlash('success', 'Si cet email existe, un lien de réinitialisation a été envoyé.');
                $sent = true;
            }
        }
        return $this->render('security/forgot_password.html.twig', ['sent' => $sent]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(string $token, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        $user = $userRepository->findOneByResetToken($token);
        if (!$user) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('reset_password', (string) $request->request->get('_token'))) {
            $password = $request->request->get('password', '');
            if (\strlen($password) >= 6) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $em->flush();
                $this->addFlash('success', 'Mot de passe modifié. Connectez-vous.');
                return $this->redirectToRoute('app_login');
            }
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
        }
        return $this->render('security/reset_password.html.twig', ['token' => $token]);
    }
}
