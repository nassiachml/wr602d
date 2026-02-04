<?php

namespace App\Controller;

use App\Repository\PdfRepository;
use App\Repository\PdfTaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(PdfRepository $pdfRepository): Response
    {
        $usedToday = 0;
        if ($this->getUser()) {
            $usedToday = $pdfRepository->countSuccessfulToday($this->getUser());
        }
        return $this->render('home/index.html.twig', [
            'used_today' => $usedToday,
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function dashboard(PdfRepository $pdfRepository, PdfTaskRepository $taskRepository): Response
    {
        $user = $this->getUser();
        $usedToday = $pdfRepository->countSuccessfulToday($user);
        $subscription = $user->getSubscription();
        $maxPerDay = $subscription ? $subscription->getMaxPdfsPerDay() : 0;
        $pendingTasks = $taskRepository->findPendingByUser($user);
        $countThisMonth = $pdfRepository->countByUserThisMonth($user);
        $countTotal = $pdfRepository->countByUserTotal($user);

        return $this->render('home/dashboard.html.twig', [
            'used_today' => $usedToday,
            'max_per_day' => $maxPerDay,
            'pending_tasks' => $pendingTasks,
            'count_this_month' => $countThisMonth,
            'count_total' => $countTotal,
        ]);
    }
}
