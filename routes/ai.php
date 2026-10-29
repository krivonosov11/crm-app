<?php

use App\Mcp\Servers\CandidatesServer;
use App\Mcp\Servers\WeatherServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/weather', WeatherServer::class);

Mcp::local('candidates', CandidatesServer::class);
