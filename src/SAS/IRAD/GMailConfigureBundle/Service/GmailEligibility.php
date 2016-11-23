<?php

namespace SAS\IRAD\GMailConfigureBundle\Service;

use SAS\IRAD\PennGroupsBundle\Service\PennGroupsQueryCache;
use SAS\IRAD\PersonInfoBundle\PersonInfo\PersonInfoInterface;

/**
 * Interface with Penngroups to determine eligibility status for Gmail
 * @author robertom
 *
 */
class GmailEligibility {
    
    private $eligibleGroups;
    private $penngroups;
    private $params;
    
    public function __construct(PennGroupsQueryCache $penngroups, array $google_params) {
        $this->penngroups  = $penngroups;
        $this->params = $google_params;
        $this->eligibleGroups = false;
    }
    
    /**
     * Return true if a user is GMail eligible (can be found in eligibility penngroup)
     * @param PersonInfoInterface $person
     * @return boolean
     */
    public function isGmailEligible(PersonInfoInterface $person) {
        return $this->penngroups->isMemberOf($this->params['eligibility-list'], $person->getPennId());
    }
    
    
    /**
     * Return the eligibility penngroups for $person
     * @param PersonInfoInterface $person
     * @return array
     */
    public function getUserEligibilityGroups(PersonInfoInterface $person) {
        $memberships = $this->penngroups->getGroupsList($person->getPennId());
        return array_intersect($this->getEligibilityGroups(), $memberships);        
    }    

    /**
     * Return the penngroups which make this person eligible for 
     * Google@SAS. If the student belongs to multiple groups, they 
     * are concatenated together.
     * @param PersonInfoInterface $person
     * @return string
     */
    public function getUserEligibilityReason(PersonInfoInterface $person) {
        return implode("; ", array_intersect($this->getUserEligibilityGroups($person), $memberships));
    }
    
    /**
     * Return the penngroups contained in params['eligibility-list']. These
     * determine why someone is eligible for an account
     */
    public function getEligibilityGroups() {
        
        if ( $this->eligibleGroups === false ) {
        
            ## the immediate members of the eligiblity-list penngroup will
            ## be other system-of-record penngroups. .
            $members = $this->penngroups->getGroupMembers($this->params['eligibility-groups'], 'Immediate');
            $groups = array();
            
            foreach ( $members as $member ) {
                if ( $member['source_id'] === 'g:gsa' ) {
                    $groups[$member['subject_id']] = $member['name'];
                }
            }
            
            $this->eligibleGroups = $groups;
        }
        
        return $this->eligibleGroups;
    }

    /**
     * Return the full list of eligible penn_id's
     * @return array
     */
    public function getEligiblePennIds() {

        $penn_ids = array();
        $results = $this->penngroups->getGroupMembers($group_id);
        
        foreach ( $results as $record ) {
            // Members of the penngroups which are themselves penngroups
            // return an id number rather than a penn_id so we need to filter
            // those out. Also, omit "fake" penn_id's if they come through
            $penn_id = $record['penn_id'];
            if ( preg_match("/^\d{8}$/", $penn_id) && !preg_match("/^0\d{7}$/", $penn_id) ) {
                $penn_ids[$penn_id] = $penn_id;
            }
        }
        
        return $penn_ids;
    }    
    
}