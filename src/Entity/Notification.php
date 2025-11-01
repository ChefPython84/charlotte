<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity:User::class)]
    #[ORM\JoinColumn(nullable:false)]
    private ?User $user = null;

    #[ORM\Column(type:"string", length:255)]
    private string $message;

    // --- AJOUT ---
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $link = null;
    // --- FIN AJOUT ---

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $dateEnvoi;

    #[ORM\Column(type:"boolean")]
    private bool $estLu = false;

    public function __construct()
    {
        $this->dateEnvoi = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }
    public function getDateEnvoi(): \DateTimeInterface { return $this->dateEnvoi; }
    public function setDateEnvoi(\DateTimeInterface $dateEnvoi): self { $this->dateEnvoi = $dateEnvoi; return $this; }
    public function getEstLu(): bool { return $this->estLu; }
    public function setEstLu(bool $estLu): self { $this->estLu = $estLu; return $this; }

    // --- AJOUT ---
    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;
        return $this;
    }
    // --- FIN AJOUT ---
}