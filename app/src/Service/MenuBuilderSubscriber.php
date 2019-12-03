<?php

namespace Db3v4l\Service;

use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuBuilderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SidebarMenuEvent::class => ['onSetupMenu', 100],
        ];
    }

    public function onSetupMenu(SidebarMenuEvent $event)
    {
        $instances = new MenuItemModel('instances', 'DB Instances', 'instance_list', [], 'fas fa-server');
        $adminer = new MenuItemModel('adminer', 'Adminer', '/adminer.php', [], 'fas fa-toolbox');
        $docs = new MenuItemModel('docs', 'Docs', 'doc_list', [], 'fas fa-book');
        $sources = new MenuItemModel('sources', 'Source code', 'https://github.com/gggeek/db-3v4l', [], 'fab fa-github');

        $event->addItem($instances);
        $event->addItem($adminer);
        $event->addItem($docs);
        $event->addItem($sources);

        $this->activateByRoute(
            $event->getRequest()->get('_route'),
            $event->getItems()
        );
    }

    /**
     * @param string $route
     * @param MenuItemModel[] $items
     */
    protected function activateByRoute($route, $items)
    {
        foreach ($items as $item) {
            if ($item->hasChildren()) {
                $this->activateByRoute($route, $item->getChildren());
            } elseif ($item->getRoute() == $route) {
                $item->setIsActive(true);
            }
        }
    }
}
