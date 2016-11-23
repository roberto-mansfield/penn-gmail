<?php 

namespace SAS\IRAD\BulkCreateAccountsBundle\Service;

use SAS\IRAD\GMailConfigureBundle\Service\GmailEligibility;
use SAS\IRAD\PennGroupsBundle\Service\WebServiceQuery;

/**
 * This class overrides the constructor of the GmailEligibity service
 * since we can't use the PennGroupsQueryCache service when running from
 * the command line (we don't have permission to write sessions!).
 * @author robertom
 *
 */
class BulkGmailEligibility extends GmailEligibility {
    
    public function __construct(WebServiceQuery $penngroups, array $google_params) {
        $this->penngroups  = $penngroups;
        $this->params = $google_params;
        $this->eligibleGroups = false;
    }
}