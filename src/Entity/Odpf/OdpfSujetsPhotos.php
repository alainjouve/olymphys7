<?php

namespace App\Entity\Odpf;

use App\Repository\Odpf\SujetsPhotosRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SujetsPhotosRepository::class)]
class OdpfSujetsPhotos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function __toString(){

        $libelle='';
        if($this->libelle!=null) {
            $libelle = $this->libelle;
        }
        return $libelle;
    }
    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }
}
