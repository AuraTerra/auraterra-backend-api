<?php
declare(strict_types=1);

namespace src\Models;

class Usuario {
    private ?int $id;
    private string $nombre;
    private string $email;
    private string $password;
    private string $rol;
    private string $estado;

    public function __construct(
        ?int $id,
        string $nombre,
        string $email,
        string $password,
        string $rol = 'agricultor',
        string $estado = 'prueba'
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->password = $password;
        $this->rol = $rol;
        $this->estado = $estado;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getNombre(): string {
        return $this->nombre;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getRol(): string {
        return $this->rol;
    }

    public function getEstado(): string {
        return $this->estado;
    }

    public function setEstado(string $estado): void {
        $this->estado = $estado;
    }
}