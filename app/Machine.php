<?php
namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use App\Scopes\CreatedSinceScope;
use munkireport\models\MRModel;
use Laravel\Scout\Searchable;

class Machine extends MRModel
{
    use HasFactory;

    use Searchable;

    protected $table = 'machine';

    protected $fillable = [
        'serial_number',
        'hostname',
        'machine_model',
        'machine_desc',
        'img_url',
        'cpu',
        'current_processor_speed',
        'cpu_arch',
        'os_version',
        'physical_memory',
        'platform_UUID',
        'number_processors',
        'SMC_version_system',
        'boot_rom_version',
        'bus_speed',
        'computer_name',
        'l2_cache',
        'machine_name',
        'packages',
        'buildversion',
    ];

    protected $casts = [
        'number_processors' => 'integer'
    ];

    public $timestamps = false;

    /**
     * Override the route key name to allow Laravel model binding to work.
     */
    public function getRouteKeyName(): string
    {
        return 'serial_number';
    }

    //// RELATIONSHIPS

    /**
     * Get report data submitted by this machine
     */
    public function reportData(): HasOne
    {
        return $this->hasOne('App\ReportData', 'serial_number', 'serial_number');
    }

    /**
     * Get network information stored by the network module.
     *
     * Unfortunately, Machine requires this to work, because ClientsController.php:get_data() needs to join on
     * network.
     */
    public function network(): HasOne
    {
        return $this->hasOne('App\Network', 'serial_number', 'serial_number');
    }

    /**
     * Get a list of machine groups this machine is part of through the
     * `report_data` table.
     */
    public function machineGroups(): HasManyThrough {
        return $this->hasManyThrough(
            'App\MachineGroup', 'App\ReportData',
            'serial_number', 'id', 'serial_number'
        );
    }

    /**
     * Get a list of events generated by this machine.
     */
    public function events(): HasMany {
        return $this->hasMany('App\Event', 'serial_number', 'serial_number');
    }

    /**
     * Get a list of comments associated with this machine.
     * @return HasMany
     */
    public function comments(): HasMany {
        return $this->hasMany('App\Comment', 'serial_number', 'serial_number');
    }

    //// SCOPES
    // Cannot use this while timestamps are disabled.
    // use CreatedSinceScope;
    use ProvidesHistogram;

    /**
     * Query scope for machines which have a duplicate computer name.
     */
    public function scopeDuplicate(Builder $query): Builder {
        return $query->groupBy('computer_name')
            ->having('COUNT(*)', '>', '1');
    }
}
