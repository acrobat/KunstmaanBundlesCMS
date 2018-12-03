<?php

namespace Kunstmaan\AdminBundle\Command;

use Kunstmaan\NodeBundle\Helper\Services\PageCreatorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupCommand extends Command
{
    const ADMIN_USERNAME = 'admin';

    private $pageCreator;

    public function __construct(PageCreatorService $pageCreator)
    {
        $this->pageCreator = $pageCreator;
        $this->requiredLocales = ['nl', 'fr', 'de', 'en'];

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('kuma:cms:setup')
            ->setDescription('Setup the cms')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Create default groups
        //Create default roles
        //Create admin user

        $this->createHomePage();

    }

    private function createHomePage()
    {
        $homePage = new \App\Entity\Pages\HomePage();
        $homePage->setTitle('Home');

        $translations = [];
        foreach ($this->requiredLocales as $locale) {
            $translations[] = [
                'language' => $locale,
                'callback' => function ($page, $translation, $seo) {
                    $translation->setTitle('Home');
                    $translation->setSlug('');
                }
            ];
        }

        $options = [
            'parent' => null,
            'page_internal_name' => 'homepage',
            'set_online' => true,
            'hidden_from_nav' => false,
            'creator' => self::ADMIN_USERNAME
        ];

        $this->pageCreator->createPage($homePage, $translations, $options);
    }
}
