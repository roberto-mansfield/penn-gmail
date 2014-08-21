<?php

namespace SAS\IRAD\BulkCreateAccountsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Account
 * @ORM\Entity
 * @ORM\Table(name="account")
 * @ORM\Entity(repositoryClass="SAS\IRAD\BulkCreateAccountsBundle\Entity\AccountRepository")
 */
class Account
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="penn_id", type="string", length=16)
     */
    private $pennId;
    
    /**
     * @var string
     * @ORM\Column(name="pennkey", type="string", length=16, nullable=true)
     */
    private $pennkey;

    /**
     * @var boolean
     * @ORM\Column(name="created", type="boolean")
     */
    private $created;
    

    public function __construct() {
        $this->created = false;
    }
    
    /**
     * Get id
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set created
     * @param boolean $created
     * @return Account
     */
    public function setCreated($created) {
        $this->created = $created;
        return $this;
    }

    /**
     * Get createdOn
     * @return boolean
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set pennkey
     * @param string $pennkey
     * @return Account
     */
    public function setPennkey($pennkey) {
        $this->pennkey = $pennkey;
        return $this;
    }

    /**
     * Get pennkey
     * @return string 
     */
    public function getPennkey() {
        return $this->pennkey;
    }

    /**
     * Set pennId
     * @param string $pennId
     * @return Account
     */
    public function setPennId($pennId) {
        $this->pennId = $pennId;
        return $this;
    }

    /**
     * Get pennId
     * @return string 
     */
    public function getPennId() {
        return $this->pennId;
    }

}
