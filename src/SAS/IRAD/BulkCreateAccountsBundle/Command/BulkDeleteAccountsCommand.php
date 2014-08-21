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

class BulkDeleteAccountsCommand extends ContainerAwareCommand {
    
    protected function configure() {
        
        $this
            ->setName('google:bulk-delete-accounts')
            ->setDescription('Bulk delete google accounts. (useful during development/testing)')
            ->addArgument('data', InputArgument::REQUIRED, "The list of penn_id's for deletion")
            ;
        
        $this->setHelp("Bulk delete student accounts for GMail@SAS");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $data_file = $input->getArgument('data');

        $helper = $this->getContainer()->get('bulk_command_helper');        
        $ldap   = $this->getContainer()->get("penngroups.ldap_query");
        $google = $this->getContainer()->get("google_admin_client");
        
        $em = $this->getContainer()->get('doctrine')->getManager("account_cache");
        // disable sql logging (in case we are in dev) to avoid memory crash
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $cacheRepo = $em->getRepository("BulkCreateAccountsBundle:Account");

        $penn_ids = $helper->loadData($data_file);
        
        // process each student in the list
        $count = 0;
        $output->writeln("Bulk deleting...");

        foreach ( $penn_ids as $penn_id ) {
            
            
            $personInfo = $ldap->findByPennId($penn_id);
            if ( !$personInfo ) {
                $personInfo = new PersonInfo(array('penn_id' => $penn_id));
            }

            $user = $google->getGoogleUser($personInfo);
            
            if ( $user ) {
                $google->deleteGoogleUser($user);
            }
            
            $account = $cacheRepo->findOneByPennId($penn_id);

            if ( $account ) {
                $account->setCreated(false);
                $em->persist($account);
                $em->flush();
            }
            
            if ( ($count%100) == 0 ) {
                $output->write('.');
                $em->clear();
            }
            $count++;
        }
        $output->writeln('.');
        
        
        
        $output->writeln("Done.");
    }
}