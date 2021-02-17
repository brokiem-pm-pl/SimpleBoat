<?php

declare(strict_types=1);

namespace brokiem\simpleboat\item;

use brokiem\simpleboat\entity\SimpleBoatEntity;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Boat;
use pocketmine\math\Vector3;
use pocketmine\Player;

class SimpleBoatItem extends Boat
{

    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool
    {
        $nbt = Entity::createBaseNBT($blockClicked->getSide($face)->add(0.5, 0.5, 0.5));
        $nbt->setInt(SimpleBoatEntity::TAG_VARIANT, $this->meta);

        $entity = Entity::createEntity("SimpleBoat", $blockClicked->getLevel(), $nbt);
        $entity->spawnToAll();

        $this->pop();
        return true;
    }

}