<?php

namespace App\Models;

use Database\Factories\SettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class settings extends Model
{
    /** @use HasFactory<SettingsFactory> */
    use HasFactory;

    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_public',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Dynamically cast and format the setting value based on its type.
     */
    /**
     * Accessor: بيتنفذ لما تقرأ $model->value
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        $type = $this->attributes['type'] ?? 'string';

        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => is_numeric($value) ? (int) $value : $value,
            'float', 'double' => is_numeric($value) ? (float) $value : $value,
            'json', 'array' => $this->decodeJson($value),
            default => (string) $value,
        };
    }

    /**
     * Mutator: بيتنفذ لما تعمل $model->value = ...
     */
    public function setValueAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['value'] = null;

            return;
        }

        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value, JSON_UNESCAPED_UNICODE);

            return;
        }

        if (is_bool($value)) {
            $this->attributes['value'] = $value ? '1' : '0';

            return;
        }

        $this->attributes['value'] = (string) $value;
    }

    private function decodeJson($value): mixed
    {
        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    protected static function newFactory(): SettingsFactory
    {
        return SettingsFactory::new();
    }
}
