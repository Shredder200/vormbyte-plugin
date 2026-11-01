<?php
/**
 * Signature-only stubs for the WordPress AI Client (WordPress\AiClient\*),
 * generated from WordPress core (GPL-2.0-or-later) for static analysis only.
 * Not shipped in the release. Regenerate with: make stubs.
 */

namespace WordPress\AiClient\Common\Contracts {
    /**
     * Interface for objects that support array transformation.
     *
     * @since 0.1.0
     *
     * @template TArrayShape of array<string, mixed>
     */
    interface WithArrayTransformationInterface
    {
        /**
         * Converts the object to an array representation.
         *
         * @since 0.1.0
         *
         * @return TArrayShape The array representation.
         */
        public function toArray(): array;
        /**
         * Creates an instance from array data.
         *
         * @since 0.1.0
         *
         * @param TArrayShape $array The array data.
         * @return self<TArrayShape> The created instance.
         */
        public static function fromArray(array $array): self;
        /**
         * Checks if the array is a valid shape for this object.
         *
         * @since 0.1.0
         *
         * @param array<mixed> $array The array to check.
         * @return bool True if the array is a valid shape.
         * @phpstan-assert-if-true TArrayShape $array
         */
        public static function isArrayShape(array $array): bool;
    }
    /**
     * Interface for objects that can provide their JSON schema representation.
     *
     * This interface is implemented by DTOs to provide a consistent way to retrieve
     * their JSON schema for validation and serialization purposes.
     *
     * @since 0.1.0
     */
    interface WithJsonSchemaInterface
    {
        /**
         * Gets the JSON schema representation of the object.
         *
         * @since 0.1.0
         *
         * @return array<string, mixed> The JSON schema as an associative array.
         */
        public static function getJsonSchema(): array;
    }
}
namespace WordPress\AiClient\Common {
    /**
     * Abstract base class for all Data Value Objects in the AI Client.
     *
     * This abstract class consolidates the common functionality needed by all
     * data transfer objects:
     * - Array transformation for data manipulation
     * - JSON schema support for validation and documentation
     * - JSON serialization with proper empty object handling
     *
     * All DTOs in the AI Client should extend this class to ensure
     * consistent behavior across the codebase.
     *
     * @since 0.1.0
     *
     * @template TArrayShape of array<string, mixed>
     * @implements \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface<TArrayShape>
     */
    abstract class AbstractDataTransferObject implements \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface, \WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface, \JsonSerializable
    {
        /**
         * Validates that required keys exist in the array data.
         *
         * @since 0.1.0
         *
         * @param array<mixed> $data The array data to validate.
         * @param string[] $requiredKeys The keys that must be present.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If any required key is missing.
         */
        protected static function validateFromArrayData(array $data, array $requiredKeys): void
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function isArrayShape(array $array): bool
        {
        }
        /**
         * Converts the object to a JSON-serializable format.
         *
         * This method uses the toArray() method and then processes the result
         * based on the JSON schema to ensure proper object representation for
         * empty arrays.
         *
         * @since 0.1.0
         *
         * @return mixed The JSON-serializable representation.
         */
        #[\ReturnTypeWillChange]
        public function jsonSerialize()
        {
        }
    }
}
namespace WordPress\AiClient\Tools\DTO {
    /**
     * Represents a function call request from an AI model.
     *
     * This DTO encapsulates information about a function that the AI model
     * wants to invoke, including the function name and its arguments.
     *
     * @since 0.1.0
     *
     * @phpstan-type FunctionCallArrayShape array{id?: string, name?: string, args?: mixed}
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<FunctionCallArrayShape>
     */
    class FunctionCall extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_ID = 'id';
        public const KEY_NAME = 'name';
        public const KEY_ARGS = 'args';
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string|null $id Unique identifier for this function call.
         * @param string|null $name The name of the function to call.
         * @param mixed $args The arguments to pass to the function.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If neither id nor name is provided.
         */
        public function __construct(?string $id = null, ?string $name = null, $args = null)
        {
        }
        /**
         * Gets the function call ID.
         *
         * @since 0.1.0
         *
         * @return string|null The function call ID.
         */
        public function getId(): ?string
        {
        }
        /**
         * Gets the function name.
         *
         * @since 0.1.0
         *
         * @return string|null The function name.
         */
        public function getName(): ?string
        {
        }
        /**
         * Gets the function arguments.
         *
         * @since 0.1.0
         *
         * @return mixed The function arguments.
         */
        public function getArgs()
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return FunctionCallArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
    /**
     * Represents a function declaration for AI models.
     *
     * This DTO describes a function that can be called by the AI model,
     * including its name, description, and parameter schema.
     *
     * @since 0.1.0
     *
     * @phpstan-type FunctionDeclarationArrayShape array{
     *     name: string,
     *     description: string,
     *     parameters?: array<string, mixed>
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<FunctionDeclarationArrayShape>
     */
    class FunctionDeclaration extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_NAME = 'name';
        public const KEY_DESCRIPTION = 'description';
        public const KEY_PARAMETERS = 'parameters';
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string $name The name of the function.
         * @param string $description A description of what the function does.
         * @param array<string, mixed>|null $parameters The JSON schema for the function parameters.
         */
        public function __construct(string $name, string $description, ?array $parameters = null)
        {
        }
        /**
         * Gets the function name.
         *
         * @since 0.1.0
         *
         * @return string The function name.
         */
        public function getName(): string
        {
        }
        /**
         * Gets the function description.
         *
         * @since 0.1.0
         *
         * @return string The function description.
         */
        public function getDescription(): string
        {
        }
        /**
         * Gets the function parameters schema.
         *
         * @since 0.1.0
         *
         * @return array<string, mixed>|null The parameters schema.
         */
        public function getParameters(): ?array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return FunctionDeclarationArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
    /**
     * Represents a response to a function call.
     *
     * This DTO encapsulates the result of executing a function that was
     * requested by the AI model through a FunctionCall.
     *
     * @since 0.1.0
     *
     * @phpstan-type FunctionResponseArrayShape array{id?: string, name?: string, response: mixed}
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<FunctionResponseArrayShape>
     */
    class FunctionResponse extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_ID = 'id';
        public const KEY_NAME = 'name';
        public const KEY_RESPONSE = 'response';
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string|null $id The ID of the function call this is responding to.
         * @param string|null $name The name of the function that was called.
         * @param mixed $response The response data from the function.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If neither id nor name is provided.
         */
        public function __construct(?string $id, ?string $name, $response)
        {
        }
        /**
         * Gets the function call ID.
         *
         * @since 0.1.0
         *
         * @return string|null The function call ID.
         */
        public function getId(): ?string
        {
        }
        /**
         * Gets the function name.
         *
         * @since 0.1.0
         *
         * @return string|null The function name.
         */
        public function getName(): ?string
        {
        }
        /**
         * Gets the function response.
         *
         * @since 0.1.0
         *
         * @return mixed The response data.
         */
        public function getResponse()
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return FunctionResponseArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
    /**
     * Represents web search configuration for AI models.
     *
     * This DTO defines constraints for web searches that AI models can perform,
     * including allowed and disallowed domains.
     *
     * @since 0.1.0
     *
     * @phpstan-type WebSearchArrayShape array{allowedDomains?: string[], disallowedDomains?: string[]}
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<WebSearchArrayShape>
     */
    class WebSearch extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_ALLOWED_DOMAINS = 'allowedDomains';
        public const KEY_DISALLOWED_DOMAINS = 'disallowedDomains';
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string[] $allowedDomains List of domains that are allowed for web search.
         * @param string[] $disallowedDomains List of domains that are disallowed for web search.
         */
        public function __construct(array $allowedDomains = [], array $disallowedDomains = [])
        {
        }
        /**
         * Gets the allowed domains.
         *
         * @since 0.1.0
         *
         * @return string[] The allowed domains.
         */
        public function getAllowedDomains(): array
        {
        }
        /**
         * Gets the disallowed domains.
         *
         * @since 0.1.0
         *
         * @return string[] The disallowed domains.
         */
        public function getDisallowedDomains(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return WebSearchArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
}
namespace WordPress\AiClient\Messages\DTO {
    /**
     * Represents a part of a message.
     *
     * Messages can contain multiple parts of different types, such as text, files,
     * function calls, etc. This DTO encapsulates one such part.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type FileArrayShape from \WordPress\AiClient\Files\DTO\File
     * @phpstan-import-type FunctionCallArrayShape from \WordPress\AiClient\Tools\DTO\FunctionCall
     * @phpstan-import-type FunctionResponseArrayShape from \WordPress\AiClient\Tools\DTO\FunctionResponse
     *
     * @phpstan-type MessagePartArrayShape array{
     *     channel: string,
     *     type: string,
     *     thoughtSignature?: string,
     *     text?: string,
     *     file?: FileArrayShape,
     *     functionCall?: FunctionCallArrayShape,
     *     functionResponse?: FunctionResponseArrayShape
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<MessagePartArrayShape>
     */
    class MessagePart extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_CHANNEL = 'channel';
        public const KEY_TYPE = 'type';
        public const KEY_THOUGHT_SIGNATURE = 'thoughtSignature';
        public const KEY_TEXT = 'text';
        public const KEY_FILE = 'file';
        public const KEY_FUNCTION_CALL = 'functionCall';
        public const KEY_FUNCTION_RESPONSE = 'functionResponse';
        /**
         * Constructor that accepts various content types and infers the message part type.
         *
         * @since 0.1.0
         *
         * @param mixed $content The content of this message part.
         * @param \WordPress\AiClient\Messages\Enums\MessagePartChannelEnum|null $channel The channel this part belongs to. Defaults to CONTENT.
         * @param string|null $thoughtSignature Optional thought signature for extended thinking.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If an unsupported content type is provided.
         */
        public function __construct($content, ?\WordPress\AiClient\Messages\Enums\MessagePartChannelEnum $channel = null, ?string $thoughtSignature = null)
        {
        }
        /**
         * Gets the channel this message part belongs to.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Messages\Enums\MessagePartChannelEnum The channel.
         */
        public function getChannel(): \WordPress\AiClient\Messages\Enums\MessagePartChannelEnum
        {
        }
        /**
         * Gets the type of this message part.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Messages\Enums\MessagePartTypeEnum The type.
         */
        public function getType(): \WordPress\AiClient\Messages\Enums\MessagePartTypeEnum
        {
        }
        /**
         * Gets the thought signature.
         *
         * @since 1.3.0
         *
         * @return string|null The thought signature or null if not set.
         */
        public function getThoughtSignature(): ?string
        {
        }
        /**
         * Gets the text content.
         *
         * @since 0.1.0
         *
         * @return string|null The text content or null if not a text part.
         */
        public function getText(): ?string
        {
        }
        /**
         * Gets the file.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\DTO\File|null The file or null if not a file part.
         */
        public function getFile(): ?\WordPress\AiClient\Files\DTO\File
        {
        }
        /**
         * Gets the function call.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Tools\DTO\FunctionCall|null The function call or null if not a function call part.
         */
        public function getFunctionCall(): ?\WordPress\AiClient\Tools\DTO\FunctionCall
        {
        }
        /**
         * Gets the function response.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Tools\DTO\FunctionResponse|null The function response or null if not a function response part.
         */
        public function getFunctionResponse(): ?\WordPress\AiClient\Tools\DTO\FunctionResponse
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return MessagePartArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
        /**
         * Performs a deep clone of the message part.
         *
         * This method ensures that nested objects (file, function call, function response)
         * are cloned to prevent modifications to the cloned part from affecting the original.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
    }
    /**
     * Represents a message in an AI conversation.
     *
     * Messages are the fundamental unit of communication with AI models,
     * containing a role and one or more parts with different content types.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type MessagePartArrayShape from MessagePart
     *
     * @phpstan-type MessageArrayShape array{
     *     role: string,
     *     parts: array<MessagePartArrayShape>
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<MessageArrayShape>
     */
    class Message extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_ROLE = 'role';
        public const KEY_PARTS = 'parts';
        /**
         * @var \WordPress\AiClient\Messages\Enums\MessageRoleEnum The role of the message sender.
         */
        protected \WordPress\AiClient\Messages\Enums\MessageRoleEnum $role;
        /**
         * @var MessagePart[] The parts that make up this message.
         */
        protected array $parts;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Messages\Enums\MessageRoleEnum $role The role of the message sender.
         * @param MessagePart[] $parts The parts that make up this message.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If parts contain invalid content for the role.
         */
        public function __construct(\WordPress\AiClient\Messages\Enums\MessageRoleEnum $role, array $parts)
        {
        }
        /**
         * Gets the role of the message sender.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Messages\Enums\MessageRoleEnum The role.
         */
        public function getRole(): \WordPress\AiClient\Messages\Enums\MessageRoleEnum
        {
        }
        /**
         * Gets the message parts.
         *
         * @since 0.1.0
         *
         * @return MessagePart[] The message parts.
         */
        public function getParts(): array
        {
        }
        /**
         * Returns a new instance with the given part appended.
         *
         * @since 0.1.0
         *
         * @param MessagePart $part The part to append.
         * @return Message A new instance with the part appended.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the part is invalid for the role.
         */
        public function withPart(\WordPress\AiClient\Messages\DTO\MessagePart $part): \WordPress\AiClient\Messages\DTO\Message
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return MessageArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return self The specific message class based on the role.
         */
        final public static function fromArray(array $array): self
        {
        }
        /**
         * Performs a deep clone of the message.
         *
         * This method ensures that message part objects are cloned to prevent
         * modifications to the cloned message from affecting the original.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
    }
    /**
     * Represents a message from a user.
     *
     * This is a convenience class that automatically sets the role to USER.
     *
     * Important: Do not rely on `instanceof UserMessage` to determine the message role.
     * This is merely a helper class for construction. Always use `$message->getRole()`
     * to check the role of a message.
     *
     * @since 0.1.0
     */
    class UserMessage extends \WordPress\AiClient\Messages\DTO\Message
    {
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param MessagePart[] $parts The parts that make up this message.
         */
        public function __construct(array $parts)
        {
        }
    }
    /**
     * Represents a message from the AI model.
     *
     * This is a convenience class that automatically sets the role to MODEL.
     * Model messages contain the AI's responses.
     *
     * Important: Do not rely on `instanceof ModelMessage` to determine the message role.
     * This is merely a helper class for construction. Always use `$message->getRole()`
     * to check the role of a message.
     *
     * @since 0.1.0
     */
    class ModelMessage extends \WordPress\AiClient\Messages\DTO\Message
    {
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param MessagePart[] $parts The parts that make up this message.
         */
        public function __construct(array $parts)
        {
        }
    }
}
namespace WordPress\AiClient\Common {
    /**
     * Abstract base class for enum-like behavior in PHP 7.4.
     *
     * This class provides enum-like functionality for PHP versions that don't support native enums.
     * Child classes should define uppercase snake_case constants for enum values.
     *
     * @example
     * class PersonEnum extends AbstractEnum {
     *     public const FIRST_NAME = 'first';
     *     public const LAST_NAME = 'last';
     * }
     *
     * // Usage:
     * $enum = PersonEnum::from('first'); // Creates instance with value 'first'
     * $enum = PersonEnum::tryFrom('invalid'); // Returns null
     * $enum = PersonEnum::firstName(); // Creates instance with value 'first'
     * $enum->name; // 'FIRST_NAME'
     * $enum->value; // 'first'
     * $enum->equals('first'); // Returns true
     * $enum->is(PersonEnum::firstName()); // Returns true
     * PersonEnum::cases(); // Returns array of all enum instances
     *
     * @property-read string $value The value of the enum instance.
     * @property-read string $name The name of the enum constant.
     *
     * @since 0.1.0
     */
    abstract class AbstractEnum implements \JsonSerializable
    {
        /**
         * Provides read-only access to properties.
         *
         * @since 0.1.0
         *
         * @param string $property The property name.
         * @return mixed The property value.
         * @throws \BadMethodCallException If property doesn't exist.
         */
        final public function __get(string $property)
        {
        }
        /**
         * Prevents property modification.
         *
         * @since 0.1.0
         *
         * @param string $property The property name.
         * @param mixed $value The value to set.
         * @throws \BadMethodCallException Always, as enum properties are read-only.
         */
        final public function __set(string $property, $value): void
        {
        }
        /**
         * Creates an enum instance from a value, throws exception if invalid.
         *
         * @since 0.1.0
         *
         * @param string $value The enum value.
         * @return static The enum instance.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the value is not valid.
         */
        final public static function from(string $value): self
        {
        }
        /**
         * Tries to create an enum instance from a value, returns null if invalid.
         *
         * @since 0.1.0
         *
         * @param string $value The enum value.
         * @return static|null The enum instance or null.
         */
        final public static function tryFrom(string $value): ?self
        {
        }
        /**
         * Gets all enum cases.
         *
         * @since 0.1.0
         *
         * @return static[] Array of all enum instances.
         */
        final public static function cases(): array
        {
        }
        /**
         * Checks if this enum has the same value as the given value.
         *
         * @since 0.1.0
         *
         * @param string|self $other The value or enum to compare.
         * @return bool True if values are equal.
         */
        final public function equals($other): bool
        {
        }
        /**
         * Checks if this enum is the same instance type and value as another enum.
         *
         * @since 0.1.0
         *
         * @param self $other The other enum to compare.
         * @return bool True if enums are identical.
         */
        final public function is(self $other): bool
        {
        }
        /**
         * Gets all valid values for this enum.
         *
         * @since 0.1.0
         *
         * @return string[] List of all enum values.
         */
        final public static function getValues(): array
        {
        }
        /**
         * Checks if a value is valid for this enum.
         *
         * @since 0.1.0
         *
         * @param string $value The value to check.
         * @return bool True if value is valid.
         */
        final public static function isValidValue(string $value): bool
        {
        }
        /**
         * Gets all constants for this enum class.
         *
         * @since 0.1.0
         *
         * @return array<string, string> Map of constant names to values.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If invalid constant found.
         */
        final protected static function getConstants(): array
        {
        }
        /**
         * Determines the class enumerations by reflecting on class constants.
         *
         * This method can be overridden by subclasses to customize how
         * enumerations are determined (e.g., to add dynamic constants).
         *
         * @since 0.1.0
         *
         * @param class-string $className The fully qualified class name.
         * @return array<string, string> Map of constant names to values.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If invalid constant found.
         */
        protected static function determineClassEnumerations(string $className): array
        {
        }
        /**
         * Handles dynamic method calls for enum checking.
         *
         * @since 0.1.0
         *
         * @param string $name The method name.
         * @param array<mixed> $arguments The method arguments.
         * @return bool True if the enum value matches.
         * @throws \BadMethodCallException If the method doesn't exist.
         */
        final public function __call(string $name, array $arguments): bool
        {
        }
        /**
         * Handles static method calls for enum creation.
         *
         * @since 0.1.0
         *
         * @param string $name The method name.
         * @param array<mixed> $arguments The method arguments.
         * @return static The enum instance.
         * @throws \BadMethodCallException If the method doesn't exist.
         */
        final public static function __callStatic(string $name, array $arguments): self
        {
        }
        /**
         * Returns string representation of the enum.
         *
         * @since 0.1.0
         *
         * @return string The enum value.
         */
        final public function __toString(): string
        {
        }
        /**
         * Converts the enum to a JSON-serializable format.
         *
         * @since 0.1.0
         *
         * @return string The enum value.
         */
        #[\ReturnTypeWillChange]
        public function jsonSerialize()
        {
        }
    }
}
namespace WordPress\AiClient\Messages\Enums {
    /**
     * Enum for message part types.
     *
     * @since 0.1.0
     *
     * @method static self text() Creates an instance for TEXT type.
     * @method static self file() Creates an instance for FILE type.
     * @method static self functionCall() Creates an instance for FUNCTION_CALL type.
     * @method static self functionResponse() Creates an instance for FUNCTION_RESPONSE type.
     * @method bool isText() Checks if the type is TEXT.
     * @method bool isFile() Checks if the type is FILE.
     * @method bool isFunctionCall() Checks if the type is FUNCTION_CALL.
     * @method bool isFunctionResponse() Checks if the type is FUNCTION_RESPONSE.
     */
    class MessagePartTypeEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Text content.
         */
        public const TEXT = 'text';
        /**
         * File content (inline or remote).
         */
        public const FILE = 'file';
        /**
         * Function call request.
         */
        public const FUNCTION_CALL = 'function_call';
        /**
         * Function response.
         */
        public const FUNCTION_RESPONSE = 'function_response';
    }
    /**
     * Enum for message part channels.
     *
     * @since 0.1.0
     *
     * @method static self content() Creates an instance for CONTENT channel.
     * @method static self thought() Creates an instance for THOUGHT channel.
     * @method bool isContent() Checks if the channel is CONTENT.
     * @method bool isThought() Checks if the channel is THOUGHT.
     */
    class MessagePartChannelEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Regular (primary) content.
         */
        public const CONTENT = 'content';
        /**
         * Model thinking or reasoning.
         */
        public const THOUGHT = 'thought';
    }
    /**
     * Enum for message roles in AI conversations.
     *
     * @since 0.1.0
     *
     * @method static self user() Creates an instance for USER role.
     * @method static self model() Creates an instance for MODEL role.
     * @method bool isUser() Checks if the role is USER.
     * @method bool isModel() Checks if the role is MODEL.
     */
    class MessageRoleEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * User role - messages from the user.
         */
        public const USER = 'user';
        /**
         * Model role - messages from the AI model.
         */
        public const MODEL = 'model';
    }
    /**
     * Enum for input/output modalities.
     *
     * @since 0.1.0
     *
     * @method static self text() Creates an instance for TEXT modality.
     * @method static self document() Creates an instance for DOCUMENT modality.
     * @method static self image() Creates an instance for IMAGE modality.
     * @method static self audio() Creates an instance for AUDIO modality.
     * @method static self video() Creates an instance for VIDEO modality.
     * @method bool isText() Checks if the modality is TEXT.
     * @method bool isDocument() Checks if the modality is DOCUMENT.
     * @method bool isImage() Checks if the modality is IMAGE.
     * @method bool isAudio() Checks if the modality is AUDIO.
     * @method bool isVideo() Checks if the modality is VIDEO.
     */
    class ModalityEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Text modality.
         */
        public const TEXT = 'text';
        /**
         * Document modality (PDFs, Word docs, etc.).
         */
        public const DOCUMENT = 'document';
        /**
         * Image modality.
         */
        public const IMAGE = 'image';
        /**
         * Audio modality.
         */
        public const AUDIO = 'audio';
        /**
         * Video modality.
         */
        public const VIDEO = 'video';
    }
}
namespace WordPress\AiClient\Builders {
    /**
     * Fluent builder for constructing AI messages.
     *
     * This class provides a fluent interface for building messages with various
     * content types including text, files, function calls, and function responses.
     *
     * @since 0.2.0
     *
     * @phpstan-import-type MessagePartArrayShape from \WordPress\AiClient\Messages\DTO\MessagePart
     *
     * @phpstan-type Input string|\WordPress\AiClient\Messages\DTO\MessagePart|MessagePartArrayShape|\WordPress\AiClient\Files\DTO\File|\WordPress\AiClient\Tools\DTO\FunctionCall|\WordPress\AiClient\Tools\DTO\FunctionResponse|null
     */
    class MessageBuilder
    {
        /**
         * @var \WordPress\AiClient\Messages\Enums\MessageRoleEnum|null The role of the message sender.
         */
        protected ?\WordPress\AiClient\Messages\Enums\MessageRoleEnum $role = null;
        /**
         * @var list<\WordPress\AiClient\Messages\DTO\MessagePart> The parts that make up the message.
         */
        protected array $parts = [];
        /**
         * Constructor.
         *
         * @since 0.2.0
         *
         * @param Input $input Optional initial content.
         * @param \WordPress\AiClient\Messages\Enums\MessageRoleEnum|null $role Optional role.
         */
        public function __construct($input = null, ?\WordPress\AiClient\Messages\Enums\MessageRoleEnum $role = null)
        {
        }
        /**
         * Creates a deep clone of this builder.
         *
         * Clones all MessagePart objects in the parts array to ensure
         * the cloned builder is independent of the original.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
        /**
         * Sets the role of the message sender.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClient\Messages\Enums\MessageRoleEnum $role The role to set.
         * @return self
         */
        public function usingRole(\WordPress\AiClient\Messages\Enums\MessageRoleEnum $role): self
        {
        }
        /**
         * Sets the role to user.
         *
         * @since 0.2.0
         *
         * @return self
         */
        public function usingUserRole(): self
        {
        }
        /**
         * Sets the role to model.
         *
         * @since 0.2.0
         *
         * @return self
         */
        public function usingModelRole(): self
        {
        }
        /**
         * Adds text content to the message.
         *
         * @since 0.2.0
         *
         * @param string $text The text to add.
         * @return self
         * @throws \InvalidArgumentException If the text is empty.
         */
        public function withText(string $text): self
        {
        }
        /**
         * Adds a file to the message.
         *
         * Accepts:
         * - File object
         * - URL string (remote file)
         * - Base64-encoded data string
         * - Data URI string (data:mime/type;base64,data)
         * - Local file path string
         *
         * @since 0.2.0
         *
         * @param string|\WordPress\AiClient\Files\DTO\File $file The file to add.
         * @param string|null $mimeType Optional MIME type (ignored if File object provided).
         * @return self
         * @throws \InvalidArgumentException If the file is invalid.
         */
        public function withFile($file, ?string $mimeType = null): self
        {
        }
        /**
         * Adds a function call to the message.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClient\Tools\DTO\FunctionCall $functionCall The function call to add.
         * @return self
         */
        public function withFunctionCall(\WordPress\AiClient\Tools\DTO\FunctionCall $functionCall): self
        {
        }
        /**
         * Adds a function response to the message.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClient\Tools\DTO\FunctionResponse $functionResponse The function response to add.
         * @return self
         */
        public function withFunctionResponse(\WordPress\AiClient\Tools\DTO\FunctionResponse $functionResponse): self
        {
        }
        /**
         * Adds multiple message parts to the message.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClient\Messages\DTO\MessagePart ...$parts The message parts to add.
         * @return self
         */
        public function withMessageParts(\WordPress\AiClient\Messages\DTO\MessagePart ...$parts): self
        {
        }
        /**
         * Builds and returns the Message object.
         *
         * @since 0.2.0
         *
         * @return \WordPress\AiClient\Messages\DTO\Message The built message.
         * @throws \InvalidArgumentException If the message validation fails.
         */
        public function get(): \WordPress\AiClient\Messages\DTO\Message
        {
        }
    }
    /**
     * Fluent builder for constructing AI prompts.
     *
     * This class provides a fluent interface for building prompts with various
     * content types and model configurations. It automatically infers model
     * requirements based on the features used in the prompt.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type MessageArrayShape from \WordPress\AiClient\Messages\DTO\Message
     * @phpstan-import-type MessagePartArrayShape from \WordPress\AiClient\Messages\DTO\MessagePart
     *
     * @phpstan-type Prompt string|\WordPress\AiClient\Messages\DTO\MessagePart|\WordPress\AiClient\Messages\DTO\Message|MessageArrayShape|list<string|\WordPress\AiClient\Messages\DTO\MessagePart|MessagePartArrayShape>|list<\WordPress\AiClient\Messages\DTO\Message>|null
     */
    class PromptBuilder
    {
        /**
         * @var list<\WordPress\AiClient\Messages\DTO\Message> The messages in the conversation.
         */
        protected array $messages = [];
        /**
         * @var \WordPress\AiClient\Providers\Models\Contracts\ModelInterface|null The model to use for generation.
         */
        protected ?\WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model = null;
        /**
         * @var list<string> Ordered list of preference keys to check when selecting a model.
         */
        protected array $modelPreferenceKeys = [];
        /**
         * @var string|null The provider ID or class name.
         */
        protected ?string $providerIdOrClassName = null;
        /**
         * @var \WordPress\AiClient\Providers\Models\DTO\ModelConfig The model configuration.
         */
        protected \WordPress\AiClient\Providers\Models\DTO\ModelConfig $modelConfig;
        /**
         * @var \WordPress\AiClient\Providers\Http\DTO\RequestOptions|null The request options for HTTP transport.
         */
        protected ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions $requestOptions = null;
        // phpcs:disable Generic.Files.LineLength.TooLong
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\ProviderRegistry $registry The provider registry for finding suitable models.
         * @param Prompt $prompt Optional initial prompt content.
         * @param \WordPress\AiClientDependencies\Psr\EventDispatcher\EventDispatcherInterface|null $eventDispatcher Optional event dispatcher for lifecycle events.
         */
        // phpcs:enable Generic.Files.LineLength.TooLong
        public function __construct(\WordPress\AiClient\Providers\ProviderRegistry $registry, $prompt = null, ?\WordPress\AiClientDependencies\Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher = null)
        {
        }
        /**
         * Creates a deep clone of this builder.
         *
         * Clones all mutable state including messages, model configuration, and request options.
         * Service objects (registry, model, event dispatcher) are intentionally NOT cloned
         * as they are shared dependencies.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
        /**
         * Adds text to the current message.
         *
         * @since 0.1.0
         *
         * @param string $text The text to add.
         * @return self
         */
        public function withText(string $text): self
        {
        }
        /**
         * Adds a file to the current message.
         *
         * Accepts:
         * - File object
         * - URL string (remote file)
         * - Base64-encoded data string
         * - Data URI string (data:mime/type;base64,data)
         * - Local file path string
         *
         * @since 0.1.0
         *
         * @param string|\WordPress\AiClient\Files\DTO\File $file The file (File object or string representation).
         * @param string|null $mimeType The MIME type (optional, ignored if File object provided).
         * @return self
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the file is invalid or MIME type cannot be determined.
         */
        public function withFile($file, ?string $mimeType = null): self
        {
        }
        /**
         * Adds a function response to the current message.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Tools\DTO\FunctionResponse $functionResponse The function response.
         * @return self
         */
        public function withFunctionResponse(\WordPress\AiClient\Tools\DTO\FunctionResponse $functionResponse): self
        {
        }
        /**
         * Adds message parts to the current message.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Messages\DTO\MessagePart ...$parts The message parts to add.
         * @return self
         */
        public function withMessageParts(\WordPress\AiClient\Messages\DTO\MessagePart ...$parts): self
        {
        }
        /**
         * Adds conversation history messages.
         *
         * Historical messages are prepended to the beginning of the message list,
         * before the current message being built.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Messages\DTO\Message ...$messages The messages to add to history.
         * @return self
         */
        public function withHistory(\WordPress\AiClient\Messages\DTO\Message ...$messages): self
        {
        }
        /**
         * Sets the model to use for generation.
         *
         * The model's configuration will be merged with the builder's configuration,
         * with the builder's configuration taking precedence for any overlapping settings.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model The model to use.
         * @return self
         */
        public function usingModel(\WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model): self
        {
        }
        /**
         * Sets preferred models to evaluate in order.
         *
         * @since 0.2.0
         *
         * @param string|\WordPress\AiClient\Providers\Models\Contracts\ModelInterface|array{0:string,1:string} ...$preferredModels The preferred models as model IDs,
         * model instances, or [provider ID, model ID] tuples. For broader compatibility, it is recommended you specify
         * only model IDs or model instances, as that will allow for different providers that expose the same model to be
         * considered.
         * @return self
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException When a preferred model has an invalid type or identifier.
         */
        public function usingModelPreference(...$preferredModels): self
        {
        }
        /**
         * Sets the model configuration.
         *
         * Merges the provided configuration with the builder's configuration,
         * with builder configuration taking precedence.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\DTO\ModelConfig $config The model configuration to merge.
         * @return self
         */
        public function usingModelConfig(\WordPress\AiClient\Providers\Models\DTO\ModelConfig $config): self
        {
        }
        /**
         * Sets the provider to use for generation.
         *
         * @since 0.1.0
         *
         * @param string $providerIdOrClassName The provider ID or class name.
         * @return self
         */
        public function usingProvider(string $providerIdOrClassName): self
        {
        }
        /**
         * Sets the system instruction.
         *
         * System instructions are stored in the model configuration and guide
         * the AI model's behavior throughout the conversation.
         *
         * @since 0.1.0
         *
         * @param string $systemInstruction The system instruction text.
         * @return self
         */
        public function usingSystemInstruction(string $systemInstruction): self
        {
        }
        /**
         * Sets the maximum number of tokens to generate.
         *
         * @since 0.1.0
         *
         * @param int $maxTokens The maximum number of tokens.
         * @return self
         */
        public function usingMaxTokens(int $maxTokens): self
        {
        }
        /**
         * Sets the temperature for generation.
         *
         * @since 0.1.0
         *
         * @param float $temperature The temperature value.
         * @return self
         */
        public function usingTemperature(float $temperature): self
        {
        }
        /**
         * Sets the top-p value for generation.
         *
         * @since 0.1.0
         *
         * @param float $topP The top-p value.
         * @return self
         */
        public function usingTopP(float $topP): self
        {
        }
        /**
         * Sets the top-k value for generation.
         *
         * @since 0.1.0
         *
         * @param int $topK The top-k value.
         * @return self
         */
        public function usingTopK(int $topK): self
        {
        }
        /**
         * Sets stop sequences for generation.
         *
         * @since 0.1.0
         *
         * @param string ...$stopSequences The stop sequences.
         * @return self
         */
        public function usingStopSequences(string ...$stopSequences): self
        {
        }
        /**
         * Sets the number of candidates to generate.
         *
         * @since 0.1.0
         *
         * @param int $candidateCount The number of candidates.
         * @return self
         */
        public function usingCandidateCount(int $candidateCount): self
        {
        }
        /**
         * Sets the function declarations available to the model.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Tools\DTO\FunctionDeclaration ...$functionDeclarations The function declarations.
         * @return self
         */
        public function usingFunctionDeclarations(\WordPress\AiClient\Tools\DTO\FunctionDeclaration ...$functionDeclarations): self
        {
        }
        /**
         * Sets the presence penalty for generation.
         *
         * @since 0.1.0
         *
         * @param float $presencePenalty The presence penalty value.
         * @return self
         */
        public function usingPresencePenalty(float $presencePenalty): self
        {
        }
        /**
         * Sets the frequency penalty for generation.
         *
         * @since 0.1.0
         *
         * @param float $frequencyPenalty The frequency penalty value.
         * @return self
         */
        public function usingFrequencyPenalty(float $frequencyPenalty): self
        {
        }
        /**
         * Sets the web search configuration.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Tools\DTO\WebSearch $webSearch The web search configuration.
         * @return self
         */
        public function usingWebSearch(\WordPress\AiClient\Tools\DTO\WebSearch $webSearch): self
        {
        }
        /**
         * Sets the request options for HTTP transport.
         *
         * @since 0.3.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\RequestOptions $requestOptions The request options.
         * @return self
         */
        public function usingRequestOptions(\WordPress\AiClient\Providers\Http\DTO\RequestOptions $requestOptions): self
        {
        }
        /**
         * Sets the top log probabilities configuration.
         *
         * If $topLogprobs is null, enables log probabilities.
         * If $topLogprobs has a value, enables log probabilities and sets the number of top log probabilities to return.
         *
         * @since 0.1.0
         *
         * @param int|null $topLogprobs The number of top log probabilities to return, or null to enable log probabilities.
         * @return self
         */
        public function usingTopLogprobs(?int $topLogprobs = null): self
        {
        }
        /**
         * Sets the output MIME type.
         *
         * @since 0.1.0
         *
         * @param string $mimeType The MIME type.
         * @return self
         */
        public function asOutputMimeType(string $mimeType): self
        {
        }
        /**
         * Sets the output schema.
         *
         * @since 0.1.0
         *
         * @param array<string, mixed> $schema The output schema.
         * @return self
         */
        public function asOutputSchema(array $schema): self
        {
        }
        /**
         * Sets the output modalities.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Messages\Enums\ModalityEnum ...$modalities The output modalities.
         * @return self
         */
        public function asOutputModalities(\WordPress\AiClient\Messages\Enums\ModalityEnum ...$modalities): self
        {
        }
        /**
         * Sets the output file type.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Files\Enums\FileTypeEnum $fileType The output file type.
         * @return self
         */
        public function asOutputFileType(\WordPress\AiClient\Files\Enums\FileTypeEnum $fileType): self
        {
        }
        /**
         * Sets the output media orientation.
         *
         * @since 1.3.0
         *
         * @param \WordPress\AiClient\Files\Enums\MediaOrientationEnum $orientation The output media orientation.
         * @return self
         */
        public function asOutputMediaOrientation(\WordPress\AiClient\Files\Enums\MediaOrientationEnum $orientation): self
        {
        }
        /**
         * Sets the output media aspect ratio.
         *
         * If set, this supersedes the output media orientation, as it is a more
         * specific configuration.
         *
         * @since 1.3.0
         *
         * @param string $aspectRatio The aspect ratio (e.g. "16:9", "3:2").
         * @return self
         */
        public function asOutputMediaAspectRatio(string $aspectRatio): self
        {
        }
        /**
         * Sets the output speech voice.
         *
         * @since 1.3.0
         *
         * @param string $voice The output speech voice.
         * @return self
         */
        public function asOutputSpeechVoice(string $voice): self
        {
        }
        /**
         * Configures the prompt for JSON response output.
         *
         * @since 0.1.0
         *
         * @param array<string, mixed>|null $schema Optional JSON schema.
         * @return self
         */
        public function asJsonResponse(?array $schema = null): self
        {
        }
        /**
         * Checks if the current prompt is supported by the selected model.
         *
         * @since 0.1.0
         * @since 0.3.0 Method visibility changed to public.
         *
         * @param \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum|null $capability Optional capability to check support for.
         * @return bool True if supported, false otherwise.
         */
        public function isSupported(?\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum $capability = null): bool
        {
        }
        /**
         * Checks if the prompt is supported for text generation.
         *
         * @since 0.1.0
         *
         * @return bool True if text generation is supported.
         */
        public function isSupportedForTextGeneration(): bool
        {
        }
        /**
         * Checks if the prompt is supported for image generation.
         *
         * @since 0.1.0
         *
         * @return bool True if image generation is supported.
         */
        public function isSupportedForImageGeneration(): bool
        {
        }
        /**
         * Checks if the prompt is supported for text to speech conversion.
         *
         * @since 0.1.0
         *
         * @return bool True if text to speech conversion is supported.
         */
        public function isSupportedForTextToSpeechConversion(): bool
        {
        }
        /**
         * Checks if the prompt is supported for video generation.
         *
         * @since 0.1.0
         *
         * @return bool True if video generation is supported.
         */
        public function isSupportedForVideoGeneration(): bool
        {
        }
        /**
         * Checks if the prompt is supported for speech generation.
         *
         * @since 0.1.0
         *
         * @return bool True if speech generation is supported.
         */
        public function isSupportedForSpeechGeneration(): bool
        {
        }
        /**
         * Checks if the prompt is supported for music generation.
         *
         * @since 0.1.0
         *
         * @return bool True if music generation is supported.
         */
        public function isSupportedForMusicGeneration(): bool
        {
        }
        /**
         * Checks if the prompt is supported for embedding generation.
         *
         * @since 0.1.0
         *
         * @return bool True if embedding generation is supported.
         */
        public function isSupportedForEmbeddingGeneration(): bool
        {
        }
        /**
         * Generates a result from the prompt.
         *
         * This is the primary execution method that generates a result (containing
         * potentially multiple candidates) based on the specified capability or
         * the configured output modality.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum|null $capability Optional capability to use for generation.
         *                                        If null, capability is inferred from output modality.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generated result containing candidates.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If the model doesn't support the required capability.
         */
        public function generateResult(?\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum $capability = null): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Generates a text result from the prompt.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generated result containing text candidates.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If the model doesn't support text generation.
         */
        public function generateTextResult(): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Generates an image result from the prompt.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generated result containing image candidates.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If the model doesn't support image generation.
         */
        public function generateImageResult(): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Generates a speech result from the prompt.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generated result containing speech audio candidates.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If the model doesn't support speech generation.
         */
        public function generateSpeechResult(): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Converts text to speech and returns the result.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generated result containing speech audio candidates.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If the model doesn't support text-to-speech conversion.
         */
        public function convertTextToSpeechResult(): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Generates a video result from the prompt.
         *
         * @since 1.3.0
         *
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generated result containing video candidates.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If the model doesn't support video generation.
         */
        public function generateVideoResult(): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Generates text from the prompt.
         *
         * @since 0.1.0
         *
         * @return string The generated text.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         */
        public function generateText(): string
        {
        }
        /**
         * Generates multiple text candidates from the prompt.
         *
         * @since 0.1.0
         *
         * @param int|null $candidateCount The number of candidates to generate.
         * @return list<string> The generated texts.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         */
        public function generateTexts(?int $candidateCount = null): array
        {
        }
        /**
         * Generates an image from the prompt.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\DTO\File The generated image file.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no image is generated.
         */
        public function generateImage(): \WordPress\AiClient\Files\DTO\File
        {
        }
        /**
         * Generates multiple images from the prompt.
         *
         * @since 0.1.0
         *
         * @param int|null $candidateCount The number of images to generate.
         * @return list<\WordPress\AiClient\Files\DTO\File> The generated image files.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no images are generated.
         */
        public function generateImages(?int $candidateCount = null): array
        {
        }
        /**
         * Converts text to speech.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\DTO\File The generated speech audio file.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no audio is generated.
         */
        public function convertTextToSpeech(): \WordPress\AiClient\Files\DTO\File
        {
        }
        /**
         * Converts text to multiple speech outputs.
         *
         * @since 0.1.0
         *
         * @param int|null $candidateCount The number of speech outputs to generate.
         * @return list<\WordPress\AiClient\Files\DTO\File> The generated speech audio files.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no audio is generated.
         */
        public function convertTextToSpeeches(?int $candidateCount = null): array
        {
        }
        /**
         * Generates speech from the prompt.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\DTO\File The generated speech audio file.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no audio is generated.
         */
        public function generateSpeech(): \WordPress\AiClient\Files\DTO\File
        {
        }
        /**
         * Generates multiple speech outputs from the prompt.
         *
         * @since 0.1.0
         *
         * @param int|null $candidateCount The number of speech outputs to generate.
         * @return list<\WordPress\AiClient\Files\DTO\File> The generated speech audio files.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no audio is generated.
         */
        public function generateSpeeches(?int $candidateCount = null): array
        {
        }
        /**
         * Generates a video from the prompt.
         *
         * @since 1.3.0
         *
         * @return \WordPress\AiClient\Files\DTO\File The generated video file.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no video is generated.
         */
        public function generateVideo(): \WordPress\AiClient\Files\DTO\File
        {
        }
        /**
         * Generates multiple videos from the prompt.
         *
         * @since 1.3.0
         *
         * @param int|null $candidateCount The number of videos to generate.
         * @return list<\WordPress\AiClient\Files\DTO\File> The generated video files.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the prompt or model validation fails.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no videos are generated.
         */
        public function generateVideos(?int $candidateCount = null): array
        {
        }
        /**
         * Appends a MessagePart to the messages array.
         *
         * If the last message has a user role, the part is added to it.
         * Otherwise, a new UserMessage is created with the part.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Messages\DTO\MessagePart $part The part to append.
         * @return void
         */
        protected function appendPartToMessages(\WordPress\AiClient\Messages\DTO\MessagePart $part): void
        {
        }
    }
}
namespace WordPress\AiClient\Providers\DTO {
    /**
     * Represents metadata about a provider and its available models.
     *
     * This class combines provider information with the models that
     * the provider offers, facilitating model discovery and selection.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type ProviderMetadataArrayShape from ProviderMetadata
     * @phpstan-import-type ModelMetadataArrayShape from \WordPress\AiClient\Providers\Models\DTO\ModelMetadata
     *
     * @phpstan-type ProviderModelsMetadataArrayShape array{
     *     provider: ProviderMetadataArrayShape,
     *     models: list<ModelMetadataArrayShape>
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<ProviderModelsMetadataArrayShape>
     */
    class ProviderModelsMetadata extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_PROVIDER = 'provider';
        public const KEY_MODELS = 'models';
        /**
         * @var ProviderMetadata The provider metadata.
         */
        protected \WordPress\AiClient\Providers\DTO\ProviderMetadata $provider;
        /**
         * @var list<\WordPress\AiClient\Providers\Models\DTO\ModelMetadata> The available models.
         */
        protected array $models;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param ProviderMetadata $provider The provider metadata.
         * @param list<\WordPress\AiClient\Providers\Models\DTO\ModelMetadata> $models The available models.
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If models is not a list.
         */
        public function __construct(\WordPress\AiClient\Providers\DTO\ProviderMetadata $provider, array $models)
        {
        }
        /**
         * Creates a deep clone of this metadata.
         *
         * Clones the provider metadata and all model metadata objects
         * to ensure the cloned instance is independent of the original.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
        /**
         * Gets the provider metadata.
         *
         * @since 0.1.0
         *
         * @return ProviderMetadata The provider metadata.
         */
        public function getProvider(): \WordPress\AiClient\Providers\DTO\ProviderMetadata
        {
        }
        /**
         * Gets the available models.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Providers\Models\DTO\ModelMetadata> The available models.
         */
        public function getModels(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return ProviderModelsMetadataArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
    /**
     * Represents metadata about an AI provider.
     *
     * This class contains information about an AI provider, including its
     * unique identifier, display name, and type (cloud, server, or client).
     *
     * @since 0.1.0
     * @since 1.2.0 Added optional description property.
     * @since 1.3.0 Added optional logoPath property.
     *
     * @phpstan-type ProviderMetadataArrayShape array{
     *     id: string,
     *     name: string,
     *     description?: ?string,
     *     type: string,
     *     credentialsUrl?: ?string,
     *     authenticationMethod?: ?string,
     *     logoPath?: ?string
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<ProviderMetadataArrayShape>
     */
    class ProviderMetadata extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_ID = 'id';
        public const KEY_NAME = 'name';
        public const KEY_DESCRIPTION = 'description';
        public const KEY_TYPE = 'type';
        public const KEY_CREDENTIALS_URL = 'credentialsUrl';
        public const KEY_AUTHENTICATION_METHOD = 'authenticationMethod';
        public const KEY_LOGO_PATH = 'logoPath';
        /**
         * @var string The provider's unique identifier.
         */
        protected string $id;
        /**
         * @var string The provider's display name.
         */
        protected string $name;
        /**
         * @var string|null The provider's description.
         */
        protected ?string $description;
        /**
         * @var \WordPress\AiClient\Providers\Enums\ProviderTypeEnum The provider type.
         */
        protected \WordPress\AiClient\Providers\Enums\ProviderTypeEnum $type;
        /**
         * @var string|null The URL where users can get credentials.
         */
        protected ?string $credentialsUrl;
        /**
         * @var \WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod|null The authentication method.
         */
        protected ?\WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod $authenticationMethod;
        /**
         * @var string|null The full path to the provider's logo image file.
         */
        protected ?string $logoPath;
        /**
         * Constructor.
         *
         * @since 0.1.0
         * @since 1.2.0 Added optional $description parameter.
         * @since 1.3.0 Added optional $logoPath parameter.
         *
         * @param string $id The provider's unique identifier.
         * @param string $name The provider's display name.
         * @param \WordPress\AiClient\Providers\Enums\ProviderTypeEnum $type The provider type.
         * @param string|null $credentialsUrl The URL where users can get credentials.
         * @param \WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod|null $authenticationMethod The authentication method.
         * @param string|null $description The provider's description.
         * @param string|null $logoPath The full path to the provider's logo image file.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the provider ID contains invalid characters.
         */
        public function __construct(string $id, string $name, \WordPress\AiClient\Providers\Enums\ProviderTypeEnum $type, ?string $credentialsUrl = null, ?\WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod $authenticationMethod = null, ?string $description = null, ?string $logoPath = null)
        {
        }
        /**
         * Gets the provider's unique identifier.
         *
         * @since 0.1.0
         *
         * @return string The provider ID.
         */
        public function getId(): string
        {
        }
        /**
         * Gets the provider's display name.
         *
         * @since 0.1.0
         *
         * @return string The provider name.
         */
        public function getName(): string
        {
        }
        /**
         * Gets the provider's description.
         *
         * @since 1.2.0
         *
         * @return string|null The provider description.
         */
        public function getDescription(): ?string
        {
        }
        /**
         * Gets the provider type.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Enums\ProviderTypeEnum The provider type.
         */
        public function getType(): \WordPress\AiClient\Providers\Enums\ProviderTypeEnum
        {
        }
        /**
         * Gets the credentials URL.
         *
         * @since 0.1.0
         *
         * @return string|null The credentials URL.
         */
        public function getCredentialsUrl(): ?string
        {
        }
        /**
         * Gets the authentication method.
         *
         * @since 0.4.0
         *
         * @return \WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod|null The authentication method.
         */
        public function getAuthenticationMethod(): ?\WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod
        {
        }
        /**
         * Gets the full path to the provider's logo image file.
         *
         * @since 1.3.0
         *
         * @return string|null The full path to the logo image file.
         */
        public function getLogoPath(): ?string
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         * @since 1.2.0 Added description to schema.
         * @since 1.3.0 Added logoPath to schema.
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         * @since 1.2.0 Added description to output.
         * @since 1.3.0 Added logoPath to output.
         *
         * @return ProviderMetadataArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         * @since 1.2.0 Added description support.
         * @since 1.3.0 Added logoPath support.
         */
        public static function fromArray(array $array): self
        {
        }
    }
}
namespace WordPress\AiClient\Providers\Contracts {
    /**
     * Interface for checking provider availability.
     *
     * Determines whether a provider is configured and available
     * for use based on API keys, credentials, or other requirements.
     *
     * @since 0.1.0
     */
    interface ProviderAvailabilityInterface
    {
        /**
         * Checks if the provider is configured.
         *
         * @since 0.1.0
         *
         * @return bool True if the provider is configured and available, false otherwise.
         */
        public function isConfigured(): bool;
    }
    /**
     * Interface for handling provider-level operations.
     *
     * Provides methods to retrieve and manage long-running operations
     * across all models within a provider. Operations are tracked at the
     * provider level rather than per-model.
     *
     * @since 0.1.0
     */
    interface ProviderOperationsHandlerInterface
    {
        /**
         * Gets an operation by ID.
         *
         * @since 0.1.0
         *
         * @param string $operationId Operation identifier.
         * @return \WordPress\AiClient\Operations\Contracts\OperationInterface The operation.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If operation not found.
         */
        public function getOperation(string $operationId): \WordPress\AiClient\Operations\Contracts\OperationInterface;
    }
    /**
     * Interface for providers that support operations handlers.
     *
     * Providers implementing this interface can return an operations handler
     * for managing long-running operations across all their models.
     *
     * @since 0.1.0
     */
    interface ProviderWithOperationsHandlerInterface
    {
        /**
         * Gets the operations handler for this provider.
         *
         * @since 0.1.0
         *
         * @return ProviderOperationsHandlerInterface The operations handler.
         */
        public static function operationsHandler(): \WordPress\AiClient\Providers\Contracts\ProviderOperationsHandlerInterface;
    }
    /**
     * Interface for AI providers.
     *
     * Providers represent AI services (Google, OpenAI, Anthropic, etc.)
     * and provide access to models, metadata, and availability information.
     *
     * @since 0.1.0
     */
    interface ProviderInterface
    {
        /**
         * Gets provider metadata.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\DTO\ProviderMetadata Provider metadata.
         */
        public static function metadata(): \WordPress\AiClient\Providers\DTO\ProviderMetadata;
        /**
         * Creates a model instance.
         *
         * @since 0.1.0
         *
         * @param string $modelId Model identifier.
         * @param ?\WordPress\AiClient\Providers\Models\DTO\ModelConfig $modelConfig Model configuration.
         * @return \WordPress\AiClient\Providers\Models\Contracts\ModelInterface Model instance.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If model not found or configuration invalid.
         */
        public static function model(string $modelId, ?\WordPress\AiClient\Providers\Models\DTO\ModelConfig $modelConfig = null): \WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
        /**
         * Gets provider availability checker.
         *
         * @since 0.1.0
         *
         * @return ProviderAvailabilityInterface Provider availability checker.
         */
        public static function availability(): \WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
        /**
         * Gets model metadata directory.
         *
         * @since 0.1.0
         *
         * @return ModelMetadataDirectoryInterface Model metadata directory.
         */
        public static function modelMetadataDirectory(): \WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
    }
    /**
     * Interface for accessing model metadata within a provider.
     *
     * Provides methods to list, check, and retrieve model metadata
     * for all models supported by a provider.
     *
     * @since 0.1.0
     */
    interface ModelMetadataDirectoryInterface
    {
        /**
         * Lists all available model metadata.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Providers\Models\DTO\ModelMetadata> Array of model metadata.
         */
        public function listModelMetadata(): array;
        /**
         * Checks if metadata exists for a specific model.
         *
         * @since 0.1.0
         *
         * @param string $modelId Model identifier.
         * @return bool True if metadata exists, false otherwise.
         */
        public function hasModelMetadata(string $modelId): bool;
        /**
         * Gets metadata for a specific model.
         *
         * @since 0.1.0
         *
         * @param string $modelId Model identifier.
         * @return \WordPress\AiClient\Providers\Models\DTO\ModelMetadata Model metadata.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If model metadata not found.
         */
        public function getModelMetadata(string $modelId): \WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
    }
}
namespace WordPress\AiClient\Providers\Enums {
    /**
     * Enum for tool types.
     *
     * @since 0.1.0
     *
     * @method static self functionDeclarations() Creates an instance for FUNCTION_DECLARATIONS type.
     * @method static self webSearch() Creates an instance for WEB_SEARCH type.
     * @method bool isFunctionDeclarations() Checks if the type is FUNCTION_DECLARATIONS.
     * @method bool isWebSearch() Checks if the type is WEB_SEARCH.
     */
    class ToolTypeEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Function declarations tool type.
         */
        public const FUNCTION_DECLARATIONS = 'function_declarations';
        /**
         * Web search tool type.
         */
        public const WEB_SEARCH = 'web_search';
    }
    /**
     * Enum for provider types.
     *
     * @since 0.1.0
     *
     * @method static self cloud() Creates an instance for CLOUD type.
     * @method static self server() Creates an instance for SERVER type.
     * @method static self client() Creates an instance for CLIENT type.
     * @method bool isCloud() Checks if the type is CLOUD.
     * @method bool isServer() Checks if the type is SERVER.
     * @method bool isClient() Checks if the type is CLIENT.
     */
    class ProviderTypeEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Cloud-based AI provider (e.g. models available via external REST APIs).
         */
        public const CLOUD = 'cloud';
        /**
         * Server-side AI provider (e.g. self-hosted models).
         */
        public const SERVER = 'server';
        /**
         * Client-side AI provider (e.g. browser-based models).
         */
        public const CLIENT = 'client';
    }
}
namespace WordPress\AiClient\Providers\Models\DTO {
    /**
     * Represents requirements that implementing code has for AI model selection.
     *
     * This class defines the capabilities and options that a model must support
     * in order to be considered suitable for the implementing code's needs.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type RequiredOptionArrayShape from RequiredOption
     *
     * @phpstan-type ModelRequirementsArrayShape array{
     *     requiredCapabilities: list<string>,
     *     requiredOptions: list<RequiredOptionArrayShape>
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<ModelRequirementsArrayShape>
     */
    class ModelRequirements extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_REQUIRED_CAPABILITIES = 'requiredCapabilities';
        public const KEY_REQUIRED_OPTIONS = 'requiredOptions';
        /**
         * @var list<\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum> The capabilities that the model must support.
         */
        protected array $requiredCapabilities;
        /**
         * @var list<RequiredOption> The options that the model must support with specific values.
         */
        protected array $requiredOptions;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum> $requiredCapabilities The capabilities that the model must support.
         * @param list<RequiredOption> $requiredOptions The options that the model must support with specific values.
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If arrays are not lists.
         */
        public function __construct(array $requiredCapabilities, array $requiredOptions)
        {
        }
        /**
         * Gets the capabilities that the model must support.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum> The required capabilities.
         */
        public function getRequiredCapabilities(): array
        {
        }
        /**
         * Gets the options that the model must support with specific values.
         *
         * @since 0.1.0
         *
         * @return list<RequiredOption> The required options.
         */
        public function getRequiredOptions(): array
        {
        }
        /**
         * Checks whether the given model metadata meets these requirements.
         *
         * @since 0.2.0
         *
         * @param ModelMetadata $metadata The model metadata to check against.
         * @return bool True if the model meets all requirements, false otherwise.
         */
        public function areMetBy(\WordPress\AiClient\Providers\Models\DTO\ModelMetadata $metadata): bool
        {
        }
        /**
         * Creates ModelRequirements from prompt data and model configuration.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum $capability The capability the model must support.
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $messages The messages in the conversation.
         * @param ModelConfig $modelConfig The model configuration.
         * @return self The created requirements.
         */
        public static function fromPromptData(\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum $capability, array $messages, \WordPress\AiClient\Providers\Models\DTO\ModelConfig $modelConfig): self
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return ModelRequirementsArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
    /**
     * Represents an option that the implementing code requires the model to support.
     *
     * This class defines an option that the model must support with a specific value
     * for it to be considered suitable for the implementing code's requirements.
     *
     * @since 0.1.0
     *
     * @phpstan-type RequiredOptionArrayShape array{
     *     name: string,
     *     value: mixed
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<RequiredOptionArrayShape>
     */
    class RequiredOption extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_NAME = 'name';
        public const KEY_VALUE = 'value';
        /**
         * @var \WordPress\AiClient\Providers\Models\Enums\OptionEnum The option name.
         */
        protected \WordPress\AiClient\Providers\Models\Enums\OptionEnum $name;
        /**
         * @var mixed The value that the model must support for this option.
         */
        protected $value;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\Enums\OptionEnum $name The option name.
         * @param mixed $value The value that the model must support for this option.
         */
        public function __construct(\WordPress\AiClient\Providers\Models\Enums\OptionEnum $name, $value)
        {
        }
        /**
         * Gets the option name.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Models\Enums\OptionEnum The option name.
         */
        public function getName(): \WordPress\AiClient\Providers\Models\Enums\OptionEnum
        {
        }
        /**
         * Gets the value that the model must support for this option.
         *
         * @since 0.1.0
         *
         * @return mixed The value that the model must support.
         */
        public function getValue()
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return RequiredOptionArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
    /**
     * Represents configuration for an AI model.
     *
     * This class allows configuring various parameters for model behavior,
     * including output modalities, system instructions, generation parameters,
     * and tool integrations.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type FunctionDeclarationArrayShape from \WordPress\AiClient\Tools\DTO\FunctionDeclaration
     * @phpstan-import-type WebSearchArrayShape from \WordPress\AiClient\Tools\DTO\WebSearch
     *
     * @phpstan-type ModelConfigArrayShape array{
     *     outputModalities?: list<string>,
     *     systemInstruction?: string,
     *     candidateCount?: int,
     *     maxTokens?: int,
     *     temperature?: float,
     *     topP?: float,
     *     topK?: int,
     *     stopSequences?: list<string>,
     *     presencePenalty?: float,
     *     frequencyPenalty?: float,
     *     logprobs?: bool,
     *     topLogprobs?: int,
     *     functionDeclarations?: list<FunctionDeclarationArrayShape>,
     *     webSearch?: WebSearchArrayShape,
     *     outputFileType?: string,
     *     outputMimeType?: string,
     *     outputSchema?: array<string, mixed>,
     *     outputMediaOrientation?: string,
     *     outputMediaAspectRatio?: string,
     *     outputSpeechVoice?: string,
     *     customOptions?: array<string, mixed>
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<ModelConfigArrayShape>
     */
    class ModelConfig extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_OUTPUT_MODALITIES = 'outputModalities';
        public const KEY_SYSTEM_INSTRUCTION = 'systemInstruction';
        public const KEY_CANDIDATE_COUNT = 'candidateCount';
        public const KEY_MAX_TOKENS = 'maxTokens';
        public const KEY_TEMPERATURE = 'temperature';
        public const KEY_TOP_P = 'topP';
        public const KEY_TOP_K = 'topK';
        public const KEY_STOP_SEQUENCES = 'stopSequences';
        public const KEY_PRESENCE_PENALTY = 'presencePenalty';
        public const KEY_FREQUENCY_PENALTY = 'frequencyPenalty';
        public const KEY_LOGPROBS = 'logprobs';
        public const KEY_TOP_LOGPROBS = 'topLogprobs';
        public const KEY_FUNCTION_DECLARATIONS = 'functionDeclarations';
        public const KEY_WEB_SEARCH = 'webSearch';
        public const KEY_OUTPUT_FILE_TYPE = 'outputFileType';
        public const KEY_OUTPUT_MIME_TYPE = 'outputMimeType';
        public const KEY_OUTPUT_SCHEMA = 'outputSchema';
        public const KEY_OUTPUT_MEDIA_ORIENTATION = 'outputMediaOrientation';
        public const KEY_OUTPUT_MEDIA_ASPECT_RATIO = 'outputMediaAspectRatio';
        public const KEY_OUTPUT_SPEECH_VOICE = 'outputSpeechVoice';
        public const KEY_CUSTOM_OPTIONS = 'customOptions';
        /*
         * Note: This key is not an actual model config key, but specified here for convenience.
         * It is relevant for model discovery, to determine which models support which input modalities.
         * The actual input modalities are part of the message sent to the model, not the model config.
         */
        public const KEY_INPUT_MODALITIES = 'inputModalities';
        /**
         * @var list<\WordPress\AiClient\Messages\Enums\ModalityEnum>|null Output modalities for the model.
         */
        protected ?array $outputModalities = null;
        /**
         * @var string|null System instruction for the model.
         */
        protected ?string $systemInstruction = null;
        /**
         * @var int|null Number of response candidates to generate.
         */
        protected ?int $candidateCount = null;
        /**
         * @var int|null Maximum number of tokens to generate.
         */
        protected ?int $maxTokens = null;
        /**
         * @var float|null Temperature for randomness (0.0 to 2.0).
         */
        protected ?float $temperature = null;
        /**
         * @var float|null Top-p nucleus sampling parameter.
         */
        protected ?float $topP = null;
        /**
         * @var int|null Top-k sampling parameter.
         */
        protected ?int $topK = null;
        /**
         * @var list<string>|null Stop sequences.
         */
        protected ?array $stopSequences = null;
        /**
         * @var float|null Presence penalty for reducing repetition.
         */
        protected ?float $presencePenalty = null;
        /**
         * @var float|null Frequency penalty for reducing repetition.
         */
        protected ?float $frequencyPenalty = null;
        /**
         * @var bool|null Whether to return log probabilities.
         */
        protected ?bool $logprobs = null;
        /**
         * @var int|null Number of top log probabilities to return.
         */
        protected ?int $topLogprobs = null;
        /**
         * @var list<\WordPress\AiClient\Tools\DTO\FunctionDeclaration>|null Function declarations available to the model.
         */
        protected ?array $functionDeclarations = null;
        /**
         * @var \WordPress\AiClient\Tools\DTO\WebSearch|null Web search configuration for the model.
         */
        protected ?\WordPress\AiClient\Tools\DTO\WebSearch $webSearch = null;
        /**
         * @var \WordPress\AiClient\Files\Enums\FileTypeEnum|null Output file type.
         */
        protected ?\WordPress\AiClient\Files\Enums\FileTypeEnum $outputFileType = null;
        /**
         * @var string|null Output MIME type.
         */
        protected ?string $outputMimeType = null;
        /**
         * @var array<string, mixed>|null Output schema (JSON schema).
         */
        protected ?array $outputSchema = null;
        /**
         * @var \WordPress\AiClient\Files\Enums\MediaOrientationEnum|null Output media orientation.
         */
        protected ?\WordPress\AiClient\Files\Enums\MediaOrientationEnum $outputMediaOrientation = null;
        /**
         * @var string|null Output media aspect ratio (e.g. 3:2, 16:9).
         */
        protected ?string $outputMediaAspectRatio = null;
        /**
         * @var string|null Output speech voice.
         */
        protected ?string $outputSpeechVoice = null;
        /**
         * @var array<string, mixed> Custom provider-specific options.
         */
        protected array $customOptions = [];
        /**
         * Creates a deep clone of this configuration.
         *
         * Clones nested objects (functionDeclarations, webSearch) to ensure
         * the cloned configuration is independent of the original.
         * Enum value objects (outputModalities, outputFileType, outputMediaOrientation)
         * are intentionally shared as they are immutable.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
        /**
         * Sets the output modalities.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\Enums\ModalityEnum> $outputModalities The output modalities.
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the array is not a list.
         */
        public function setOutputModalities(array $outputModalities): void
        {
        }
        /**
         * Gets the output modalities.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Messages\Enums\ModalityEnum>|null The output modalities.
         */
        public function getOutputModalities(): ?array
        {
        }
        /**
         * Sets the system instruction.
         *
         * @since 0.1.0
         *
         * @param string $systemInstruction The system instruction.
         */
        public function setSystemInstruction(string $systemInstruction): void
        {
        }
        /**
         * Gets the system instruction.
         *
         * @since 0.1.0
         *
         * @return string|null The system instruction.
         */
        public function getSystemInstruction(): ?string
        {
        }
        /**
         * Sets the candidate count.
         *
         * @since 0.1.0
         *
         * @param int $candidateCount The candidate count.
         */
        public function setCandidateCount(int $candidateCount): void
        {
        }
        /**
         * Gets the candidate count.
         *
         * @since 0.1.0
         *
         * @return int|null The candidate count.
         */
        public function getCandidateCount(): ?int
        {
        }
        /**
         * Sets the maximum tokens.
         *
         * @since 0.1.0
         *
         * @param int $maxTokens The maximum tokens.
         */
        public function setMaxTokens(int $maxTokens): void
        {
        }
        /**
         * Gets the maximum tokens.
         *
         * @since 0.1.0
         *
         * @return int|null The maximum tokens.
         */
        public function getMaxTokens(): ?int
        {
        }
        /**
         * Sets the temperature.
         *
         * @since 0.1.0
         *
         * @param float $temperature The temperature.
         */
        public function setTemperature(float $temperature): void
        {
        }
        /**
         * Gets the temperature.
         *
         * @since 0.1.0
         *
         * @return float|null The temperature.
         */
        public function getTemperature(): ?float
        {
        }
        /**
         * Sets the top-p parameter.
         *
         * @since 0.1.0
         *
         * @param float $topP The top-p parameter.
         */
        public function setTopP(float $topP): void
        {
        }
        /**
         * Gets the top-p parameter.
         *
         * @since 0.1.0
         *
         * @return float|null The top-p parameter.
         */
        public function getTopP(): ?float
        {
        }
        /**
         * Sets the top-k parameter.
         *
         * @since 0.1.0
         *
         * @param int $topK The top-k parameter.
         */
        public function setTopK(int $topK): void
        {
        }
        /**
         * Gets the top-k parameter.
         *
         * @since 0.1.0
         *
         * @return int|null The top-k parameter.
         */
        public function getTopK(): ?int
        {
        }
        /**
         * Sets the stop sequences.
         *
         * @since 0.1.0
         *
         * @param list<string> $stopSequences The stop sequences.
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the array is not a list.
         */
        public function setStopSequences(array $stopSequences): void
        {
        }
        /**
         * Gets the stop sequences.
         *
         * @since 0.1.0
         *
         * @return list<string>|null The stop sequences.
         */
        public function getStopSequences(): ?array
        {
        }
        /**
         * Sets the presence penalty.
         *
         * @since 0.1.0
         *
         * @param float $presencePenalty The presence penalty.
         */
        public function setPresencePenalty(float $presencePenalty): void
        {
        }
        /**
         * Gets the presence penalty.
         *
         * @since 0.1.0
         *
         * @return float|null The presence penalty.
         */
        public function getPresencePenalty(): ?float
        {
        }
        /**
         * Sets the frequency penalty.
         *
         * @since 0.1.0
         *
         * @param float $frequencyPenalty The frequency penalty.
         */
        public function setFrequencyPenalty(float $frequencyPenalty): void
        {
        }
        /**
         * Gets the frequency penalty.
         *
         * @since 0.1.0
         *
         * @return float|null The frequency penalty.
         */
        public function getFrequencyPenalty(): ?float
        {
        }
        /**
         * Sets whether to return log probabilities.
         *
         * @since 0.1.0
         *
         * @param bool $logprobs Whether to return log probabilities.
         */
        public function setLogprobs(bool $logprobs): void
        {
        }
        /**
         * Gets whether to return log probabilities.
         *
         * @since 0.1.0
         *
         * @return bool|null Whether to return log probabilities.
         */
        public function getLogprobs(): ?bool
        {
        }
        /**
         * Sets the number of top log probabilities to return.
         *
         * @since 0.1.0
         *
         * @param int $topLogprobs The number of top log probabilities.
         */
        public function setTopLogprobs(int $topLogprobs): void
        {
        }
        /**
         * Gets the number of top log probabilities to return.
         *
         * @since 0.1.0
         *
         * @return int|null The number of top log probabilities.
         */
        public function getTopLogprobs(): ?int
        {
        }
        /**
         * Sets the function declarations.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Tools\DTO\FunctionDeclaration> $functionDeclarations The function declarations.
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the array is not a list.
         */
        public function setFunctionDeclarations(array $functionDeclarations): void
        {
        }
        /**
         * Gets the function declarations.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Tools\DTO\FunctionDeclaration>|null The function declarations.
         */
        public function getFunctionDeclarations(): ?array
        {
        }
        /**
         * Sets the web search configuration.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Tools\DTO\WebSearch $webSearch The web search configuration.
         */
        public function setWebSearch(\WordPress\AiClient\Tools\DTO\WebSearch $webSearch): void
        {
        }
        /**
         * Gets the web search configuration.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Tools\DTO\WebSearch|null The web search configuration.
         */
        public function getWebSearch(): ?\WordPress\AiClient\Tools\DTO\WebSearch
        {
        }
        /**
         * Sets the output file type.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Files\Enums\FileTypeEnum $outputFileType The output file type.
         */
        public function setOutputFileType(\WordPress\AiClient\Files\Enums\FileTypeEnum $outputFileType): void
        {
        }
        /**
         * Gets the output file type.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\Enums\FileTypeEnum|null The output file type.
         */
        public function getOutputFileType(): ?\WordPress\AiClient\Files\Enums\FileTypeEnum
        {
        }
        /**
         * Sets the output MIME type.
         *
         * @since 0.1.0
         *
         * @param string $outputMimeType The output MIME type.
         */
        public function setOutputMimeType(string $outputMimeType): void
        {
        }
        /**
         * Gets the output MIME type.
         *
         * @since 0.1.0
         *
         * @return string|null The output MIME type.
         */
        public function getOutputMimeType(): ?string
        {
        }
        /**
         * Sets the output schema.
         *
         * When setting an output schema, this method automatically sets
         * the output MIME type to "application/json" if not already set.
         *
         * @since 0.1.0
         *
         * @param array<string, mixed> $outputSchema The output schema (JSON schema).
         */
        public function setOutputSchema(array $outputSchema): void
        {
        }
        /**
         * Gets the output schema.
         *
         * @since 0.1.0
         *
         * @return array<string, mixed>|null The output schema.
         */
        public function getOutputSchema(): ?array
        {
        }
        /**
         * Sets the output media orientation.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Files\Enums\MediaOrientationEnum $outputMediaOrientation The output media orientation.
         */
        public function setOutputMediaOrientation(\WordPress\AiClient\Files\Enums\MediaOrientationEnum $outputMediaOrientation): void
        {
        }
        /**
         * Gets the output media orientation.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\Enums\MediaOrientationEnum|null The output media orientation.
         */
        public function getOutputMediaOrientation(): ?\WordPress\AiClient\Files\Enums\MediaOrientationEnum
        {
        }
        /**
         * Sets the output media aspect ratio.
         *
         * If set, this supersedes the output media orientation, as it is a more specific configuration.
         *
         * @since 0.1.0
         *
         * @param string $outputMediaAspectRatio The output media aspect ratio (e.g. 3:2, 16:9).
         */
        public function setOutputMediaAspectRatio(string $outputMediaAspectRatio): void
        {
        }
        /**
         * Gets the output media aspect ratio.
         *
         * @since 0.1.0
         *
         * @return string|null The output media aspect ratio (e.g. 3:2, 16:9).
         */
        public function getOutputMediaAspectRatio(): ?string
        {
        }
        /**
         * Validates that the given media orientation and aspect ratio values do not conflict with each other.
         *
         * @since 0.4.0
         *
         * @param \WordPress\AiClient\Files\Enums\MediaOrientationEnum $orientation The desired media orientation.
         * @param string $aspectRatio The desired media aspect ratio.
         */
        protected function validateMediaOrientationAspectRatioCompatibility(\WordPress\AiClient\Files\Enums\MediaOrientationEnum $orientation, string $aspectRatio): void
        {
        }
        /**
         * Sets the output speech voice.
         *
         * @since 0.1.0
         *
         * @param string $outputSpeechVoice The output speech voice.
         */
        public function setOutputSpeechVoice(string $outputSpeechVoice): void
        {
        }
        /**
         * Gets the output speech voice.
         *
         * @since 0.1.0
         *
         * @return string|null The output speech voice.
         */
        public function getOutputSpeechVoice(): ?string
        {
        }
        /**
         * Sets a single custom option.
         *
         * @since 0.1.0
         *
         * @param string $key   The option key.
         * @param mixed  $value The option value.
         */
        public function setCustomOption(string $key, $value): void
        {
        }
        /**
         * Sets the custom options.
         *
         * @since 0.1.0
         *
         * @param array<string, mixed> $customOptions The custom options.
         */
        public function setCustomOptions(array $customOptions): void
        {
        }
        /**
         * Gets the custom options.
         *
         * @since 0.1.0
         *
         * @return array<string, mixed> The custom options.
         */
        public function getCustomOptions(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return ModelConfigArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
    /**
     * Represents a supported configuration option for an AI model.
     *
     * This class defines an option that a model supports, including its name
     * and the values that are valid for that option.
     *
     * @since 0.1.0
     *
     * @phpstan-type SupportedOptionArrayShape array{
     *     name: string,
     *     supportedValues?: list<mixed>
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<SupportedOptionArrayShape>
     */
    class SupportedOption extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_NAME = 'name';
        public const KEY_SUPPORTED_VALUES = 'supportedValues';
        /**
         * @var \WordPress\AiClient\Providers\Models\Enums\OptionEnum The option name.
         */
        protected \WordPress\AiClient\Providers\Models\Enums\OptionEnum $name;
        /**
         * @var list<mixed>|null The supported values for this option.
         */
        protected ?array $supportedValues;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\Enums\OptionEnum $name The option name.
         * @param list<mixed>|null $supportedValues The supported values for this option, or null if any value is supported.
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If supportedValues is not null and not a list.
         */
        public function __construct(\WordPress\AiClient\Providers\Models\Enums\OptionEnum $name, ?array $supportedValues = null)
        {
        }
        /**
         * Gets the option name.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Models\Enums\OptionEnum The option name.
         */
        public function getName(): \WordPress\AiClient\Providers\Models\Enums\OptionEnum
        {
        }
        /**
         * Checks if a value is supported for this option.
         *
         * @since 0.1.0
         *
         * @param mixed $value The value to check.
         * @return bool True if the value is supported, false otherwise.
         */
        public function isSupportedValue($value): bool
        {
        }
        /**
         * Gets the supported values for this option.
         *
         * @since 0.1.0
         *
         * @return list<mixed>|null The supported values, or null if any value is supported.
         */
        public function getSupportedValues(): ?array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return SupportedOptionArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
    /**
     * Represents metadata about an AI model.
     *
     * This class contains information about a specific AI model, including
     * its identifier, display name, supported capabilities, and configuration options.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type SupportedOptionArrayShape from SupportedOption
     *
     * @phpstan-type ModelMetadataArrayShape array{
     *     id: string,
     *     name: string,
     *     supportedCapabilities: list<string>,
     *     supportedOptions: list<SupportedOptionArrayShape>
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<ModelMetadataArrayShape>
     */
    class ModelMetadata extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_ID = 'id';
        public const KEY_NAME = 'name';
        public const KEY_SUPPORTED_CAPABILITIES = 'supportedCapabilities';
        public const KEY_SUPPORTED_OPTIONS = 'supportedOptions';
        /**
         * @var string The model's unique identifier.
         */
        protected string $id;
        /**
         * @var string The model's display name.
         */
        protected string $name;
        /**
         * @var list<\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum> The model's supported capabilities.
         */
        protected array $supportedCapabilities;
        /**
         * @var list<SupportedOption> The model's supported configuration options.
         */
        protected array $supportedOptions;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string $id The model's unique identifier.
         * @param string $name The model's display name.
         * @param list<\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum> $supportedCapabilities The model's supported capabilities.
         * @param list<SupportedOption> $supportedOptions The model's supported configuration options.
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If arrays are not lists.
         */
        public function __construct(string $id, string $name, array $supportedCapabilities, array $supportedOptions)
        {
        }
        /**
         * Gets the model's unique identifier.
         *
         * @since 0.1.0
         *
         * @return string The model ID.
         */
        public function getId(): string
        {
        }
        /**
         * Gets the model's display name.
         *
         * @since 0.1.0
         *
         * @return string The model name.
         */
        public function getName(): string
        {
        }
        /**
         * Gets the model's supported capabilities.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum> The supported capabilities.
         */
        public function getSupportedCapabilities(): array
        {
        }
        /**
         * Gets the model's supported configuration options.
         *
         * @since 0.1.0
         *
         * @return list<SupportedOption> The supported options.
         */
        public function getSupportedOptions(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return ModelMetadataArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
        /**
         * Performs a deep clone of the model metadata.
         *
         * This method ensures that supported option objects are cloned to prevent
         * modifications to the cloned metadata from affecting the original.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
    }
}
namespace WordPress\AiClient\Providers\Models\ImageGeneration\Contracts {
    /**
     * Interface for models that support asynchronous image generation operations.
     *
     * Provides methods for initiating long-running image generation tasks.
     *
     * @since 0.1.0
     */
    interface ImageGenerationOperationModelInterface
    {
        /**
         * Creates an image generation operation.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the image generation prompt.
         * @return \WordPress\AiClient\Operations\DTO\GenerativeAiOperation The initiated image generation operation.
         */
        public function generateImageOperation(array $prompt): \WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
    }
    /**
     * Interface for models that support image generation.
     *
     * Provides synchronous methods for generating images from text prompts.
     *
     * @since 0.1.0
     */
    interface ImageGenerationModelInterface
    {
        /**
         * Generates images from a prompt.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the image generation prompt.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult Result containing generated images.
         */
        public function generateImageResult(array $prompt): \WordPress\AiClient\Results\DTO\GenerativeAiResult;
    }
}
namespace WordPress\AiClient\Providers\Models\TextGeneration\Contracts {
    /**
     * Interface for models that support text generation.
     *
     * Provides synchronous and streaming methods for generating text from prompts.
     *
     * @since 0.1.0
     */
    interface TextGenerationModelInterface
    {
        /**
         * Generates text from a prompt.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the text generation prompt.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult Result containing generated text.
         */
        public function generateTextResult(array $prompt): \WordPress\AiClient\Results\DTO\GenerativeAiResult;
    }
    /**
     * Interface for models that support asynchronous text generation operations.
     *
     * Provides methods for initiating long-running text generation tasks.
     *
     * @since 0.1.0
     */
    interface TextGenerationOperationModelInterface
    {
        /**
         * Creates a text generation operation.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the text generation prompt.
         * @return \WordPress\AiClient\Operations\DTO\GenerativeAiOperation The initiated text generation operation.
         */
        public function generateTextOperation(array $prompt): \WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
    }
}
namespace WordPress\AiClient\Providers\Models\Contracts {
    /**
     * Interface for AI models.
     *
     * Models represent specific AI models from providers and define
     * their capabilities, configuration, and execution methods.
     *
     * @since 0.1.0
     */
    interface ModelInterface
    {
        /**
         * Gets model metadata.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Models\DTO\ModelMetadata Model metadata.
         */
        public function metadata(): \WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
        /**
         * Returns the metadata for the model's provider.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\DTO\ProviderMetadata The provider metadata.
         */
        public function providerMetadata(): \WordPress\AiClient\Providers\DTO\ProviderMetadata;
        /**
         * Sets model configuration.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\DTO\ModelConfig $config Model configuration.
         * @return void
         */
        public function setConfig(\WordPress\AiClient\Providers\Models\DTO\ModelConfig $config): void;
        /**
         * Gets model configuration.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Models\DTO\ModelConfig Current model configuration.
         */
        public function getConfig(): \WordPress\AiClient\Providers\Models\DTO\ModelConfig;
    }
}
namespace WordPress\AiClient\Providers\Models\Enums {
    /**
     * Enum for model options.
     *
     * This enum dynamically includes all options from ModelConfig KEY_* constants
     * in addition to the explicitly defined constants below.
     *
     * Explicitly defined option (not in ModelConfig):
     * @method static self inputModalities() Creates an instance for INPUT_MODALITIES option.
     * @method bool isInputModalities() Checks if the option is INPUT_MODALITIES.
     *
     * Dynamically loaded from ModelConfig KEY_* constants:
     * @method static self candidateCount() Creates an instance for CANDIDATE_COUNT option.
     * @method static self customOptions() Creates an instance for CUSTOM_OPTIONS option.
     * @method static self frequencyPenalty() Creates an instance for FREQUENCY_PENALTY option.
     * @method static self functionDeclarations() Creates an instance for FUNCTION_DECLARATIONS option.
     * @method static self logprobs() Creates an instance for LOGPROBS option.
     * @method static self maxTokens() Creates an instance for MAX_TOKENS option.
     * @method static self outputFileType() Creates an instance for OUTPUT_FILE_TYPE option.
     * @method static self outputMediaAspectRatio() Creates an instance for OUTPUT_MEDIA_ASPECT_RATIO option.
     * @method static self outputMediaOrientation() Creates an instance for OUTPUT_MEDIA_ORIENTATION option.
     * @method static self outputMimeType() Creates an instance for OUTPUT_MIME_TYPE option.
     * @method static self outputModalities() Creates an instance for OUTPUT_MODALITIES option.
     * @method static self outputSchema() Creates an instance for OUTPUT_SCHEMA option.
     * @method static self outputSpeechVoice() Creates an instance for OUTPUT_SPEECH_VOICE option.
     * @method static self presencePenalty() Creates an instance for PRESENCE_PENALTY option.
     * @method static self stopSequences() Creates an instance for STOP_SEQUENCES option.
     * @method static self systemInstruction() Creates an instance for SYSTEM_INSTRUCTION option.
     * @method static self temperature() Creates an instance for TEMPERATURE option.
     * @method static self topK() Creates an instance for TOP_K option.
     * @method static self topLogprobs() Creates an instance for TOP_LOGPROBS option.
     * @method static self topP() Creates an instance for TOP_P option.
     * @method static self webSearch() Creates an instance for WEB_SEARCH option.
     * @method bool isCandidateCount() Checks if the option is CANDIDATE_COUNT.
     * @method bool isCustomOptions() Checks if the option is CUSTOM_OPTIONS.
     * @method bool isFrequencyPenalty() Checks if the option is FREQUENCY_PENALTY.
     * @method bool isFunctionDeclarations() Checks if the option is FUNCTION_DECLARATIONS.
     * @method bool isLogprobs() Checks if the option is LOGPROBS.
     * @method bool isMaxTokens() Checks if the option is MAX_TOKENS.
     * @method bool isOutputFileType() Checks if the option is OUTPUT_FILE_TYPE.
     * @method bool isOutputMediaAspectRatio() Checks if the option is OUTPUT_MEDIA_ASPECT_RATIO.
     * @method bool isOutputMediaOrientation() Checks if the option is OUTPUT_MEDIA_ORIENTATION.
     * @method bool isOutputMimeType() Checks if the option is OUTPUT_MIME_TYPE.
     * @method bool isOutputModalities() Checks if the option is OUTPUT_MODALITIES.
     * @method bool isOutputSchema() Checks if the option is OUTPUT_SCHEMA.
     * @method bool isOutputSpeechVoice() Checks if the option is OUTPUT_SPEECH_VOICE.
     * @method bool isPresencePenalty() Checks if the option is PRESENCE_PENALTY.
     * @method bool isStopSequences() Checks if the option is STOP_SEQUENCES.
     * @method bool isSystemInstruction() Checks if the option is SYSTEM_INSTRUCTION.
     * @method bool isTemperature() Checks if the option is TEMPERATURE.
     * @method bool isTopK() Checks if the option is TOP_K.
     * @method bool isTopLogprobs() Checks if the option is TOP_LOGPROBS.
     * @method bool isTopP() Checks if the option is TOP_P.
     * @method bool isWebSearch() Checks if the option is WEB_SEARCH.
     *
     * @since 0.1.0
     */
    class OptionEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Input modalities option.
         *
         * This constant is not in ModelConfig as it's derived from message content,
         * not configured directly.
         */
        public const INPUT_MODALITIES = 'input_modalities';
        /**
         * Determines the class enumerations by reflecting on class constants.
         *
         * Overrides the parent method to dynamically add constants from ModelConfig
         * that are prefixed with KEY_. These are transformed to remove the KEY_ prefix
         * and converted to snake_case values.
         *
         * @since 0.1.0
         *
         * @param class-string $className The fully qualified class name.
         * @return array<string, string> The enum constants.
         */
        protected static function determineClassEnumerations(string $className): array
        {
        }
    }
    /**
     * Enum for model capabilities.
     *
     * @since 0.1.0
     *
     * @method static self textGeneration() Creates an instance for TEXT_GENERATION capability.
     * @method static self imageGeneration() Creates an instance for IMAGE_GENERATION capability.
     * @method static self textToSpeechConversion() Creates an instance for TEXT_TO_SPEECH_CONVERSION capability.
     * @method static self speechGeneration() Creates an instance for SPEECH_GENERATION capability.
     * @method static self musicGeneration() Creates an instance for MUSIC_GENERATION capability.
     * @method static self videoGeneration() Creates an instance for VIDEO_GENERATION capability.
     * @method static self embeddingGeneration() Creates an instance for EMBEDDING_GENERATION capability.
     * @method static self chatHistory() Creates an instance for CHAT_HISTORY capability.
     * @method bool isTextGeneration() Checks if the capability is TEXT_GENERATION.
     * @method bool isImageGeneration() Checks if the capability is IMAGE_GENERATION.
     * @method bool isTextToSpeechConversion() Checks if the capability is TEXT_TO_SPEECH_CONVERSION.
     * @method bool isSpeechGeneration() Checks if the capability is SPEECH_GENERATION.
     * @method bool isMusicGeneration() Checks if the capability is MUSIC_GENERATION.
     * @method bool isVideoGeneration() Checks if the capability is VIDEO_GENERATION.
     * @method bool isEmbeddingGeneration() Checks if the capability is EMBEDDING_GENERATION.
     * @method bool isChatHistory() Checks if the capability is CHAT_HISTORY.
     */
    class CapabilityEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Text generation capability.
         */
        public const TEXT_GENERATION = 'text_generation';
        /**
         * Image generation capability.
         */
        public const IMAGE_GENERATION = 'image_generation';
        /**
         * Text to speech conversion capability.
         */
        public const TEXT_TO_SPEECH_CONVERSION = 'text_to_speech_conversion';
        /**
         * Speech generation capability.
         */
        public const SPEECH_GENERATION = 'speech_generation';
        /**
         * Music generation capability.
         */
        public const MUSIC_GENERATION = 'music_generation';
        /**
         * Video generation capability.
         */
        public const VIDEO_GENERATION = 'video_generation';
        /**
         * Embedding generation capability.
         */
        public const EMBEDDING_GENERATION = 'embedding_generation';
        /**
         * Chat history support capability.
         */
        public const CHAT_HISTORY = 'chat_history';
    }
}
namespace WordPress\AiClient\Providers\Models\VideoGeneration\Contracts {
    /**
     * Interface for models that support asynchronous video generation operations.
     *
     * Provides methods for initiating long-running video generation tasks.
     *
     * @since 1.3.0
     */
    interface VideoGenerationOperationModelInterface
    {
        /**
         * Creates a video generation operation.
         *
         * @since 1.3.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the video generation prompt.
         * @return \WordPress\AiClient\Operations\DTO\GenerativeAiOperation The initiated video generation operation.
         */
        public function generateVideoOperation(array $prompt): \WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
    }
    /**
     * Interface for models that support video generation.
     *
     * Provides synchronous methods for generating videos from prompts.
     *
     * @since 1.3.0
     */
    interface VideoGenerationModelInterface
    {
        /**
         * Generates videos from a prompt.
         *
         * @since 1.3.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the video generation prompt.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult Result containing generated videos.
         */
        public function generateVideoResult(array $prompt): \WordPress\AiClient\Results\DTO\GenerativeAiResult;
    }
}
namespace WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts {
    /**
     * Interface for models that support asynchronous speech generation operations.
     *
     * Provides methods for initiating long-running speech generation tasks.
     *
     * @since 0.1.0
     */
    interface SpeechGenerationOperationModelInterface
    {
        /**
         * Creates a speech generation operation.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the speech generation prompt.
         * @return \WordPress\AiClient\Operations\DTO\GenerativeAiOperation The initiated speech generation operation.
         */
        public function generateSpeechOperation(array $prompt): \WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
    }
    /**
     * Interface for models that support speech generation.
     *
     * Provides synchronous methods for generating speech from prompts.
     *
     * @since 0.1.0
     */
    interface SpeechGenerationModelInterface
    {
        /**
         * Generates speech from a prompt.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the speech generation prompt.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult Result containing generated speech audio.
         */
        public function generateSpeechResult(array $prompt): \WordPress\AiClient\Results\DTO\GenerativeAiResult;
    }
}
namespace WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts {
    /**
     * Interface for models that support asynchronous text-to-speech conversion operations.
     *
     * Provides methods for initiating long-running text-to-speech conversion tasks.
     *
     * @since 0.1.0
     */
    interface TextToSpeechConversionOperationModelInterface
    {
        /**
         * Creates a text-to-speech conversion operation.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the text to convert to speech.
         * @return \WordPress\AiClient\Operations\DTO\GenerativeAiOperation The initiated text-to-speech conversion operation.
         */
        public function convertTextToSpeechOperation(array $prompt): \WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
    }
    /**
     * Interface for models that support text-to-speech conversion.
     *
     * Provides synchronous methods for converting text to speech audio.
     *
     * @since 0.1.0
     */
    interface TextToSpeechConversionModelInterface
    {
        /**
         * Converts text to speech.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Array of messages containing the text to convert to speech.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult Result containing generated speech audio.
         */
        public function convertTextToSpeechResult(array $prompt): \WordPress\AiClient\Results\DTO\GenerativeAiResult;
    }
}
namespace WordPress\AiClient\Providers\ApiBasedImplementation\Contracts {
    /**
     * Interface for API-based AI models that support HTTP transport configuration.
     *
     * This interface extends ModelInterface to add request options support
     * for models that communicate with external APIs via HTTP.
     *
     * @since 0.3.0
     */
    interface ApiBasedModelInterface extends \WordPress\AiClient\Providers\Models\Contracts\ModelInterface
    {
        /**
         * Sets the request options for HTTP transport.
         *
         * @since 0.3.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\RequestOptions $requestOptions The request options to use.
         * @return void
         */
        public function setRequestOptions(\WordPress\AiClient\Providers\Http\DTO\RequestOptions $requestOptions): void;
        /**
         * Gets the request options for HTTP transport.
         *
         * @since 0.3.0
         *
         * @return \WordPress\AiClient\Providers\Http\DTO\RequestOptions|null The request options, or null if not set.
         */
        public function getRequestOptions(): ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions;
    }
}
namespace WordPress\AiClient\Providers\Http\Contracts {
    /**
     * Interface for models that require HTTP transport capabilities.
     *
     * @since 0.1.0
     */
    interface WithHttpTransporterInterface
    {
        /**
         * Sets the HTTP transporter.
         *
         * @since 0.1.0
         *
         * @param HttpTransporterInterface $transporter The HTTP transporter instance.
         * @return void
         */
        public function setHttpTransporter(\WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface $transporter): void;
        /**
         * Returns the HTTP transporter.
         *
         * @since 0.1.0
         *
         * @return HttpTransporterInterface The HTTP transporter instance.
         */
        public function getHttpTransporter(): \WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
    }
    /**
     * Interface for models that support request authentication.
     *
     * @since 0.1.0
     */
    interface WithRequestAuthenticationInterface
    {
        /**
         * Sets the request authentication.
         *
         * @since 0.1.0
         *
         * @param RequestAuthenticationInterface $authentication The authentication instance.
         * @return void
         */
        public function setRequestAuthentication(\WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface $authentication): void;
        /**
         * Returns the request authentication.
         *
         * @since 0.1.0
         *
         * @return RequestAuthenticationInterface The authentication instance.
         */
        public function getRequestAuthentication(): \WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
    }
}
namespace WordPress\AiClient\Providers\Http\Traits {
    /**
     * Trait for a class that implements WithHttpTransporterInterface.
     *
     * @since 0.1.0
     */
    trait WithHttpTransporterTrait
    {
        /**
         * @var \WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface|null The HTTP transporter instance.
         */
        private ?\WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface $httpTransporter = null;
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function setHttpTransporter(\WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface $httpTransporter): void
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function getHttpTransporter(): \WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface
        {
        }
    }
    /**
     * Trait for a class that implements WithRequestAuthenticationInterface.
     *
     * @since 0.1.0
     */
    trait WithRequestAuthenticationTrait
    {
        /**
         * @var \WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface|null The request authentication instance.
         */
        private ?\WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface $requestAuthentication = null;
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function setRequestAuthentication(\WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface $requestAuthentication): void
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function getRequestAuthentication(): \WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface
        {
        }
    }
}
namespace WordPress\AiClient\Providers\ApiBasedImplementation {
    /**
     * Base class for an API-based model for a provider.
     *
     * While this class contains no abstract methods, it is still abstract to ensure that each model class can actually
     * perform generative AI tasks by implementing the corresponding interfaces.
     *
     * @since 0.1.0
     */
    abstract class AbstractApiBasedModel implements \WordPress\AiClient\Providers\ApiBasedImplementation\Contracts\ApiBasedModelInterface, \WordPress\AiClient\Providers\Http\Contracts\WithHttpTransporterInterface, \WordPress\AiClient\Providers\Http\Contracts\WithRequestAuthenticationInterface
    {
        use \WordPress\AiClient\Providers\Http\Traits\WithHttpTransporterTrait;
        use \WordPress\AiClient\Providers\Http\Traits\WithRequestAuthenticationTrait;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\DTO\ModelMetadata $metadata The metadata for the model.
         * @param \WordPress\AiClient\Providers\DTO\ProviderMetadata $providerMetadata The metadata for the model's provider.
         */
        public function __construct(\WordPress\AiClient\Providers\Models\DTO\ModelMetadata $metadata, \WordPress\AiClient\Providers\DTO\ProviderMetadata $providerMetadata)
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public function metadata(): \WordPress\AiClient\Providers\Models\DTO\ModelMetadata
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public function providerMetadata(): \WordPress\AiClient\Providers\DTO\ProviderMetadata
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public function setConfig(\WordPress\AiClient\Providers\Models\DTO\ModelConfig $config): void
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public function getConfig(): \WordPress\AiClient\Providers\Models\DTO\ModelConfig
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.3.0
         */
        final public function setRequestOptions(\WordPress\AiClient\Providers\Http\DTO\RequestOptions $requestOptions): void
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.3.0
         */
        final public function getRequestOptions(): ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions
        {
        }
    }
}
namespace WordPress\AiClient\Providers\OpenAiCompatibleImplementation {
    /**
     * Base class for an image generation model for providers that implement OpenAI's API format.
     *
     * This abstract class is designed to work with any AI provider that offers an OpenAI-compatible
     * API endpoint for image generation, including but not limited to Anthropic, Google, and other
     * providers that have adopted OpenAI's image generation API specification as a standard interface.
     *
     * @since 0.1.0
     *
     * @phpstan-type ImageGenerationParams array{
     *     model: string,
     *     prompt: string,
     *     n?: int,
     *     response_format?: string,
     *     output_format?: string|null,
     *     size?: string,
     *     ...
     * }
     * @phpstan-type ChoiceData array{
     *     url?: string,
     *     b64_json?: string
     * }
     * @phpstan-type UsageData array{
     *     input_tokens?: int,
     *     output_tokens?: int,
     *     total_tokens?: int
     * }
     * @phpstan-type ResponseData array{
     *     id?: string,
     *     data?: list<ChoiceData>,
     *     usage?: UsageData
     * }
     */
    abstract class AbstractOpenAiCompatibleImageGenerationModel extends \WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel implements \WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface
    {
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function generateImageResult(array $prompt): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Prepares the given prompt and the model configuration into parameters for the API request.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt The prompt to generate an image for. Either a single message or a list of messages
         *                              from a chat. However as of today, OpenAI compatible image generation endpoints only
         *                              support a single user message.
         * @return ImageGenerationParams The parameters for the API request.
         */
        protected function prepareGenerateImageParams(array $prompt): array
        {
        }
        /**
         * Prepares the prompt parameter for the API request.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $messages The messages to prepare. However as of today, OpenAI compatible image generation
         *                                endpoints only support a single user message.
         * @return string The prepared prompt parameter.
         */
        protected function preparePromptParam(array $messages): string
        {
        }
        /**
         * Prepares the size parameter for the API request.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Files\Enums\MediaOrientationEnum|null $orientation The desired media orientation.
         * @param string|null $aspectRatio The desired media aspect ratio.
         * @return string The prepared size parameter.
         */
        protected function prepareSizeParam(?\WordPress\AiClient\Files\Enums\MediaOrientationEnum $orientation, ?string $aspectRatio): string
        {
        }
        /**
         * Creates a request object for the provider's API.
         *
         * Implementations should use $this->getRequestOptions() to attach any
         * configured request options to the Request.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method The HTTP method.
         * @param string $path The API endpoint path, relative to the base URI.
         * @param array<string, string|list<string>> $headers The request headers.
         * @param string|array<string, mixed>|null $data The request data.
         * @return \WordPress\AiClient\Providers\Http\DTO\Request The request object.
         */
        abstract protected function createRequest(\WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method, string $path, array $headers = [], $data = null): \WordPress\AiClient\Providers\Http\DTO\Request;
        /**
         * Throws an exception if the response is not successful.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP response to check.
         * @throws \WordPress\AiClient\Providers\Http\Exception\ResponseException If the response is not successful.
         */
        protected function throwIfNotSuccessful(\WordPress\AiClient\Providers\Http\DTO\Response $response): void
        {
        }
        /**
         * Parses the response from the API endpoint to a generative AI result.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The response from the API endpoint.
         * @param string   $expectedMimeType The expected MIME type the response is in.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The parsed generative AI result.
         */
        protected function parseResponseToGenerativeAiResult(\WordPress\AiClient\Providers\Http\DTO\Response $response, string $expectedMimeType = 'image/png'): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Parses a single choice from the API response into a Candidate object.
         *
         * @since 0.1.0
         *
         * @param ChoiceData $choiceData The choice data from the API response.
         * @param int $index The index of the choice in the choices array.
         * @param string   $expectedMimeType The expected MIME type the response is in.
         * @return \WordPress\AiClient\Results\DTO\Candidate The parsed candidate.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If the choice data is invalid.
         */
        protected function parseResponseChoiceToCandidate(array $choiceData, int $index, string $expectedMimeType = 'image/png'): \WordPress\AiClient\Results\DTO\Candidate
        {
        }
        /**
         * Extracts the result ID from the API response data.
         *
         * @since 0.4.0
         *
         * @param array<string, mixed> $responseData The response data from the API.
         * @return string The result ID.
         */
        protected function getResultId(array $responseData): string
        {
        }
    }
    /**
     * Base class for a text generation model for providers that implement OpenAI's API format.
     *
     * This abstract class is designed to work with any AI provider that offers an OpenAI-compatible
     * API endpoint, including but not limited to Anthropic, Google, and other providers
     * that have adopted OpenAI's API specification as a standard interface.
     *
     * @since 0.1.0
     *
     * @phpstan-type ToolCallData array{
     *     type?: string,
     *     id?: string,
     *     function?: array{
     *         name?: string,
     *         arguments: string|array<string, mixed>
     *     }
     * }
     * @phpstan-type MessageData array{
     *     role?: string,
     *     reasoning_content?: string,
     *     content?: string,
     *     tool_calls?: list<ToolCallData>
     * }
     * @phpstan-type ChoiceData array{
     *     message?: MessageData,
     *     finish_reason?: string
     * }
     * @phpstan-type UsageData array{
     *     prompt_tokens?: int,
     *     completion_tokens?: int,
     *     total_tokens?: int
     * }
     * @phpstan-type ResponseData array{
     *     id?: string,
     *     choices?: list<ChoiceData>,
     *     usage?: UsageData
     * }
     */
    abstract class AbstractOpenAiCompatibleTextGenerationModel extends \WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel implements \WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface
    {
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public function generateTextResult(array $prompt): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Prepares the given prompt and the model configuration into parameters for the API request.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt The prompt to generate text for. Either a single message or a list of messages
         *                              from a chat.
         * @return array<string, mixed> The parameters for the API request.
         */
        protected function prepareGenerateTextParams(array $prompt): array
        {
        }
        /**
         * Prepares the messages parameter for the API request.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $messages The messages to prepare.
         * @param string|null $systemInstruction An optional system instruction to prepend to the messages.
         * @return list<array<string, mixed>> The prepared messages parameter.
         */
        protected function prepareMessagesParam(array $messages, ?string $systemInstruction = null): array
        {
        }
        /**
         * Returns the OpenAI API specific role string for the given message role.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Messages\Enums\MessageRoleEnum $role The message role.
         * @return string The role for the API request.
         */
        protected function getMessageRoleString(\WordPress\AiClient\Messages\Enums\MessageRoleEnum $role): string
        {
        }
        /**
         * Returns the OpenAI API specific content data for a message part.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Messages\DTO\MessagePart $part The message part to get the data for.
         * @return ?array<string, mixed> The data for the message content part, or null if not applicable.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the message part type or data is unsupported.
         */
        protected function getMessagePartContentData(\WordPress\AiClient\Messages\DTO\MessagePart $part): ?array
        {
        }
        /**
         * Returns the OpenAI API specific tool calls data for a message part.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Messages\DTO\MessagePart $part The message part to get the data for.
         * @return ?array<string, mixed> The data for the message tool call part, or null if not applicable.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the message part type or data is unsupported.
         */
        protected function getMessagePartToolCallData(\WordPress\AiClient\Messages\DTO\MessagePart $part): ?array
        {
        }
        /**
         * Validates that the given output modalities to ensure that at least one output modality is text.
         *
         * @since 0.1.0
         *
         * @param array<\WordPress\AiClient\Messages\Enums\ModalityEnum> $outputModalities The output modalities to validate.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If no text output modality is present.
         */
        protected function validateOutputModalities(array $outputModalities): void
        {
        }
        /**
         * Prepares the output modalities parameter for the API request.
         *
         * @since 0.1.0
         *
         * @param array<\WordPress\AiClient\Messages\Enums\ModalityEnum> $modalities The modalities to prepare.
         * @return list<string> The prepared modalities parameter.
         */
        protected function prepareOutputModalitiesParam(array $modalities): array
        {
        }
        /**
         * Prepares the tools parameter for the API request.
         *
         * @since 0.1.0
         *
         * @param list<\WordPress\AiClient\Tools\DTO\FunctionDeclaration> $functionDeclarations The function declarations.
         * @return list<array<string, mixed>> The prepared tools parameter.
         */
        protected function prepareToolsParam(array $functionDeclarations): array
        {
        }
        /**
         * Prepares the response format parameter for the API request.
         *
         * This is only called if the output MIME type is `application/json`.
         *
         * @since 0.1.0
         *
         * @param array<string, mixed>|null $outputSchema The output schema.
         * @return array<string, mixed> The prepared response format parameter.
         */
        protected function prepareResponseFormatParam(?array $outputSchema): array
        {
        }
        /**
         * Creates a request object for the provider's API.
         *
         * Implementations should use $this->getRequestOptions() to attach any
         * configured request options to the Request.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method The HTTP method.
         * @param string $path The API endpoint path, relative to the base URI.
         * @param array<string, string|list<string>> $headers The request headers.
         * @param string|array<string, mixed>|null $data The request data.
         * @return \WordPress\AiClient\Providers\Http\DTO\Request The request object.
         */
        abstract protected function createRequest(\WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method, string $path, array $headers = [], $data = null): \WordPress\AiClient\Providers\Http\DTO\Request;
        /**
         * Throws an exception if the response is not successful.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP response to check.
         * @throws \WordPress\AiClient\Providers\Http\Exception\ResponseException If the response is not successful.
         */
        protected function throwIfNotSuccessful(\WordPress\AiClient\Providers\Http\DTO\Response $response): void
        {
        }
        /**
         * Parses the response from the API endpoint to a generative AI result.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The response from the API endpoint.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The parsed generative AI result.
         */
        protected function parseResponseToGenerativeAiResult(\WordPress\AiClient\Providers\Http\DTO\Response $response): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Parses a single choice from the API response into a Candidate object.
         *
         * @since 0.1.0
         *
         * @param ChoiceData $choiceData The choice data from the API response.
         * @param int $index The index of the choice in the choices array.
         * @return \WordPress\AiClient\Results\DTO\Candidate The parsed candidate.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If the choice data is invalid.
         */
        protected function parseResponseChoiceToCandidate(array $choiceData, int $index): \WordPress\AiClient\Results\DTO\Candidate
        {
        }
        /**
         * Parses the message from a choice in the API response.
         *
         * @since 0.1.0
         *
         * @param MessageData $messageData The message data from the API response.
         * @param int $index The index of the choice in the choices array.
         * @return \WordPress\AiClient\Messages\DTO\Message The parsed message.
         */
        protected function parseResponseChoiceMessage(array $messageData, int $index): \WordPress\AiClient\Messages\DTO\Message
        {
        }
        /**
         * Parses the message parts from a choice in the API response.
         *
         * @since 0.1.0
         *
         * @param MessageData $messageData The message data from the API response.
         * @param int $index The index of the choice in the choices array.
         * @return \WordPress\AiClient\Messages\DTO\MessagePart[] The parsed message parts.
         */
        protected function parseResponseChoiceMessageParts(array $messageData, int $index): array
        {
        }
        /**
         * Parses a tool call part from the API response.
         *
         * @since 0.1.0
         *
         * @param ToolCallData $toolCallData The tool call data from the API response.
         * @return \WordPress\AiClient\Messages\DTO\MessagePart|null The parsed message part for the tool call, or null if not applicable.
         */
        protected function parseResponseChoiceMessageToolCallPart(array $toolCallData): ?\WordPress\AiClient\Messages\DTO\MessagePart
        {
        }
    }
}
namespace WordPress\AiClient\Common\Contracts {
    /**
     * Interface for objects that cache data.
     *
     * @since 0.4.0
     */
    interface CachesDataInterface
    {
        /**
         * Invalidates all caches managed by this object.
         *
         * @since 0.4.0
         *
         * @return void
         */
        public function invalidateCaches(): void;
    }
}
namespace WordPress\AiClient\Common\Traits {
    /**
     * Trait for objects that cache data using PSR-16 cache with in-memory fallback.
     *
     * When a PSR-16 cache is configured via AiClient::setCache(), data is stored persistently.
     * Otherwise, data is cached in-memory for the duration of the request.
     *
     * @since 0.4.0
     */
    trait WithDataCachingTrait
    {
        /**
         * In-memory cache used when no PSR-16 cache is configured.
         *
         * @since 0.4.0
         *
         * @var array<string, mixed>
         */
        private array $localCache = [];
        /**
         * Gets the cache key suffixes managed by this object.
         *
         * @since 0.4.0
         *
         * @return list<string> The cache key suffixes.
         */
        abstract protected function getCachedKeys(): array;
        /**
         * Gets the base cache key for this object.
         *
         * The base cache key is used as a prefix for all cache keys managed by this object.
         * It should be unique to the implementing class to avoid cache key collisions.
         *
         * @since 0.4.0
         *
         * @return string The base cache key.
         */
        abstract protected function getBaseCacheKey(): string;
        /**
         * Checks if a value exists in the cache.
         *
         * @since 0.4.0
         *
         * @param string $key The cache key suffix (will be appended to the base key).
         * @return bool True if the value exists in cache, false otherwise.
         */
        protected function hasCache(string $key): bool
        {
        }
        /**
         * Gets a value from the cache, or computes and caches it if not present.
         *
         * @since 0.4.0
         *
         * @param string                 $key      The cache key suffix (will be appended to the base key).
         * @param callable               $callback The callback to compute the value if not cached.
         * @param int|\DateInterval|null $ttl      The TTL for the cache entry, or null for default.
         *                                         Ignored for local cache.
         * @return mixed The cached or computed value.
         */
        protected function cached(string $key, callable $callback, $ttl = null)
        {
        }
        /**
         * Gets a value from the cache.
         *
         * @since 0.4.0
         *
         * @param string $key     The cache key suffix (will be appended to the base key).
         * @param mixed  $default The default value to return if the key does not exist.
         * @return mixed The cached value or the default value if not found.
         */
        protected function getCache(string $key, $default = null)
        {
        }
        /**
         * Sets a value in the cache.
         *
         * @since 0.4.0
         *
         * @param string                $key   The cache key suffix (will be appended to the base key).
         * @param mixed                 $value The value to cache.
         * @param int|\DateInterval|null $ttl   The TTL for the cache entry, or null for default. Ignored for local cache.
         * @return bool True on success, false on failure.
         */
        protected function setCache(string $key, $value, $ttl = null): bool
        {
        }
        /**
         * Invalidates all caches managed by this object.
         *
         * @since 0.4.0
         *
         * @return void
         */
        public function invalidateCaches(): void
        {
        }
        /**
         * Clears a value from the cache.
         *
         * @since 0.4.0
         *
         * @param string $key The cache key suffix (will be appended to the base key).
         * @return bool True on success, false on failure.
         */
        protected function clearCache(string $key): bool
        {
        }
        /**
         * Builds the full cache key by combining the base key with the suffix.
         *
         * @since 0.4.0
         *
         * @param string $key The cache key suffix.
         * @return string The full cache key.
         */
        private function buildCacheKey(string $key): string
        {
        }
    }
}
namespace WordPress\AiClient\Providers\ApiBasedImplementation {
    /**
     * Base class for an API-based model metadata directory for a provider.
     *
     * @since 0.1.0
     */
    abstract class AbstractApiBasedModelMetadataDirectory implements \WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface, \WordPress\AiClient\Providers\Http\Contracts\WithHttpTransporterInterface, \WordPress\AiClient\Providers\Http\Contracts\WithRequestAuthenticationInterface, \WordPress\AiClient\Common\Contracts\CachesDataInterface
    {
        use \WordPress\AiClient\Providers\Http\Traits\WithHttpTransporterTrait;
        use \WordPress\AiClient\Providers\Http\Traits\WithRequestAuthenticationTrait;
        use \WordPress\AiClient\Common\Traits\WithDataCachingTrait;
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public function listModelMetadata(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public function hasModelMetadata(string $modelId): bool
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public function getModelMetadata(string $modelId): \WordPress\AiClient\Providers\Models\DTO\ModelMetadata
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.4.0
         */
        protected function getCachedKeys(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.4.0
         */
        protected function getBaseCacheKey(): string
        {
        }
        /**
         * Sends the API request to list models from the provider and returns the map of model ID to model metadata.
         *
         * @since 0.1.0
         *
         * @return array<string, \WordPress\AiClient\Providers\Models\DTO\ModelMetadata> Map of model ID to model metadata.
         */
        abstract protected function sendListModelsRequest(): array;
    }
}
namespace WordPress\AiClient\Providers\OpenAiCompatibleImplementation {
    /**
     * Base class for a model metadata directory for providers that implement OpenAI's API format.
     *
     * This abstract class is designed to work with any AI provider that offers an OpenAI-compatible
     * models listing endpoint, including but not limited to Anthropic, Google, and other
     * providers that have adopted OpenAI's models API specification as a standard interface.
     *
     * @since 0.1.0
     */
    abstract class AbstractOpenAiCompatibleModelMetadataDirectory extends \WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory
    {
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        protected function sendListModelsRequest(): array
        {
        }
        /**
         * Creates a request object for the provider's API.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method The HTTP method.
         * @param string $path The API endpoint path, relative to the base URI.
         * @param array<string, string|list<string>> $headers The request headers.
         * @param string|array<string, mixed>|null $data The request data.
         * @return \WordPress\AiClient\Providers\Http\DTO\Request The request object.
         */
        abstract protected function createRequest(\WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method, string $path, array $headers = [], $data = null): \WordPress\AiClient\Providers\Http\DTO\Request;
        /**
         * Throws an exception if the response is not successful.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP response to check.
         * @throws \WordPress\AiClient\Providers\Http\Exception\ResponseException If the response is not successful.
         */
        protected function throwIfNotSuccessful(\WordPress\AiClient\Providers\Http\DTO\Response $response): void
        {
        }
        /**
         * Parses the response from the API endpoint to list models into a list of model metadata objects.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The response from the API endpoint to list models.
         * @return list<\WordPress\AiClient\Providers\Models\DTO\ModelMetadata> List of model metadata objects.
         */
        abstract protected function parseResponseToModelMetadataList(\WordPress\AiClient\Providers\Http\DTO\Response $response): array;
    }
}
namespace WordPress\AiClient\Providers\Http\Contracts {
    /**
     * Interface for HTTP request authentication.
     *
     * @since 0.1.0
     */
    interface RequestAuthenticationInterface extends \WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface
    {
        /**
         * Authenticates an HTTP request.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Request $request The request to authenticate.
         * @return \WordPress\AiClient\Providers\Http\DTO\Request The authenticated request.
         */
        public function authenticateRequest(\WordPress\AiClient\Providers\Http\DTO\Request $request): \WordPress\AiClient\Providers\Http\DTO\Request;
    }
}
namespace WordPress\AiClient\Providers\Http\DTO {
    /**
     * Class for HTTP request authentication using an API key.
     *
     * @since 0.1.0
     *
     * @phpstan-type ApiKeyRequestAuthenticationArrayShape array{
     *     apiKey: string
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<ApiKeyRequestAuthenticationArrayShape>
     */
    class ApiKeyRequestAuthentication extends \WordPress\AiClient\Common\AbstractDataTransferObject implements \WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface
    {
        public const KEY_API_KEY = 'apiKey';
        /**
         * @var string The API key used for authentication.
         */
        protected string $apiKey;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string $apiKey The API key used for authentication.
         */
        public function __construct(string $apiKey)
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function authenticateRequest(\WordPress\AiClient\Providers\Http\DTO\Request $request): \WordPress\AiClient\Providers\Http\DTO\Request
        {
        }
        /**
         * Gets the API key.
         *
         * @since 0.1.0
         *
         * @return string The API key.
         */
        public function getApiKey(): string
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @since 0.1.0
         *
         * @return ApiKeyRequestAuthenticationArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
    }
    /**
     * Represents an HTTP response.
     *
     * This class encapsulates HTTP response data that has been converted
     * from PSR-7 responses by the HTTP transporter.
     *
     * @since 0.1.0
     *
     * @phpstan-type ResponseArrayShape array{
     *     statusCode: int,
     *     headers: array<string, list<string>>,
     *     body?: string|null
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<ResponseArrayShape>
     */
    class Response extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_STATUS_CODE = 'statusCode';
        public const KEY_HEADERS = 'headers';
        public const KEY_BODY = 'body';
        /**
         * @var int The HTTP status code.
         */
        protected int $statusCode;
        /**
         * @var \WordPress\AiClient\Providers\Http\Collections\HeadersCollection The response headers.
         */
        protected \WordPress\AiClient\Providers\Http\Collections\HeadersCollection $headers;
        /**
         * @var string|null The response body.
         */
        protected ?string $body;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param int $statusCode The HTTP status code.
         * @param array<string, string|list<string>> $headers The response headers.
         * @param string|null $body The response body.
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the status code is invalid.
         */
        public function __construct(int $statusCode, array $headers, ?string $body = null)
        {
        }
        /**
         * Creates a deep clone of this response.
         *
         * Clones the headers collection to ensure the cloned
         * response is independent of the original.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
        /**
         * Gets the HTTP status code.
         *
         * @since 0.1.0
         *
         * @return int The status code.
         */
        public function getStatusCode(): int
        {
        }
        /**
         * Gets the response headers.
         *
         * @since 0.1.0
         *
         * @return array<string, list<string>> The headers.
         */
        public function getHeaders(): array
        {
        }
        /**
         * Gets a specific header value.
         *
         * @since 0.1.0
         *
         * @param string $name The header name (case-insensitive).
         * @return list<string>|null The header value(s) or null if not found.
         */
        public function getHeader(string $name): ?array
        {
        }
        /**
         * Gets header values as a comma-separated string.
         *
         * @since 0.1.0
         *
         * @param string $name The header name (case-insensitive).
         * @return string|null The header values as a comma-separated string or null if not found.
         */
        public function getHeaderAsString(string $name): ?string
        {
        }
        /**
         * Gets the response body.
         *
         * @since 0.1.0
         *
         * @return string|null The body.
         */
        public function getBody(): ?string
        {
        }
        /**
         * Checks if the response has a header.
         *
         * @since 0.1.0
         *
         * @param string $name The header name.
         * @return bool True if the header exists, false otherwise.
         */
        public function hasHeader(string $name): bool
        {
        }
        /**
         * Checks if the response indicates success.
         *
         * @since 0.1.0
         *
         * @return bool True if status code is 2xx, false otherwise.
         */
        public function isSuccessful(): bool
        {
        }
        /**
         * Gets the response data as an array.
         *
         * Attempts to decode the body as JSON. Returns null if the body
         * is empty or not valid JSON.
         *
         * @since 0.1.0
         *
         * @return array<string, mixed>|null The decoded data or null.
         */
        public function getData(): ?array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return ResponseArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
    /**
     * Represents optional HTTP transport configuration for a single request.
     *
     * Provides mutable setters for working with timeouts and redirect handling.
     *
     * @since 0.2.0
     *
     * @phpstan-type RequestOptionsArrayShape array{
     *     timeout?: float|null,
     *     connectTimeout?: float|null,
     *     maxRedirects?: int|null
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<RequestOptionsArrayShape>
     */
    class RequestOptions extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_TIMEOUT = 'timeout';
        public const KEY_CONNECT_TIMEOUT = 'connectTimeout';
        public const KEY_MAX_REDIRECTS = 'maxRedirects';
        /**
         * @var float|null Maximum duration in seconds to wait for the full response.
         */
        protected ?float $timeout = null;
        /**
         * @var float|null Maximum duration in seconds to wait for the initial connection.
         */
        protected ?float $connectTimeout = null;
        /**
         * @var int|null Maximum number of redirects to follow. 0 disables redirects, null is unspecified.
         */
        protected ?int $maxRedirects = null;
        /**
         * Sets the request timeout in seconds.
         *
         * @since 0.2.0
         *
         * @param float|null $timeout Timeout in seconds.
         * @return void
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException When timeout is negative.
         */
        public function setTimeout(?float $timeout): void
        {
        }
        /**
         * Sets the connection timeout in seconds.
         *
         * @since 0.2.0
         *
         * @param float|null $timeout Connection timeout in seconds.
         * @return void
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException When timeout is negative.
         */
        public function setConnectTimeout(?float $timeout): void
        {
        }
        /**
         * Sets the maximum number of redirects to follow.
         *
         * Set to 0 to disable redirects, null for unspecified, or a positive integer
         * to enable redirects with a maximum count.
         *
         * @since 0.2.0
         *
         * @param int|null $maxRedirects Maximum redirects to follow, or 0 to disable, or null for unspecified.
         * @return void
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException When redirect count is negative.
         */
        public function setMaxRedirects(?int $maxRedirects): void
        {
        }
        /**
         * Gets the request timeout in seconds.
         *
         * @since 0.2.0
         *
         * @return float|null Timeout in seconds.
         */
        public function getTimeout(): ?float
        {
        }
        /**
         * Gets the connection timeout in seconds.
         *
         * @since 0.2.0
         *
         * @return float|null Connection timeout in seconds.
         */
        public function getConnectTimeout(): ?float
        {
        }
        /**
         * Checks whether redirects are allowed.
         *
         * @since 0.2.0
         *
         * @return bool|null True when redirects are allowed (maxRedirects > 0),
         *                   false when disabled (maxRedirects = 0),
         *                   null when unspecified (maxRedirects = null).
         */
        public function allowsRedirects(): ?bool
        {
        }
        /**
         * Gets the maximum number of redirects to follow.
         *
         * @since 0.2.0
         *
         * @return int|null Maximum redirects or null when not specified.
         */
        public function getMaxRedirects(): ?int
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.2.0
         *
         * @return RequestOptionsArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.2.0
         */
        public static function fromArray(array $array): self
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.2.0
         */
        public static function getJsonSchema(): array
        {
        }
    }
    /**
     * Represents an HTTP request.
     *
     * This class encapsulates HTTP request data that can be converted
     * to PSR-7 requests by the HTTP transporter.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type RequestOptionsArrayShape from RequestOptions
     * @phpstan-type RequestArrayShape array{
     *     method: string,
     *     uri: string,
     *     headers: array<string, list<string>>,
     *     body?: string|null,
     *     options?: RequestOptionsArrayShape
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<RequestArrayShape>
     */
    class Request extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_METHOD = 'method';
        public const KEY_URI = 'uri';
        public const KEY_HEADERS = 'headers';
        public const KEY_BODY = 'body';
        public const KEY_OPTIONS = 'options';
        /**
         * @var \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum The HTTP method.
         */
        protected \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method;
        /**
         * @var string The request URI.
         */
        protected string $uri;
        /**
         * @var \WordPress\AiClient\Providers\Http\Collections\HeadersCollection The request headers.
         */
        protected \WordPress\AiClient\Providers\Http\Collections\HeadersCollection $headers;
        /**
         * @var array<string, mixed>|null The request data (for query params or form data).
         */
        protected ?array $data = null;
        /**
         * @var string|null The request body (raw string content).
         */
        protected ?string $body = null;
        /**
         * @var RequestOptions|null Request transport options.
         */
        protected ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions $options = null;
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method The HTTP method.
         * @param string $uri The request URI.
         * @param array<string, string|list<string>> $headers The request headers.
         * @param string|array<string, mixed>|null $data The request data.
         * @param RequestOptions|null $options The request transport options.
         *
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the URI is empty.
         */
        public function __construct(\WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method, string $uri, array $headers = [], $data = null, ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions $options = null)
        {
        }
        /**
         * Creates a deep clone of this request.
         *
         * Clones the headers collection and request options to ensure
         * the cloned request is independent of the original.
         * The HTTP method enum is immutable and can be safely shared.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
        /**
         * Gets the HTTP method.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum The HTTP method.
         */
        public function getMethod(): \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum
        {
        }
        /**
         * Gets the request URI.
         *
         * For GET requests with array data, appends the data as query parameters.
         *
         * @since 0.1.0
         *
         * @return string The URI.
         */
        public function getUri(): string
        {
        }
        /**
         * Gets the request headers.
         *
         * @since 0.1.0
         *
         * @return array<string, list<string>> The headers.
         */
        public function getHeaders(): array
        {
        }
        /**
         * Gets a specific header value.
         *
         * @since 0.1.0
         *
         * @param string $name The header name (case-insensitive).
         * @return list<string>|null The header value(s) or null if not found.
         */
        public function getHeader(string $name): ?array
        {
        }
        /**
         * Gets header values as a comma-separated string.
         *
         * @since 0.1.0
         *
         * @param string $name The header name (case-insensitive).
         * @return string|null The header values as a comma-separated string, or null if not found.
         */
        public function getHeaderAsString(string $name): ?string
        {
        }
        /**
         * Checks if a header exists.
         *
         * @since 0.1.0
         *
         * @param string $name The header name (case-insensitive).
         * @return bool True if the header exists, false otherwise.
         */
        public function hasHeader(string $name): bool
        {
        }
        /**
         * Gets the request body.
         *
         * For GET requests, returns null.
         * For POST/PUT/PATCH requests:
         * - If body is set, returns it as-is
         * - If data is set and Content-Type is JSON, returns JSON-encoded data
         * - If data is set and Content-Type is form, returns URL-encoded data
         *
         * @since 0.1.0
         *
         * @return string|null The body.
         * @throws \JsonException If the data cannot be encoded to JSON.
         */
        public function getBody(): ?string
        {
        }
        /**
         * Returns a new instance with the specified header.
         *
         * @since 0.1.0
         *
         * @param string $name The header name.
         * @param string|list<string> $value The header value(s).
         * @return self A new instance with the header.
         */
        public function withHeader(string $name, $value): self
        {
        }
        /**
         * Returns a new instance with the specified data.
         *
         * @since 0.1.0
         *
         * @param string|array<string, mixed> $data The request data.
         * @return self A new instance with the data.
         */
        public function withData($data): self
        {
        }
        /**
         * Gets the request data array.
         *
         * @since 0.1.0
         *
         * @return array<string, mixed>|null The request data array.
         */
        public function getData(): ?array
        {
        }
        /**
         * Gets the request options.
         *
         * @since 0.2.0
         *
         * @return RequestOptions|null Request transport options when configured.
         */
        public function getOptions(): ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions
        {
        }
        /**
         * Returns a new instance with the specified request options.
         *
         * @since 0.2.0
         *
         * @param RequestOptions|null $options The request options to apply.
         * @return self A new instance with the options.
         */
        public function withOptions(?\WordPress\AiClient\Providers\Http\DTO\RequestOptions $options): self
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return RequestArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
        /**
         * Creates a Request instance from a PSR-7 RequestInterface.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $psrRequest The PSR-7 request to convert.
         * @return self A new Request instance.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the HTTP method is not supported.
         */
        public static function fromPsrRequest(\WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $psrRequest): self
        {
        }
    }
}
namespace WordPress\AiClient\Providers\Http\Util {
    /**
     * Class with static utility methods to process HTTP responses.
     *
     * @since 0.1.0
     */
    class ResponseUtil
    {
        /**
         * Throws an appropriate exception if the given response is not successful.
         *
         * This method checks the HTTP status code of the response and throws
         * the appropriate exception type based on the status code range:
         * - 3xx: RedirectException (redirect responses)
         * - 4xx: ClientException (client errors)
         * - 5xx: ServerException (server errors)
         * - Other unsuccessful responses: RuntimeException (invalid status codes)
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP response to check.
         * @throws \WordPress\AiClient\Providers\Http\Exception\RedirectException If the response indicates a redirect (3xx).
         * @throws \WordPress\AiClient\Providers\Http\Exception\ClientException If the response indicates a client error (4xx).
         * @throws \WordPress\AiClient\Providers\Http\Exception\ServerException If the response indicates a server error (5xx).
         * @throws \RuntimeException If the response has an invalid status code.
         */
        public static function throwIfNotSuccessful(\WordPress\AiClient\Providers\Http\DTO\Response $response): void
        {
        }
    }
    /**
     * Utility for extracting error messages from API response data.
     *
     * Centralizes the logic for parsing common API error response formats
     * to avoid code duplication across exception classes.
     *
     * @since 0.2.0
     * @since 0.4.0 Moved from Utilities namespace to Util namespace.
     */
    class ErrorMessageExtractor
    {
        /**
         * Extracts error message from API response data.
         *
         * Handles common error response formats:
         * - { "error": { "message": "Error text" } }
         * - { "error": "Error text" }
         * - { "message": "Error text" }
         *
         * @since 0.2.0
         *
         * @param mixed $data The response data to extract error message from.
         * @return string|null The extracted error message, or null if none found.
         */
        public static function extractFromResponseData($data): ?string
        {
        }
    }
}
namespace WordPress\AiClient\Providers\Http\Contracts {
    /**
     * Interface for HTTP transport implementations.
     *
     * Handles sending HTTP requests and receiving responses using
     * PSR-7, PSR-17, and PSR-18 standards internally.
     *
     * @since 0.1.0
     */
    interface HttpTransporterInterface
    {
        /**
         * Sends an HTTP request and returns the response.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Request $request The request to send.
         * @param \WordPress\AiClient\Providers\Http\DTO\RequestOptions|null $options Optional transport options for the request.
         * @return \WordPress\AiClient\Providers\Http\DTO\Response The response received.
         */
        public function send(\WordPress\AiClient\Providers\Http\DTO\Request $request, ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions $options = null): \WordPress\AiClient\Providers\Http\DTO\Response;
    }
    /**
     * Interface for HTTP clients that support per-request transport options.
     *
     * Extends the capabilities of PSR-18 clients by allowing custom transport
     * configuration such as timeouts and redirect handling on each request.
     *
     * @since 0.2.0
     */
    interface ClientWithOptionsInterface
    {
        /**
         * Sends an HTTP request with the given transport options.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $request The PSR-7 request to send.
         * @param \WordPress\AiClient\Providers\Http\DTO\RequestOptions $options The request transport options. Must not be null.
         * @return \WordPress\AiClientDependencies\Psr\Http\Message\ResponseInterface The PSR-7 response received.
         */
        public function sendRequestWithOptions(\WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $request, \WordPress\AiClient\Providers\Http\DTO\RequestOptions $options): \WordPress\AiClientDependencies\Psr\Http\Message\ResponseInterface;
    }
}
namespace WordPress\AiClient\Providers\Http\Enums {
    /**
     * Represents HTTP request methods.
     *
     * @since 0.1.0
     *
     * @method static self GET()
     * @method static self POST()
     * @method static self PUT()
     * @method static self PATCH()
     * @method static self DELETE()
     * @method static self HEAD()
     * @method static self OPTIONS()
     * @method static self CONNECT()
     * @method static self TRACE()
     *
     * @method bool isGet()
     * @method bool isPost()
     * @method bool isPut()
     * @method bool isPatch()
     * @method bool isDelete()
     * @method bool isHead()
     * @method bool isOptions()
     * @method bool isConnect()
     * @method bool isTrace()
     */
    final class HttpMethodEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * GET method for retrieving resources.
         *
         * @var string
         */
        public const GET = 'GET';
        /**
         * POST method for creating resources.
         *
         * @var string
         */
        public const POST = 'POST';
        /**
         * PUT method for updating/replacing resources.
         *
         * @var string
         */
        public const PUT = 'PUT';
        /**
         * PATCH method for partially updating resources.
         *
         * @var string
         */
        public const PATCH = 'PATCH';
        /**
         * DELETE method for removing resources.
         *
         * @var string
         */
        public const DELETE = 'DELETE';
        /**
         * HEAD method for retrieving headers only.
         *
         * @var string
         */
        public const HEAD = 'HEAD';
        /**
         * OPTIONS method for retrieving allowed methods.
         *
         * @var string
         */
        public const OPTIONS = 'OPTIONS';
        /**
         * CONNECT method for establishing tunnel.
         *
         * @var string
         */
        public const CONNECT = 'CONNECT';
        /**
         * TRACE method for diagnostic purposes.
         *
         * @var string
         */
        public const TRACE = 'TRACE';
        /**
         * Checks if this method is idempotent.
         *
         * @since 0.1.0
         *
         * @return bool True if the method is idempotent, false otherwise.
         */
        public function isIdempotent(): bool
        {
        }
        /**
         * Checks if this method typically has a request body.
         *
         * @since 0.1.0
         *
         * @return bool True if the method typically has a body, false otherwise.
         */
        public function hasBody(): bool
        {
        }
    }
    /**
     * Enum for request authentication methods.
     *
     * @since 0.4.0
     *
     * @method static self apiKey() Creates an instance for API_KEY method.
     * @method bool isApiKey() Checks if the method is API_KEY.
     */
    class RequestAuthenticationMethod extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * API key authentication.
         */
        public const API_KEY = 'api_key';
        /**
         * Gets the implementation class for the authentication method.
         *
         * @since 0.4.0
         *
         * @return class-string<\WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface&\WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface> The implementation class.
         *
         * @phpstan-ignore missingType.generics
         */
        public function getImplementationClass(): string
        {
        }
    }
}
namespace WordPress\AiClient\Providers\Http\Abstracts {
    /**
     * Abstract discovery strategy for HTTP client implementations.
     *
     * Provides a base for registering custom HTTP client implementations
     * with HTTPlug's discovery mechanism. Subclasses must implement
     * the createClient() method to provide their specific PSR-18
     * HTTP client instance using the provided Psr17Factory.
     *
     * @since 1.1.0
     */
    abstract class AbstractClientDiscoveryStrategy implements \WordPress\AiClientDependencies\Http\Discovery\Strategy\DiscoveryStrategy
    {
        /**
         * Initializes and registers the discovery strategy.
         *
         * @since 1.1.0
         *
         * @return void
         */
        public static function init(): void
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 1.1.0
         *
         * @param string $type The type of discovery.
         * @return array<array<string, mixed>> The discovery candidates.
         */
        public static function getCandidates($type)
        {
        }
        /**
         * Creates an instance of the HTTP client.
         *
         * Subclasses must implement this method to return their specific
         * PSR-18 HTTP client instance. The provided Psr17Factory implements
         * all PSR-17 interfaces (RequestFactory, ResponseFactory, StreamFactory,
         * etc.) and can be used to satisfy client constructor dependencies.
         *
         * @since 1.1.0
         *
         * @param \WordPress\AiClientDependencies\Nyholm\Psr7\Factory\Psr17Factory $psr17Factory The PSR-17 factory for creating HTTP messages.
         * @return \WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface The PSR-18 HTTP client.
         */
        abstract protected static function createClient(\WordPress\AiClientDependencies\Nyholm\Psr7\Factory\Psr17Factory $psr17Factory): \WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface;
    }
}
namespace WordPress\AiClient\Providers\Http {
    /**
     * Factory for creating HTTP transporters.
     *
     * Uses HTTPlug's Discovery component to automatically find
     * available HTTP clients and factories.
     *
     * @since 0.1.0
     */
    class HttpTransporterFactory
    {
        /**
         * Creates an HTTP transporter.
         *
         * Uses HTTPlug Discovery to automatically find PSR-18 client
         * and PSR-17 factories if not provided.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface The HTTP transporter.
         */
        public static function createTransporter(): \WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface
        {
        }
    }
}
namespace WordPress\AiClient\Providers\Http\Collections {
    /**
     * Simple collection for managing HTTP headers with case-insensitive access.
     *
     * This class stores HTTP headers while preserving their original casing
     * and provides efficient case-insensitive lookups.
     *
     * @since 0.1.0
     */
    class HeadersCollection
    {
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param array<string, string|list<string>> $headers Initial headers.
         */
        public function __construct(array $headers = [])
        {
        }
        /**
         * Gets a specific header value.
         *
         * @since 0.1.0
         *
         * @param string $name The header name (case-insensitive).
         * @return list<string>|null The header value(s) or null if not found.
         */
        public function get(string $name): ?array
        {
        }
        /**
         * Gets all headers.
         *
         * @since 0.1.0
         *
         * @return array<string, list<string>> All headers with their original casing.
         */
        public function getAll(): array
        {
        }
        /**
         * Gets header values as a comma-separated string.
         *
         * @since 0.1.0
         *
         * @param string $name The header name (case-insensitive).
         * @return string|null The header values as a comma-separated string or null if not found.
         */
        public function getAsString(string $name): ?string
        {
        }
        /**
         * Checks if a header exists.
         *
         * @since 0.1.0
         *
         * @param string $name The header name (case-insensitive).
         * @return bool True if the header exists, false otherwise.
         */
        public function has(string $name): bool
        {
        }
        /**
         * Returns a new instance with the specified header.
         *
         * @since 0.1.0
         *
         * @param string $name The header name.
         * @param string|list<string> $value The header value(s).
         * @return self A new instance with the header.
         */
        public function withHeader(string $name, $value): self
        {
        }
    }
}
namespace WordPress\AiClient\Common\Contracts {
    /**
     * Base interface for all AI Client exceptions.
     *
     * This interface allows callers to catch all AI Client specific exceptions
     * with a single catch statement.
     *
     * @since 0.2.0
     */
    interface AiClientExceptionInterface extends \Throwable
    {
    }
}
namespace WordPress\AiClient\Common\Exception {
    /**
     * Exception thrown for runtime errors.
     *
     * This extends PHP's built-in RuntimeException while implementing
     * the AI Client exception interface for consistent catch handling.
     *
     * @since 0.2.0
     */
    class RuntimeException extends \RuntimeException implements \WordPress\AiClient\Common\Contracts\AiClientExceptionInterface
    {
    }
}
namespace WordPress\AiClient\Providers\Http\Exception {
    /**
     * Exception thrown for 3xx HTTP redirect responses.
     *
     * This represents cases where the server indicates that the request
     * should be retried at a different location, but automatic redirect
     * handling was not successful or not enabled.
     *
     * @since 0.2.0
     */
    class RedirectException extends \WordPress\AiClient\Common\Exception\RuntimeException
    {
        /**
         * Creates a RedirectException from a redirect response.
         *
         * This method extracts redirect information from the response headers
         * and creates an exception with a descriptive message and status code.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP redirect response.
         * @return self
         */
        public static function fromRedirectResponse(\WordPress\AiClient\Providers\Http\DTO\Response $response): self
        {
        }
    }
    /**
     * Exception thrown for 5xx HTTP server errors.
     *
     * This represents errors where the server failed to fulfill
     * a valid request due to internal server errors.
     *
     * @since 0.2.0
     */
    class ServerException extends \WordPress\AiClient\Common\Exception\RuntimeException
    {
        /**
         * Creates a ServerException from a server error response.
         *
         * This method extracts error details from common API response formats
         * and creates an exception with a descriptive message and status code.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP response that failed.
         * @return self
         */
        public static function fromServerErrorResponse(\WordPress\AiClient\Providers\Http\DTO\Response $response): self
        {
        }
    }
}
namespace WordPress\AiClient\Common\Exception {
    /**
     * Exception thrown when an invalid argument is provided.
     *
     * This extends PHP's built-in InvalidArgumentException while implementing
     * the AI Client exception interface for consistent catch handling.
     *
     * @since 0.2.0
     */
    class InvalidArgumentException extends \InvalidArgumentException implements \WordPress\AiClient\Common\Contracts\AiClientExceptionInterface
    {
    }
}
namespace WordPress\AiClient\Providers\Http\Exception {
    /**
     * Exception thrown for 4xx HTTP client errors.
     *
     * This represents errors where the client request was malformed,
     * unauthorized, forbidden, or otherwise invalid.
     *
     * @since 0.2.0
     */
    class ClientException extends \WordPress\AiClient\Common\Exception\InvalidArgumentException
    {
        /**
         * The request that failed.
         *
         * @var \WordPress\AiClient\Providers\Http\DTO\Request|null
         */
        protected ?\WordPress\AiClient\Providers\Http\DTO\Request $request = null;
        /**
         * Returns the request that failed as our Request DTO.
         *
         * @since 0.2.0
         *
         * @return \WordPress\AiClient\Providers\Http\DTO\Request
         * @throws \RuntimeException If no request is available
         */
        public function getRequest(): \WordPress\AiClient\Providers\Http\DTO\Request
        {
        }
        /**
         * Creates a ClientException from a client error response (4xx).
         *
         * This method extracts error details from common API response formats
         * and creates an exception with a descriptive message and status code.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response The HTTP response that failed.
         * @return self
         */
        public static function fromClientErrorResponse(\WordPress\AiClient\Providers\Http\DTO\Response $response): self
        {
        }
    }
    /**
     * Exception class for HTTP response errors.
     *
     * This is used when response data is unexpected or malformed,
     * typically indicating that a provider changed in ways our code
     * is not aware of or when parsing response data fails.
     *
     * @since 0.1.0
     */
    class ResponseException extends \WordPress\AiClient\Common\Exception\RuntimeException
    {
        /**
         * Creates a ResponseException for missing expected data.
         *
         * @since 0.2.0
         *
         * @param string $apiName The name of the API/provider.
         * @param string $fieldName The field that was expected but missing.
         * @return self
         */
        public static function fromMissingData(string $apiName, string $fieldName): self
        {
        }
        /**
         * Creates a ResponseException from invalid data in an API response.
         *
         * @since 0.2.0
         *
         * @param string $apiName The name of the API service (e.g., 'OpenAI', 'Anthropic').
         * @param string $fieldName The field that was invalid.
         * @param string $message The specific error message describing the invalid data.
         * @return self
         */
        public static function fromInvalidData(string $apiName, string $fieldName, string $message): self
        {
        }
    }
    /**
     * Exception thrown for network-related errors.
     *
     * This includes HTTP transport errors, connection failures,
     * timeouts, and other network-related issues.
     *
     * @since 0.2.0
     */
    class NetworkException extends \WordPress\AiClient\Common\Exception\RuntimeException
    {
        /**
         * The request that failed.
         *
         * @var \WordPress\AiClient\Providers\Http\DTO\Request|null
         */
        protected ?\WordPress\AiClient\Providers\Http\DTO\Request $request = null;
        /**
         * Returns the request that failed as our Request DTO.
         *
         * @since 0.2.0
         *
         * @return \WordPress\AiClient\Providers\Http\DTO\Request
         * @throws \RuntimeException If no request is available
         */
        public function getRequest(): \WordPress\AiClient\Providers\Http\DTO\Request
        {
        }
        /**
         * Creates a NetworkException from a PSR-18 network exception.
         *
         * @since 0.2.0
         *
         * @param \WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $psrRequest The PSR-7 request that failed.
         * @param \Throwable $networkException The PSR-18 network exception.
         * @return self
         */
        public static function fromPsr18NetworkException(\WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $psrRequest, \Throwable $networkException): self
        {
        }
    }
}
namespace WordPress\AiClient\Providers\Http {
    /**
     * HTTP transporter implementation using HTTPlug.
     *
     * This class handles the conversion between custom Request/Response
     * objects and PSR-7 messages, using HTTPlug for client abstraction
     * and PSR-17 factories for message creation.
     *
     * @since 0.1.0
     */
    class HttpTransporter implements \WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface
    {
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface|null $client PSR-18 HTTP client.
         * @param \WordPress\AiClientDependencies\Psr\Http\Message\RequestFactoryInterface|null $requestFactory PSR-17 request factory.
         * @param \WordPress\AiClientDependencies\Psr\Http\Message\StreamFactoryInterface|null $streamFactory PSR-17 stream factory.
         */
        public function __construct(?\WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface $client = null, ?\WordPress\AiClientDependencies\Psr\Http\Message\RequestFactoryInterface $requestFactory = null, ?\WordPress\AiClientDependencies\Psr\Http\Message\StreamFactoryInterface $streamFactory = null)
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         * @since 0.2.0 Added optional RequestOptions parameter and ClientWithOptions support.
         */
        public function send(\WordPress\AiClient\Providers\Http\DTO\Request $request, ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions $options = null): \WordPress\AiClient\Providers\Http\DTO\Response
        {
        }
    }
}
namespace WordPress\AiClient\Providers {
    /**
     * Registry for managing AI providers and their models.
     *
     * This class provides a centralized way to register AI providers, discover
     * their capabilities, and find suitable models based on requirements.
     *
     * @since 0.1.0
     */
    class ProviderRegistry implements \WordPress\AiClient\Providers\Http\Contracts\WithHttpTransporterInterface
    {
        use \WordPress\AiClient\Providers\Http\Traits\WithHttpTransporterTrait {
            setHttpTransporter as setHttpTransporterOriginal;
        }
        /**
         * Registers a provider class with the registry.
         *
         * @since 0.1.0
         *
         * @param class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> $className The fully qualified provider class name implementing the
         * ProviderInterface
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the class doesn't exist or implement the required interface.
         */
        public function registerProvider(string $className): void
        {
        }
        /**
         * Gets a list of all registered provider IDs.
         *
         * @since 0.1.0
         *
         * @return list<string> List of registered provider IDs.
         */
        public function getRegisteredProviderIds(): array
        {
        }
        /**
         * Checks if a provider is registered.
         *
         * @since 0.1.0
         *
         * @param string|class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> $idOrClassName The provider ID or class name to check.
         * @return bool True if the provider is registered.
         */
        public function hasProvider(string $idOrClassName): bool
        {
        }
        /**
         * Gets the class name for a registered provider.
         *
         * @since 0.1.0
         *
         * @param string|class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> $idOrClassName The provider ID or class name.
         * @return class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> The provider class name.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the provider is not registered.
         */
        public function getProviderClassName(string $idOrClassName): string
        {
        }
        /**
         * Gets the provider ID for a registered provider.
         *
         * @since 0.2.0
         *
         * @param string|class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> $idOrClassName The provider ID or class name.
         * @return string The provider ID.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the provider is not registered.
         */
        public function getProviderId(string $idOrClassName): string
        {
        }
        /**
         * Checks if a provider is properly configured.
         *
         * @since 0.1.0
         *
         * @param string|class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> $idOrClassName The provider ID or class name.
         * @return bool True if the provider is configured and ready to use.
         */
        public function isProviderConfigured(string $idOrClassName): bool
        {
        }
        /**
         * Finds models across all available providers that support the given requirements.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\DTO\ModelRequirements $modelRequirements The requirements to match against.
         * @return list<\WordPress\AiClient\Providers\DTO\ProviderModelsMetadata> List of provider models metadata that match requirements.
         */
        public function findModelsMetadataForSupport(\WordPress\AiClient\Providers\Models\DTO\ModelRequirements $modelRequirements): array
        {
        }
        /**
         * Finds models within a specific available provider that support the given requirements.
         *
         * @since 0.1.0
         *
         * @param string $idOrClassName The provider ID or class name.
         * @param \WordPress\AiClient\Providers\Models\DTO\ModelRequirements $modelRequirements The requirements to match against.
         * @return list<\WordPress\AiClient\Providers\Models\DTO\ModelMetadata> List of model metadata that match requirements.
         */
        public function findProviderModelsMetadataForSupport(string $idOrClassName, \WordPress\AiClient\Providers\Models\DTO\ModelRequirements $modelRequirements): array
        {
        }
        /**
         * Gets a configured model instance from a provider.
         *
         * @since 0.1.0
         *
         * @param string|class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> $idOrClassName The provider ID or class name.
         * @param string $modelId The model identifier.
         * @param \WordPress\AiClient\Providers\Models\DTO\ModelConfig|null $modelConfig The model configuration.
         * @return \WordPress\AiClient\Providers\Models\Contracts\ModelInterface The configured model instance.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If provider or model is not found.
         */
        public function getProviderModel(string $idOrClassName, string $modelId, ?\WordPress\AiClient\Providers\Models\DTO\ModelConfig $modelConfig = null): \WordPress\AiClient\Providers\Models\Contracts\ModelInterface
        {
        }
        /**
         * Binds dependencies to a model instance.
         *
         * This method injects required dependencies such as HTTP transporter
         * and authentication into model instances that need them.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface $modelInstance The model instance to bind dependencies to.
         * @return void
         */
        public function bindModelDependencies(\WordPress\AiClient\Providers\Models\Contracts\ModelInterface $modelInstance): void
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function setHttpTransporter(\WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface $httpTransporter): void
        {
        }
        /**
         * Sets the request authentication instance for the given provider.
         *
         * @since 0.1.0
         *
         * @param string|class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> $idOrClassName The provider ID or class name.
         * @param \WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface $requestAuthentication The request authentication instance.
         */
        public function setProviderRequestAuthentication(string $idOrClassName, \WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface $requestAuthentication): void
        {
        }
        /**
         * Gets the request authentication instance for the given provider, if set.
         *
         * @since 0.1.0
         *
         * @param string|class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> $idOrClassName The provider ID or class name.
         * @return ?\WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface The request authentication instance, or null if not set.
         */
        public function getProviderRequestAuthentication(string $idOrClassName): ?\WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface
        {
        }
    }
}
namespace WordPress\AiClient\Providers\ApiBasedImplementation {
    /**
     * Class to check availability for an API-based provider via a test request to the endpoint to generate text.
     *
     * This class should be used for cloud-based providers that do not offer a model listing endpoint, but do offer a
     * text generation endpoint which requires authentication. A minimal request to this endpoint is used to determine
     * if the provider is properly configured with valid credentials.
     *
     * @since 0.1.0
     */
    class GenerateTextApiBasedProviderAvailability implements \WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface
    {
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model The model to use for checking availability.
         */
        public function __construct(\WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model)
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function isConfigured(): bool
        {
        }
    }
}
namespace WordPress\AiClient\Providers {
    /**
     * Base class for a provider.
     *
     * @since 0.1.0
     */
    abstract class AbstractProvider implements \WordPress\AiClient\Providers\Contracts\ProviderInterface
    {
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public static function metadata(): \WordPress\AiClient\Providers\DTO\ProviderMetadata
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public static function model(string $modelId, ?\WordPress\AiClient\Providers\Models\DTO\ModelConfig $modelConfig = null): \WordPress\AiClient\Providers\Models\Contracts\ModelInterface
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public static function availability(): \WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        final public static function modelMetadataDirectory(): \WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface
        {
        }
        /**
         * Creates a model instance based on the given model metadata and provider metadata.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Models\DTO\ModelMetadata $modelMetadata The model metadata.
         * @param \WordPress\AiClient\Providers\DTO\ProviderMetadata $providerMetadata The provider metadata.
         * @return \WordPress\AiClient\Providers\Models\Contracts\ModelInterface The new model instance.
         */
        abstract protected static function createModel(\WordPress\AiClient\Providers\Models\DTO\ModelMetadata $modelMetadata, \WordPress\AiClient\Providers\DTO\ProviderMetadata $providerMetadata): \WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
        /**
         * Creates the provider metadata instance.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\DTO\ProviderMetadata The provider metadata.
         */
        abstract protected static function createProviderMetadata(): \WordPress\AiClient\Providers\DTO\ProviderMetadata;
        /**
         * Creates the provider availability instance.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface The provider availability.
         */
        abstract protected static function createProviderAvailability(): \WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
        /**
         * Creates the model metadata directory instance.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface The model metadata directory.
         */
        abstract protected static function createModelMetadataDirectory(): \WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
    }
}
namespace WordPress\AiClient\Providers\ApiBasedImplementation {
    /**
     * Base class for API-based providers.
     *
     * This abstract class provides URL construction utilities for providers that
     * communicate with REST APIs. It standardizes the pattern of combining a base
     * URL with endpoint paths.
     *
     * @since 0.2.0
     */
    abstract class AbstractApiProvider extends \WordPress\AiClient\Providers\AbstractProvider
    {
        /**
         * Gets the base URL for the provider's API.
         *
         * The base URL should include the protocol and domain, and may include
         * the API version path (e.g., "https://api.example.com/v1").
         *
         * @since 0.2.0
         *
         * @return string The base URL for the provider's API.
         */
        abstract protected static function baseUrl(): string;
        /**
         * Constructs a full URL by combining the base URL with an optional path.
         *
         * This method ensures proper URL construction by:
         * - Using the provider's base URL
         * - Trimming leading slashes from the path to prevent double-slashes
         * - Joining the base URL and path with a single forward slash
         *
         * @since 0.2.0
         *
         * @param string $path Optional path to append to the base URL. Default empty string.
         * @return string The complete URL.
         */
        public static function url(string $path = ''): string
        {
        }
    }
    /**
     * Class to check availability for an API-based provider via a test request to the endpoint to list models.
     *
     * This class should be used for cloud-based providers that offer a model listing endpoint which requires
     * authentication. A request to this endpoint is used to determine if the provider is properly configured
     * with valid credentials.
     *
     * @since 0.1.0
     */
    class ListModelsApiBasedProviderAvailability implements \WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface
    {
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface $modelMetadataDirectory The model metadata directory to use for checking
         *                                                                availability.
         */
        public function __construct(\WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface $modelMetadataDirectory)
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function isConfigured(): bool
        {
        }
    }
}
namespace WordPress\AiClient\Operations\Contracts {
    /**
     * Interface for AI operations.
     *
     * Operations represent long-running AI tasks that may not complete immediately.
     * They provide a way to track the progress and retrieve results asynchronously.
     *
     * @since 0.1.0
     */
    interface OperationInterface
    {
        /**
         * Gets the operation ID.
         *
         * @since 0.1.0
         *
         * @return string The unique operation identifier.
         */
        public function getId(): string;
        /**
         * Gets the current state of the operation.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Operations\Enums\OperationStateEnum The operation state.
         */
        public function getState(): \WordPress\AiClient\Operations\Enums\OperationStateEnum;
    }
}
namespace WordPress\AiClient\Operations\DTO {
    /**
     * Represents a long-running generative AI operation.
     *
     * This DTO tracks the progress of generative AI tasks that may not complete
     * immediately, providing access to the result once available.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type GenerativeAiResultArrayShape from \WordPress\AiClient\Results\DTO\GenerativeAiResult
     *
     * @phpstan-type GenerativeAiOperationArrayShape array{id: string, state: string, result?: GenerativeAiResultArrayShape}
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<GenerativeAiOperationArrayShape>
     */
    class GenerativeAiOperation extends \WordPress\AiClient\Common\AbstractDataTransferObject implements \WordPress\AiClient\Operations\Contracts\OperationInterface
    {
        public const KEY_ID = 'id';
        public const KEY_STATE = 'state';
        public const KEY_RESULT = 'result';
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string $id Unique identifier for this operation.
         * @param \WordPress\AiClient\Operations\Enums\OperationStateEnum $state The current state of the operation.
         * @param \WordPress\AiClient\Results\DTO\GenerativeAiResult|null $result The result once the operation completes.
         */
        public function __construct(string $id, \WordPress\AiClient\Operations\Enums\OperationStateEnum $state, ?\WordPress\AiClient\Results\DTO\GenerativeAiResult $result = null)
        {
        }
        /**
         * Creates a deep clone of this operation.
         *
         * Clones the result object if present to ensure the cloned
         * operation is independent of the original.
         * The state enum is immutable and can be safely shared.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function getId(): string
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function getState(): \WordPress\AiClient\Operations\Enums\OperationStateEnum
        {
        }
        /**
         * Gets the operation result.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult|null The result or null if not yet complete.
         */
        public function getResult(): ?\WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return GenerativeAiOperationArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
}
namespace WordPress\AiClient\Operations\Enums {
    /**
     * Enum for operation states.
     *
     * @since 0.1.0
     *
     * @method static self starting() Creates an instance for STARTING state.
     * @method static self processing() Creates an instance for PROCESSING state.
     * @method static self succeeded() Creates an instance for SUCCEEDED state.
     * @method static self failed() Creates an instance for FAILED state.
     * @method static self canceled() Creates an instance for CANCELED state.
     * @method bool isStarting() Checks if the state is STARTING.
     * @method bool isProcessing() Checks if the state is PROCESSING.
     * @method bool isSucceeded() Checks if the state is SUCCEEDED.
     * @method bool isFailed() Checks if the state is FAILED.
     * @method bool isCanceled() Checks if the state is CANCELED.
     */
    class OperationStateEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Operation is starting.
         */
        public const STARTING = 'starting';
        /**
         * Operation is processing.
         */
        public const PROCESSING = 'processing';
        /**
         * Operation succeeded.
         */
        public const SUCCEEDED = 'succeeded';
        /**
         * Operation failed.
         */
        public const FAILED = 'failed';
        /**
         * Operation was canceled.
         */
        public const CANCELED = 'canceled';
    }
}
namespace WordPress\AiClient\Results\DTO {
    /**
     * Represents a candidate response from an AI model.
     *
     * When generating content, AI models can produce multiple candidates.
     * Each candidate contains a message and metadata about why generation stopped.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type MessageArrayShape from \WordPress\AiClient\Messages\DTO\Message
     *
     * @phpstan-type CandidateArrayShape array{message: MessageArrayShape, finishReason: string}
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<CandidateArrayShape>
     */
    class Candidate extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_MESSAGE = 'message';
        public const KEY_FINISH_REASON = 'finishReason';
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param \WordPress\AiClient\Messages\DTO\Message $message The generated message.
         * @param \WordPress\AiClient\Results\Enums\FinishReasonEnum $finishReason The reason generation stopped.
         */
        public function __construct(\WordPress\AiClient\Messages\DTO\Message $message, \WordPress\AiClient\Results\Enums\FinishReasonEnum $finishReason)
        {
        }
        /**
         * Gets the generated message.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Messages\DTO\Message The message.
         */
        public function getMessage(): \WordPress\AiClient\Messages\DTO\Message
        {
        }
        /**
         * Gets the finish reason.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Results\Enums\FinishReasonEnum The finish reason.
         */
        public function getFinishReason(): \WordPress\AiClient\Results\Enums\FinishReasonEnum
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return CandidateArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
        /**
         * Performs a deep clone of the candidate.
         *
         * This method ensures that the message object is cloned to prevent
         * modifications to the cloned candidate from affecting the original.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
    }
    /**
     * Represents token usage statistics for an AI operation.
     *
     * This DTO tracks the number of tokens used in prompts and completions,
     * which is important for monitoring usage and costs.
     *
     * Note that thought tokens are a subset of completion tokens, not additive.
     * In other words: completionTokens - thoughtTokens = tokens of actual output content.
     *
     * @since 0.1.0
     *
     * @phpstan-type TokenUsageArrayShape array{
     *     promptTokens: int,
     *     completionTokens: int,
     *     totalTokens: int,
     *     thoughtTokens?: int
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<TokenUsageArrayShape>
     */
    class TokenUsage extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_PROMPT_TOKENS = 'promptTokens';
        public const KEY_COMPLETION_TOKENS = 'completionTokens';
        public const KEY_TOTAL_TOKENS = 'totalTokens';
        public const KEY_THOUGHT_TOKENS = 'thoughtTokens';
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param int $promptTokens Number of tokens in the prompt.
         * @param int $completionTokens Number of tokens in the completion, including any thought tokens.
         * @param int $totalTokens Total number of tokens used.
         * @param int|null $thoughtTokens Number of tokens used for thinking, as a subset of completion tokens.
         */
        public function __construct(int $promptTokens, int $completionTokens, int $totalTokens, ?int $thoughtTokens = null)
        {
        }
        /**
         * Gets the number of prompt tokens.
         *
         * @since 0.1.0
         *
         * @return int The prompt token count.
         */
        public function getPromptTokens(): int
        {
        }
        /**
         * Gets the number of completion tokens, including any thought tokens.
         *
         * @since 0.1.0
         *
         * @return int The completion token count.
         */
        public function getCompletionTokens(): int
        {
        }
        /**
         * Gets the total number of tokens.
         *
         * @since 0.1.0
         *
         * @return int The total token count.
         */
        public function getTotalTokens(): int
        {
        }
        /**
         * Gets the number of thought tokens, which is a subset of the completion token count.
         *
         * @since 1.3.0
         *
         * @return int|null The thought token count or null if not available.
         */
        public function getThoughtTokens(): ?int
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return TokenUsageArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
    }
}
namespace WordPress\AiClient\Results\Contracts {
    /**
     * Interface for AI operation results.
     *
     * Results contain the output from AI operations along with metadata
     * such as token usage and provider-specific information.
     *
     * @since 0.1.0
     */
    interface ResultInterface
    {
        /**
         * Gets the result ID.
         *
         * @since 0.1.0
         *
         * @return string The unique result identifier.
         */
        public function getId(): string;
        /**
         * Gets token usage information.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Results\DTO\TokenUsage Token usage statistics.
         */
        public function getTokenUsage(): \WordPress\AiClient\Results\DTO\TokenUsage;
        /**
         * Gets the provider metadata.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\DTO\ProviderMetadata The provider metadata.
         */
        public function getProviderMetadata(): \WordPress\AiClient\Providers\DTO\ProviderMetadata;
        /**
         * Gets the model metadata.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Models\DTO\ModelMetadata The model metadata.
         */
        public function getModelMetadata(): \WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
        /**
         * Gets provider-specific metadata.
         *
         * @since 0.1.0
         *
         * @return array<string, mixed> Provider metadata.
         */
        public function getAdditionalData(): array;
    }
}
namespace WordPress\AiClient\Results\DTO {
    /**
     * Represents the result of a generative AI operation.
     *
     * This DTO contains the generated candidates along with usage statistics
     * and metadata from the AI provider.
     *
     * @since 0.1.0
     *
     * @phpstan-import-type CandidateArrayShape from Candidate
     * @phpstan-import-type TokenUsageArrayShape from TokenUsage
     * @phpstan-import-type ProviderMetadataArrayShape from \WordPress\AiClient\Providers\DTO\ProviderMetadata
     * @phpstan-import-type ModelMetadataArrayShape from \WordPress\AiClient\Providers\Models\DTO\ModelMetadata
     *
     * @phpstan-type GenerativeAiResultArrayShape array{
     *     id: string,
     *     candidates: array<CandidateArrayShape>,
     *     tokenUsage: TokenUsageArrayShape,
     *     providerMetadata: ProviderMetadataArrayShape,
     *     modelMetadata: ModelMetadataArrayShape,
     *     additionalData?: array<string, mixed>
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<GenerativeAiResultArrayShape>
     */
    class GenerativeAiResult extends \WordPress\AiClient\Common\AbstractDataTransferObject implements \WordPress\AiClient\Results\Contracts\ResultInterface
    {
        public const KEY_ID = 'id';
        public const KEY_CANDIDATES = 'candidates';
        public const KEY_TOKEN_USAGE = 'tokenUsage';
        public const KEY_PROVIDER_METADATA = 'providerMetadata';
        public const KEY_MODEL_METADATA = 'modelMetadata';
        public const KEY_ADDITIONAL_DATA = 'additionalData';
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string $id Unique identifier for this result.
         * @param Candidate[] $candidates The generated candidates.
         * @param TokenUsage $tokenUsage Token usage statistics.
         * @param \WordPress\AiClient\Providers\DTO\ProviderMetadata $providerMetadata Provider metadata.
         * @param \WordPress\AiClient\Providers\Models\DTO\ModelMetadata $modelMetadata Model metadata.
         * @param array<string, mixed> $additionalData Additional data.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If no candidates provided.
         */
        public function __construct(string $id, array $candidates, \WordPress\AiClient\Results\DTO\TokenUsage $tokenUsage, \WordPress\AiClient\Providers\DTO\ProviderMetadata $providerMetadata, \WordPress\AiClient\Providers\Models\DTO\ModelMetadata $modelMetadata, array $additionalData = [])
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function getId(): string
        {
        }
        /**
         * Gets the generated candidates.
         *
         * @since 0.1.0
         *
         * @return Candidate[] The candidates.
         */
        public function getCandidates(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function getTokenUsage(): \WordPress\AiClient\Results\DTO\TokenUsage
        {
        }
        /**
         * Gets the provider metadata.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\DTO\ProviderMetadata The provider metadata.
         */
        public function getProviderMetadata(): \WordPress\AiClient\Providers\DTO\ProviderMetadata
        {
        }
        /**
         * Gets the model metadata.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\Models\DTO\ModelMetadata The model metadata.
         */
        public function getModelMetadata(): \WordPress\AiClient\Providers\Models\DTO\ModelMetadata
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public function getAdditionalData(): array
        {
        }
        /**
         * Gets the total number of candidates.
         *
         * @since 0.1.0
         *
         * @return int The total number of candidates.
         */
        public function getCandidateCount(): int
        {
        }
        /**
         * Checks if the result has multiple candidates.
         *
         * @since 0.1.0
         *
         * @return bool True if there are multiple candidates, false otherwise.
         */
        public function hasMultipleCandidates(): bool
        {
        }
        /**
         * Converts the first candidate to text.
         *
         * Only text from the content channel is considered. Text within model thought or reasoning is ignored.
         *
         * @since 0.1.0
         *
         * @return string The text content.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no text content.
         */
        public function toText(): string
        {
        }
        /**
         * Converts the first candidate to a file.
         *
         * Only files from the content channel are considered. Files within model thought or reasoning are ignored.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\DTO\File The file.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no file content.
         */
        public function toFile(): \WordPress\AiClient\Files\DTO\File
        {
        }
        /**
         * Converts the first candidate to an image file.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\DTO\File The image file.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no image content.
         */
        public function toImageFile(): \WordPress\AiClient\Files\DTO\File
        {
        }
        /**
         * Converts the first candidate to an audio file.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\DTO\File The audio file.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no audio content.
         */
        public function toAudioFile(): \WordPress\AiClient\Files\DTO\File
        {
        }
        /**
         * Converts the first candidate to a video file.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\DTO\File The video file.
         * @throws \WordPress\AiClient\Common\Exception\RuntimeException If no video content.
         */
        public function toVideoFile(): \WordPress\AiClient\Files\DTO\File
        {
        }
        /**
         * Converts the first candidate to a message.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Messages\DTO\Message The message.
         */
        public function toMessage(): \WordPress\AiClient\Messages\DTO\Message
        {
        }
        /**
         * Converts all candidates to text.
         *
         * @since 0.1.0
         *
         * @return list<string> Array of text content.
         */
        public function toTexts(): array
        {
        }
        /**
         * Converts all candidates to files.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Files\DTO\File> Array of files.
         */
        public function toFiles(): array
        {
        }
        /**
         * Converts all candidates to image files.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Files\DTO\File> Array of image files.
         */
        public function toImageFiles(): array
        {
        }
        /**
         * Converts all candidates to audio files.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Files\DTO\File> Array of audio files.
         */
        public function toAudioFiles(): array
        {
        }
        /**
         * Converts all candidates to video files.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Files\DTO\File> Array of video files.
         */
        public function toVideoFiles(): array
        {
        }
        /**
         * Converts all candidates to messages.
         *
         * @since 0.1.0
         *
         * @return list<\WordPress\AiClient\Messages\DTO\Message> Array of messages.
         */
        public function toMessages(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return GenerativeAiResultArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
        /**
         * Performs a deep clone of the result.
         *
         * This method ensures that all nested objects (candidates, token usage, metadata)
         * are cloned to prevent modifications to the cloned result from affecting the original.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
    }
}
namespace WordPress\AiClient\Results\Enums {
    /**
     * Enum for finish reasons of AI generation.
     *
     * @since 0.1.0
     *
     * @method static self stop() Creates an instance for STOP reason.
     * @method static self length() Creates an instance for LENGTH reason.
     * @method static self contentFilter() Creates an instance for CONTENT_FILTER reason.
     * @method static self toolCalls() Creates an instance for TOOL_CALLS reason.
     * @method static self error() Creates an instance for ERROR reason.
     * @method bool isStop() Checks if the reason is STOP.
     * @method bool isLength() Checks if the reason is LENGTH.
     * @method bool isContentFilter() Checks if the reason is CONTENT_FILTER.
     * @method bool isToolCalls() Checks if the reason is TOOL_CALLS.
     * @method bool isError() Checks if the reason is ERROR.
     */
    class FinishReasonEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Generation stopped naturally.
         */
        public const STOP = 'stop';
        /**
         * Generation stopped due to max length.
         */
        public const LENGTH = 'length';
        /**
         * Generation stopped due to content filter.
         */
        public const CONTENT_FILTER = 'content_filter';
        /**
         * Generation stopped to make tool calls.
         */
        public const TOOL_CALLS = 'tool_calls';
        /**
         * Generation stopped due to error.
         */
        public const ERROR = 'error';
    }
}
namespace WordPress\AiClient\Common\Exception {
    /**
     * Exception thrown when a token limit is reached during prompt fulfillment.
     *
     * Providers should throw this exception when the token usage for a request
     * exceeds the allowed limit, whether that is the model's context window
     * or a configured maximum.
     *
     * @since 1.0.0
     */
    class TokenLimitReachedException extends \WordPress\AiClient\Common\Exception\RuntimeException
    {
        /**
         * Creates a new TokenLimitReachedException.
         *
         * @since 1.0.0
         *
         * @param string         $message   The exception message.
         * @param int|null       $maxTokens The token limit that was reached, if known.
         * @param \Throwable|null $previous  The previous throwable used for exception chaining.
         */
        public function __construct(string $message = '', ?int $maxTokens = null, ?\Throwable $previous = null)
        {
        }
        /**
         * Returns the token limit that was reached, if known.
         *
         * @since 1.0.0
         *
         * @return int|null The token limit, or null if not provided.
         */
        public function getMaxTokens(): ?int
        {
        }
    }
}
namespace WordPress\AiClient {
    /**
     * Main AI Client class providing both fluent and traditional APIs for AI operations.
     *
     * This class serves as the primary entry point for AI operations, offering:
     * - Fluent API for easy-to-read chained method calls
     * - Traditional API for array-based configuration (WordPress style)
     * - Integration with provider registry for model discovery
     * - Support for three model specification approaches
     *
     * All model requirements analysis and capability matching is handled
     * automatically by the PromptBuilder, which provides intelligent model
     * discovery based on prompt content and configuration.
     *
     * ## Model Specification Approaches
     *
     * ### 1. Specific Model Instance
     * Use a specific ModelInterface instance when you know exactly which model to use:
     * ```php
     * $model = $registry->getProvider('openai')->getModel('gpt-4');
     * $result = AiClient::generateTextResult('What is PHP?', $model);
     * ```
     *
     * ### 2. ModelConfig for Auto-Discovery
     * Use ModelConfig to specify requirements and let the system discover the best model:
     * ```php
     * $config = new ModelConfig();
     * $config->setTemperature(0.7);
     * $config->setMaxTokens(150);
     *
     * $result = AiClient::generateTextResult('What is PHP?', $config);
     * ```
     *
     * ### 3. Automatic Discovery (Default)
     * Pass null or omit the parameter for intelligent model discovery based on prompt content:
     * ```php
     * // System analyzes prompt and selects appropriate model automatically
     * $result = AiClient::generateTextResult('What is PHP?');
     * $imageResult = AiClient::generateImageResult('A sunset over mountains');
     * ```
     *
     * ## Fluent API Examples
     * ```php
     * // Fluent API with automatic model discovery
     * $result = AiClient::prompt('Generate an image of a sunset')
     *     ->usingTemperature(0.7)
     *     ->generateImageResult();
     *
     * // Fluent API with specific model
     * $result = AiClient::prompt('What is PHP?')
     *     ->usingModel($specificModel)
     *     ->usingTemperature(0.5)
     *     ->generateTextResult();
     *
     * // Fluent API with model configuration
     * $result = AiClient::prompt('Explain quantum physics')
     *     ->usingModelConfig($config)
     *     ->generateTextResult();
     * ```
     *
     * @since 0.1.0
     *
     * @phpstan-import-type Prompt from \WordPress\AiClient\Builders\PromptBuilder
     *
     * phpcs:ignore Generic.Files.LineLength.TooLong
     */
    class AiClient
    {
        /**
         * @var string The version of the AI Client.
         */
        public const VERSION = '1.3.1';
        /**
         * Gets the default provider registry instance.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Providers\ProviderRegistry The default provider registry.
         */
        public static function defaultRegistry(): \WordPress\AiClient\Providers\ProviderRegistry
        {
        }
        /**
         * Sets the event dispatcher for prompt lifecycle events.
         *
         * The event dispatcher will be used to dispatch BeforeGenerateResultEvent and
         * AfterGenerateResultEvent during prompt generation.
         *
         * @since 0.4.0
         *
         * @param \WordPress\AiClientDependencies\Psr\EventDispatcher\EventDispatcherInterface|null $dispatcher The event dispatcher, or null to disable.
         * @return void
         */
        public static function setEventDispatcher(?\WordPress\AiClientDependencies\Psr\EventDispatcher\EventDispatcherInterface $dispatcher): void
        {
        }
        /**
         * Gets the event dispatcher for prompt lifecycle events.
         *
         * @since 0.4.0
         *
         * @return \WordPress\AiClientDependencies\Psr\EventDispatcher\EventDispatcherInterface|null The event dispatcher, or null if not set.
         */
        public static function getEventDispatcher(): ?\WordPress\AiClientDependencies\Psr\EventDispatcher\EventDispatcherInterface
        {
        }
        /**
         * Sets the PSR-16 cache for storing and retrieving cached data.
         *
         * The cache can be used to store AI responses and other data to avoid
         * redundant API calls and improve performance.
         *
         * @since 0.4.0
         *
         * @param \WordPress\AiClientDependencies\Psr\SimpleCache\CacheInterface|null $cache The PSR-16 cache instance, or null to disable caching.
         * @return void
         */
        public static function setCache(?\WordPress\AiClientDependencies\Psr\SimpleCache\CacheInterface $cache): void
        {
        }
        /**
         * Gets the PSR-16 cache instance.
         *
         * @since 0.4.0
         *
         * @return \WordPress\AiClientDependencies\Psr\SimpleCache\CacheInterface|null The cache instance, or null if not set.
         */
        public static function getCache(): ?\WordPress\AiClientDependencies\Psr\SimpleCache\CacheInterface
        {
        }
        /**
         * Checks if a provider is configured and available for use.
         *
         * Supports multiple input formats for developer convenience:
         * - ProviderAvailabilityInterface: Direct availability check
         * - string (provider ID): e.g., AiClient::isConfigured('openai')
         * - string (class name): e.g., AiClient::isConfigured(OpenAiProvider::class)
         *
         * When using string input, this method leverages the ProviderRegistry's centralized
         * dependency management, ensuring HttpTransporter and authentication are properly
         * injected into availability instances.
         *
         * @since 0.1.0
         * @since 0.2.0 Now supports being passed a provider ID or class name.
         *
         * @param \WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface|string|class-string<\WordPress\AiClient\Providers\Contracts\ProviderInterface> $availabilityOrIdOrClassName
         *        The provider availability instance, provider ID, or provider class name.
         * @return bool True if the provider is configured and available, false otherwise.
         */
        public static function isConfigured($availabilityOrIdOrClassName): bool
        {
        }
        /**
         * Creates a new prompt builder for fluent API usage.
         *
         * Returns a PromptBuilder instance configured with the specified or default registry.
         * The traditional API methods in this class delegate to PromptBuilder
         * for all generation logic.
         *
         * @since 0.1.0
         *
         * @param Prompt $prompt Optional initial prompt content.
         * @param \WordPress\AiClient\Providers\ProviderRegistry|null $registry Optional custom registry. If null, uses default.
         * @return \WordPress\AiClient\Builders\PromptBuilder The prompt builder instance.
         */
        public static function prompt($prompt = null, ?\WordPress\AiClient\Providers\ProviderRegistry $registry = null): \WordPress\AiClient\Builders\PromptBuilder
        {
        }
        /**
         * Generates content using a unified API that automatically detects model capabilities.
         *
         * When no model is provided, this method delegates to PromptBuilder for intelligent
         * model discovery based on prompt content and configuration. When a model is provided,
         * it infers the capability from the model's interfaces and delegates to the capability-based method.
         *
         * @since 0.1.0
         *
         * @param Prompt $prompt The prompt content.
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface|\WordPress\AiClient\Providers\Models\DTO\ModelConfig $modelOrConfig Specific model to use, or model configuration
         *                                                  for auto-discovery.
         * @param \WordPress\AiClient\Providers\ProviderRegistry|null $registry Optional custom registry. If null, uses default.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generation result.
         *
         * @throws \InvalidArgumentException If the provided model doesn't support any known generation type.
         * @throws \RuntimeException If no suitable model can be found for the prompt.
         */
        public static function generateResult($prompt, $modelOrConfig, ?\WordPress\AiClient\Providers\ProviderRegistry $registry = null): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Generates text using the traditional API approach.
         *
         * @since 0.1.0
         *
         * @param Prompt $prompt The prompt content.
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface|\WordPress\AiClient\Providers\Models\DTO\ModelConfig|null $modelOrConfig Optional specific model to use,
         *                                                        or model configuration for auto-discovery,
         *                                                        or null for defaults.
         * @param \WordPress\AiClient\Providers\ProviderRegistry|null $registry Optional custom registry. If null, uses default.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generation result.
         *
         * @throws \InvalidArgumentException If the prompt format is invalid.
         * @throws \RuntimeException If no suitable model is found.
         */
        public static function generateTextResult($prompt, $modelOrConfig = null, ?\WordPress\AiClient\Providers\ProviderRegistry $registry = null): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Generates an image using the traditional API approach.
         *
         * @since 0.1.0
         *
         * @param Prompt $prompt The prompt content.
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface|\WordPress\AiClient\Providers\Models\DTO\ModelConfig|null $modelOrConfig Optional specific model to use,
         *                                                        or model configuration for auto-discovery,
         *                                                        or null for defaults.
         * @param \WordPress\AiClient\Providers\ProviderRegistry|null $registry Optional custom registry. If null, uses default.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generation result.
         *
         * @throws \InvalidArgumentException If the prompt format is invalid.
         * @throws \RuntimeException If no suitable model is found.
         */
        public static function generateImageResult($prompt, $modelOrConfig = null, ?\WordPress\AiClient\Providers\ProviderRegistry $registry = null): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Converts text to speech using the traditional API approach.
         *
         * @since 0.1.0
         *
         * @param Prompt $prompt The prompt content.
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface|\WordPress\AiClient\Providers\Models\DTO\ModelConfig|null $modelOrConfig Optional specific model to use,
         *                                                        or model configuration for auto-discovery,
         *                                                        or null for defaults.
         * @param \WordPress\AiClient\Providers\ProviderRegistry|null $registry Optional custom registry. If null, uses default.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generation result.
         *
         * @throws \InvalidArgumentException If the prompt format is invalid.
         * @throws \RuntimeException If no suitable model is found.
         */
        public static function convertTextToSpeechResult($prompt, $modelOrConfig = null, ?\WordPress\AiClient\Providers\ProviderRegistry $registry = null): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Generates speech using the traditional API approach.
         *
         * @since 0.1.0
         *
         * @param Prompt $prompt The prompt content.
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface|\WordPress\AiClient\Providers\Models\DTO\ModelConfig|null $modelOrConfig Optional specific model to use,
         *                                                        or model configuration for auto-discovery,
         *                                                        or null for defaults.
         * @param \WordPress\AiClient\Providers\ProviderRegistry|null $registry Optional custom registry. If null, uses default.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generation result.
         *
         * @throws \InvalidArgumentException If the prompt format is invalid.
         * @throws \RuntimeException If no suitable model is found.
         */
        public static function generateSpeechResult($prompt, $modelOrConfig = null, ?\WordPress\AiClient\Providers\ProviderRegistry $registry = null): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Generates a video using the traditional API approach.
         *
         * @since 1.3.0
         *
         * @param Prompt $prompt The prompt content.
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface|\WordPress\AiClient\Providers\Models\DTO\ModelConfig|null $modelOrConfig Optional specific model to use,
         *                                                        or model configuration for auto-discovery,
         *                                                        or null for defaults.
         * @param \WordPress\AiClient\Providers\ProviderRegistry|null $registry Optional custom registry. If null, uses default.
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The generation result.
         *
         * @throws \InvalidArgumentException If the prompt format is invalid.
         * @throws \RuntimeException If no suitable model is found.
         */
        public static function generateVideoResult($prompt, $modelOrConfig = null, ?\WordPress\AiClient\Providers\ProviderRegistry $registry = null): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Creates a new message builder for fluent API usage.
         *
         * This method will be implemented once MessageBuilder is available.
         * MessageBuilder will provide a fluent interface for constructing complex
         * messages with multiple parts, attachments, and metadata.
         *
         * @since 0.1.0
         *
         * @param string|null $text Optional initial message text.
         * @return object MessageBuilder instance (type will be updated when MessageBuilder is available).
         *
         * @throws \RuntimeException When MessageBuilder is not yet available.
         */
        public static function message(?string $text = null)
        {
        }
    }
}
namespace WordPress\AiClient\Files\DTO {
    /**
     * Represents a file in the AI client.
     *
     * This DTO automatically detects whether a file is a URL, base64 data, or local file path
     * and handles them appropriately.
     *
     * @since 0.1.0
     *
     * @phpstan-type FileArrayShape array{
     *     fileType: string,
     *     url?: string,
     *     mimeType: string,
     *     base64Data?: string
     * }
     *
     * @extends \WordPress\AiClient\Common\AbstractDataTransferObject<FileArrayShape>
     */
    class File extends \WordPress\AiClient\Common\AbstractDataTransferObject
    {
        public const KEY_FILE_TYPE = 'fileType';
        public const KEY_MIME_TYPE = 'mimeType';
        public const KEY_URL = 'url';
        public const KEY_BASE64_DATA = 'base64Data';
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string $file The file string (URL, base64 data, or local path).
         * @param string|null $mimeType The MIME type of the file (optional).
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the file format is invalid or MIME type cannot be determined.
         */
        public function __construct(string $file, ?string $mimeType = null)
        {
        }
        /**
         * Gets the file type.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\Enums\FileTypeEnum The file type.
         */
        public function getFileType(): \WordPress\AiClient\Files\Enums\FileTypeEnum
        {
        }
        /**
         * Checks if the file is an inline file.
         *
         * @since 0.1.0
         *
         * @return bool True if the file is inline (base64/data URI).
         */
        public function isInline(): bool
        {
        }
        /**
         * Checks if the file is a remote file.
         *
         * @since 0.1.0
         *
         * @return bool True if the file is remote (URL).
         */
        public function isRemote(): bool
        {
        }
        /**
         * Gets the URL for remote files.
         *
         * @since 0.1.0
         *
         * @return string|null The URL, or null if not a remote file.
         */
        public function getUrl(): ?string
        {
        }
        /**
         * Gets the base64-encoded data for inline files.
         *
         * @since 0.1.0
         *
         * @return string|null The plain base64-encoded data (without data URI prefix), or null if not an inline file.
         */
        public function getBase64Data(): ?string
        {
        }
        /**
         * Gets the data as a data URI for inline files.
         *
         * @since 0.1.0
         *
         * @return string|null The data URI in format: data:[mimeType];base64,[data], or null if not an inline file.
         */
        public function getDataUri(): ?string
        {
        }
        /**
         * Gets the MIME type of the file as a string.
         *
         * @since 0.1.0
         *
         * @return string The MIME type string value.
         */
        public function getMimeType(): string
        {
        }
        /**
         * Gets the MIME type object.
         *
         * @since 0.1.0
         *
         * @return \WordPress\AiClient\Files\ValueObjects\MimeType The MIME type object.
         */
        public function getMimeTypeObject(): \WordPress\AiClient\Files\ValueObjects\MimeType
        {
        }
        /**
         * Checks if the file is a video.
         *
         * @since 0.1.0
         *
         * @return bool True if the file is a video.
         */
        public function isVideo(): bool
        {
        }
        /**
         * Checks if the file is an image.
         *
         * @since 0.1.0
         *
         * @return bool True if the file is an image.
         */
        public function isImage(): bool
        {
        }
        /**
         * Checks if the file is audio.
         *
         * @since 0.1.0
         *
         * @return bool True if the file is audio.
         */
        public function isAudio(): bool
        {
        }
        /**
         * Checks if the file is text.
         *
         * @since 0.1.0
         *
         * @return bool True if the file is text.
         */
        public function isText(): bool
        {
        }
        /**
         * Checks if the file is a document.
         *
         * @since 0.1.0
         *
         * @return bool True if the file is a document.
         */
        public function isDocument(): bool
        {
        }
        /**
         * Checks if the file is a specific MIME type.
         *
         * @since 0.1.0
         *
         * @param string $type The mime type to check (e.g. 'image', 'text', 'video', 'audio').
         *
         * @return bool True if the file is of the specified type.
         */
        public function isMimeType(string $type): bool
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function getJsonSchema(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         *
         * @return FileArrayShape
         */
        public function toArray(): array
        {
        }
        /**
         * {@inheritDoc}
         *
         * @since 0.1.0
         */
        public static function fromArray(array $array): self
        {
        }
        /**
         * Performs a deep clone of the file.
         *
         * This method ensures that the MimeType value object is cloned to prevent
         * any shared references between the original and cloned file.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
    }
}
namespace WordPress\AiClient\Files\Enums {
    /**
     * Represents the type of file storage.
     *
     * @method static self square() Returns the square orientation
     * @method static self landscape() Returns the landscape orientation.
     * @method static self portrait() Returns the portrait orientation.
     * @method bool isSquare() Checks if this is an square orientation
     * @method bool isLandscape() Checks if this is a landscape orientation.
     * @method bool isPortrait() Checks if this is a portrait orientation.
     *
     * @since 0.1.0
     */
    class MediaOrientationEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Square orientation.
         *
         * @var string
         */
        public const SQUARE = 'square';
        /**
         * Landscape orientation.
         *
         * @var string
         */
        public const LANDSCAPE = 'landscape';
        /**
         * Portrait orientation.
         *
         * @var string
         */
        public const PORTRAIT = 'portrait';
    }
    /**
     * Represents the type of file storage.
     *
     * @method static self inline() Returns the inline file type.
     * @method static self remote() Returns the remote file type.
     * @method bool isInline() Checks if this is an inline file type.
     * @method bool isRemote() Checks if this is a remote file type.
     *
     * @since 0.1.0
     */
    class FileTypeEnum extends \WordPress\AiClient\Common\AbstractEnum
    {
        /**
         * Inline file with base64-encoded data.
         *
         * @var string
         */
        public const INLINE = 'inline';
        /**
         * Remote file referenced by URL.
         *
         * @var string
         */
        public const REMOTE = 'remote';
    }
}
namespace WordPress\AiClient\Files\ValueObjects {
    /**
     * Value object representing a MIME type.
     *
     * This immutable value object encapsulates MIME type validation and
     * provides convenient methods for checking MIME type categories.
     *
     * @since 0.1.0
     */
    final class MimeType
    {
        /**
         * Constructor.
         *
         * @since 0.1.0
         *
         * @param string $value The MIME type value.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the MIME type is invalid.
         */
        public function __construct(string $value)
        {
        }
        /**
         * Gets the primary known file extension for this MIME type.
         *
         * @since 0.1.0
         *
         * @return string The file extension (without the dot).
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If no known extension exists for this MIME type.
         */
        public function toExtension(): string
        {
        }
        /**
         * Creates a MimeType from a file extension.
         *
         * @since 0.1.0
         *
         * @param string $extension The file extension (without the dot).
         * @return self The MimeType instance.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the extension is not recognized.
         */
        public static function fromExtension(string $extension): self
        {
        }
        /**
         * Checks if a MIME type string is valid.
         *
         * @since 0.1.0
         *
         * @param string $mimeType The MIME type to validate.
         * @return bool True if valid.
         */
        public static function isValid(string $mimeType): bool
        {
        }
        /**
         * Checks if this MIME type is a specific type.
         *
         * This method returns true when the stored MIME type begins with the
         * given prefix. For example, `"audio"` matches `"audio/mpeg"`.
         *
         * @since 0.1.0
         *
         * @param string $mimeType The MIME type prefix to check (e.g., "audio", "image").
         * @return bool True if this MIME type is of the specified type.
         */
        public function isType(string $mimeType): bool
        {
        }
        /**
         * Checks if this is an image MIME type.
         *
         * @since 0.1.0
         *
         * @return bool True if this is an image type.
         */
        public function isImage(): bool
        {
        }
        /**
         * Checks if this is an audio MIME type.
         *
         * @since 0.1.0
         *
         * @return bool True if this is an audio type.
         */
        public function isAudio(): bool
        {
        }
        /**
         * Checks if this is a video MIME type.
         *
         * @since 0.1.0
         *
         * @return bool True if this is a video type.
         */
        public function isVideo(): bool
        {
        }
        /**
         * Checks if this is a text MIME type.
         *
         * @since 0.1.0
         *
         * @return bool True if this is a text type.
         */
        public function isText(): bool
        {
        }
        /**
         * Checks if this is a document MIME type.
         *
         * @since 0.1.0
         *
         * @return bool True if this is a document type.
         */
        public function isDocument(): bool
        {
        }
        /**
         * Checks if this MIME type equals another.
         *
         * @since 0.1.0
         *
         * @param self|string $other The other MIME type to compare.
         * @return bool True if equal.
         * @throws \WordPress\AiClient\Common\Exception\InvalidArgumentException If the other MIME type is invalid.
         */
        public function equals($other): bool
        {
        }
        /**
         * Gets the string representation of the MIME type.
         *
         * @since 0.1.0
         *
         * @return string The MIME type value.
         */
        public function __toString(): string
        {
        }
    }
}
namespace WordPress\AiClient\Events {
    /**
     * Event dispatched before a prompt is sent to the AI model.
     *
     * This event allows listeners to inspect and modify the messages before they
     * are sent to the model. The event is not stoppable, meaning the model call
     * will always proceed regardless of listener actions.
     *
     * @since 0.4.0
     */
    class BeforeGenerateResultEvent
    {
        /**
         * Constructor.
         *
         * @since 0.4.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $messages The messages to be sent to the model.
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model The model that will process the prompt.
         * @param \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum|null $capability The capability being used for generation.
         */
        public function __construct(array $messages, \WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model, ?\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum $capability)
        {
        }
        /**
         * Gets the messages to be sent to the model.
         *
         * @since 0.4.0
         *
         * @return list<\WordPress\AiClient\Messages\DTO\Message> The messages.
         */
        public function getMessages(): array
        {
        }
        /**
         * Gets the model that will process the prompt.
         *
         * @since 0.4.0
         *
         * @return \WordPress\AiClient\Providers\Models\Contracts\ModelInterface The model.
         */
        public function getModel(): \WordPress\AiClient\Providers\Models\Contracts\ModelInterface
        {
        }
        /**
         * Gets the capability being used for generation.
         *
         * @since 0.4.0
         *
         * @return \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum|null The capability, or null if not specified.
         */
        public function getCapability(): ?\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum
        {
        }
        /**
         * Performs a deep clone of the event.
         *
         * This method ensures that message objects are cloned to prevent
         * modifications to the cloned event from affecting the original.
         * The model object is not cloned as it is a service object.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
    }
    /**
     * Event dispatched after a prompt has been sent to the AI model and a response received.
     *
     * This event allows listeners to inspect the result of the model call for logging,
     * analytics, or other post-processing purposes. The result object is immutable.
     *
     * @since 0.4.0
     */
    class AfterGenerateResultEvent
    {
        /**
         * Constructor.
         *
         * @since 0.4.0
         *
         * @param list<\WordPress\AiClient\Messages\DTO\Message> $messages The messages that were sent to the model.
         * @param \WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model The model that processed the prompt.
         * @param \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum|null $capability The capability that was used for generation.
         * @param \WordPress\AiClient\Results\DTO\GenerativeAiResult $result The result from the model.
         */
        public function __construct(array $messages, \WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model, ?\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum $capability, \WordPress\AiClient\Results\DTO\GenerativeAiResult $result)
        {
        }
        /**
         * Gets the messages that were sent to the model.
         *
         * @since 0.4.0
         *
         * @return list<\WordPress\AiClient\Messages\DTO\Message> The messages.
         */
        public function getMessages(): array
        {
        }
        /**
         * Gets the model that processed the prompt.
         *
         * @since 0.4.0
         *
         * @return \WordPress\AiClient\Providers\Models\Contracts\ModelInterface The model.
         */
        public function getModel(): \WordPress\AiClient\Providers\Models\Contracts\ModelInterface
        {
        }
        /**
         * Gets the capability that was used for generation.
         *
         * @since 0.4.0
         *
         * @return \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum|null The capability, or null if not specified.
         */
        public function getCapability(): ?\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum
        {
        }
        /**
         * Gets the result from the model.
         *
         * @since 0.4.0
         *
         * @return \WordPress\AiClient\Results\DTO\GenerativeAiResult The result.
         */
        public function getResult(): \WordPress\AiClient\Results\DTO\GenerativeAiResult
        {
        }
        /**
         * Performs a deep clone of the event.
         *
         * This method ensures that message and result objects are cloned to prevent
         * modifications to the cloned event from affecting the original.
         * The model object is not cloned as it is a service object.
         *
         * @since 0.4.2
         */
        public function __clone()
        {
        }
    }
}
namespace {
    /**
     * Fluent builder for constructing AI prompts, returning WP_Error on failure.
     *
     * This class provides a fluent interface for building prompts with various
     * content types and model configurations. It wraps the PHP AI Client SDK's
     * PromptBuilder and adds WordPress-specific behavior including WP_Error
     * handling instead of exceptions, snake_case method naming, and integration
     * with the Abilities API.
     *
     * Only the generating methods will return a WP_Error, to not break the fluent
     * interface. As soon as any exception is caught in a chain of method calls,
     * the returned instance will be in an error state, and all subsequent method
     * calls will be no-ops that just return the same error state instance. Only
     * when a generating method is called, the WP_Error will be returned.
     *
     * @since 7.0.0
     *
     * @phpstan-import-type Prompt from \WordPress\AiClient\Builders\PromptBuilder
     *
     * @method self with_text(string $text) Adds text to the current message.
     * @method self with_file($file, ?string $mimeType = null) Adds a file to the current message.
     * @method self with_function_response(\WordPress\AiClient\Tools\DTO\FunctionResponse $functionResponse) Adds a function response to the current message.
     * @method self with_message_parts(\WordPress\AiClient\Messages\DTO\MessagePart ...$parts) Adds message parts to the current message.
     * @method self with_history(\WordPress\AiClient\Messages\DTO\Message ...$messages) Adds conversation history messages.
     * @method self using_model(\WordPress\AiClient\Providers\Models\Contracts\ModelInterface $model) Sets the model to use for generation.
     * @method self using_model_preference(...$preferredModels) Sets preferred models to evaluate in order.
     * @method self using_model_config(\WordPress\AiClient\Providers\Models\DTO\ModelConfig $config) Sets the model configuration.
     * @method self using_provider(string $providerIdOrClassName) Sets the provider to use for generation.
     * @method self using_system_instruction(string $systemInstruction) Sets the system instruction.
     * @method self using_max_tokens(int $maxTokens) Sets the maximum number of tokens to generate.
     * @method self using_temperature(float $temperature) Sets the temperature for generation.
     * @method self using_top_p(float $topP) Sets the top-p value for generation.
     * @method self using_top_k(int $topK) Sets the top-k value for generation.
     * @method self using_stop_sequences(string ...$stopSequences) Sets stop sequences for generation.
     * @method self using_candidate_count(int $candidateCount) Sets the number of candidates to generate.
     * @method self using_function_declarations(\WordPress\AiClient\Tools\DTO\FunctionDeclaration ...$functionDeclarations) Sets the function declarations available to the model.
     * @method self using_presence_penalty(float $presencePenalty) Sets the presence penalty for generation.
     * @method self using_frequency_penalty(float $frequencyPenalty) Sets the frequency penalty for generation.
     * @method self using_web_search(\WordPress\AiClient\Tools\DTO\WebSearch $webSearch) Sets the web search configuration.
     * @method self using_request_options(\WordPress\AiClient\Providers\Http\DTO\RequestOptions $options) Sets the request options for HTTP transport.
     * @method self using_top_logprobs(?int $topLogprobs = null) Sets the top log probabilities configuration.
     * @method self as_output_mime_type(string $mimeType) Sets the output MIME type.
     * @method self as_output_schema(array<string, mixed> $schema) Sets the output schema.
     * @method self as_output_modalities(\WordPress\AiClient\Messages\Enums\ModalityEnum ...$modalities) Sets the output modalities.
     * @method self as_output_file_type(\WordPress\AiClient\Files\Enums\FileTypeEnum $fileType) Sets the output file type.
     * @method self as_output_media_orientation(\WordPress\AiClient\Files\Enums\MediaOrientationEnum $orientation) Sets the output media orientation.
     * @method self as_output_media_aspect_ratio(string $aspectRatio) Sets the output media aspect ratio.
     * @method self as_output_speech_voice(string $voice) Sets the output speech voice.
     * @method self as_json_response(?array<string, mixed> $schema = null) Configures the prompt for JSON response output.
     * @method bool|WP_Error is_supported(?\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum $capability = null) Checks if the prompt is supported for the given capability.
     * @method bool is_supported_for_text_generation() Checks if the prompt is supported for text generation.
     * @method bool is_supported_for_image_generation() Checks if the prompt is supported for image generation.
     * @method bool is_supported_for_text_to_speech_conversion() Checks if the prompt is supported for text to speech conversion.
     * @method bool is_supported_for_video_generation() Checks if the prompt is supported for video generation.
     * @method bool is_supported_for_speech_generation() Checks if the prompt is supported for speech generation.
     * @method bool is_supported_for_music_generation() Checks if the prompt is supported for music generation.
     * @method bool is_supported_for_embedding_generation() Checks if the prompt is supported for embedding generation.
     * @method \WordPress\AiClient\Results\DTO\GenerativeAiResult|WP_Error generate_result(?\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum $capability = null) Generates a result from the prompt.
     * @method \WordPress\AiClient\Results\DTO\GenerativeAiResult|WP_Error generate_text_result() Generates a text result from the prompt.
     * @method \WordPress\AiClient\Results\DTO\GenerativeAiResult|WP_Error generate_image_result() Generates an image result from the prompt.
     * @method \WordPress\AiClient\Results\DTO\GenerativeAiResult|WP_Error generate_speech_result() Generates a speech result from the prompt.
     * @method \WordPress\AiClient\Results\DTO\GenerativeAiResult|WP_Error convert_text_to_speech_result() Converts text to speech and returns the result.
     * @method \WordPress\AiClient\Results\DTO\GenerativeAiResult|WP_Error generate_video_result() Generates a video result from the prompt.
     * @method string|WP_Error generate_text() Generates text from the prompt.
     * @method list<string>|WP_Error generate_texts(?int $candidateCount = null) Generates multiple text candidates from the prompt.
     * @method \WordPress\AiClient\Files\DTO\File|WP_Error generate_image() Generates an image from the prompt.
     * @method list<\WordPress\AiClient\Files\DTO\File>|WP_Error generate_images(?int $candidateCount = null) Generates multiple images from the prompt.
     * @method \WordPress\AiClient\Files\DTO\File|WP_Error convert_text_to_speech() Converts text to speech.
     * @method list<\WordPress\AiClient\Files\DTO\File>|WP_Error convert_text_to_speeches(?int $candidateCount = null) Converts text to multiple speech outputs.
     * @method \WordPress\AiClient\Files\DTO\File|WP_Error generate_speech() Generates speech from the prompt.
     * @method list<\WordPress\AiClient\Files\DTO\File>|WP_Error generate_speeches(?int $candidateCount = null) Generates multiple speech outputs from the prompt.
     * @method \WordPress\AiClient\Files\DTO\File|WP_Error generate_video() Generates a video from the prompt.
     * @method list<\WordPress\AiClient\Files\DTO\File>|WP_Error generate_videos(?int $candidateCount = null) Generates multiple videos from the prompt.
     */
    class WP_AI_Client_Prompt_Builder
    {
        /**
         * Constructor.
         *
         * @since 7.0.0
         *
         * @param \WordPress\AiClient\Providers\ProviderRegistry $registry The provider registry for finding suitable models.
         * @param Prompt           $prompt   Optional. Initial prompt content.
         *                                   A string for simple text prompts,
         *                                   a MessagePart or Message object for
         *                                   structured content, an array for a
         *                                   message array shape, or a list of
         *                                   parts or messages for multi-turn
         *                                   conversations. Default null.
         */
        public function __construct(\WordPress\AiClient\Providers\ProviderRegistry $registry, $prompt = \null)
        {
        }
        /**
         * Registers WordPress abilities as function declarations for the AI model.
         *
         * Converts each WP_Ability to a FunctionDeclaration using the wpab__ prefix
         * naming convention and passes them to the underlying prompt builder.
         *
         * @since 7.0.0
         *
         * @param WP_Ability|string ...$abilities The abilities to register, either as WP_Ability objects or ability name strings.
         * @return self The current instance for method chaining.
         */
        public function using_abilities(...$abilities): self
        {
        }
        /**
         * Magic method to proxy snake_case method calls to their PHP AI Client camelCase counterparts.
         *
         * This allows WordPress developers to use snake_case naming conventions. It catches
         * any exceptions thrown, stores them, and returns a WP_Error when a terminate method
         * is called.
         *
         * @since 7.0.0
         *
         * @param string            $name      The method name in snake_case.
         * @param array<int, mixed> $arguments The method arguments.
         * @return mixed The result of the method call.
         */
        public function __call(string $name, array $arguments)
        {
        }
        /**
         * Retrieves a callable for a given PHP AI Client SDK prompt builder method name.
         *
         * @since 7.0.0
         *
         * @param string $name The method name in snake_case.
         * @return callable The callable for the specified method.
         *
         * @throws BadMethodCallException If the method does not exist.
         */
        protected function get_builder_callable(string $name): callable
        {
        }
    }
    /**
     * WordPress-specific PSR-16 cache adapter for the AI Client.
     *
     * Bridges PSR-16 cache operations to WordPress object cache functions,
     * enabling the AI client to leverage WordPress caching infrastructure.
     *
     * @since 7.0.0
     * @internal Intended only to wire up the PHP AI Client SDK to WordPress's caching system.
     * @access private
     */
    class WP_AI_Client_Cache implements \WordPress\AiClientDependencies\Psr\SimpleCache\CacheInterface
    {
        /**
         * Fetches a value from the cache.
         *
         * @since 7.0.0
         *
         * @param string $key           The unique key of this item in the cache.
         * @param mixed  $default_value Default value to return if the key does not exist.
         * @return mixed The value of the item from the cache, or $default_value in case of cache miss.
         */
        public function get($key, $default_value = \null)
        {
        }
        /**
         * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
         *
         * @since 7.0.0
         *
         * @param string                $key   The key of the item to store.
         * @param mixed                 $value The value of the item to store, must be serializable.
         * @param null|int|DateInterval $ttl   Optional. The TTL value of this item.
         * @return bool True on success and false on failure.
         */
        public function set($key, $value, $ttl = \null): bool
        {
        }
        /**
         * Delete an item from the cache by its unique key.
         *
         * @since 7.0.0
         *
         * @param string $key The unique cache key of the item to delete.
         * @return bool True if the item was successfully removed. False if there was an error.
         */
        public function delete($key): bool
        {
        }
        /**
         * Wipes clean the entire cache's keys.
         *
         * This method only clears the cache group used by this adapter. If the underlying
         * cache implementation does not support group flushing, this method returns false.
         *
         * @since 7.0.0
         *
         * @return bool True on success and false on failure.
         */
        public function clear(): bool
        {
        }
        /**
         * Obtains multiple cache items by their unique keys.
         *
         * @since 7.0.0
         *
         * @param iterable<string> $keys          A list of keys that can be obtained in a single operation.
         * @param mixed            $default_value Default value to return for keys that do not exist.
         * @return array<string, mixed> A list of key => value pairs.
         */
        public function getMultiple($keys, $default_value = \null): array
        {
        }
        /**
         * Persists a set of key => value pairs in the cache, with an optional TTL.
         *
         * @since 7.0.0
         *
         * @param iterable<string, mixed> $values A list of key => value pairs for a multiple-set operation.
         * @param null|int|DateInterval   $ttl    Optional. The TTL value of this item.
         * @return bool True on success and false on failure.
         */
        public function setMultiple($values, $ttl = \null): bool
        {
        }
        /**
         * Deletes multiple cache items in a single operation.
         *
         * @since 7.0.0
         *
         * @param iterable<string> $keys A list of string-based keys to be deleted.
         * @return bool True if the items were successfully removed. False if there was an error.
         */
        public function deleteMultiple($keys): bool
        {
        }
        /**
         * Determines whether an item is present in the cache.
         *
         * @since 7.0.0
         *
         * @param string $key The cache item key.
         * @return bool True if the item exists in the cache, false otherwise.
         */
        public function has($key): bool
        {
        }
    }
    /**
     * Discovery strategy for WordPress HTTP client.
     *
     * Registers the WordPress HTTP client adapter with the HTTPlug discovery system
     * so the AI Client SDK can find and use it automatically.
     *
     * @since 7.0.0
     * @internal Intended only to register WordPress's HTTP client so that the PHP AI Client SDK can use it.
     * @access private
     */
    class WP_AI_Client_Discovery_Strategy extends \WordPress\AiClient\Providers\Http\Abstracts\AbstractClientDiscoveryStrategy
    {
        /**
         * Creates an instance of the WordPress HTTP client.
         *
         * @since 7.0.0
         *
         * @param \WordPress\AiClientDependencies\Nyholm\Psr7\Factory\Psr17Factory $psr17_factory The PSR-17 factory for creating HTTP messages.
         * @return \WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface The PSR-18 HTTP client.
         */
        protected static function createClient(\WordPress\AiClientDependencies\Nyholm\Psr7\Factory\Psr17Factory $psr17_factory): \WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface
        {
        }
    }
    /**
     * PSR-18 HTTP Client adapter using WordPress HTTP API.
     *
     * Allows WordPress HTTP functions to be used as a PSR-18 compliant HTTP client
     * for the AI Client SDK.
     *
     * @since 7.0.0
     * @internal Intended only to wire up the PHP AI Client SDK to WordPress's HTTP client.
     * @access private
     */
    class WP_AI_Client_HTTP_Client implements \WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface, \WordPress\AiClient\Providers\Http\Contracts\ClientWithOptionsInterface
    {
        /**
         * Constructor.
         *
         * @since 7.0.0
         *
         * @param \WordPress\AiClientDependencies\Psr\Http\Message\ResponseFactoryInterface $response_factory PSR-17 Response factory.
         * @param \WordPress\AiClientDependencies\Psr\Http\Message\StreamFactoryInterface   $stream_factory   PSR-17 Stream factory.
         */
        public function __construct(\WordPress\AiClientDependencies\Psr\Http\Message\ResponseFactoryInterface $response_factory, \WordPress\AiClientDependencies\Psr\Http\Message\StreamFactoryInterface $stream_factory)
        {
        }
        /**
         * Sends a PSR-7 request and returns a PSR-7 response.
         *
         * @since 7.0.0
         *
         * @param \WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $request The PSR-7 request.
         * @return \WordPress\AiClientDependencies\Psr\Http\Message\ResponseInterface The PSR-7 response.
         *
         * @throws \WordPress\AiClient\Providers\Http\Exception\NetworkException If the WordPress HTTP request fails.
         */
        public function sendRequest(\WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $request): \WordPress\AiClientDependencies\Psr\Http\Message\ResponseInterface
        {
        }
        /**
         * Sends a PSR-7 request with transport options and returns a PSR-7 response.
         *
         * @since 7.0.0
         *
         * @param \WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $request The PSR-7 request.
         * @param \WordPress\AiClient\Providers\Http\DTO\RequestOptions   $options Transport options for the request.
         * @return \WordPress\AiClientDependencies\Psr\Http\Message\ResponseInterface The PSR-7 response.
         *
         * @throws \WordPress\AiClient\Providers\Http\Exception\NetworkException If the WordPress HTTP request fails.
         */
        public function sendRequestWithOptions(\WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $request, \WordPress\AiClient\Providers\Http\DTO\RequestOptions $options): \WordPress\AiClientDependencies\Psr\Http\Message\ResponseInterface
        {
        }
    }
    /**
     * WordPress-specific PSR-14 event dispatcher for the AI Client.
     *
     * Bridges PSR-14 events to WordPress action hooks, enabling plugins to hook
     * into AI client lifecycle events.
     *
     * @since 7.0.0
     * @internal Intended only to wire up the PHP AI Client SDK to WordPress's hook system.
     * @access private
     */
    class WP_AI_Client_Event_Dispatcher implements \WordPress\AiClientDependencies\Psr\EventDispatcher\EventDispatcherInterface
    {
        /**
         * Dispatches an event to WordPress action hooks.
         *
         * Converts the event class name to a WordPress action hook name and fires it.
         * For example, BeforeGenerateResultEvent becomes wp_ai_client_before_generate_result.
         *
         * @since 7.0.0
         *
         * @param object $event The event object to dispatch.
         * @return object The same event object, potentially modified by listeners.
         */
        public function dispatch(object $event): object
        {
        }
    }
    /**
     * Resolves and executes WordPress Abilities API function calls from AI models.
     *
     * This class must be instantiated with the specific abilities that the AI model
     * is allowed to execute, ensuring that only explicitly specified abilities can
     * be called. This prevents the model from executing arbitrary abilities.
     *
     * @since 7.0.0
     */
    class WP_AI_Client_Ability_Function_Resolver
    {
        /**
         * Constructor.
         *
         * @since 7.0.0
         *
         * @param WP_Ability|string ...$abilities The abilities that this resolver is allowed to execute.
         */
        public function __construct(...$abilities)
        {
        }
        /**
         * Checks if a function call is an ability call.
         *
         * @since 7.0.0
         *
         * @param \WordPress\AiClient\Tools\DTO\FunctionCall $call The function call to check.
         * @return bool True if the function call is an ability call, false otherwise.
         */
        public function is_ability_call(\WordPress\AiClient\Tools\DTO\FunctionCall $call): bool
        {
        }
        /**
         * Executes a WordPress ability from a function call.
         *
         * Only abilities that were specified in the constructor are allowed to be
         * executed. If the ability is not in the allowed list, an error response
         * with code `ability_not_allowed` is returned.
         *
         * @since 7.0.0
         *
         * @param \WordPress\AiClient\Tools\DTO\FunctionCall $call The function call to execute.
         * @return \WordPress\AiClient\Tools\DTO\FunctionResponse The response from executing the ability.
         */
        public function execute_ability(\WordPress\AiClient\Tools\DTO\FunctionCall $call): \WordPress\AiClient\Tools\DTO\FunctionResponse
        {
        }
        /**
         * Checks if a message contains any ability function calls.
         *
         * @since 7.0.0
         *
         * @param \WordPress\AiClient\Messages\DTO\Message $message The message to check.
         * @return bool True if the message contains ability calls, false otherwise.
         */
        public function has_ability_calls(\WordPress\AiClient\Messages\DTO\Message $message): bool
        {
        }
        /**
         * Executes all ability function calls in a message.
         *
         * @since 7.0.0
         *
         * @param \WordPress\AiClient\Messages\DTO\Message $message The message containing function calls.
         * @return \WordPress\AiClient\Messages\DTO\Message A new message with function responses.
         */
        public function execute_abilities(\WordPress\AiClient\Messages\DTO\Message $message): \WordPress\AiClient\Messages\DTO\Message
        {
        }
        /**
         * Converts an ability name to a function name.
         *
         * Transforms "tec/create_event" to "wpab__tec__create_event".
         *
         * @since 7.0.0
         *
         * @param string $ability_name The ability name to convert.
         * @return string The function name.
         */
        public static function ability_name_to_function_name(string $ability_name): string
        {
        }
        /**
         * Converts a function name to an ability name.
         *
         * Transforms "wpab__tec__create_event" to "tec/create_event".
         *
         * @since 7.0.0
         *
         * @param string $function_name The function name to convert.
         * @return string The ability name.
         */
        public static function function_name_to_ability_name(string $function_name): string
        {
        }
    }
}
namespace {
    /**
     * Returns whether AI features are supported in the current environment.
     *
     * @since 7.0.0
     *
     * @return bool Whether AI features are supported.
     */
    function wp_supports_ai(): bool
    {
    }
    /**
     * Creates a new AI prompt builder using the default provider registry.
     *
     * This is the main entry point for generating AI content in WordPress. It returns
     * a fluent builder that can be used to configure and execute AI prompts.
     *
     * The prompt can be provided as a simple string for basic text prompts, or as more
     * complex types for advanced use cases like multi-modal content or conversation history.
     *
     * @since 7.0.0
     *
     * @param string|\WordPress\AiClient\Messages\DTO\MessagePart|\WordPress\AiClient\Messages\DTO\Message|array|list<string|\WordPress\AiClient\Messages\DTO\MessagePart|array>|list<\WordPress\AiClient\Messages\DTO\Message>|null $prompt Optional. Initial prompt content.
     *                                                                                                   A string for simple text prompts,
     *                                                                                                   a MessagePart or Message object for
     *                                                                                                   structured content, an array for a
     *                                                                                                   message array shape, or a list of
     *                                                                                                   parts or messages for multi-turn
     *                                                                                                   conversations. Default null.
     * @return WP_AI_Client_Prompt_Builder The prompt builder instance.
     */
    function wp_ai_client_prompt($prompt = \null): \WP_AI_Client_Prompt_Builder
    {
    }
}