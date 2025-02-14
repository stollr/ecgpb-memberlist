<?php

namespace App\Domain;

enum SettingType: string
{
    case TString = 'string';
    case TInt = 'int';
    case TBool = 'bool';
    case TNull = 'null';

    public function castDbValue(?string $dbValue): mixed
    {
        return match ($this) {
            self::TString => (string) $dbValue,
            self::TInt => (int) $dbValue,
            self::TBool => (bool) $dbValue,
            self::TNull => null
        };
    }

    public static function fromPhpValue(mixed $value): self
    {
        return match (true) {
            is_string($value) => self::TString,
            is_integer($value) => self::TInt,
            is_bool($value) => self::TBool,
            is_null($value) => self::TNull,
        };
    }
}
