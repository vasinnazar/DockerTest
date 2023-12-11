<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Cassandra\Date;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\Cloner\Data;

class UserController extends AbstractController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    #[Route('/user', name: 'app_user')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    public function show(User $user)
    {

    }

//    /**
//     * @Route("/user/create",name="homepage")
//     */
//    #[Route('/user/create',  methods:['PUT'])]
    public function create(Request $request/*, ValidatorInterface $validator*/): Response
    {
        $date = '2001-03-24';

//        $user = new User();
//
//        // ... сделать что-то с объектом $author
//
//        $errors = $validator->validate($user);
//
//        if (count($errors) > 0) {
//            /*
//             * Использует метод __toString в переменной $errors, которая является объектом
//             * ConstraintViolationList. Это дает хорошую строку для отладки.
//             */
//            $errorsString = (string) $errors;
//
//            return new Response($errorsString);
//        }

        $data = json_decode($request->getContent());
        $user = $this->userRepository->create(
            $data->name,
            $data->email,
            (int)$data->sex,
            $data->age,
            $data->phone,
            new DateTime($data->birthday)
        );

//        $val = $date->name;
//        var_dump($val);
//        echo $val;
       return new Response($user);
    }

    public function update(User $user)
    {

    }

    public function delete(User $user)
    {

    }
}
