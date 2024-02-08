<?php

namespace SoipoServices\Replicate;

use SoipoServices\Replicate\Data\PredictionData;
use SoipoServices\Replicate\Data\PredictionsData;
use SoipoServices\Replicate\Requests\GetPrediction;
use SoipoServices\Replicate\Requests\GetPredictions;
use SoipoServices\Replicate\Requests\PostPrediction;
use Exception;

class PredictionsResource extends Resource
{
    protected ?string $webhookUrl = null;

    /**
     * @var array<string>
     */
    protected ?array $webhookEvents;

    public function list(?string $cursor = null): PredictionsData
    {
        $request = new GetPredictions();

        if ($cursor) {
            $request->query()->add('cursor', $cursor);
        }

        $response = $this->connector->send($request);
        $data = $response->dtoOrFail();
        if (! $data instanceof PredictionsData) {
            throw new Exception('Unexpected data type');
        }

        return $data;
    }

    public function get(string $id): PredictionData
    {
        $request = new GetPrediction($id);
        $response = $this->connector->send($request);

        $data = $response->dtoOrFail();
        if (! $data instanceof PredictionData) {
            throw new Exception('Unexpected data type');
        }

        return $data;
    }

    /**
     * @param  array<string, float|int|string|null>  $input
     *
     * @throws Exception
     */
    public function create(string $version, array $input): PredictionData
    {
        $request = new PostPrediction($version, $input);
        if ($this->webhookUrl) {
            // https://replicate.com/changelog/2023-02-10-improved-webhook-events-and-event-filtering
            $request->body()->merge([
                'webhook' => $this->webhookUrl,
                'webhook_events_filter' => $this->webhookEvents,
            ]);
        }

        $response = $this->connector->send($request);

        $data = $response->dtoOrFail();
        if (! $data instanceof PredictionData) {
            throw new Exception('Unexpected data type');
        }

        return $data;
    }

    /**
     * @param  array<string>  $events
     */
    public function withWebhook(string $url, ?array $events = ['completed']): self
    {
        $this->webhookUrl = $url;
        $this->webhookEvents = $events;

        return $this;
    }
}
