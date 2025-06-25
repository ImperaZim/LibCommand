<?php

declare(strict_types = 1);

namespace imperazim\command;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\event\EventPriority;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use imperazim\packet\handler\PacketHandlerInterface;
use imperazim\command\enum\CommandEnumManager;

/**
* Intercepts AvailableCommandsPacket to dynamically modify command UI.
* Applies constraints and rebuilds command overloads based on player permissions.
*/
class LibCommandInterceptor implements PacketHandlerInterface {

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

        $player = $session->getPlayer();
        if (!($player instanceof Player)) {
            return true;
        }

        $server = Server::getInstance();
        foreach ($packet->commandData as $name => $data) {
            $cmd = $server->getCommandMap()->getCommand($name);

            // Skip non-custom commands
            if (!($cmd instanceof Command)) {
                continue;
            }

            // Apply constraints
            foreach ($cmd->getConstraints() as $constraint) {
                if (!$constraint->isSatisfiedBy($player)) {
                    unset($packet->commandData[$name]);
                    continue 2;
                }
            }

            // Rebuild command UI
            $packet->commandData[$name]->overloads = $this->getOverloads($player, $cmd);
        }

        // Update dynamic enums
        $packet->softEnums = CommandEnumManager::getEnums();
        return true;
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
            $param = new CommandParameter();
            $param->paramName = $sub->getName();
            $param->paramType = AvailableCommandsPacket::ARG_FLAG_ENUM | AvailableCommandsPacket::ARG_FLAG_VALID;
            $param->isOptional = false;
            $param->enum = new CommandEnum($sub->getName(), [$sub->getName()]);

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
                if (isset($param->enum) && $param->enum instanceof CommandEnum) {
                    $ref = new \ReflectionProperty(CommandEnum::class, 'enumName');
                    $ref->setAccessible(true);
                    $ref->setValue($param->enum, 'enum#' . spl_object_id($param->enum));
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