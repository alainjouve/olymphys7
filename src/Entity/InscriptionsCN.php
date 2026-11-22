<?php

namespace App\Entity;

use App\Repository\InscriptionsCNRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InscriptionsCNRepository::class)]
class InscriptionsCN
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qualite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ipAdress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $personnes = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTime $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getQualite(): ?string
    {
        return $this->qualite;
    }

    public function setQualite(?string $qualite): static
    {
        $this->qualite = $qualite;

        return $this;
    }

    public function getIpAdress(): ?string
    {
        return $this->ipAdress;
    }

    public function setIpAdress(?string $ipAdress): static
    {
        $this->ipAdress = $ipAdress;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return array<int, array{nom: string, prenom: string, present: bool}>
     */
    public function getPersonnes(): array
    {
        return array_map(
            static fn (array $personne): array => $personne + ['present' => false],
            $this->personnes ?? []
        );
    }

    /**
     * @param array<int, array{nom: string, prenom: string, present: bool}> $personnes
     */
    public function setPersonnes(array $personnes): static
    {
        $this->personnes = $personnes;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
