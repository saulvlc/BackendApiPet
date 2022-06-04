<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\User;
use BackendBundle\Entity\Animal;
use AppBundle\Services\Helpers;
use AppBundle\Services\jwtAuth;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;

class AnimalController extends Controller{

    public function newAction(Request $request, $id = null){
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if($authCheck){
            
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);
            $json = $request->get('json', null);

            if($json != null){
                $params = json_decode($json);

                $user_id = ($identity->sub != null) ? $identity->sub : null;
                $nombre = (isset($params->nombre)) ? $params->nombre : null;
                $tipo = (isset($params->tipo)) ? $params->tipo : null;
                $raza = (isset($params->raza)) ? $params->raza : null;
                $edad = (isset($params->edad)) ? $params->edad : null;
                $provincia = (isset($params->provincia)) ? $params->provincia : null;
                $localidad = (isset($params->localidad)) ? $params->localidad : null;
                $tamanio = (isset($params->tamanio)) ? $params->tamanio : null;
                $descripcion = (isset($params->descripcion)) ? $params->descripcion : null;

                if($user_id != null && $nombre != null && $tipo != null && $raza != null && $edad != null && $provincia != null && $localidad != null && $tamanio != null && $descripcion != null){
                    //entity manager
                    $em = $this->getDoctrine()->getManager();
                    
                    $isset_user = $em->getRepository('BackendBundle:User')->findOneBy(array(
                        'id' => $user_id
                    ));

                    if($id == null){

                        $animal = new Animal();
                        $animal->setNombre($params->nombre);
                        $animal->setTipo($params->tipo);
                        $animal->setRaza($params->raza);
                        $animal->setEdad($params->edad);
                        $animal->setProvincia($params->provincia);
                        $animal->setLocalidad($params->localidad);
                        $animal->setTamanio($params->tamanio);
                        $animal->setDescripcion($params->descripcion);
                        $animal->setUserId($isset_user->getId());

                        $em->persist($animal);
                        $em->flush();

                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'msg' => 'animal creado correctamente',
                            'animal' => $animal,
                            'user' => $isset_user->getNombre()
                        );

                    }else{

                        $animal = $em->getRepository('BackendBundle:Animal')->findOneBy(array(
                            'id' => $id
                        ));

                        if(isset($identity->sub) && $identity->sub == $animal->getUserId()){
                            
                            $animal->setNombre($params->nombre);
                            $animal->setTipo($params->tipo);
                            $animal->setRaza($params->raza);
                            $animal->setEdad($params->edad);
                            $animal->setProvincia($params->provincia);
                            $animal->setLocalidad($params->localidad);
                            $animal->setTamanio($params->tamanio);
                            $animal->setDescripcion($params->descripcion);

                            $em->persist($animal);
                            $em->flush();

                            $data = array(
                                'status' => 'success',
                                'code' => 200,
                                'msg' => 'animal actualizado correctamente',
                                'animal' => $animal,
                                'user' => $isset_user->getNombre()
                            );

                        }else{
                            $data = array(
                                'status' => 'error',
                                'code' => 400,
                                'msg' => 'No tiene permisos para actualizar el animal'
                            );
                        }
                    }
                    
                }else{
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'error al crear el animal'
                    );
                }
                
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'error en los datos del animal'
                );
            }

        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'autorización no válida'
            );
        }

        return $helpers->json($data);
    }

    public function mostrarAnimalesUsuarioAction(Request $request){
        
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if($authCheck){
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();
            $animals = $em->getRepository('BackendBundle:Animal')->findBy(array(
                'userId' => $identity->sub
            ));

            foreach ($animals as $animal) {
                        $animales[] = array(
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

            //Recogemos los datos de la pagina que nos vienen por GET
            $page = $request->query->getInt('page', 1);
            $paginator = $this->get('knp_paginator');
            $items_per_page = 9;
            $pagination = $paginator->paginate($animales, $page, $items_per_page);
            $total_items_count = $pagination->getTotalItemCount();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total_items_count,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total_items_count / $items_per_page),
                'data' => $pagination
            );

        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'autorización no válida'
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

    public function mostrarAnimalAction(Request $request, $id = null){

        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if($authCheck){
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();
            $animalBuscar = $em->getRepository('BackendBundle:Animal')->findOneBy(array(
                'id' => $id
            ));

            

            if(is_object($animalBuscar) && $animalBuscar && $animalBuscar->getUserId() == $identity->sub){
                
                $animal = array(
                    'id' => $animalBuscar->getId(),
                    'nombre' => $animalBuscar->getNombre(),
                    'tipo' => $animalBuscar->getTipo(),
                    'raza' => $animalBuscar->getRaza(),
                    'edad' => $animalBuscar->getEdad(),
                    'tamanio' => $animalBuscar->getTamanio(),
                    'provincia' => $animalBuscar->getProvincia(),
                    'localidad' => $animalBuscar->getLocalidad(),
                    'userId' => $animalBuscar->getUserId(),
                    'descripcion' => $animalBuscar->getDescripcion(),
                );
                
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'animal' => $animal
                );
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'animal no existe'
                );
            }

        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'autorización no válida'
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

        return $helpers->json($data);
    }

    public function buscadorAnimalesAction(Request $request){
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);
        $json = $request->get('json', null);

        if($authCheck){
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);

                $repository = $this->getDoctrine()->getRepository(Animal::class);
                $animals = $repository->findAll();

                foreach ($animals as $animal) {
                        $animales[] = array(
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
                
                //Recogemos los datos de la pagina que nos vienen por GET
                $page = $request->query->getInt('page', 1);
                $paginator = $this->get('knp_paginator');
                $items_per_page = 9;
                $pagination = $paginator->paginate($animales, $page, $items_per_page);
                $total_items_count = $pagination->getTotalItemCount();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'total_items_count' => $total_items_count,
                    'page_actual' => $page,
                    'items_per_page' => $items_per_page,
                    'total_pages' => ceil($total_items_count / $items_per_page),
                    'data' => $pagination
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

                //return $helpers->json($data);
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'autorización no válida'
            );
            return $helpers->json($data);
        }
    }

    public function eliminarAnimalAction(Request $request, $id = null){
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if($authCheck){
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();
            $animal = $em->getRepository('BackendBundle:Animal')->findOneBy(array(
                'id' => $id
            ));

            if(is_object($animal) && $animal && $animal->getUserId() == $identity->sub){
                $em->remove($animal);
                $em->flush();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'msg' => 'animal eliminado'
                );
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'animal no existe o no le pertenece'
                );
            }

        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'autorización no válida'
            );
        }

        return $helpers->json($data);
    }

    public function getAnimalAction(Request $request, $id = null){

        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if($authCheck){
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();
            $animalBuscar = $em->getRepository('BackendBundle:Animal')->findOneBy(array(
                'id' => $id
            ));

            

            if(is_object($animalBuscar) && $animalBuscar){

                $animal = array(
                    'id' => $animalBuscar->getId(),
                    'nombre' => $animalBuscar->getNombre(),
                    'tipo' => $animalBuscar->getTipo(),
                    'raza' => $animalBuscar->getRaza(),
                    'edad' => $animalBuscar->getEdad(),
                    'tamanio' => $animalBuscar->getTamanio(),
                    'provincia' => $animalBuscar->getProvincia(),
                    'localidad' => $animalBuscar->getLocalidad(),
                    'userId' => $animalBuscar->getUserId(),
                    'descripcion' => $animalBuscar->getDescripcion(),
                );

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'animal' => $animal
                );
            }else{
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'animal no existe'
                );
            }

        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'autorización no válida'
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

        return $helpers->json($data);
    }

    // public function todosAnimalesAction(Request $request){
    //     $helpers = $this->get(Helpers::class);
    //     $encoder = new JsonEncoder();
    //     $normalizer = new ObjectNormalizer();
    //     $jwt_auth = $this->get(jwtAuth::class);
    //     $token = $request->get('authorization', null);
    //     $authCheck = $jwt_auth->checkToken($token);
    //     $json = $request->get('json', null);

    //     if($authCheck){
    //         //conseguir los datos del usurario via token
    //         $identity = $jwt_auth->checkToken($token, true);

    //             $normalizer->setCircularReferenceHandler(function ($object, string $format = null, array $context = array()) {
    //             return $object->getId();
    //             });
    //             $serializer = new Serializer(array($normalizer), array($encoder));

    //             $repository = $this->getDoctrine()->getRepository(Animal::class);
    //             $animales = $repository->findAll();

    //             //Buscamos todos los tipo de animales y los guardamos en un array sin que se repitan
    //             $tipos = array();
    //             $razas = array();
    //             $provincias = array();

    //             foreach ($animales as $animal) {
    //                 if(!in_array($animal->getTipo(), $tipos)){
    //                     array_push($tipos, $animal->getTipo());
    //                 }
    //                 if(!in_array($animal->getRaza(), $razas)){
    //                     array_push($razas, $animal->getRaza());
    //                 }
    //                 if(!in_array($animal->getProvincia(), $provincias)){
    //                     array_push($provincias, $animal->getProvincia());
    //                 }
    //             }

    //             $data = array(
    //                 'status' => 'success',
    //                 'code' => 200,
    //                 'tipos' => $tipos,
    //                 'razas' => $razas,
    //                 'provincias' => $provincias,
    //                 'data' => $animales
    //             );

    //             $encoder = new JsonEncoder();
    //             $normalizer = new ObjectNormalizer();
    //             $normalizer->setCircularReferenceHandler(function ($object, string $format = null, array $context = array()) {
    //                 return $object->getId();
    //             });
    //             $serializer = new Serializer(array($normalizer), array($encoder));

    //             $response = new Response($serializer->serialize($data, 'json'));
    //             $response->headers->set('Content-Type', 'application/json');
    //             return $response;

    //     }else{
    //         $data = array(
    //             'status' => 'error',
    //             'code' => 400,
    //             'msg' => 'autorización no válida'
    //         );
    //         return $helpers->json($data);
    //     }
    // }

    public function searchAnimalesAction(Request $request, $tipo = null, $nombre = null, $provincia = null){
        $helpers = $this->get(Helpers::class);
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $jwt_auth = $this->get(jwtAuth::class);
        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);
        $json = $request->get('json', null);

        if($authCheck){
            //conseguir los datos del usurario via token
            $identity = $jwt_auth->checkToken($token, true);

                $normalizer->setCircularReferenceHandler(function ($object, string $format = null, array $context = array()) {
                return $object->getId();
                });
                $serializer = new Serializer(array($normalizer), array($encoder));

                $repository = $this->getDoctrine()->getRepository(Animal::class);

                if($tipo != "vacio" && $nombre != "vacio" && $provincia != "vacio"){
                    $animals = $repository->findBy(array(
                        'tipo' => $tipo,
                        'nombre' => $nombre,
                        'provincia' => $provincia
                    ));
                }elseif($tipo != "vacio" && $nombre != "vacio"){
                    $animals = $repository->findBy(array(
                        'tipo' => $tipo,
                        'nombre' => $nombre
                    ));
                }elseif($tipo != "vacio" && $provincia != "vacio"){
                    $animals = $repository->findBy(array(
                        'tipo' => $tipo,
                        'provincia' => $provincia
                    ));
                }elseif($nombre != "vacio" && $provincia != "vacio"){
                    $animals = $repository->findBy(array(
                        'nombre' => $nombre,
                        'provincia' => $provincia
                    ));
                }elseif($tipo != "vacio"){
                    $animals = $repository->findBy(array(
                        'tipo' => $tipo
                    ));
                }elseif($nombre != "vacio"){
                    $animals = $repository->findBy(array(
                        'nombre' => $nombre
                    ));
                }elseif($provincia != "vacio"){
                    $animals = $repository->findBy(array(
                        'provincia' => $provincia
                    ));
                }else{
                    $animals = $repository->findAll();
                }

                if(count($animals) > 0){
                    foreach ($animals as $animal) {
                        $animales[] = array(
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

                    //Recogemos los datos de la pagina que nos vienen por GET
                    $page = $request->query->getInt('page', 1);
                    $paginator = $this->get('knp_paginator');
                    $items_per_page = 9;
                    $pagination = $paginator->paginate($animales, $page, $items_per_page);
                    $total_items_count = $pagination->getTotalItemCount();

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'total_items_count' => $total_items_count,
                        'page_actual' => $page,
                        'items_per_page' => $items_per_page,
                        'total_pages' => ceil($total_items_count / $items_per_page),
                        'data' => $pagination
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
                        'msg' => 'No hay animales'
                    );
                    return $helpers->json($data);
                }
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'autorización no válida'
            );
            return $helpers->json($data);
        }
    }
}