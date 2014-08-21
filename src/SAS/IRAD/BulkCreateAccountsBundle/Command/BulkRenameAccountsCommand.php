<?php

namespace SAS\IRAD\BulkCreateAccountsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use SAS\IRAD\PersonInfoBundle\PersonInfo\PersonInfo;
use SAS\IRAD\BulkCreateAccountsBundle\Entity\Account;

class BulkRenameAccountsCommand extends ContainerAwareCommand {
    
    private $em;
    
    protected function configure() {
        
        $this
            ->setName('google:bulk-rename-accounts')
            ->setDescription('Bulk rename google accounts where pennkey has changed.')
            ;
        
        $this->setHelp("Bulk rename Google@SAS accounts where the pennkey has " .
                       "changed. The command compares account_cache.db to PennGroups " .
                       "to find any account where the pennkey has changed since the " .
                       "account was created.");
    }

    
    protected function execute(InputInterface $input, OutputInterface $output) {

        $helper = $this->getContainer()->get('bulk_command_helper');
        $logger = $this->getContainer()->get('account_logger');
        $ldap   = $this->getContainer()->get("penngroups.ldap_query");
        $google = $this->getContainer()->get("google_admin_client");
        
        $this->em = $this->getContainer()->get('doctrine')->getManager("account_cache");
        // disable sql logging (in case we are in dev) to avoid memory crash
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $cacheRepo = $this->em->getRepository("BulkCreateAccountsBundle:Account");

        $students = $helper->getEligibleStudents();
        $count   = 0;
        $errors  = 0;
        $skipped = 0;
        
        $output->writeln("Checking for changed pennkeys...");
        foreach ( $cacheRepo->findAllActive() as $account ) {

            $lookup = $ldap->findByPennId($account->getPennId());
            
            if ( !$lookup ) {
                // nothing found
                $skipped++;
                continue;
            }
            
            if ( !in_array($lookup->getPennId(), $students) ) {
                // student is not currently eligible, skip
                $skipped++;
                continue;
            }
            
            if ( $lookup->getPennkey() != $account->getPennkey() ) {

                // pennkey has changed!
                $penn_id = $account->getPennId();
                $pennkey = $account->getPennkey();
                $new_pennkey = $lookup->getPennkey();
                
                // does a google account exist for the old pennkey? (it should according to cache)
                $personInfo = new PersonInfo(compact('penn_id', 'pennkey'));
                $user = $google->getGoogleUser($personInfo);
                
                if ( !$user ) {
                    $helper->errorLog("Tried to rename account for $pennkey ($penn_id), but no google account found");
                    $errors++;
                    continue;
                }
                
                if ( $user->getUsername() == $new_pennkey ) {
                    $helper->errorLog("Account for $pennkey ($penn_id) has already been renamed to $new_pennkey");
                    $this->updateCache($account, $new_pennkey);
                    $errors++;
                    continue;
                }
                
                try {
                    $google->renameGoogleUser($user, $new_pennkey);
                } catch (\Exception $e) {
                    $helper->errorLog("Exception while renaming account $pennkey ($penn_id): " . $e->getMessage());
                    $errors++;
                    continue;
                }
                
                // update logs
                $logger->updatePennkey($penn_id, $pennkey, $new_pennkey);
                
                // update the cache
                $this->updateCache($account, $new_pennkey);
                
                $count++;
            }
        }
        
        $output->writeln("Accounts renamed: $count");
        
        if ( $skipped ) {
            $output->writeln("Accounts skipped: $skipped");
        }

        if ( $errors ) {
            $output->writeln("Account errors: $errors");
        }
   }

   
   private function updateCache($account, $new_pennkey) {
       $account->setPennkey($new_pennkey);
       $this->em->persist($account);
       $this->em->flush();
   }
    
}