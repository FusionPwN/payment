<?php

declare(strict_types=1);

/**
 * Contains the PaymentMethod class.
 *
 * @copyright   Copyright (c) 2020 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2020-04-26
 *
 */

namespace Vanilo\Payment\Models;

use App\Models\Admin\Media;
use App\Models\Admin\PaymentMethodsLocations;
use App\Models\Themes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Konekt\Enum\Eloquent\CastsEnums;
use Vanilo\Payment\Contracts\PaymentGateway;
use Vanilo\Payment\Contracts\PaymentMethod as PaymentMethodContract;
use Vanilo\Payment\Gateways\NullGateway;
use Vanilo\Payment\PaymentGateways;

/**
 * @property int $id
 * @property string $name
 * @property null|string $description
 * @property string $gateway
 * @property array $configuration
 * @property bool $is_enabled
 * @property integer $transaction_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 * @method PaymentMethod create(array $attributes)
 */
class PaymentMethod extends Model implements PaymentMethodContract
{
	use CastsEnums;
	
    protected $guarded = ['id', 'transaction_count', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'configuration' => 'json'
    ];

	protected $enums = [
		'location' => PaymentMethodsLocations::class,
	];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (null === $model->configuration) {
                $model->configuration = [];
            }
        });
    }

	public function image()
	{
		return $this->hasMany(Media::class, 'id', 'image_default');
	}

	public function imageSrc()
	{
		$image = $this->image;
		$service = $this->getConfigurationValue('SERVICE') !== null ? strtolower($this->getConfigurationValue('SERVICE')) : null;

		if (isset($service)) {
			if (isset($image[0]->file)) {
				return asset('storage/uploads/uploads_360_360/' . $image[0]->file);
			} else if (File::exists('themes/' . Themes::getActiveTheme() . '/images/' . $service . '.jpg')) {
				return asset('themes/' . Themes::getActiveTheme() . '/images/' . $service . '.jpg');
			} else if (File::exists('themes/' . Themes::getActiveTheme() . '/images/' . $service . '.png')) {
				return asset('themes/' . Themes::getActiveTheme() . '/images/' . $service . '.png');
			} else if (File::exists('themes/' . Themes::getActiveTheme() . '/images/' . $service . '.svg')) {
				return asset('themes/' . Themes::getActiveTheme() . '/images/' . $service . '.svg');
			} else if (File::exists('images/' . $service . '.jpg')) {
				return mix('images/' . $service . '.jpg');
			} else if (File::exists('images/' . $service . '.png')) {
				return mix('images/' . $service . '.png');
			} else if (File::exists('images/' . $service . '.svg')) {
				return mix('images/' . $service . '.svg');
			} else if ($service == "cartao_cliente") {
				return mix('images/' . 'icon-farmacool' . '.png');
			}
		}

		return '';
	}

    public function getTimeout(): int
    {
        if (!is_array($this->configuration)) {
            return PaymentMethodContract::DEFAULT_TIMEOUT;
        }

        return Arr::get($this->configuration, 'timeout', PaymentMethodContract::DEFAULT_TIMEOUT);
    }

    public function getGateway(): PaymentGateway
    {
        if (null === $this->gateway) {
            return new NullGateway();
        }

        return PaymentGateways::make($this->gateway, ['paymentMethod' => $this]);
    }

    public function getConfiguration(): array
    {
        return $this->configuration ?? [];
    }

	public function getConfigurationValue(string $key = null)
	{
		return Arr::get($this->configuration, $key);
	}

    public function isEnabled(): bool
    {
        return (bool) $this->is_enabled;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function isCardPayment(): bool
    {
        if(isset($this->configuration['SERVICE']) && $this->configuration['SERVICE'] == "cartao_cliente"){
            return true;
        }

        return false;
    }

    public function scopeActives(Builder $query)
    {
        return $query->where('is_enabled', true)->where('configuration', 'not like', "%cartao_cliente%");
    }

    public function scopeCard(Builder $query){
        return $query->where('is_enabled', true)->where('configuration', 'like', "%cartao_cliente%");
    }

    public function scopeInActives(Builder $query)
    {
        return $query->where('is_enabled', false);
    }

    public function getGatewayName(): string
    {
        if (null === $this->gateway) {
            return NullGateway::getName();
        }

        $gwClass = PaymentGateways::getClass($this->gateway);

        return $gwClass::getName();
    }

	public function incrementTransactionNr()
	{
		$this->transaction_count += 1;
		$this->save();
	}

	public function scopePrescription(Builder $query)
	{
		return $query->whereIn('location', PaymentMethodsLocations::prescriptionLocations());
	}

	public function scopeCheckout(Builder $query)
	{
		return $query->whereIn('location', PaymentMethodsLocations::checkoutLocations());
	}
}
