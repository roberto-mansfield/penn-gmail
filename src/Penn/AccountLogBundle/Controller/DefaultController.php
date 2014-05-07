<?php

namespace Penn\AccountLogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="accountLogIndex")
     * @Template()
     */
    public function indexAction() {
        
        $repo = $this->getDoctrine()->getRepository("AccountLogBundle:AccountLog");
        $entries = $repo->getRecentEntries(25);
        
        return array("entries" => $entries);
    }
    
    
    /**
     * @Route("/search/{search_term}", name="accountLogByUser", defaults={"search_term" = false})
     * @Template()
     */
    public function accountLogByUserAction($search_term) {
    
        $entries = array();
        
        if ( $search_term ) {
            $repo = $this->getDoctrine()->getRepository("AccountLogBundle:AccountLog");
            $entries = $repo->getBySearchTerm($search_term);
        }
        
        return array("entries"     => $entries,
                     "search_term" => $search_term);
    }
}
