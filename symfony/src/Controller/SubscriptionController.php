<?php

namespace App\Controller;

use App\Repository\PdfRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class SubscriptionController extends AbstractController
{
    #[Route('/subscriptions', name: 'app_subscriptions', methods: ['GET', 'POST'])]
    public function list(Request $request, SubscriptionRepository $subscriptionRepository, PdfRepository $pdfRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $subscriptions = $subscriptionRepository->findActive();
        $usedToday = $pdfRepository->countSuccessfulToday($user);
        $current = $user->getSubscription();
        $maxPerDay = $current ? $current->getMaxPdfsPerDay() : 0;

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('subscription_switch', (string) $request->request->get('_token'))) {
            $id = (int) $request->request->get('subscription');
            $sub = $subscriptionRepository->find($id);
            if ($sub) {
                $user->setSubscription($sub);
                $em->flush();
                $this->addFlash('success', 'Abonnement modifié.');
                return $this->redirectToRoute('app_subscriptions');
            }
        }

        return $this->render('subscription/list.html.twig', [
            'subscriptions' => $subscriptions,
            'current' => $current,
            'used_today' => $usedToday,
            'max_per_day' => $maxPerDay,
        ]);
    }

    #[Route('/subscription/change', name: 'app_subscription_change', methods: ['GET'])]
    public function changeRedirect(): Response
    {
        return $this->redirectToRoute('app_subscriptions');
    }
}
