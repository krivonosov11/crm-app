<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Fetches the current weather forecast for a specified location.')]
class CurrentWeatherTool extends Tool
{
    public function handle(Request $request): Response
    {
        $location = $request->get('location');

        $validated = $request->validate([
            'location' => 'required|string|max:100',
            'units' => 'in:celsius,fahrenheit',
        ], [
            'location.required' => 'You must specify a location to get the weather for. For example, "New York City" or "Tokyo".',
            'units.in' => 'You must specify either "celsius" or "fahrenheit" for the units.',
        ]);

        return Response::text('Weather Summary: Sunny, 72°F');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()
                ->description('The location to get the weather for.')
                ->required(),

            'units' => $schema->string()
                ->enum(['celsius', 'fahrenheit'])
                ->description('The temperature units to use.')
                ->default('celsius'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'temperature' => $schema->number()->description('Temperature in Celsius')->required(),
            'conditions' => $schema->string()->description('Weather conditions')->required(),
            'humidity' => $schema->integer()->description('Humidity percentage')->required(),
        ];
    }
}
