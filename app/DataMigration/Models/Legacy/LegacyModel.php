<?php

namespace App\DataMigration\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all legacy Goldlink models.
 * All legacy models are read-only and use the 'legacy' database connection.
 */
abstract class LegacyModel extends Model
{
    /**
     * The database connection to use.
     */
    protected $connection = 'legacy';

    /**
     * Disable mass assignment protection for read-only models.
     */
    protected $guarded = [];
}
