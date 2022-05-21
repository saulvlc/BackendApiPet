<?php

namespace BackendBundle\Entity;

/**
 * Imagenes
 */
class Imagenes
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $imagen;

    /**
     * @var \BackendBundle\Entity\Animal
     */
    private $animal;


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
     * Set imagen
     *
     * @param string $imagen
     *
     * @return Imagenes
     */
    public function setImagen($imagen)
    {
        $this->imagen = $imagen;

        return $this;
    }

    /**
     * Get imagen
     *
     * @return string
     */
    public function getImagen()
    {
        return $this->imagen;
    }

    /**
     * Set animal
     *
     * @param \BackendBundle\Entity\Animal $animal
     *
     * @return Imagenes
     */
    public function setAnimal(\BackendBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \BackendBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }
}

