<?php

namespace Yoco\Cron;

interface JobInterface {

	public function process( string $mode): void;
}
