<?php
/**
 * This class provides a basis for all other worker command classes
 *
 * @abstract
 */

namespace Maleficarum\Command;

abstract class AbstractCommand {
    /**
     * Definition of the parent handler id.
     */
    const DATA_KEY_PARENT_HANDLER_ID = '__parentHandlerId';

    /**
     * Internal storage for command data.
     *
     * @var array
     */
    protected $data = [];

    /* ------------------------------------ Magic methods START ---------------------------------------- */
    /**
     * Initialize a new command object.
     */
    public function __construct() {
        $this->data['__type'] = $this->getType();
    }

    /**
     * Created for logging command parameters
     *
     * @return string
     */
    public function __toString(): string {
        // copy data
        $toStringArray = $this->data;

        // not needed in logs
        unset($toStringArray['__type']);
        unset($toStringArray[self::DATA_KEY_PARENT_HANDLER_ID]);

        return json_encode($toStringArray);
    }
    /* ------------------------------------ Magic methods END ------------------------------------------ */

    /* ------------------------------------ AbstractCommand methods START ------------------------------ */
    /**
     * Fetch current command data in the form of a serialized JSON string - this is sent to the queue broker.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function toJSON(): string {
        if (!$this->validate()) {
            throw new \RuntimeException(sprintf('Attempting to serialize an incomplete command object. \%s::toJSON()', static::class));
        }

        $json = json_encode($this->data);

        if (!is_string($json)) {
            throw new \RuntimeException(sprintf('Cannot encode JSON data. \%s::toJSON()', static::class));
        }

        return $json;
    }

    /**
     * Unserialize this command data based on the provided JSON string.
     *
     * @param string $json
     *
     * @return \Maleficarum\Command\AbstractCommand
     */
    public function fromJSON(string $json): \Maleficarum\Command\AbstractCommand {
        $this->data = json_decode($json, true);
        is_array($this->data) or $this->data = ['__type' => $this->getType()];

        return $this;
    }

    /**
     * Create a command object based on the provided JSON data.
     *
     * @param string $json
     *
     * @throws \InvalidArgumentException
     * @return \Maleficarum\Command\AbstractCommand|null
     */
    static public function decode(string $json): ?\Maleficarum\Command\AbstractCommand {
        $data = json_decode($json, true);

        // not a JSON structure
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf('Incorrect command received - not a proper JSON. \%s::decode()', static::class));
        }

        // not a command
        if (!array_key_exists('__type', $data)) {
            return null;
        }

        // not a supported command (no command object or no handler)
        if (!class_exists('\Command\\' . $data['__type'], true)) {
            return null;
        }

        if (!class_exists('\Handler\\' . $data['__type'], true)) {
            return null;
        }

        /** @var \Maleficarum\Command\AbstractCommand $command */
        $command = \Maleficarum\Ioc\Container::get('Command\\' . $data['__type'])->fromJson($json);

        return $command;
    }
    /* ------------------------------------ AbstractCommand methods END -------------------------------- */

    /* ------------------------------------ Abstract methods START ------------------------------------- */
    /**
     * Validate current state of the $data property to check if it can be considered a complete command.
     *
     * @abstract
     * @return bool
     */
    abstract public function validate(): bool;

    /**
     * Fetch the type of current command. This is used to distinguish which handler to use for this command (on the worker side).
     *
     * @return string
     */
    abstract public function getType(): string;
    /* ------------------------------------ Abstract methods END --------------------------------------- */

    /* ------------------------------------ Setters & Getters START ------------------------------------ */
    /**
     * Set the parent handler ID.
     *
     * @param string $id
     *
     * @return \Maleficarum\Command\AbstractCommand
     */
    public function setParentHandlerId(string $id): \Maleficarum\Command\AbstractCommand {
        $this->data[self::DATA_KEY_PARENT_HANDLER_ID] = $id;

        return $this;
    }

    /**
     * Fetch the current parent handler id.
     *
     * @return string
     */
    public function getParentHandlerId(): string {
        return $this->data[self::DATA_KEY_PARENT_HANDLER_ID] ?? '';
    }
    /* ------------------------------------ Setters & Getters END -------------------------------------- */
}
