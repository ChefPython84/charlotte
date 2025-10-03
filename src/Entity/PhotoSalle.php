<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PhotoSalle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Salle::class, inversedBy: "photos")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Salle $salle = null;

    #[ORM\Column(length:255)]
    private string $url;

    #[ORM\Column(length:255, nullable:true)]
    private ?string $description = null;

    #[ORM\Column(type:"boolean")]
    private bool $isPrincipale = false;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $uploadedAt;

    public function getId(): ?int { return $this->id; }

    public function getSalle(): ?Salle { return $this->salle; }
    public function setSalle(?Salle $salle): self { $this->salle = $salle; return $this; }

    public function getUrl(): string { return $this->url; }
    public function setUrl(string $url): self { $this->url = $url; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function isPrincipale(): bool { return $this->isPrincipale; }
    public function setIsPrincipale(bool $isPrincipale): self { $this->isPrincipale = $isPrincipale; return $this; }

    public function getUploadedAt(): \DateTimeInterface { return $this->uploadedAt; }
    public function setUploadedAt(\DateTimeInterface $uploadedAt): self { $this->uploadedAt = $uploadedAt; return $this; }
}
