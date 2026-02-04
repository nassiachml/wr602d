<?php

namespace App\Tests\Entity;

use App\Entity\Pdf;
use App\Entity\User;
use App\Entity\Subscription;
use PHPUnit\Framework\TestCase;

class PdfTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $pdf = new Pdf();
        $filename = 'document.pdf';
        $originalUrl = 'https://example.com';
        $errorMessage = 'Error';
        $isSuccess = true;
        $fileSize = 1024;

        $subscription = new Subscription();
        $subscription->setName('Free');
        $subscription->setMaxPdfsPerDay(5);
        $subscription->setPrice(0);
        $user = new User();
        $user->setEmail('u@test.com');
        $user->setPassword('hash');
        $user->setSubscription($subscription);

        $pdf->setUser($user);
        $pdf->setFilename($filename);
        $pdf->setOriginalUrl($originalUrl);
        $pdf->setErrorMessage($errorMessage);
        $pdf->setIsSuccess($isSuccess);
        $pdf->setFileSize($fileSize);

        $this->assertSame($user, $pdf->getUser());
        $this->assertEquals($filename, $pdf->getFilename());
        $this->assertEquals($originalUrl, $pdf->getOriginalUrl());
        $this->assertEquals($errorMessage, $pdf->getErrorMessage());
        $this->assertTrue($pdf->isSuccess());
        $this->assertEquals($fileSize, $pdf->getFileSize());
        $this->assertNotNull($pdf->getCreatedAt());
    }
}
