<?php

namespace Tighten\SolanaPhpSdk\Borsh;

use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tighten\SolanaPhpSdk\Util\Buffer;

class Borsh
{
    /**
     * @param array $schema
     * @param $object
     * @return array
     */
    public static function serialize(
        array $schema,
        $object
    ) : array
    {
        $writer = new BinaryWriter();
        static::serializeObject($schema, $object, $writer);
        return $writer->toArray();
    }

    /**
     * @param array $schema
     * @param $object
     * @param BinaryWriter $writer
     */
    protected static function serializeObject(
        array $schema,
        $object,
        BinaryWriter $writer
    ) {
        $objectSchema = $schema[get_class($object)] ?? null;
        if (! $objectSchema) {
            $class = get_class($object);
            throw new BorshException("Class {$class} is missing in schema");
        }

        if ($objectSchema['kind'] === 'struct') {
            foreach ($objectSchema['fields'] as list($fieldName, $fieldType)) {
                static::serializeField($schema, $fieldName, $object->{$fieldName}, $fieldType, $writer);
            }
        } elseif ($objectSchema['kind'] === 'enum') {
            throw new TodoException("TODO: Enums don't exist in PHP yet???");
        } else {
            $kind = $objectSchema['kind'];
            $class = get_class($object);
            throw new BorshException("Unexpected schema kind: {$kind} for {$class}");
        }
    }

    /**
     * @param array $schema
     * @param $fieldName
     * @param $value
     * @param $fieldType
     * @param BinaryWriter $writer
     */
    protected static function serializeField(
        array $schema,
        $fieldName,
        $value,
        $fieldType,
        BinaryWriter $writer
    ) {
        if (is_string($fieldType)) {
            $writer->{'write' . ucfirst($fieldType)}($value);
        } elseif (is_array($fieldType) && isset($fieldType[0])) { // sequential array
            if (is_int($fieldType[0])) {
                if (sizeof($value) !== $fieldType[0]) {
                    $sizeOf = sizeof($value);
                    throw new BorshException("Expecting byte array of length {$fieldType[0]}, but got ${$sizeOf} bytes");
                }
                $writer->writeFixedArray($value);
            } elseif (sizeof($fieldType) === 2 && is_int($fieldType[1])) {
                if (sizeof($value) !== $fieldType[1]) {
                    $sizeOf = sizeof($value);
                    throw new BorshException("Expecting byte array of length {$fieldType[1]}, but got ${$sizeOf} bytes");
                }

                for ($i = 0; $i < $fieldType[1]; $i++) {
                    static::serializeField($schema, null, $value[$i], $fieldType[0], $writer);
                }
            } else {
                $writer->writeArray($value, fn ($item) => static::serializeField($schema, $fieldName, $item, $fieldType[0], $writer));
            }
        } elseif (isset($fieldType['kind'])) { // associative array
            switch ($fieldType['kind']) {
                case 'option':
                    if ($value) {
                        $writer->writeU8(1);
                        static::serializeField($schema, $fieldName, $value, $fieldType['type'], $writer);
                    } else {
                        $writer->writeU8(0);
                    }
                    break;
                default:
                    throw new BorshException("FieldType {$fieldType['kind']} unrecognized");
            }
        } else {
            static::serializeObject($schema, $value, $writer);
        }
    }

    /**
     * @param array $schema
     * @param string $class
     * @param array $buffer
     */
    public static function deserialize(
        array $schema,
        string $class,
        array $buffer
    )
    {
        $reader = new BinaryReader(Buffer::from($buffer));
        return static::deserializeObject($schema, $class, $reader);
    }

    /**
     * @param array $schema
     * @param string $class
     * @param BinaryReader $reader
     */
    protected static function deserializeObject(
        array $schema,
        string $class,
        BinaryReader $reader
    ) {
        $objectSchema = $schema[$class] ?? null;
        if (! $objectSchema) {
            throw new BorshException("Class {$class} is missing in schema");
        }

        if ($objectSchema['kind'] === 'struct') {
            if (! method_exists($class, 'borshConstructor')) {
                throw new BorshException("Class {$class} does not implement borshConstructor. Please use the BorshDeserialize trait.");
            }

            $result = $class::borshConstructor();
            foreach ($objectSchema['fields'] as list($fieldName, $fieldType)) {
                $result->{$fieldName} = static::deserializeField($schema, $fieldName, $fieldType, $reader);
            }
            return $result;
        }

        if ($objectSchema['kind'] === 'enum') {
            throw new TodoException("TODO: Enums don't exist in PHP yet???");
        }

        $kind = $objectSchema['kind'];
        throw new BorshException("Unexpected schema kind: {$kind} for {$class}");
    }

    /**
     * @param array $schema
     * @param $fieldName
     * @param $fieldType
     * @param BinaryReader $reader
     */
    protected static function deserializeField(
        array $schema,
        $fieldName,
        $fieldType,
        BinaryReader $reader
    ) {
        if (is_string($fieldType) && ! class_exists($fieldType)) {
            return $reader->{'read' . ucfirst($fieldType)}();
        }

        if (is_array($fieldType) && isset($fieldType[0])) { // sequential array
            if (is_int($fieldType[0])) {
                return $reader->readFixedArray($fieldType[0]);
            } elseif (sizeof($fieldType) === 2 && is_int($fieldType[1])) {
                $array = [];
                for ($i = 0; $i < $fieldType[1]; $i++) {
                    array_push($array, static::deserializeField($schema, null, $fieldType[0], $reader));
                }
                return $array;
            } else {
                return $reader->readArray(fn () => static::deserializeField($schema, $fieldName, $fieldType[0], $reader));
            }
        }

        if (isset($fieldType['kind']) && $fieldType['kind'] === 'option') { // associative array
            $option = $reader->readU8();
            if ($option) {
                return static::deserializeField($schema, $fieldName, $fieldType['type'], $reader);
            }

            return null;
        }

        return static::deserializeObject($schema, $fieldType, $reader);
    }
}
