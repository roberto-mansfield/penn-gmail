<?php

namespace Penn\AccountLogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccountLog
 *
 * @ORM\Table(name="accountLog")
 * @ORM\Entity(repositoryClass="Penn\AccountLogBundle\Entity\AccountLogRepository")
 */
class AccountLog
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="log_timestamp", type="datetime")
     */
    private $logTimestamp;

    /**
     * @var string
     * @ORM\Column(name="pennkey", type="string", length=16)
     */
    private $pennkey;

    /**
     * @var string
     * @ORM\Column(name="penn_id", type="string", length=16)
     */
    private $pennId;

    /**
     * @var string
     * @ORM\Column(name="log_type", type="string", length=16)
     */
    private $logType;

    /**
     * @var string
     * @ORM\Column(name="message", type="string", length=200)
     */
    private $message;


    /**
     * Get id
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set logTimestamp
     * @param \DateTime $logTimestamp
     * @return AccountLog
     */
    public function setLogTimestamp($logTimestamp) {
        $this->logTimestamp = $logTimestamp;
        return $this;
    }

    /**
     * Get logTimestamp
     * @return \DateTime 
     */
    public function getLogTimestamp() {
        return $this->logTimestamp;
    }

    /**
     * Set pennkey
     * @param string $pennkey
     * @return AccountLog
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
     * @return AccountLog
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

    /**
     * Set logType
     * @param string $logType
     * @return AccountLog
     */
    public function setLogType($logType) {
        $this->logType = $logType;
        return $this;
    }

    /**
     * Get logType
     * @return string 
     */
    public function getLogType() {
        return $this->logType;
    }

    /**
     * Set message
     * @param string $message
     * @return AccountLog
     */
    public function setMessage($message) {
        $this->message = $message;
        return $this;
    }

    /**
     * Get message
     * @return string 
     */
    public function getMessage() {
        return $this->message;
    }
}
