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

class BulkCreateAccountsCommand extends ContainerAwareCommand {
    
    protected function configure() {
        
        $this
            ->setName('google:bulk-create-accounts')
            ->setDescription('Bulk create google accounts. Options defined in parameters.yml.')
            ->addOption('rebuild-cache', false, InputOption::VALUE_NONE, 'If set, rebuild the account cache database')
            ;
        
        $this->setHelp("Bulk create student accounts for GMail@SAS");
    }

    
    protected function execute(InputInterface $input, OutputInterface $output) {

        $rebuild_cache = $input->getOption('rebuild-cache');
        
        $helper   = $this->getContainer()->get('bulk_command_helper');
        $google   = $this->getContainer()->get("google_admin_client");
        $eligible = $this->getContainer()->get("bulk_gmail_eligibility");
        
        $em = $this->getContainer()->get('doctrine')->getManager("account_cache");
        // disable sql logging (in case we are in dev) to avoid memory crash
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $cacheRepo = $em->getRepository("BulkCreateAccountsBundle:Account");
        
        $records = $eligible->getEligibleRecords();
        
        if ( $rebuild_cache ) {
            $output->writeln("Clearing cache entries...");
            $cacheRepo->deleteAll();

            $output->writeln("Retrieving current list of google accounts...");
            $users = array();
            foreach ( $google->getAllGoogleUsers() as $user ) {
                list($username, $domain) = explode('@', $user);
                $users[$username] = $username;
            }
        }
                
        // process each student in the list
        $count = 0;
        $output->writeln("Checking for new accounts...");
        foreach ( $records as $penn_id => $record ) {

            $account = $cacheRepo->findOneByPennId($penn_id);
            
            if ( !$account ) {
                $account = new Account();
                $account->setPennId($penn_id);
            }

            if ( !$account->getPennkey() && $record['pennkey'] ) {
                $account->setPennkey($record['pennkey']);
            }

            if ( $rebuild_cache ) {
                $hash = $google->getPennIdHash($penn_id);
                $created = ( in_array($account->getPennkey(), $users) || in_array($hash, $users) );
                $account->setCreated($created);
            }
            
            if ( !$account->getCreated() ) {
                
                $personInfo = new PersonInfo($record);
                $groups = $eligible->getUserEligibilityReason($personInfo);
                
                if ( !$personInfo ) {
                    $helper->errorLog("No lookup data found for penn_id: $penn_id");
                } else {
                    try {
                        $google->createGoogleUser($personInfo, sha1($helper->randomPassword()), "Student eligible via $groups");
                        $output->writeln("$penn_id/{$record['pennkey']} eligible via $groups");
                        $account->setCreated(true);
                    } catch (\Exception $e) {
                        $helper->errorLog("Exception occurred creating account for penn_id: $penn_id, pennkey: " . $account->getPennkey() . ", error: " . $e->getMessage());
                        if ( preg_match("/\(409\) Entity already exists/", $e->getMessage()) ) {
                            // if entity already exists, update cache
                            $account->setCreated(true);
                        }
                    }
                    
                }
            }
            
            $em->persist($account);
            $em->flush();
            
            if ( ($count%100) == 0 ) {
                $output->write('.');
                $em->clear();
            }
            
            if ( $count == 3 ) {
                $output->writeln("Bailout!");
                exit;
            }
            
            $count++;
        }
        $output->writeln('.');
        $output->writeln("Done.");
    }
   
}