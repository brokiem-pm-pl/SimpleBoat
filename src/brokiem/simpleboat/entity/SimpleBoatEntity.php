<?php

declare(strict_types=1);

namespace brokiem\simpleboat\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Vehicle;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Player;
use pocketmine\Server;

class SimpleBoatEntity extends Vehicle
{
    const NETWORK_ID = self::BOAT;

    const TAG_VARIANT = "Data";

    /** @var float $height */
    public $height = 0.455;

    /** @var float $width */
    public $width = 1.4;

    /** @var ?Player $rider */
    private $rider = null;

    /** @var ?Player $passenger */
    private $passenger = null;

    protected function initEntity(): void
    {
        $this->setMaxHealth(40);
        $this->setGenericFlag(self::DATA_FLAG_STACKABLE, true);

        $this->setBoatType($this->namedtag->getInt(self::TAG_VARIANT, 0));

        parent::initEntity();
    }

    public function saveNBT(): void
    {
        $this->namedtag->setInt(self::TAG_VARIANT, $this->getBoatType());
        parent::saveNBT();
    }

    /**
     * @param int $type
     */
    public function setBoatType(int $type): void
    {
        $this->propertyManager->setInt(self::DATA_VARIANT, $type);
    }

    /**
     * @return int
     */
    public function getBoatType(): int
    {
        return $this->propertyManager->getInt(self::DATA_VARIANT);
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->setBaseDamage($source->getBaseDamage() * 10);

        if (!$source->isCancelled() and $source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            $this->broadcastEntityEvent(ActorEventPacket::HURT_ANIMATION, 10);

            $flag = $damager instanceof Player and $damager->isCreative();

            if ($flag or $this->getHealth() <= 0) {
                $this->kill();

                if (!$flag) {
                    $this->level->dropItem($this, ItemFactory::get(Item::BOAT, $this->getBoatType()));
                }
            }
        }
    }

    /**
     * @param Entity $rider
     * @return bool
     */
    public function link(Entity $rider): bool
    {
        if ($this->rider === null) {
            $rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, true);

            $rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 1, 0));

            $rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, 1);
            $rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MAX_ROTATION, 90);
            $rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MIN_ROTATION, -90);

            $pk = new SetActorLinkPacket();
            $pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_RIDER, true, true);
            Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

            $this->rider = $rider;
            return true;
        } elseif ($this->passenger === null) {
            $rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, true);

            $rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(1, 1, 1));

            $rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, 1);
            $rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MAX_ROTATION, 90);
            $rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MIN_ROTATION, -90);

            $pk = new SetActorLinkPacket();
            $pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_RIDER, true, true);
            Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

            $this->passenger = $rider;
            return true;
        }
        return false;
    }

    /**
     * @param Entity $rider
     * @return bool
     */
    public function unlink(Entity $rider): bool
    {
        if ($this->rider === $rider) {
            $rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, false);

            $rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 0, 0));
            $rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, 0);

            $pk = new SetActorLinkPacket();
            $pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_REMOVE, true, true);
            Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

            $this->rider = null;
            return true;
        } elseif ($this->passenger === $rider) {
            $rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, false);

            $rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 0, 0));
            $rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, 0);

            $pk = new SetActorLinkPacket();
            $pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_REMOVE, true, true);
            Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

            $this->passenger = null;
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function canLink() : bool{
        return $this->rider === null or $this->passenger === null;
    }

    /**
     * @return ?Player
     */
    public function getRider(): ?Player
    {
        return $this->rider;
    }

    /**
     * @return ?Player
     */
    public function getPassenger(): ?Player
    {
        return $this->passenger;
    }


}