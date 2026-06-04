<?php

namespace App\Entity;

use App\Repository\SemaforoIndicadoresMediaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SemaforoIndicadoresMediaRepository::class)]
#[ORM\Table(name: 'semaforo_indicadores_media')]
#[ORM\UniqueConstraint(name: 'UNIQ_SEMAFORO_MEDIA_INDICADOR', columns: ['id_indicadorbasico'])]
class SemaforoIndicadoresMedia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_indicadorbasico', referencedColumnName: 'id', nullable: false)]
    private ?IndicadoresBasicos $indicadorBasico = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    private ?string $mediaEstatal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    private ?string $mediaNacional = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIndicadorBasico(): ?IndicadoresBasicos
    {
        return $this->indicadorBasico;
    }

    public function setIndicadorBasico(?IndicadoresBasicos $indicadorBasico): static
    {
        $this->indicadorBasico = $indicadorBasico;

        return $this;
    }

    public function getMediaEstatal(): ?string
    {
        return $this->mediaEstatal;
    }

    public function setMediaEstatal(?string $mediaEstatal): static
    {
        $this->mediaEstatal = $mediaEstatal;

        return $this;
    }

    public function getMediaNacional(): ?string
    {
        return $this->mediaNacional;
    }

    public function setMediaNacional(?string $mediaNacional): static
    {
        $this->mediaNacional = $mediaNacional;

        return $this;
    }
}
