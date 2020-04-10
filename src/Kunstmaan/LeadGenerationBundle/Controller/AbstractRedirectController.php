<?php

namespace Kunstmaan\LeadGenerationBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractRedirectController extends Controller
{
    /**
     * @Route("/{popup}", name="redirect_index", requirements={"popup": "\d+"})
     */
    public function indexAction($popup)
    {
        /** @var \Kunstmaan\LeadGenerationBundle\Entity\Popup\AbstractPopup $thePopup */
        $thePopup = $this->getDoctrine()->getRepository(\Kunstmaan\LeadGenerationBundle\Entity\Popup\AbstractPopup::class)->find($popup);

        return $this->render($this->getIndexTemplate(), array(
            'popup' => $thePopup,
        ));
    }

    protected function getIndexTemplate()
    {
        return '@KunstmaanLeadGeneration/Redirect/index.html.twig';
    }
}
