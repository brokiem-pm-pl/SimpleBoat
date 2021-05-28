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

	/**
	 * @var float|null
	 */
    protected $gravity = 0.4;

    protected function initEntity(): void
    {
        $this->setMaxHealth(4);
        $this->setHealth(4);
        $this->setGenericFlag(self::DATA_FLAG_STACKABLE, true);
        $this->setBoatType($this->namedtag->getInt(self::TAG_VARIANT, 0));
        $this->propertyManager->setInt(self::DATA_HURT_DIRECTION, 1);
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

    public function onUpdate(int $currentTick): bool
    {
        if ($this->closed) {
            return false;
        }

        if ($this->ticksLived > 1200 and $this->getRider() === null and $this->getPassenger() === null) {
            $this->close();
        }

        return parent::onUpdate($currentTick);
    }

    public function attack(EntityDamageEvent $source): void
    {
        if(!$source->isCancelled() and $source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            $this->propertyManager->setInt(self::DATA_HURT_TIME, 10);
            $this->propertyManager->setInt(self::DATA_HURT_DIRECTION, -$this->propertyManager->getInt(self::DATA_HURT_DIRECTION));

            $flag = ($damager instanceof Player and $damager->isCreative());

            if($flag or ($this->getHealth() - $source->getFinalDamage() < 1)) {
                $this->kill();
                if(!$flag) {
                    $this->level->dropItem(new Vector3($this->getX(), $this->getY(), $this->getZ()), ItemFactory::get(Item::BOAT, $this->getBoatType()));
                }
            }
        }

        parent::attack($source);
    }

    /**
     * @param Entity $rider
     * @return bool
     */
    public function link(Entity $rider): bool
    {
        if($this->rider === null) {
            $rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, true);

            $rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 1, 0));

            $rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, 1);
            $rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MAX_ROTATION, 90);
            $rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MIN_ROTATION, -90);

            $pk = new SetActorLinkPacket();
            $pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_RIDER, false, true);
            Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

            $this->rider = $rider;
            return true;
        } elseif($this->passenger === null) {
            $rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, true);

            $rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(-0.74, 1, 0));

            $rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, 1);
            $rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MAX_ROTATION, 90);
            $rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MIN_ROTATION, -90);

            $pk = new SetActorLinkPacket();
            $pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_PASSENGER, false, true);
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
        if($this->rider === $rider) {
            $rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, false);

            $rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 0, 0));
            $rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, 0);

            $pk = new SetActorLinkPacket();
            $pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_REMOVE, false, true);
            Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

            $this->rider = null;
            return true;
        } elseif($this->passenger === $rider) {
            $rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, false);

            $rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 0, 0));
            $rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, 0);

            $pk = new SetActorLinkPacket();
            $pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_REMOVE, false, true);
            Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

            $this->passenger = null;
            return true;
        }
        return false;
    }

    /**
     * @param Vector3 $pos
     * @param float|int $yaw
     * @param float|int $pitch
     */
    public function absoluteMove(Vector3 $pos, float $yaw = 0, float $pitch = 0) : void{
        $this->setComponents($pos->x, $pos->y, $pos->z);
        $this->setRotation($yaw, $pitch);
        $this->updateMovement();
    }

    /**
     * @return bool
     */
    public function canLink(): bool
    {
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