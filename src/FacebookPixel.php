<?php

namespace Bkfdev\FacebookPixel;

use Exception;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\EventResponse;
use FacebookAds\Object\ServerSide\UserData;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Traits\Macroable;

class FacebookPixel
{
    use Macroable;

    private bool $enabled;

    private string $pixelId;

    private array $pixelIds;

    private ?string $token;

    private string $sessionKey;

    private ?string $testEventCode;

    private EventLayer $eventLayer;

    private EventLayer $customEventLayer;

    private EventLayer $flashEventLayer;

    private UserData $userData;

    public function __construct(array $data = [])
    {
        if (count($data)) {
            $this->enabled = $data['enabled'];
            $this->pixelId = $data['facebook_pixel_id'];
            $this->pixelIds = $data['facebook_pixel_ids'];
            $this->token = $data['token'];
            $this->sessionKey = $data['sessionKey'];
            $this->testEventCode = $data['test_event_code'];
        } else {
            $this->enabled = config('facebook-pixel.enabled');
            $this->pixelId = config('facebook-pixel.facebook_pixel_id');
            $this->pixelIds = config('facebook-pixel.facebook_pixel_ids');
            $this->token = config('facebook-pixel.token');
            $this->sessionKey = config('facebook-pixel.sessionKey');
            $this->testEventCode = config('facebook-pixel.test_event_code');
        }
        $this->eventLayer = new EventLayer();
        $this->customEventLayer = new EventLayer();
        $this->flashEventLayer = new EventLayer();
        $this->userData = new UserData();
    }

    public function pixelId(): string
    {
        return (string) $this->pixelId;
    }

    public function pixelIds(): array
    {
        return (array) $this->pixelIds;
    }

    public function sessionKey()
    {
        return $this->sessionKey;
    }

    public function token()
    {
        return $this->token;
    }

    public function testEnabled(): bool
    {
        return (bool) $this->testEventCode;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function setPixelId(int|string $id): void
    {
        $this->pixelId = (string) $id;
    }

    public function setPixelIds(array $ids): void
    {
        $this->pixelIds = $ids;
    }

    /**
     * Add event to the event layer.
     */
    public function track(string $eventName, array $parameters = [], ?string $eventId = null): void
    {
        $this->eventLayer->set($eventName, $parameters, $eventId);
    }

    /**
     * Add custom event to the event layer.
     */
    public function trackCustom(string $eventName, array $parameters = [], ?string $eventId = null): void
    {
        $this->customEventLayer->set($eventName, $parameters, $eventId);
    }

    /**
     * Add event data to the event layer for the next request.
     */
    public function flashEvent(string $eventName, array $parameters = [], ?string $eventId = null): void
    {
        $this->flashEventLayer->set($eventName, $parameters, $eventId);
    }

    public function userData(): UserData
    {
        if ($user = $this->getUser()) {
            return $this->userData
                ->setEmail($user['em'])
                ->setExternalId($user['external_id'])
                ->setClientIpAddress(Request::ip())
                ->setClientUserAgent(Request::userAgent())
                ->setFbc(Arr::get($_COOKIE, '_fbc'))
                ->setFbp(Arr::get($_COOKIE, '_fbp'));
        }

        return $this->userData
            ->setClientIpAddress(Request::ip())
            ->setClientUserAgent(Request::userAgent())
            ->setFbc(Arr::get($_COOKIE, '_fbc'))
            ->setFbp(Arr::get($_COOKIE, '_fbp'));
    }

    /**
     * Send request using Conversions API
     */
    public function send(string $eventName, string $eventID, CustomData $customData, ?UserData $userData = null): ?EventResponse
    {
        if (! $this->isEnabled()) {
            return null;
        }
        if (empty($this->token())) {
            throw new Exception('You need to set a token in your .env file to use the Conversions API.');
        }

        $api = Api::init(null, null, $this->token);
        $api->setLogger(new CurlLogger());

        $event = (new Event())
            ->setEventName($eventName)
            ->setEventTime(time())
            ->setEventId($eventID)
            ->setEventSourceUrl(URL::current())
            ->setUserData($userData ?? $this->userData())
            ->setCustomData($customData)
            ->setActionSource(ActionSource::WEBSITE);

        $request = (new EventRequest($this->pixelId()))->setEvents([$event]);

        if ($this->testEnabled()) {
            $request->setTestEventCode($this->testEventCode);
        }

        try {
            return $request->execute();
        } catch (Exception $e) {
            Log::error($e);
        }

        return null;
    }

    /**
     * Merge array data with the event layer.
     */
    public function merge(array $eventSession): void
    {
        $this->eventLayer->merge($eventSession);
    }

    /**
     * Retrieve the event layer.
     */
    public function getEventLayer(): EventLayer
    {
        return $this->eventLayer;
    }

    /**
     * Retrieve custom event layer.
     */
    public function getCustomEventLayer(): EventLayer
    {
        return $this->customEventLayer;
    }

    /**
     * Retrieve the event layer's data for the next request.
     */
    public function getFlashedEvent(): array
    {
        return $this->flashEventLayer->toArray();
    }

    /**
     * Retrieve the email to use it advanced matching.
     * To use advanced matching we will get the email if the user is authenticated
     */
    public function getUser(): ?array
    {
        if (Auth::check()) {
            return [
                'em' => strtolower(Auth::user()->email),
                'external_id' => Auth::user()->id,
            ];
        }

        return null;
    }

    public function clear(): void
    {
        $this->eventLayer = new EventLayer();
    }
}
