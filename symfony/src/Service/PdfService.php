<?php

namespace App\Service;

use App\Entity\Pdf;
use App\Entity\PdfTask;
use App\Entity\User;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PdfService
{
    public function __construct(
        private readonly string $gotenbergUrl,
        private readonly string $pdfDir,
        private readonly HttpClientInterface $httpClient,
    ) {
        if (!is_dir($this->pdfDir)) {
            mkdir($this->pdfDir, 0755, true);
        }
    }

    public function generateFromUrl(string $url, User $user): Pdf
    {
        $filename = sprintf('pdf_%s_%s.pdf', $user->getId(), uniqid());
        $filepath = $this->pdfDir . '/' . $filename;

        try {
            // Gotenberg attend du multipart/form-data ; HttpClient le gère avec le body en tableau
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
        } catch (\Throwable $e) {
            $pdf = new Pdf();
            $pdf->setUser($user);
            $pdf->setFilename('');
            $pdf->setOriginalUrl($url);
            $pdf->setIsSuccess(false);
            $pdf->setErrorMessage($e->getMessage());
            return $pdf;
        }
    }

    public function generateFromHtml(string $html, User $user): Pdf
    {
        $filename = sprintf('pdf_%s_%s.pdf', $user->getId(), uniqid());
        $filepath = $this->pdfDir . '/' . $filename;

        try {
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
            $pdf->setOriginalUrl(null);
            $pdf->setIsSuccess(true);
            $pdf->setFileSize((int) filesize($filepath));
            return $pdf;
        } catch (\Throwable $e) {
            $pdf = new Pdf();
            $pdf->setUser($user);
            $pdf->setFilename('');
            $pdf->setIsSuccess(false);
            $pdf->setErrorMessage($e->getMessage());
            return $pdf;
        }
    }

    public function generateFromFile(string $tempPath, string $originalName, User $user): Pdf
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = sprintf('pdf_%s_%s.pdf', $user->getId(), uniqid());
        $filepath = $this->pdfDir . '/' . $filename;

        try {
            if ($ext === 'html' || $ext === 'htm') {
                $html = file_get_contents($tempPath);
                $pdf = $this->generateFromHtml($html, $user);
                if ($pdf->isSuccess()) {
                    rename($this->pdfDir . '/' . $pdf->getFilename(), $filepath);
                    $pdf->setFilename($filename);
                    $pdf->setFileSize((int) filesize($filepath));
                }
                return $pdf;
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
        } catch (\Throwable $e) {
            $pdf = new Pdf();
            $pdf->setUser($user);
            $pdf->setFilename('');
            $pdf->setIsSuccess(false);
            $pdf->setErrorMessage($e->getMessage());
            return $pdf;
        }
    }

    public function getAbsolutePath(Pdf $pdf): string
    {
        return $this->pdfDir . '/' . $pdf->getFilename();
    }
}
