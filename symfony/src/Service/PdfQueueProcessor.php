<?php

namespace App\Service;

use App\Entity\Pdf;
use App\Entity\PdfTask;
use App\Repository\PdfRepository;
use App\Repository\PdfTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PdfQueueProcessor
{
    public function __construct(
        private readonly PdfTaskRepository $taskRepository,
        private readonly PdfRepository $pdfRepository,
        private readonly EntityManagerInterface $em,
        private readonly string $pdfDir,
        private readonly string $gotenbergUrl,
        private readonly HttpClientInterface $httpClient,
    ) {
        if (!is_dir($this->pdfDir)) {
            mkdir($this->pdfDir, 0755, true);
        }
    }

    public function processTask(PdfTask $task): void
    {
        $task->setStatus(PdfTask::STATUS_PROCESSING);
        $this->em->flush();

        try {
            $pdf = $this->executeTask($task);
            $task->setPdf($pdf);
            $task->setStatus(PdfTask::STATUS_DONE);
            $task->setProcessedAt(new \DateTimeImmutable());
            $this->em->persist($pdf);
            $this->em->flush();
        } catch (\Throwable $e) {
            $task->setStatus(PdfTask::STATUS_FAILED);
            $task->setProcessedAt(new \DateTimeImmutable());
            $pdf = new Pdf();
            $pdf->setUser($task->getUser());
            $pdf->setFilename('');
            $pdf->setIsSuccess(false);
            $pdf->setErrorMessage($e->getMessage());
            $this->em->persist($pdf);
            $this->em->flush();
        }
    }

    private function executeTask(PdfTask $task): Pdf
    {
        $user = $task->getUser();
        $payload = $task->getPayload();

        switch ($task->getType()) {
            case PdfTask::TYPE_URL:
                $url = $payload['url'] ?? '';
                if ($url === '') {
                    throw new \InvalidArgumentException('URL manquante');
                }
                return $this->generateFromUrl($url, $user);
            case PdfTask::TYPE_WYSIWYG:
                $html = $payload['html'] ?? '';
                return $this->generateFromHtml($html, $user);
            case PdfTask::TYPE_FILE:
                $path = $payload['file_path'] ?? null;
                $name = $payload['file_name'] ?? 'document';
                if (!$path || !is_file($path)) {
                    throw new \InvalidArgumentException('Fichier source manquant');
                }
                return $this->generateFromFile($path, $name, $user);
            default:
                throw new \InvalidArgumentException('Type de tâche inconnu: ' . $task->getType());
        }
    }

    private function generateFromUrl(string $url, $user): Pdf
    {
        $filename = sprintf('pdf_%s_%s.pdf', $user->getId(), uniqid());
        $filepath = $this->pdfDir . '/' . $filename;
        $response = $this->httpClient->request('POST', rtrim($this->gotenbergUrl, '/') . '/forms/chromium/convert/url', [
            'body' => ['url' => $url],
        ]);
        file_put_contents($filepath, $response->getContent());
        $pdf = new Pdf();
        $pdf->setUser($user);
        $pdf->setFilename($filename);
        $pdf->setOriginalUrl($url);
        $pdf->setIsSuccess(true);
        $pdf->setFileSize((int) filesize($filepath));
        return $pdf;
    }

    private function generateFromHtml(string $html, $user): Pdf
    {
        $filename = sprintf('pdf_%s_%s.pdf', $user->getId(), uniqid());
        $filepath = $this->pdfDir . '/' . $filename;
        $response = $this->httpClient->request('POST', rtrim($this->gotenbergUrl, '/') . '/forms/chromium/convert/html', [
            'body' => [
                'files' => [
                    ['name' => 'index.html', 'content' => $html],
                ],
            ],
        ]);
        file_put_contents($filepath, $response->getContent());
        $pdf = new Pdf();
        $pdf->setUser($user);
        $pdf->setFilename($filename);
        $pdf->setIsSuccess(true);
        $pdf->setFileSize((int) filesize($filepath));
        return $pdf;
    }

    private function generateFromFile(string $tempPath, string $originalName, $user): Pdf
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = sprintf('pdf_%s_%s.pdf', $user->getId(), uniqid());
        $filepath = $this->pdfDir . '/' . $filename;

        if ($ext === 'html' || $ext === 'htm') {
            $html = file_get_contents($tempPath);
            return $this->generateFromHtml($html, $user);
        }

        $response = $this->httpClient->request('POST', rtrim($this->gotenbergUrl, '/') . '/forms/libreoffice/convert', [
            'body' => [
                'files' => [
                    ['name' => $originalName, 'content' => fopen($tempPath, 'r')],
                ],
            ],
        ]);
        file_put_contents($filepath, $response->getContent());
        $pdf = new Pdf();
        $pdf->setUser($user);
        $pdf->setFilename($filename);
        $pdf->setIsSuccess(true);
        $pdf->setFileSize((int) filesize($filepath));
        return $pdf;
    }

    /** @return PdfTask[] */
    public function processPending(int $limit = 10): array
    {
        $tasks = $this->taskRepository->findPending($limit);
        foreach ($tasks as $task) {
            $this->processTask($task);
        }
        return $tasks;
    }
}
