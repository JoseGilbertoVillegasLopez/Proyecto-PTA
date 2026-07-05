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
    /**
     * Catálogo fijo de documentos de verificación seleccionables.
     * Clave => etiqueta mostrada al usuario.
     */
    public const DOCUMENTOS_VERIFICACION = [
        'factura' => 'FACTURA(S)',
        'constancia_situacion_fiscal_clabe' => 'CONSTANCIA DE SITUACIÓN FISCAL & CLABE INTERBANCARIA (CARÁTULA EDO DE CTA DATOS DEL TITULAR)',
        'formato_pago' => 'FORMATO PARA PAGO No.',
        'recibo' => 'RECIBO(S)',
        'reporte_nomina' => 'REPORTE DE NÓMINA No.',
        'cotizacion' => 'COTIZACIÓN',
        'oficio_comision' => 'OFICIO DE COMISIÓN',
    ];

    /**
     * pendiente = creada, nadie la ha abierto.
     * en_revision = al menos un revisor la abrió; se mantiene hasta que los 3 voten o alguno rechace.
     * aceptada = los 3 revisores aceptaron.
     * rechazada = algún revisor rechazó (regla interina, ver RevisionSolicitudGastosService).
     * resuelto = aceptada y ya se subió el comprobante de pago/transferencia.
     */
    public const ESTADOS = ['pendiente', 'en_revision', 'aceptada', 'rechazada', 'resuelto'];

    /**
     * Único tipo de solicitud (por nombre, ver TipoSolicitud) que exige
     * documentación pendiente. Campo derivado: no se captura, se calcula.
     */
    private const TIPO_SOLICITUD_REQUIERE_DOCUMENTACION_PENDIENTE = 'Gastos por comprobar';

    private const DOCUMENTACION_PENDIENTE_TEXTO = '*CFDI Y VALIDACION DE CFDI';

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

    /**
     * Clave del catálogo DOCUMENTOS_VERIFICACION seleccionada por el solicitante.
     */
    #[ORM\Column(length: 60, nullable: true)]
    private ?string $documentoVerificacion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentoVerificacionDescripcion = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Personal $jefeArea = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Personal $autoriza = null;

    /**
     * @var Collection<int, SolicitudGastosEvidencia>
     */
    #[ORM\OneToMany(
        targetEntity: SolicitudGastosEvidencia::class,
        mappedBy: 'solicitud',
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    #[ORM\OrderBy(['orden' => 'ASC'])]
    private Collection $evidencias;

    /**
     * @var Collection<int, SolicitudGastosRevision>
     */
    #[ORM\OneToMany(
        targetEntity: SolicitudGastosRevision::class,
        mappedBy: 'solicitud',
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    private Collection $revisiones;

    #[ORM\OneToOne(mappedBy: 'solicitud', targetEntity: SolicitudGastosComprobante::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?SolicitudGastosComprobante $comprobante = null;

    public function __construct()
    {
        $this->partidas = new ArrayCollection();
        $this->evidencias = new ArrayCollection();
        $this->revisiones = new ArrayCollection();
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

    public function getDocumentoVerificacion(): ?string
    {
        return $this->documentoVerificacion;
    }

    public function setDocumentoVerificacion(?string $documentoVerificacion): static
    {
        $this->documentoVerificacion = $documentoVerificacion;

        return $this;
    }

    public function getDocumentoVerificacionDescripcion(): ?string
    {
        return $this->documentoVerificacionDescripcion;
    }

    public function setDocumentoVerificacionDescripcion(?string $documentoVerificacionDescripcion): static
    {
        $this->documentoVerificacionDescripcion = $documentoVerificacionDescripcion;

        return $this;
    }

    /**
     * Etiqueta legible del documento de verificación seleccionado.
     */
    public function getDocumentoVerificacionLabel(): ?string
    {
        return self::DOCUMENTOS_VERIFICACION[$this->documentoVerificacion] ?? null;
    }

    /**
     * Solo el tipo "Gastos por comprobar" exige documentación pendiente.
     */
    public function requiereDocumentacionPendiente(): bool
    {
        return $this->tipoSolicitud?->getNombre() === self::TIPO_SOLICITUD_REQUIERE_DOCUMENTACION_PENDIENTE;
    }

    public function getDocumentacionPendienteTexto(): ?string
    {
        return $this->requiereDocumentacionPendiente() ? self::DOCUMENTACION_PENDIENTE_TEXTO : null;
    }

    public function getJefeArea(): ?Personal
    {
        return $this->jefeArea;
    }

    public function setJefeArea(?Personal $jefeArea): static
    {
        $this->jefeArea = $jefeArea;

        return $this;
    }

    public function getAutoriza(): ?Personal
    {
        return $this->autoriza;
    }

    public function setAutoriza(?Personal $autoriza): static
    {
        $this->autoriza = $autoriza;

        return $this;
    }

    /**
     * @return Collection<int, SolicitudGastosEvidencia>
     */
    public function getEvidencias(): Collection
    {
        return $this->evidencias;
    }

    public function addEvidencia(SolicitudGastosEvidencia $evidencia): static
    {
        if (!$this->evidencias->contains($evidencia)) {
            $this->evidencias->add($evidencia);
            $evidencia->setSolicitud($this);
        }

        return $this;
    }

    public function removeEvidencia(SolicitudGastosEvidencia $evidencia): static
    {
        if ($this->evidencias->removeElement($evidencia)) {
            if ($evidencia->getSolicitud() === $this) {
                $evidencia->setSolicitud(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SolicitudGastosRevision>
     */
    public function getRevisiones(): Collection
    {
        return $this->revisiones;
    }

    public function addRevision(SolicitudGastosRevision $revision): static
    {
        if (!$this->revisiones->contains($revision)) {
            $this->revisiones->add($revision);
            $revision->setSolicitud($this);
        }

        return $this;
    }

    public function getRevisionPorCargo(string $cargo): ?SolicitudGastosRevision
    {
        foreach ($this->revisiones as $revision) {
            if ($revision->getCargo() === $cargo) {
                return $revision;
            }
        }

        return null;
    }

    public function contarRevisionesResueltas(): int
    {
        $resueltas = 0;
        foreach ($this->revisiones as $revision) {
            if (in_array($revision->getEstado(), ['aceptada', 'rechazada'], true)) {
                $resueltas++;
            }
        }

        return $resueltas;
    }

    public function getComprobante(): ?SolicitudGastosComprobante
    {
        return $this->comprobante;
    }

    public function setComprobante(?SolicitudGastosComprobante $comprobante): static
    {
        if ($comprobante !== null) {
            $comprobante->setSolicitud($this);
        }
        $this->comprobante = $comprobante;

        return $this;
    }
}
