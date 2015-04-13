<?php

namespace HeVinci\FavouriteBundle\Listener;

use Claroline\CoreBundle\Event\CustomActionResourceEvent;
use Claroline\CoreBundle\Event\DisplayWidgetEvent;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Templating\EngineInterface;

/**
 * @DI\Service
 */
class FavouriteListener extends ContainerAware
{
    private $om;
    private $sc;
    private $router;
    private $templatingEngine;
    /**
     * @DI\InjectParams({
     *     "om"                 = @DI\Inject("claroline.persistence.object_manager"),
     *     "sc"                 = @DI\Inject("security.context"),
     *     "router"             = @DI\Inject("router"),
     *      "templatingEngine" = @DI\Inject("templating")
     * })
     */
    public function __construct(
        ObjectManager $om,
        SecurityContext $sc,
        Router $router,
        EngineInterface $templatingEngine
    ){
        $this->om = $om;
        $this->sc = $sc;
        $this->router = $router;
        $this->templatingEngine = $templatingEngine;
    }

    /**
     * @DI\Observe("resource_action_hevinci_favourite")
     *
     * @param CustomActionResourceEvent $event
     */
    public function onFavoriteAction(CustomActionResourceEvent $event)
    {
        $user = $this->sc->getToken()->getUser();
        $resourceNode = $event->getResource()->getResourceNode();
        $isFavourite = $this->om->getRepository('HeVinciFavouriteBundle:Favourite')
            ->findOneBy(array('user' => $user, 'resourceNode' => $resourceNode));

        $isFavourite = ($isFavourite) ? 1 : 0;

        $route = $this->router->generate('hevinci_favourite_index', array(
                'isFavourite' => $isFavourite,
                'id' => $event->getResource()->getResourceNode()->getId()
            ));
        $event->setResponse(new RedirectResponse($route));
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("widget_hevinci_favourite_widget")
     *
     * @param DisplayWidgetEvent $event
     */
    public function onDisplay(DisplayWidgetEvent $event)
    {
        $favourites = $this->om->getRepository('HeVinciFavouriteBundle:Favourite')
            ->findBy(array('user' => $this->sc->getToken()->getUser()));

        $content = $this->templatingEngine->render(
            'HeVinciFavouriteBundle:widget:favourite.html.twig',
            array(
                'favourites' => $favourites
            )
        );

        $event->setContent($content);
        $event->stopPropagation();
    }
}