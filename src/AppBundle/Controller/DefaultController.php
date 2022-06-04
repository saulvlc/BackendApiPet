<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Services\Helpers;
use AppBundle\Services\jwtAuth;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    public function loginAction(Request $request){
        $helpers = $this->get(Helpers::class);

        //Recibir json por Post
        $json = $request->get('json', null);

        //Array de datos a devolver por defecto
        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'Login no válido'
        );

        if($json != null ){
            //Hacemos el login
            //Convertimos un json a un objeto
            $params = json_decode($json);

            //Comprobamos si los datos son correctos y no estan vacios
            $email = (isset($params->email)) ? $params->email : null;
            $password = (isset($params->password)) ? $params->password : null;
            $getHahs = (isset($params->getHash)) ? $params->getHash : null;

            //Validamos el email
            $emailContraint = new Assert\Email();
            $emailContraint->message = "El email no es válido";
            $validate_email = $this->get("validator")->validate($email, $emailContraint);

            //Ciframos la contraseña
            $pwd = hash('sha256', $password);


            //Hacemos las comprobaciones
            if(\count($validate_email) == 0 && $email != null && $password != null){
                
                $jwt_Auth = $this->get(jwtAuth::class);

                if($getHahs == null || $getHahs == 'false'){
                    $singUp = $jwt_Auth->singUp($email, $pwd);
                }else{
                    $singUp = $jwt_Auth->singUp($email, $pwd, true);
                }

                return $this->json($singUp);
                
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'El email o la contraseña son incorrectos'
                );
            }

            
        }else{

        }

        return $helpers->json($data);
    }

    public function pruebaAction(Request $request)
    {
        $token = $request->get('authorization', null);
        $helpers = $this->get(Helpers::class);
        $jwt_Auth = $this->get(jwtAuth::class);
        
        if($token && $jwt_Auth->checkToken($token)){

            $em = $this->getDoctrine()->getManager();
            $users = $em->getRepository('BackendBundle:User')->findAll();
            
            return $helpers->json(array(
                'status' => 'success',
                'code' => 200,
                'data' => $users
            ));
        }else{
            return $helpers->json(array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'autorización no válida'
            ));
        }

        

        // return new JsonResponse(array(
        //     'status' => 'success',
        //     'data' => $users[0]->getNombre()
        // ));
    }
}
