<?php

namespace App\Controller;

use App\Entity\Pdf;
use App\Entity\PdfTask;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ContactRepository;
use App\Repository\PdfRepository;
use App\Service\PdfQueueProcessor;
use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class PdfController extends AbstractController
{
    #[Route('/pdf/generate', name: 'app_pdf_generate', methods: ['GET', 'POST'])]
    public function generate(
        Request $request,
        PdfService $pdfService,
        PdfQueueProcessor $queueProcessor,
        ContactRepository $contactRepository,
        PdfRepository $pdfRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        $contacts = $contactRepository->findByUser($user);
        $usedToday = $pdfRepository->countSuccessfulToday($user);
        $maxPerDay = $user->getSubscription()?->getMaxPdfsPerDay() ?? 0;

        if ($request->isMethod('POST')) {
            $type = $request->request->get('type', 'url');
            $addToQueue = (bool) $request->request->get('add_to_queue', false);
            $sendByEmail = (bool) $request->request->get('send_by_email', false);
            $contactIds = array_map('intval', (array) $request->request->get('contact_ids', []));

            if ($addToQueue) {
                $task = new PdfTask();
                $task->setUser($user);
                $task->setType($type);
                $task->setSendByEmail($sendByEmail);
                $task->setPayload(['contact_ids' => $contactIds]);

                if ($type === PdfTask::TYPE_URL) {
                    $task->setPayload(['url' => $request->request->get('url', ''), 'contact_ids' => $contactIds]);
                } elseif ($type === PdfTask::TYPE_WYSIWYG) {
                    $task->setPayload(['html' => $request->request->get('html', ''), 'contact_ids' => $contactIds]);
                } elseif ($type === PdfTask::TYPE_FILE) {
                    $file = $request->files->get('file');
                    if (!$file) {
                        $this->addFlash('error', 'Veuillez sélectionner un fichier.');
                        return $this->render('pdf/generate_pdf.html.twig', [
                            'contacts' => $contacts,
                            'used_today' => $usedToday,
                            'max_per_day' => $maxPerDay,
                            'prefill_url' => $request->query->get('url'),
                        ]);
                    }
                    $tmp = $file->getPathname();
                    $task->setPayload(['file_path' => $tmp, 'file_name' => $file->getClientOriginalName(), 'contact_ids' => $contactIds]);
                }

                $em->persist($task);
                $em->flush();
                $this->addFlash('success', 'Tâche ajoutée à la file d\'attente.');
                return $this->redirectToRoute('app_pdf_generate');
            }

            if ($usedToday >= $maxPerDay && $maxPerDay > 0) {
                $this->addFlash('error', 'Quota du jour atteint.');
                return $this->render('pdf/generate_pdf.html.twig', [
                    'contacts' => $contacts,
                    'used_today' => $usedToday,
                    'max_per_day' => $maxPerDay,
                    'prefill_url' => $request->query->get('url'),
                ]);
            }

            $pdf = null;
            if ($type === PdfTask::TYPE_URL) {
                $url = $request->request->get('url', '');
                if ($url !== '') {
                    $pdf = $pdfService->generateFromUrl($url, $user);
                }
            } elseif ($type === PdfTask::TYPE_WYSIWYG) {
                $html = $request->request->get('html', '');
                $pdf = $pdfService->generateFromHtml($html, $user);
            } elseif ($type === PdfTask::TYPE_FILE) {
                $file = $request->files->get('file');
                if ($file) {
                    $pdf = $pdfService->generateFromFile($file->getPathname(), $file->getClientOriginalName(), $user);
                }
            }

            if ($pdf) {
                $em->persist($pdf);
                $em->flush();
                if ($pdf->isSuccess()) {
                    $this->addFlash('success', 'PDF généré.');
                    return $this->redirectToRoute('app_pdf_download', ['id' => $pdf->getId()]);
                }
                $this->addFlash('error', $pdf->getErrorMessage() ?? 'Erreur de génération.');
            }
        }

        return $this->render('pdf/generate_pdf.html.twig', [
            'contacts' => $contacts,
            'used_today' => $usedToday,
            'max_per_day' => $maxPerDay,
            'prefill_url' => $request->query->get('url'),
        ]);
    }

    #[Route('/pdf/process-queue', name: 'app_pdf_process_queue', methods: ['POST'])]
    public function processQueue(Request $request, PdfQueueProcessor $queueProcessor): Response
    {
        if (!$this->isCsrfTokenValid('process_queue', (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException();
        }
        $processed = $queueProcessor->processPending(5);
        $this->addFlash('success', count($processed) . ' tâche(s) traitée(s).');
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/pdf/history', name: 'app_pdf_history', methods: ['GET'])]
    public function history(Request $request, PdfRepository $pdfRepository): Response
    {
        $user = $this->getUser();
        $search = $request->query->get('q');
        $status = $request->query->get('status');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 15;
        [$pdfs, $total] = $pdfRepository->findByUserPaginated($user, $search, $status, $page, $perPage);
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        return $this->render('pdf/history.html.twig', [
            'pdfs' => $pdfs,
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'status_filter' => $status,
        ]);
    }

    #[Route('/pdf/delete/{id}', name: 'app_pdf_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, PdfRepository $pdfRepository, PdfService $pdfService, EntityManagerInterface $em): Response
    {
        $pdf = $pdfRepository->find($id);
        if (!$pdf || $pdf->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('pdf_delete_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $path = $pdfService->getAbsolutePath($pdf);
        if ($path && is_file($path)) {
            @unlink($path);
        }
        $em->remove($pdf);
        $em->flush();
        $this->addFlash('success', 'PDF supprimé de l\'historique.');
        $params = array_filter([
            'page' => $request->request->get('page'),
            'q' => $request->request->get('q'),
            'status' => $request->request->get('status'),
        ], fn ($v) => $v !== null && $v !== '');
        return $this->redirectToRoute('app_pdf_history', $params);
    }

    #[Route('/pdf/download/{id}', name: 'app_pdf_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function download(int $id, PdfService $pdfService, PdfRepository $pdfRepository): Response
    {
        $pdf = $pdfRepository->find($id);
        if (!$pdf || $pdf->getUser() !== $this->getUser() || !$pdf->isSuccess()) {
            throw $this->createNotFoundException();
        }
        $path = $pdfService->getAbsolutePath($pdf);
        if (!is_file($path)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }
        $response = new Response(file_get_contents($path));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $pdf->getFilename()
        ));
        return $response;
    }
}
