<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account', methods: ['GET', 'POST'])]
    public function show(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('account', (string) $request->request->get('_token'))) {
            $user->setDisplayName($request->request->get('display_name'));
            $file = $request->files->get('avatar');
            if ($file && $file->isValid()) {
                $dir = $this->getParameter('kernel.project_dir') . '/var/uploads/avatars';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $name = sprintf('user_%d_%s.%s', $user->getId(), uniqid(), $file->getClientOriginalExtension() ?: 'png');
                $file->move($dir, $name);
                $user->setAvatarFilename($name);
            }
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            if ($newPassword !== '' && \strlen($newPassword) >= 6) {
                if ($currentPassword !== '' && $passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                    $this->addFlash('success', 'Mot de passe modifié.');
                } else {
                    $this->addFlash('error', 'Mot de passe actuel incorrect.');
                }
            }
            $em->flush();
            if (!$request->request->get('new_password')) {
                $this->addFlash('success', 'Profil mis à jour.');
            }
            return $this->redirectToRoute('app_account');
        }
        return $this->render('account/show.html.twig');
    }

    #[Route('/account/avatar', name: 'app_account_avatar', methods: ['GET'])]
    public function avatar(): Response
    {
        $user = $this->getUser();
        $filename = $user->getAvatarFilename();
        if ($filename) {
            $path = $this->getParameter('kernel.project_dir') . '/var/uploads/avatars/' . $filename;
            if (is_file($path)) {
                $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };
                $response = new Response(file_get_contents($path));
                $response->headers->set('Content-Type', $mime);
                $response->headers->set('Cache-Control', 'private, max-age=300');
                return $response;
            }
        }
        $initial = mb_strtoupper(mb_substr($user->getDisplayName() ?: $user->getEmail(), 0, 1)) ?: '?';
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><rect width="40" height="40" fill="%s"/><text x="20" y="24" text-anchor="middle" fill="%s" font-family="system-ui,sans-serif" font-size="18" font-weight="600">%s</text></svg>',
            '#f97316',
            '#fff',
            htmlspecialchars($initial)
        );
        $response = new Response($svg);
        $response->headers->set('Content-Type', 'image/svg+xml; charset=utf-8');
        $response->headers->set('Cache-Control', 'private, max-age=300');
        return $response;
    }
}
