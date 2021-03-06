<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\schematics;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\Fixer;
use czechpmdevs\buildertools\editors\object\BlockList;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;

/**
 * Class UnloadedSchematic
 * @package czechpmdevs\buildertools\schematics
 */
class UnloadedSchematic extends SchematicData {

    /** @var string $file */
    public $file;

    /**
     * UnloadedSchematic constructor.
     * @param string $file
     */
    public function __construct(string $file) {
        $this->file = $file;

        $nbt = new BigEndianNBTStream();

        /** @var CompoundTag $data */
        $data = $nbt->readCompressed(file_get_contents($file));
        $this->width = (int)$data->getShort("Width");
        $this->height = (int)$data->getShort("Height");
        $this->length = (int)$data->getShort("Length");

        if($data->offsetExists("Materials")) {
            $this->materialType = $data->getString("Materials");
        }

        $this->isLoaded = true;

        unset($data);
        unset($nbt);
    }

    /**
     * @return CompoundTag
     */
    public function getCompoundTag(): CompoundTag {
        $nbt = new BigEndianNBTStream();

        /** @var CompoundTag $compound */
        $compound = $nbt->readCompressed(file_get_contents($this->file));
        return $compound;
    }

    /**
     * @return BlockList|null
     */
    public function getBlockList(): ?BlockList {
        $nbt = new BigEndianNBTStream();

        /** @var CompoundTag $data */
        $data = $nbt->readCompressed(file_get_contents($this->file));

        $list = new BlockList();

        if($data->offsetExists("Blocks") && $data->offsetExists("Data")) {
            $blocks = $data->getByteArray("Blocks");
            $data = $data->getByteArray("Data");

            $i = 0;
            for($y = 0; $y < $this->height; $y++) {
                for ($z = 0; $z < $this->length; $z++) {
                    for($x = 0; $x < $this->width; $x++) {
                        $id = ord($blocks{$i});
                        $damage = ord($data{$i});
                        if($damage >= 16) $damage = 0; // prevents bug
                        $list->addBlock(new Vector3($x, $y, $z), Block::get($id, $damage));
                        $i++;
                    }
                }
            }
        }
        // WORLDEDIT BY SK89Q and Sponge schematics
        else {
            BuilderTools::getInstance()->getLogger()->error("Could not load schematic {$this->file}: BuilderTools supports only MCEdit schematic format.");
            return null;
        }

        if($this->materialType == "Classic" || $this->materialType == "Alpha") {
            $this->materialType = "Pocket";
            /** @var Fixer $fixer */
            $fixer = BuilderTools::getEditor(Editor::FIXER);
            $list = $fixer->fixBlockList($list);
        }

        return $list;
    }


}