<?php

namespace BackendBundle\Entity;

/**
 * Intereses
 */
class Intereses
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $tipo = 'NULL';

    /**
     * @var string
     */
    private $raza = 'NULL';

    /**
     * @var integer
     */
    private $edad = 'NULL';

    /**
     * @var string
     */
    private $provincia = 'NULL';

    /**
     * @var string
     */
    private $localidad = 'NULL';

    /**
     * @var integer
     */
    private $tamanio = 'NULL';

    /**
     * @var \BackendBundle\Entity\User
     */
    private $user;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set tipo
     *
     * @param string $tipo
     *
     * @return Intereses
     */
    public function setTipo($tipo)
    {
        $this->tipo = $tipo;

        return $this;
    }

    /**
     * Get tipo
     *
     * @return string
     */
    public function getTipo()
    {
        return $this->tipo;
    }

    /**
     * Set raza
     *
     * @param string $raza
     *
     * @return Intereses
     */
    public function setRaza($raza)
    {
        $this->raza = $raza;

        return $this;
    }

    /**
     * Get raza
     *
     * @return string
     */
    public function getRaza()
    {
        return $this->raza;
    }

    /**
     * Set edad
     *
     * @param integer $edad
     *
     * @return Intereses
     */
    public function setEdad($edad)
    {
        $this->edad = $edad;

        return $this;
    }

    /**
     * Get edad
     *
     * @return integer
     */
    public function getEdad()
    {
        return $this->edad;
    }

    /**
     * Set provincia
     *
     * @param string $provincia
     *
     * @return Intereses
     */
    public function setProvincia($provincia)
    {
        $this->provincia = $provincia;

        return $this;
    }

    /**
     * Get provincia
     *
     * @return string
     */
    public function getProvincia()
    {
        return $this->provincia;
    }

    /**
     * Set localidad
     *
     * @param string $localidad
     *
     * @return Intereses
     */
    public function setLocalidad($localidad)
    {
        $this->localidad = $localidad;

        return $this;
    }

    /**
     * Get localidad
     *
     * @return string
     */
    public function getLocalidad()
    {
        return $this->localidad;
    }

    /**
     * Set tamanio
     *
     * @param integer $tamanio
     *
     * @return Intereses
     */
    public function setTamanio($tamanio)
    {
        $this->tamanio = $tamanio;

        return $this;
    }

    /**
     * Get tamanio
     *
     * @return integer
     */
    public function getTamanio()
    {
        return $this->tamanio;
    }

    /**
     * Set user
     *
     * @param \BackendBundle\Entity\User $user
     *
     * @return Intereses
     */
    public function setUser(\BackendBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \BackendBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}

