<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\User;
use BackendBundle\Entity\Intereses;
use BackendBundle\Entity\Animal;
use AppBundle\Services\Helpers;
use AppBundle\Services\jwtAuth;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

class UserController extends Controller{

    public function newAction(Request $request){
        $helpers = $this->get(Helpers::class);
        $json = $request->get('json', null);
        $params = json_decode($json);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => array(
                'email' => $params->email,
                'password' => $params->password,
                'apellido' => $params->apellidos,
                'nombre' => $params->nombre,
                'telefono' => $params->telefono,
                'ciudad' => $params->ciudad,
                'edad' => $params->edad,
                'image' => $params->imagen
            ),
            'data' => $json
        );

        

        if($json != null){
            $email = (isset($params->email)) ? $params->email : null;
            $nombre = (isset($params->nombre)) ? $params->nombre : null;
            $apellidos = (isset($params->apellidos)) ? $params->apellidos : null;
            $password = (isset($params->password)) ? $params->password : null;
            $ciudad = (isset($params->ciudad)) ? $params->ciudad : null;
            $telefono = (isset($params->telefono)) ? $params->telefono : null;
            $edad = (isset($params->edad)) ? $params->edad : null;
            $imagen = (isset($params->imagen)) ? $params->imagen : null;
            $role = 'ROLE_USER';

            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "El email no es válido";
            $validate_email = $this->get("validator")->validate($email, $emailConstraint);

            if(\count($validate_email) == 0 && $email != null && $nombre != null && $apellidos != null && $password != null && $ciudad != null && $telefono != null && $edad != null && $imagen != null){
                $user = new User();
                $user->setEmail($email);
                $user->setNombre($nombre);
                $user->setApellidos($apellidos);
                $user->setRoles($role);
                $user->setCiudad($ciudad);
                $user->setTelefono($telefono);
                $user->setEdad($edad);

                if($imagen != null){
                    //Recibimos un objeto como imagen la imagen a app/Resources/imagenes/perfil
                    $user->setImagen($imagen);
                
                    // $base64 = substr($imagen, strpos($imagen, ",")+1);
                    // $data = base64_decode($base64);
                    // $file = $this->getParameter('kernel.project_dir').'/web/imagenes/perfil/'.$email.'.png';
                    // file_put_contents($file, $data);
                    // $user->setImagen($email.'.png');
                }

                //Ciframos la contraseña
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);

                $em = $this->getDoctrine()->getManager();
                $isset_user = $em->getRepository('BackendBundle:User')->findBy(array(
                    'email' => $email
                ));

                if(count($isset_user) == 0){
                    $em->persist($user);
                    $em->flush();

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'msg' => 'usuario creado correctamente',
                        //'user' => $user
                    );
                }else{
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'el usuario ya existe'
                    );
                }
            }
        }

        return $helpers->json($data);
    }

    public function editAction(Request $request){
        $helpers = $this->get(Helpers::class);
        
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);
        
        if($authCheck){
            //entity manager
            $em = $this->getDoctrine()->getManager();
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            //conseguir el usuario a actualizar
            $id = "";
            $id = $identity->sub;
            
            //recoger datos del post
            $json = $request->get('json', null);
            $params = json_decode($json);

            //array de error por defecto
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'error al editar el usuario'
            );
            if($json != null){
                $email = (isset($params->email)) ? $params->email : null;
                $nombre = (isset($params->nombre)) ? $params->nombre : null;
                $apellidos = (isset($params->apellidos)) ? $params->apellidos : null;
                $password = (isset($params->password)) ? $params->password : null;
                $ciudad = (isset($params->ciudad)) ? $params->ciudad : null;
                $telefono = (isset($params->telefono)) ? $params->telefono : null;
                $edad = (isset($params->edad)) ? $params->edad : null;
                //$imagen = (isset($params->imagen)) ? $params->imagen : null;

                $emailConstraint = new Assert\Email();
                $emailConstraint->message = "El email no es válido";
                $validate_email = $this->get("validator")->validate($email, $emailConstraint);

                if(\count($validate_email) == 0 && $email != null && $nombre != null && $apellidos != null && $ciudad != null && $telefono != null && $edad != null){
                    //Actualizamos el usuario
                    $users = $em->getRepository('BackendBundle:User')->findAll();
                    foreach($users as $user){
                        if($user->getId() == $id){
                            $user->setEmail($email);
                            $user->setNombre($nombre);
                            $user->setApellidos($apellidos);
                            $user->setCiudad($ciudad);
                            $user->setTelefono($telefono);
                            $user->setEdad($edad);
                            $user->setRoles('ROLE_USER'); 

                            //Ciframos la contraseña
                            if($password != null){
                                $pwd = hash('sha256', $password);
                                $user->setPassword($pwd);
                            }
                        }
                    }
                    
                    // if($imagen != null){
                    //     $image_path = $this->getParameter('image_directory');
                    //     $ext = $imagen->guessExtension();
                    //     if($ext == 'jpeg' || $ext == 'jpg' || $ext == 'png' || $ext == 'gif'){
                    //         $imagen_name = $helpers->getToken().'.'.$ext;
                    //         $imagen->move($image_path, $imagen_name);
                    //         $user->setImagen($imagen_name);
                    //     }else{
                    //         $data = array(
                    //             'status' => 'error',
                    //             'code' => 400,
                    //             'msg' => 'El formato de la imagen no es valido'
                    //         );
                    //         return $helpers->json($data);
                    //     }
                    // }
                    
                    foreach($users as $user){
                        if($email == $user->getEmail()){
                            $em->persist($user);
                            $em->flush();

                            $data = array(
                                'status' => 'success',
                                'code' => 200,
                                'msg' => 'usuario actualizado correctamente',
                            );
                        }
                    }

                    
                }
            }
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'No se puede actualizar el usuario, el usuario no esta identificado'
            );
        }

        return $helpers->json($data);
    }

    //Enviar los datos del usuario y su imagen
    public function getAction(Request $request, $email){
        $helpers = $this->get(Helpers::class);
        
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        //array de error por defecto
        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'error al enviar la imagen y datos del usuario'
        );
        
        if($authCheck){
            //entity manager
            $em = $this->getDoctrine()->getManager();
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            //conseguir el usuario a actualizar

            if($email == $identity->email){
                $user = $em->getRepository('BackendBundle:User')->findOneBy(array('email' => $email));

                //Extraemos la imagen del usuario y la convertimos
                $imagen_user = $user->getImagen();
                // $imagen_user = str_replace('data:image/png;base64,', '', $imagen_user);
                // $imagen_user = str_replace(' ', '+', $imagen_user);
                // $imagen_user = base64_decode($imagen_user);
                // $imagen_user = 'data:image/png;base64,' . base64_encode($imagen_user);
                $imagen_user_string = base64_encode(stream_get_contents($imagen_user));
                $imagen_user = 'data:image/png;base64,' . $imagen_user_string;

                if($user){
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                            'data' => array(
                                'id' => $user->getId(),
                                'nombre' => $user->getNombre(),
                                'apellidos' => $user->getApellidos(),
                                'email' => $user->getEmail(),
                                'ciudad' => $user->getCiudad(),
                                'telefono' => $user->getTelefono(),
                                'edad' => $user->getEdad(),
                                'imagen' => $imagen_user
                            )
                    );

                    $encoder = new JsonEncoder();
                    $normalizer = new ObjectNormalizer();
                    $normalizer->setCircularReferenceHandler(function ($object, string $format = null, array $context = array()) {
                        return $object->getId();
                    });
                    $serializer = new Serializer(array($normalizer), array($encoder));

                    $response = new Response($serializer->serialize($data, 'json'));
                    $response->headers->set('Content-Type', 'application/json');

                    return $response;
                }else{
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'Usuario no encontrado'
                    );
                }
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'No tienes permisos'
                );
            }
        }

            return $helpers->json($data);
    }

    //Introducir los interese del usuario
    public function newInteresesAction(Request $request){
        $helpers = $this->get(Helpers::class);
        
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        //array de error por defecto
        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'error al enviar la imagen y datos del usuario'
        );
        
        if($authCheck){
            //entity manager
            $em = $this->getDoctrine()->getManager();
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            //conseguir el usuario a actualizar
            $json = $request->get('json', null);
            $params = json_decode($json);
            $tipo = (isset($params->tipo)) ? $params->tipo : null;
            $provincia = (isset($params->provincia)) ? $params->provincia : null;

            if(!$json){
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'No se ha enviado ningun dato'
                );
            }else{
                
                
                $user = $em->getRepository('BackendBundle:User')->findOneBy(array('email' => $identity->email));

                if($user){
                    //Buscamos si el usuario tiene intereses
                    $intereses = $em->getRepository('BackendBundle:Intereses')->findOneBy(array('user' => $user));
                    //Si no tiene intereses lo creamos
                    if(!$intereses){
                        $intereses = new Intereses();
                        $intereses->setTipo($tipo);
                        $intereses->setProvincia($provincia);
                        $intereses->setUser($user);

                        $em->persist( $intereses);
                        $em->flush();

                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'msg' => 'intereses actualizados correctamente'
                        );
                    }else{
                        //Si ya tiene intereses lo actualizamos
                        $intereses->setTipo($tipo);
                        $intereses->setProvincia($provincia);
                        $intereses->setUser($user);

                        $em->persist( $intereses);
                        $em->flush();

                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'msg' => 'intereses actualizados correctamente'
                        );
                    }
                    
                }else{
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'Usuario no encontrado'
                    );
                }
            }
        }

        return $helpers->json($data);
    }

    //Devolver los interese del usuario
    public function getInteresesAction(Request $request, $email){
        $helpers = $this->get(Helpers::class);
        
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        //array de error por defecto
        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'error con los intereses del usuario'
        );
        
        if($authCheck){
            //entity manager
            $em = $this->getDoctrine()->getManager();
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            //conseguir el usuario a actualizar
            $user = $em->getRepository('BackendBundle:User')->findOneBy(array('email' => $email));

            if($user){
                $intereses = $em->getRepository('BackendBundle:Intereses')->findOneBy(array('user' => $user));

                if($intereses){
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'data' => array(
                            'id' => $intereses->getId(),
                            'tipo' => $intereses->getTipo(),
                            'provincia' => $intereses->getProvincia()
                        )
                    );
                }else{
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'No hay intereses'
                    );
                }
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Usuario no encontrado'
                );
            }
        }
        return $helpers->json($data);
    }

    //Agregar animal favorito al usuario
    public function favoritosAction(Request $request, $email, $id){
        $helpers = $this->get(Helpers::class);
        
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        //array de error por defecto
        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'error con los favoritos del usuario'
        );
        
        if($authCheck){
            //entity manager
            $em = $this->getDoctrine()->getManager();
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            //conseguir el usuario a actualizar
            $user = $em->getRepository('BackendBundle:User')->findOneBy(array('email' => $email));
            $animal = $em->getRepository('BackendBundle:Animal')->findOneBy(array('id' => $id));

            if($user && $animal){
                $user->addAnimal($animal);
                $animal->addUser($user);

                $em->persist($user);
                $em->persist($animal);
                $em->flush();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'msg' => 'Animal agregado a favoritos'
                );
            }
        }
        return $helpers->json($data);
    }

    //Eliminar animal del usuario
    public function eliminarAnimalAction(Request $request, $email, $id){
        $helpers = $this->get(Helpers::class);
        
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        //array de error por defecto
        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'error al eliminar el animal'
        );
        
        if($authCheck){
            //entity manager
            $em = $this->getDoctrine()->getManager();
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            //conseguir el usuario a actualizar
            $user = $em->getRepository('BackendBundle:User')->findOneBy(array('email' => $email));
            $animal = $em->getRepository('BackendBundle:Animal')->findOneBy(array('id' => $id));

            //Si existe el usuario y el animal
            if($user && $animal){
                //Si el usuario tiene el animal
                if($user->getId() == $animal->getUserId()){
                    //Eliminamos el animal de la base de datos
                    $em->remove($animal);
                    $em->flush();

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'msg' => 'Animal eliminado correctamente'
                    );
                }else{
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'El animal no pertenece al usuario'
                    );
                }
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Usuario o animal no encontrado'
                );
            }
        }
        return $helpers->json($data);
    }

    //Eliminar intereses del usuario
    public function eliminarInteresesAction(Request $request, $email){
        $helpers = $this->get(Helpers::class);
        
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        //array de error por defecto
        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'error al eliminar los intereses'
        );
        
        if($authCheck){
            //entity manager
            $em = $this->getDoctrine()->getManager();
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            //conseguir el usuario a actualizar
            $user = $em->getRepository('BackendBundle:User')->findOneBy(array('email' => $email));

            //Si existe el usuario
            if($user){
                //Buscamos los intereses de ese usuario
                $intereses = $em->getRepository('BackendBundle:Intereses')->findOneBy(array('user' => $user));
                //Eliminamos los intereses de la base de datos
                $em->remove($intereses);
                $em->flush();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'msg' => 'Intereses eliminados correctamente'
                );
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Usuario no encontrado'
                );
            }
        }
        return $helpers->json($data);
    }

    //Eliminar animal favorito del usuario
    public function eliminarFavoritoAction(Request $request, $email, $id){
        $helpers = $this->get(Helpers::class);
        
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        //array de error por defecto
        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'error al eliminar el animal'
        );
        
        if($authCheck){
            //entity manager
            $em = $this->getDoctrine()->getManager();
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            //conseguir el usuario a actualizar
            $user = $em->getRepository('BackendBundle:User')->findOneBy(array('email' => $email));
            $animal = $em->getRepository('BackendBundle:Animal')->findOneBy(array('id' => $id));

            //Si existe el usuario y el animal
            if($user && $animal){
                //Si el usuario tiene el animal en favoritos
                if($user->getAnimal()->contains($animal) && $animal->getUser()->contains($user)){
                    //Eliminamos el animal de la coleccion de favoritos del usuario y de la coleccion de favoritos del animal
                    $user->removeAnimal($animal);
                    $animal->removeUser($user);
                    $em->persist($user);
                    $em->persist($animal);
                    $em->flush();

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'msg' => 'Animal eliminado de favoritos'
                    );
                }
                
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'No existe el animal'
                );
            }
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'error con la autenticacion'
            );
        }
        return $helpers->json($data);
    }

    //Obtener los animales favoritos del usuario
    public function getFavoritosAction(Request $request, $email){
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        //array de error por defecto
        $data = array(
            'status' => 'error',
            'code' => 400,
            'msg' => 'error al obtener los animales favoritos'
        );
        
        if($authCheck){
            //entity manager
            $em = $this->getDoctrine()->getManager();
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            //conseguir el usuario a actualizar
            $user = $em->getRepository('BackendBundle:User')->findOneBy(array('email' => $email));

            //Si existe el usuario
            if($user){
                //Buscamos los animales favoritos del usuario
                $animales = $user->getAnimal();
                //Si tiene animales en favoritos
                if(count($animales) > 0){
                    foreach ($animales as $animal) {
                        $datos[] = array(
                            'id' => $animal->getId(),
                            'nombre' => $animal->getNombre(),
                            'tipo' => $animal->getTipo(),
                            'raza' => $animal->getRaza(),
                            'edad' => $animal->getEdad(),
                            'tamanio' => $animal->getTamanio(),
                            'provincia' => $animal->getProvincia(),
                            'localidad' => $animal->getLocalidad(),
                            'userId' => $animal->getUserId(),
                            'descripcion' => $animal->getDescripcion(),
                        );
                    }
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'data' => $datos
                    );
                }else{
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'No tienes animales favoritos'
                    );
                }
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Usuario no encontrado'
                );
            }
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'error con la autenticacion'
            );
        }

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $normalizer->setCircularReferenceHandler(function ($object, string $format = null, array $context = array()) {
            return $object->getId();
        });
        $serializer = new Serializer(array($normalizer), array($encoder));

        $response = new Response($serializer->serialize($data, 'json'));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}