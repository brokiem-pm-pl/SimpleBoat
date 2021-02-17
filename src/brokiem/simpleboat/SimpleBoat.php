<?php

declare(strict_types=1);

namespace brokiem\simpleboat;

use brokiem\simpleboat\entity\SimpleBoatEntity;
use brokiem\simpleboat\item\SimpleBoatItem;
use brokiem\simpleboat\listener\EventListener;
use pocketmine\entity\Entity;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;

class SimpleBoat extends PluginBase
{

    public function onEnable()
    {
        Entity::registerEntity(SimpleBoatEntity::class, true, ["SimpleBoat", "minecraft:boat"]);
        ItemFactory::registerItem(new SimpleBoatItem(), true);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }
}