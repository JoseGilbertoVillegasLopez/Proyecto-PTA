<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Form\UsuarioType;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/usuario')]
final class UsuarioController extends AbstractController
{
private UserPasswordHasherInterface $passwordHasher; // inyectamos el servicio de hasheo de contraseñas

public function __construct(UserPasswordHasherInterface $passwordHasher) // constructor para inyectar el servicio de hasheo de contraseñas 
{
    $this->passwordHasher = $passwordHasher;
}

    
    #[Route(name: 'app_usuario_index', methods: ['GET'])]
    public function index(UsuarioRepository $usuarioRepository): Response
    {
        return $this->render('usuario/index.html.twig', [
            'usuarios' => $usuarioRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_usuario_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $usuario = new Usuario();
        $form = $this->createForm(UsuarioType::class, $usuario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->asignarRolSegunPuesto($usuario);
            $plain = $form->get('plainPassword')->getData(); // obtenemos la contraseña en texto plano del formulario
            $this->aplicarHashPassword($usuario, $plain); // aplicamos el hash a la contraseña
            $entityManager->persist($usuario);
            $entityManager->flush();

            return $this->redirectToRoute('app_usuario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('usuario/new.html.twig', [
            'usuario' => $usuario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_usuario_show', methods: ['GET'])]
    public function show(Usuario $usuario): Response
    {
        return $this->render('usuario/show.html.twig', [
            'usuario' => $usuario,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_usuario_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Usuario $usuario, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UsuarioType::class, $usuario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->asignarRolSegunPuesto($usuario);
            $entityManager->flush();

            return $this->redirectToRoute('app_usuario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('usuario/edit.html.twig', [
            'usuario' => $usuario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_usuario_delete', methods: ['POST'])]
    public function delete(Request $request, Usuario $usuario, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$usuario->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($usuario);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_usuario_index', [], Response::HTTP_SEE_OTHER);
    }


    private function asignarRolSegunPuesto(Usuario $usuario): void
    {
        //obtengo el puesto del usuario
        $puesto = $usuario->getPersonal()?->getPuesto(); 
        //obtengo el nombre del puesto en mayusculas y sin espacios al inicio o final
        $nombre = mb_strtoupper(trim($puesto?->getNombre()?? ''), 'UTF-8'); 
        //remuevo acentos para mayor compatibilidad
        $nombre = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $nombre);


        //asigno el rol segun el nombre del puesto
         if ($nombre === 'DIRECCION GENERAL'){
            $usuario->setRol('ROLE_DIRECCION_GENERAL');
         }
        elseif (in_array($nombre, [
            'DIRECCION ACADEMICA',
            'DIRECCION DE PLANEACION Y VINCULACION',
            'DIRECCION SUBDIRECCION DE SERVICIOS ADMINISTRATIVOS'
        ])) {
                $usuario->setRol('ROLE_DIRECCION');
            }
        elseif (in_array($nombre, [
            'SUBDIRECCION ACADEMICA',
            'SUBDIRECCION DE POSGRADO E INVESTIGACION',
            'SUBDIRECCION DE VINCULACION',
            'SUBDIRECCION DE PLANEACION'
        ])) {
                $usuario->setRol('ROLE_SUBDIRECCION');
            }
        elseif (in_array($nombre, [
            'DEV',
            'ADMIN'
        ])) {
                $usuario->setRol('ROLE_ADMIN');
            }
        else{
            $usuario->setRol('ROLE_USER');
        }
    }

    private function aplicarHashPassword(Usuario $usuario, ?string $plainPassword): void
{
    if (!$plainPassword) {
        return;
    }
    $hash = $this->passwordHasher->hashPassword($usuario, $plainPassword);
    $usuario->setPassword($hash);
}

    
}
