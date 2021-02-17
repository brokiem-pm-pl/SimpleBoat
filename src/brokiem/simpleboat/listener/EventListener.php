<?php

declare(strict_types=1);

namespace brokiem\simpleboat\listener;

use brokiem\simpleboat\entity\SimpleBoatEntity;
use brokiem\simpleboat\SimpleBoat;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;

class EventListener implements Listener
{

    /** @var SimpleBoat */
    private $plugin;

    public function __construct(SimpleBoat $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if ($player->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING)) {
            foreach ($player->getLevel()->getNearbyEntities($player->getBoundingBox()->expand(2, 2, 2), $player) as $key => $entity) {
                if ($entity instanceof SimpleBoatEntity) {
                    $entity->unlink($player);
                }
            }
        }
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if ($packet instanceof InventoryTransactionPacket and $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
            $entity = $player->getLevel()->getEntity($packet->trData->entityRuntimeId);
            if ($entity instanceof SimpleBoatEntity) {
                if ($packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT) {
                    if ($entity->canLink()) {
                        $entity->link($player);
                    }

                    $event->setCancelled();
                }
            }
        } elseif ($packet instanceof InteractPacket) {
            $entity = $player->getLevel()->getEntity($packet->target);
            if ($entity instanceof SimpleBoatEntity) {
                if ($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE and $entity->getRider() === $player) {
                    $entity->unlink($player);
                }
                $event->setCancelled();
            }
        } elseif ($packet instanceof PlayerInputPacket or $packet instanceof SetActorMotionPacket) {
            if ($player->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING)) {
                $event->setCancelled();
            }
        }
    }
}