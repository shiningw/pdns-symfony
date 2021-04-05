<?php

namespace Shiningw\PdnsBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;


class ConfigurePdnsCommand extends Command
{
    protected static $defaultName = 'pdns:configure';

    public function __construct()
    {
        parent::__construct();

    }

    protected function configure()
    {
        $this
            ->setName('pdns:configure')
            ->setDescription('Configure Powerdns.')
            ->setDefinition(array(
                new InputArgument('apikey', InputArgument::REQUIRED, 'API KEY'),
                new InputArgument('nameserver', InputArgument::IS_ARRAY, 'Name Servers'),
            ))
            ->setHelp('CONFIGURE POWERDNS');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('apikey');
        $nameserver = $input->getArgument('nameserver');
        $container = $this->getApplication()->getKernel()->getContainer();
        $yaml_file = $container->get('kernel')->getRootDir() . '/config/pdns.yml';

        try {
            $yaml = Yaml::parse(file_get_contents($yaml_file));
        }catch(ParseException $e){
            printf('Unable to parse the YAML string: %s', $e->getMessage());
        }
        $yaml['parameters']['pdns_apikey'] = $key;

        if(isset($nameserver)) {
            $yaml['parameters']['ns_servers'] = $nameserver;
        }
        
        $data = Yaml::dump($yaml);
        file_put_contents($yaml_file, $data);
        $output->writeln(sprintf('Set API TO %s', $key));

    }
}
