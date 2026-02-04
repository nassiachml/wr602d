<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'app_locale', requirements: ['locale' => 'fr|en'], methods: ['GET'])]
    public function switch(string $locale): RedirectResponse
    {
        return $this->redirectToRoute('app_home');
    }
}
