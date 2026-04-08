<?php

declare(strict_types = 1);

namespace imperazim\command;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\serializer\AvailableCommandsPacketAssembler;
use pocketmine\network\mcpe\protocol\serializer\AvailableCommandsPacketDisassembler;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use imperazim\packet\handler\PacketHandlerInterface;
use imperazim\command\enum\CommandEnumManager;

/**
* Intercepts AvailableCommandsPacket to dynamically modify command UI.
* Applies constraints and rebuilds command overloads based on player permissions.
*/
final class LibCommandInterceptor implements PacketHandlerInterface {

    /** @var array<int, bool> Track processed packets to avoid recursion */
    private static array $processedPackets = [];

    /**
    * Gets the packet IDs this handler manages.
    *
    * @return array Packet network IDs
    */
    public function getPacketIds(): array {
        return [AvailableCommandsPacket::NETWORK_ID];
    }

    /**
    * Handles the AvailableCommandsPacket.
    *
    * @param mixed $packet The packet to handle
    * @param mixed $session The network session
    *
    * @return bool Whether to allow the packet to continue
    */
    public function handle($packet, $session): bool {
        if (!$packet instanceof AvailableCommandsPacket) {
            return true;
        }

        $packetId = spl_object_id($packet);
        
        // Skip if already processed to avoid recursion
        if (isset(self::$processedPackets[$packetId])) {
            return true;
        }

        $player = $session->getPlayer();
        if (!($player instanceof Player)) {
            return true;
        }

        // Mark as processing
        self::$processedPackets[$packetId] = true;

        $server = Server::getInstance();
        $logger = $server->getLogger();

        try {
            $disassembled = AvailableCommandsPacketDisassembler::disassemble($packet);
        } catch (\Throwable $e) {
            $logger->warning("[LibCommand] Failed to disassemble packet: " . $e->getMessage());
            return true;
        }
        $commandDataList = $disassembled->commandData;

        foreach($commandDataList as $index => $commandData) {
            $cmd = $server->getCommandMap()->getCommand($commandData->getName());
            if (!($cmd instanceof Command)) {
                continue;
            }

            // Apply constraints
            foreach ($cmd->getConstraints() as $constraint) {
                if (!$constraint->isSatisfiedBy($player)) {
                    unset($commandDataList[$index]);
                    continue 2;
                }
            }

            // Rebuild command UI
            $overloads = $this->getOverloads($player, $cmd);
            $logger->debug("[LibCommand] Command '{$commandData->getName()}' overloads: " . count($overloads) . ", args: " . count($cmd->getArguments()));
            $commandData->overloads = $overloads;
        }

        // Send modified packet
        try {
            $modifiedPacket = AvailableCommandsPacketAssembler::assemble(array_values($commandDataList), [], CommandEnumManager::getEnums());
        } catch (\Throwable $e) {
            $logger->warning("[LibCommand] Failed to assemble packet: " . $e->getMessage());
            return true;
        }
        self::$processedPackets[spl_object_id($modifiedPacket)] = true;

        $session->sendDataPacket($modifiedPacket);
        
        // Clean up old packet ID from tracking
        unset(self::$processedPackets[$packetId]);
        
        return false; // Cancel original packet
    }

    /**
    * Generates command overloads for the command UI.
    *
    * @param CommandSender $sender The command sender
    * @param Command|SubCommand $command The command or subcommand
    *
    * @return array Generated command overloads
    */
    private function getOverloads(CommandSender $sender, Command|SubCommand $command): array {
        $overloads = [];

        // Process subcommands
        foreach ($command->getSubCommands() as $sub) {
            // Check subcommand constraints
            foreach ($sub->getConstraints() as $c) {
                if (!$c->isSatisfiedBy($sender)) {
                    continue 2;
                }
            }

            // Create subcommand parameter
            $param = CommandParameter::enum(
				$sub->getName(),
				new CommandHardEnum($sub->getName(), [$sub->getName()]),
				0,
				optional: false
			);

            // Recursively process child subcommands
            $child = $this->getOverloads($sender, $sub);
            if (!empty($child)) {
                foreach ($child as $ov) {
                    $overloads[] = new CommandOverload(false, [$param, ...$ov->getParameters()]);
                }
            } else {
                $overloads[] = new CommandOverload(false, [$param]);
            }
        }

        // Process arguments
        $args = $command->getArguments();
        $sets = [];

        // Normalize arguments to sets
        foreach ($args as $arg) {
            $sets[] = is_array($arg) ? $arg : [$arg];
        }

        // Generate argument combinations
        $indexes = array_fill(0, count($sets), 0);
        $total = array_product(array_map(fn($s) => count($s), $sets));

        for ($i = 0; $i < $total; ++$i) {
            $params = [];
            foreach ($indexes as $k => $idx) {
                $argument = $sets[$k][$idx];
                $param = clone $argument->getParameterData();

                // Handle enums
                if(isset($param->enum) && $param->enum instanceof CommandHardEnum){
					//TODO: This hack is not needed on PM's account as of 5.36.0, but since enums are initialised
					//with an empty name in StringEnumArgument (and I don't know why), it's best to preserve the
					//original behaviour
					$param->enum = new CommandHardEnum(
						"enum#" . spl_object_id($param->enum),
						$param->enum->getValues()
					);
                }
                $params[] = $param;
            }
            $overloads[] = new CommandOverload(false, $params);

            // Increment indexes
            for ($j = count($indexes) - 1; $j >= 0; --$j) {
                $indexes[$j]++;
                if ($indexes[$j] < count($sets[$j])) break;
                $indexes[$j] = 0;
            }
        }

        return $overloads;
    }
}