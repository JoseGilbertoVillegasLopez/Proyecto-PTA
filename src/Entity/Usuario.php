<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\ORM\Mapping as ORM;
// Esta línea le dice a PHP:
//“Trae (importa) esta interfaz llamada PasswordAuthenticatedUserInterface desde el componente de seguridad de Symfony”.
//En otras palabras, estás importando una interfaz predefinida de Symfony que define el contrato para cualquier entidad que maneje contraseñas seguras 
// (por ejemplo, un usuario que inicia sesión).
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]

//Esta línea indica que tu clase Usuario “implementa” esa interfaz.
//👉 “Implementar” significa que aceptas las reglas definidas en la interfaz, y por tanto debes incluir los métodos obligatorios que esta interfaz declara.
//En este caso, PasswordAuthenticatedUserInterface te obliga a tener, al menos, este método: getPassword(): ? string; que ya biene por defecto en la tabla.
class Usuario implements PasswordAuthenticatedUserInterface

{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $usuario = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $rol = null;

    #[ORM\Column (options: ['default' => true])]
    private ?bool $activo = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personal $personal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): ?string
    {
        return $this->usuario;
    }

    public function setUsuario(string $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRol(): ?string
    {
        return $this->rol;
    }

    public function setRol(string $rol): static
    {
        $this->rol = $rol;

        return $this;
    }

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }

    public function getPersonal(): ?Personal
    {
        return $this->personal;
    }

    public function setPersonal(?Personal $personal): static
    {
        $this->personal = $personal;

        return $this;
    }
}
