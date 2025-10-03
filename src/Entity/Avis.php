<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"integer")]
    private int $note; // 1 à 5

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $commentaire = null;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $dateAvis;

    #[ORM\ManyToOne(targetEntity:User::class, inversedBy:"avis")]
    #[ORM\JoinColumn(nullable:false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity:Salle::class, inversedBy:"avis")]
    #[ORM\JoinColumn(nullable:false)]
    private ?Salle $salle = null;

    public function __construct()
    {
        $this->dateAvis = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getNote(): int { return $this->note; }
    public function setNote(int $note): self { $this->note = $note; return $this; }
    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): self { $this->commentaire = $commentaire; return $this; }
    public function getDateAvis(): \DateTimeInterface { return $this->dateAvis; }
    public function setDateAvis(\DateTimeInterface $dateAvis): self { $this->dateAvis = $dateAvis; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getSalle(): ?Salle { return $this->salle; }
    public function setSalle(?Salle $salle): self { $this->salle = $salle; return $this; }
}
