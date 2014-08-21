<?php

namespace SAS\IRAD\BulkCreateAccountsBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * AccountLogRepository
 */
class AccountRepository extends EntityRepository {
    
    public function deleteAll() {
        
        $query = $this->getEntityManager()
            ->createQuery('DELETE FROM BulkCreateAccountsBundle:Account a');
        
        $result = $query->execute();
    }

    
    public function findAllActive() {
        
        $qb = $this->getEntityManager()->createQueryBuilder()
                ->select('a')
                ->from('BulkCreateAccountsBundle:Account', 'a')
                ->where('a.created = 1')
                ->andWhere('a.pennkey IS NOT NULL');
        
        return $qb->getQuery()->getResult();
    }

}
