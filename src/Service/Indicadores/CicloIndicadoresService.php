<?php

namespace App\Service\Indicadores;

use App\Entity\CicloIndicadores;
use App\Repository\CicloIndicadoresRepository;
use Doctrine\ORM\EntityManagerInterface;

class CicloIndicadoresService
{
    public function __construct(
        private CicloIndicadoresRepository $repository,
        private EntityManagerInterface $em,
        private ?string $fakeToday = null
    ) {
    }

    /**
     * @return CicloIndicadores[]
     */
    public function getCiclosVisibles(): array
    {
        $this->ensureCicloActual();

        return $this->repository->findLatestVisible(3);
    }

    /**
     * @return CicloIndicadores[]
     */
    public function getCiclosDisponibles(): array
    {
        $this->ensureCicloActual();

        return $this->repository->findAllOrderByFechaApertura();
    }

    /**
     * @param array<int, int|string|null> $selectedIds
     * @return CicloIndicadores[]
     */
    public function getCiclosParaVista(array $selectedIds = []): array
    {
        $defaultCiclos = $this->getCiclosVisibles();

        if ($selectedIds === []) {
            return $defaultCiclos;
        }

        $ciclos = [];

        foreach ([0, 1, 2] as $index) {
            $selectedId = isset($selectedIds[$index]) ? (int) $selectedIds[$index] : null;
            $ciclo = $selectedId ? $this->repository->find($selectedId) : null;
            $ciclos[] = $ciclo ?? $defaultCiclos[$index] ?? null;
        }

        return array_values(array_filter($ciclos));
    }

    public function ensureCicloActual(?\DateTimeInterface $date = null): CicloIndicadores
    {
        $date ??= $this->resolveToday();
        $nombre = $this->buildNombreFromDate($date);
        $ciclo = $this->repository->findOneByNombre($nombre);

        if (!$ciclo) {
            $startYear = $this->getStartYearFromDate($date);
            $ciclo = $this->createCiclo($startYear);
            $this->em->persist($ciclo);
        }

        foreach ($this->repository->findAllOrderByFechaApertura() as $existing) {
            $existing->setActivo($existing->getNombre() === $nombre);
        }

        $this->em->flush();

        return $ciclo;
    }

    private function createCiclo(int $startYear): CicloIndicadores
    {
        $endYear = $startYear + 1;

        return (new CicloIndicadores())
            ->setNombre($startYear . '-' . $endYear)
            ->setFechaApertura(new \DateTimeImmutable($startYear . '-07-01'))
            ->setFechaCierre(new \DateTimeImmutable($endYear . '-06-30'))
            ->setActivo(false)
            ->setVisible(true);
    }

    private function buildNombreFromDate(\DateTimeInterface $date): string
    {
        $startYear = $this->getStartYearFromDate($date);

        return $startYear . '-' . ($startYear + 1);
    }

    private function getStartYearFromDate(\DateTimeInterface $date): int
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        return $month >= 7 ? $year : $year - 1;
    }

    private function resolveToday(): \DateTimeImmutable
    {
        $fakeToday = trim($this->fakeToday ?? '');

        if ($fakeToday === '') {
            return new \DateTimeImmutable('today');
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $fakeToday);

        return $date ?: new \DateTimeImmutable('today');
    }
}
