<?php

namespace App\Entity\Odpf;

use App\Repository\Odpf\OdpfCategorieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OdpfCategorieRepository::class)]
#[ORM\Table(options: ["collate" => "utf8mb4_unicode_ci", "charset" => "utf8mb4"])]
class OdpfCategorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorie;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function __toString()
    {
        return $this->categorie;

    }

    public function setCategorie(string $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }


}
