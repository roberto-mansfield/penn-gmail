<?php

namespace SAS\IRAD\GMailConfigureBundle\Service;

use SAS\IRAD\PennGroupsBundle\Service\PennGroupsQueryCache;
use SAS\IRAD\PersonInfoBundle\PersonInfo\PersonInfoInterface;

/**
 * Interface with Penngroups to determine eligibility status for Gmail.
 * @author robertom
 *
 */
class GmailEligibility {
    
    protected $eligibleGroups;
    protected $penngroups;
    protected $params;
    
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
        return implode("; ", array_intersect($this->getUserEligibilityGroups($person), $this->getUserEligibilityGroups($person)));
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
     * Return the full list of people eligible for google accounts
     * @return array
     */
    public function getEligibleRecords() {

        $records = array();
        $results = $this->penngroups->getGroupMembers($this->params['eligibility-list']);
        
        foreach ( $results as $record ) {
            
            // skip non-person records
            if ( $record['source_id'] != 'pennperson' ) {
                continue;
            }
            
            // Make sure we have a well-formed penn_id
            $penn_id = $record['penn_id'];
            if ( preg_match("/^\d{8}$/", $penn_id) && !preg_match("/^0\d{7}$/", $penn_id) ) {
                $records[$penn_id] = $record;
            }
        }
        
        return $records;
    }    
    
}