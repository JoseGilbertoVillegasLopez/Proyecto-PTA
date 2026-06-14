<?php

namespace App\Entity;

use App\Repository\SolicitudGastosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\SolicitudGastosBanco;

#[ORM\Entity(repositoryClass: SolicitudGastosRepository::class)]
class SolicitudGastos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $fechaSolicitud = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $fechaNecesita = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $solicitante = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TipoSolicitud $tipoSolicitud = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transferenciaEnBeneficioDe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ctaClaveBeneficiario = null;

    #[ORM\Column(type: 'text')]
    private ?string $porConceptoDe = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $cantidadTotal = '0.00';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ProcesoEstrategico $procesoEstrategico = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ProcesoClave $procesoClave = null;

    #[ORM\Column(length: 20, options: ['default' => 'pendiente'])]
    private string $estado = 'pendiente';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?SolicitudGastosBanco $banco = null;

    /**
     * @var Collection<int, SolicitudGastosPartida>
     */
    #[ORM\OneToMany(
        targetEntity: SolicitudGastosPartida::class,
        mappedBy: 'solicitud',
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    private Collection $partidas;

    public function __construct()
    {
        $this->partidas = new ArrayCollection();
        $this->fechaSolicitud = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFechaSolicitud(): ?\DateTimeInterface
    {
        return $this->fechaSolicitud;
    }

    public function setFechaSolicitud(\DateTimeInterface $fechaSolicitud): static
    {
        $this->fechaSolicitud = $fechaSolicitud;

        return $this;
    }

    public function getFechaNecesita(): ?\DateTimeInterface
    {
        return $this->fechaNecesita;
    }

    public function setFechaNecesita(\DateTimeInterface $fechaNecesita): static
    {
        $this->fechaNecesita = $fechaNecesita;

        return $this;
    }

    public function getSolicitante(): ?Personal
    {
        return $this->solicitante;
    }

    public function setSolicitante(?Personal $solicitante): static
    {
        $this->solicitante = $solicitante;

        return $this;
    }

    public function getTipoSolicitud(): ?TipoSolicitud
    {
        return $this->tipoSolicitud;
    }

    public function setTipoSolicitud(?TipoSolicitud $tipoSolicitud): static
    {
        $this->tipoSolicitud = $tipoSolicitud;

        return $this;
    }

    public function getTransferenciaEnBeneficioDe(): ?string
    {
        return $this->transferenciaEnBeneficioDe;
    }

    public function setTransferenciaEnBeneficioDe(?string $transferenciaEnBeneficioDe): static
    {
        $this->transferenciaEnBeneficioDe = $transferenciaEnBeneficioDe;

        return $this;
    }

    public function getCtaClaveBeneficiario(): ?string
    {
        return $this->ctaClaveBeneficiario;
    }

    public function setCtaClaveBeneficiario(?string $ctaClaveBeneficiario): static
    {
        $this->ctaClaveBeneficiario = $ctaClaveBeneficiario;

        return $this;
    }

    public function getPorConceptoDe(): ?string
    {
        return $this->porConceptoDe;
    }

    public function setPorConceptoDe(string $porConceptoDe): static
    {
        $this->porConceptoDe = $porConceptoDe;

        return $this;
    }

    public function getCantidadTotal(): string
    {
        return $this->cantidadTotal;
    }

    public function setCantidadTotal(string $cantidadTotal): static
    {
        $this->cantidadTotal = $cantidadTotal;

        return $this;
    }

    public function getProcesoEstrategico(): ?ProcesoEstrategico
    {
        return $this->procesoEstrategico;
    }

    public function setProcesoEstrategico(?ProcesoEstrategico $procesoEstrategico): static
    {
        $this->procesoEstrategico = $procesoEstrategico;

        return $this;
    }

    public function getProcesoClave(): ?ProcesoClave
    {
        return $this->procesoClave;
    }

    public function setProcesoClave(?ProcesoClave $procesoClave): static
    {
        $this->procesoClave = $procesoClave;

        return $this;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    /**
     * @return Collection<int, SolicitudGastosPartida>
     */
    public function getPartidas(): Collection
    {
        return $this->partidas;
    }

    public function addPartida(SolicitudGastosPartida $partida): static
    {
        if (!$this->partidas->contains($partida)) {
            $this->partidas->add($partida);
            $partida->setSolicitud($this);
        }

        return $this;
    }

    public function removePartida(SolicitudGastosPartida $partida): static
    {
        if ($this->partidas->removeElement($partida)) {
            if ($partida->getSolicitud() === $this) {
                $partida->setSolicitud(null);
            }
        }

        return $this;
    }

    public function getBanco(): ?SolicitudGastosBanco
    {
        return $this->banco;
    }

    public function setBanco(?SolicitudGastosBanco $banco): static
    {
        $this->banco = $banco;

        return $this;
    }
}
