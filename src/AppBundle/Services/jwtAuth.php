<?php
namespace AppBundle\Services;

use Firebase\JWT\JWT;

class jwtAuth{
    public $manager;
    public $key;

    public function __construct($manager){
        $this->manager = $manager;
        $this->key = "123clave-secreta321";
    }

    public function singUp($email, $password, $getHash = NULL){

        $user = $this->manager->getRepository('BackendBundle:User')->findOneBy(array(
            "email" => $email,
            "password" => $password
        ));
        $singUp = false;
        $data = array();

        if(is_object($user)){
            $singUp = true;
        }

        if($singUp == true){

            //Generamos el token JWT
            $token = array(
                'sub' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getNombre(),
                'surname' => $user->getApellidos(),
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getNombre(),
                    'surname' => $user->getApellidos(),
                    'ciudad' => $user->getCiudad(),
                    'edad' => $user->getEdad(),
                    'telefono' => $user->getTelefono(),
                    'roles' => $user->getRoles(),
                    'password' => $user->getPassword(),
                ]
            );

            $jwt = JWT::encode($token, $this->key, 'HS256');
            $decoded = JWT::decode($jwt, $this->key, array('HS256'));

            if($getHash == NULL){
                $data = $jwt;
            }else{
                $data = $decoded;
            }
            
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'El usuario no se ha podido identificar'
            );
        }

        return $data;
    }

    public function checkToken($jwt, $getIdentity = false){
        $auth = false;

        try{
            $decoded = JWT::decode($jwt, $this->key, array('HS256'));
        }catch(\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }

        if(isset($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }

        if($getIdentity == true){
            return $decoded;
        }else{
            return $auth;
        }
    }
}