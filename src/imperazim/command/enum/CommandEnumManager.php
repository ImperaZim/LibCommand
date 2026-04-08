<?php

declare(strict_types = 1);

namespace imperazim\command\enum;

use pocketmine\Server;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\UpdateSoftEnumPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum;
use imperazim\command\exception\ArgumentException as CommandException;

/**
* Manages soft command enums for dynamic command suggestions
*
* Handles registration, updating, and synchronization of enums across players
*/
final class CommandEnumManager {
    
    /**
    * @var CommandSoftEnum[] Registered soft enums indexed by name
    */
    private static array $enums = [];

    /**
    * Retrieves a soft enum by its name
    *
    * @param string $name Enum identifier
    * @return CommandSoftEnum|null Enum instance or null if not found
    */
    public static function getEnumByName(string $name): ?CommandSoftEnum {
        return self::$enums[$name] ?? null;
    }

    /**
    * Gets all registered soft enums
    *
    * @return CommandSoftEnum[] Array of enum instances
    */
    public static function getEnums(): array {
        return self::$enums;
    }

    /**
    * Registers a new soft enum
    *
    * @param CommandSoftEnum $enum Enum instance to register
    * @throws CommandException If enum with same name already exists
    */
    public static function addEnum(CommandSoftEnum $enum): void {
        $name = $enum->getName();

        if (isset(self::$enums[$name])) {
            throw new CommandException("Soft enum '{$name}' already exists");
        }

        self::$enums[$name] = $enum;
        self::broadcastUpdate($enum, UpdateSoftEnumPacket::TYPE_ADD);
    }

    /**
    * Updates an existing soft enum with new values
    *
    * @param string $enumName Name of enum to update
    * @param string[] $values New enum values
    * @throws CommandException If specified enum doesn't exist
    */
    public static function updateEnum(string $enumName, array $values): void {
        if (!isset(self::$enums[$enumName])) {
            throw new CommandException("Unknown enum '{$enumName}'");
        }

        $enum = new CommandSoftEnum($enumName, $values);
        self::$enums[$enumName] = $enum;
        self::broadcastUpdate($enum, UpdateSoftEnumPacket::TYPE_SET);
    }

    /**
    * Removes a soft enum
    *
    * @param string $enumName Name of enum to remove
    * @throws CommandException If specified enum doesn't exist
    */
    public static function removeEnum(string $enumName): void {
        if (!isset(self::$enums[$enumName])) {
            throw new CommandException("Unknown enum '{$enumName}'");
        }

        $enum = self::$enums[$enumName];
        unset(self::$enums[$enumName]);
        self::broadcastUpdate($enum, UpdateSoftEnumPacket::TYPE_REMOVE);
    }

    /**
    * Broadcasts enum changes to all online players
    *
    * @param CommandSoftEnum $enum Enum instance to broadcast
    * @param int $updateType Update type (add/set/remove)
    */
    private static function broadcastUpdate(CommandSoftEnum $enum, int $updateType): void {
        $packet = new UpdateSoftEnumPacket();
        $packet->enumName = $enum->getName();
        $packet->values = $enum->getValues();
        $packet->type = $updateType;

        self::broadcastToPlayers($packet);
    }

    /**
    * Broadcasts a packet to all online players
    *
    * @param ClientboundPacket $packet Packet to broadcast
    */
    private static function broadcastToPlayers(ClientboundPacket $packet): void {
        $players = Server::getInstance()->getOnlinePlayers();
        NetworkBroadcastUtils::broadcastPackets($players, [$packet]);
    }
}