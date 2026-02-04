<?php

namespace App\Entity;

use App\Repository\PdfTaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PdfTaskRepository::class)]
class PdfTask
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public const TYPE_URL = 'url';
    public const TYPE_FILE = 'file';
    public const TYPE_WYSIWYG = 'wysiwyg';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_URL;

    #[ORM\Column(type: Types::JSON)]
    private array $payload = [];

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\OneToOne(targetEntity: Pdf::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Pdf $pdf = null;

    #[ORM\Column]
    private bool $sendByEmail = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): static
    {
        $this->payload = $payload;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getPdf(): ?Pdf
    {
        return $this->pdf;
    }

    public function setPdf(?Pdf $pdf): static
    {
        $this->pdf = $pdf;
        return $this;
    }

    public function isSendByEmail(): bool
    {
        return $this->sendByEmail;
    }

    public function setSendByEmail(bool $sendByEmail): static
    {
        $this->sendByEmail = $sendByEmail;
        return $this;
    }
}
