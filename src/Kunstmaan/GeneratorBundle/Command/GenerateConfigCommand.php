<?php

namespace Kunstmaan\GeneratorBundle\Command;

use Kunstmaan\GeneratorBundle\Generator\ConfigGenerator;
use Symfony\Component\Console\Input\InputOption;

/**
 * Generates config files
 */
class GenerateConfigCommand extends KunstmaanGenerateCommand
{
    /** @var string */
    private $projectDir;

    /** @var bool */
    private $overwriteSecurity;

    /** @var bool */
    private $overwriteLiipImagine;

    /** @var bool */
    private $overwriteFosHttpCache;

    /** @var bool */
    private $overwriteFosUser;
    /** @var bool */
    private $newAuthentication;

    public function __construct(string $projectDir, bool $newAuthentication = false)
    {
        $this->projectDir = $projectDir;
        $this->newAuthentication = $newAuthentication;

        parent::__construct();
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setDescription('Generates all needed config files not generated by recipes')
            ->addOption(
                'overwrite-security',
                '',
                InputOption::VALUE_REQUIRED,
                'Whether the command should generate an example or just overwrite the already existing config file'
            )
            ->addOption(
                'overwrite-liipimagine',
                '',
                InputOption::VALUE_REQUIRED,
                'Whether the command should generate an example or just overwrite the already existing config file'
            )
            ->addOption(
                'overwrite-foshttpcache',
                '',
                InputOption::VALUE_REQUIRED,
                'Whether the command should generate an example or just overwrite the already existing config file'
            )
            // NEXT_MAJOR: remove option
            ->addOption(
                'overwrite-fosuser',
                '',
                InputOption::VALUE_REQUIRED,
                'DEPRECATED. Whether the command should generate an example or just overwrite the already existing config file'
            )
            ->setName('kuma:generate:config');
    }

    /**
     * {@inheritdoc}
     */
    protected function getWelcomeText()
    {
        return 'Welcome to the Kunstmaan config generator';
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute()
    {
        $this->assistant->writeSection('Config generation');

        $this->createGenerator()->generate(
            $this->projectDir,
            $this->overwriteSecurity,
            $this->overwriteLiipImagine,
            $this->overwriteFosHttpCache,
            $this->newAuthentication ? false : $this->overwriteFosUser
        );

        $this->assistant->writeSection('Config successfully created', 'bg=green;fg=black');

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doInteract()
    {
        $this->assistant->writeLine(["This helps you to set all default config files needed to run KunstmaanCMS.\n"]);

        if ($this->assistant->hasOption('overwrite-fosuser')) {
            @trigger_error(sprintf('Passing the option "overwrite-fosuser" is deprecated since KunstmaanGeneratorBundle 5.9 and will be removed in KunstmaanGeneratorBundle 6.0. Use the new KunstmaanAdminBundle authentication system instead.'), E_USER_DEPRECATED);
        }

        $this->overwriteSecurity = $this->assistant->getOptionOrDefault('overwrite-security', null);
        $this->overwriteLiipImagine = $this->assistant->getOptionOrDefault('overwrite-liipimagine', null);
        $this->overwriteFosHttpCache = $this->assistant->getOptionOrDefault('overwrite-foshttpcache', null);
        $this->overwriteFosUser = $this->assistant->getOptionOrDefault('overwrite-fosuser', null);

        if (null === $this->overwriteSecurity) {
            $this->overwriteSecurity = $this->assistant->askConfirmation(
                'Do you want to overwrite the default security.yaml configuration file? (y/n)',
                'y'
            );
        }
        if (null === $this->overwriteLiipImagine) {
            $this->overwriteLiipImagine = $this->assistant->askConfirmation(
                'Do you want to overwrite the default liip_imagine.yaml configuration file? (y/n)',
                'y'
            );
        }
        if (null === $this->overwriteFosHttpCache) {
            $this->overwriteFosHttpCache = $this->assistant->askConfirmation(
                'Do you want to overwrite the production fos_http_cache.yaml configuration file? (y/n)',
                'y'
            );
        }
        if (null === $this->overwriteFosUser && false === $this->newAuthentication) {
            $this->overwriteFosUser = $this->assistant->askConfirmation(
                'Do you want to overwrite the fos_user.yaml configuration file? (y/n)',
                'y'
            );
        }
    }

    /**
     * @return ConfigGenerator
     */
    protected function createGenerator()
    {
        $filesystem = $this->getContainer()->get('filesystem');
        $registry = $this->getContainer()->get('doctrine');

        return new ConfigGenerator($filesystem, $registry, '/config', $this->assistant, $this->getContainer(), $this->newAuthentication);
    }
}
