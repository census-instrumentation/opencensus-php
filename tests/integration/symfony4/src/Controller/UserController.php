<?php
/**
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function homepage()
    {
        return new Response('Hello world!');
    }

    /**
     * @Route("/user", name="user")
     */
    public function index()
    {
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAll();

        return $this->json(array_map([$this, 'userToJson'], $users));
    }

    /**
     * @Route("/user/create", name="user_create")
     */
    public function create()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $user = new User();
        $user->setName('New User');
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json($this->userToJson($user));
    }

    /**
     * @Route("/user/{id}", name="user_show")
     */
    public function show($id)
    {
        $repository = $this->getDoctrine()->getRepository(User::class);
        $user = $repository->findOneBy(['id' => $id]);
        return $this->json($this->userToJson($user));
    }

    /**
     * @Route("/user/{id}/update")
     */
    public function update($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        }

        $user->setName('New Name');
        $entityManager->flush();

        return $this->json($this->userToJson($user));
    }

    /**
     * @Route("/user/{id}/delete")
     */
    public function delete($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json([]);
    }

    private function userToJson($user)
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName()
        ];
    }
}
