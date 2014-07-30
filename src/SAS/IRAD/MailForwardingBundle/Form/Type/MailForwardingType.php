<?php

namespace SAS\IRAD\MailForwardingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class MailForwardingType extends AbstractType {
    
    private $max_forwards;
    
    public function __construct($max_forwards = 3) {
        $this->max_forwards = $max_forwards;
    }
    
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
    
        $choices = array('gmail'    => 'gmail',
                         'pennlive' => 'pennlive',
                         'other'    => 'other');
                
        $builder->add('forwarding_type', 'choice',
                array('choices'  => $choices,
                      'label'    => false,
                      'expanded' => true));
    
        $builder->add("forwarding_address", 'collection',
                array('type'     => 'text',
                      'required' => false,
                      'label'    => false,
                      'data'     => range(0, $this->max_forwards-1),
                      'options'  => 
                        array('max_length'  => '30',
                              'label'       => false,
                              'attr'        => array('class' => 'email', 
                                                     'size'  => '30')
                              )));
    }
    
    public function getName() {
        return 'MailForwarding';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
    }    
    
}