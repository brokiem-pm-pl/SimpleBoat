<?php

declare(strict_types=1);

namespace brokiem\simpleboat\listener;

use _HumbugBoxa991b62ce91e\Nette\Neon\Entity;
use brokiem\simpleboat\entity\SimpleBoatEntity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

class EventListener implements Listener
{

	/** @var string[] $players */
	public $players = [];

	public function onPlayerQuit(PlayerQuitEvent $event): void
	{
		$player = $event->getPlayer();

		if (isset($this->players[$player->getName()])) {
			$entity = $this->players[$player->getName()];
			if ($entity instanceof SimpleBoatEntity) {
				$entity->unlink($player);
			}
			unset($this->players[$player->getName()]);
		}
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event): void
	{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if ($packet instanceof InventoryTransactionPacket and $packet->trData instanceof UseItemOnEntityTransactionData) {
			if ($packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK || $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_INTERACT) {
				$entity = $player->getLevel()->getEntity($packet->trData->getEntityRuntimeId());
				if ($entity instanceof SimpleBoatEntity) {
					if ($entity->canLink()) {
						if (isset($this->players[$player->getName()])) {
							$boatEntity = $this->players[$player->getName()];
							if ($boatEntity instanceof SimpleBoatEntity) {
								$boatEntity->unlink($player);
							}
						} else {
							$this->players[$player->getName()] = $entity;
						}
						$entity->link($player);
					}
					$event->setCancelled();
				}
			}
		}elseif($packet instanceof InteractPacket) {
			$entity = $player->getLevel()->getEntity($packet->target);
			if($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE and $entity instanceof SimpleBoatEntity and $entity->getRider() === $player) {
				if(isset($this->players[$player->getName()])) {
					unset($this->players[$player->getName()]);
				}
				$entity->unlink($player);
				$event->setCancelled();
			}
		}elseif($packet instanceof MoveActorAbsolutePacket) {
			$entity = $player->getLevel()->getEntity($packet->entityRuntimeId);
			if($entity instanceof SimpleBoatEntity and $entity->getRider() === $player) {
				$entity->absoluteMove($packet->position, $packet->xRot, $packet->zRot);
				$event->setCancelled();
			}
		}
	}

	public function onSneak(PlayerToggleSneakEvent $event) : void
	{
		$player = $event->getPlayer();
		if(isset($this->players[$player->getName()])){
			/** @var Entity $entity */
			$entity = $this->players[$player->getName()];
			if($entity instanceof SimpleBoatEntity){
				$entity->unlink($player);
				unset($this->players[$player->getName()]);
			}
		}
	}
}